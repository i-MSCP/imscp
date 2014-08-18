#!/usr/bin/perl

=head1 NAME

 iMSCP::Composer - i-MSCP Composer packages installer

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

 Composer packages installer for iMSCP.

=head1 PUBLIC METHODS

=over 4

=item registerPackage($package, [$packageVersion = 'dev-master'])

 Register the given composer package for installation

 Param string $package Package name
 Param string $packageVersion OPTIONAL Package version
 Return int 0

=cut

sub registerPackage
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

 Initialize instance

 Return iMSCP::Composer

=cut

sub _init
{
	my $self = $_[0];

	$self->{'toInstall'} = [];

	$self->{'wrkDir'} = "$main::imscpConfig{'CACHE_DATA_DIR'}/addons";

	# Override default composer home directory
	$ENV{'COMPOSER_HOME'} = "$self->{'wrkDir'}/.composer";

	# Increase composer process timeout for slow connections
	$ENV{'COMPOSER_PROCESS_TIMEOUT'} = 2000;

	# We do not want any user interaction
	$ENV{'COMPOSER_NO_INTERACTION'} = '1';

	# We discard any change made in vendor
	$ENV{'COMPOSER_DISCARD_CHANGES'} = 'true';

	$self->{'phpCmd'} = $main::imscpConfig{'CMD_PHP'} .
		' -d allow_url_fopen=1' .
		' -d suhosin.executor.include.whitelist=phar';

	iMSCP::HooksManager->getInstance()->register(
		'afterSetupPreInstallPackages', sub {
			iMSCP::Dialog->factory()->endGauge();

			my $rs = iMSCP::Dir->new('dirname' => $self->{'wrkDir'})->make();
			return $rs if $rs;

			# Clear local repository if asked by user
			$rs = $self->_clearLocalRepository() if iMSCP::Getopt->cleanAddons;
			return $rs if $rs;

			$rs = $self->_getComposer();
			return $rs if $rs;

			# Skip packages update if asked by user but only if all requirements for package versions are meets
			if( ! iMSCP::Getopt->skipAddonsUpdate || $self->_checkRequirements()) {
				$rs = $self->_installPackages();
			}

			$rs;
		}
	);

	$self;
}

=item _installPackages()

 Install or update packages

 Return 0 on success, other on failure

=cut

sub _installPackages
{
	my $self = $_[0];

	my $rs = $self->_buildComposerFile();
	return $rs if $rs;

	iMSCP::Dialog->factory()->infobox(<<EOF);

Fetching i-MSCP composer packages from GitHub.

Please wait, depending on your connection, this may take few minutes.
EOF

	# The update option is used here but composer will automatically fallback to install mode when needed
	my ($stdout, $stderr);
	$rs = execute(
		"$self->{'phpCmd'} $self->{'wrkDir'}/composer.phar --no-ansi -d=$self->{'wrkDir'} update --prefer-dist",
		\$stdout,
		\$stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error('Unable to fetch i-MSCP composer packages from GitHub') if $rs && ! $stderr;

	$rs;
}

=item _buildComposerFile()

 Build composer.json file

 Return 0 on success, other on failure

=cut

sub _buildComposerFile
{
	my $self = $_[0];

	iMSCP::Dialog->factory()->infobox(<<EOF);

Building composer.json file for composer packages...
EOF

	my $file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/composer.json");

	my $rs = $file->set(
		process({ 'PACKAGES' => join ",\n", @{$self->{'toInstall'}} }, $self->_getComposerFileTpl())
	);
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

	my $curDir = getcwd();
	my $rs = 0;

	unless (-f "$self->{'wrkDir'}/composer.phar") {
		unless(chdir($self->{'wrkDir'})) {
			error("Unable to change working directory to $self->{'wrkDir'}: $!");
			return 1;
		}

		iMSCP::Dialog->factory()->infobox(<<EOF);

Fetching composer.phar from http://getcomposer.org.

Please wait, depending on your connection, this may take few seconds...
EOF

		my ($stdout, $stderr);
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
		unless(chdir($self->{'wrkDir'})) {
			error("Unable to change working directory to $self->{'wrkDir'}: $!");
			return 1;
		}

		iMSCP::Dialog->factory()->infobox(<<EOF);

Updating composer.phar from http://getcomposer.org.

Please wait, depending on your connection, this may take few seconds...
EOF

		my ($stdout, $stderr);
		$rs = execute(
			"$self->{'phpCmd'} $self->{'wrkDir'}/composer.phar --no-ansi -d=$self->{'wrkDir'} self-update",
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

 Return string

=cut

sub _getComposerFileTpl
{
	<<EOF;
{
	"require": {
{PACKAGES}
	},
	"minimum-stability":"dev"
}
EOF
}

=item _clearLocalRepository()

 clear local repository

 Return 0 on success, other on failure

=cut

sub _clearLocalRepository
{
	my $self = $_[0];

	my $rs = 0;

	if(-d $self->{'wrkDir'}) {
		my ($stdout, $stderr);
		$rs = execute("$main::imscpConfig{'CMD_RM'} -fR $self->{'wrkDir'}/*", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		error('Unable to clear local repository') if $rs && ! $stderr;
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

	return 1 unless -d $self->{'wrkDir'};

	my $rs = 0;

	for(@{$self->{'toInstall'}}) {
		my ($package, $version) = $_ =~ /"(.*)":"(.*)"/;

		my @cmd = (
			$self->{'phpCmd'},
			"$self->{'wrkDir'}/composer.phar",
			'--no-ansi',
			"-d=$self->{'wrkDir'}",
			'show',
			'--installed',
			escapeShell($package),
			escapeShell($version)
		);

		my ($stdout, $stderr);
		$rs = execute("@cmd", \$stdout, \$stderr);
		debug($stdout) if $stdout;

		if($rs) {
			debug(sprintf("Required version (%s) of package %s not found in local repository.", $package, $version));
			last;
		}
	}

	$rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
