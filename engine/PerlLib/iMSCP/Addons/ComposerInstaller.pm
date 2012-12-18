#!/usr/bin/perl

=head1 NAME

 iMSCP::Addons::ComposerInstaller - i-MSCP Addons Composer installer

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010 - 2012 by internet Multi Server Control Panel
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
#
# @category		i-MSCP
# @copyright	2010 - 2012 by i-MSCP | http://i-mscp.net
# @author		Laurent Declercq <l.declercq@nuxwin.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::Addons::ComposerInstaller;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::File;
use iMSCP::Dir;
use iMSCP::Execute;
use iMSCP::Templator;
use iMSCP::HooksManager;
use iMSCP::Getopt;
use iMSCP::Dialog;
use Cwd;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Composer installer for iMSCP. Allows to install composer packages from packagist.org.

=head1 PUBLIC METHODS

=over 4

=item getInstance()

 Implements Singleton Design Pattern - Returns instance of this class.

 Return iMSCP::Addons::ComposerInstaller

=cut

sub getInstance
{
	iMSCP::Addons::ComposerInstaller->new();
}

=item registerPackage($package)

 Register the given composer package for installation.

 Return int - 0

=cut

sub registerPackage($ $)
{
	my $self = shift;
	my $package = shift;

	push(@{$self->{'toInstall'}}, "\t\t\"$package\":\"dev-master\"");

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item getInstance()

 Called by new(). initialize instance

 Return iMSCP::Addons::ComposerInstaller

=cut

sub _init
{
	my $self = shift;

	$self->{'toInstall'} = ();
	$self->{'cacheDir'} = $main::imscpConfig{'ADDON_PACKAGES_CACHE_DIR'};
	$self->{'phpCmd'} = "$main::imscpConfig{'CMD_PHP'} -d suhosin.executor.include.whitelist=phar";

	my $dir = iMSCP::Dir->new(dirname => $self->{'cacheDir'});
	$dir->make() and die("Unable to create the cache directory for addon packages");

	# Override default composer home directory
	$ENV{'COMPOSER_HOME'} = "$self->{'cacheDir'}/.composer";

	# Cleanup addon packages cache directory if asked by user - This will cause all addon packages to be fetched again
	$self->_cleanCacheDir() if iMSCP::Getopt->cleanAddons;

	# Schedule package installation (done after addons preinstallation)
	iMSCP::HooksManager->getInstance()->register('afterSetupPreInstallAddons', sub { $self->_installPackages(@_) });

	$self;
}

=item _installPackages()

 Install packages in temporary directory

 return 0 on success, other on failure

=cut

sub _installPackages
{
	my $self = shift;
	my ($stdout, $stderr) = (undef, undef);

	$self->_buildComposerFile() and return 1;
	$self->_getComposer() and return 1;

	# Install packages
	iMSCP::Dialog->factory()->infobox(
"
Getting composer addon packages from packagist.org.

Please wait, this may take a few seconds...
"
	);

	# update option is used here but composer will automatically fallback to installation when needed
	my $rs = execute(
		"$self->{'phpCmd'} $self->{'cacheDir'}/composer.phar -d=$self->{'cacheDir'} update", \$stdout, \$stderr
	);
	error("Unable to get i-MSCP addon packages from packagist.org: $stderr") if $rs;
	debug($stdout) if $stdout;

	$rs;
}

=item _buildComposerFile()

 Build composer.json file

 Return 0 on success, other on failure

=cut

sub _buildComposerFile
{
	my $self = shift;

	iMSCP::Dialog->factory()->infobox("\nBuilding composer.json file for addons packages...");

	my $composerJsonFile = iMSCP::Templator::process(
		{ 'PACKAGES' => join ",\n", @{$self->{'toInstall'}} },
		$self->_getComposerFileTpl()
	);

	my $file = iMSCP::File->new(filename => "$self->{'cacheDir'}/composer.json");
	$file->set($composerJsonFile) and return 1;
	$file->save();
}

=item _getComposer()

 Get composer.phar

 Return 0 on success, other on failure

=cut

sub _getComposer
{
	my $self = shift;
	my ($stdout, $stderr) = (undef, undef);
	my $curDir = getcwd();
	my $rs = 0;

	if (! -f "$self->{'cacheDir'}/composer.phar") {
		unless(chdir($self->{'cacheDir'})) {
			error("Unable to change working directory to $self->{'cacheDir'}: $!");
			return 1;
		}

		iMSCP::Dialog->factory()->infobox(
"
Getting composer installer from http://getcomposer.org.

Please wait, this may take a few seconds...
"
		);

		$rs = execute(
			"$main::imscpConfig{'CMD_CURL'} -s http://getcomposer.org/installer | $self->{'phpCmd'}",
			\$stdout,
			\$stderr
		);
		error("Unable to get composer installer from http://getcomposer.org: $stderr") if $rs;
		debug($stdout) if $stdout;

		unless(chdir($curDir)) {
    		error("Unable to change working directory to $curDir: $!");
    		return 1;
    	}
    }

	$rs;
}

=item _getComposer()

 Get composer.json template

 return string

=cut

sub _getComposerFileTpl
{
	my $self = shift;

	my $json = <<EOF;
{
	"name":"imscp/addons",
	"description":"i-MSCP addons composer file",
	"license":"GPL-2.0+",
	"require":{
{PACKAGES}
	},
	"minimum-stability":"dev"
}
EOF

	$json;
}

=item _cleanTmp()

 Clean local addon packages repository directory

 return 0 on success, other on failure

=cut

sub _cleanCacheDir
{
	my $self = shift;

	iMSCP::Dialog->factory()->infobox("\nCleaning composer installer cache directory.");

	execute("$main::imscpConfig{'CMD_RM'} -rf $self->{'cacheDir'}/*");
}

=back

=head1

 TODO: Implements composer installer to install package in the tools directory directy

=cut

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
