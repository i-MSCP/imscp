#!/usr/bin/perl

=head1 NAME

 iMSCP::EventManager - i-MSCP event Manager

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

package iMSCP::EventManager;

use strict qw/vars subs/;
use warnings;

# Alias package to ensure backward compatibility (transitional)
*{'iMSCP::HooksManager::'} = \*{'iMSCP::EventManager::'};
$INC{'iMSCP/HooksManager.pm'} = $INC{'iMSCP/EventManager.pm'};

use iMSCP::Debug;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 The i-MSCP event manager is the central point of the engine event system.

 Event listeners are registered on the manager and events are triggered through the manager. The listeners are
references to subroutines that listen to specific events.

=head1 PUBLIC METHODS

=over 4

=item register($event, $callback)

 Register a listener for the given event

 Param string $event Name of event that the listener listen
 Param code $callback Callback which represent the event listener
 Return int 0 on success, 1 on failure

=cut

sub register
{
	my ($self, $event, $callback) = @_;

	if (ref $callback eq 'CODE') {
		debug("Registering listener on the '$event' event from " . ((caller(1))[3] || 'main'));
		push(@{$self->{'events'}{$event}}, $callback);
	} else {
		error("Invalid listener provided for the '$event' event");
		return 1;
	}

	0;
}

=item unregister($event)

 Unregister any listener which listen to the given event

 Param string $event Event name
 Return int 0

=cut

sub unregister
{
	my ($self, $event) = @_;

	delete $self->{'events'}->{$event};

	0;
}

=item trigger($event, [$params], [$paramsN])

 Trigger the given event

 Param string $event Event name
 Param mixed OPTIONAL parameters to pass to the listeners
 Return int 0 on success, other on failure

=cut

sub trigger
{
	my ($self, $event, @params) = @_;

    my $rs = 0;

	if(exists $self->{'events'}->{$event}) {
		debug("Triggering $event event");

		for(@{$self->{'events'}->{$event}}) {
			if($rs = $_->(@params)) {
				my $caller = (caller(1))[3] || 'main';
				require Data::Dumper;
				Data::Dumper->import();
				local $Data::Dumper::Terse = 1;
				local $Data::Dumper::Deparse = 1;
				error(
					"A listener registered on the '$event' event and triggered in $caller has failed.\n\n" .
					"Listener code was:\n\n" . Dumper($_)
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

 Initialize instance

 Return iMSCP::EventManager

=cut

sub _init
{
	my $self = $_[0];

	$self->{'events'} = { };

	# Load any user hook files
	my $hooksDir = "$main::imscpConfig{'CONF_DIR'}/hooks.d";

	if(-d $hooksDir) {
		require $_ for glob "$hooksDir/*.pl";
	}

	$self;
}

=back

=head1 TODO

 Priorities support

=cut

=head1 AUTHOR

Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
