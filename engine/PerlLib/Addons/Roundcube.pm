#!/usr/bin/perl

=head1 NAME

Addons::Roundcube - i-MSCP Roundcube addon

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by internet Multi Server Control Panel
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
# @copyright   2010-2015 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Addons::Roundcube;

use strict;
use warnings;

use iMSCP::Debug;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Roundcube addon for i-MSCP

 RoundCube Webmail is a browser-based multilingual IMAP client with an application-like user interface. It provides full
functionality expected from an email client, including MIME support, address book, folder manipulation and message
filters.

 The user interface is fully skinnable using XHTML and CSS 2.

 Project homepage: http://www.roundcube.net/

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

	require Addons::Roundcube::Installer;
	Addons::Roundcube::Installer->getInstance()->registerSetupListeners($eventManager);
}

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	require Addons::Roundcube::Installer;
	Addons::Roundcube::Installer->getInstance()->preinstall();
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	require Addons::Roundcube::Installer;
	Addons::Roundcube::Installer->getInstance()->install();
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	require Addons::Roundcube::Uninstaller;
	Addons::Roundcube::Uninstaller->getInstance()->uninstall();
}

=item setGuiPermissions()

 Set gui permissions

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
	require Addons::Roundcube::Installer;
	Addons::Roundcube::Installer->getInstance()->setGuiPermissions();
}

=item deleteMail(\%data)

 Process deleteMail tasks

 Param hash \%data Mail data
 Return int 0 on success, other on failure

=cut

sub deleteMail
{
	my ($self, $data) = @_;

	my $roundcubeDbName = $main::imscpConfig{'DATABASE_NAME'} . '_roundcube';
	my $rs = 0;

	if($data->{'MAIL_TYPE'} =~ /_mail/) {
		my $database = iMSCP::Database->factory();
		$database->set('DATABASE_NAME', $roundcubeDbName);
		$rs = $database->connect();

		if(!$rs) {
			my $rdata = $database->doQuery('dummy', 'DELETE FROM `users` WHERE `username` = ?', $data->{'MAIL_ADDR'});
			unless(ref $rdata eq 'HASH') {
				error("Unable to remove mail user '$data->{'MAIL_ADDR'}' from roundcube database: $rdata");
				$rs = 1;
			}
		} else {
			error($rs);
			$rs = 1;
		}

		# Restore connection to i-MSCP database
		$database->set('DATABASE_NAME', $main::imscpConfig{'DATABASE_NAME'});

		if($database->connect()) {
			fatal("Unable to restore connection to i-MSCP database: $rs");
		}
	}

	$rs;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Addons::Roundcube

=cut

sub _init
{
	my $self = $_[0];

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/roundcube";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	tie %{$self->{'config'}}, 'iMSCP::Config', 'fileName' => "$self->{'cfgDir'}/roundcube.data";

	$self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
