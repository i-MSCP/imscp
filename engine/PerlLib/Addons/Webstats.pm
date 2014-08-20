#!/usr/bin/perl

=head1 NAME

Addons::Webstats - i-MSCP Webstats addon

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

package Addons::Webstats;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Debug;
use iMSCP::Getopt;
use iMSCP::Execute;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Webstats addon for i-MSCP.

 i-MSCP Webstats addon. This is a wrapper that handle all available Webstats addons found in the Webstats directory.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners(\%eventManager)

 Register setup event listeners

 Param iMSCP::EventManager \%eventManager
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
	my ($self, $eventManager) = @_;

	$eventManager->register('beforeSetupDialog', sub { push @{$_[0]}, sub { $self->showDialog(@_) }; 0; });
}

=item showDialog(\%dialog)

 Show dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 or 30

=cut

sub showDialog
{
	my ($self, $dialog, $rs) = (@_, 0);

	my $addons = [split ',', main::setupGetQuestion('WEBSTATS_ADDONS')];

	if(
		$main::reconfigure ~~ ['webstats', 'all', 'forced'] || ! @{$addons} ||
		grep { not $_ ~~ [$self->{'ADDONS'}, 'No'] } @{$addons}
	) {
		($rs, $addons) = $dialog->checkbox(
			"\nPlease, select the Webstats addons you want install:",
			$self->{'ADDONS'},
			(@{$addons} ~~ 'No') ? () : (@{$addons} ? @{$addons} : @{$self->{'ADDONS'}})
		);
	}

	if($rs != 30) {
		main::setupSetQuestion('WEBSTATS_ADDONS', @{$addons} ? join ',', @{$addons} : 'No');

		if(not 'No' ~~ @{$addons}) {
			for(@{$addons}) {
				my $addon = "Addons::Webstats::${_}::${_}";
				eval "require $addon";

				unless($@) {
					$addon = $addon->getInstance();
					$rs = $addon->showDialog($dialog) if $addon->can('showDialog');
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

 /!\ This method also trigger uninstallation of unselected Webstats addons.

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	my $self = $_[0];

	my $rs = 0;
	my @addons = split ',', main::setupGetQuestion('WEBSTATS_ADDONS');
	my $addonsToInstall = [grep { $_ ne 'No'} @addons];
	my $addonsToUninstall = [grep { not $_ ~~  @{$addonsToInstall}} @{$self->{'ADDONS'}}];

	if(@{$addonsToUninstall}) {
		my $packages = [];

		for(@{$addonsToUninstall}) {
			my $addon = "Addons::Webstats::${_}::${_}";
			eval "require $addon";

			unless($@) {
				$addon = $addon->getInstance();
				$rs = $addon->uninstall(); # Mandatory method
				return $rs if $rs;

				@{$packages} = (@{$packages}, @{$addon->getPackages()}) if $addon->can('getPackages');
			} else {
				error($@);
				return 1;
			}
		}

		$rs = $self->_removePackages($packages) if @${packages};
		return $rs if $rs;
	}

	if(@{$addonsToInstall}) {
		my $packages = [];

		for(@{$addonsToInstall}) {

			my $addon = "Addons::Webstats::${_}::${_}";
			eval "require $addon";

			unless($@) {
				$addon = $addon->getInstance();
				$rs = $addon->preinstall() if $addon->can('preinstall');
				return $rs if $rs;

				@{$packages} = (@{$packages}, @{$addon->getPackages()}) if $addon->can('getPackages');
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
	my @addons = split ',', main::setupGetQuestion('WEBSTATS_ADDONS');

	if(not 'No' ~~ @addons) {
		for(@addons) {
			my $addon = "Addons::Webstats::${_}::${_}";
			eval "require $addon";

			unless($@) {
				$addon = $addon->getInstance();
				my $rs = $addon->install() if $addon->can('install');
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

	my @addons = split ',', $main::imscpConfig{'WEBSTATS_ADDONS'};

	my $packages = [];
	my $rs = 0;

	for(@addons) {
		if($_ ~~ @{$self->{'ADDONS'}}) {
			my $addon = "Addons::Webstats::${_}::${_}";
			eval "require $addon";

			unless($@) {
				$addon = $addon->getInstance();
				$rs = $addon->uninstall(); # Mandatory method;
				return $rs if $rs;

				@{$packages} = (@{$packages}, @{$addon->getPackages()}) if $addon->can('getPackages');
			} else {
				error($@);
				return 1;
			}
		}
	}

	$rs = $self->_removePackages($packages) if @${packages};

	$rs;
}

=item setEnginePermissions()

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
	my $self = $_[0];

	my @addons = split ',', $main::imscpConfig{'WEBSTATS_ADDONS'};

	for(@addons) {
		if($_ ~~ @{$self->{'ADDONS'}}) {
			my $addon = "Addons::Webstats::${_}::${_}";
			eval "require $addon";

			unless($@) {
				$addon = $addon->getInstance();
				my $rs = $addon->setEnginePermissions() if $addon->can('setEnginePermissions');
				return $rs if $rs;
			} else {
				error($@);
				return 1;
			}
		}
	}

	0;
}

=item preaddDmn(\%data)

 Process preAddDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub preaddDmn
{
	my ($self, $data) = @_;

	if($data->{'FORWARD'} eq 'no') {
		my @addons = split ',', $main::imscpConfig{'WEBSTATS_ADDONS'};

		for(@addons) {
			if($_ ~~ @{$self->{'ADDONS'}}) {
				my $addon = "Addons::Webstats::${_}::${_}";
				eval "require $addon";

				unless($@) {
					$addon = $addon->getInstance();
					my $rs = $addon->preaddDmn($data) if $addon->can('preaddDmn');
					return $rs if $rs;
				} else {
					error($@);
					return 1;
				}
			}
		}
	}

	0;
}

=item addDmn(\%data)

 Process addDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub addDmn
{
	my ($self, $data) = @_;

	if($data->{'FORWARD'} eq 'no') {
		my @addons = split ',', $main::imscpConfig{'WEBSTATS_ADDONS'};

		for(@addons) {
			if($_ ~~ @{$self->{'ADDONS'}}) {
				my $addon = "Addons::Webstats::${_}::${_}";
				eval "require $addon";

				unless($@) {
					$addon = $addon->getInstance();
					my $rs = $addon->addDmn($data) if $addon->can('addDmn');
					return $rs if $rs;
				} else {
					error($@);
					return 1;
				}
			}
		}
	}

	0;
}

=item deleteDmn(\%data)

 Process deleteDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub deleteDmn
{
	my ($self, $data) = @_;

	if($data->{'FORWARD'} eq 'no') {
		my @addons = split ',', $main::imscpConfig{'WEBSTATS_ADDONS'};

		for(@addons) {
			if($_ ~~ @{$self->{'ADDONS'}}) {
				my $addon = "Addons::Webstats::${_}::${_}";
				eval "require $addon";

				unless($@) {
					$addon = $addon->getInstance();
					my $rs = $addon->deleteDmn($data) if $addon->can('deleteDmn');
					return $rs if $rs;
				} else {
					error($@);
					return 1;
				}
			}
		}
	}

	0;
}

=item preaddSub(\%data)

 Process preaddSub tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub preaddSub
{
	my ($self, $data) = @_;

	$self->preaddDmn($data);
}

=item addSub(\%data)

 Process addSub tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub addSub
{
	my ($self, $data) = @_;

	$self->addDmn($data);
}

=item deleteSub(\%data)

 Process deleteSub tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub deleteSub
{
	my ($self, $data) = @_;

	$self->deleteDmn($data);
}

=back

=head1 PRIVATE METHODS

=over 4

=item init()

 Initialize instance

 Return Addons::Webstats

=cut

sub _init
{
	my $self = $_[0];

	@{$self->{'ADDONS'}} = iMSCP::Dir->new(
		'dirname' => "$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Addons/Webstats"
	)->getDirs();

	$self;
}

=item _installPackages(\@packages)

 Install packages

 Param array \@packages Packages to install
 Return int 0 on success, other on failure

=cut

sub _installPackages
{
	my ($self, $packages) = @_;

	my $command = 'apt-get';
	my $preseed = iMSCP::Getopt->preseed;

	iMSCP::Dialog->factory()->endGauge();

	$command = 'debconf-apt-progress --logstderr -- ' . $command if ! $preseed && ! $main::noprompt;

	my ($stdout, $stderr);
	my $rs = execute(
		"$command -y -o DPkg::Options::='--force-confdef' install @{$packages} --auto-remove --purge " .
			"--no-install-recommends",,
		($preseed || $main::noprompt) ? \$stdout : undef, \$stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error('Unable to install anti-rootkits packages') if $rs && ! $stderr;

	$rs;
}

=item _removePackages(\@packages)

 Remove packages

 Param array \@packages Packages to remove
 Return int 0 on success, other on failure

=cut

sub _removePackages
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
	error('Unable to remove anti-rootkits addons packages') if $rs && ! $stderr;

	$rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
