=head1 NAME

 Package::AntiRootkits::Chkrootkit::Chkrootkit - Chkrootkit package

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Package::AntiRootkits::Chkrootkit::Chkrootkit;

use strict;
use warnings;
use iMSCP::Debug qw/ debug error /;
use iMSCP::Execute 'execute';
use iMSCP::File;
use iMSCP::Rights 'setRights';
use Servers::cron;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 The chkrootkit security scanner searches the local system for signs that it is
 infected with a 'rootkit'. Rootkits are set of programs and hacks designed to
 take control of a target machine by using known security flaws.

=head1 PUBLIC METHODS

=over 4

=item preinstall( )

 Process pre-installation tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ( $self ) = @_;

    $self->_disableDebianConfig();
}

=item postinstall( )

 Process post-installation tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my ( $self ) = @_;

    my $rs = $self->_addCronTask();
    $rs ||= $self->_scheduleCheck();
}

=item uninstall( )

 Process uninstallation tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ( $self ) = @_;

    $self->_restoreDebianConfig();
}

=item setEnginePermissions( )

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    setRights( $::imscpConfig{'CHKROOTKIT_LOG'}, {
        user  => $::imscpConfig{'ROOT_USER'},
        group => $::imscpConfig{'IMSCP_GROUP'},
        mode  => '0640'
    } );
}

=item getDistributionPackages( )

 Get list of distribution packages to install or uninstall, depending on context

 Return List of distribution packages

=cut

sub getDistributionPackages
{
    'chkrootkit';
}

=back

=head1 PRIVATE METHODS

=over 4

=item _disableDebianConfig( )

 Disable default configuration as provided by the chkrootkit Debian package

 Return int 0 on success, other on failure

=cut

sub _disableDebianConfig
{
    return 0 unless -f '/etc/cron.daily/chkrootkit';

    iMSCP::File->new(
        filename => '/etc/cron.daily/chkrootkit'
    )->moveFile(
        '/etc/cron.daily/chkrootkit.disabled'
    );
}

=item _addCronTask( )

 Add cron task

 Return int 0 on success, other on failure

=cut

sub _addCronTask
{
    Servers::cron->factory()->addTask( {
        TASKID  => 'Package::AntiRootkits::Chkrootkit',
        MINUTE  => '@weekly',
        HOUR    => '',
        DAY     => '',
        MONTH   => '',
        DWEEK   => '',
        USER    => $::imscpConfig{'ROOT_USER'},
        COMMAND => "/usr/bin/nice -n 10 /usr/bin/ionice -c2 -n5 /bin/bash /usr/sbin/chkrootkit -e > $::imscpConfig{'CHKROOTKIT_LOG'} 2>&1"
    } );
}

=item _scheduleCheck( )

 Schedule check if log file doesn't exist or is empty

 Return int 0 on success, other on failure

=cut

sub _scheduleCheck
{
    return 0 if -f -s $::imscpConfig{'CHKROOTKIT_LOG'};

    # Create an empty file to avoid planning multiple check if installer is run many time
    my $file = iMSCP::File->new( filename => $::imscpConfig{'CHKROOTKIT_LOG'} );
    $file->set( "Check scheduled...\n" );
    my $rs = $file->save();
    return $rs if $rs;

    $rs = execute(
        "echo '/bin/bash /usr/sbin/chkrootkit -e > $::imscpConfig{'CHKROOTKIT_LOG'} 2>&1' | /usr/bin/at now + 10 minutes",
        \my $stdout,
        \my $stderr
    );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    $rs;
}

=item _restoreDebianConfig( )

 Restore default configuration

 Return int 0 on success, other on failure

=cut

sub _restoreDebianConfig
{
    return 0 unless -f '/etc/cron.daily/chkrootkit.disabled';

    iMSCP::File->new(
        filename => '/etc/cron.daily/chkrootkit.disabled'
    )->moveFile(
        '/etc/cron.daily/chkrootkit'
    );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
