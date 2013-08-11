#!/usr/bin/perl

=head1 NAME

Addons::filemanager::net2ftp::installer - i-MSCP Net2Ftp addon installer

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2013 by internet Multi Server Control Panel
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
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Addons::filemanager::net2ftp::installer;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Addons::ComposerInstaller;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This is the installer for the i-MSCP Net2ftp addon.

 Net2ftp is a web-based FTP client written in PHP.

 Project homepage: http://www.net2ftp.com/

=head1 PUBLIC METHODS

=over 4

=item preinstall()

 Register Net2ftp composer package for installation.

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	my $self = shift;

	iMSCP::Addons::ComposerInstaller->getInstance()->registerPackage('imscp/net2ftp');
}

=item install()

 Process Net2ftp addon install tasks.

 Return int 0 on success, 1 on failure

=cut

sub install
{
	my $self = shift;

	$self->_installFiles(); # Install ajaxplorer files from local addon packages repository
}

=item setGuiPermissions()

 Set Net2ftp files permissions.

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
	my $self = shift;

	my $panelUName =
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};

	require iMSCP::Rights;
	iMSCP::Rights->import();

	setRights(
		"$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/filemanager",
		{ 'user' => $panelUName, 'group' => $panelGName, 'dirmode' => '0550', 'filemode' => '0440', 'recursive' => 1 }
	);
}

=back

=head1 PRIVATE METHODS

=over 4

=item _installFiles()

 Install Net2ftp files in production directory.

 Return int 0 on success, other on failure

=cut

sub _installFiles
{
	my $self = shift;

	my $repoDir = $main::imscpConfig{'ADDON_PACKAGES_CACHE_DIR'};
	my $rs = 0;

	if(-d "$repoDir/vendor/imscp/net2ftp") {
		my $guiPublicDir = $main::imscpConfig{'GUI_PUBLIC_DIR'};
		my ($stdout, $stderr);

		require iMSCP::Execute;
		iMSCP::Execute->import();

		$rs = execute(
			"$main::imscpConfig{'CMD_CP'} -rTf $repoDir/vendor/imscp/net2ftp $guiPublicDir/tools/filemanager",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $rs && $stderr;
		return $rs if $rs;

		$rs = execute("$main::imscpConfig{'CMD_RM'} -fR $guiPublicDir/tools/filemanager/.git", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $rs && $stderr;
		return $rs if $rs;
	} else {
		error("Couldn't find the imscp/net2ftp package into the local repository");
		$rs = 1;
	}

	$rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
