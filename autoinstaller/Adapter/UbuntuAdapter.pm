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
use iMSCP::EventManager;
use iMSCP::LsbRelease;
use iMSCP::Execute;
use parent 'autoinstaller::Adapter::DebianAdapter';

=head1 DESCRIPTION

 i-MSCP autoinstaller adapter implementation for Ubuntu.

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

	$self->{'eventManager'} = iMSCP::EventManager->getInstance();

	delete $ENV{'DEBCONF_FORCE_DIALOG'};
	$ENV{'DEBIAN_FRONTEND'} = 'noninteractive' if iMSCP::Getopt->preseed || iMSCP::Getopt->noprompt;

	$self->{'repositorySections'} = ['main', 'universe', 'multiverse'];
	$self->{'preRequiredPackages'} = [
		'aptitude', 'debconf-utils', 'dialog', 'liblist-moreutils-perl', 'libxml-simple-perl', 'wget', 'resolvconf',
		'rsync'
	];

	if(iMSCP::LsbRelease->getInstance()->getRelease(1) < 12.10) {
		push @{$self->{'preRequiredPackages'}}, 'python-software-properties';
	} else {
		push @{$self->{'preRequiredPackages'}}, 'software-properties-common';
	}

	$self->{'aptRepositoriesToRemove'} = { };
	$self->{'aptRepositoriesToAdd'} = { };
	$self->{'aptPreferences'} = [];
	$self->{'packagesToInstall'} = [];
	$self->{'packagesToUninstall'} = [];

	$self->_updateAptSourceList() and fatal('Unable to configure APT packages manager') unless $main::skippackages;

	$self;
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

		my $distroRelease = iMSCP::LsbRelease->getInstance()->getRelease(1);
		my (@cmd, $stdout, $stderr);

		for(keys %{$self->{'aptRepositoriesToRemove'}}) {
			if(/^ppa:/ || $fileContent =~ /^$_/m) {
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
						$fileContent = $file->get();
						$fileContent =~ s/\n?$_\n?//gm;

						$rs = $file->set($fileContent);
						return $rs if $rs;

						$rs = $file->save();
						return $rs if $rs;
					}
				}
			}
		}

		# Add needed APT repositories
		for(keys %{$self->{'aptRepositoriesToAdd'}}) {
			if(/^ppa:/ || $fileContent !~ /^$_/m) {
				my $repository = $self->{'aptRepositoriesToAdd'}->{$_};

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
