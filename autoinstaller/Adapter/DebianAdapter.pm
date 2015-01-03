#!/usr/bin/perl

=head1 NAME

 autoinstaller::Adapter::DebianAdapter - Debian autoinstaller adapter class

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright 2010-2014 by internet Multi Server Control Panel
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

package autoinstaller::Adapter::DebianAdapter;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Debug;
use iMSCP::EventManager;
use iMSCP::Execute;
use iMSCP::Dialog;
use iMSCP::File;
use iMSCP::Stepper;
use iMSCP::Getopt;
use iMSCP::ProgramFinder;
use File::Temp;
use parent 'autoinstaller::Adapter::AbstractAdapter';

=head1 DESCRIPTION

 i-MSCP autoinstaller adapter implementation for Debian.

=head1 PUBLIC METHODS

=over 4

=item installPreRequiredPackages()

 Install pre-required packages

 Return int 0 on success, other on failure

=cut

sub installPreRequiredPackages
{
	my $self = $_[0];

	$self->{'eventManager'}->trigger('beforeInstallPreRequiredPackages', $self->{'preRequiredPackages'});

	my $command = 'apt-get';
	my $preseed = iMSCP::Getopt->preseed;

	unless(iMSCP::ProgramFinder::find($command)) {
		fatal('Not a Debian like system');
	}

	# Ensure packages index is up to date
	my $rs = $self->_updatePackagesIndex();
	return $rs if $rs;

	unless($preseed || $main::noprompt || ! iMSCP::ProgramFinder::find('debconf-apt-progress')) {
		$command = 'debconf-apt-progress --logstderr -- ' . $command;
	}

	my ($stdout, $stderr);
	$rs = execute(
		"$command -y -o DPkg::Options::='--force-confnew' -o DPkg::Options::='--force-confmiss' --auto-remove --purge " .
			"--no-install-recommends install @{$self->{'preRequiredPackages'}}",
		($preseed || $main::noprompt) ? \$stdout : undef, \$stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error('Unable to install pre-required packages') if $rs && ! $stderr;
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterInstallPreRequiredPackages');
}

=item preBuild()

 Process preBuild tasks

 Return int 0 on success, other on failure

=cut

sub preBuild
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforePreBuild');
	return $rs if $rs;

	unless($main::skippackages) {
		if($main::imscpConfig{'DATABASE_PASSWORD'} ne '' && not $main::reconfigure ~~ ['sql', 'servers', 'all']) {
			$ENV{'DEBIAN_PRIORITY'} = 'critical';
		}

		my @steps = (
			[sub { $self->_buildPackageList(); },       'Building list of packages to install/uninstall'],
			[sub { $self->_prefillDebconfDatabase(); }, 'Pre-fill debconf database'],
			[sub { $self->_processAptRepositories(); }, 'Processing APT repositories if any'],
			[sub { $self->_processAptPreferences(); },  'Processing APT preferences if any'],
			[sub { $self->_updatePackagesIndex(); },    'Updating packages index']
		);

		my $step = 1;
		my $nbSteps = scalar @steps;

		for (@steps) {
			$rs = step($_->[0], $_->[1], $nbSteps, $step);
			return $rs if $rs;
			$step++;
		}
	}

	$self->{'eventManager'}->trigger('afterPreBuild');
}

=item installPackages()

 Install Debian packages

 Return int 0 on success, other on failure

=cut

sub installPackages
{
	my $self = $_[0];

	iMSCP::Dialog->getInstance()->endGauge();

	my $rs = $self->{'eventManager'}->trigger(
		'beforeInstallPackages', $self->{'packagesToInstall'}, $self->{'packagesToInstallDelayed'}
	);
	return $rs if $rs;

	# Prevent the package manager to start some services itself using the policy layer interface.
	# Apache2: This prevents failures such as when nginx is installed after Apache2 which is already listening on port 80...
	# Bind9: This avoid error when resolvconf is not configured yet
	my $file = iMSCP::File->new('filename' => '/usr/sbin/policy-rc.d');
	$rs = $file->set(<<EOF);
#/bin/sh
initscript=\$1
action=\$2
if [ "\$action" = "start" ] && { [ "\$initscript" = "apache2" ] || [ "\$initscript" = "bind9" ] ; } then
        exit 101;
fi
exit 0
EOF
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0755);
	return $rs if $rs;

	my $preseed = iMSCP::Getopt->preseed;

	for($self->{'packagesToInstall'}, $self->{'packagesToInstallDelayed'}) {
		if(@{$_}) {
			my @command = ();

			unless($preseed || $main::noprompt || ! iMSCP::ProgramFinder::find('debconf-apt-progress')) {
				push @command, 'debconf-apt-progress --logstderr --';
			}

			unshift @command, 'UCF_FORCE_CONFFMISS=1 '; # Force installation of missing conffiles which are managed by UCF

			if($main::forcereinstall) {
				push @command, "apt-get -y -o DPkg::Options::='--force-confnew' -o DPkg::Options::='--force-confmiss' " .
					"--reinstall --auto-remove --purge install @{$_}";
			} else {
				push @command, "apt-get -y -o DPkg::Options::='--force-confnew' -o DPkg::Options::='--force-confmiss' " .
					"--auto-remove --purge install @{$_}";
			}

			my ($stdout, $stderr);
			$rs = execute("@command", ($preseed || $main::noprompt) ? \$stdout : undef, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			error('Unable to install packages') if $rs && ! $stderr;
			return $rs if $rs;
		}
	}

	# Delete '/usr/sbin/policy-rc.d file
	$rs = $file->delFile();
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterInstallPackages');
}

=item uninstallPackages()

 Uninstall Debian packages not longer needed

 Return int 0 on success, other on failure

=cut

sub uninstallPackages
{
	my $self = $_[0];

	eval "use List::MoreUtils qw(uniq); 1";
	fatal($@) if $@;

	# Remove any duplicate entry
	# Do not try to remove any packages which were scheduled for installation
	@{$self->{'packagesToUninstall'}} = grep {
		not $_ ~~ [@{$self->{'packagesToInstall'}}, @{$self->{'packagesToInstallDelayed'}}]
	} uniq(@{$self->{'packagesToUninstall'}});

	# Do not try to remove packages which are no longer available on the system or not installed
	if(@{$self->{'packagesToUninstall'}}) {
		my ($stdout, $stderr);
		my $rs = execute(
			"dpkg-query -W -f='\${Package}/\${Status}\n' @{$self->{'packagesToUninstall'}}", \$stdout, \$stderr
		);
		error($stderr) if $stderr && $rs > 1;
		return $rs if $rs > 1;

		@{$self->{'packagesToUninstall'}} = grep { m%^(.*?)/install% && ($_ =  $1) } split /\n/, $stdout;
	}

	my $rs = $self->{'eventManager'}->trigger('beforeUninstallPackages', $self->{'packagesToUninstall'});
	return $rs if $rs;

	if(@{$self->{'packagesToUninstall'}}) {
		my ($stdout, $stderr);
		my $command = 'apt-get';
		my $preseed = iMSCP::Getopt->preseed;

		unless($preseed || $main::noprompt || ! iMSCP::ProgramFinder::find('debconf-apt-progress')) {
			iMSCP::Dialog->getInstance()->endGauge();

			$command = 'debconf-apt-progress --logstderr -- ' . $command;
		}

		my $rs = execute(
			"$command -y remove @{$self->{'packagesToUninstall'}} --auto-remove --purge --no-install-recommends",
			($preseed || $main::noprompt) ? \$stdout : undef, \$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		error('Unable to uninstall packages') if $rs && ! $stderr;
		return $rs if $rs;
	}

	$self->{'eventManager'}->trigger('afterUninstallPackages');
}

=item postBuild()

 Process postBuild tasks

 Return int 0 on success, other on failure

=cut

sub postBuild
{
	# Make sure that PHP modules are enabled
	if(iMSCP::ProgramFinder::find('php5enmod')) {
		my($stdout, $stderr);
		my $rs = execute('php5enmod gd imap intl json mcrypt mysql mysqli mysqlnd pdo pdo_mysql', \$stdout, \$stderr);
		debug($stdout) if $stdout;
		unless($rs ~~ [0, 2]) {
			error($stderr) if $stderr;
			return $rs;
		}
	}

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return autoinstaller::Adapter::DebianAdapter

=cut

sub _init
{
	my $self = $_[0];

	$self->{'eventManager'} = iMSCP::EventManager->getInstance();

	delete $ENV{'DEBCONF_FORCE_DIALOG'};
	$ENV{'DEBIAN_FRONTEND'} = 'noninteractive' if iMSCP::Getopt->preseed || iMSCP::Getopt->noprompt;

	$self->{'repositorySections'} = ['main', 'non-free'];
	$self->{'preRequiredPackages'} = [
		'aptitude', 'debconf-utils', 'dialog', 'liblist-moreutils-perl', 'libxml-simple-perl', 'wget', 'rsync'
	];
	$self->{'aptRepositoriesToRemove'} = { };
	$self->{'aptRepositoriesToAdd'} = { };
	$self->{'aptPreferences'} = [];
	$self->{'packagesToInstall'} = [];
	$self->{'packagesToInstallDelayed'} = [];
	$self->{'packagesToUninstall'} = [];

	$self->_updateAptSourceList() and fatal('Unable to configure APT packages manager') unless $main::skippackages;

	$self;
}

=item _buildPackageList()

 Build lists of Debian packages to uninstalle and install

 Return int 0 on success, other on failure

=cut

sub _buildPackageList
{
	my $self = $_[0];

	my $lsbRelease = iMSCP::LsbRelease->getInstance();
	my $dist = lc($lsbRelease->getId(1));
	my $codename = lc($lsbRelease->getCodename(1));
	my $pkgFile = "$FindBin::Bin/docs/" . ucfirst($dist) . "/packages-$codename.xml";

	eval "use XML::Simple; 1";
	fatal($@) if $@;

	my $xml = XML::Simple->new(NoEscape => 1);
	my $pkgList = eval { $xml->XMLin($pkgFile, ForceArray => [ 'package', 'package_delayed' ]) };

	unless($@) {
		# For each package section find in package list
		for (sort keys %{$pkgList}) {
			if(exists $pkgList->{$_}->{'package'} || exists $pkgList->{$_}->{'package_delayed'}) { # Simple list of packages to install
				if(exists $pkgList->{$_}->{'package'}) {
					push @{$self->{'packagesToInstall'}}, @{$pkgList->{$_}->{'package'}};
				}

				if(exists $pkgList->{$_}->{'package_delayed'}) {
					push @{$self->{'packagesToInstallDelayed'}}, @{$pkgList->{$_}->{'package_delayed'}};
				}
			} else { # List of alternative package ( software ) available for installation
				my $defaultAlt = delete $pkgList->{$_}->{'default'};
				my $selectedAlt = $main::questions{ uc($_) . '_SERVER' } || $main::imscpConfig{ uc($_) . '_SERVER' };
				my $forceDialog = ($selectedAlt) ? 0 : 1;
				$selectedAlt = $defaultAlt if $forceDialog;

				my @alts = keys %{$pkgList->{$_}}; # List of alternative softwares

				if(not $selectedAlt ~~ @alts) { # Handle wrong or deprecated entry case
					$selectedAlt = $defaultAlt;
					$forceDialog = 1;
				}

				if(exists $pkgList->{$_}->{$selectedAlt}->{'allow_switch_to'}) {
					if($pkgList->{$_}->{$selectedAlt}->{'allow_switch_to'} ne '') {
						my @allowedAlts = (split(',', $pkgList->{$_}->{$selectedAlt}->{'allow_switch_to'}), $selectedAlt);
						@alts = grep { $_ ~~ @allowedAlts } @alts;
					} else {
						@alts = ($selectedAlt);
					}
				}

				@alts = sort @alts;

				# Ask user for alternative software to install if needed
				if(@alts > 1 && ($forceDialog || $main::reconfigure ~~ [$_, 'servers', 'all'])) {
					iMSCP::Dialog->getInstance()->set('no-cancel', '');
					(my $ret, $selectedAlt) = iMSCP::Dialog->getInstance()->radiolist(<<EOF, [@alts], $selectedAlt);

Please, choose the i-MSCP server implementation you want use for the $_ service:
EOF
					return $ret if $ret; # Handle ESC case

					iMSCP::Dialog->getInstance()->set('no-cancel');
				}

				if($_ eq 'sql') {
					my ($stdout, $stderr);
					my $rs = execute("$main::imscpConfig{'CMD_RM'} -f /var/lib/mysql/debian-*.flag", \$stdout, \$stderr);
					debug($stdout) if $stdout;
					error($stderr) if $rs && $stderr;
					return $rs if $rs;
				}

				for my $alt(@alts) {
					if($alt ne $selectedAlt) {
						# APT repository to remove
						if(exists $pkgList->{$_}->{$alt}->{'repository'}) {
							$self->{'aptRepositoriesToRemove'}->{$pkgList->{$_}->{$alt}->{'repository'}} = {
								'repository' => $pkgList->{$_}->{$alt}->{'repository'},
								'repository_origin' => $pkgList->{$_}->{$alt}->{'repository_origin'}
							};
						}

						# Packages to uninstall
						if(exists $pkgList->{$_}->{$alt}->{'package'}) {
							push @{$self->{'packagesToUninstall'}}, @{$pkgList->{$_}->{$alt}->{'package'}};
						}

						if(exists $pkgList->{$_}->{$alt}->{'package_delayed'}) {
							push @{$self->{'packagesToUninstall'}}, @{$pkgList->{$_}->{$alt}->{'package_delayed'}};
						}
					}
				}

				# APT preferences to add
				if(exists $pkgList->{$_}->{$selectedAlt}->{'pinning_package'}) {
					push @{$self->{'aptPreferences'}}, {
						'pinning_package' => $pkgList->{$_}->{$selectedAlt}->{'pinning_package'},
						'pinning_pin' => $pkgList->{$_}->{$selectedAlt}->{'pinning_pin'} || undef,
						'pinning_pin_priority' => $pkgList->{$_}->{$selectedAlt}->{'pinning_pin_priority'} || undef,
					};
				}

				# APT repository to add
				if(exists $pkgList->{$_}->{$selectedAlt}->{'repository'}) {
					$self->{'aptRepositoriesToAdd'}->{$pkgList->{$_}->{$selectedAlt}->{'repository'}} = {
						'repository' => $pkgList->{$_}->{$selectedAlt}->{'repository'},
						'repository_key_uri' => $pkgList->{$_}->{$selectedAlt}->{'repository_key_uri'} || undef,
						'repository_key_id' => $pkgList->{$_}->{$selectedAlt}->{'repository_key_id'} || undef,
						'repository_key_srv' => $pkgList->{$_}->{$selectedAlt}->{'repository_key_srv'} || undef
					};
				}

				# Packages to install
				if(exists $pkgList->{$_}->{$selectedAlt}->{'package'}) {
					push @{$self->{'packagesToInstall'}}, @{$pkgList->{$_}->{$selectedAlt}->{'package'}};
				}

				if(exists $pkgList->{$_}->{$selectedAlt}->{'package_delayed'}) {
					push @{$self->{'packagesToInstallDelayed'}}, @{$pkgList->{$_}->{$selectedAlt}->{'package_delayed'}};
				}

				# Set server implementation to use
				$main::questions{ uc($_) . '_SERVER' } = $selectedAlt;
			}
		}
	} else {
		error($@);
		return 1;
	}

	0;
}

=item _updateAptSourceList()

 Add required repository sections to repositories that support them

 Return int 0 on success, other on failure

=cut

sub _updateAptSourceList
{
	my $self = $_[0];

	my $sourceListFile = iMSCP::File->new('filename' => '/etc/apt/sources.list');

	my $rs = $sourceListFile->copyFile('/etc/apt/sources.list.bkp') unless -f '/etc/apt/sources.list.bkp';
	return $rs if $rs;

	my $sourceListFileContent = $sourceListFile->get();

	unless (defined $sourceListFileContent) {
		error('Unable to read /etc/apt/sources.list file');
		return 1;
	}

	my ($foundSection, $stdout, $stderr);

	for(@{$self->{'repositorySections'}}) {
		my $section = $_;
		my @seen = ();

		while($sourceListFileContent =~ /^deb\s+(?<uri>(?:https?|ftp)[^\s]+)\s+(?<distrib>[^\s]+)\s+(?<components>.+)$/gm) {
			my %repository = %+;

			if("$repository{'uri'} $repository{'distrib'}" ~~ @seen) {
				debug("Repository '$repository{'uri'} $repository{'distrib'}' already checked for '$section' section");
				next;
			}

			debug("Checking repository '$repository{'uri'} $repository{'distrib'}' for '$section' section");

			unless($sourceListFileContent =~ /^deb\s+$repository{'uri'}\s+\b$repository{'distrib'}\b\s+.*\b$section\b/m) {
				my $uri = "$repository{'uri'}/dists/$repository{'distrib'}/$section/";
				$rs = execute("wget --spider $uri", \$stdout, \$stderr);
				debug($stdout) if $stdout;
				debug($stderr) if $rs && $stderr;

				unless ($rs) {
					$foundSection = 1;
					debug("Enabling section '$section' on '$repository{'uri'} $repository{'distrib'}'");
					$sourceListFileContent =~ s/^($&)$/$1 $section/m;
				}
			} else {
				debug("Section '$section' already enabled on '$repository{'uri'} $repository{'distrib'}'");
				$foundSection = 1;
			}

			push @seen, "$repository{'uri'} $repository{'distrib'}";
		}

		unless($foundSection) {
			error("Unable to found repository supporting '$section' section");
			return 1;
		}
	}

	$rs = $sourceListFile->set($sourceListFileContent);
	return $rs if $rs;

	$rs = $sourceListFile->save();
	return $rs if $rs;

	0;
}

=item _processAptRepositories()

 Process APT repositories

 Return int 0 on success, other on failure

=cut

sub _processAptRepositories
{
	my $self = $_[0];

	if(%{$self->{'aptRepositoriesToRemove'}} || %{$self->{'aptRepositoriesToAdd'}}) {
		my $file = iMSCP::File->new('filename' => '/etc/apt/sources.list');

		my $rs = $file->copyFile('/etc/apt/sources.list.bkp') unless -f '/etc/apt/sources.list.bkp';
		return $rs if $rs;

		my $fileContent = $file->get();

		unless (defined $fileContent) {
			error('Unable to read /etc/apt/sources.list file');
			return 1;
		}

		delete $self->{'aptRepositoriesToRemove'}->{$_} for keys %{$self->{'aptRepositoriesToAdd'}};

		for(keys %{$self->{'aptRepositoriesToRemove'}}) {
			if($fileContent =~ /^$_/m) {
				# Remove the repository from the sources.list file
				(my $regexp = $_) =~ s/deb/(?:deb|deb-src)/; # Ensure backward compatibility (deb-src)
				$fileContent =~ s/\n?$regexp?\n?//gm;
			}
		}

		# Add needed APT repositories
		for(keys %{$self->{'aptRepositoriesToAdd'}}) {
			if($fileContent !~ /^$_/m) {
				my @cmd = ();
				my $repository = $self->{'aptRepositoriesToAdd'}->{$_};

				$fileContent .= "\n$_\n";

				if($repository->{'repository_key_srv'}) { # Add the repository key from the given server, using key id
					if($repository->{'repository_key_id'}) {
						@cmd = (
							'apt-key adv --recv-keys --keyserver',
							escapeShell($repository->{'repository_key_srv'}),
							escapeShell($repository->{'repository_key_id'})
						);
					} else {
						error("The repository_key_id entry for the '$_' repository was not found");
						return 1;
					}
				} elsif($repository->{'repository_key_uri'}) { # Add the repository key by fetching it from the given URI
					@cmd = ('wget -qO-', escapeShell($repository->{'repository_key_uri'}), '| apt-key add -');
				}

				if(@cmd) {
					my ($stdout, $stderr);
					$rs = execute("@cmd", \$stdout, \$stderr);
					debug($stdout) if $stdout;
					error($stderr) if $stderr && $rs;
					return $rs if $rs;
				}
			}
		}

		$rs = $file->set($fileContent);
		return $rs if $rs;

		$file->save();
		return $rs if $rs;
	}

	0;
}

=item _processAptPreferences()

 Process apt preferences

 Return 0 on success, other on failure

=cut

sub _processAptPreferences
{
	my $self = $_[0];

	my $fileContent = '';
	my $rs = 0;

	for(@{$self->{'aptPreferences'}}) {
		unless(exists $_->{'pinning_pin'} || exists $_->{'pinning_pin_priority'}) {
			error('One of these attributes is missing: pinning_pin or pinning_pin_priority');
			return 1;
		}

		$fileContent .= "Package: $_->{'pinning_package'}\n";
		$fileContent .= "Pin: $_->{'pinning_pin'}\n";
		$fileContent .= "Pin-Priority: $_->{'pinning_pin_priority'}\n\n";
	}

	my $file = iMSCP::File->new('filename' => '/etc/apt/preferences.d/imscp');

	if($fileContent ne '') {
		$rs = $file->set($fileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		$rs = $file->mode(0644);
		return $rs if $rs;
	} elsif(-f '/etc/apt/preferences.d/imscp') {
		$rs = $file->delFile();
		return $rs if $rs;
	}

	0;
}

=item _updatePackagesIndex()

 Update Debian packages index

 Return int 0 on success, other on failure

=cut

sub _updatePackagesIndex
{
	my $self = $_[0];

	my $command = 'apt-get';
	my ($stdout, $stderr);
	my $preseed = iMSCP::Getopt->preseed;

	unless($preseed || $main::noprompt || ! iMSCP::ProgramFinder::find('debconf-apt-progress')) {
		iMSCP::Dialog->getInstance()->endGauge() if iMSCP::ProgramFinder::find('dialog');

		$command = 'debconf-apt-progress --logstderr -- ' . $command;
	}

	my $rs = execute("$command -y update", ($preseed || $main::noprompt) ? \$stdout : undef, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error('Unable to update package index from remote repository') if $rs && ! $stderr;

	$rs
}

=item _prefillDebconfDatabase()

 Pre-fill debconf database

 Return int 0 on success, other on failure

=cut

sub _prefillDebconfDatabase
{
	my $self = $_[0];

	my $sqlServer = $main::questions{'SQL_SERVER'} || undef;
	my $poServer = $main::questions{'PO_SERVER'} || undef;
	my $sqlServerPackageName = undef;

	if(defined $sqlServer) {
		if($sqlServer ne 'remote_server') {
			if($sqlServer =~ /^(mysql|mariadb|percona)_(\d+\.\d+)$/) {
				$sqlServerPackageName = "$1-server" . ($1 eq 'percona' ? '-server' : '') . "-$2";
			} else {
				error("Unknown SQL server package name: $sqlServer");
				return 1;
			}
		}
	} else {
		error('Unable to retrieve SQL server name');
		return 1;
	}

	# Most values below are not really important because i-MSCP will override them after package installation
	my $mailname = `hostname --fqdn 2>/dev/null` || 'localdomain';
	chomp $mailname;

	my $hostname = ($mailname ne 'localdomain') ? $mailname : 'localhost';

	my $domain = `hostname --domain 2>/dev/null` || 'localdomain';
	chomp $domain;

	# From postfix package postfix.config script
	my $destinations;
	if ($mailname eq $hostname) {
		$destinations = join ', ', ($mailname, 'localhost.' . $domain, ', localhost');
	} else {
		$destinations = join ', ', ($mailname, $hostname, 'localhost.' . $domain . ', localhost');
	}

	my $selectionsFileContent = <<EOF;
postfix postfix/main_mailer_type select Internet Site
postfix postfix/mailname string $mailname
postfix postfix/destinations string $destinations
proftpd-basic shared/proftpd/inetd_or_standalone select standalone
EOF

	if(defined $poServer) {
		if($poServer eq 'courier') {
			$selectionsFileContent .= <<EOF;
courier-base courier-base/webadmin-configmode boolean false
courier-ssl courier-ssl/certnotice note
EOF
		} elsif($poServer eq 'dovecot') {
	$selectionsFileContent .= <<EOF;
dovecot-core dovecot-core/create-ssl-cert boolean false
EOF
		}
	} else {
		error('Unable to retrieve PO server name');
		return 1;
	}

	if(iMSCP::Getopt->preseed && $sqlServer ne 'remote_server') {
		$selectionsFileContent .= <<EOF;
$sqlServerPackageName mysql-server/root_password password $main::questions{'DATABASE_PASSWORD'}
$sqlServerPackageName mysql-server/root_password_again password $main::questions{'DATABASE_PASSWORD'}
EOF
	}

	my $debconfSelectionsFile = File::Temp->new();
	print $debconfSelectionsFile $selectionsFileContent;

	my ($stdout, $stderr);
	my $rs = execute("debconf-set-selections $debconfSelectionsFile", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $rs && $stderr;
	error('Unable to pre-fill debconf database') if $rs && ! $stderr;

	$rs;
}

=back

=head1 AUTHORS

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
