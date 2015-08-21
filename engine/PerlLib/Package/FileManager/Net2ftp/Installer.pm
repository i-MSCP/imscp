=head1 NAME

Package::FileManager::Net2ftp::Installer - i-MSCP Net2ftp package installer

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

package Package::FileManager::Net2ftp::Installer;

use strict;
use warnings;
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use iMSCP::Debug;
use iMSCP::EventManager;
use iMSCP::Execute;
use iMSCP::Rights;
use iMSCP::Composer;
use iMSCP::TemplateParser;
use iMSCP::File;
use iMSCP::Dir;
use Package::FrontEnd;
use parent 'Common::SingletonClass';

our $VERSION = '0.1.1.*@dev';

=head1 DESCRIPTION

 i-MSCP Net2ftp package installer.

=head1 PUBLIC METHODS

=over 4

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, die on failure

=cut

sub preinstall
{
	my $self = shift;

	iMSCP::Composer->getInstance()->registerPackage('imscp/net2ftp', $VERSION);
	$self->{'eventManager'}->register('afterFrontEndBuildConfFile', \&afterFrontEndBuildConfFile);
}

=item install()

 Process install tasks

 Return int 0 on success, other or die on failure

=cut

sub install
{
	my $self = shift;

	my $rs = $self->_installFiles();
	return $rs if $rs;

	$rs = $self->_buildHttpdConfig();
	return $rs if $rs;

	$self->_buildConfig();
}

=item setGuiPermissions()

 Set gui permissions

 Return int 0 on success, die on failure

=cut

sub setGuiPermissions
{
	my $panelUName =
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};

	setRights("$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/ftp", {
		user => $panelUName, group => $panelGName, dirmode => '0550', filemode => '0440', recursive => 1
	});
}

=back

=head1 EVENT LISTENERS

=over 4

=item afterFrontEndBuildConfFile(\$tplContent, $filename)

 Include httpd configuration into frontEnd vhost files

 Param string \$tplContent Template file tplContent
 Param string $tplName Template name
 Return int 0

=cut

sub afterFrontEndBuildConfFile
{
	my ($tplContent, $tplName) = @_;

	if($tplName ~~ [ '00_master.conf', '00_master_ssl.conf' ]) {
		$$tplContent = replaceBloc(
			"# SECTION custom BEGIN.\n",
			"# SECTION custom END.\n",
			"    # SECTION custom BEGIN.\n" .
			getBloc(
				"# SECTION custom BEGIN.\n",
				"# SECTION custom END.\n",
				$$tplContent
			) .
				"    include imscp_net2ftp.conf;\n" .
				"    # SECTION custom END.\n",
			$$tplContent
		);
	}

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Package::FileManager::Net2ftp::Installer

=cut

sub _init
{
	my $self = shift;

	$self->{'eventManager'} = iMSCP::EventManager->getInstance();
	$self;
}

=item _installFiles()

 Install Net2ftp files in production directory

 Return int 0 on success, die on failure

=cut

sub _installFiles
{
	my $packageDir = "$main::imscpConfig{'CACHE_DATA_DIR'}/packages/vendor/imscp/net2ftp";

	-d $packageDir or die('Could not find the imscp/net2ftp package at %s', $packageDir);

	my $destDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/ftp";
	iMSCP::Dir->new( dirname => $destDir )->remove();
	iMSCP::Dir->new( dirname => $packageDir )->rcopy($destDir);
}

=item _generateMd5SaltString()

 Generate MD5 salt string

 Return string Salt string

=cut

sub _generateMd5SaltString
{
	my $saltString = '';
	$saltString .= ('A'..'Z', '0'..'9')[rand(35)] for 1..38;
	$saltString;
}

=item _buildHttpdConfig()

 Build Httpd configuration

 Return int 0 on success, other on failure

=cut

sub _buildHttpdConfig
{
	my $frontEnd = Package::FrontEnd->getInstance();

	$frontEnd->buildConfFile(
		"$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/FileManager/Net2ftp/config/nginx/imscp_net2ftp.conf",
		{ GUI_PUBLIC_DIR => $main::imscpConfig{'GUI_PUBLIC_DIR'} },
		{ destination => "$frontEnd->{'config'}->{'HTTPD_CONF_DIR'}/imscp_net2ftp.conf" }
	);
}

=item _buildConfig()

 Build configuration file

 Return int 0 on success, die on failure

=cut

sub _buildConfig
{
	my $self = shift;

	my $panelUName =
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $conffile = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/ftp/settings.inc.php";

	my $data = {
		ADMIN_EMAIL => ($main::imscpConfig{'DEFAULT_ADMIN_ADDRESS'}) ? $main::imscpConfig{'DEFAULT_ADMIN_ADDRESS'} : '',
		MD5_SALT_STRING => $self->_generateMd5SaltString()
	};

	$self->{'eventManager'}->trigger('onLoadTemplate', 'net2ftp', 'settings.inc.php', \my $cfgTpl, $data);

	$cfgTpl = iMSCP::File->new( filename => $conffile )->get() unless defined $cfgTpl;
	$cfgTpl = process($data, $cfgTpl);

	my $file = iMSCP::File->new( filename  => $conffile );
	$file->set($cfgTpl);
	$file->save();
	$file->mode(0640);
	$file->owner($panelUName, $panelGName);
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
