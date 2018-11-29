=head1 NAME

 iMSCP::Provider::Service::Interface - Interface for init providers

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

package iMSCP::Provider::Service::Interface;

use strict;
use warnings;
use Carp 'croak';

=head1 DESCRIPTION

 Interface for init providers.

=head1 PUBLIC METHODS

=over 4

=item isEnabled( $service )

 Is the given service enabled?

 Param string $service Service name
 Return boolean TRUE if the service is enabled, FALSE otherwise, croak if the service doesn't exist

=cut

sub isEnabled
{
    my ( $self ) = @_;

    croak( sprintf( 'The %s class must implement the isEnabled() method', ref $self ));
}

=item enable( $service )

 Enable the given service

 If the service is already enabled, no failure *MUST* be raised.
 If the iMSCP::Provider::Service::Interface provider provide a compatibility
 layer for SysVinit scripts, the SysVinit script *SHOULD* be also enabled.

 Param string $service Service name
 Return void, croak on failure or if the service doesn't exist

=cut

sub enable
{
    my ( $self ) = @_;

    croak( sprintf( 'The %s class must implement the enable() method', ref $self ));
}

=item disable( $service )

 Disable the given service

 If the service is already disabled, no failure *MUST* be raised.
 
 If the iMSCP::Provider::Service::Interface provider provide a compatibility
 layer for SysVinit scripts, the SysVinit script *SHOULD* be also disabled.

 Param string $service Service name
 Return void, croak on failure or if the service doesn't exist

=cut

sub disable
{
    my ( $self ) = @_;

    croak( sprintf( 'The %s class must implement the disable() method', ref $self ));
}

=item remove( $service )

 Remove the given service

 If the service doesn't exist, no failure *MUST* be raised.
 If the iMSCP::Provider::Service::Interface provider provide a compatibility
 layer for SysVinit scripts, the SysVinit script *SHOULD* be also removed.

 Any cached result for service file resolving *MUST* be cleared.

 Param string $service Service name
 Return void, croak on failure

=cut

sub remove
{
    my ( $self ) = @_;

    croak( sprintf( 'The %s class must implement the remove() method', ref $self ));
}

=item start( $service )

 Start the given service

 If the service is already running, no failure *MUST* be raised.

 Param string $service Service name
 Return void, croak on failure or if the service doesn't exist

=cut

sub start
{
    my ( $self ) = @_;

    croak( sprintf( 'The %s class must implement the start() method', ref $self ));
}

=item stop( $service )

 Stop the given service

 If the service is not running, no failure *MUST* be raised.

 Param string $service Service name
 Return void, croak on failure or if the service doesn't exist

=cut

sub stop
{
    my ( $self ) = @_;

    croak( sprintf( 'The %s class must implement the stop() method', ref $self ));
}

=item restart( $service )

 Restart the given service

 If the service is not running, it *MUST* be started.

 Param string $service Service name
 Return void, croak on failure or if the service doesn't exist

=cut

sub restart
{
    my ( $self ) = @_;

    croak( sprintf( 'The %s class must implement the restart() method', ref $self ));
}

=item reload( $service )

 Reload the given service

 If the service doesn't support the reload action, it *MUST* be restarted.
 If the service is not running, it *MUST* be started.

 Param string $service Service name
 Return void, croak on failure or if the service doesn't exist

=cut

sub reload
{
    my ( $self ) = @_;

    croak( sprintf( 'The %s class must implement the reload() method', ref $self ));
}

=item isRunning( $service )

 Is the given service running?

 Param string $service Service name
 Return boolean TRUE if the service is running, FALSE otherwise, croak if the service doesn't exist

=cut

sub isRunning
{
    my ( $self ) = @_;

    croak( sprintf( 'The %s class must implement the isRunning() method', ref $self ));
}

=item hasService( $service )

 Does the given service exist?

 Due to the nature of this routine, its result *MUST* not be cached, nor rely
 on a previously cached result. This necessarily involve file resolving. A
 service can be non-existent at some time but this doesn't necessarily mean
 that it will not be available later on, and of course, the other way around is
 also possible.

 Param string $service Service name
 Return boolean TRUE if the service exits, FALSE otherwise

=cut

sub hasService
{
    my ( $self ) = @_;

    croak( sprintf( 'The %s class must implement the hasService() method', ref $self ));
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
