#!/usr/bin/perl

=head1 NAME

Addons::filemanager::installer - i-MSCP Web File manager addon installer

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
# @category    i-MSCP
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Addons::filemanager::installer;

use strict;
use warnings;

use iMSCP::Debug;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This is the installer for the i-MSCP filemanager addon.

=head1 PUBLIC METHODS

=over 4

=item registerSetupHooks(HooksManager)

 Register filemanager setup hook functions.

 Param iMSCP::HooksManager instance
 Return int 0 on success, 1 on failure

=cut

sub registerSetupHooks
{
	my $self = shift;
	my $hooksManager = shift;

	$hooksManager->register(
		'beforeSetupDialog', sub { my $dialogStack = shift; push(@$dialogStack, sub { $self->askFilemanager(@_) }); 0; }
	);
}

=item preinstall()

 Process file manager addon preinstall tasks.

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	my $self = shift;

	my $fileManagerAddon = $main::imscpConfig{'FILEMANAGER_ADDON'} || '';

	if($fileManagerAddon eq 'AjaxPlorer') {
		require Addons::filemanager::ajaxplorer::installer;
		Addons::filemanager::ajaxplorer::installer->getInstance()->preinstall();
	} elsif($fileManagerAddon eq 'Net2ftp') {
		require Addons::filemanager::net2ftp::installer;
		Addons::filemanager::net2ftp::installer->getInstance()->preinstall();
	} else {
		error("Unknown Web Ftp file manager addon: $fileManagerAddon");
		return 1;
	}
}

=item install()

 Process file manager addon install tasks.

 Return int 0 on success, 1 on failure

=cut

sub install
{
	my $self = shift;

	my $fileManagerAddon = $main::imscpConfig{'FILEMANAGER_ADDON'} || '';

	if($fileManagerAddon eq 'AjaxPlorer') {
		require Addons::filemanager::ajaxplorer::installer;
		Addons::filemanager::ajaxplorer::installer->getInstance()->install();
	} elsif($fileManagerAddon eq 'Net2ftp') {
		require Addons::filemanager::net2ftp::installer;
		Addons::filemanager::net2ftp::installer->getInstance()->install();
	} else {
		error("Unknown Web Ftp file manager addon: $fileManagerAddon");
		return 1;
	}
}

=item setGuiPermissions()

 Set file manager addon files permissions.

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
	my $self = shift;

	my $fileManagerAddon = $main::imscpConfig{'FILEMANAGER_ADDON'} || '';

	if($fileManagerAddon eq 'AjaxPlorer') {
		require Addons::filemanager::ajaxplorer::installer;
		Addons::filemanager::ajaxplorer::installer->getInstance()->setGuiPermissions();
	} elsif($fileManagerAddon eq 'Net2ftp') {
		require Addons::filemanager::net2ftp::installer;
		Addons::filemanager::net2ftp::installer->getInstance()->setGuiPermissions();
	} else {
		error("Unknown Web Ftp file manager addon: $fileManagerAddon");
		return 1;
	}
}

=back

=head1 HOOK FUNCTIONS

=over 4

=item askFilemanager()

 Show file manager addon question.

 Hook function responsible to show filemanager installer question.

 Param iMSCP::Dialog
 Return int 0 or 30

=cut

sub askFilemanager
{
	my ($self, $dialog, $rs) = (shift, shift, 0);

	my $fileManagerAddon = main::setupGetQuestion('FILEMANAGER_ADDON');

	if($main::reconfigure ~~ ['filemanager', 'ftp', 'all', 'forced'] || $fileManagerAddon !~ /^AjaxPlorer|Net2ftp$/) {
		($rs, $fileManagerAddon) = $dialog->radiolist(
			"\nPlease, select the Ftp Web file manager addon you want use:",
			['AjaxPlorer', 'Net2ftp'],
			$fileManagerAddon ne '' ? $fileManagerAddon : 'AjaxPlorer'
		);
	}

	main::setupSetQuestion('FILEMANAGER_ADDON', $fileManagerAddon) if $rs != 30;

	$rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
