#!/usr/bin/perl

=head1 NAME

Addons::webstats::installer - i-MSCP Web File manager addon installer

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

package Addons::webstats::installer;

use strict;
use warnings;

use iMSCP::Debug;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This is the installer for the i-MSCP webstats addon.

=head1 PUBLIC METHODS

=over 4

=item registerSetupHooks(HooksManager)

 Register webstats setup hook functions.

 Param iMSCP::HooksManager instance
 Return int 0 on success, 1 on failure

=cut

sub registerSetupHooks
{
	my $self = shift;
	my $hooksManager = shift;

	$hooksManager->register(
		'beforeSetupDialog', sub { my $dialogStack = shift; push(@$dialogStack, sub { $self->askWebstats(@_) }); 0; }
	);
}

=item askWebstats()

 Show webstats addon question.

 Hook function responsible to show webstats addon question(s).

 Param iMSCP::Dialog
 Return int 0 or 30

=cut

sub askWebstats
{
	my ($self, $dialog, $rs) = (shift, shift, 0);

	my $webStatsAddon = main::setupGetQuestion('WEBSTATS_ADDON');

	if($main::reconfigure ~~ ['webstats', 'all', 'forced'] || $webStatsAddon !~ /^Awstats|No$/) {
		($rs, $webStatsAddon) = $dialog->radiolist(
"
Please, select the Web statistics addon you want install.

Choose 'No' if you do not want provide any Web statistics for your customers.
",
			['Awstats', 'No'],
			$webStatsAddon ne '' ? $webStatsAddon : 'Awstats'
		);
	}

	$main::questions{'WEBSTATS_ADDON'} = $webStatsAddon if $rs != 30;

	if($rs != 30) {
		if($webStatsAddon eq 'Awstats') {
			require Addons::webstats::awstats::installer;
			$rs = Addons::webstats::awstats::installer->getInstance()->askAwstats($dialog);
		}
	}

	$rs;
}

=item preinstall()

 Process webstats addon preinstall tasks.

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	my $self = shift;

	my $webStatsAddon = $main::imscpConfig{'WEBSTATS_ADDON'} || '';

	if($webStatsAddon eq 'Awstats') {
		require Addons::webstats::awstats::installer;
		Addons::webstats::awstats::installer->getInstance()->preinstall();
	} elsif($webStatsAddon ne 'No') {
		error("Unknown Web Statistics addon: $webStatsAddon");
		return 1;
	}
}

=item install()

 Process webstats addon install tasks.

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	my $webStatsAddon = $main::imscpConfig{'WEBSTATS_ADDON'} || '';
	my $rs = 0;

	# In any case, the install method on the Awstats addon installer must be called since it act also as uninstaller
	# for Awstats global vhost file
	# TODO review addon implementation to avoid such thing
	require Addons::webstats::awstats::installer;
	$rs = Addons::webstats::awstats::installer->getInstance()->install();
	return $rs if $rs;

	if($webStatsAddon ne 'Awstats' && $webStatsAddon ne 'No') {
		error("Unknown Web Statistics addon: $webStatsAddon");
		$rs = 1;
	}

	$rs;
}

=item setGuiPermissions()

 Set webstats addon files permissions.

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
	my $self = shift;
	my $addon;
	my $rs = 0;

	my $webStatsAddon = $main::imscpConfig{'WEBSTATS_ADDON'} || '';

	if($webStatsAddon eq 'Awstats') {
		require Addons::webstats::awstats::installer;
		$addon = Addons::webstats::awstats::installer->getInstance();
	} elsif($webStatsAddon ne 'No') {
		error("Unknown Web Statistics addon: $webStatsAddon");
		return 1;
	} else {
		return 0;
	}

	$rs = $addon->setGuiPermissions() if $addon->can('setGuiPermissions');

	$rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
