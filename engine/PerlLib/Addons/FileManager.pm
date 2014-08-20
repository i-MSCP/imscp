#!/usr/bin/perl

=head1 NAME

Addons::FileManager - i-MSCP FileManager addon

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

package Addons::FileManager;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Debug;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP FileManager addon. This is a wrapper that handle all available FileManager addons found in the FileManager
directory.

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

	my $addon = main::setupGetQuestion('FILEMANAGER_ADDON');

	if(
		$main::reconfigure ~~ ['filemanager', 'all', 'forced'] || ! $addon || not $addon ~~ @{$self->{'ADDONS'}}
	) {
		($rs, $addon) = $dialog->radiolist(
			"\nPlease, select the Ftp Web file manager addon you want install:",
			$self->{'ADDONS'},
			($addon ne  '' && $addon ~~ $self->{'ADDONS'} ) ? $addon : @{$self->{'ADDONS'}}[0]
		);
	}

	if($rs != 30) {
		main::setupSetQuestion('FILEMANAGER_ADDON', $addon);

		$addon = "Addons::FileManager::${addon}::${addon}";
		eval "require $addon";

		if(! $@) {
			$addon = $addon->getInstance();
			$rs = $addon->showDialog($dialog) if $addon->can('showDialog');
			last if $rs;
		} else {
			error($@);
			return 1;
		}
	}

	$rs;
}

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	my $addon = main::setupGetQuestion('FILEMANAGER_ADDON');

	$addon = "Addons::FileManager::${addon}::${addon}";
	eval "require $addon";

	if(! $@) {
		$addon = $addon->getInstance();
		my $rs = $addon->preinstall() if $addon->can('preinstall');
		return $rs if $rs;
	} else {
		error($@);
		return 1;
	}

	0;
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $addon = main::setupGetQuestion('FILEMANAGER_ADDON');

	$addon = "Addons::FileManager::${addon}::${addon}";
	eval "require $addon";

	if(! $@) {
		$addon = $addon->getInstance();
		my $rs = $addon->install() if $addon->can('install');
		return $rs if $rs;
	} else {
		error($@);
		return 1;
	}

	0;
}

=item setGuiPermissions()

 Set gui permissions

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
	my $self = $_[0];

	my $addon = $main::imscpConfig{'FILEMANAGER_ADDON'};

	if($addon ~~ @{$self->{'ADDONS'}}) {
		my $addon = "Addons::FileManager::${addon}::${addon}";
		eval "require $addon";

		if(! $@) {
			$addon = $addon->getInstance();
			my $rs = $addon->setGuiPermissions() if $addon->can('setGuiPermissions');
			return $rs if $rs;
		} else {
			error($@);
			return 1;
		}
	}

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item init()

 Initialize insance

 Return Addons::AntiRootkits

=cut

sub _init
{
	my $self = $_[0];

	# Find list of available FileManager addons
	@{$self->{'ADDONS'}} = iMSCP::Dir->new(
		'dirname' => "$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Addons/FileManager"
	)->getDirs();

	$self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
