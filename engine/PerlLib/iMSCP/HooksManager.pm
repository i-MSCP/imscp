#!/usr/bin/perl

=head1 NAME

 iMSCP::HooksManager - i-MSCP Hooks Manager

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
# @category     i-MSCP
# @copyright    2010-2014 by i-MSCP | http://i-mscp.net
# @author       Laurent Declercq <l.declercq@nuxwin.com>
# @link         http://i-mscp.net i-MSCP Home Site
# @license      http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::HooksManager;

use strict;
use warnings;

use iMSCP::Debug;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 The i-MSCP Hooks Manager is the central point of the i-MSCP's engine hooks system.

 The hook functions are registered on the manager and hooks are triggered through the manager.

 The hook functions are references to subroutines that hooks into the i-MSCP engine hooks. They can receive parameters
which, in most cases, are passed by reference to the hook function to allow it to act as filter.

=head1 PUBLIC METHODS

=over 4

=item register($hook, $hookFunction)

 Register the given hook function on the manager for the given hook.

 Param string $hook Hook name
 Param code_ref $hookFunction function
 Return int - 0 on success, 1 on failure

=cut

sub register($$$)
{
	my ($self, $hook, $hookFunction) = @_;

	if (ref $hookFunction eq 'CODE') {
		debug("Register hook function on the '$hook' hook");
		push(@{$self->{'hooks'}{$hook}}, $hookFunction);
	} else {
		error("Invalid hook function provided for the '$hook' hook");
		return 1;
	}

	0;
}

=item register($hook)

 Unregister hook functions for the given hook.

 Param string Hook name
 Return int - 0

=cut

sub unregisterHook($$)
{
	my ($self, $hook) = @_;

	delete $self->{'hooks'}->{$hook};

	0;
}

=item trigger($hook, [$params][...])

 Trigger the given hook.

 Param string Hook name
 Param mixed OPTIONAL parameters which are passed to the hook function
 Return int - 0 on success, other on failure

=cut

sub trigger($$)
{
	my ($self, $hook, @params) = @_;

    my $rs = 0;

	if(exists $self->{'hooks'}->{$hook}) {
		debug("Trigger $hook hook");

		my @hookFunctions = @{$self->{'hooks'}->{$hook}};

		for(@hookFunctions) {
			if($rs = $_->(@params)) {
				my $caller = (caller(1))[3] ? (caller(1))[3] : 'main';
				require Data::Dumper;
				Data::Dumper->import();
				local $Data::Dumper::Terse = 1;
				local $Data::Dumper::Deparse = 1;
				error(
					"A hook function registered on the '$hook' hook and triggered in $caller has failed.\n\n" .
					"Hook function code was:\n\n" . Dumper($_)
				);
				last;
			}
		}
	}

	$rs;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 This is called by getInstance(). Initialize instance.

 Return iMSCP::HooksManager

=cut

sub _init
{
	my $self = shift;

	$self->{'hooks'} = {};

	# Load any user hook files
	my $hooksDir = "$main::imscpConfig{'CONF_DIR'}/hooks.d";

	if(-d $hooksDir) {
		require $_ for glob "$hooksDir/*.pl";
	}

	$self;
}

=back

=head1 TODO

 - Add priorities support
 - Allow to get list of registered hooks

=cut

=head1 AUTHOR

Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
