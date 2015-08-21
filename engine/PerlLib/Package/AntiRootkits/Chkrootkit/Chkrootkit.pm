=head1 NAME

Package::AntiRootkits::Chkrootkit::Chkrootkit - i-MSCP Chkrootkit package

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

package Package::AntiRootkits::Chkrootkit::Chkrootkit;

use strict;
use warnings;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Chkrootkit package.

 The chkrootkit security scanner searches the local system for signs that it is infected with a 'rootkit'. Rootkits are
set of programs and hacks designed to take control of a target machine by using known security flaws.

=head1 PUBLIC METHODS

=over 4

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	require Package::AntiRootkits::Chkrootkit::Installer;
	Package::AntiRootkits::Chkrootkit::Installer->getInstance()->preinstall();
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	require Package::AntiRootkits::Chkrootkit::Installer;
	Package::AntiRootkits::Chkrootkit::Installer->getInstance()->install();
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	require Package::AntiRootkits::Chkrootkit::Uninstaller;
	Package::AntiRootkits::Chkrootkit::Uninstaller->getInstance()->uninstall();
}

=item setEnginePermissions()

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
	require Package::AntiRootkits::Chkrootkit::Installer;
	Package::AntiRootkits::Chkrootkit::Installer->getInstance()->setEnginePermissions();
}

=item getDistroPackages()

 Get list of Debian packages

 Return array List of packages

=cut

sub getDistroPackages
{
	['chkrootkit'];
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
