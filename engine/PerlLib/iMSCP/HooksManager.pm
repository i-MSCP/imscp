#!/usr/bin/perl

=head1 NAME

iMSCP::HooksManager - i-MSCP Hooks Manager

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
#
# @category		i-MSCP
# @copyright	2010 - 2012 by i-MSCP | http://i-mscp.net
# @author		Laurent Declercq <l.declercq@nuxwin.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::HooksManager;

use strict;
use warnings;
use iMSCP::Debug;
use Data::Dumper;
use base 'Common::SingletonClass';

=head1 DESCRIPTION

The i-MSCP Hooks Manager class is the central point of the i-MSCP's engine hooks system.

The hook functions are registered on the manager and hooks are triggered through the
manager.

The hook functions are references to subroutines that hooks into the i-MSCP engine hooks.
They can receive parameters that in most cases, are passed by reference to allow them
to act as filters.

The i-MSCP hooks are triggered once. That mean that if you want trigger them again, the
hook functions must re-register by itself on the manager. Any hook function must return
0 on success and 1 on failure.

=head1 METHODS

=over 4

=item _init()

This is called by new(). Initialize hooks manager instance;

=cut

sub _init
{
	my $self = shift;

	$self->{hooks} = {};
}

=item getInstance()

Implements Singleton Design Pattern - Returns instance of this class.

=cut

sub getInstance
{
	return iMSCP::HooksManager->new();
}

=item register(hookName, hookFunction)

Register the given hook function on the manager.

Return 0 on success, 1 on failure.

=cut

sub register($$$)
{
	my $self = shift;
	my $hookName = shift;
	my $hookFunction = shift;

	if (ref $hookFunction eq 'CODE') {
		debug("Register hook function on the '$hookName' hook");
		push(@{$self->{hooks}{$hookName}}, $hookFunction);
	} else {
		error("Invalid hook function provided for the '$hookName' hook");
		return 1;
	}

	0;
}

=item trigger(hookName, [parameters][...])

Trigger the given hook.

Return 0 on success, 1 on failure.

=cut

sub trigger($$)
{
	my $self = shift;
    my $hookName = shift;
    my $rs = 0;

	debug("Trigger the $hookName hook");

	if(exists $self->{hooks}->{$hookName}) {
		my @hookFunctions = @{$self->{hooks}->{$hookName}};
		delete $self->{hooks}->{$hookName};
		for(@hookFunctions) {
			$rs = $_->(@_);

			if($rs) {
				my $caller = (caller(1))[3] ? (caller(1))[3] : 'main';
				error("An hook function registered on the '$hookName' hook triggered in $caller has failed")
			}
			return $rs if $rs;
		}
	}

	0;
}

=back

=head1 TODO

 - Add priorities support
 - Allow to unregister hook functions for a specific hook
 - Allow to get list of registered hooks

=cut

=head1 AUTHOR

Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
