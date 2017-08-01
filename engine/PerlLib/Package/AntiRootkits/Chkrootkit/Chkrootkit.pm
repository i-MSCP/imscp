=head1 NAME

 Package::AntiRootkits::Chkrootkit::Chkrootkit - i-MSCP Chkrootkit package

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

package Package::AntiRootkits::Chkrootkit::Chkrootkit;

use strict;
use warnings;
use Class::Autouse qw/ :nostat Package::AntiRootkits::Chkrootkit::Installer Package::AntiRootkits::Chkrootkit::Uninstaller /;
use iMSCP::Rights;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Chkrootkit package.

 The chkrootkit security scanner searches the local system for signs that it is infected with a 'rootkit'. Rootkits are
 set of programs and hacks designed to take control of a target machine by using known security flaws.

=head1 PUBLIC METHODS

=over 4

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    Package::AntiRootkits::Chkrootkit::Installer->getInstance()->preinstall();
}

=item postinstall( )

 Process post install tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    Package::AntiRootkits::Chkrootkit::Installer->getInstance()->postinstall();
}

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    Package::AntiRootkits::Chkrootkit::Uninstaller->getInstance()->uninstall();
}

=item setEnginePermissions( )

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    setRights(
        $main::imscpConfig{'CHKROOTKIT_LOG'},
        {
            user  => $main::imscpConfig{'ROOT_USER'},
            group => $main::imscpConfig{'IMSCP_GROUP'},
            mode  => '0640'
        }
    );
}

=item getDistroPackages( )

 Get list of Debian packages

 Return list List of packages

=cut

sub getDistroPackages
{
    'chkrootkit';
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
