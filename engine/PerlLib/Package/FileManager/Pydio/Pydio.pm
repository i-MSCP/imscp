=head1 NAME

 Package::FileManager::Pydio::Pydio - i-MSCP Pydio package

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

package Package::FileManager::Pydio::Pydio;

use strict;
use warnings;
use Class::Autouse qw/ :nostat Package::FileManager::Pydio::Installer Package::FileManager::Pydio::Uninstaller /;
use iMSCP::Rights;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Pydio package.

 Pydio ( formely AjaXplorer ) is a software that can turn any web server into a powerfull file management system and an
 alternative to mainstream cloud storage providers.

 Project homepage: https://pyd.io/

=head1 PUBLIC METHODS

=over 4

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    Package::FileManager::Pydio::Installer->getInstance()->preinstall();
}

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    Package::FileManager::Pydio::Installer->getInstance()->install();
}

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    Package::FileManager::Pydio::Uninstaller->getInstance()->uninstall();
}

=item setGuiPermissions( )

 Set file permissions

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
    my $panelUName = my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
    my $rs = setRights(
        "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/ftp",
        {
            user      => $panelUName,
            group     => $panelGName,
            dirmode   => '0550',
            filemode  => '0440',
            recursive => 1
        }
    );
    $rs ||= setRights(
        "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/ftp/data",
        {
            user      => $panelUName,
            group     => $panelGName,
            dirmode   => '0750',
            filemode  => '0640',
            recursive => 1
        }
    );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
