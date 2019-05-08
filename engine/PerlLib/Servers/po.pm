=head1 NAME

 Servers::po - i-MSCP po server implementation

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

package Servers::po;

use strict;
use warnings;

# po server instance
my $instance;

=head1 DESCRIPTION

 i-MSCP po server implementation.

=head1 PUBLIC METHODS

=over 4

=item factory( )

 Create and return po server instance

 Also trigger uninstallation of old po server when required.

 Return po server instance

=cut

sub factory
{
    return $instance if $instance;

    my $package = $::imscpConfig{'PO_PACKAGE'} || 'Servers::noserver';

    if ( %main::imscpOldConfig
        && exists $::imscpOldConfig{'PO_PACKAGE'}
        && $::imscpOldConfig{'PO_PACKAGE'} ne ''
        && $::imscpOldConfig{'PO_PACKAGE'} ne $package
    ) {
        eval "require $::imscpOldConfig{'PO_PACKAGE'}";
        die( $@ ) if $@;

        my $rs = $::imscpOldConfig{'PO_PACKAGE'}->getInstance()->uninstall();
        die( sprintf( "Couldn't uninstall the '%s' server", $::imscpOldConfig{'PO_PACKAGE'} )) if $rs;
    }

    eval "require $package";
    die( $@ ) if $@;
    $instance = $package->getInstance();
}

=item getPriority( )

 Get server priority

 Return int Server priority

=cut

sub getPriority
{
    30;
}

=item can( $method )

 Checks if the po server package provides the given method

 Param string $method Method name
 Return subref|undef

=cut

sub can
{
    my ( undef, $method ) = @_;

    my $package = $::imscpConfig{'PO_PACKAGE'} || 'Servers::noserver';
    eval "require $package";
    die( $@ ) if $@;
    $package->can( $method );
}

END
    {
        return if $? || !$instance || ( defined $::execmode
            && $::execmode eq 'setup'
        );

        $? = $instance->restart() if $instance->{'restart'};
    }

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
