#!/usr/bin/perl

=head1 NAME

Package::AntiRootkits - i-MSCP Anti-Rootkits package

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

package Package::AntiRootkits;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Debug;
use iMSCP::Getopt;
use iMSCP::Execute;
use iMSCP::Dir;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Anti-Rootkits package.

 This is a wrapper that handle all available Anti-Rootkits packages found in the AntiRootkits directory.

=head1 PUBLIC METHODS

=over 4

=item registerSetupHooks(\%hooksManager)

 Register setup hook functions

 Param iMSCP::HooksManager instance
 Return int 0 on success, 1 on failure

=cut

sub registerSetupHooks($$)
{
	my ($self, $hooksManager) = @_;

	$hooksManager->register(
		'beforeSetupDialog', sub { my $dialogStack = shift; push(@$dialogStack, sub { $self->showDialog(@_) }); 0; }
	);
}

=item askAntiRootkits(\%dialog)

 Show dialog

 Param iMSCP::Dialog::Dialog|iMSCP::Dialog::Whiptail $dialog
 Return int 0 or 30

=cut

sub showDialog($$)
{
	my ($self, $dialog, $rs) = (@_, 0);

	my $packages = [split ',', main::setupGetQuestion('ANTI_ROOTKITS_PACKAGES')];

	if(
		$main::reconfigure ~~ ['antirootkits', 'all', 'forced'] || ! @{$packages} ||
		grep { not $_ ~~ [$self->{'PACKAGES'}, 'No'] } @{$packages}
	) {
		($rs, $packages) = $dialog->checkbox(
			"\nPlease, select the Anti-Rootkits package you want install:",
			$self->{'PACKAGES'},
			(@{$packages} ~~ 'No') ? () : (@{$packages} ? @{$packages} : @{$self->{'PACKAGES'}})
		);
	}

	if($rs != 30) {
		main::setupSetQuestion('ANTI_ROOTKITS_PACKAGES', @{$packages} ? join ',', @{$packages} : 'No');

		if(not 'No' ~~ @{$packages}) {
			for(@{$packages}) {
				my $package = "Package::AntiRootkits::${_}::${_}";
				eval "require $package";

				if(! $@) {
					$package = $package->getInstance();
					$rs = $package->showDialog($dialog) if $package->can('showDialog');
					last if $rs;
				} else {
					error($@);
					return 1;
				}
			}
		}
	}

	$rs;
}

=item preinstall()

 Process preinstall tasks

 Note: This method also trigger uninstallation of unselected Anti-Rootkits packages.

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	my $self = $_[0];

	my $rs = 0;
	my @packages = split ',', main::setupGetQuestion('ANTI_ROOTKITS_PACKAGES');
	my $packagesToInstall = [grep { $_ ne 'No'} @packages];
	my $packagesToUninstall = [grep { not $_ ~~  @{$packagesToInstall}} @{$self->{'PACKAGES'}}];

	if(@{$packagesToUninstall}) {
		my $packages = [];

		for(@{$packagesToUninstall}) {
			my $package = "Package::AntiRootkits::${_}::${_}";
			eval "require $package";

			if(! $@) {
				$package = $package->getInstance();
				$rs = $package->uninstall(); # Mandatory method
				return $rs if $rs;

				@{$packages} = (@{$packages}, @{$package->getDistroPackages()}) if $package->can('getDistroPackages');
			} else {
				error($@);
				return 1;
			}
		}

		$rs = $self->_removePackages($packages) if @${packages};
		return $rs if $rs;
	}

	if(@{$packagesToInstall}) {
		my $packages = [];

		for(@{$packagesToInstall}) {
			my $package = "Package::AntiRootkits::${_}::${_}";
			eval "require $package";

			if(! $@) {
				$package = $package->getInstance();
				$rs = $package->preinstall() if $package->can('preinstall');
				return $rs if $rs;

				@{$packages} = (@{$packages}, @{$package->getDistroPackages()}) if $package->can('getDistroPackages');
			} else {
				error($@);
				return 1;
			}
		}

		$rs = $self->_installPackages($packages) if @{$packages};
		return $rs if $rs;
	}

	$rs;
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my @packages = split ',', main::setupGetQuestion('ANTI_ROOTKITS_PACKAGES');

	if(not 'No' ~~ @packages) {
		for(@packages) {
			my $package = "Package::AntiRootkits::${_}::${_}";
			eval "require $package";

			if(! $@) {
				$package = $package->getInstance();
				my $rs = $package->install() if $package->can('install');
				return $rs if $rs;
			} else {
				error($@);
				return 1;
			}
		}
	}

	0;
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = $_[0];

	my @packages = split ',', $main::imscpConfig{'ANTI_ROOTKITS_PACKAGES'};

	my $packages = [];
	my $rs = 0;

	for(@packages) {
		if($_ ~~ @{$self->{'PACKAGES'}}) {
			my $package = "Package::AntiRootkits::${_}::${_}";
			eval "require $package";

			if(! $@) {
				$package = $package->getInstance();
				$rs = $package->uninstall(); # Mandatory method;
				return $rs if $rs;

				@{$packages} = (@{$packages}, @{$package->getDistroPackages()}) if $package->can('getDistroPackages');
			} else {
				error($@);
				return 1;
			}
		}
	}

	$rs = $self->_removePackages($packages) if @{$packages};

	$rs;
}

=item setEnginePermissions()

 Set file permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
	my $self = $_[0];

	my @packages = split ',', $main::imscpConfig{'ANTI_ROOTKITS_PACKAGES'};

	for(@packages) {
		if($_ ~~ @{$self->{'PACKAGES'}}) {
			my $package = "Package::AntiRootkits::${_}::${_}";
			eval "require $package";

			if(! $@) {
				$package = $package->getInstance();
				my $rs = $package->setEnginePermissions() if $package->can('setEnginePermissions');
				return $rs if $rs;
			} else {
				error($@);
				return 1;
			}
		}
	}

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item init()

 Initialize instance

 Return Package::AntiRootkits

=cut

sub _init()
{
	my $self = $_[0];

	# Find list of available AntiRootkits packages
	@{$self->{'PACKAGES'}} = iMSCP::Dir->new(
		'dirname' => "$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/AntiRootkits"
	)->getDirs();

	$self;
}

=item _installPackages(\@packages)

 Install packages

 Param array_ref $packages List of packages to install
 Return int 0 on success, other on failure

=cut

sub _installPackages($$)
{
	my ($self, $packages) = @_;

	my $command = 'apt-get';
	my $preseed = iMSCP::Getopt->preseed;

	iMSCP::Dialog->factory()->endGauge();

	$command = 'debconf-apt-progress --logstderr -- ' . $command if ! $preseed && ! $main::noprompt;

	my ($stdout, $stderr);
	my $rs = execute(
		"$command -y -o DPkg::Options::='--force-confdef' install @{$packages} --auto-remove --purge " .
			"--no-install-recommends",
		($preseed || $main::noprompt) ? \$stdout : undef, \$stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error('Unable to install anti-rootkits distro packages') if $rs && ! $stderr;

	$rs;
}

=item _removePackages(\@packages)

 Remove packages

 Param array_ref $packages List of packages to remove
 Return int 0 on success, other on failure

=cut

sub _removePackages($$)
{
	my ($self, $packages) = @_;

	my $command = 'apt-get';
	my $preseed = iMSCP::Getopt->preseed;

	iMSCP::Dialog->factory()->endGauge();

	$command = 'debconf-apt-progress --logstderr -- ' . $command if ! $preseed && ! $main::noprompt;

	my ($stdout, $stderr);
	my $rs = execute(
		"$command -y remove @{$packages} --auto-remove --purge",
		($preseed || $main::noprompt) ? \$stdout : undef, \$stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error('Unable to remove anti-rootkits distro packages') if $rs && ! $stderr;

	$rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
