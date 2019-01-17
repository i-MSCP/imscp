=head1 NAME

 Package::Backup - i-MSCP backup

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

package Package::Backup;

use strict;
use warnings;
use iMSCP::Getopt;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP backup.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( \%em )

 Register setup event listeners

 Param iMSCP::EventManager \%em
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ( $self, $em ) = @_;

    $em->register( 'beforeSetupDialog', sub {
        push @{ $_[0] },
            sub { $self->imscpBackupDialog( @_ ) },
            sub { $self->customerBackupDialog( @_ ) };
        0;
    } );
}

=item imscpBackupDialog( \%dialog )

 Ask for i-MSCP backup

 Param iMSCP::Dialog \%dialog
 Return int 0 or 30

=cut

sub imscpBackupDialog
{
    my ( undef, $dialog ) = @_;

    my $backupImscp = ::setupGetQuestion( 'BACKUP_CP' );

    if ( iMSCP::Getopt->reconfigure =~ /^(?:backup|all|forced)$/ || $backupImscp !~ /^(?:yes|no)$/ ) {
        ( my $rs, $backupImscp ) = $dialog->radiolist( <<"EOF", [ 'yes', 'no' ], $backupImscp ne 'no' ? 'yes' : 'no' );

\\Z4\\Zb\\Zui-MSCP Backup Feature\\Zn

Do you want to activate the backup feature for i-MSCP?

The backup feature for i-MSCP allows the daily save of all i-MSCP configuration files and its database. It's greatly recommended to activate this feature.
EOF
        return $rs if $rs >= 30;
    }

    ::setupSetQuestion( 'BACKUP_CP', $backupImscp );
    0;
}

=item customerBackupDialog( \%dialog )

 Ask for customer backup

 Param iMSCP::Dialog \%dialog
 Return int 0 or 30

=cut

sub customerBackupDialog
{
    my ( undef, $dialog ) = @_;

    my $backupDomains = ::setupGetQuestion( 'BACKUP_CLIENTS' );

    if ( iMSCP::Getopt->reconfigure =~ /^(?:backup|all|forced)$/ || $backupDomains !~ /^(?:yes|no)$/ ) {
        ( my $rs, $backupDomains ) = $dialog->radiolist( <<"EOF", [ 'yes', 'no' ], $backupDomains ne 'no' ? 'yes' : 'no' );

\\Z4\\Zb\\ZuDomains Backup Feature\\Zn

Do you want to activate the backup feature for the clients?

This feature allows resellers to enable backup for their customers such as:

 - Full (domains and SQL databases)
 - Domains only (Web files)
 - SQL databases only
 - None (no backup)
EOF
        return $rs if $rs >= 30;
    }

    ::setupSetQuestion( 'BACKUP_CLIENTS', $backupDomains );
    0;
}

=item getPriority( )

 Get package priority

 Return int package priority

=cut

sub getPriority
{
    0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
