#!/usr/bin/perl

=head1 NAME

 Servers::ftpd::proftpd - i-MSCP Proftpd Server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2014 by internet Multi Server Control Panel
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
#
# @category    i-MSCP
# @copyright   2010-2014 by i-MSCP | http://i-mscp.net
# @author      Daniel Andreca <sci2tech@gmail.com>
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::ftpd::proftpd;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::EventManager;
use iMSCP::Execute;
use iMSCP::File;
use File::Basename;
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

	$rs = $self->stop();
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterFtpdPreinstall');
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	require Servers::ftpd::proftpd::installer;
	Servers::ftpd::proftpd::installer->getInstance()->install();
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

	$self->{'start'} = 'yes';

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

	$rs = $self->{'eventManager'}->trigger('afterFtpdUninstall', 'proftpd');
	return $rs if $rs;

	$self->{'restart'} = 'yes';

	0;
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

 Return int 0 on success, other on failure

=cut

sub start
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeFtpdStart');
	return $rs if $rs;

	my $stdout;
	$rs = execute("$main::imscpConfig{'SERVICE_MNGR'} $self->{'config'}->{'FTPD_SNAME'} start 2>/dev/null", \$stdout);
	debug($stdout) if $stdout;
	error('Unable to start Proftpd') if $rs > 1;
	return $rs if $rs > 1;

	$self->{'eventManager'}->trigger('afterFtpdStart');
}

=item stop()

 Stop Proftpd

 Return int 0 on success, other on failure

=cut

sub stop
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeFtpdStop');
	return $rs if $rs;

	my $stdout;
	$rs = execute("$main::imscpConfig{'SERVICE_MNGR'} $self->{'config'}->{'FTPD_SNAME'} stop 2>/dev/null", \$stdout);
	debug($stdout) if $stdout;
	error('Unable to stop Proftpd') if $rs > 1;
	return $rs if $rs > 1;

	$self->{'eventManager'}->trigger('afterFtpdStop');
}

=item restart()

 Restart Proftpd

 Return int 0 on success, other on failure

=cut

sub restart
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeFtpdRestart');
	return $rs if $rs;

	my $stdout;
	$rs = execute("$main::imscpConfig{'SERVICE_MNGR'} $self->{'config'}->{'FTPD_SNAME'} restart 2>/dev/null", \$stdout);
	debug($stdout) if $stdout;
	error('Unable to restart Proftpd') if $rs > 1;
	return $rs if $rs > 1;

	$self->{'eventManager'}->trigger('afterFtpdRestart');
}

=item getTraffic()

 Get ftp traffic data

 Return hash Traffic data or die on failure

=cut

sub getTraffic
{
	my $self = $_[0];

	my $trafficDbPath = "$main::imscpConfig{'VARIABLE_DATA_DIR'}/ftp_traffic.db";

	# Load traffic database
	tie my %trafficDb, 'iMSCP::Config', 'fileName' => $trafficDbPath, 'nowarn' => 1;

	my $trafficLogFile = "$main::imscpConfig{'TRAFF_LOG_DIR'}/$self->{'config'}->{'FTP_TRAFF_LOG_PATH'}";

	if(-f $trafficLogFile && -s _) {
		my $wrkLogFile = "$main::imscpConfig{'LOG_DIR'}/" . basename($trafficLogFile);

		# Creating working file from current state of upstream data source
		my $rs = iMSCP::File->new('filename' => $trafficLogFile)->moveFile($wrkLogFile);
		die(iMSCP::Debug::getLastError()) if $rs;

		# Getting working file content
		my $wrkLogContent = iMSCP::File->new('filename' => $wrkLogFile)->get();
		die(iMSCP::Debug::getLastError()) unless defined $wrkLogContent;

		# Getting FTP traffic
		$trafficDb{$2} += $1 while($wrkLogContent =~ /^(\d+)\s+[^\@]+\@(.*)$/gmo);
	}

	# Schedule deletion of traffic database. This is only done on success. On failure, the traffic database is kept
	# in place for later processing. In such case, data already processed (put in database) are zeroed by the
	# traffic processor script.
	$self->{'eventManager'}->register(
		'afterVrlTraffic',
		sub {
			if(-f $trafficDbPath) {
				iMSCP::File->new('filename' => $trafficDbPath)->delFile();
			} else {
				0;
			}
		}
	) and die(iMSCP::Debug::getLastError());

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

	$self->{'eventManager'} = iMSCP::EventManager->getInstance();

	$self->{'eventManager'}->trigger(
		'beforeFtpdInit', $self, 'proftpd'
	) and fatal('proftpd - beforeFtpdInit has failed');

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/proftpd";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	$self->{'commentChar'} = '#';

	tie %{$self->{'config'}}, 'iMSCP::Config', 'fileName' => "$self->{'cfgDir'}/proftpd.data";

	$self->{'eventManager'}->trigger(
		'afterFtpdInit', $self, 'proftpd'
	) and fatal('proftpd - afterFtpdInit has failed');

	$self;
}

=item END

 Code triggered at the very end of script execution.

 - Start or restart proftpd if needed
 - Remove old traffic logs file if exists

 Return int Exit code

=cut

END
{
	my $exitCode = $?;
	my $self = Servers::ftpd::proftpd->getInstance();
	my $rs = 0;

	if($self->{'start'} && $self->{'start'} eq 'yes') {
		$rs = $self->start();
	} elsif($self->{'restart'} && $self->{'restart'} eq 'yes') {
		$rs = $self->restart();
	}

	$? = $exitCode || $rs;
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
