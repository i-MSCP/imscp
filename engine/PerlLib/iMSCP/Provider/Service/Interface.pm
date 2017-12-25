=head1 NAME

 iMSCP::Provider::Service::Interface - Interface for service providers

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

=head1 DESCRIPTION

 Interface for service providers

=head1 PUBLIC METHODS

=over 4

=item isEnabled( $service )

 Does the given service is enabled?

 Return TRUE if the given service is enabled, FALSE otherwise

=cut

sub isEnabled
{
    die 'not implemented';
}

=item enable( $service )

 Enable the given service

 Param string $service Service name
 Return bool TRUE on success, die on failure

=cut

sub enable
{
    die 'not implemented';
}

=item disable( $service )

 Disable the given service

 Param string $service Service name
 Return bool TRUE on success, die on failure

=cut

sub disable
{
    die 'not implemented';
}

=item remove( $service )

 Remove the given service

 Param string $service Service name
 Return bool TRUE on success, die on failure

=cut

sub remove
{
    die 'not implemented';
}

=item start( $service )

 Start the given service

 Param string $service Service name
 Return bool TRUE on success, die on failure

=cut

sub start
{
    die 'not implemented';
}

=item stop( $service )

 Stop the given service

 Param string $service Service name
 Return bool TRUE on success, die on failure

=cut

sub stop
{
    die 'not implemented';
}

=item restart( $service )

 Restart the given service

 Param string $service Service name
 Return bool TRUE on success, die on failure

=cut

sub restart
{
    die 'not implemented';
}

=item reload( $service )

 Reload the given service

 Param string $service Service name
 Return bool TRUE on success, die on failure

=cut

sub reload
{
    die 'not implemented';
}


=item isRunning( $service )

 Is the given service running?

 Param string $service Service name
 Return bool TRUE if the given service is running, FALSE otherwise

=cut

sub isRunning
{
    die 'not implemented';
}

=item hasService( $service )

 Does the given service exists?

 Return bool TRUE if the given service exits, FALSE otherwise

=cut

sub hasService
{
    die 'not implemented';
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
