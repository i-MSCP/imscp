=head1 NAME

 Servers::cron::Abstract - i-MSCP Cron server abstract implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Servers::cron::Abstract;

use strict;
use warnings;
use parent 'Servers::cron::Interface', 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Cron server abstract implementation.

=head1 PRIVATE METHODS

=over 4

=item _validateCronTask( )

 Validate cron task attributes

 die if an attribute is not valid

=cut

sub _validateCronTask
{
    my ($self, $data) = @_;

    if ( $data->{'MINUTE'} =~ /^\@(?:reboot|yearly|annually|monthly|weekly|daily|midnight|hourly)$/ ) {
        $data->{'HOUR'} = $data->{'DAY'} = $data->{'MONTH'} = $data->{'DWEEK'} = '';
        return;
    }

    for my $attribute( qw/ minute hour day month dweek / ) {
        $self->_validateAttribute( $attribute, $data->{ uc( $attribute ) } );
    }
}

=item _validateAttribute( )

 Validate the given cron task attribute value

 Param string $name Attribute name
 Param string $value Attribute value
 die if an attribute is not valid

=cut

sub _validateAttribute
{
    my (undef, $name, $value) = @_;

    defined $name or die( '$name is undefined' );
    defined $value or die( '$value is undefined' );
    $value ne '' or die( sprintf( "Value for the '%s' cron task attribute cannot be empty", $name ));
    return if $value eq '*';

    my $step = '[1-9]?[0-9]';
    my $months = 'jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec';
    my $days = 'mon|tue|wed|thu|fri|sat|sun';
    my @namesArr = ();
    my $pattern;

    if ( $name eq 'minute' ) {
        $pattern = '[ ]*(\b[0-5]?[0-9]\b)[ ]*';
    } elsif ( $name eq 'hour' ) {
        $pattern = '[ ]*(\b[01]?[0-9]\b|\b2[0-3]\b)[ ]*';
    } elsif ( $name eq 'day' ) {
        $pattern = '[ ]*(\b[01]?[1-9]\b|\b2[0-9]\b|\b3[01]\b)[ ]*';
    } elsif ( $name eq 'month' ) {
        @namesArr = split '|', $months;
        $pattern = "([ ]*(\b[0-1]?[0-9]\b)[ ]*)|([ ]*($months)[ ]*)";
    } elsif ( $name eq 'dweek' ) {
        @namesArr = split '|', $days;
        $pattern = "([ ]*(\b[0]?[0-7]\b)[ ]*)|([ ]*($days)[ ]*)";
    }

    defined $pattern or die( sprintf( "Unknown '%s' cron task attribute", $name ));

    my $range = "((($pattern)|(\\*\\/$step)?)|((($pattern)-($pattern))(\\/$step)?))";
    my $longPattern = "$range(,$range)*";

    $value =~ /^$longPattern$/i or die(
        sprintf( "Invalid value '%s' given for the '%s' cron task attribute", $value, $name )
    );

    for my $testField ( split ',', $value ) {
        next if $testField !~ /^((($pattern)-($pattern))(\/$step)?)+$/;

        my @compare = split '-', $testField;
        my @compareSlash = split '/', $compare['1'];

        $compare[1] = $compareSlash[0] if scalar @compareSlash == 2;

        my ($left) = grep { $namesArr[$_] eq lc( $compare[0] ) } 0 .. $#namesArr;
        my ($right) = grep { $namesArr[$_] eq lc( $compare[1] ) } 0 .. $#namesArr;

        $left = $compare[0] unless $left;
        $right = $compare[1] unless $right;

        if ( int( $left ) > int( $right ) ) {
            die( sprintf( "Invalid value '%s' given for the '%s' cron task attribute", $value, $name ));
        }
    }

    undef;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
