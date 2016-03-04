=head1 NAME

Package::FileManager::Net2ftp::Net2ftp - i-MSCP package

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Package::FileManager::Net2ftp::Net2ftp;

use strict;
use warnings;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Net2ftp package.

 Net2ftp is a web-based FTP client written in PHP.

 Project homepage: http://www..com/

=head1 PUBLIC METHODS

=over 4

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	require Package::FileManager::Net2ftp::Installer;
	Package::FileManager::Net2ftp::Installer->getInstance()->preinstall();
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	require Package::FileManager::Net2ftp::Installer;
	Package::FileManager::Net2ftp::Installer->getInstance()->install();
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	require Package::FileManager::Net2ftp::Uninstaller;
	Package::FileManager::Net2ftp::Uninstaller->getInstance()->uninstall();
}

=item setGuiPermissions()

 Set file permissions

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
	require Package::FileManager::Net2ftp::Installer;
	Package::FileManager::Net2ftp::Installer->getInstance()->setGuiPermissions();
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
