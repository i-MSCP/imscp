=head1 NAME

 Servers::cron - i-MSCP cron server implementation

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

package Servers::cron;

use strict;
use warnings;
use iMSCP::Debug qw/ fatal /;

# cron server instance
my $instance;

=head1 DESCRIPTION

 i-MSCP cron server implementation.

=head1 PUBLIC METHODS

=over 4

=item factory( )

 Create and return cron server instance

 Return cron server implementation

=cut

sub factory
{
    return $instance if $instance;

    my $package = 'Servers::cron::cron';
    eval "require $package";
    fatal( $@ ) if $@;
    $instance = $package->getInstance();
}

=item can( $method )

 Checks if the cron server package provides the given method

 Param string $method Method name
 Return subref|undef

=cut

sub can
{
    my (undef, $method) = @_;

    my $package = 'Servers::cron::cron';
    eval "require $package";
    fatal( $@ ) if $@;
    $package->can( $method );
}

=item getPriority( )

 Get server priority

 Return int Server priority

=cut

sub getPriority
{
    10;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
