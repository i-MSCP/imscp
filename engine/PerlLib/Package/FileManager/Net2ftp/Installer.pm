#!/usr/bin/perl

=head1 NAME

Package::FileManager::Net2ftp::Installer - i-MSCP Net2ftp package installer

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

package Package::FileManager::Net2ftp::Installer;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Execute;
use iMSCP::Rights;
use iMSCP::Composer;
use iMSCP::TemplateParser;
use iMSCP::File;
use parent 'Common::SingletonClass';

our $VERSION = '0.1.0';

=head1 DESCRIPTION

 i-MSCP Net2ftp package installer

=head1 PUBLIC METHODS

=over 4

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	iMSCP::Composer->getInstance()->registerPackage('imscp/net2ftp', "$VERSION.*\@dev");
}

=item install()

 Process install tasks

 Return int 0 on success, 1 on failure

=cut

sub install
{
	my $self = $_[0];

	my $rs = $self->_installFiles();
	return $rs if $rs;

	$self->_buildConfig();
}

=item setGuiPermissions()

 Set file permissions

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
	my $panelUName =
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};

	setRights(
		"$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/ftp",
		{ 'user' => $panelUName, 'group' => $panelGName, 'dirmode' => '0550', 'filemode' => '0440', 'recursive' => 1 }
	);
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by getInstance(). Initialize Net2ftp package installer instance

 Return Package::FileManager::Net2ftp::Installer

=cut

sub _init
{
	my $self = $_[0];

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self;
}

=item _installFiles()

 Install Net2ftp files in production directory

 Return int 0 on success, other on failure

=cut

sub _installFiles
{
	my $repoDir = "$main::imscpConfig{'CACHE_DATA_DIR'}/packages";
	my $rs = 0;

	if(-d "$repoDir/vendor/imscp/net2ftp") {
		my $guiPublicDir = $main::imscpConfig{'GUI_PUBLIC_DIR'};

		my ($stdout, $stderr);
		$rs = execute("$main::imscpConfig{'CMD_RM'} -fR $guiPublicDir/tools/ftp", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $rs && $stderr;
		return $rs if $rs;

		$rs = execute(
			"$main::imscpConfig{'CMD_CP'} -R $repoDir/vendor/imscp/net2ftp $guiPublicDir/tools/ftp",
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
		error("Couldn't find the imscp/net2ftp package into the packages cache directory");
		$rs = 1;
	}

	$rs;
}

=item _generateMd5SaltString()

 Generate MD5 salt string

 Return int 0

=cut

sub _generateMd5SaltString
{
	my $saltString = '';
	$saltString .= ('A'..'Z', '0'..'9')[rand(35)] for 1..38;

	$saltString;
}

=item _buildConfig()

 Build Net2ftp configuration file

 Return int 0 on success, 1 on failure

=cut

sub _buildConfig
{
	my $self = $_[0];

	my $panelUName =
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $conffile = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/ftp/settings.inc.php";

	# Define data

	my $data = {
		ADMIN_EMAIL => ($main::imscpConfig{'DEFAULT_ADMIN_ADDRESS'}) ? $main::imscpConfig{'DEFAULT_ADMIN_ADDRESS'} : '',
		MD5_SALT_STRING => $self->_generateMd5SaltString()
	};

	# Load template

	my $cfgTpl;
	my $rs = $self->{'hooksManager'}->trigger('onLoadTemplate', 'net2ftp', 'settings.inc.php', \$cfgTpl, $data);
	return $rs if $rs;

	unless(defined $cfgTpl) {
		$cfgTpl = iMSCP::File->new('filename' => $conffile)->get();
		unless(defined $cfgTpl) {
			error("Unable to read file $conffile");
			return 1;
		}
	}

	# Build file

	$cfgTpl = process($data, $cfgTpl);

	# Store file

	my $file = iMSCP::File->new('filename' => $conffile);
	$rs = $file->set($cfgTpl);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	$file->owner($panelUName, $panelGName);
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
