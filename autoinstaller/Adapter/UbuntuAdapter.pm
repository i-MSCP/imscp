#!/usr/bin/perl

=head1 NAME

 autoinstaller::Adapter::UbuntuAdapter - Ubuntu autoinstaller adapter class

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
# @Author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package autoinstaller::Adapter::UbuntuAdapter;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::LsbRelease;
use iMSCP::Execute;
use parent 'autoinstaller::Adapter::DebianAdapter';

=head1 DESCRIPTION

 i-MSCP distro autoinstaller adapter implementation for Ubuntu.

 See the autoinstaller::Adapter::Debian autoinstaller adapter for more information.

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return autoinstaller::Adapter::UbuntuAdapter

=cut

sub _init
{
	my $self = $_[0];

	delete $ENV{'DEBCONF_FORCE_DIALOG'};

	$self->{'repositorySections'} = ['main', 'universe', 'multiverse'];
	$self->{'preRequiredPackages'} = [
		'aptitude', 'debconf-utils', 'dialog', 'liblist-moreutils-perl', 'libxml-simple-perl', 'wget', 'resolvconf'
	];

	if(iMSCP::LsbRelease->getInstance()->getRelease(1) < 12.10) {
		push @{$self->{'preRequiredPackages'}}, 'python-software-properties';
	} else {
		push @{$self->{'preRequiredPackages'}}, 'software-properties-common';
	}

	$self->{'externalRepositoriesToRemove'} = { };
	$self->{'externalRepositoriesToAdd'} = { };
	$self->{'aptPreferences'} = [];
	$self->{'packagesToInstall'} = [];
	$self->{'packagesToUninstall'} = [];

	$self->_updateAptSourceList() and fatal('Unable to configure APT packages manager') if ! $main::skippackages;

	$self;
}

=item _processExternalRepositories()

 Process external repositories

 Return int 0 on success, other on failure

=cut

sub _processExternalRepositories
{
	my $self = $_[0];

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

		my $distroRelease = iMSCP::LsbRelease->getInstance()->getRelease(1);
		my (@cmd, $stdout, $stderr);

		for(keys %{$self->{'externalRepositoriesToRemove'}}) {
			if(/^ppa:/ || $sourceListFileContent =~ /^$_/m) {
				my $repository = $self->{'externalRepositoriesToRemove'}->{$_};

				my @cmd = (
					'aptitude search', escapeShell("?installed?origin($repository->{'repository_origin'})"),
					"| cut -b 5- | cut -d ' ' -f 1",
				);
				# Retrieve any packages installed from the repository to remove
				$rs = execute("@cmd", \$stdout, \$stderr);
				debug($stdout) if $stdout;
				error($stderr) if $stderr && $rs;
				return $rs if $rs;

				# Schedule packages for deletion
				@{$self->{'packagesToUninstall'}} = (@{$self->{'packagesToUninstall'}}, split("\n", $stdout)) if $stdout;

				if($distroRelease > 10.04) {
					@cmd = ('add-apt-repository -y -r', escapeShell($_));
					$rs = execute("@cmd", \$stdout, \$stderr);
					debug($stdout) if $stdout;
					error($stderr) if $stderr && $rs;
					return $rs if $rs;
				} else {
					if(m%^ppa:(.*)/(.*)%) { # PPA repository
						my $ppaFile = "/etc/apt/sources.list.d/$1-$2-*";

						if(glob $ppaFile) {
							$rs = execute("$main::imscpConfig{'CMD_RM'} $ppaFile", \$stdout, \$stderr);
							debug($stdout) if $stdout;
							error($stderr) if $stderr && $rs;
							return $rs if $rs;
						}
					} else { # Normal repository
						# Remove the repository from the sources.list file
						$sourceListFileContent = $sourceListFile->get();
						$sourceListFileContent =~ s/\n?$_\n?//gm;

						$rs = $sourceListFile->set($sourceListFileContent);
						return $rs if $rs;

						$rs = $sourceListFile->save();
						return $rs if $rs;
					}
				}
			}
		}

		eval "use List::MoreUtils qw(uniq); 1";
		fatal('Unable to load the List::MoreUtils perl module') if $@;

		# Remove any duplicate entries
		@{$self->{'packagesToUninstall'}} = uniq(@{$self->{'packagesToUninstall'}});

		# Add needed external repositories
		for(keys %{$self->{'externalRepositoriesToAdd'}}) {
			if(/^ppa:/ || $sourceListFileContent !~ /^$_/m) {
				my $repository = $self->{'externalRepositoriesToAdd'}->{$_};

				if(/^ppa:/) { # PPA repository
					if($distroRelease > 10.4) {
						if($repository->{'repository_key_srv'}) {
							@cmd = (
								'add-apt-repository -y -k',
								escapeShell($repository->{'repository_key_srv'}),
								escapeShell($_)
							);
						} else {
							@cmd = ('add-apt-repository -y', escapeShell($_));
						}
				 	} else {
						@cmd = ('add-apt-repository', escapeShell($_));
				 	}

					$rs = execute("@cmd", \$stdout, \$stderr);
					debug($stdout) if $stdout;
					error($stderr) if $stderr && $rs;
					return $rs if $rs
				} else { # Normal repository
					if($distroRelease > 10.4) {
						@cmd = ('add-apt-repository -y ', escapeShell($_));
						$rs = execute("@cmd", \$stdout, \$stderr);
					} else {
						@cmd = ('add-apt-repository ', escapeShell($_));
						$rs = execute("@cmd", \$stdout, \$stderr);
					}
					debug($stdout) if $stdout;
					error($stderr) if $stderr && $rs;
					return $rs if $rs;

					if($repository->{'repository_key_srv'}) {
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
					} elsif($repository->{'repository_key_uri'}) {
						@cmd = ('wget -qO-', escapeShell($repository->{'repository_key_uri'}), '| apt-key add -');
					} else {
						error("The repository_key_uri entry for the '$_' repository was not found");
						return 1;
					}

					$rs = execute("@cmd", \$stdout, \$stderr);
					debug($stdout) if $stdout;
					error($stderr) if $stderr && $rs;
					return $rs if $rs
				}
			}
		}
	}

	0;
}

=back

=head1 Author

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
