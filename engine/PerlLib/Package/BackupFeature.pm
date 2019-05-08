=head1 NAME

 Package::BackupFeature - Backup feature

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

package Package::BackupFeature;

use strict;
use warnings;
use iMSCP::Boolean;
use Servers::cron;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Provides backup feature.

=head1 PUBLIC METHODS

=over 4

=item getPriority( )

 Get package priority

 Return int package priority

=cut

sub getPriority
{
    -20;
}

=item registerSetupListeners( \%events )

 Register setup event listeners

 Param iMSCP::EventManager \%events
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ( $self, $events ) = @_;

    $events->registerOne( 'beforeSetupDialog', sub {
        push @{ $_[0] },
            sub { $self->_dialogForCpBackup( @_ ); },
            sub { $self->_dialogForClientBackup( @_ ); };
        0;
    } );
}

=item install( )

 Installation tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my $cron = Servers::cron->factory();

    if ( $::imscpConfig{'BACKUP_IMSCP'} eq 'yes' ) {
        # Cron task for the control panel (conffiles and database)
        my $rs = $cron->addTask( {
            TASKID  => __PACKAGE__ . '::ControlPanel',
            MINUTE  => '@daily',
            COMMAND => "/usr/bin/perl $::imscpConfig{'BACKUP_ROOT_DIR'}/imscp-backup-imscp > "
                . "$::imscpConfig{'LOG_DIR'}/imscp-backup-imscp.log 2>&1"
        } );
        # Cron task for deletion of outdated control panel backups
        $rs ||= $cron->addTask( {
            TASKID  => __PACKAGE__ . '::ControlPanel::Gc',
            MINUTE  => '@weekly',
            COMMAND => "/usr/bin/find $::imscpConfig{'BACKUP_FILE_DIR'} "
                . "-type f -mtime +7 -exec rm -- {} +"
        } );
        return $rs if $rs;
    }

    if ( $::imscpConfig{'BACKUP_DOMAINS'} eq 'yes' ) {
        # Cron task for client backups
        my $rs = $cron->addTask( {
            TASKID  => __PACKAGE__ . '::Clients',
            MINUTE  => $::imscpConfig{'BACKUP_MINUTE'},
            HOUR    => $::imscpConfig{'BACKUP_HOUR'},
            COMMAND => "/usr/bin/nice -n 10 /usr/bin/ionice -c2 -n5 "
                . "/usr/bin/perl $::imscpConfig{'BACKUP_ROOT_DIR'}/imscp-backup-all > "
                . "$::imscpConfig{'LOG_DIR'}/imscp-backup-all.log 2>&1"
        } );
        return $rs if $rs;
    }

    # Cron task for deletion of outdated server backup files
    $cron->addTask( {
        TASKID  => __PACKAGE__ . '::Servers::Gc',
        MINUTE  => '@weekly',
        COMMAND => "/usr/bin/find $::imscpConfig{'CONF_DIR'}/*/backup -type f "
            . "-mtime +7 -regextype sed -regex '.*/.*[0-9]\{10\}\$' -exec rm -- {} +"
    } );
}

=item uninstall( )

 Uninstallation tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my $cron = Servers::cron->factory();

    for my $taskID ( qw/ ControlPanel ControlPanel::Gc Clients Servers::Gc / ) {
        my $rs = $cron->deleteTask( { TASKID => __PACKAGE__ . "::${taskID}" } );
        return $rs if $rs;
    }

    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _dialogForCpBackup( \%dialog )

 Dialog for control panel backup

 Param iMSCP::Dialog \%dialog
 Return int 0 (Next), 20 (Skip), 30 (Back)

=cut

sub _dialogForCpBackup
{
    my ( undef, $dialog ) = @_;

    my $value = ::setupGetQuestion( 'BACKUP_IMSCP' );

    if ( !grep ( $_ eq $::reconfigure, qw/ backup all / )
        && grep ( $_ eq $value, qw/ yes no / )
    ) {
        return 20;
    }

    my $ret = $dialog->boolean( <<'EOF', $value eq 'no', TRUE );
Do you want to enable the daily backup feature for the control panel (database and configuration files)?
EOF
    return 30 if $ret == 30;

    ::setupSetQuestion( 'BACKUP_IMSCP', $ret ? 'no' : 'yes' );
    0;
}

=item _dialogForClientBackup( \%dialog )

 Dialog for clent backup

 Param iMSCP::Dialog \%dialog
 Return int 0 (Next), 20 (Skip), 30 (Back)

=cut

sub _dialogForClientBackup
{
    my ( undef, $dialog ) = @_;

    my $value = ::setupGetQuestion( 'BACKUP_DOMAINS' );

    if ( !grep ( $_ eq $::reconfigure, qw/ backup all / )
        && grep ( $_ eq $value, qw/ yes no / )
    ) {
        return 20;
    }

    my $ret = $dialog->boolean( <<'EOF', $value eq 'no' );
Do you want to enable the backup feature for the clients?

When this feature is enabled, resellers can enable backup feature for their clients.
EOF
    return 30 if $ret == 30;

    ::setupSetQuestion( 'BACKUP_DOMAINS', $ret = 0 );
    0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
