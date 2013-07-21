#!/usr/bin/perl

=head1 NAME

 autoinstaller::Adapter::Debian - Debian autoinstaller adapter class

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright 2010-2013 by internet Multi Server Control Panel
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
# @author      Daniel Andreca <sci2tech@gmail.com>
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package autoinstaller::Adapter::Debian;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Execute;
use iMSCP::Dialog;
use iMSCP::File;
use iMSCP::Stepper;
use autoinstaller::Common 'checkCommandAvailability';
use parent 'autoinstaller::Adapter::Abstract';

=head1 DESCRIPTION

 i-MSCP distro autoinstaller adapter implementation for Debian.

=head1 PUBLIC METHODS

=over 4

=item installPreRequiredPackages()

 Install pre-required packages.

 Return int 0 on success, other on failure

=cut

sub installPreRequiredPackages
{
	my $self = shift;

	my $command = 'apt-get';

	fatal('Not a Debian like system') if checkCommandAvailability($command);

	# Ensure packages index is up to date
	my $rs = $self->_updatePackagesIndex();
	return $rs if $rs;

	if(! %main::preseed && ! $main::noprompt && ! checkCommandAvailability('debconf-apt-progress')) {
		$command = 'debconf-apt-progress --logstderr -- ' . $command;
	}

	my ($stdout, $stderr);
	$rs = execute(
		"$command -y install @{$self->{'preRequiredPackages'}}",
		(%main::preseed || $main::noprompt) ? \$stdout : undef, \$stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error('Unable to install pre-required packages') if $rs && ! $stderr;

	$rs;
}

=item preBuild()

 Process preBuild tasks.

 Return int 0 on success, other on failure

=cut

sub preBuild
{
	my $self = shift;

	unless($main::skippackages) {
		iMSCP::Dialog->factory()->endGauge();

		if($main::imscpConfig{'DATABASE_PASSWORD'} ne '' && not $main::reconfigure ~~ ['sql', 'servers', 'all']) {
			$ENV{'DEBIAN_PRIORITY'} = 'critical';
		}

		my @steps = (
			[sub { $self->_preparePackagesList() }, 'Generating list of packages to uninstall and install'],
			[sub { $self->_processExternalRepositories() }, 'Process external repositories if any'],
			[sub { $self->_processAptPreferencesFile() }, 'Process APT preferences file if any'],
			[sub { $self->_updatePackagesIndex() }, 'Updating packages index files']
		);

		my $rs = 0;
		my $step = 1;
		my $nbSteps = scalar @steps;

		for (@steps) {
			$rs = step($_->[0], $_->[1], $nbSteps, $step);
			return $rs if $rs;
			$step++;
		}
	}

	0;
}

=item uninstallPackages()

 Uninstall Debian packages not longer needed by i-MSCP.

 Return int 0 on success, other on failure

=cut

sub uninstallPackages
{
	my $self = shift;

	my $rs = iMSCP::HooksManager->getInstance()->trigger('beforeUninstallPackages', $self->{'packagesToUninstall'});
	return $rs if $rs;

	if(@{$self->{'packagesToUninstall'}}) {
		my ($stdout, $stderr);
		my $command = 'apt-get';

		iMSCP::Dialog->factory()->endGauge();

		if(! %main::preseed && ! $main::noprompt && ! checkCommandAvailability('debconf-apt-progress')) {
			$command = 'debconf-apt-progress --logstderr -- ' . $command;
		}

		my $rs = execute(
			"$command -y remove @{$self->{'packagesToUninstall'}} --auto-remove --purge",
			(%main::preseed || $main::noprompt) ? \$stdout : undef, \$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		error('Unable to uninstall packages') if $rs && ! $stderr;
		return $rs if $rs;
	}

	iMSCP::HooksManager->getInstance()->trigger('afterUninstallPackages');
}

=item installPackages()

 Install Debian packages for i-MSCP.

 Return int 0 on success, other on failure

=cut

sub installPackages
{
	my $self = shift;

	my $rs = iMSCP::HooksManager->getInstance()->trigger('beforeInstallPackages', $self->{'packagesToInstall'});
	return $rs if $rs;

	my ($stdout, $stderr);
	my $command = 'apt-get';

	iMSCP::Dialog->factory()->endGauge();

	if(! %main::preseed&& ! $main::noprompt && ! checkCommandAvailability('debconf-apt-progress')) {
		$command = 'debconf-apt-progress --logstderr -- ' . $command;
	}

	$rs = execute(
		"$command -y -o DPkg::Options::='--force-confdef' install @{$self->{'packagesToInstall'}} --auto-remove --purge",
		(%main::preseed || $main::noprompt) ? \$stdout : undef, \$stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error('Unable to install packages') if $rs && ! $stderr;
	return $rs if $rs;

	iMSCP::HooksManager->getInstance()->trigger('afterInstallPackages');
}

=item postBuild()

 Process postBuild tasks.

 Return int 0 on success, other on failure

=cut

sub postBuild
{
	my $self = shift;

	# Add user servers selection in imscp.conf file by creating/updating server variables
	$main::imscpConfig{uc($_) . '_SERVER'} = lc($self->{'userSelection'}->{$_}) for keys %{$self->{'userSelection'}};

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by getInstance(). Initialize instance.

 Return autoinstaller::Adapter::Debian

=cut

sub _init
{
	my $self = shift;

	delete $ENV{'DEBCONF_FORCE_DIALOG'};

	$self->{'repositorySections'} = ['main', 'non-free'];
	$self->{'preRequiredPackages'} = [
		'aptitude', 'debconf-utils', 'dialog', 'liblist-moreutils-perl', 'libxml-simple-perl', 'wget'
	];
	$self->{'externalRepositoriesToRemove'} = {};
	$self->{'externalRepositoriesToAdd'} = {};
	$self->{'aptPreferences'} = [];
	$self->{'packagesToInstall'} = [];
	$self->{'packagesToUninstall'} = [];

	$self->_updateAptSourceList() and fatal('Unable to configure APT packages manager') if ! $main::skippackages;

	if(%main::preseed) {
		iMSCP::HooksManager->getInstance()->register('beforeInstallPackages', sub { _debconfSetSelections(); });
	}

	$self;
}

=item _preparePackagesList()

 Prepare lists of Debian packages to be uninstalled and installed.

 Return int 0 on success, other on failure

=cut

sub _preparePackagesList
{
	my $self = shift;
	my $lsbRelease = iMSCP::LsbRelease->getInstance();
	my $distribution = lc($lsbRelease->getId(1));
	my $codename = lc($lsbRelease->getCodename(1));
	my $packagesFile = "$FindBin::Bin/docs/" . ucfirst($distribution) . "/packages-$codename.xml";
	my $rs = 0;

	eval "use XML::Simple; 1";
	fatal("Unable to load the XML::Simple perl module: $@") if $@;

	my $xml = XML::Simple->new(NoEscape => 1);
	my $data = eval { $xml->XMLin($packagesFile, KeyAttr => 'name') };

	fatal("Unable to parse $packagesFile: $@") if $@;

	for(sort keys %{$data}) {
		if($data->{$_}->{'alternative'}) {
			my $service  = $_;

			my $default = $data->{$service}->{'alternative'}->{'default'} || '';
			delete $data->{$service}->{'alternative'}->{'default'};

			my @alternative = sort keys %{$data->{$service}->{'alternative'}};
			my $serviceName = uc($service) . '_SERVER';

			my $currentServer = exists $main::imscpConfig{$serviceName} ? $main::imscpConfig{$serviceName} : '';
			my $newServer = exists $main::preseed{'SERVERS'} ? $main::preseed{'SERVERS'}->{$serviceName} : $currentServer;

			$newServer = '' if not $newServer ~~ @alternative;

			my $server = '';

			# Only ask for server to use if not already defined or not found in list of available servers
			# or if user asked for reconfiguration
			 if($main::reconfigure ~~ [$service, 'servers', 'all'] || ! $newServer) {
				if(@alternative > 1) { # Do no ask for server if only one is available

					my @humanAlternative = @alternative;
					s/_/ /g for @humanAlternative; # Humanize
					$newServer =~ s/_/ /g; # Humanize
					$default =~ s/_/ /g; # Humanize

					iMSCP::Dialog->factory->set('no-cancel', '');

					do {
						$server = iMSCP::Dialog->factory()->radiolist(
"
\\Z4\\Zu" . uc($_) . " service\\Zn

Please, choose the server you want use for the $_ service:
",
							[@humanAlternative],
							$newServer || $default
						);

						$server =~ s/ /_/g; # Normalize

						if(
							ref $data->{$service}->{'alternative'}->{$server} eq 'HASH' &&
							exists $data->{$service}->{'alternative'}->{$server}->{'repository'}
						) {
							$rs = iMSCP::Dialog->factory()->yesno(
"
\\Z4\\ZuExternal repository\\Zn

The $service service requires usage of an external repository:

$data->{$service}->{'alternative'}->{$server}->{'repository'}

Do you agree?
"
							);
						}

						$server = '' if $rs;
						$rs = 0;
					} while (! $server);

					iMSCP::Dialog->factory->set('no-cancel', undef);

				} else {
					$server = pop(@alternative);
				}
			} else {
				$server = $newServer;
			}

			$self->{'userSelection'}->{$service} = $server eq 'Not used' ? 'no' : $server;

			for(@alternative) {
				# Remove unselected server
				if($server ne $_) {
					if(ref $data->{$service}->{'alternative'}->{$_} eq 'HASH' &&
						exists $data->{$service}->{'alternative'}->{$_}->{'repository'}
					) {

						$self->{'externalRepositoriesToRemove'}->{$data->{$service}->{'alternative'}->{$_}->{'repository'}} = {
							'repository' => $data->{$service}->{'alternative'}->{$_}->{'repository'},
							'repository_origin' => $data->{$service}->{'alternative'}->{$_}->{'repository_origin'}
						};
					}

					for my $attr (keys %{$data->{$service}->{'alternative'}->{$_}}) {
						delete $data->{$service}->{'alternative'}->{$_}->{$attr} if $attr ne 'package';
					}

					if($server ne $currentServer) {
						$self->_parseHash($data->{$service}->{'alternative'}->{$_}, 'packagesToUninstall');
					}

					delete($data->{$service}->{'alternative'}->{$_});

					next;
				}

				# Add external repositories if any
				if(
					ref $data->{$service}->{'alternative'}->{$_} eq 'HASH' &&
                   	exists $data->{$service}->{'alternative'}->{$_}->{'repository'}
				) {
					$self->{'externalRepositoriesToAdd'}->{$data->{$service}->{'alternative'}->{$_}->{'repository'}} = {
							'repository' => $data->{$service}->{'alternative'}->{$_}->{'repository'},
							'repository_key_uri' => $data->{$service}->{'alternative'}->{$_}->{'repository_key_uri'} || undef,
							'repository_key_id' => $data->{$service}->{'alternative'}->{$_}->{'repository_key_id'} || undef,
							'repository_key_srv' => $data->{$service}->{'alternative'}->{$_}->{'repository_key_srv'} || undef
					}
				}

				# Add apt preferences if any
				if(
					ref $data->{$service}->{'alternative'}->{$_} eq 'HASH' &&
                   	exists $data->{$service}->{'alternative'}->{$_}->{'pinning_package'}
				) {
					push(
						@{$self->{'aptPreferences'}},
						{
							'pinning_package' => $data->{$service}->{'alternative'}->{$_}->{'pinning_package'},
							'pinning_pin' => $data->{$service}->{'alternative'}->{$_}->{'pinning_pin'} || undef,
							'pinning_pin_priority' => $data->{$service}->{'alternative'}->{$_}->{'pinning_pin_priority'} || undef,
						}
					);
				}

				# keep only packages
				for my $attr (keys %{$data->{$service}->{'alternative'}->{$_}}) {
					delete $data->{$service}->{'alternative'}->{$_}->{$attr} if $attr ne 'package';
				}
			}
		}

		$self->_parseHash($data->{$_});
	}

	# Build list of packages to uninstall

	eval "use List::MoreUtils qw(uniq); 1";
	fatal("Unable to load the List::MoreUtils perl module: $@") if $@;

	@{$self->{'packagesToUninstall'}} = uniq(@{$self->{'packagesToUninstall'}});

	# Do not remove a package scheduled for installation
	@{$self->{'packagesToUninstall'}} = grep {
		not $_ ~~  @{$self->{'packagesToInstall'}}
	} @{$self->{'packagesToUninstall'}};

	# This test is needed to be sure to not try to remove package no longer available
	if(@{$self->{'packagesToUninstall'}}) {
		my ($stdout, $stderr);
		$rs = execute("dpkg-query -W -f='\${Package}/\${Status}\n' @{$self->{'packagesToUninstall'}}", \$stdout, \$stderr);
		error($stderr) if $stderr && $rs > 1;
		return $rs if $rs > 1;

		$self->{'packagesToUninstall'} = [];

		for(split "\n", $stdout) {
			push @{$self->{'packagesToUninstall'}}, $1 if m%^(.*?)/install%;
		}
	}

	0;
}

=item _updateAptSourceList()

 Add required repository sections to repositories that support them.

 Return int 0 on success, other on failure

=cut

sub _updateAptSourceList
{
	my $self = shift;

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

=item _processExternalRepositories()

 Process external repositories.

 Return int 0 on success, other on failure.

=cut

sub _processExternalRepositories
{
	my $self = shift;

	if(%{$self->{'externalRepositoriesToRemove'}} || %{$self->{'externalRepositoriesToAdd'}}) {

		my $sourceListFile = iMSCP::File->new('filename' => '/etc/apt/sources.list');

		my $rs = $sourceListFile->copyFile('/etc/apt/sources.list.bkp') unless -f '/etc/apt/sources.list.bkp';
		return $rs if $rs;

		my $sourceListFileContent = $sourceListFile->get();

		unless (defined $sourceListFileContent) {
			error('Unable to read /etc/apt/sources.list file');
			return 1;
		}

		delete $self->{'externalRepositoriesToRemove'}->{$_} for keys %{$self->{'externalRepositoriesToAdd'}};

		my ($cmd, $stdout, $stderr);

		for(keys %{$self->{'externalRepositoriesToRemove'}}) {
			if($sourceListFileContent =~  /$_/) {
				my $repository = $self->{'externalRepositoriesToRemove'}->{$_};

				# Retrieve any packages installed from the repository to remove
				# TODO This command is too slow ; A better alternative must be found
				$rs = execute(
					"aptitude search '?installed?origin($repository->{'repository_origin'})' | cut -b 5- | cut -d ' ' -f 1",
					\$stdout, \$stderr
				);
				debug($stdout) if $stdout;
				error($stderr) if $stderr && $rs;
				return $rs if $rs;

				# Schedule packages for deletion
				@{$self->{'packagesToUninstall'}} = (@{$self->{'packagesToUninstall'}}, split("\n", $stdout)) if $stdout;

				# Remove the repository from the sources.list file
				$sourceListFileContent =~ s/\n?(deb|deb-src)\s+$_\n?//gm;
			}
		}

		eval "use List::MoreUtils qw(uniq); 1";
		fatal('Unable to load the List::MoreUtils perl module') if $@;

		# Remove duplicate entries
		@{$self->{'packagesToUninstall'}} = uniq(@{$self->{'packagesToUninstall'}});

		# Add needed external repositories
		for(keys %{$self->{'externalRepositoriesToAdd'}}) {
			if($sourceListFileContent !~ /^deb\s+$_$/m) {
				my $repository = $self->{'externalRepositoriesToAdd'}->{$_};

				$sourceListFileContent .= "\ndeb $_\ndeb-src $_\n";

				if($repository->{'repository_key_srv'}) { # Add the repository key from the given server, using key id
					if($repository->{'repository_key_id'}) {
						$cmd = "apt-key adv --recv-keys --keyserver $repository->{'repository_key_srv'} $repository->{'repository_key_id'}";
					} else {
						error("The repository_key_id entry for the '$_' repository was not found");
						return 1;
					}
				} elsif($repository->{'repository_key_uri'}) { # Add the repository key by fetching it from the given URI
					$cmd = "wget -qO- $repository->{'repository_key_uri'} | apt-key add -";
				} else {
					error("The repository_key_uri entry for the '$_' repository was not found");
					return 1;
				}

				$rs = execute($cmd, \$stdout, \$stderr);
				debug($stdout) if $stdout;
				error($stderr) if $stderr && $rs;
				return $rs if $rs;
			}
		}

		$rs = $sourceListFile->set($sourceListFileContent);
		return $rs if $rs;

		$sourceListFile->save();
		return $rs if $rs;
	}

	0;
}

=item _processAptPreferencesFile()

 Process apt preferences file.

 Return 0 on success, other on failure

=cut

sub _processAptPreferencesFile
{
	my $self = shift;
	my $fileContent = '';
	my $rs = 0;

	for(@{$self->{'aptPreferences'}}) {
		if(! exists $_->{'pinning_pin'} || ! exists $_->{'pinning_pin_priority'}) {
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

 Update Debian packages index.

 Return int 0 on success, other on failure

=cut

sub _updatePackagesIndex
{
	my $self = shift;

	my $command = 'apt-get';
	my ($stdout, $stderr);

	if(! %main::preseed && ! $main::noprompt && ! checkCommandAvailability('debconf-apt-progress')) {
		$command = 'debconf-apt-progress --logstderr -- ' . $command;
	}

	my $rs = execute("$command -y update", (%main::preseed || $main::noprompt) ? \$stdout : undef, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error('Unable to update package index from remote repository') if $rs && ! $stderr;

	$rs
}

=item _debconfSetSelection()

 Pre-fill debconf database using values from i-MSCP preseed file

 Return int 0 on success, other on failure

=cut

sub _debconfSetSelections
{
	my $self = shift;

	my $sqlServer = $main::preseed{'SERVERS'}->{'SQL_SERVER'} || undef;
	my $poServer = $main::preseed{'SERVERS'}->{'PO_SERVER'} || undef;
	my $sqlServerPackageName = undef;

	if(defined $sqlServer) {
		if( $sqlServer =~ /^(mysql|mariadb)_(\d{1,2}\.\d)$/) {
			$sqlServerPackageName = "$1-server-$2";
		} else {
			error("Unknown SQL server package name: $sqlServer");
			return 1;
		}
	} else {
		error('Unable to retrieve SQL server name in your preseed file');
		return 1;
	}

	my $selectionsFileContent = <<EOF;
$sqlServerPackageName mysql-server/root_password password $main::preseed{'DATABASE_PASSWORD'}
$sqlServerPackageName mysql-server/root_password_again password $main::preseed{'DATABASE_PASSWORD'}
postfix postfix/main_mailer_type select Internet Site
postfix postfix/destinations string $main::preseed{'SERVER_HOSTNAME'}, $main::preseed{'SERVER_HOSTNAME'}.local, localhost
postfix	postfix/mailname string $main::preseed{'SERVER_HOSTNAME'}
proftpd-basic shared/proftpd/inetd_or_standalone select standalone
EOF

	if(defined $poServer) {
		if($poServer eq 'courier') {
			$selectionsFileContent .= "courier-base courier-base/webadmin-configmode boolean false\n";
			$selectionsFileContent .= "courier-ssl courier-ssl/certnotice note\n";
		}
	} else {
		error('Unable to retrieve PO server name in your preseed file');
		return 1;
	}

	my $debconfSelectionsFile = iMSCP::File->new('filename' => '/tmp/imscp-debconf-selections');

	my $rs= $debconfSelectionsFile->set($selectionsFileContent);
	return $rs if $rs;

	$rs = $debconfSelectionsFile->save();
	return $rs if $rs;

	$rs = $debconfSelectionsFile->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$rs = $debconfSelectionsFile->mode('0600');
	return $rs if $rs;

	my ($stdout, $stderr);
	$rs = execute('debconf-set-selections /tmp/imscp-debconf-selections', \$stdout, $stderr);
	debug($stdout) if $stdout;
	error($stderr) if $rs && $stderr;
	error('Unable to insert entries in debconf database') if $rs && ! $stderr;

	# In any case, the file must be deleted
	$rs = $debconfSelectionsFile->delFile();
	return $rs if $rs;

	$rs;
}

=item _parseHash(\%hash, $target)

 Parse the given hash and put result in the target array.

 Param hash_ref $hash Reference to a hash
 Param string Target array name (packagesToUninstall|packagesToInstall)
 Return undef

=cut

sub _parseHash
{
	my $self = shift;
	my $hash = shift;
	my $target = shift || 'packagesToInstall';

	for(values %{$hash}) {
		if(ref $_  eq 'HASH') {
			$self->_parseHash($_, $target);
		} elsif(ref $_  eq 'ARRAY') {
			$self->_parseArray($_, $target);
		} else {
			push @{$self->{$target}}, $_;
		}
	}

	undef;
}

=item _parseArray(\@array, $target)

 Parse the given array and put the result in the target array.

 Param array_ref $array Reference to an array
 Param string Target array (packagesToUninstall|packagesToInstall)
 Return undef
=cut

sub _parseArray
{
	my $self = shift;
	my $array = shift;
	my $target = shift || 'packagesToInstall';

	for(@{$array}) {
		if(ref $_ eq 'HASH') {
			$self->_parseHash($_, $target);
		} elsif(ref $_ eq 'ARRAY') {
			$self->_parseArray($_, $target);
		} else {
			push @{$self->{$target}}, $_;
		}
	}

	undef;
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
