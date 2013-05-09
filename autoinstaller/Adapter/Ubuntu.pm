#!/usr/bin/perl

=head1 NAME

 autoinstaller::Adapter::Ubuntu - Ubuntu autoinstaller adapter class

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
# @Author		Laurent Declercq <l.declercq@nuxwin.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package autoinstaller::Adapter::Ubuntu;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::LsbRelease;
use iMSCP::Execute;
use parent 'autoinstaller::Adapter::Debian';

=head1 DESCRIPTION

 i-MSCP distro autoinstaller adapter implementation for Ubuntu.

 See the autoinstaller::Adapter::Debian autoinstaller adapter for more information.

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by getInstance(). Initialize instance.

 Return autoinstaller::Adapter::Ubuntu

=cut

sub _init
{
	my $self = shift;

	delete $ENV{'DEBCONF_FORCE_DIALOG'};

	$self->{'repositorySections'} = ['main', 'universe', 'multiverse'];
	$self->{'preRequiredPackages'} = [
		'aptitude', 'debconf-utils', 'dialog', 'liblist-moreutils-perl', 'libxml-simple-perl', 'wget'
	];

	if(iMSCP::LsbRelease->getInstance()->getRelease(1) < 12.10) {
		push @{$self->{'preRequiredPackages'}}, 'python-software-properties';
	} else {
		push @{$self->{'preRequiredPackages'}}, 'software-properties-common';
	}

	$self->{'externalRepositoriesToRemove'} = [];
	$self->{'externalRepositories'} = [];
	$self->{'aptPreferences'} = [];
	$self->{'packagesToInstall'} = [];
	$self->{'packagesToUninstall'} = [];

	$self->_updateAptSourceList() and fatal('Unable to configure APT packages manager') if ! $main::skippackages;

	$self;
}

=item _addExternalRepositories()

 Add external repositories to the sources.list file and their gpg keys.

 Note: Also removes repositories that are no longer needed.

 Return int 0 on success, other on failure.

=cut

sub _addExternalRepositories
{
	my $self = shift;

	my $distroRelease = iMSCP::LsbRelease->getInstance()->getRelease(1);
	my $rs = 0;

	if(@{$self->{'externalRepositoriesToRemove'}} || @{$self->{'externalRepositories'}}) {

		my $sourceListFile = iMSCP::File->new('filename' => '/etc/apt/sources.list');

		unless(-f '/etc/apt/sources.list.bkp') {
			$rs = $sourceListFile->copyFile('/etc/apt/sources.list.bkp');
			return $rs if $rs;
		}

		for(@{$self->{'externalRepositories'}}) {
			my $repository = $_->{'repository'};

			for(@{$self->{'externalRepositoriesToRemove'}}) {
				if($repository eq $_->{'repository'}) {
					my $index = 0;
					$index++ until @{$self->{'externalRepositoriesToRemove'}}[$index] eq $_;
					splice(@{$self->{'externalRepositoriesToRemove'}}, $index, 1);
				}
			}
		}

		my ($cmd, $stdout, $stderr);

		for(@{$self->{'externalRepositoriesToRemove'}}) {
			# Retrieve any packages installed from the repository to remove
			# TODO This command is too slow. Try to find a replacement for it
			$rs = execute(
				"aptitude search '?installed?origin($_->{'repository_origin'})' | cut -b 5- | cut -d ' ' -f 1",
				\$stdout, \$stderr
			);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;

			# Schedule packages for deletion
			@{$self->{'packagesToUninstall'}} = (@{$self->{'packagesToUninstall'}}, split("\n", $stdout)) if $stdout;

			# Remove the repository
			if($distroRelease > 10.04) {
				$rs = execute("add-apt-repository -y -r $_->{'repository'}", \$stdout, \$stderr);
				debug($stdout) if $stdout;
				error($stderr) if $stderr && $rs;
			} else {
				# add-apt-repository command as provided by Ubuntu lucid doesn't provide any option
				if($_->{'repository'} =~ m%^ppa:(.*)/(.*)%) { # PPA repository
					my $ppaFile = "/etc/apt/sources.list.d/$1-$2-*";

					if(glob $ppaFile) {
						$rs = execute("$main::imscpConfig{'CMD_RM'} $ppaFile", \$stdout, \$stderr);
						debug($stdout) if $stdout;
						error($stderr) if $stderr && $rs;
						return $rs if $rs;
					}
				} else { # Normal repository
					# Remove the repository from the sources.list file
					my $content = $sourceListFile->get();
					$content =~ s/\n?(deb|deb-src)\s+$_->{'repository'}\n?//gm;

					$rs = $sourceListFile->set($content);
					return $rs if $rs;

					$rs = $sourceListFile->save();
					return $rs if $rs;
				}
			}
		}

		eval "use List::MoreUtils qw(uniq); 1";
		fatal('Unable to load the List::MoreUtils perl module') if($@);

		# Remove any duplicate entries
		@{$self->{'packagesToUninstall'}} = uniq(@{$self->{'packagesToUninstall'}});

		# Add needed external repositories
		for(@{$self->{'externalRepositories'}}) {
			if($_->{'repository'} =~ /^ppa:/) { # PPA repository
				if($distroRelease > 10.4) {
					if($_->{'repository_key_srv'}) {
						$cmd = "add-apt-repository -y -k $_->{'repository_key_srv'} $_->{'repository'}";
					} else {
						$cmd = "add-apt-repository -y $_->{'repository'}";
					}
				 } else {
					$cmd = "add-apt-repository $_->{'repository'}";
				 }

				$rs = execute($cmd, \$stdout, \$stderr);
				debug($stdout) if $stdout;
				error($stderr) if $stderr && $rs;
				return $rs if $rs
			} else { # Normal repository
				if($distroRelease > 10.4) {
					$rs = execute("add-apt-repository -y $_->{'repository'}", \$stdout, \$stderr);
				} else {
					$rs = execute("add-apt-repository $_->{'repository'}", \$stdout, \$stderr);
				}
				debug($stdout) if $stdout;
				error($stderr) if $stderr && $rs;
				return $rs if $rs;

				if($_->{'repository_key_srv'}) {
					if($_->{'repository_key_id'}) {
						$cmd = "apt-key adv --recv-keys --keyserver $_->{'repository_key_srv'} $_->{'repository_key_id'}";
					} else {
						error("The repository_key_id entry for the '$_->{'repository'} repository was not found");
						return 1;
					}
				} elsif($_->{'repository_key_uri'}) {
					$cmd = "wget -qO- $_->{'repository_key_uri'} | apt-key add -";
				} else {
					error("The repository_key_uri entry for the '$_->{'repository'}' repository was not found");
					return 1;
				}

				$rs = execute($cmd, \$stdout, \$stderr);
				debug($stdout) if $stdout;
				error($stderr) if $stderr && $rs;
				return $rs if $rs
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
