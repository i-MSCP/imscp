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
# @category		i-MSCP
# @copyright	2010-2013 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @author		Laurent Declercq <l.declercq@nuxwin.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package autoinstaller::Adapter::Debian;

use strict;
use warnings;
use Symbol;
use iMSCP::Debug;
use iMSCP::Execute 'execute';
use iMSCP::Dialog;
use iMSCP::File;
use List::MoreUtils qw(uniq);
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
	my($rs, $stdout, $stderr);

	fatal('Not a Debian like system') if checkCommandAvailability('apt-get');

	my $command = 'apt-get';

	if(! %main::preseed && ! $main::noprompt && ! checkCommandAvailability('debconf-apt-progress')) {
		$command = 'debconf-apt-progress --logstderr -- ' . $command;
	}

	$rs = execute("$command -y install @{$self->{'preRequiredPackages'}}", (%main::preseed || $main::noprompt) ? \$stdout : undef, \$stderr);
	debug($stdout) if $stdout;
	error("Unable to install pre-required packages: $stderr") if $rs;

	$rs;
}

=item preBuild()

 Process preBuild tasks.

 Return int 0 on success, other on failure

=cut

sub preBuild
{
	my $self = shift;
	my $rs = 0;

	unless($main::skippackages) {
		$rs |= $self->_preparePackagesList();
		$rs |= $self->_updateAptSourceList();
		$rs |= $self->_addExternalRepositories();
		$rs |= $self->_updatePackagesIndex();
	}

	$rs;
}

=item uninstallPackages()

 Uninstall Debian packages not needed i-MSCP.

 Return int 0 on success, other on failure

=cut

sub uninstallPackages
{
	my $self = shift;

	my ($stdout, $stderr);
	my $command = 'apt-get';

	iMSCP::Dialog->factory()->endGauge(); # Really needed !

	if(! %main::preseed&& ! $main::noprompt && ! checkCommandAvailability('debconf-apt-progress')) {
		$command = 'debconf-apt-progress --logstderr -- ' . $command;
	}

	my $rs = execute(
		"$command -y remove @{$self->{'packagesToUninstall'}} --auto-remove",
		(%main::preseed || $main::noprompt) ? \$stdout : undef, \$stderr
	);
	debug($stdout) if $stdout;

	if($rs) {
		error("Unable to uninstall packages: $stderr");
		return $rs;
	}

	0;
}

=item installPackages()

 Install Debian packages for i-MSCP.

 Return int 0 on success, other on failure

=cut

sub installPackages
{
	my $self = shift;

	my ($stdout, $stderr);
	my $command = 'apt-get';

	iMSCP::Dialog->factory()->endGauge(); # Really needed !

	if(! %main::preseed&& ! $main::noprompt && ! checkCommandAvailability('debconf-apt-progress')) {
		$command = 'debconf-apt-progress --logstderr -- ' . $command;
	}

	my $rs = execute("$command -y install @{$self->{'packagesToInstall'}}", (%main::preseed || $main::noprompt) ? \$stdout : undef, \$stderr);
	debug($stdout) if $stdout;

	if($rs) {
		error("Unable to install packages: $stderr");
		return $rs;
	}

	0;
}

=item postBuild()

 Process postBuild tasks.

 Return int 0 on success, other on failure

=cut

sub postBuild
{
	my $self = shift;

	# Add user server selection in imscp.conf file by creating/updating server variables
	$main::imscpConfig{uc($_) . '_SERVER'} = lc($self->{'userSelection'}->{$_}) for keys %{$self->{'userSelection'}};

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by new(). Initialize instance.

 Return autoinstaller::Adapter::Debian

=cut

sub _init
{
	my $self = shift;

	$self->{'preRequiredPackages'} = ['wget', 'dialog', 'libxml-simple-perl', 'liblist-moreutils-perl'];
	$self->{'packagesToInstall'} = [];
	$self->{'packagesToUninstall'} = [];
	$self->{'externalRepositories'} = [];
	$self->{'repositorySections'} = ['main', 'non-free'];

	$self;
}

=item _preparePackagesList()

 Prepare lists of Debian packages to be uninstalled and installed.

 Return int 0 on success, other on failure

=cut

sub _preparePackagesList
{
	my $self = shift;
	my $lsbRelease = iMSCP::LsbRelease->new();
	my $distribution = lc($lsbRelease->getId(1));
	my $codename = lc($lsbRelease->getCodename(1));
	my $packagesFile = "$FindBin::Bin/docs/" . ucfirst($distribution) . '/' . $distribution . '-packages-' . $codename . '.xml';
	my $rs;

	eval "use XML::Simple; 1";
	fatal('Unable to load perl module XML::Simple') if($@);

	my $xml = XML::Simple->new(NoEscape => 1);
	my $data = eval { $xml->XMLin($packagesFile, KeyAttr => 'name') };

	for(sort keys %{$data}) {
		if($data->{$_}->{'alternative'}) {
			my $service  = $_;

			my $default = $data->{$service}->{'alternative'}->{'default'} || '';
			delete $data->{$service}->{'alternative'}->{'default'};

			my @alternative = sort keys %{$data->{$service}->{'alternative'}};
			my $serviceName = uc($service) . '_SERVER';
			my $currentServer = exists $main::preseed{'SERVERS'}
				? $main::preseed{'SERVERS'}->{$serviceName} : $main::imscpConfig{$serviceName} || '';

			my $server = '';

			# Only ask for server to use if not already defined or not found in list of available server
			# or if user asked for reconfiguration
			 if($main::reconfigure || ! $currentServer || ! ($currentServer ~~ @alternative)) {
				if(@alternative > 1) { # Do no ask for server if only one is available
					do {
						iMSCP::Dialog->factory->set('no-cancel', '');
						$server = iMSCP::Dialog->factory()->radiolist(
"
\\Z4\\Zu" . uc($_) . " service\\Zn

Please, choose the server you want use for the $_ service:
",
							[@alternative],
							$currentServer ? $currentServer : $default
							# uncoment after dependencies check is implemented
							#'Not Used'
						);

						if(
							ref $data->{$service}->{'alternative'}->{$server} eq 'HASH' &&
							exists $data->{$service}->{'alternative'}->{$server}->{'repository'}
						) {
							$rs = iMSCP::Dialog->factory()->yesno(
"
\\Z4\\ZuExternal repository\\Zn

The server '$server' requires usage of an external repository:

$data->{$service}->{'alternative'}->{$server}->{'repository'}

Do you agree?
"
							);
						}

						$server = '' if $rs;
					} while (! $server);
				} else {
					$server = pop(@alternative);
				}
			} else {
				$server = $currentServer;
			}

			$self->{'userSelection'}->{$service} = $server eq 'Not used' ? 'no' : $server;

			for(@alternative) {
				# Remove unselected server
				if($server ne $_) {
					# Add package to purge
					for my $attr (keys %{$data->{$service}->{'alternative'}->{$_}}) {
						delete $data->{$service}->{'alternative'}->{$_}->{$attr} if $attr ne 'package';
					}
					$self->_parseHash($data->{$service}->{'alternative'}->{$_}, 'packagesToUninstall');
					delete($data->{$service}->{'alternative'}->{$_});
				} elsif(
					ref $data->{$service}->{'alternative'}->{$_} eq 'HASH' &&
                   	exists $data->{$service}->{'alternative'}->{$_}->{'repository'}
				) { # Set needed external repository if any
					push (
						@{$self->{'externalRepositories'}},
						{
							'repository' => $data->{$service}->{'alternative'}->{$_}->{'repository'},
							'repository_key_uri' => $data->{$service}->{'alternative'}->{$_}->{'repository_key_uri'} || undef,
							'repository_key_id' => $data->{$service}->{'alternative'}->{$_}->{'repository_key_id'} || undef,
							'repository_key_srv' => $data->{$service}->{'alternative'}->{$_}->{'repository_key_srv'} || undef
						}
					);
				}

				for my $attr (keys %{$data->{$service}->{'alternative'}->{$_}}) {
					delete $data->{$service}->{'alternative'}->{$_}->{$attr} if $attr ne 'package';
				}
			}
		}

		$self->_parseHash($data->{$_});
	}

	@{$self->{'packagesToUninstall'}} = uniq(
		grep {  ! ( $_ ~~ @{$self->{'packagesToInstall'}} ) } @{$self->{'packagesToUninstall'}}
	);

	0;
}

=item _updateAptSourceList()

 Add required repository sections to repositories that support them.

 Return int 0 on success, other on failure

=cut

sub _updateAptSourceList
{
	my $self = shift;

	iMSCP::Dialog->factory()->infobox("\nProcessing apt sources list");

	my $file = iMSCP::File->new(filename => '/etc/apt/sources.list');

	$file->copyFile('/etc/apt/sources.list.bkp') unless -f '/etc/apt/sources.list.bkp';
	my $content = $file->get();

	unless ($content) {
		error('Unable to read /etc/apt/sources.list file');
		return 1;
	}

	my ($foundSection, $needUpdate, $rs, $stdout, $stderr);

	for(@{$self->{'repositorySections'}}) {
		my $section = $_;

		while($content =~ /^deb\s+(?<uri>(?:https?|ftp)[^\s]+)\s+(?<distrib>[^\s]+)\s+(?<components>.+)$/mg) {
			my %repos = %+;

			# is a section available in repository?
			unless($repos{'components'} =~ /\s?$section(\s|$)/) {
				my $uri = "$repos{uri}/dists/$repos{'distrib'}/$section/";
				$rs = execute("wget --spider $uri", \$stdout, \$stderr);
				debug($stdout) if $stdout;
				debug($stderr) if $stderr;

				unless ($rs) {
					$foundSection = 1;
					debug("Enabling section '$section' on $repos{uri}");
					$content =~ s/^($&)$/$1 $section/mg;
					$needUpdate = 1;
				}
			} else {
				debug("Section '$section' is already enabled on $repos{uri}");
				$foundSection = 1;
			}
		}

		unless($foundSection) {
			error("Unable to found repository supporting '$section' packages");
			return 1;
		}
	}

	if($needUpdate) {
		$file->set($content);
		$file->save() and return 1;
	}

	0;
}

=item _addExternalRepositories()

 Add external repositories to the sources.list file and their gpg keys.

 Return int 0 on success, other on failure.

=cut

sub _addExternalRepositories
{
	my $self = shift;

	if(@{$self->{'externalRepositories'}}) {

		my $file = iMSCP::File->new(filename => '/etc/apt/sources.list');

		$file->copyFile('/etc/apt/sources.list.bkp') unless -f '/etc/apt/sources.list.bkp';
		my $content = $file->get();

		unless ($content) {
			error('Unable to read /etc/apt/sources.list file');
			return 1;
		}

		my ($rs, $cmd, $stdout, $stderr, $needUpdate);

		for(@{$self->{'externalRepositories'}}) {
			if($content !~ /^deb\s+$_->{'repository'}$/mg) {
				$content .= "\ndeb $_->{'repository'}\ndeb-src $_->{'repository'}\n";

				if($_->{'repository_key_srv'}) {
					if($_->{'repository_key_id'}) {
						$cmd = "apt-key adv --recv-keys --keyserver $_->{'repository_key_srv'} $_->{'repository_key_id'}";
					} else {
						error("The repository_key_id entry for the '$_->{'repository'} repository was not found");
						return 1;
					}
				} elsif($_->{'repository_key_uri'}) {
					$cmd = "wget -qO- $_->{'repository_key_uri'} | apt-key add -"
				} else {
					error("The repository_key_uri entry for the '$_->{'repository'}' repository was not found");
					return 1;
				}

				$rs = execute($cmd, \$stdout, \$stderr);
				debug($stdout) if $stdout;
				error($stderr) if $stderr && $rs;
				return $rs if $rs;

				$needUpdate = 1;
			}
		}

		if($needUpdate) {
			$file->set($content);
			$file->save() and return 1;
		}
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
	my ($rs, $stdout, $stderr);
	my $command = 'apt-get';

	if(! %main::preseed && ! $main::noprompt &&  ! checkCommandAvailability('debconf-apt-progress')) {
		$command = 'debconf-apt-progress --logstderr -- ' . $command;
	}

	$rs = execute("$command -y update", (%main::preseed || $main::noprompt) ? \$stdout : undef, \$stderr);
	debug($stdout) if $stdout;

	if($rs) {
		error('Unable to update package index from remote repository: $stderr');
		return $rs;
	}

	0;
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
