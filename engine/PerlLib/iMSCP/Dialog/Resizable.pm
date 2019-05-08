=head1 NAME

 iMSCP::Dialog::Whiptail - Base class for resizable frontEnds

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2019 Laurent Declercq <l.declercq@nuxwin.com>
#
# This library is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public
# License as published by the Free Software Foundation; either
# version 2.1 of the License, or (at your option) any later version.
#
# This library is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
# Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public
# License along with this library; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA

package iMSCP::Dialog::Resizable;

use strict;
use warnings;
use Carp 'croak';
use parent 'iMSCP::Dialog::FrontEndInterface';

=head1 DESCRIPTION

 Base class for resizable frontEnds.

=head1 PUBLIC METHODS/FUNCTIONS

=over 4

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 See iMSCP::Common::Singleton::_init()

=cut

sub _init
{
    my $self = shift;

    $self->SUPER::_init( @_ );

    $self->_resize();
    $SIG{'WINCH'} = sub {
        # There is a short period during global destruction where $self may
        # have been destroyed but the handler still operative.
        $self->_resize() if defined $self
    };

    $self;
}

=item _resize( )

 This method is called whenever the tty is resized, and probes to determine the
 new screen size

 return void

=cut

sub _resize
{
    my ( $self ) = @_;

    if ( exists $ENV{'LINES'} ) {
        $self->{'screenHeight'} = $ENV{'LINES'};
    } else {
        my ( $rows ) = `stty -a 2>/dev/null` =~ /rows (\d+)/s;
        $self->{'screenHeight'} = $rows // 25;
    }

    if ( exists $ENV{'COLUMNS'} ) {
        $self->{'screenWidth'} = $ENV{'COLUMNS'};
    } else {
        my ( $cols ) = `stty -a 2>/dev/null` =~ /columns (\d+)/s;
        $self->{'screenWidth'} = ( $cols || 80 );
    }
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
