=head1 NAME

 iMSCP::EventManager - i-MSCP Event Manager

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by Laurent Declercq <l.declercq@nuxwin.com>
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

package iMSCP::EventManager;

use strict;
use warnings;
use Hash::Util::FieldHash 'fieldhash';
use iMSCP::Debug;
use parent 'Common::SingletonClass';

fieldhash my %events;

=head1 DESCRIPTION

 The i-MSCP event manager is the central point of the engine event system.

 Event listeners are registered on the event manager and events are triggered through the event manager. The event
 listeners are references to subroutines that listen to one or many events.

=head1 PUBLIC METHODS

=over 4

=item register($event, $callback)

 Register an event listener for the given event

 Param string $event Name of event that the listener listen
 Param code $callback Callback which represent the event listener
 Return int 0 on success, 1 on failure

=cut

sub register
{
	my ($self, $event, $callback) = @_;

	if (ref $callback eq 'CODE') {
		debug(sprintf('Registering listener on the %s event from %s', $event, (caller(1))[3] || 'main'));
		push @{ $events{$self}->{$event} }, $callback;
		0;
	} else {
		error(sprintf('Invalid listener provided for the %s event', $event));
		1;
	}
}

=item unregister($event)

 Unregister any listener which listen to the given event

 Param string $event Event name
 Return int 0

=cut

sub unregister
{
	my ($self, $event) = @_;

	delete $events{$self}->{$event};

	0;
}

=item trigger($event, [$param], [$paramN])

 Trigger the given event

 Param string $event Event name
 Param mixed OPTIONAL parameters to pass to the listeners
 Return int 0 on success, other on failure

=cut

sub trigger
{
	my ($self, $event) = (shift, shift);

	my $rs = 0;

	if(exists $events{$self}->{$event}) {
		debug(sprintf('Triggering %s event', $event));

		for my $listener(@{$events{$self}->{$event}}) {
			if($rs = $listener->(@_)) {
				my $caller = (caller(1))[3] || 'main';
				require Data::Dumper;
				Data::Dumper->import();
				local $Data::Dumper::Terse = 1;
				local $Data::Dumper::Deparse = 1;
				error(sprintf(
					"A listener registered on the %s event and triggered in %s has failed.\n\nListener code was: %s\n\n",
					$event, $caller, Dumper($listener)
				));
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

	$events{$self} = { };

	# Load listener files
	#
	# We try to load listeners from the hooks.d directory first (old location) to be sure that the listeners are loaded
	# even on upgrade
	my $listenersDir;

	if(-d "$main::imscpConfig{'CONF_DIR'}/hooks.d") {
		$listenersDir = "$main::imscpConfig{'CONF_DIR'}/hooks.d";
	} elsif(-d "$main::imscpConfig{'CONF_DIR'}/listeners.d") {
		$listenersDir = "$main::imscpConfig{'CONF_DIR'}/listeners.d";
	}

	if($listenersDir) {
		require $_ for glob "$listenersDir/*.pl";
	}

	$self;
}

=back

=head1 TODO

 Listener priorities support

=cut

=head1 AUTHOR

Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
