#!/usr/bin/perl

=head1 NAME

 iMSCP::Log - i-MSCP generic message storing mechanism

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010 - 2013 by internet Multi Server Control Panel
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
#
# @category    i-MSCP
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::Log;

use strict;
use warnings;

use Params::Check qw[check];
use iMSCP::CarpFixed ();

local $Params::Check::VERBOSE = 1;

=head1 DESCRIPTION

 Generic message storage mechanism allowing to store messages on a stack.

 Code upon based on the Log-Message module by Chris Williams, which has been simplified for i-MSCP.

=head1 PUBLIC METHODS

=over 4

=item new()

 Create new iMSCP::Log object

 Return iMSCP::Log

=cut

sub new
{
	my $class = shift;
	my %hash  = ();

	my $tmpl = {
		'stack' => { default  => [] }
	};

	my $args = check($tmpl, \%hash) or die(
		sprintf('Could not create a new iMSCP::Log object: %s1', Params::Check->last_error)
	);

	bless $args, $class
}

=item store()

 Create a new item hash and store it on the stack.

 Possible arguments you can give to it are:

=over 4

=item message

 This is the only argument that is required. If no other arguments are given, you may even leave off the C<message> key.
 The argument will then automatically be assumed to be the message.

=item tag

The tag to add to this message. If not provided, default tag 'none' will be used.

=item level

The level at which this message should be handled. If not provided, iMSCP::Log will use 'log'.

=back

Return true upon success and undef upon failure, as well as issue a warning as to why it failed.

=cut

sub store
{
    my $self = shift;
    my %hash = ();

	my $tmpl = {
		'message' => {
			'default' => 'empty log',
			'strict_type' => 1,
			'required' => 1
		},
		'tag' => { 'default' => 'none' },
		'level' => { 'default' => 'log' },
		'shortmess' => { 'default' => _clean(Carp::shortmess()) },
		'longmess' => { 'default' => _clean(Carp::longmess()) }
	};

	if( @_ == 1 ) {
		$hash{message} = shift;
	} else {
		%hash = @_;
	}

	my $args = check( $tmpl, \%hash ) or (
		warn(sprintf('Could not store error: %s', Params::Check->last_error)),
		return
	);

	my $item = {
		'when' => scalar localtime,
		'parent' => $self,
		'id' => scalar @{$self->{'stack'}},
		'message' => $args->{'message'},
		'tag' => $args->{'tag'},
		'level' => $args->{'level'},
		'shortmess' => $args->{'shortmess'},
		'longmess' => $args->{'longmess'}
	};

	push @{$self->{'stack'}}, $item;

	my $sub = "_$args->{'level'}";
	$self->$sub($item);

	1;
}

=item retrieve()

 Retrieve all message items matching the criteria specified from the stack.

 Here are the criteria you can discriminate on:

=over 4

=item tag

 A regex to which the tag must adhere. For example C<qr/\w/>.

=item level

 A regex to which the level must adhere.

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
		'tag' => { 'default' => qr/.*/ },
		'level' => { 'default' => qr/.*/ },
		'message' => { 'default' => qr/.*/ },
		'amount' => { 'default' => '' },
		'remove' => { 'default' => 0 },
		'chrono' => { 'default' => 1 }
	};

	if( @_ == 1 ) {
		$hash{'amount'} = shift;
	} else {
		%hash = @_;
	}

	my $args = check($tmpl, \%hash) or (
		warn(sprintf('Could not parse input: %s', Params::Check->last_error)),
		return
	);

	my @list =
		grep { $_->{'tag'} =~ /$args->{'tag'}/ ? 1 : 0 }
		grep { $_->{'level'} =~ /$args->{'level'}/ ? 1 : 0 }
		grep { $_->{'message'} =~ /$args->{'message'}/ ? 1 : 0 }
		grep { defined }
		$args->{'chrono'} ? @{$self->{'stack'}} : reverse @{$self->{'stack'}};

	my $amount = $args->{'amount'} || scalar @list;

    my @rv = map {
		$args->{'remove'} ? splice(@{$self->{'stack'}}, $_->{'id'}, 1, undef) : $_
    } scalar @list > $amount ? splice(@list, 0, $amount) : @list;

	wantarray ? @rv : $rv[0];
}

=item first()

 Retrieve the first item(s) stored on the stack. It will default to only retrieving one if called with no arguments, and
will always return results in chronological order.

 If you only supply one argument, it is assumed to be the amount you wish returned.

 Furthermore, it can take the same arguments as C<retrieve> can.

=cut

sub first
{
	my $self = shift;
	my $amt = @_ == 1 ? shift : 1;

	$self->retrieve('amount' => $amt, @_, 'chrono' => 1);
}

=item final()

 Retrieve the last item(s) stored on the stack. It will default to only retrieving one if called with no arguments, and
will always return results in reverse chronological order.

 If you only supply one argument, it is assumed to be the amount you wish returned.

 Furthermore, it can take the same arguments as C<retrieve> can.

=cut

sub final
{
	my $self = shift;
	my $amt = @_ == 1 ? shift : 1;

	$self->retrieve('amount' => $amt, @_, 'chrono' => 0);
}

=item flush()

 Removes all items from the stack and returns them to the caller

=cut

sub flush()
{
	my $self = shift;

	my @rv = grep { defined } @{$self->{'stack'}};
	$self->{'stack'} = [];

	@rv;
}

=back

=head1 PRIVATE METHODS

=over 4

=item

=cut

sub _clean
{
	map { s/\s*//; chomp; $_ } shift;
}

=item _log

 Will simply log the error on the stack, and do nothing special

=cut

sub _log
{
	1;
}

=item _carp

Will carp (see the Carp manpage) with the error, and add the timestamp of when it occurred.

=cut

sub _carp
{
	my $self = shift;
	my $item = shift;

	warn join " ", $item->{'message'}, $item->{'shortmess'}, 'at', $item->{'when'}, "\n";
}

=item _croak

 Will croak (see the Carp manpage) with the error, and add the timestamp of when it occurred.

=cut

sub _croak
{
	my $self = shift;
	my $item = shift;

	die join " ", $item->{'message'}, $item->{'shortmess'}, 'at', $item->{'when'}, "\n";
}

=item _cluck

 Will cluck (see the Carp manpage) with the error, and add the timestamp of when it occurred.

=cut

sub _cluck
{
	my $self = shift;
	my $item = shift;

	warn join " ", $item->{'message'}, $item->{'longmess'}, 'at', $item->{'when'}, "\n";
}

=item confess

 Will confess (see the Carp manpage) with the error, and add the timestamp of when it occurred

=cut

sub _confess
{
	my $self = shift;
	my $item = shift;

	die join " ", $item->{'message'}, $item->{'longmess'}, 'at', $item->{'when'}, "\n";
}

=item _die

Will simply die with the error message of the item

=cut

sub _die
{
	my $self = shift;
	my $item = shift;

	die $item->{'message'};
}

=item _warn

 Will simply warn with the error message of the item

=cut

sub _warn
{
	my $self = shift;
	my $item = shift;

	warn $item->{'message'};
}

=item _trace

 Will provide a traceback of this error item back to the first one that occurred, clucking with every item as it comes
across it.

=cut

sub _trace
{
	my $self = shift;
	my $item = shift;

	$_->cluck for $item->{'parent'}->retrieve('chrono' => 0);
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
