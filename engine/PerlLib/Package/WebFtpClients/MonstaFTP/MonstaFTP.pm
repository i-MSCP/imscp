=head1 NAME

 Package::WebFtpClients::MonstaFTP::MonstaFTP - i-MSCP package

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

package Package::WebFtpClients::MonstaFTP::MonstaFTP;

use strict;
use warnings;
use Class::Autouse qw/ :nostat Package::WebFtpClients::MonstaFTP::Installer Package::WebFtpClients::MonstaFTP::Uninstaller /;
use iMSCP::Boolean;
use iMSCP::Rights;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP MonstaFTP package.

 MonstaFTP is a web-based FTP client written in PHP.

 Project homepage: http://www.monstaftp.com//

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( \%em )

 Register setup event listeners

 Param iMSCP::EventManager \%em
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ( undef, $em ) = @_;

    Package::WebFtpClients::MonstaFTP::Installer->getInstance()->registerSetupListeners( $em );
}

=item preinstall( )

 Process pre-installation tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    Package::WebFtpClients::MonstaFTP::Installer->getInstance()->preinstall();
}

=item install( )

 Process installation tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    Package::WebFtpClients::MonstaFTP::Installer->getInstance()->install();
}

=item uninstall( )

 Process uninstallation tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    Package::WebFtpClients::MonstaFTP::Uninstaller->getInstance()->uninstall();
}

=item setGuiPermissions( )

 Set GUI permissions

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
    my $panelUName = my $panelGName = $::imscpConfig{'SYSTEM_USER_PREFIX'} . $::imscpConfig{'SYSTEM_USER_MIN_UID'};

    setRights( "$::imscpConfig{'GUI_PUBLIC_DIR'}/tools/ftp", {
        user      => $panelUName,
        group     => $panelGName,
        dirmode   => '0550',
        filemode  => '0440',
        recursive => TRUE
    } );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
