#!/usr/bin/perl

=head1 NAME

 iMSCP::Composer - i-MSCP Composer package installer

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
#
# @category    i-MSCP
# @copyright   2010-2014 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::Composer;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::File;
use iMSCP::Dir;
use iMSCP::Execute;
use iMSCP::TemplateParser;
use iMSCP::HooksManager;
use iMSCP::Getopt;
use iMSCP::Dialog;
use Cwd;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Composer package installer for iMSCP. Allows to install composer packages from packagist.org (GitHub).

=head1 PUBLIC METHODS

=over 4

=item registerPackage($package, [$packageVersion = 'dev-master'])

 Register the given composer package for installation

 Return int 0

=cut

sub registerPackage($$;$)
{
	my ($self, $package, $packageVersion) = @_;

	$packageVersion ||= 'dev-master';

	push @{$self->{'toInstall'}}, "\t\t\"$package\":\"$packageVersion\"";

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by getInstance(). Initialize instance of this class

 Return iMSCP::Composer

=cut

sub _init
{
	my $self = $_[0];

	$self->{'toInstall'} = [];
	$self->{'cacheDir'} = "$main::imscpConfig{'CACHE_DATA_DIR'}/packages";
	$self->{'phpCmd'} = $main::imscpConfig{'CMD_PHP'} .
		' -d memory_limit=512M -d allow_url_fopen=1' .
		' -d suhosin.executor.include.whitelist=phar';

	# Increase composer process timeout for slow connections
	$ENV{'COMPOSER_PROCESS_TIMEOUT'} = 2000;

	# Override default composer home directory
	$ENV{'COMPOSER_HOME'} = "$self->{'cacheDir'}/.composer";

	iMSCP::HooksManager->getInstance()->register(
		'afterSetupPreInstallPackages', sub {
			iMSCP::Dialog->factory()->endGauge();

			my $rs = iMSCP::Dir->new('dirname' => $self->{'cacheDir'})->make();
			return $rs if $rs;

			# Cleanup i-MSCP packages cache directory if asked by user
			$rs = $self->_cleanCacheDir() if iMSCP::Getopt->cleanPackagesCache;
			return $rs if $rs;

			$rs = $self->_getComposer();
			return $rs if $rs;

			# Skip the packages update if asked by user but only if all requirement for package versions are meets
			if(! iMSCP::Getopt->skipPackagesUpdate || $self->_checkRequirements()) {
				$rs = $self->_installPackages();
			}

			$rs;
		}
	);

	$self;
}

=item _installPackages()

 Install or update packages in package cache repository

 Return 0 on success, other on failure

=cut

sub _installPackages
{
	my $self = $_[0];

	my $rs = $self->_buildComposerFile();
	return $rs if $rs;

	iMSCP::Dialog->factory()->infobox(
'
Fetching i-MSCP packages from GitHub.

Please wait, depending on your connection, this may take few minutes.
'
	);

	# The update option is used here but composer will automatically fallback to install mode when needed
	my ($stdout, $stderr);
	$rs = execute(
		"$self->{'phpCmd'} $self->{'cacheDir'}/composer.phar --no-ansi -d=$self->{'cacheDir'} update",
		\$stdout,
		\$stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error('Unable to get i-MSCP packages from GitHub') if $rs && ! $stderr;

	$rs;
}

=item _buildComposerFile()

 Build composer.json file

 Return 0 on success, other on failure

=cut

sub _buildComposerFile
{
	my $self = $_[0];

	iMSCP::Dialog->factory()->infobox("\nBuilding composer.json file for i-MSCP packages...");

	my $composerJsonFile = process({ 'PACKAGES' => join ",\n", @{$self->{'toInstall'}} }, $self->_getComposerFileTpl());

	my $file = iMSCP::File->new('filename' => "$self->{'cacheDir'}/composer.json");

	my $rs = $file->set($composerJsonFile);
	return $rs if $rs;

	$file->save();
}

=item _getComposer()

 Get composer.phar

 Return 0 on success, other on failure

=cut

sub _getComposer
{
	my $self = $_[0];

	my ($stdout, $stderr);
	my $curDir = getcwd();
	my $rs = 0;

	if (! -f "$self->{'cacheDir'}/composer.phar") {
		unless(chdir($self->{'cacheDir'})) {
			error("Unable to change working directory to $self->{'cacheDir'}: $!");
			return 1;
		}

		iMSCP::Dialog->factory()->infobox(
"
Fetching composer installer from http://getcomposer.org.

Please wait, depending on your connection, this may take few seconds...
"
		);

		$rs = execute(
			"$main::imscpConfig{'CMD_CURL'} -s http://getcomposer.org/installer | $self->{'phpCmd'}",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		error($stdout) if ! $stderr && $stdout && $rs;
		error('Unable to get composer installer from http://getcomposer.org') if $rs && ! $stdout && ! $stderr;

		unless(chdir($curDir)) {
		error("Unable to change working directory to $curDir: $!");
		return 1;
		}
	} else {
		unless(chdir($self->{'cacheDir'})) {
			error("Unable to change working directory to $self->{'cacheDir'}: $!");
			return 1;
		}

		iMSCP::Dialog->factory()->infobox(
"
Updating composer installer from http://getcomposer.org.

Please wait, depending on your connection, this may take few seconds...
"
		);

		$rs = execute(
			"$self->{'phpCmd'} $self->{'cacheDir'}/composer.phar --no-ansi -d=$self->{'cacheDir'} self-update",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		error('Unable to update composer installer') if $rs && ! $stderr;

		unless(chdir($curDir)) {
			error("Unable to change working directory to $curDir: $!");
			return 1;
		}
	}

	$rs;
}

=item _getComposerFileTpl()

 Get composer.json template

 Return string composer.json template file content

=cut

sub _getComposerFileTpl
{
	<<EOF;
{
	"require":{
{PACKAGES}
	},
	"minimum-stability":"dev"
}
EOF
}

=item _cleanCacheDir()

 Clean i-MSCP packages cache

 Return 0 on success, other on failure

=cut

sub _cleanCacheDir
{
	my $self = $_[0];

	my $rs = 0;

	if(-d $self->{'cacheDir'}) {
		my ($stdout, $stderr);
		$rs = execute("$main::imscpConfig{'CMD_RM'} -fR $self->{'cacheDir'}/*", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		error('Unable to clean packages cache directory') if $rs && ! $stderr;
	}

	$rs;
}

=item _checkRequirements()

 Check package version requirements

 Return int 0 if all requirements are meets, 1 otherwise

=cut

sub _checkRequirements
{
	my $self = $_[0];

	return 1 unless -d $self->{'cacheDir'};

	my $rs = 0;

	for(@{$self->{'toInstall'}}) {
		my ($package, $version) = $_ =~ /"(.*)":"(.*)"/;

		my @cmd = (
			$self->{'phpCmd'},
			"$self->{'cacheDir'}/composer.phar",
			'--no-ansi',
			"-d=$self->{'cacheDir'}",
			'show', '--installed', escapeShell($package), escapeShell($version)
		);

		my ($stdout, $stderr);
		$rs = execute("@cmd", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		debug(sprintf("Required version (%s) of package %s not found in cache directory.", $package, $version)) if $rs;
		last if $rs;
	}

	$rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
