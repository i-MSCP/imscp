=head1 NAME

 Servers::ftpd::vsftpd - i-MSCP VsFTPd Server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2015-2016 by Laurent Declercq <l.declercq@nuxwin.com>
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

package Servers::ftpd::vsftpd;

use strict;
use warnings;
use iMSCP::Config;
use iMSCP::Debug;
use iMSCP::EventManager;
use iMSCP::File;
use iMSCP::Service;
use iMSCP::TemplateParser;
use File::Basename;
use Scalar::Defer;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP VsFTPd Server implementation.

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

	require Servers::ftpd::vsftpd::installer;
	Servers::ftpd::vsftpd::installer->getInstance()->registerSetupListeners($eventManager);
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

	my $rs = $self->{'eventManager'}->trigger('beforeFtpdInstall', 'vsftpd');
	require Servers::ftpd::vsftpd::installer;
	$rs ||= Servers::ftpd::vsftpd::installer->getInstance()->install();
	$rs ||= $self->{'eventManager'}->trigger('afterFtpdInstall', 'vsftpd');
}

=item postinstall()

 Process postinstall tasks

 Return int 0 on success, die on failure

=cut

sub postinstall
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeFtpdPostInstall', 'vsftpd');

	local $@;
	eval { iMSCP::Service->getInstance()->enable($self->{'config'}->{'FTPD_SNAME'}); };
	if($@) {
		error($@);
		return 1;
	}

	$rs = $self->{'eventManager'}->register('beforeSetupRestartServices', sub {
		push @{$_[0]}, [ sub { $self->start() }, 'VsFTPd server' ]; 0
	});
	$rs ||= $self->{'eventManager'}->trigger('afterFtpdPostInstall', 'vsftpd');
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, die on failure

=cut

sub uninstall
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeFtpdUninstall', 'vsftpd');
	require Servers::ftpd::vsftpd::uninstaller;
	$rs ||= Servers::ftpd::vsftpd::uninstaller->getInstance()->uninstall();
	$rs || ($self->{'restart'} = 1);
	$rs ||= $self->{'eventManager'}->trigger('afterFtpdUninstall', 'vsftpd');
}

=item addUser(\%data)

 Process addUser tasks

 Param hash \%data user data as provided by Modules::FtpUser module
 Return int 0 on success, other on failure

=cut

sub addUser
{
	my ($self, $data) = @_;

	$self->{'eventManager'}->trigger('beforeFtpdAddUser', $data);

	my $db = iMSCP::Database->factory();
	my $ret = $db->doQuery(
		'u', 'UPDATE ftp_users SET uid = ?, gid = ? WHERE admin_id = ?',
		$data->{'USER_SYS_UID'}, $data->{'USER_SYS_GID'}, $data->{'USER_ID'}
	);
	unless(ref $ret eq 'HASH') {
		error($ret);
		return 1;
	}

	$ret = $db->doQuery(
		'u', 'UPDATE ftp_group SET gid = ? WHERE groupname = ?', $data->{'USER_SYS_GID'}, $data->{'USERNAME'}
	);
	unless(ref $ret eq 'HASH') {
		error($ret);
		return 1;
	}

	$self->{'eventManager'}->trigger('AfterFtpdAddUser', $data);
}

=item addFtpUser(\%data)

 Add FTP user

 Param hash \%data Ftp user as provided by Modules::FtpUser module
 Return int 0 on success, other on failure

=cut

sub addFtpUser
{
	my ($self, $data) = @_;

	my $rs = $self->{'eventManager'}->trigger('beforeFtpdAddFtpUser', $data);
	$rs ||= $self->_createFtpUserConffile($data);
	$rs || ($self->{'reload'} = 1);
	$rs ||= $self->{'eventManager'}->trigger('afterFtpdAddFtpUser', $data);
}

=item disableFtpUser(\%data)

 Disable FTP user

 Param hash \%data Ftp user data as provided by Modules::FtpUser module
 Return int 0 on success, other on failure

=cut

sub disableFtpUser
{
	my ($self, $data) = @_;

	my $rs = $self->{'eventManager'}->trigger('beforeFtpdDisableFtpUser', $data);
	$rs ||= $self->_deleteFtpUserConffile($data);
	$rs || ($self->{'reload'} = 1);
	$rs ||= $self->{'eventManager'}->trigger('afterFtpdDisableFtpUser', $data);
}

=item deleteFtpUser(\%data)

 Delete FTP user

 Param hash \%data Ftp user data as provided by Modules::FtpUser module
 Return int 0 on success, other on failure

=cut

sub deleteFtpUser
{
	my ($self, $data) = @_;

	my $rs = $self->{'eventManager'}->trigger('beforeFtpdDeleteFtpUser', $data);
	$rs ||= $self->_deleteFtpUserConffile($data);
	$rs || ($self->{'reload'} = 1);
	$rs ||= $self->{'eventManager'}->trigger('afterFtpdDeleteFtpUser', $data);
}

=item start()

 Start vsftpd

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

 Stop vsftpd

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

 Restart vsftpd

 Return int 0, other on failure

=cut

sub restart
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeFtpdRestart');
	return $rs if $rs;

	local $@;
	eval { iMSCP::Service->getInstance()->restart($self->{'config'}->{'FTPD_SNAME'}); };
	if($@) {
		error($@);
		return 1;
	}

	$self->{'eventManager'}->trigger('afterFtpdRestart');
}

=item reload()

 Reload vsftpd

 Return int 0, other on failure

=cut

sub reload
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeFtpdReload');
	return $rs if $rs;

	local $@;
	eval { iMSCP::Service->getInstance()->reload($self->{'config'}->{'FTPD_SNAME'}); };
	if($@) {
		error($@);
		return 1;
	}

	$self->{'eventManager'}->trigger('afterFtpdReload');
}

=item getTraffic()

 Get VsFTPd traffic data

 Return hash Traffic data, die on failure

=cut

sub getTraffic
{
	my $self = shift;

	my $trafficDbPath = "$main::imscpConfig{'VARIABLE_DATA_DIR'}/ftp_traffic.db";

	# Load traffic database
	tie my %trafficDb, 'iMSCP::Config', fileName => $trafficDbPath, nowarn => 1;

	# Data source file
	my $trafficDataSrc = $self->{'config'}->{'FTPD_TRAFF_LOG_PATH'};

	if(-f $trafficDataSrc && -s _) {
		require File::Temp;
		my $tmpFile = File::Temp->new();

		# Create a snapshot of log file to process
		iMSCP::File->new( filename => $trafficDataSrc)->moveFile($tmpFile) == 0 or die(sprintf(
			'Could not move %s file to %s', $trafficDataSrc, $tmpFile
		));

		# Read and parse file (line by line)
		$trafficDb{$2} += $1 while(<$tmpFile> =~ /^(?:[^\s]+\s){7}([\d]+)\s(?:[^\s]+\s){5}([^\s]+)/gmo);
	}

	# Schedule deletion of full traffic database. This is only done on success. On failure, the traffic database is kept
	# in place for later processing. In such case, data already processed are zeroed by the traffic processor script.
	$self->{'eventManager'}->register('afterVrlTraffic', sub {
		-f $trafficDbPath ? iMSCP::File->new( filename => $trafficDbPath )->delFile() : 0
	});
	\%trafficDb;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Servers::ftpd::vsftpd

=cut

sub _init
{
	my $self = shift;

	$self->{'eventManager'} = iMSCP::EventManager->getInstance();
	$self->{'start'} = 0;
	$self->{'restart'} = 0;
	$self->{'reload'} = 0;
	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/vsftpd";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'config'} = lazy { tie my %c, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/vsftpd.data"; \%c };
	$self;
}

=item _createFtpUserConffile(\%data)

 Create user vsftpd configuration file

 Param hash \%data Ftp user data as provided by Modules::FtpUser module
 Return int 0, other on failure

=cut

sub _createFtpUserConffile
{
	my ($self, $data) = @_;

	my $rs = $self->{'eventManager'}->trigger('onLoadTemplate', 'vsftpd', 'vsftpd_user.conf', \my $cfgTpl, $data);
	return $rs if $rs;

	unless(defined $cfgTpl) {
		$cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/vsftpd_user.conf" )->get();
		unless(defined $cfgTpl) {
			error(sprintf('Could not read %s file', "$self->{'cfgDir'}/vsftpd_user.conf"));
			return 1;
		}
	}

	$rs = $self->{'eventManager'}->trigger('beforeFtpdBuildConf', \$cfgTpl, 'vsftpd_user.conf');
	return $rs if $rs;

	$cfgTpl = process($data, $cfgTpl);

	$rs = $self->{'eventManager'}->trigger('afterFtpdBuildConf', \$cfgTpl, 'vsftpd_user.conf');
	return $rs if $rs;

	my $file = iMSCP::File->new( filename => "$self->{'config'}->{'FTPD_USER_CONF_DIR'}/$data->{'USERNAME'}" );
	$rs = $file->set($cfgTpl);
	$rs ||= $file->save();
}

=item _deleteFtpUserConffile(\%data)

 Delete user vsftpd configuration file

 Param hash \%data Ftp user data as provided by Modules::FtpUser module
 Return int 0, other on failure

=cut

sub _deleteFtpUserConffile
{
	my ($self, $data) = @_;

	return 0 unless -f "$self->{'config'}->{'FTPD_USER_CONF_DIR'}/$data->{'USERNAME'}";

	iMSCP::File->new( filename => "$self->{'config'}->{'FTPD_USER_CONF_DIR'}/$data->{'USERNAME'}" )->delFile();
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
