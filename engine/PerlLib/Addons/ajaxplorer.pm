#!/usr/bin/perl

=head1 NAME

Addons::ajaxplorer - i-MSCP AjaxPlorer addon

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

package Addons::ajaxplorer;

use strict;
use warnings;
use iMSCP::Debug;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 AjaxPlorer addon for i-MSCP.

 AjaXplorer is a software that can turn any web server into a powerfull file management system and an alternative to
mainstream cloud storage providers.

 Project homepage: http://ajaxplorer.info/

=head1 CLASS METHODS

=over 4

=item factory()

 Implement singleton design pattern. Return instance of this class.

 Return Addons::ajaxplorer

=cut

sub factory
{
	Addons::ajaxplorer->new();
}

=back

=head1 PUBLIC METHODS

=over 4

=item preinstall()

 Run the preinstall method on the AjaxPlorer addon installer.

=cut

sub preinstall
{
	my $self = shift;

	use Addons::ajaxplorer::installer;
	Addons::ajaxplorer::installer->new()->preinstall();
}

=item install()

 Run the install method on the AjaxPlorer addon installer.

 Return int - 0 on success, 1 on failure

=cut

sub install
{
	my $self = shift;

	use Addons::ajaxplorer::installer;
	Addons::ajaxplorer::installer->new()->install();
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
