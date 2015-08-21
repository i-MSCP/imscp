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
use Carp;
use Hash::Util::FieldHash 'fieldhash';
use parent 'Common::SingletonClass';

fieldhash my %events;

=head1 DESCRIPTION

 The i-MSCP event manager is the central point of the engine event system.

 Event listeners are registered on the event manager and events are triggered through the event manager. The event
 listeners are CODE references that listen to one or many events.

=head1 PUBLIC METHODS

=over 4

=item register($event, $listener)

 Register an event listener on the given event

 Param string $event Event name
 Param coderef $listener Listener
 Return int 0 on success, croak on failure

=cut

sub register
{
	my ($self, $event, $listener) = @_;

	ref $listener eq 'CODE' or croak(sprintf('Invalid listener provided for the %s event', $event));
	push @{ $events{$self}->{$event} }, $listener;
	0;
}

=item unregister($event)

 Unregister any listener which listen to the given event

 Param string $event Event name
 Return  hash|undef

=cut

sub unregister
{
	my ($self, $event) = @_;

	delete $events{$self}->{$event};
}

=item trigger($event [, $param [ $paramN ]])

 Trigger the given event

 Param string $event Event name
 Param mixed OPTIONAL parameters to pass to the listeners
 Return int 0 on success, croak on failure

=cut

sub trigger
{
	my ($self, $event) = (shift, shift);

	if($events{$self}->{$event}) {
		for my $listener(@{$events{$self}->{$event}}) {
			local $@;
			eval { $listener->(@_) == 0 } or do {
				my $errorStr = $@ ? $@ : getMessageByType('error', { amount => 1, remove => 1 }) || 'Unknown error';
				require Data::Dumper;
				Data::Dumper->import();
				local $Data::Dumper::Terse = 1;
				local $Data::Dumper::Deparse = 1;
				croak(sprintf(
					"A listener registered on the '%s' event has failed: %s\nListener was:\n\n%s\n",
					$event, $errorStr, Dumper($listener)
				));
			};
		}
	}

	0;
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
	my $self = shift;

	$events{$self} = { };

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

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
