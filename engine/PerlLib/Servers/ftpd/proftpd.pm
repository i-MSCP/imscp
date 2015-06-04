=head1 NAME

 Servers::ftpd::proftpd - i-MSCP Proftpd Server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by internet Multi Server Control Panel
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

package Servers::ftpd::proftpd;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Config;
use iMSCP::EventManager;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Service;
use File::Basename;
use Scalar::Defer;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Proftpd Server implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners(\%eventManager)

 Register setup event listeners

 Param iMSCP::EventManager \%eventManager
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
	my ($self, $eventManager) = @_;

	require Servers::ftpd::proftpd::installer;
	Servers::ftpd::proftpd::installer->getInstance()->registerSetupListeners($eventManager);
}

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeFtpdPreinstall');
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterFtpdPreinstall');
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeFtpdInstall', 'proftpd');
	return $rs if $rs;

	require Servers::ftpd::proftpd::installer;
	$rs = Servers::ftpd::proftpd::installer->getInstance()->install();
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterFtpdInstall', 'proftpd');
}

=item postinstall()

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeFtpdPostInstall', 'proftpd');
	return $rs if $rs;

	iMSCP::Service->getInstance()->enable($self->{'config'}->{'FTPD_SNAME'});

	$self->{'eventManager'}->register(
		'beforeSetupRestartServices', sub { push @{$_[0]}, [ sub { $self->restart(); }, 'Proftpd' ]; 0; }
	);

	$self->{'eventManager'}->trigger('afterFtpdPostInstall', 'proftpd');
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeFtpdUninstall', 'proftpd');
	return $rs if $rs;

	require Servers::ftpd::proftpd::uninstaller;
	$rs = Servers::ftpd::proftpd::uninstaller->getInstance()->uninstall();
	return $rs if $rs;

	$self->{'restart'} = 1;

	$self->{'eventManager'}->trigger('afterFtpdUninstall', 'proftpd');
}

=item addUser(\%data)

 Process addUser tasks

 Param hash \%data User data
 Return int 0 on success, other on failure

=cut

sub addUser
{
	my ($self, $data) = @_;

	my $rs = $self->{'eventManager'}->trigger('beforeFtpdAddUser', $data);
	return $rs if $rs;

	my $db = iMSCP::Database->factory();

	# Updating ftp_users.uid and ftp_users.gid columns
	my $rdata = $db->doQuery(
		'dummy',
		'UPDATE `ftp_users` SET `uid` = ?, `gid` = ? WHERE `admin_id` = ?',
		$data->{'USER_SYS_UID'},
		$data->{'USER_SYS_GID'},
		$data->{'USER_ID'}
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	# Updating ftp_group.gid column
	$rdata = $db->doQuery(
		'dummy',
		'UPDATE `ftp_group` SET `gid` = ? WHERE `groupname` = ?',
		$data->{'USER_SYS_GID'},
		$data->{'USERNAME'}
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	$self->{'eventManager'}->trigger('AfterFtpdAddUser', $data);
}

=item start()

 Start Proftpd

 Return int 0, other on failure

=cut

sub start
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeFtpdStart');
	return $rs if $rs;

	iMSCP::Service->getInstance()->start($self->{'config'}->{'FTPD_SNAME'});

	$self->{'eventManager'}->trigger('afterFtpdStart');
}

=item stop()

 Stop Proftpd

 Return int 0, other on failure

=cut

sub stop
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeFtpdStop');
	return $rs if $rs;

	iMSCP::Service->getInstance()->stop($self->{'config'}->{'FTPD_SNAME'});

	$self->{'eventManager'}->trigger('afterFtpdStop');
}

=item restart()

 Restart Proftpd

 Return int 0, other on failure

=cut

sub restart
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeFtpdRestart');
	return $rs if $rs;

	iMSCP::Service->getInstance()->restart($self->{'config'}->{'FTPD_SNAME'});

	$self->{'eventManager'}->trigger('afterFtpdRestart');
}

=item getTraffic()

 Get ftpd traffic data

 Return hash Traffic data or die on failure

=cut

sub getTraffic
{
	my $self = $_[0];

	my $trafficDbPath = "$main::imscpConfig{'VARIABLE_DATA_DIR'}/ftp_traffic.db";

	# Load traffic database
	tie my %trafficDb, 'iMSCP::Config', fileName => $trafficDbPath, nowarn => 1;

	# Data source file
	my $trafficDataSrc = "$main::imscpConfig{'TRAFF_LOG_DIR'}/$self->{'config'}->{'FTP_TRAFF_LOG_PATH'}";

	if(-f $trafficDataSrc && -s _) {
		my $wrkLogFile = "$main::imscpConfig{'LOG_DIR'}/" . basename($trafficDataSrc);

		# Creating working file from current state of data source
		my $rs = iMSCP::File->new( filename => $trafficDataSrc)->moveFile($wrkLogFile);
		die(iMSCP::Debug::getLastError()) if $rs;

		# Read and parse file (line by line)
		open my $file, '<', $wrkLogFile or die("Unable to open $wrkLogFile: $!");
		$trafficDb{$2} += $1 while(<$file> =~ /^(\d+)\s+[^\@]+\@(.*)$/gmo);
		close $file;
	}

	# Schedule deletion of full traffic database. This is only done on success. On failure, the traffic database is kept
	# in place for later processing. In such case, data already processed are zeroed by the traffic processor script.
	$self->{'eventManager'}->register(
		'afterVrlTraffic', sub { (-f $trafficDbPath) ? iMSCP::File->new( filename => $trafficDbPath )->delFile() : 0; }
	);

	\%trafficDb;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Servers::ftpd::proftpd

=cut

sub _init
{
	my $self = $_[0];

	$self->{'start'} = 0;
	$self->{'restart'} = 0;

	$self->{'eventManager'} = iMSCP::EventManager->getInstance();

	$self->{'eventManager'}->trigger(
		'beforeFtpdInit', $self, 'proftpd'
	) and fatal('proftpd - beforeFtpdInit has failed');

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/proftpd";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	$self->{'commentChar'} = '#';

	$self->{'config'} = lazy { tie my %c, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/proftpd.data"; \%c; };

	$self->{'eventManager'}->trigger(
		'afterFtpdInit', $self, 'proftpd'
	) and fatal('proftpd - afterFtpdInit has failed');

	$self;
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
