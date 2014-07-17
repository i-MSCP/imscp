#!/usr/bin/perl

=head1 NAME

Package::AntiRootkits::Chkrootkit::Installer - i-MSCP Chkrootkit package installer

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
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Package::AntiRootkits::Chkrootkit::Installer;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::File;
use iMSCP::Execute;
use iMSCP::Rights;
use Servers::cron;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Chkrootkit package installer

=head1 PUBLIC METHODS

=over 4

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	$_[0]->_disableDebianConfig();
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = $_[0];

	my $rs = $self->_addCronTask();
	return $rs if $rs;

	$self->_scheduleCheck();
}

=item setEnginePermissions

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions()
{
	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $imscpGName = $main::imscpConfig{'IMSCP_GROUP'};

	setRights($main::imscpConfig{'CHKROOTKIT_LOG'}, { 'user' => $rootUName, 'group' => $imscpGName, mode => '0640' });
}

=back

=head1 PRIVATE METHODS

=over 4

=item _disableDebianConfig()

 Disable default configuration as provided by the chkrootkit Debian package

 Return int 0 on success, 1 on failure

=cut

sub _disableDebianConfig
{
	my $rs = 0;

	# Disable daily cron tasks
	$rs = iMSCP::File->new(
		'filename' => '/etc/cron.daily/chkrootkit'
	)->moveFile(
		'/etc/cron.daily/chkrootkit.disabled'
	) if -f '/etc/cron.daily/chkrootkit';

	$rs;
}

=item _addCronTask()

 Add cron task

 Return int 0 on success, 1 on failure

=cut

sub _addCronTask
{
	Servers::cron->factory()->addTask(
		{
			TASKID => 'Package::AntiRootkits::Chkrootkit',
			MINUTE => '@weekly',
			HOUR => '',
			DAY => '',
			MONTH => '',
			DWEEK => '',
			USER => $main::imscpConfig{'ROOT_USER'},
			COMMAND => "$main::imscpConfig{'CMD_NICE'} -n 19 $main::imscpConfig{'CMD_CHKROOTKIT'} " .
				"1> $main::imscpConfig{'CHKROOTKIT_LOG'} 2>&1"
		}
	);
}

=item _scheduleCheck()

 Schedule check if log file doesn't exist or is empty

 Return int 0 on success, other on failure

=cut

sub _scheduleCheck
{
	if(! -f -s $main::imscpConfig{'CHKROOTKIT_LOG'}) {
		# Create a dummy file to avoid planning multiple check if installer is run many time
		my $file = iMSCP::File->new('filename' => $main::imscpConfig{'CHKROOTKIT_LOG'});

		my $rs = $file->set('Check scheduled...');
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		my ($stdout, $stderr);
		$rs = execute(
			"$main::imscpConfig{'CMD_ECHO'} '$main::imscpConfig{'CMD_NICE'} -n 19 " .
			"$main::imscpConfig{'CMD_CHKROOTKIT'} 1> $main::imscpConfig{'CHKROOTKIT_LOG'} 2>&1' " .
			"| $main::imscpConfig{'CMD_BATCH'}",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		error("Unable to schedule Chkrootkit check") if $rs && ! $stderr;
		return $rs if $rs;
	}

	0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
