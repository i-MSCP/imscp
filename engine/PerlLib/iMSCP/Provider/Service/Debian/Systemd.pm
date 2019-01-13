=head1 NAME

 iMSCP::Provider::Service::Debian::Systemd - Debian systemd init provider

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

package iMSCP::Provider::Service::Debian::Systemd;

use strict;
use warnings;
use File::Basename 'fileparse';
use iMSCP::Boolean;
use parent qw/ iMSCP::Provider::Service::Systemd iMSCP::Provider::Service::Debian::SysVinit /;

=head1 DESCRIPTION

 systemd init provider for Debian-like distributions.
 
 Difference with the iMSCP::Provider::Service::Systemd init provider is the
 support for the 'is-enabled' API call that is not available in older Systemd
 version, and the support for SysVinit script removal.

 See:
  https://wiki.debian.org/systemd
  https://wiki.debian.org/systemd/Packaging
  https://wiki.debian.org/systemd/Integration

=head1 PUBLIC METHODS

=over 4

=item isEnabled( $unit )

 See iMSCP::Provider::Service::Systemd::isEnabled()

=cut

sub isEnabled
{
    my ( $self, $unit ) = @_;

    # We need to catch STDERR as we do not want raise failure when command
    # status is other than 0 but no STDERR
    my $ret = $self->_exec(
        [ $iMSCP::Provider::Service::Systemd::COMMANDS{'systemctl'}, 'is-enabled', $self->resolveUnit( $unit ) ], \my $stdout, \my $stderr
    );

    # The 'is-enabled' support for SysV init scripts isn't available in older
    # systemd versions. Thus, if the previous command has failed, we need do
    # another check by relying on the Debian SysVinit init provider.
    if ( $ret && length $stderr ) {
        ( $unit, undef, my $suffix ) = fileparse( $unit, qr/\.[^.]*/ );
        return $self->iMSCP::Provider::Service::Debian::SysVinit::isEnabled( $unit ) if grep ( $suffix eq $_, '', '.service' );

        # Not a SysVinit script and the previous systemd 'is-enabled' command has
        # failed. We propagate the error to caller.
        die( $stderr );
    }

    # The indirect state indicates that the unit is not enabled.
    chomp( $stdout );
    return FALSE if $stdout eq 'indirect';

    # The command status 0 indicate that the service is enabled
    $ret == 0;
}

=item remove( $unit )

 See iMSCP::Provider::Service::Interface::remove()

=cut

sub remove
{
    my ( $self, $unit ) = @_;

    defined $unit or die( 'parameter $unit is not defined' );

    # For the SysVinit scripts, we want operate only on services
    my ( $init, undef, $suffix ) = fileparse( $unit, qr/\.[^.]*/ );
    $self->iMSCP::Provider::Service::Debian::SysVinit::remove( $init ) if grep ( $suffix eq $_, '', '.service' );
    $self->SUPER::remove( $unit );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
