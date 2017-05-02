=head1 NAME

 iMSCP::Provider::Service::Debian::Systemd - Service provider for Debian `systemd' service/socket units

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

package iMSCP::Provider::Service::Debian::Systemd;

use strict;
use warnings;
use File::Basename;
use parent qw/ iMSCP::Provider::Service::Systemd iMSCP::Provider::Service::Debian::Sysvinit /;

# Commands used in that package
my %COMMANDS = (
    dpkg      => '/usr/bin/dpkg',
    systemctl => '/bin/systemctl'
);

=head1 DESCRIPTION

 Service provider for Debian `systemd' service/socket units.

 The only differences with the base `systemd' provider are support for enabling, disabling and removing underlying
 sysvinit scripts. This provider also provides backware compatibility mode for older Debian systemd package versions.

 See:
  https://wiki.debian.org/systemd
  https://wiki.debian.org/systemd/Packaging
  https://wiki.debian.org/systemd/Integration

=head1 PUBLIC METHODS

=over 4

=item isEnabled( $unit )

 Is the given service/socket unit enabled?

 Param string $unit Unit name
 Return bool TRUE if the given service is enabled, FALSE otherwise

=cut

sub isEnabled
{
    my ($self, $unit) = @_;

    defined $unit or die( 'parameter $unit is not defined' );
    return $self->SUPER::isEnabled( $unit ) if $self->_isSystemd( $unit );
    return 0 if $unit =~ /\.socket$/;
    # is-enabled API call is not available for sysvinit scripts. We must invoke the Debian sysvinit provider
    # to known whether or not the sysvinit script is enabled.
    $self->iMSCP::Provider::Service::Debian::Sysvinit::isEnabled( $unit );
}

=item enable( $unit )

 Enable the given service/socket unit

 Param string $unit Unit name
 Return bool TRUE on success, FALSE on failure

=cut

sub enable
{
    my ($self, $unit) = @_;

    defined $unit or die( 'parameter $unit is not defined' );

    my $realUnit = $unit;
    if ($self->_isSystemd( $unit )) {
        my $unitFilePath = $self->getUnitFilePath( $unit );
        $realUnit = basename( readlink( $unitFilePath ), '.service', '.socket' ) if -l $unitFilePath;
    }

    # Note: Will automatically call update-rc.d in case of a sysvinit script
    $self->SUPER::enable( $realUnit );
}

=item disable( $unit )

 Disable the given service/socket unit

 Param string $unit Unit name
 Return bool TRUE on success, FALSE on failure

=cut

sub disable
{
    my ($self, $unit) = @_;

    defined $unit or die( 'parameter $unit is not defined' );

    my $realUnit = $unit;
    if ($self->_isSystemd( $unit )) {
        my $unitFilePath = $self->getUnitFilePath( $unit );
        $realUnit = basename( readlink( $unitFilePath ), '.service', '.socket' ) if -l $unitFilePath;
    }

    # Note: Will automatically call update-rc.d in case of a sysvinit script
    $self->SUPER::disable( $realUnit );
}

=item remove( $unit )

 Remove the given service/socket unit

 Param string $unit Unit name
 Return bool TRUE on success, FALSE on failure

=cut

sub remove
{
    my ($self, $unit) = @_;

    defined $unit or die( 'parameter $unit is not defined' );

    if ($self->_isSystemd( $unit )) {
        return 0 unless $self->SUPER::remove( $unit );
    }

    # Remove the underlying sysvinit script if any and make systemd aware of changes
    if ($self->_isSysvinit( $unit )) {
        return $self->iMSCP::Provider::Service::Debian::Sysvinit::remove( $unit )
            && $self->_exec( $COMMANDS{'systemctl'}, '--system', 'daemon-reload' ) == 0;
    }

    1;
}

=item hasService( $service )

 Does the given service exists?

 Param string $service Service name
 Return bool TRUE if the given service exits, FALSE otherwise

=cut

sub hasService
{
    my ($self, $service) = @_;

    defined $service or die( 'parameter $service is not defined' );

    return 1 if $self->SUPER::hasService( $service );
    return 0 if $service =~ /\.socket$/;
    $self->iMSCP::Provider::Service::Debian::Sysvinit::hasService( $service );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
