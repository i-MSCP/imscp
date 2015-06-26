=head1 NAME

 iMSCP::Composer - i-MSCP Composer packages installer

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

package iMSCP::Composer;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::File;
use iMSCP::Dir;
use iMSCP::Execute;
use iMSCP::TemplateParser;
use iMSCP::EventManager;
use iMSCP::Getopt;
use iMSCP::Dialog;
use Cwd;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Composer packages installer for iMSCP.

=head1 PUBLIC METHODS

=over 4

=item registerPackage($package [, $packageVersion = 'dev-master' ])

 Register the given composer package for installation

 Param string $package Package name
 Param string $packageVersion OPTIONAL Package version
 Return int 0

=cut

sub registerPackage
{
	my ($self, $package, $packageVersion) = @_;

	$packageVersion ||= 'dev-master';

	push @{$self->{'toInstall'}}, "        \"$package\": \"$packageVersion\"";

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
	my $self = shift;

	$self->{'toInstall'} = [];
	$self->{'composer_path'} = "$main::imscpConfig{'ENGINE_ROOT_DIR'}/setup/composer.phar";
	$self->{'wrkDir'} = "$main::imscpConfig{'CACHE_DATA_DIR'}/packages";

	# Override default composer home directory
	$ENV{'COMPOSER_HOME'} = "$self->{'wrkDir'}/.composer";

	# Increase composer process timeout for slow connections
	$ENV{'COMPOSER_PROCESS_TIMEOUT'} = 2000;

	# We do not want any user interaction
	$ENV{'COMPOSER_NO_INTERACTION'} = '1';

	# We discard any change made in vendor
	$ENV{'COMPOSER_DISCARD_CHANGES'} = 'true';

	$self->{'phpCmd'} = 'php -d allow_url_fopen=1 -d suhosin.executor.include.whitelist=phar';

	iMSCP::EventManager->getInstance()->register(
		'afterSetupPreInstallPackages', sub {
			iMSCP::Dialog->getInstance()->endGauge();

			# Clear local repository if asked by user
			my $rs = $self->_clearLocalRepository() if iMSCP::Getopt->cleanPackagesCache;
			return $rs if $rs;

			$rs = iMSCP::Dir->new( dirname => $self->{'wrkDir'} )->make();
			return $rs if $rs;

			# Skip packages update if asked by user but only if all requirements for package versions are meets
			if(! iMSCP::Getopt->skipPackagesUpdate || $self->_checkRequirements()) {
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
	my $self = shift;

	my $rs = $self->_buildComposerFile();
	return $rs if $rs;

	my $stderr;
	my $dialog = iMSCP::Dialog->getInstance();
	my $msgHeader = <<EOF;

Installing/Updating i-MSCP composer packages from Github
EOF

	# The update option is used here but composer will automatically fallback to install mode when needed
	# Note: Any progress/status info goes to stderr (See https://github.com/composer/composer/issues/3795)
	$rs = executeNoWait(
		"$self->{'phpCmd'} $self->{'composer_path'} --no-ansi -d=$self->{'wrkDir'} update --prefer-dist",
		sub { my $str = shift; $$str = ''; },
		sub { my $str = shift; ($$str =~ /^$/m) ? $$str = '' : $dialog->infobox(
			"$msgHeader\n$$str\nPlease wait, depending on your connection, this may take few seconds..."
		); }
	);

	error("Unable to install/update i-MSCP composer packages from GitHub: $stderr") if $stderr && $rs;
	error('Unable to install/update i-MSCP composer packages from GitHub: Unknown error') if $rs && ! $stderr;

	$rs;
}

=item _buildComposerFile()

 Build composer.json file

 Return 0 on success, other on failure

=cut

sub _buildComposerFile
{
	my $self = shift;

	iMSCP::Dialog->getInstance()->infobox(<<EOF);

Building composer.json file for composer packages...
EOF

	my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/composer.json" );

	my $rs = $file->set(process({ 'PACKAGES' => join ",\n", @{$self->{'toInstall'}} }, $self->_getComposerFileTpl()));
	return $rs if $rs;

	$file->save();
}

=item _getComposerFileTpl()

 Get composer.json template

 Return string

=cut

sub _getComposerFileTpl
{
	<<EOF;
{
    "name": "imscp/packages",
    "description": "i-MSCP composer packages",
    "licence": "GPL-2.0+",
    "require": {
{PACKAGES}
    },
    "minimum-stability": "dev"
}
EOF
}

=item _clearLocalRepository()

 clear local repository

 Return 0 on success, other on failure

=cut

sub _clearLocalRepository
{
	my $self = shift;

	if(-d $self->{'wrkDir'}) {
		my ($stdout, $stderr);
		my $rs = execute("rm -fR $self->{'wrkDir'}", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error("Unable to clear local repository: $stderr") if $stderr && $rs;
		error('Unable to clear local repository: Unknown error') if $rs && ! $stderr;
		return $rs;
	}

	0;
}

=item _checkRequirements()

 Check package version requirements

 Return int 0 if all requirements are meets, 1 otherwise

=cut

sub _checkRequirements
{
	my $self = shift;

	return 1 unless -d $self->{'wrkDir'};

	for(@{$self->{'toInstall'}}) {
		my ($package, $version) = $_ =~ /"(.*)":"(.*)"/;

		my @cmd = (
			$self->{'phpCmd'}, $self->{'composer_path'}, '--no-ansi', "-d=$self->{'wrkDir'}", 'show', '--installed',
			escapeShell($package), escapeShell($version)
		);

		my ($stdout, $stderr);
		my $rs = execute("@cmd", \$stdout, \$stderr);
		debug($stdout) if $stdout;

		if($rs) {
			debug(sprintf("Required version (%s) of package %s not found in local repository.", $package, $version));
			return 1;
		}
	}

	0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
