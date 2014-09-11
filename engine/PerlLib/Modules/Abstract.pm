#!/usr/bin/perl

=head1 NAME

 Modules::Abstract - Base class for i-MSCP modules

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
#
# @category    i-MSCP
# @copyright   2010-2014 by i-MSCP | http://i-mscp.net
# @author      Daniel Andreca <sci2tech@gmail.com>
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Modules::Abstract;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Servers;
use iMSCP::Addons;
use parent 'Common::Object';

=head1 DESCRIPTION

 i-MSCP modules abstract class.

=head1 PUBLIC METHODS

=over 4

=item getType()

 Get module type

 Return string Module type

=cut

sub getType
{
	fatal(ref($_[0]) . ' module must implement the getType() method');
}

=item process()

 Process action (add|delete|restore|disable) according item status.

 Return int 0 on success, other on failure

=cut

sub process
{
	fatal(ref($_[0]) . ' module must implement the process() method');
}

=item add()

 Add item

 Should be called for items with 'toadd|tochange|toenable' status.

 Return int 0 on success, other on failure

=cut

sub add
{
	 $_[0]->_runAllActions('add');
}

=item delete()

 Delete item

 Should be called for items with 'todelete' status.

 Return int 0 on success, other on failure

=cut

sub delete
{
	$_[0]->_runAllActions('delete');
}

=item restore()

 Restore item

 Should be called for items with 'torestore' status.

 Return int 0 on success, other on failure

=cut

sub restore
{
	$_[0]->_runAllActions('restore');
}

=item disable()

 Disable item

 Should be called for items with 'todisable' status.

 Return int 0 on success, other on failure

=cut

sub disable
{
	$_[0]->_runAllActions('disable');
}

=back

=head1 PRIVATES METHODS

=over 4

=item _loadData()

 Load data for current module

 Return int 0 on success, other on failure

=cut

sub _loadData
{
	fatal(ref($_[0]) . ' module must implement the _loadData() method');
}

=item _runAction($action, \@items, $itemType)

 Run the given action on each server/addon that implement it

 Param string $action Action to run
 Param array \@items Server|Addon name
 Param string $itemType Items type (Servers|Addons)
 Return int 0 on success, other on failure

=cut

sub _runAction
{
	my ($self, $action, $items, $itemType) = @_;

	for (@{$items}) {
		next if $_ eq 'noserver';

		my $dataProvider = '_get' .  (($itemType ne 'Addons') ? ucfirst($_) : 'Addons') . 'Data';
		my %moduleData = $self->$dataProvider($action);

		if(%moduleData) {
			my $package = "${itemType}::$_";

			eval "require $package";

			unless($@) {
				my $instance = ($itemType eq 'Addons') ? $package->getInstance() : $package->factory();

				if ($instance->can($action)) {
					debug("Calling action $action on $package");
					my $rs = $instance->$action(\%moduleData);
					return $rs if $rs;
				}
			} else {
				error($@);
				return 1;
			}
		}
	}

	0;
}

=item _runAllActions()

 Run actions (pre<Action>, <Action>, post<Action>) on each servers and addons

 Return int 0 on success, other on failure

=cut

sub _runAllActions
{
	my ($self, $action) = @_;

	my @servers = iMSCP::Servers->getInstance()->get();
	my @addons = iMSCP::Addons->getInstance()->get();

	my $moduleType = $self->getType();

	for('pre', '', 'post') {
		my $rs = $self->_runAction("$_$action$moduleType", \@servers, 'Servers');
		return $rs if $rs;

		$rs = $self->_runAction("$_$action$moduleType", \@addons, 'Addons');
		return $rs if $rs;
	}

	0;
}

=item _getAddonsData($action)

 Data provider method for i-MSCP addons

 This method must be implemented by any module which provides data for i-MSCP Addonss.

 Param string $action Action
 Return hash Hash containing module data

=cut

sub _getAddonsData
{
	();
}

=item _getCronData($action)

 Data provider method for cron servers

 This method must be implemented by any module which provides data for cron servers.

 Param string $action Action
 Return hash Hash containing module data

=cut

sub _getCronData
{
	();
}

=item _getFtpdData($action)

 Data provider method for Ftpd servers

 This method must be implemented by any module which provides data for Ftpd servers.

 Param string $action Action
 Return hash Hash containing module data

=cut

sub _getFtpdData
{
	();
}

=item _getHttpdData($action)

 Data provider method for Httpd servers

 This method must be implemented by any module which provides data for Httpd servers.

 Param string $action Action
 Return hash Hash containing module data

=cut

sub _getHttpdData
{
	();
}

=item _getMtaData($action)

 Data provider method for MTA servers

 This method must be implemented by any module which provides data for MTA servers.

 Param string $action Action
 Return hash Hash containing module data

=cut

sub _getMtaData
{
	();
}

=item _getNamedData($action)

 Data provider method for named servers

 This method must be implemented by any module which provides data for named servers.

 Param string $action Action
 Return hash Hash containing module data

=cut

sub _getNamedData
{
	();
}

=item _getPoData($action)

 Data provider method for IMAP/POP3 servers

 This method should be implemented by any module which provides data for IMAP/POP3 servers.

 Param string $action Action
 Return hash Hash containing module data

=cut

sub _getPoData
{
	();
}

=item _getSqldData($action)

 Data provider method for SQL servers

 This method should be implemented by any module which provides data for SQL servers.

 Param string $action Action
 Return hash Hash containing module data

=cut

sub _getSqldData
{
	();
}

=back

=head1 AUTHOR

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
