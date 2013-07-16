#!/usr/bin/perl

=head1 NAME

Addons::filemanager::ajaxplorer::installer - i-MSCP AjaxPlorer addon installer

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
# @category		i-MSCP
# @copyright	2010-2013 by i-MSCP | http://i-mscp.net
# @author		Laurent Declercq <l.declercq@nuxwin.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Addons::filemanager::ajaxplorer::installer;

use strict;
use warnings;

use iMSCP::Debug;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This is the installer for the i-MSCP AjaxPlorer addon.

 AjaXplorer is a software that can turn any web server into a powerfull file management system and an alternative to
mainstream cloud storage providers.

 Project homepage: http://ajaxplorer.info/

=head1 PUBLIC METHODS

=over 4

=item preinstall()

 Register AjaxPlorer composer package for installation.

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	my $self = shift;

	require iMSCP::Addons::ComposerInstaller;
	iMSCP::Addons::ComposerInstaller->getInstance()->registerPackage('imscp/ajaxplorer');
}

=item install()

 Process AjaxPlorer addon install tasks.

 Return int 0 on success, 1 on failure

=cut

sub install
{
	my $self = shift;

	$self->_installFiles();	# Install ajaxplorer files from local addon packages repository
}

=item setGuiPermissions()

 Set AjaxPlorer files permissions.

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
	my $self = shift;
	my $panelUName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $panelGName = $panelUName;
	my $rootDir = $main::imscpConfig{'ROOT_DIR'};
	my $rs = 0;

	require Servers::httpd;
	my $http = Servers::httpd->factory();
	my $apacheGName = $http->can('getRunningGroup') ? $http->getRunningGroup() : $main::imscpConfig{'ROOT_GROUP'};

	require iMSCP::Rights;
	iMSCP::Rights->import();

	$rs = setRights(
		"$rootDir/gui/public/tools/filemanager",
		{ 'user' => $panelUName, 'group' => $apacheGName, 'dirmode' => '0550', 'filemode' => '0440', 'recursive' => 1 }
	);
	return $rs if $rs;

	setRights(
		"$rootDir/gui/public/tools/filemanager/data",
		{ 'user' => $panelUName, 'group' => $panelGName, 'dirmode' => '0700', 'filemode' => '0600', 'recursive' => 1 }
	);
}

=back

=head1 PRIVATE METHODS

=over 4

=item _installFiles()

 Install AjaxPlorer files in production directory.

 Return int 0 on success, other on failure

=cut

sub _installFiles
{
	my $self = shift;
	my $repoDir = $main::imscpConfig{'ADDON_PACKAGES_CACHE_DIR'};
	my ($stdout, $stderr);
	my $rs = 0;

	if(-d "$repoDir/vendor/imscp/ajaxplorer") {

		require iMSCP::Execute;
		iMSCP::Execute->import();

		$rs = execute(
			"$main::imscpConfig{'CMD_CP'} -rTf $repoDir/vendor/imscp/ajaxplorer $main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/filemanager",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $rs && $stderr;
		return $rs if $rs;

		$rs = execute(
			"$main::imscpConfig{'CMD_RM'} -fR $main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/filemanager/.git",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $rs && $stderr;
		return $rs if $rs;
	} else {
		error("Couldn't find the imscp/ajaxplorer package into the local repository");
		$rs = 1;
	}

	$rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
