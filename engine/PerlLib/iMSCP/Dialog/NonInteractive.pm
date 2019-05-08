=head1 NAME

 iMSCP::Dialog::NonInteractive - Non-interactive FrontEnd

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

package iMSCP::Dialog::NonInteractive;

use strict;
use warnings;
use parent 'iMSCP::Dialog::FrontEndInterface';

=head1 DESCRIPTION

 Non-interactive FrontEnd for user interface.

=head1 PRIVATE METHODS

=over 4

=item AUTOLOAD

 Implement AUTOLOADING

=cut

sub AUTOLOAD
{
    ( my $method = $iMSCP::Dialog::NonInteractive::AUTOLOAD ) =~ s/.*:://;

    grep ( $method eq $_, qw/
        select multiselect boolean error note text string password startGauge
        setGauge endGauge hasGauge backup
    / ) or do {
        require Data::Dumper;
        Data::Dumper->import( 'Dumper' );
        my @argv = map { ref \$_ eq 'SCALAR' ? s/\\Z[0-7bBrRuUn]//gmr : $_ } @_;
        die( sprintf(
            "Undefined '%s' method.\n\nContext:\n\n%s",
            $method,
            Dumper( \@argv )
        ));
    };

    no strict 'refs';
    *{ $iMSCP::Dialog::NonInteractive::AUTOLOAD } = grep ( $method eq $_, qw/
        setGauge endGauge hasGauge note text backup
    / ) ? sub { 0; } : do {
        require Data::Dumper;
        Data::Dumper->import( 'Dumper' );
        my @argv = map { ref \$_ eq 'SCALAR' ? s/\\Z[0-7bBrRuUn]//gmr : $_ } @_;
        die( sprintf(
            "Invalid configuration or unexpected error.\n\nContext:\n\n%s",
            Dumper( \@argv )
        ));
    };

    goto &{ $iMSCP::Dialog::NonInteractive::AUTOLOAD };
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
