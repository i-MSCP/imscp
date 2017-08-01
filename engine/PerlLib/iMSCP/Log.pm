=head1 NAME

 iMSCP::Log - i-MSCP generic message storing mechanism

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

package iMSCP::Log;

use strict;
use warnings;
use Params::Check qw[ check ];

local $Params::Check::VERBOSE = 1;

=head1 DESCRIPTION

 Generic message storage mechanism allowing to store messages on a stack.

 Code upon based on the Log-Message module by Chris Williams, which has been simplified for i-MSCP.

=head1 PUBLIC METHODS

=over 4

=item new( )

 Create new iMSCP::Log object

 Return iMSCP::Log

=cut

sub new
{
    my $class = shift;
    my %hash = @_;

    my $tmpl = {
        id    => {
            default     => 'dummy',
            strict_type => 1,
            required    => 1
        },
        stack => {
            default => []
        }
    };

    my $args = check( $tmpl, \%hash ) or die(
        sprintf( "Couldn't create a new iMSCP::Log object: %s1", Params::Check->last_error )
    );

    bless $args, $class
}

=item getId( )

 Get identifier

 Return string

=cut

sub getId
{
    $_[0]->{'id'};
}

=item store( )

 Create a new item hash and store it on the stack.

 Possible arguments you can give to it are:

=over 4

=item message

 This is the only argument that is required. If no other arguments are given, you may even leave off the C<message> key.
 The argument will then automatically be assumed to be the message.

=item tag

 The tag to add to this message. If not provided, default tag 'none' will be used.

=item when

 The time to add to this message. If not provided, value from localtime will be used

=back

 Return true upon success and undef upon failure, as well as issue a warning as to why it failed.

=cut

sub store
{
    my $self = shift;

    my %hash = ();
    my $tmpl = {
        when    => {
            default => scalar localtime,
                strict_type => 1,
        },
        message => {
            default     => 'empty log',
            strict_type => 1,
            required    => 1
        },
        tag     => { default => 'none' }
    };

    if ( @_ == 1 ) {
        $hash{'message'} = shift;
    } else {
        %hash = @_;
    }

    my $args = check( $tmpl, \%hash ) or (
        warn( sprintf( "Couldn't store message: %s", Params::Check->last_error )),
        return
    );

    my $item = {
        when    => $args->{'when'},
        message => $args->{'message'},
        tag     => $args->{'tag'}
    };

    push @{$self->{'stack'}}, $item;
    1;
}

=item retrieve( )

 Retrieve all message items matching the criteria specified from the stack.

 Here are the criteria you can discriminate on:

=over 4

=item tag

 A regex to which the tag must adhere. For example C<qr/\w/>.

=item message

 A regex to which the message must adhere.

=item amount

 Maximum amount of errors to return

=item chrono

 Return in chronological order, or not?

=item remove

 Remove items from the stack upon retrieval?

=back

 In scalar context it will return the first item matching your criteria and in list context, it will return all of them.

 If an error occurs while retrieving, a warning will be issued and undef will be returned.

=cut

sub retrieve
{
    my $self = shift;

    my %hash = ();
    my $tmpl = {
        tag     => {
            default => qr/.*/
        },
        message => {
            default => qr/.*/
        },
        amount  => {
            default => undef
        },
        remove  => {
            default => 0
        },
        chrono  => {
            default => 1
        }
    };

    # single arg means just the amount otherwise, they are named
    if ( @_ == 1 ) {
        $hash{'amount'} = shift;
    } else {
        %hash = @_;
    }

    my $args = check( $tmpl, \%hash ) or (
        warn( sprintf( "Couldn't parse input: %s", Params::Check->last_error )), return
    );

    my @list = ();
    for( @{$self->{'stack'}} ) {
        if ( $_->{'tag'} =~ /$args->{'tag'}/ && $_->{'message'} =~ /$args->{'message'}/ ) {
            push @list, $_;
            undef $_ if $args->{'remove'};
        }
    }

    @{$self->{'stack'}} = grep(defined, @{$self->{'stack'}}) if $args->{'remove'};
    my $amount = $args->{'amount'} || scalar @list;
    @list = ( $amount >= @list ) ? @list : @list[0 .. $amount-1] if @list;
    wantarray ? ( $args->{'chrono'} ) ? @list : reverse( @list ) : ( $args->{'chrono'} ) ? $list[0] : $list[$#list];
}

=item first( )

 Retrieve the first item(s) stored on the stack. It will default to only retrieving one if called with no arguments, and
 will always return results in chronological order.

 If you only supply one argument, it is assumed to be the amount you wish returned.

 Furthermore, it can take the same arguments as C<retrieve> can.

=cut

sub first
{
    my $self = shift;

    my $amt = @_ == 1 ? shift : 1;
    $self->retrieve( amount => $amt, @_, chrono => 1 );
}

=item final( )

 Retrieve the last item(s) stored on the stack. It will default to only retrieving one if called with no arguments, and
 will always return results in reverse chronological order.

 If you only supply one argument, it is assumed to be the amount you wish returned.

 Furthermore, it can take the same arguments as C<retrieve> can.

=cut

sub final
{
    my $self = shift;

    my $amt = @_ == 1 ? shift : 1;
    $self->retrieve( amount => $amt, @_, chrono => 0 );
}

=item flush( )

 Removes all items from the stack and returns them to the caller

=cut

sub flush
{
    splice @{$_[0]->{'stack'}};
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
