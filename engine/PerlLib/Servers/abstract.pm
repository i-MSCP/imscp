=head1 NAME

 Servers::abstract - i-MSCP server abstract implementation

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

package Servers::abstract;

use strict;
use warnings;
use iMSCP::EventManager;
use iMSCP::Service;

# Server package names
my %PACKAGES;

=head1 DESCRIPTION

 i-MSCP server abstract implementation.

=head1 CLASS METHODS

=over 4

=item getPriority( )

 Get server priority

 Return int Server priority

=cut

sub getPriority
{
    0;
}

=item factory( [ $package = $main::imscpConfig{$class} ] )

 Create and return a server instance

 Param string $package OPTIONAL Name of package
 Return Servers::httpd::Interface

=cut

sub factory
{
    my ($class, $package) = @_;

    unless ( $package ) {
        return $PACKAGES{$class}->getInstance() if $PACKAGES{$class};
        $PACKAGES{$class} = $package = $main::imscpConfig{$class} || 'Servers::noserver';
    }

    eval "require $package; 1" or die( $@ );
    $package->getInstance( eventManager => iMSCP::EventManager->getInstance());
}

=item getInstance( )

 See Servers::abstract

=cut

sub getInstance()
{
    my ($self) = @_;

    $self->factory();
}

=item can( $method )

 Checks if the server package implements the given method

 Bear in mind that this method always operates on the selected alternative.

 Param string $method Method name
 Return subref|undef

=cut

sub can
{
    my ($class, $method) = @_;

    return $PACKAGES{$class}->can( $method ) if $PACKAGES{$class};

    my $package = $main::imscpConfig{$class} || 'Servers::noserver';

    eval "require $package; 1" or die( $@ );
    $package->can( $method );
}

=item AUTOLOAD()

 Implement autoloading for inexistent methods

 Return mixed

=cut

sub AUTOLOAD
{
    ( my $method = our $AUTOLOAD ) =~ s/.*:://;

    __PACKAGE__->factory()->$method( @_ );
}

=item DESTROY

 Short-circuit AUTOLOADING
 
 Return void

=cut

DESTROY
    {

    }

=item END

 Process shutdown tasks

 Return void

=cut

END {
    return if $? || !%PACKAGES || ( defined $main::execmode && $main::execmode eq 'setup' );

    while ( my ( $server, $package ) = each( %PACKAGES ) ) {
        ( $package->can( 'shutdown' ) or next )->( $package->getInstance(), $server->getPriority())
    }
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
