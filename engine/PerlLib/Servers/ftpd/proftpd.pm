=head1 NAME

 Servers::ftpd::proftpd - i-MSCP Proftpd Server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by internet Multi Server Control Panel
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
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeFtpdPreinstall');
	$rs ||= $self->stop();
	$rs ||= $self->{'eventManager'}->trigger('afterFtpdPreinstall');
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeFtpdInstall', 'proftpd');

	require Servers::ftpd::proftpd::installer;
	$rs ||= Servers::ftpd::proftpd::installer->getInstance()->install();
	$rs ||=$self->{'eventManager'}->trigger('afterFtpdInstall', 'proftpd');
}

=item postinstall()

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeFtpdPostInstall', 'proftpd');
	return $rs if $rs;

	local $@;
	eval { iMSCP::Service->getInstance()->enable($self->{'config'}->{'FTPD_SNAME'}); };
	if($@) {
		error($@);
		return 1;
	}

	$self->{'eventManager'}->register(
		'beforeSetupRestartServices', sub { push @{$_[0]}, [ sub { $self->start(); }, 'ProFTPD' ]; 0; }
	);

	$self->{'eventManager'}->trigger('afterFtpdPostInstall', 'proftpd');
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeFtpdUninstall', 'proftpd');

	require Servers::ftpd::proftpd::uninstaller;
	$rs ||= Servers::ftpd::proftpd::uninstaller->getInstance()->uninstall();
	$self->{'restart'} = 1 unless $rs;
	$rs ||= $self->{'eventManager'}->trigger('afterFtpdUninstall', 'proftpd');
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

	my $rdata = $db->doQuery(
		'u',
		'UPDATE ftp_users SET uid = ?, gid = ? WHERE admin_id = ?',
		$data->{'USER_SYS_UID'},
		$data->{'USER_SYS_GID'},
		$data->{'USER_ID'}
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	$rdata = $db->doQuery(
		'u', 'UPDATE ftp_group SET gid = ? WHERE groupname = ?', $data->{'USER_SYS_GID'}, $data->{'USERNAME'}
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
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeFtpdStart');
	return $rs if $rs;

	local $@;
	eval { iMSCP::Service->getInstance()->start($self->{'config'}->{'FTPD_SNAME'}); };
	if($@) {
		error($@);
		return 1;
	}

	$self->{'eventManager'}->trigger('afterFtpdStart');
}

=item stop()

 Stop Proftpd

 Return int 0, other on failure

=cut

sub stop
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeFtpdStop');
	return $rs if $rs;

	local $@;
	eval { iMSCP::Service->getInstance()->stop($self->{'config'}->{'FTPD_SNAME'}); };
	if($@) {
		error($@);
		return 1;
	}

	$self->{'eventManager'}->trigger('afterFtpdStop');
}

=item restart()

 Restart Proftpd

 Return int 0, other on failure

=cut

sub restart
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeFtpdRestart');
	return $rs if $rs;

	local $@;
	eval {
		my $serviceMngr = iMSCP::Service->getInstance();

		# Mitigate restart problems by waiting a bit before start
		# For instance on Ubuntu Trusty, ProFTPD stay in not running state when using restart command
		$serviceMngr->stop($self->{'config'}->{'FTPD_SNAME'});

		# Give ProFTPD sufficient time for stopping
		sleep 2;

		$serviceMngr->start($self->{'config'}->{'FTPD_SNAME'});
	};
	if($@) {
		error($@);
		return 1;
	}

	$self->{'eventManager'}->trigger('afterFtpdRestart');
}

=item getTraffic()

 Get ftpd traffic data

 Return hash Traffic data or die on failure

=cut

sub getTraffic
{
	my $self = shift;

	require File::Temp;

	my $trafficDbPath = "$main::imscpConfig{'VARIABLE_DATA_DIR'}/ftp_traffic.db";

	# Load traffic database
	tie my %trafficDb, 'iMSCP::Config', fileName => $trafficDbPath, nowarn => 1;

	# Data source file
	my $trafficDataSrc = "$main::imscpConfig{'TRAFF_LOG_DIR'}/$self->{'config'}->{'FTP_TRAFF_LOG_PATH'}";

	if(-f $trafficDataSrc && -s _) {
		my $tpmFile = File::Temp->new();

		# Create a snapshot of log file to process
		my $rs = iMSCP::File->new( filename => $trafficDataSrc)->moveFile($tpmFile);
		die(iMSCP::Debug::getLastError()) if $rs;

		# Read and parse file (line by line)
		$trafficDb{$2} += $1 while(<$tpmFile> =~ /^(\d+)\s+[^\@]+\@(.*)$/gmo);
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
	my $self = shift;

	$self->{'start'} = 0;
	$self->{'restart'} = 0;
	$self->{'eventManager'} = iMSCP::EventManager->getInstance();
	$self->{'eventManager'}->trigger('beforeFtpdInit', $self, 'proftpd') and fatal('proftpd - beforeFtpdInit has failed');
	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/proftpd";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";
	$self->{'commentChar'} = '#';
	$self->{'config'} = lazy { tie my %c, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/proftpd.data"; \%c; };
	$self->{'eventManager'}->trigger('afterFtpdInit', $self, 'proftpd') and fatal('proftpd - afterFtpdInit has failed');
	$self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
