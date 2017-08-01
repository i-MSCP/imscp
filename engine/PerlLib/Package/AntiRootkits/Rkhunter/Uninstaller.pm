=head1 NAME

 Package::AntiRootkits::Rkhunter::Uninstaller - i-MSCP Rkhunter Anti-Rootkits package uninstaller

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Package::AntiRootkits::Rkhunter::Uninstaller;

use strict;
use warnings;
use iMSCP::File;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Rkhunter package uninstaller.

=head1 PUBLIC METHODS

=over 4

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    $_[0]->_restoreDebianConfig();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _restoreDebianConfig( )

 Restore default configuration

 Return int 0 on success, other on failure

=cut

sub _restoreDebianConfig
{
    if ( -f '/etc/default/rkhunter' ) {
        my $file = iMSCP::File->new( filename => '/etc/default/rkhunter' );
        my $fileContent = $file->get();
        unless ( defined $fileContent ) {
            error( "Couldn't read /etc/default/rkhunter file" );
            return 1;
        }

        $fileContent =~ s/CRON_DAILY_RUN=".*"/CRON_DAILY_RUN=""/i;
        $fileContent =~ s/CRON_DB_UPDATE=".*"/CRON_DB_UPDATE=""/i;
        $file->set( $fileContent );
        my $rs = $file->save();
        return $rs if $rs;
    }

    if ( -f '/etc/cron.daily/rkhunter.disabled' ) {
        my $rs = iMSCP::File->new( filename => '/etc/cron.daily/rkhunter.disabled' )->moveFile(
            '/etc/cron.daily/rkhunter'
        );
        return $rs if $rs;
    }

    if ( -f '/etc/cron.weekly/rkhunter.disabled' ) {
        my $rs = iMSCP::File->new( filename => '/etc/cron.weekly/rkhunter.disabled' )->moveFile(
            '/etc/cron.weekly/rkhunter'
        );
        return $rs if $rs;
    }

    if ( -f "$main::imscpConfig{'LOGROTATE_CONF_DIR'}/rkhunter.disabled" ) {
        my $rs = iMSCP::File->new( filename => "$main::imscpConfig{'LOGROTATE_CONF_DIR'}/rkhunter.disabled" )->moveFile(
            "$main::imscpConfig{'LOGROTATE_CONF_DIR'}/rkhunter"
        );
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
