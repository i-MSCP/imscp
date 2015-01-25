#!/usr/bin/perl

=head1 NAME

Addons::FileManager::Net2ftp::Net2ftp - i-MSCP addon

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by internet Multi Server Control Panel
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
#
# @category    i-MSCP
# @copyright   2010-2015 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Addons::FileManager::Net2ftp::Net2ftp;

use strict;
use warnings;

use iMSCP::Debug;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Net2ftp addon.

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
	require Addons::FileManager::Net2ftp::Installer;
	Addons::FileManager::Net2ftp::Installer->getInstance()->preinstall();
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	require Addons::FileManager::Net2ftp::Installer;
	Addons::FileManager::Net2ftp::Installer->getInstance()->install();
}

=item setGuiPermissions()

 Set gui permissions

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
	require Addons::FileManager::Net2ftp::Installer;
	Addons::FileManager::Net2ftp::Installer->getInstance()->setGuiPermissions();
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
