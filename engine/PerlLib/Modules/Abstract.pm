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
use iMSCP::Packages;
use parent 'Common::Object';

=head1 DESCRIPTION

 i-MSCP modules abstract class.

=head1 PUBLIC METHODS

=over 4

=item add()

 Add item

 Should be called for items with 'toadd|tochange|toenable' status.

 return int 0 on success, other on failure

=cut

sub add
{
	my $self = $_[0];

	$self->{'action'} = 'add';

	$self->_runAllActions();
}

=item delete()

 Delete item

 Should be called for items with 'todelete' status.

 return int 0 on success, other on failure

=cut

sub delete
{
	my $self = $_[0];

	$self->{'action'} = 'delete';

	$self->_runAllActions();
}

=item

 Restore item

 Should be called for items with 'torestore' status.

 return int 0 on success, other on failure

=cut restore()

sub restore
{
	my $self = $_[0];

	$self->{'action'} = 'restore';

	$self->_runAllActions();
}

=item disable()

 Disable item

 Should be called for database items with 'todisable' status.

 return int 0 on success, other on failure

=cut

sub disable
{
	my $self = $_[0];

	$self->{'action'} = 'disable';

	$self->_runAllActions();
}

=back

=head1 ABSTRACT METHODS

=over 4

=item loadData()

 Load data for current module

 return int 0 on success, other on failure

=cut

sub loadData
{
	fatal(ref($_[0]) . ' module must implement the loadData() method');
}

=item process()

 Process action (add|delete|restore|disable) according item status.

 return int 0 on success, other on failure

=cut

sub process
{
	fatal(ref($_[0]) . ' module must implement the process() method');
}

=back

=head1 PRIVATES METHODS

=over 4

=back

=item _init()

 Initialize instance

=cut

sub _init
{
	fatal(ref($_[0]) . ' module must implement the _init() method');
}

=item _runAction($action, \@items, $itemType)

 Run the given action on each server/package that implement it

 return int 0 on success, other on failure

=cut

sub _runAction ($$$$)
{
	my ($self, $action, $items, $itemType) = @_;

	for (@{$items}) {
		my $paramName = ($itemType eq 'Packages') ? 'Package' : $_;

		# Does this module provide data for the current item
		if(exists $self->{$paramName}) {
			my $package = "${itemType}::$_";

			eval "require $package";

			unless($@) {
				my $instance = ($itemType eq 'Packages') ? $package->getInstance() : $package->factory();

				if ($instance->can($action)) {
					debug("Calling action $action on $package");
					my $rs = $instance->$action($self->{$paramName});
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

 Trigger actions (pre<Action>, <Action>, post<Action>) on each i-MSCP servers and packages.

 return int 0 on success, other on failure

=cut

sub _runAllActions
{
	my $self = $_[0];

	my @servers = iMSCP::Servers->getInstance()->get();
	my @packages = iMSCP::Packages->getInstance()->get();

	# Build service/package data if provided by the module
	for(@servers, 'Packages') {
		next if $_ eq 'noserver';

		my $methodName = '_get' . ucfirst($_) . 'Data';

		my $rs = $self->$methodName();
		return $rs if $rs;
	}

	for('pre', '', 'post') {
		my $rs = $self->_runAction("$_$self->{'action'}$self->{'type'}", \@servers, 'Servers');
		return $rs if $rs;

		$rs = $self->_runAction("$_$self->{'action'}$self->{'type'}", \@packages, 'Packages');
		return $rs if $rs;
	}

	0;
}

=back

=head1 STUB METHODS

=over 4

=item _getPackagesData()

 Get package data

 This method should be implemented by any module which provides data for i-MSCP packages.
 Resulting data must be stored in an anonymous hash accessible through the 'packages' attribute.

 return int 0 on success, other on failure

=cut

sub _getPackagesData
{
	0;
}

=item _getCronData()

 Get CRON data

 This method should be implemented by any module which provides data for CRON service.
 Resulting data must be stored in an anonymous hash accessible through the 'cron' attribute.

 return int 0 on success, other on failure

=cut

sub _getCronData
{
	0;
}

=item _getFtpdData()

 Get FTPD data

 This method should be implemented by any module which provides data for FTPD service.
 Resulting data must be stored in an anonymous hash accessible through the 'ftpd' attribute.

 return int 0 on success, other on failure

=cut

sub _getFtpdData
{
	0;
}

=item _getHttpdData()

 Get Httpd data

 This method should be implemented by any module which provides data for HTTPD service.
 Resulting data must be stored in an anonymous hash accessible through the 'httpd' attribute.

 return int 0 on success, other on failure

=cut

sub _getHttpdData
{
	0;
}

=item _getMtaData()

 Get MTA data

 This method should be implemented by any module which provides data for MTA service.
 Resulting data must be stored in an anonymous hash accessible through the 'mta' attribute.

 return int 0 on success, other on failure

=cut

sub _getMtaData
{
	0;
}

=item _getNamedData()

 Get named data.

 This method should be implemented by any module which provides data for NAMED service.
 Resulting data must be stored in an anonymous hash accessible through the 'named' attribute.

 return int 0 on success, other on failure

=cut

sub _getNamedData
{
	0;
}

=item _getPoData()

 Get PO data

 This method should be implemented by any module which provides data for PO service.
 Resulting data must be stored in an anonymous hash accessible through the 'po' attribute.

 return int 0 on success, other on failure

=cut

sub _getPoData
{
	0;
}

=item _getSqldData()

 Get SQL data

 This method should be implemented by any module which provides data for SQL service.
 Resulting data must be stored in an anonymous hash accessible through the 'sqld' attribute.

 return int 0 on success, other on failure

=cut

sub _getSqldData
{
	0;
}

=back

=head1 AUTHOR

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
