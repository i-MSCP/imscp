=head1 NAME

 iMSCP::EventManager - i-MSCP Event Manager

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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

 The i-MSCP event manager is the central point of the event system.

 Event listeners are registered on the event manager and events are triggered through the event manager. Event
 listeners are references to subroutines that listen to particular event.

=head1 PUBLIC METHODS

=over 4

=item register( $event, @callables )

 Register one or many listeners for the given event

 Param string $event Name of event that the listener listen
 Param list @callables Callables which represent event listeners
 Return int 0 on success, 1 on failure

=cut

sub register
{
    my ($self, $event, @callables) = @_;

    unless (defined $event) {
        error( '$event parameter is not defined' );
        return 1;
    }

    unless (@callables) {
        error( 'At least one listener is required' );
        return 1;
    }

    for(@callables) {
        unless (ref $_ eq 'CODE') {
            error( sprintf( 'Invalid listener provided for the %s event', $event ) );
            return 1;
        }

        debug( sprintf( 'Registering listener on the %s event from %s', $event, (caller( 1 ))[3] || 'main' ) );
        push @{ $events{$self}->{$event} }, $_;
    }

    0;
}

=item unregister( $event )

 Unregister all listeners for the given event

 Param string $event Event name
 Return int 0 on success, 1 on failure

=cut

sub unregister
{
    my ($self, $event) = @_;

    unless (defined $event) {
        error( '$event parameter is not defined' );
        return 1;
    }

    delete $events{$self}->{$event};
    0;
}

=item trigger( $event [, @params ] )

 Trigger the given event

 Param string $event Event name
 Param mixed @params OPTIONAL parameters passed-in to the listeners
 Return int 0 on success, other on failure

=cut

sub trigger
{
    my ($self, $event, @params) = @_;

    unless (defined $event) {
        error( '$event parameter is not defined' );
        return 1;
    }

    return 0 unless exists $events{$self}->{$event};

    debug( sprintf( 'Triggering %s event', $event ) );

    my $rs = 0;
    for my $listener(@{$events{$self}->{$event}}) {
        $rs = $listener->( @params );
        return $rs if $rs;
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
    my $self = shift;

    $events{$self} = { };

    # Load listener files
    #
    # We try to load listeners from the hooks.d directory first (old location) to be sure that the listeners are loaded
    # even on upgrade
    my $listenersDir;

    if (-d "$main::imscpConfig{'CONF_DIR'}/hooks.d") {
        $listenersDir = "$main::imscpConfig{'CONF_DIR'}/hooks.d";
    } elsif (-d "$main::imscpConfig{'CONF_DIR'}/listeners.d") {
        $listenersDir = "$main::imscpConfig{'CONF_DIR'}/listeners.d";
    }

    if ($listenersDir) {
        for my $listenerFile(glob "$listenersDir/*.pl") {
            debug( sprintf( 'Loading %s listener file', $listenerFile ) );
            require $listenerFile;
        }
    }

    $self;
}

=back

=head1 AUTHOR

Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
