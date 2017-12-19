=head1 NAME

 Servers::php - i-MSCP PHP server implementation

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

package Servers::php;

use strict;
use warnings;
use iMSCP::Service;

# PHP Servers::php::Abstract concret implementation package name
my $PACKAGE;

=head1 DESCRIPTION

 i-MSCP PHP server implementation.

=head1 CLASS METHODS

=over 4

=item getPriority( )

 Get server priority

 Return int Server priority

=cut

sub getPriority
{
    70;
}

=item factory( )

 Create and return a Servers::php::Abstract instance

 Return Servers::php

=cut

sub factory
{
    return $PACKAGE->getInstance() if $PACKAGE;

    $PACKAGE = "Servers::php::@{[ ucfirst $main::imscpConfig{'DISTRO_ID'} ] }";
    eval "require $PACKAGE; 1" or die( $@ );
    $PACKAGE->getInstance();
}

=item can( $method )

 Checks if the httpd server package provides the given method

 Param string $method Method name
 Return subref|undef

=cut

sub can
{
    my (undef, $method) = @_;

    return $PACKAGE->can( $method ) if $PACKAGE;

    my $package = "Servers::php::@{[ ucfirst $main::imscpConfig{'DISTRO_ID'} ] }";
    eval "require $package; 1" or die( $@ );
    $package->can( $method );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
