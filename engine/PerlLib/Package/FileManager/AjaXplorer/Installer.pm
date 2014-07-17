#!/usr/bin/perl

=head1 NAME

Package::FileManager::AjaXplorer::Installer - i-MSCP AjaXplorer package installer

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2014 by internet Multi Server Control Panel
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
# @copyright   2010-2014 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Package::FileManager::AjaXplorer::Installer;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Execute;
use iMSCP::Rights;
use iMSCP::Composer;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP AjaXplorer package installer

=head1 PUBLIC METHODS

=over 4

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	iMSCP::Composer->getInstance()->registerPackage('imscp/ajaxplorer');
}

=item install()

 Process install tasks.

 Return int 0 on success, 1 on failure

=cut

sub install
{
	$_[0]->_installFiles();
}

=item setGuiPermissions()

 Set file permissions

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
	my $panelUName =
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $guiPublicDir = $main::imscpConfig{'GUI_PUBLIC_DIR'};

	my $rs = setRights(
		"$guiPublicDir/tools/ftp",
		{ 'user' => $panelUName, 'group' => $panelGName, 'dirmode' => '0550', 'filemode' => '0440', 'recursive' => 1 }
	);
	return $rs if $rs;

	setRights(
		"$guiPublicDir/tools/ftp/data",
		{ 'user' => $panelUName, 'group' => $panelGName, 'dirmode' => '0700', 'filemode' => '0600', 'recursive' => 1 }
	);
}

=back

=head1 PRIVATE METHODS

=over 4

=item _installFiles()

 Install AjaXplorer files in production directory

 Return int 0 on success, other on failure

=cut

sub _installFiles
{
	my $repoDir = "$main::imscpConfig{'CACHE_DATA_DIR'}/packages";
	my $rs = 0;

	if(-d "$repoDir/vendor/imscp/ajaxplorer") {
		my $guiPublicDir = $main::imscpConfig{'GUI_PUBLIC_DIR'};

		my ($stdout, $stderr);
		$rs = execute("$main::imscpConfig{'CMD_RM'} -fR $guiPublicDir/tools/ftp", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $rs && $stderr;
		return $rs if $rs;

		$rs = execute(
			"$main::imscpConfig{'CMD_CP'} -R $repoDir/vendor/imscp/ajaxplorer $guiPublicDir/tools/ftp",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $rs && $stderr;
		return $rs if $rs;

		$rs = execute("$main::imscpConfig{'CMD_RM'} -fR $guiPublicDir/tools/ftp/.git", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $rs && $stderr;
		return $rs if $rs;
	} else {
		error("Couldn't find the imscp/ajaxplorer package into the packages cache directory");
		$rs = 1;
	}

	$rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
