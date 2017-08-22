=head1 NAME

 iMSCP::EventManager::ListenerPriorityQueue - Event listener priority Queue

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

package iMSCP::EventManager::ListenerPriorityQueue;

use strict;
use warnings;
use List::Util qw/ max /;
use Scalar::Util qw / looks_like_number /;

=head1 DESCRIPTION

 This class implements a simple priority queue for event listeners.

=head1 PUBLIC METHODS

=over 4

=item new

 Constructor

 Return iMSCP::EventManager::ListenerPriorityQueue

=cut

sub new
{
    my ($class) = @_;

    bless {
            queue            => {},
            priorities       => {},
            highest_priority => undef
        },
        $class;
}

=item hasListener( $listener )
 
 Does this priority queue has the given listener?
 
 Param coderef $listener Listener
 Return bool TRUE if this priority queue has the given listener, FALSE otherwise, die on failure
 
=cut

sub hasListener
{
    my ($self, $listener) = @_;

    defined $listener or die '$listener parameter is not defined';
    ref $listener eq 'CODE' or die 'Invalid $listener. Expects CODE reference';

    exists $self->{'priorities'}->{$listener};
}

=item addListener( $listener [, $priority = 1 ] )

 Add the given listener into the queue

 Note that if a $listener is added twice, it replace the old-one.

 Param coderef $listener Listener
 Param int $priority OPTIONAL Listener priority (Highest values have highest priority)
 Return iMSCP::EventManager::ListenerPriorityQueue, die on failure
 
=cut

sub addListener
{
    my ($self, $listener, $priority) = @_;

    defined $listener or die '$listener parameter is not defined';
    $priority //= 1;
    looks_like_number $priority && ( $priority > -1001 && $priority < 1001 ) or die(
        'Invalid $priority. Expects an integer in range [-1000 .. 1000]'
    );
    ref $listener eq 'CODE' or die 'Invalid $listener. Expects CODE reference';
    $self->removeListener( $listener ) if $self->hasListener( $listener );
    $self->{'priorities'}->{$listener} = $priority;
    push @{$self->{'queue'}->{$priority}}, $listener;
    $self->{'highest_priority'} = max $priority, $self->{'highest_priority'} // $priority;
    $self;
}

=item removeListener( $listener )

 Remove the given listener from the queue

 Return bool TRUE if the given listener has been found and removed, FALSE otherwise, die on failure

=cut

sub removeListener
{
    my ($self, $listener) = @_;

    defined $listener or die '$listener parameter is not defined';
    ref $listener eq 'CODE' or die 'Invalid $listener. Expects CODE reference';
    my $oldPriority = $self->{'priorities'}->{$listener};
    return 0 unless defined $oldPriority;
    $self->{'queue'}->{$oldPriority} = [ grep { $_ ne $listener } @{$self->{'queue'}->{$oldPriority}} ];
    delete $self->{'priorities'}->{$listener};
    return 1 if @{$self->{'queue'}->{$oldPriority}};
    delete $self->{'queue'}->{$oldPriority};
    return 1 unless $self->{'highest_priority'} == $self->{'highest_priority'};
    $self->{'highest_priority'} = max keys( %{$self->{'queue'}} );
    1;
}

=item isEmpty( )

 Is the queue empty?

 Return bool TRUE if the queue is empty, FALSE otherwise

=cut

sub isEmpty
{
    !%{$_[0]->{'priorities'}};
}

=item count( )

 How many items are in the queue?

 Return int

=cut

sub count
{
    scalar keys %{$_[0]->{'priorities'}};
}

=item pop( )

 Pop item with highter priority from the queue

 Return coderef|undef Listener or undef if the queue is empty

=cut

sub pop
{
    my ($self) = @_;

    return undef unless defined $self->{'highest_priority'};
    my $listener = shift @{$self->{'queue'}->{$self->{'highest_priority'}}};

    unless ( @{$self->{'queue'}->{$self->{'highest_priority'}}} ) {
        delete $self->{'queue'}->{$self->{'highest_priority'}};
        $self->{'highest_priority'} = max keys( %{$self->{'queue'}} );
    }

    delete $self->{'priorities'}->{$listener};
    $listener;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
