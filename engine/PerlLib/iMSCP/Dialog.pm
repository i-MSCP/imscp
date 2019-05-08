=head1 NAME

 iMSCP::Dialog - Proxy to various iMSCP::Dialog::FrontEndInterface implementations

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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

package iMSCP::Dialog;

use strict;
use warnings;
use Carp;
use iMSCP::Boolean;
use iMSCP::Debug ();
use iMSCP::Getopt;
use parent qw/ Common::SingletonClass iMSCP::Dialog::FrontEndInterface /;

=head1 DESCRIPTION

 Proxy to iMSCP::Dialog::FrontEndInterface implementations

=head1 PUBLIC METHODS

=over 4

=item executeDialogs( \@stack [, $fromStack = FALSE [, $state = 0] ] )

 Execute a stack of dialog subroutines

 Implements a simple state machine with backup capability
  - Dialog subroutines SHOULD NOT fail. However, they can die on
    unrecoverable errors
  - On success a dialog subroutine MUST return 0
  - When skipped a dialog subroutine MUST return 20
  - When backing-up a dialog subroutine MUST return 30
  - When aborting a dialog subroutine MUST return 50

 When a dialog stack is executed through another dialog stack, the
 $fromStack flag SHOULD BE set to make the backup capability wokring.

 @param $dialogs \@stack Stack of dialogs subroutines
 @param bool $fromStack Flag indicating whether or not the given dialog
 stack is executed from another dialog stack
 @param int $state Initial state. $state must be an index of the stack.
 @return int 0 (Success), 1 (Failure), 30 (Back) 50 (Abort)

=cut

# Keep last return value of the execute() call.
# See getLastExecuteRetval() for further details.
my $ret = 0;

sub execute
{
    my ( $self, $stack, $fromStack, $state ) = @_;
    $fromStack //= FALSE;
    $state //= 0;

    ref $stack eq 'ARRAY' or die(
        'Invalid or missing stack parameter. Array expected.'
    );

    ref \$fromStack eq 'SCALAR' && grep ( $fromStack eq $_, TRUE, FALSE ) or die(
        'Invalid or missing stack parameter. Boolean expected.'
    );

    ref \$state eq 'SCALAR' && ( $state >= 0 && $state <= @{ $stack } ) or die(
        'Invalid $state parameter. Array expected.'
    );

    $ret = eval {
        my ( $prevRet, $backup ) = ( 0, $self->backup() );

        while ( $state < @{ $stack } ) {
            $backup && $self->backup( ( $fromStack && ( $ret == 0 || $state > 0 ) ) || $state > 0 );

            $ret = $stack->[$state]->( $self );
            return $ret if $ret == 50 || (
                $fromStack && $state == 0 && $ret == 30
            );

            if ( $state == 0
                || $ret == 0
                || ( $ret == 20 && $prevRet != 30 )
            ) {
                $prevRet = $ret;
                $state++;
                next;
            }

            $prevRet = 30 if $ret != 20;
            $state--;
        }
    };
    if ( $@ ) {
        die if $fromStack;
        iMSCP::Debug::error( $@ );
        $ret = 1;
    }

    $ret;
}

=item executeRetval( [$retval = $ret ] )

 Set and/or return value of the last execute() call

 This method make possible to get return value of the last execute()
 method call. This is used when executing a dialog stack from another
 dialog stack, for the backup capability implementation. It is also
 possible set new retval for manual backup capability implementation.
 See the test= suites (./Dialog/tests.pl) for a usage example.

 Int int 0 (Success), 1 (Failure), 30 (Back) 50 (Abort)

=cut

sub executeRetval
{
    my (undef, $retval) = @_;

    $ret = $retval if defined $retval;
    $ret;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 See iMSCP::Common::Singleton::_init()

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'_frontEnd'} = do {
        if ( iMSCP::Getopt->noprompt ) {
            require iMSCP::Dialog::NonInteractive;
            iMSCP::Dialog::NonInteractive->getInstance();
        } else {
            require iMSCP::Dialog::Dialog;
            iMSCP::Dialog::Dialog->getInstance();
        }
    };
    
    # Allows localization of dialog frontEnd options through this object
    $self->{'_opts'} = $self->{'_frontEnd'}->{'_opts'} // {};
    $self->{'program'} = $self->{'_frontEnd'}->{'program'} // 'none';

    $self;
}

=item AUTOLOAD( )

 Proxy to iMSCP::Dialog::FrontEndInterface implementations

=cut

sub AUTOLOAD
{
    ( my $method = $iMSCP::Dialog::AUTOLOAD ) =~ s/.*:://;

    no strict 'refs';
    *{ $iMSCP::Dialog::AUTOLOAD } = sub {
        ( shift )->{'_frontEnd'}->$method( @_ );
    };

    goto &{ $iMSCP::Dialog::AUTOLOAD };
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
