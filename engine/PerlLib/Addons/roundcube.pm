#!/usr/bin/perl

=head1 NAME

Addons::roundcube - i-MSCP Roundcube addon

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010 - 2012 by internet Multi Server Control Panel
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
# @copyright	2010 - 2012 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @author		Laurent Declercq <l.declercq@nuxwin.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Addons::roundcube;

use strict;
use warnings;
use iMSCP::Debug;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Roundcube addon for i-MSCP.

 RoundCube Webmail is a browser-based multilingual IMAP client with an application-like user interface.
 It provides full functionality expected from an e-mail client, including MIME support, address book,
folder manipulation and message filters.

 The user interface is fully skinnable using XHTML and CSS 2.

 Project homepage: http://www.roundcube.net/

=head1 CLASS METHODS

=over 4

=item factory()

 Implement singleton design pattern. Return instance of this class.

 Return Addons::roundcube

=cut

sub factory
{
	Addons::roundcube->new();
}

=back

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

	use Addons::roundcube::installer;
	Addons::roundcube::installer->new()->registerSetupHooks($hooksManager);
}

=item preinstall()

 Run the install method on the Roundcube addon installer.

=cut

sub preinstall
{
	my $self = shift;

	use Addons::roundcube::installer;
	Addons::roundcube::installer->new()->preinstall();
}

=item install()

 Run the install method on the Roundcube addon installer.

 Return int - 0 on success, 1 on failure

=cut

sub install
{
	my $self = shift;

	use Addons::roundcube::installer;
	Addons::roundcube::installer->new()->install();
}

=item delMail()

 Delete mail user from Roundcube database.

 Return int - 0 on success, other on failure

=cut

sub delMail
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	if($data->{'MAIL_TYPE'} =~ /_mail/) {
		my $database = iMSCP::Database->factory();
		$database->set('DATABASE_NAME', 'imscp_roundcube');
		$rs = $database->connect();
		return $rs if $rs;

		$rs = $database->doQuery('dummy', 'DELETE FROM `users` WHERE `username` = ?', $data->{'MAIL_ADDR'});
		if(ref $rs ne 'HASH') {
			error("Unable to remove mail user '$data->{'MAIL_ADDR'}' from the roundcube database: $rs");
			return $rs;
		}

		# Restore connection to imscp database
		$database->set('DATABASE_NAME', $main::imscpConfig{'DATABASE_NAME'});
		$rs = $database->connect();
	}

	$rs;
}

=back

=head1 AUTHORS

 - Daniel Andreca <sci2tech@gmail.com>
 - Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
