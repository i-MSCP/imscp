=head1 NAME

Package::AntiRootkits::Chkrootkit::Installer - i-MSCP Chkrootkit package installer

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by Laurent Declercq <l.declercq@nuxwin.com>
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

 Chkrootkit package installer.

=head1 PUBLIC METHODS

=over 4

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	(shift)->_disableDebianConfig();
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	my $rs = $self->_addCronTask();
	return $rs if $rs;

	$self->_scheduleCheck();
}

=item setEnginePermissions

 Set engine permissions

 Return int 0 on success, die on failure

=cut

sub setEnginePermissions()
{
	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $imscpGName = $main::imscpConfig{'IMSCP_GROUP'};
	setRights($main::imscpConfig{'CHKROOTKIT_LOG'}, { user => $rootUName, group => $imscpGName, mode => '0640' });
}

=back

=head1 PRIVATE METHODS

=over 4

=item _disableDebianConfig()

 Disable default configuration as provided by the chkrootkit Debian package

 Return int 0 on success, other on failure

=cut

sub _disableDebianConfig
{
	if(-f '/etc/cron.daily/chkrootkit') {
		iMSCP::File->new( filename => '/etc/cron.daily/chkrootkit' )->moveFile( '/etc/cron.daily/chkrootkit.disabled' );
	} else {
		0;
	}
}

=item _addCronTask()

 Add cron task

 Return int 0 on success, other on failure

=cut

sub _addCronTask
{
	Servers::cron->factory()->addTask({
		TASKID => 'Package::AntiRootkits::Chkrootkit',
		MINUTE => '@weekly',
		HOUR => '',
		DAY => '',
		MONTH => '',
		DWEEK => '',
		USER => $main::imscpConfig{'ROOT_USER'},
		COMMAND => "nice -n 15 ionice -c2 -n5 bash chkrootkit -e > $main::imscpConfig{'CHKROOTKIT_LOG'} 2>&1"
	});
}

=item _scheduleCheck()

 Schedule check if log file doesn't exist or is empty

 Return int 0 on success, other on failure

=cut

sub _scheduleCheck
{
	unless(-f -s $main::imscpConfig{'CHKROOTKIT_LOG'}) {
		# Create an emtpy file to avoid planning multiple check if installer is run many time
		my $file = iMSCP::File->new( filename => $main::imscpConfig{'CHKROOTKIT_LOG'} );
		$file->set('Check scheduled...');
		$file->save();

		my $rs = execute(
			"echo 'bash chkrootkit -e > $main::imscpConfig{'CHKROOTKIT_LOG'} 2>&1' | batch", \my $stdout, \my $stderr
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
__END__
