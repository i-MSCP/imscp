=head1 NAME

 autoinstaller::Adapter::UbuntuAdapter - Ubuntu autoinstaller adapter class

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

package autoinstaller::Adapter::UbuntuAdapter;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::EventManager;
use iMSCP::LsbRelease;
use iMSCP::Execute;
use version;
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
	my $self = shift;

	$self->{'eventManager'} = iMSCP::EventManager->getInstance();

	delete $ENV{'DEBCONF_FORCE_DIALOG'};
	$ENV{'DEBIAN_FRONTEND'} = 'noninteractive' if iMSCP::Getopt->preseed || iMSCP::Getopt->noprompt;

	$self->{'repositorySections'} = [ 'main', 'universe', 'multiverse' ];
	$self->{'preRequiredPackages'} = [
		'aptitude', 'debconf-utils', 'dialog', 'libbit-vector-perl', 'libclass-insideout-perl', 'liblist-moreutils-perl',
		'libscalar-defer-perl', 'libxml-simple-perl', 'wget'
	];

	if(version->parse(iMSCP::LsbRelease->getInstance()->getRelease(1)) < version->parse('12.10')) {
		push @{$self->{'preRequiredPackages'}}, 'python-software-properties';
	} else {
		push @{$self->{'preRequiredPackages'}}, 'software-properties-common';
	}

	$self->{'aptRepositoriesToRemove'} = [];
	$self->{'aptRepositoriesToAdd'} = [];
	$self->{'aptPreferences'} = [];
	$self->{'packagesToInstall'} = [];
	$self->{'packagesToInstallDelayed'} = [];
	$self->{'packagesToPreUninstall'} = [];
	$self->{'packagesToUninstall'} = [];

	unless($main::skippackages) {
		($self->_setupInitScriptPolicyLayer('enable') == 0) or die('Could not setup initscript policy layer');
		($self->_updateAptSourceList() == 0) or die('Could not configure APT packages manager');
	}

	$self;
}

=item _processAptRepositories()

 Process APT repositories

 Return int 0 on success, other on failure

=cut

sub _processAptRepositories
{
	my $self = shift;

	if(@{$self->{'aptRepositoriesToRemove'}} || @{$self->{'aptRepositoriesToAdd'}}) {
		my $file = iMSCP::File->new( filename => '/etc/apt/sources.list' );

		$file->copyFile('/etc/apt/sources.list.bkp') unless -f '/etc/apt/sources.list.bkp';

		my $fileContent = $file->get();

		# Filter list of repositories which must not be removed
		for my $repository(@{$self->{'aptRepositoriesToAdd'}}) {
			@{$self->{'aptRepositoriesToRemove'}} = grep {
				 $repository->{'repository'} !~ /^$_/
			} @{$self->{'aptRepositoriesToRemove'}};
		}

		my $distroRelease = iMSCP::LsbRelease->getInstance()->getRelease(1);

		for my $repository(@{$self->{'aptRepositoriesToRemove'}}) {
			my $isPPA = ($repository =~ /^ppa:/);

			if($isPPA || $fileContent =~ /^$repository/m) {
				if($isPPA) {
					my @cmd = ('add-apt-repository -y -r', escapeShell("deb $repository"));
					my $rs = execute("@cmd", \my $stdout, \my $stderr);
					debug($stdout) if $stdout;
					error($stderr) if $stderr && $rs;
					return $rs if $rs;
				} else {
					my $regexp = qr/(?:#\s*)?(?:deb|deb-src)$repository/;
					$fileContent =~ s/^\n?$regexp\n//gm;
				}
			}
		}

		# Save new sources.list file
		$file->set($fileContent);
		$file->save();

		# Add needed APT repositories
		for my $repository(@{$self->{'aptRepositoriesToAdd'}}) {
			my $isPPA = ($repository->{'repository'} =~ /^ppa:/);

			if($isPPA || $fileContent !~ /^deb $repository->{'repository'}/m) {
				if($isPPA) { # PPA repository
					my @cmd = ();

					if($repository->{'repository_key_srv'}) {
						@cmd = (
							'add-apt-repository -s -y -k',
							escapeShell($repository->{'repository_key_srv'}),
							escapeShell("deb $repository->{'repository'}")
						);
					} else {
						@cmd = ('add-apt-repository -s -y', escapeShell("deb $repository->{'repository'}"));
					}

					my $rs = execute("@cmd", \my $stdout, \my $stderr);
					debug($stdout) if $stdout;
					error($stderr) if $stderr && $rs;
					return $rs if $rs
				} else { # Normal repository
					my @cmd = ('add-apt-repository -s -y ', escapeShell("deb $repository->{'repository'}"));
					my $rs = execute("@cmd", \my $stdout, \my $stderr);

					debug($stdout) if $stdout;
					error($stderr) if $stderr && $rs;
					return $rs if $rs;

					@cmd = ();

					if($repository->{'repository_key_srv'}) {
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
					} elsif($repository->{'repository_key_uri'}) {
						@cmd = ('wget -qO-', escapeShell($repository->{'repository_key_uri'}), '| apt-key add -');
					}

					if(@cmd) {
						my $rs = execute("@cmd", \my $stdout, \my $stderr);
						debug($stdout) if $stdout;
						error($stderr) if $stderr && $rs;
						return $rs if $rs
					}
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
__END__
