#!/usr/bin/perl

=head1 NAME

Addons::webstats - i-MSCP webstats addon

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2013 by internet Multi Server Control Panel
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
# @author		Laurent Declercq <l.declercq@nuxwin.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Addons::webstats;

use strict;
use warnings;

use iMSCP::Debug;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Webstats addon for i-MSCP.

 This addon provide Web statistics for i-MSCP customers. For now only Awstats is available.

=head1 PUBLIC METHODS

=over 4

=item registerSetupHooks($hooksManager)

 Register setup hook functions.

 Param iMSCP::HooksManager instance
 Return int - 0 on success, 1 on failure

=cut

sub registerSetupHooks
{
	my $self = shift;
	my $hooksManager = shift;

	require Addons::webstats::installer;
	Addons::webstats::installer->getInstance()->registerSetupHooks($hooksManager);
}

=item preinstall()

 Run the preinstall method on the webstats addon installer.

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	my $self = shift;

	require Addons::webstats::installer;
	Addons::webstats::installer->getInstance()->preinstall();
}

=item install()

 Run the install method on the webstats addon installer.

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	require Addons::webstats::installer;
	Addons::webstats::installer->getInstance()->install();
}

=item setGuiPermissions()

 Set webstats addon files permissions (FrontEnd part only).

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
	my $self = shift;

	require Addons::webstats::installer;
	Addons::webstats::installer->getInstance()->setGuiPermissions();
}

=item setEnginePermissions()

 Set webstats addon files permissions (backend part only).

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
	my $self = shift;

	require Addons::webstats::installer;
	Addons::webstats::installer->getInstance()->setEnginePermissions();
}

=item preaddDmn(\$data)

 Process preAddDmn tasks.

 Return int 0 on success, other on failure

=cut

sub preaddDmn
{
	my $self = shift;
	my $data = shift;

	my $webStatsAddon = $main::imscpConfig{'WEBSTATS_ADDON'};
	my $rs = 0;

	if($webStatsAddon eq 'Awstats') {
		require Addons::webstats::awstats::awstats;
		$rs = Addons::webstats::awstats::awstats->getInstance()->preaddDmn($data);
	}

	$rs;
}

=item addDmn(\$data)

 Process addDmn tasks.

 Return int 0 on success, other on failure

=cut

sub addDmn
{
	my $self = shift;
	my $data = shift;

	my $webStatsAddon = $main::imscpConfig{'WEBSTATS_ADDON'};
	my $rs = 0;

	if($webStatsAddon eq 'Awstats') {
		require Addons::webstats::awstats::awstats;
		$rs = Addons::webstats::awstats::awstats->getInstance()->addDmn($data);
	} else {
		# Needed to remove any Awstats configuration file when switching to another Web statistics addon
		# TODO review addon implementation to avoid such thing
		require Addons::webstats::awstats::awstats;
		$rs = Addons::webstats::awstats::awstats->getInstance()->delDmn($data);
	}

	$rs;
}

=item delDmn(\$data)

 Process delDmn tasks.

 Return int 0 on success, other on failure

=cut

sub delDmn
{
	my $self = shift;
	my $data = shift;

	my $webStatsAddon = $main::imscpConfig{'WEBSTATS_ADDON'};
	my $rs = 0;

	if($webStatsAddon eq 'Awstats') {
		require Addons::webstats::awstats::awstats;
		$rs = Addons::webstats::awstats::awstats->getInstance()->delDmn($data);
	}

	$rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
