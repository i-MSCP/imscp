=head1 NAME

 iMSCP::EventManager - i-MSCP Event Manager

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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
use autouse 'Clone' => qw/ clone /;
use Hash::Util::FieldHash 'fieldhash';
use iMSCP::Debug;
use iMSCP::EventManager::ListenerPriorityQueue;
use Scalar::Util qw / blessed /;
use parent 'Common::SingletonClass';

fieldhash my %EVENTS;

=head1 DESCRIPTION

 The i-MSCP event manager is the central point of the event system.

 Event listeners are registered on the event manager and events are triggered through the event manager. Event
 listeners are references to subroutines that listen to particular event(s).

=head1 PUBLIC METHODS

=over 4

=item trigger( $eventName [, @params ] )

 Trigger the given event

 Param string $eventName Event name
 Param mixed @params OPTIONAL parameters passed-in to the listeners
 Return int 0 on success, other on failure

=cut

sub trigger
{
    my ($self, $eventName, @params) = @_;

    unless (defined $eventName) {
        error( '$eventName parameter is not defined' );
        return 1;
    }

    return 0 unless $EVENTS{$self}->{$eventName};
    debug( sprintf( 'Triggering %s event', $eventName ) );

    # The priority queue acts as a heap, which implies that as items are popped
    # they are also removed. Thus we clone it for purposes of iteration.
    my $listenerPriorityQueue = clone( $EVENTS{$self}->{$eventName} );
    while(my $listener = $listenerPriorityQueue->pop( )) {
        my $rs = $listener->( @params );
        return $rs if $rs;
    }

    0;
}

=item register( $eventNames, $listener, priority )

 Register the given listener for the given event(s)

 Param string|arrayref $eventNames Event(s) that the listener listen to
 Param subref|object $listener A subroutine reference or object implementing $eventNames method
 Param int $priority OPTIONAL Listener priority (Highest values have highest priority)
 Return int 0 on success, 1 on failure

=cut

sub register
{
    my ($self, $eventNames, $listener, $priority) = @_;

    local $@;
    eval {
        defined $eventNames or die '$eventNames parameter is not defined';

        if (ref $eventNames eq 'ARRAY') {
            $self->register( $_, $listener, $priority ) for @{$eventNames};
            return 0;
        }

        unless ($EVENTS{$self}->{$eventNames}) {
            $EVENTS{$self}->{$eventNames} = iMSCP::EventManager::ListenerPriorityQueue->new( );
        }

        $EVENTS{$self}->{$eventNames}->addListener(
            ((blessed $listener) ? sub { $listener->$eventNames( @_ ) } : $listener),
            $priority
        );
    };
    if ($@) {
        error($@);
        return 1;
    }

    0;
}

=item unregister( $listener [, $eventName = undef ] )

 Unregister the given listener from all or the given event

 Param subref $listener Listener
 Param string $eventName Event name
 Return int 0 on success, 1 on failure

=cut

sub unregister
{
    my ($self, $listener, $eventName) = @_;

    local $@;
    eval {
        defined $listener or die '$listener parameter is not defined';

        if (defined $eventName) {
            $EVENTS{$self}->{$eventName}->removeListener( $listener ) if $EVENTS{$self}->{$eventName};
        } else {
            $_->removeListener( $listener ) for values %{$EVENTS{$self}};
        }
    };
    if ($@) {
        error($@);
        return 1;
    }

    0;
}

=item clearListeners( $eventName )

 Clear all listeners for the given event

 Param string $event Event name
 Return int 0 on success, 1 on failure

=cut

sub clearListeners
{
    my ($self, $eventName) = @_;

    unless (defined $eventName) {
        error( '$eventName parameter is not defined' );
        return 1;
    }

    delete $EVENTS{$self}->{$eventName};
    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return iMSCP::EventManager

=cut

sub _init
{
    my ($self) = @_;

    $EVENTS{$self} = { };

    for (glob "$main::imscpConfig{'CONF_DIR'}/listeners.d/*.pl") {
        debug( sprintf( 'Loading %s listener file', $_ ) );
        require $_;
    }

    $self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
