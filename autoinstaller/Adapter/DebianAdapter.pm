=head1 NAME

 autoinstaller::Adapter::DebianAdapter - Debian autoinstaller adapter class

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright 2010-2015 by internet Multi Server Control Panel
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
# @copyright   2010-2015 by i-MSCP | http://i-mscp.net
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

	fatal('Not a Debian like system') unless iMSCP::ProgramFinder::find($command);

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
		if($main::imscpConfig{'DATABASE_PASSWORD'} ne '' && not $main::reconfigure ~~ [ 'sql', 'servers', 'all' ]) {
			$ENV{'DEBIAN_PRIORITY'} = 'critical';
		}

		my @steps = (
			[ sub { $self->_buildPackageList(); },       'Building list of packages to install/uninstall' ],
			[ sub { $self->_prefillDebconfDatabase(); }, 'Pre-fill debconf database' ],
			[ sub { $self->_processAptRepositories(); }, 'Processing APT repositories if any' ],
			[ sub { $self->_processAptPreferences(); },  'Processing APT preferences if any' ],
			[ sub { $self->_updatePackagesIndex(); },    'Updating packages index' ]
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

	# Remove packages which must be pre-removed
	my $rs = $self->uninstallPackages($self->{'packagesToPreUninstall'});
	return $rs if $rs;

	$rs = $self->{'eventManager'}->trigger(
		'beforeInstallPackages', $self->{'packagesToInstall'}, $self->{'packagesToInstallDelayed'}
	);
	return $rs if $rs;

	my $preseed = iMSCP::Getopt->preseed;

	for my $packages($self->{'packagesToInstall'}, $self->{'packagesToInstallDelayed'}) {
		if(@{$packages}) {
			my @command = ();

			unless($preseed || $main::noprompt || ! iMSCP::ProgramFinder::find('debconf-apt-progress')) {
				push @command, 'debconf-apt-progress --logstderr --';
			}

			unshift @command, 'UCF_FORCE_CONFFMISS=1 '; # Force installation of missing conffiles which are managed by UCF

			if($main::forcereinstall) {
				push @command, "apt-get -y -o DPkg::Options::='--force-confnew' -o DPkg::Options::='--force-confmiss' " .
					"--reinstall --auto-remove --purge --no-install-recommends --force-yes install @{$packages}";
			} else {
				push @command, "apt-get -y -o DPkg::Options::='--force-confnew' -o DPkg::Options::='--force-confmiss' " .
					"--auto-remove --purge --no-install-recommends --force-yes install @{$packages}";
			}

			my ($stdout, $stderr);
			$rs = execute("@command", ($preseed || $main::noprompt) ? \$stdout : undef, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			error('Unable to install packages') if $rs && ! $stderr;
			return $rs if $rs;
		}
	}

	$self->{'eventManager'}->trigger('afterInstallPackages');
}

=item uninstallPackages([ \@packages ])

 Uninstall Debian packages

 Param array \@packages OPTIONAL List of packages to uninstall ( default is list from the packagesToUninstall attribute )
 Return int 0 on success, other on failure

=cut

sub uninstallPackages
{
	my ($self, $packages) = @_;

	$packages ||= $self->{'packagesToUninstall'};

	eval "use List::MoreUtils qw(uniq); 1";
	fatal($@) if $@;

	# Remove any duplicate entry
	# Do not try to remove any packages which were scheduled for installation
	@{$packages} = grep {
		not $_ ~~ [ @{$self->{'packagesToInstall'}}, @{$self->{'packagesToInstallDelayed'}} ]
	} uniq(@{$packages});

	# Do not try to remove packages which are no longer available
	if(@{$packages}) {
		my ($stdout, $stderr);
		my $rs = execute("LANG=C dpkg-query -W -f='\${Package}\n' @{$packages} 2>/dev/null", \$stdout, \$stderr);
		error($stderr) if $stderr && $rs > 1;
		return $rs if $rs > 1;

		@{$packages} = split /\n/, $stdout;
	}

	my $rs = $self->{'eventManager'}->trigger('beforeUninstallPackages', @{$packages});
	return $rs if $rs;

	if(@{$packages}) {
		my $preseed = iMSCP::Getopt->preseed;
		my @command = ();

		unless($preseed || $main::noprompt || ! iMSCP::ProgramFinder::find('debconf-apt-progress')) {
			iMSCP::Dialog->getInstance()->endGauge();
			push @command, 'debconf-apt-progress --logstderr --';
		}

		push @command, "apt-get -y --auto-remove --purge --no-install-recommends remove @{$packages}";

		my ($stdout, $stderr);
		my $rs = execute("@command", ($preseed || $main::noprompt) ? \$stdout : undef, \$stderr);
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
	my $self = shift;

	# Needed to fix #IP-1246
	if(iMSCP::ProgramFinder::find('php5dismod')) {
		for (
			'apc', 'curl', 'gd', 'imap', 'intl', 'json', 'mcrypt', 'mysqlnd', 'mysqli', 'mysql', 'opcache', 'pdo',
			'pdo_mysql'
		) {
			my($stdout, $stderr);
			my $rs = execute("php5dismod $_", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			unless($rs ~~ [ 0, 2 ]) {
				error($stderr) if $stderr;
				return $rs;
			}
		}
	}

	# Enable needed PHP modules ( only if they are available )
	if(iMSCP::ProgramFinder::find('php5enmod')) {
		for (
			'apc', 'curl', 'gd', 'imap', 'intl', 'json', 'mcrypt', 'mysqlnd/10', 'mysqli', 'mysql', 'opcache', 'pdo/10',
			'pdo_mysql'
		) {
			my($stdout, $stderr);
			my $rs = execute("php5enmod $_", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			unless($rs ~~ [ 0, 2 ]) {
				error($stderr) if $stderr;
				return $rs;
			}
		}
	}

	$self->_setupInitScriptPolicyLayer('disable');
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

	$self->{'repositorySections'} = [ 'main', 'non-free' ];
	$self->{'preRequiredPackages'} = [
		'aptitude', 'debconf-utils', 'dialog', 'libbit-vector-perl', 'libclass-insideout-perl', 'liblist-moreutils-perl',
		 'libscalar-defer-perl', 'libxml-simple-perl', 'wget', 'rsync'
	];
	$self->{'aptRepositoriesToRemove'} = [];
	$self->{'aptRepositoriesToAdd'} = [];
	$self->{'aptPreferences'} = [];
	$self->{'packagesToInstall'} = [];
	$self->{'packagesToInstallDelayed'} = [];
	$self->{'packagesToPreUninstall'} = [];
	$self->{'packagesToUninstall'} = [];

	unless($main::skippackages) {
		($self->_setupInitScriptPolicyLayer('enable') == 0 ) or die('Unable to setup initscript policy layer');
		($self->_updateAptSourceList() == 0) or die('Unable to configure APT packages manager');
	}

	$self;
}

=item _setupInitScriptPolicyLayer($action)

 Enable or disable initscript policy layer

 See https://people.debian.org/~hmh/invokerc.d-policyrc.d-specification.txt
 See man invoke-rc.d

 Param string $action Action ( enable|disable )
 Return int 0 on success, other on failure

=cut

sub _setupInitScriptPolicyLayer
{
	my $action = $_[1];
	my $rs = 0;

	if($action eq 'enable') {
		# Prevents invoke-rc.d ( which is invoked by package maintainer scripts ) to start some services
		# apache2 and nginx: This prevents failures such as "bind() to 0.0.0.0:80 failed (98: Address already in use"
		# bind9: This avoid error when resolvconf is not configured yet
		my $file = iMSCP::File->new( filename => '/usr/sbin/policy-rc.d' );
		my $rs = $file->set(<<EOF);
#/bin/sh
initscript=\$1
action=\$2

if [ "\$action" = "start" ] || [ "\$action" = "restart" ]; then
	for i in apache2 bind9 nginx; do
		if [ "\$initscript" = "\$i" ]; then
			exit 101;
		fi
	done
fi
EOF

		$rs ||= $file->save();
		$rs ||= $file->mode(0755);
	} elsif($action eq 'disable') {
		if(-f '/usr/sbin/policy-rc.d') {
			$rs = iMSCP::File->new( filename => '/usr/sbin/policy-rc.d' )->delFile();
		}
	} else {
		error('Unknown action');
		$rs = 1;
	}

	$rs;
}

=item _buildPackageList()

 Build lists of Debian packages to uninstall and install

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
	my $pkgList = eval { $xml->XMLin($pkgFile, ForceArray => [ 'package', 'package_delayed', 'package_conflict' ]) };
	if($@) {
		error($@);
		return 1;
	}

	for my $section(sort keys %{$pkgList}) { # Simple list of packages to install
		if($pkgList->{$section}->{'package'} || $pkgList->{$section}->{'package_delayed'}) {
			if($pkgList->{$section}->{'package'}) {
				push @{$self->{'packagesToInstall'}}, @{$pkgList->{$section}->{'package'}};
			}

			if($pkgList->{$section}->{'package_delayed'}) {
				push @{$self->{'packagesToInstallDelayed'}}, @{$pkgList->{$section}->{'package_delayed'}};
			}

			next;
		}

		# List of alternative services
		my $dAlt = delete $pkgList->{$section}->{'default'};
		my $sAlt = $main::questions{ uc($section) . '_SERVER' } || $main::imscpConfig{ uc($section) . '_SERVER' };
		my $forceDialog = $sAlt ? 0 : 1;
		$sAlt = $dAlt if $forceDialog;

		my @alts = keys %{$pkgList->{$section}};

		if(not $sAlt ~~ @alts) { # Handle wrong or deprecated entry case
			$sAlt = $dAlt;
			$forceDialog = 1;
		}

		if($pkgList->{$section}->{$sAlt}->{'allow_switch_to'}) {
			@alts = grep { $_ ~~ @alts } split(',', $pkgList->{$section}->{$sAlt}->{'allow_switch_to'}), $sAlt;
		}

		@alts = sort @alts;

		# Ask user service to install if needed
		if(@alts > 1 && ($forceDialog || $main::reconfigure ~~ [ $section, 'servers', 'all' ])) {
			iMSCP::Dialog->getInstance()->set('no-cancel', '');
			(my $ret, $sAlt) = iMSCP::Dialog->getInstance()->radiolist(<<EOF, [ @alts ], $sAlt);

Please, choose the server you want use for the $section service:
EOF
			return $ret if $ret; # Handle ESC case
			iMSCP::Dialog->getInstance()->set('no-cancel');
		}

		if($section eq 'sql') {
			my $rs = execute('rm -f /var/lib/mysql/debian-*.flag', \my $stdout, \my $stderr);
			debug($stdout) if $stdout;
			error($stderr) if $rs && $stderr;
			return $rs if $rs;
		}

		for my $alt(@alts) {
			if($alt ne $sAlt) {
				if($pkgList->{$section}->{$alt}->{'repository'}) { # APT repository to remove
					push @{$self->{'aptRepositoriesToRemove'}}, $pkgList->{$section}->{$alt}->{'repository'};
				}

				if($pkgList->{$section}->{$alt}->{'repository_conflict'}) {
					push @{$self->{'aptRepositoriesToRemove'}}, $pkgList->{$section}->{$alt}->{'repository_conflict'};
				}

				# Packages to uninstall
				if($pkgList->{$section}->{$alt}->{'package'}) {
					push @{$self->{'packagesToUninstall'}}, @{$pkgList->{$section}->{$alt}->{'package'}};
				}
				if($pkgList->{$section}->{$alt}->{'package_delayed'}) {
					push @{$self->{'packagesToUninstall'}}, @{$pkgList->{$section}->{$alt}->{'package_delayed'}};
				}
			}
		}

		if($pkgList->{$section}->{$sAlt}->{'pinning_package'}) { # APT preferences to add
			push @{$self->{'aptPreferences'}}, {
				'pinning_package' => $pkgList->{$section}->{$sAlt}->{'pinning_package'},
				'pinning_pin' => $pkgList->{$section}->{$sAlt}->{'pinning_pin'} || undef,
				'pinning_pin_priority' => $pkgList->{$section}->{$sAlt}->{'pinning_pin_priority'} || undef,
			};
		}

		if($pkgList->{$section}->{$sAlt}->{'repository_conflict'}) { # Conflicting repository which must be removed
			push @{$self->{'aptRepositoriesToRemove'}}, $pkgList->{$section}->{$sAlt}->{'repository_conflict'};
		}

		if($pkgList->{$section}->{$sAlt}->{'repository'}) { # APT repository to add
			push @{$self->{'aptRepositoriesToAdd'}}, {
				'repository' => $pkgList->{$section}->{$sAlt}->{'repository'},
				'repository_key_uri' => $pkgList->{$section}->{$sAlt}->{'repository_key_uri'} || undef,
				'repository_key_id' => $pkgList->{$section}->{$sAlt}->{'repository_key_id'} || undef,
				'repository_key_srv' => $pkgList->{$section}->{$sAlt}->{'repository_key_srv'} || undef
			};
		}

		if($pkgList->{$section}->{$sAlt}->{'package_conflict'}) { # Conflicting packages which must be pre-removed
			push @{$self->{'packagesToPreUninstall'}}, @{$pkgList->{$section}->{$sAlt}->{'package_conflict'}};
		}

		# Packages to install
		if($pkgList->{$section}->{$sAlt}->{'package'}) {
			push @{$self->{'packagesToInstall'}}, @{$pkgList->{$section}->{$sAlt}->{'package'}};
		}
		if($pkgList->{$section}->{$sAlt}->{'package_delayed'}) {
			push @{$self->{'packagesToInstallDelayed'}}, @{$pkgList->{$section}->{$sAlt}->{'package_delayed'}};
		}

		$main::questions{ uc($section) . '_SERVER' } = $sAlt; # Set server to use
	}

	0;
}

=item _updateAptSourceList()

 Add required sections to repositories that support them

 Return int 0 on success, other on failure

=cut

sub _updateAptSourceList
{
	my $self = $_[0];

	my $file = iMSCP::File->new( filename => '/etc/apt/sources.list' );

	my $rs = $file->copyFile('/etc/apt/sources.list.bkp') unless -f '/etc/apt/sources.list.bkp';
	return $rs if $rs;

	my $fileContent = $file->get();

	unless (defined $fileContent) {
		error('Unable to read /etc/apt/sources.list file');
		return 1;
	}

	my ($foundSection, $stdout, $stderr);

	for(@{$self->{'repositorySections'}}) {
		my $section = $_;
		my @seen = ();

		while($fileContent =~ /^deb\s+(?<uri>(?:https?|ftp)[^\s]+)\s+(?<distrib>[^\s]+)\s+(?<components>.+)$/gm) {
			my %repository = %+;

			if("$repository{'uri'} $repository{'distrib'}" ~~ @seen) {
				debug("Repository '$repository{'uri'} $repository{'distrib'}' already checked for '$section' section");
				next;
			}

			debug("Checking repository '$repository{'uri'} $repository{'distrib'}' for '$section' section");

			unless($fileContent =~ /^deb\s+$repository{'uri'}\s+\b$repository{'distrib'}\b\s+.*\b$section\b/m) {
				my $uri = "$repository{'uri'}/dists/$repository{'distrib'}/$section/";
				$rs = execute("wget --spider $uri", \$stdout, \$stderr);
				debug($stdout) if $stdout;
				debug($stderr) if $rs && $stderr;

				unless ($rs) {
					$foundSection = 1;
					debug("Enabling section '$section' on '$repository{'uri'} $repository{'distrib'}'");
					$fileContent =~ s/^($&)$/$1 $section/m;
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

	$rs = $file->set($fileContent);
	$rs ||= $file->save();
}

=item _processAptRepositories()

 Process APT repositories

 Return int 0 on success, other on failure

=cut

sub _processAptRepositories
{
	my $self = $_[0];

	if(@{$self->{'aptRepositoriesToRemove'}} || @{$self->{'aptRepositoriesToAdd'}}) {
		my ($stdout, $stderr);
		my @cmd = ();

		my $file = iMSCP::File->new( filename => '/etc/apt/sources.list' );

		my $rs = $file->copyFile('/etc/apt/sources.list.bkp') unless -f '/etc/apt/sources.list.bkp';
		return $rs if $rs;

		my $fileContent = $file->get();
		unless (defined $fileContent) {
			error('Unable to read /etc/apt/sources.list file');
			return 1;
		}

		# Filter list of repositories which must not be removed
		for my $repository(@{$self->{'aptRepositoriesToAdd'}}) {
			@{$self->{'aptRepositoriesToRemove'}} = grep {
				 $repository->{'repository'} !~ /^$_/
			} @{$self->{'aptRepositoriesToRemove'}};
		}

		for my $repository(@{$self->{'aptRepositoriesToRemove'}}) {
			# Remove the repository from the sources.list file
			(my $regexp = $repository) =~ s/deb/(?:#\\s*)?(?:deb|deb-src)/;
			$fileContent =~ s/^\n?$regexp\n//gm;
		}

		# Add needed APT repositories
		for my $repository(@{$self->{'aptRepositoriesToAdd'}}) {
			if($fileContent !~ /^$repository->{'repository'}/m) {
				$fileContent .= "\n$repository->{'repository'}\n";

				@cmd = ();

				if($repository->{'repository_key_srv'}) { # Add the repository key from the given server, using key id
					if($repository->{'repository_key_id'}) {
						@cmd = (
							'apt-key adv --recv-keys --keyserver',
							escapeShell($repository->{'repository_key_srv'}),
							escapeShell($repository->{'repository_key_id'})
						);
					} else {
						error("The repository_key_id entry for the '$repository->{'repository'}' repository was not found");
						return 1;
					}
				} elsif($repository->{'repository_key_uri'}) { # Add the repository key by fetching it from the given URI
					@cmd = ('wget -qO-', escapeShell($repository->{'repository_key_uri'}), '| apt-key add -');
				}

				if(@cmd) {
					$rs = execute("@cmd", \$stdout, \$stderr);
					debug($stdout) if $stdout;
					error($stderr) if $stderr && $rs;
					return $rs if $rs;
				}
			}
		}

		# Save new sources.list file
		$rs = $file->set($fileContent);
		$rs ||= $file->save();
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

	for(@{$self->{'aptPreferences'}}) {
		unless(exists $_->{'pinning_pin'} || exists $_->{'pinning_pin_priority'}) {
			error('One of these attributes is missing: pinning_pin or pinning_pin_priority');
			return 1;
		}

		$fileContent .= "Package: $_->{'pinning_package'}\n";
		$fileContent .= "Pin: $_->{'pinning_pin'}\n";
		$fileContent .= "Pin-Priority: $_->{'pinning_pin_priority'}\n\n";
	}

	my $file = iMSCP::File->new( filename => '/etc/apt/preferences.d/imscp' );

	if($fileContent ne '') {
		my $rs = $file->set($fileContent);
		$rs ||= $file->save();
		$rs ||= $file->mode(0644);
		return $rs if $rs;
	} elsif(-f '/etc/apt/preferences.d/imscp') {
		my $rs = $file->delFile();
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
dovecot-core dovecot-core/create-ssl-cert boolean true
dovecot-core dovecot-core/ssl-cert-name string localhost
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
mysql-community-server mysql-community-server/root-pass password $main::questions{'DATABASE_PASSWORD'}
mysql-community-server mysql-community-server/re-root-pass password $main::questions{'DATABASE_PASSWORD'}
EOF
	}

	my $debconfSelectionsFile = File::Temp->new();
	print $debconfSelectionsFile $selectionsFileContent;

	my $rs = execute("debconf-set-selections $debconfSelectionsFile", \my $stdout, \my $stderr);
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
__END__
