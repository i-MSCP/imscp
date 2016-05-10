=head1 NAME

 iMSCP::Provider::Service::Systemd - Base service provider for `systemd` service/socket units

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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

package iMSCP::Provider::Service::Systemd;

use strict;
use warnings;
use File::Spec;
use iMSCP::File;
use parent 'iMSCP::Provider::Service::Sysvinit';

# Commands used in that package
my %commands = (
    systemctl => '/bin/systemctl --system'
);

# Paths in which service units must be searched
my @unitFilePaths = (
    '/etc/systemd/system',
    '/lib/systemd/system',
    '/usr/local/lib/systemd/system',
    '/usr/lib/systemd/system'
);

# Cache for unit file paths
my %unitFilePathsCache = ();

=head1 DESCRIPTION

 Base service provider for `systemd` service/socket units.

 See:
  - https://www.freedesktop.org/wiki/Software/systemd/
  - https://www.freedesktop.org/software/systemd/man/systemd.service.html
  - https://www.freedesktop.org/software/systemd/man/systemd.socket.html

=head1 PUBLIC METHODS

=over 4

=item isEnabled($unit)

 Is the given service/socket unit is enabled?

 Param string $unit Unit name
 Return bool TRUE if the given unit is enabled, FALSE otherwise

=cut

sub isEnabled
{
    my ($self, $unit) = @_;

    defined $unit or die( 'parameter $unit is not defined' );
    $unit .= '.service' unless $unit =~ /\.(?:service|socket)$/;
    $self->_exec( $commands{'systemctl'}, '--quiet', 'is-enabled', $unit ) == 0;
}

=item enable($unit)

 Enable the given service or socket unit

 Param string $unit Unit name
 Return bool TRUE on success, FALSE on failure

=cut

sub enable
{
    my ($self, $unit) = @_;

    defined $unit or die( 'parameter $unit is not defined' );
    $unit .= '.service' unless $unit =~ /\.(?:service|socket)$/;
    $self->_exec( $commands{'systemctl'}, '--force', '--quiet', 'enable', $unit ) == 0;
}

=item disable($unit)

 Disable the given service/socket unit

 Param string $unit Unit name
 Return bool TRUE on success, FALSE on failure

=cut

sub disable
{
    my ($self, $unit) = @_;

    defined $unit or die( 'parameter $unit is not defined' );
    $unit .= '.service' unless $unit =~ /\.(?:service|socket)$/;
    $self->_exec( $commands{'systemctl'}, '--quiet', 'disable', $unit ) == 0;
}

=item remove($unit)

 Remove the given service or socket unit

 Param string $unit Unit name
 Return bool TRUE on success, FALSE on failure

=cut

sub remove
{
    my ($self, $unit) = @_;

    defined $unit or die( 'parameter $unit is not defined' );
    $unit .= '.service' unless $unit =~ /\.(?:service|socket)$/;
    return 0 unless $self->stop( $unit ) && $self->disable( $unit );

    local $@;
    if (my $unitFilePath = eval { $self->getUnitFilePath( $unit ); }) {
        delete $unitFilePathsCache{$unit};
        return 0 if iMSCP::File->new( filename => $unitFilePath )->delFile();
    }

    $self->_exec( $commands{'systemctl'}, 'daemon-reload' ) == 0;
}

=item start($unit)

 Start the given service/socket unit

 Param string $unit Unit name
 Return bool TRUE on success, FALSE on failure

=cut

sub start
{
    my ($self, $unit) = @_;

    defined $unit or die( 'parameter $unit is not defined' );
    $unit .= '.service' unless $unit =~ /\.(?:service|socket)$/;
    $self->_exec( $commands{'systemctl'}, 'start', $unit ) == 0;
}

=item stop($unit)

 Stop the given service/socket unit

 Param string $unit Unit name
 Return bool TRUE on success, FALSE on failure

=cut

sub stop
{
    my ($self, $unit) = @_;

    defined $unit or die( 'parameter $unit is not defined' );
    $unit .= '.service' unless $unit =~ /\.(?:service|socket)$/;
    return 1 unless $self->isRunning( $unit );
    $self->_exec( $commands{'systemctl'}, 'stop', $unit ) == 0;
}

=item restart($unit)

 Restart the given service/socket unit

 Param string $unit Unit name
 Return bool TRUE on success, FALSE on failure

=cut

sub restart
{
    my ($self, $unit) = @_;

    defined $unit or die( 'parameter $unit is not defined' );
    $unit .= '.service' unless $unit =~ /\.(?:service|socket)$/;
    return $self->_exec( $commands{'systemctl'}, 'restart', $unit ) == 0 if $self->isRunning( $unit );
    $self->_exec( $commands{'systemctl'}, 'start', $unit ) == 0;
}

=item reload($service)

 Reload the given service unit

 Note: Not applicable to socket units

 Param string $unit Unit name
 Return bool TRUE on success, FALSE on failure

=cut

sub reload
{
    my ($self, $unit) = @_;

    defined $unit or die( 'parameter $unit is not defined' );
    $unit .= '.service' unless $unit =~ /\.service$/;
    return $self->_exec( $commands{'systemctl'}, 'reload', $unit ) == 0 if $self->isRunning( $unit );
    $self->start( $unit );
}

=item isRunning($unit)

 Is the given service/scoket is running (active)?

 Param string $unit Unit name
 Return bool TRUE if the given service is running, FALSE otherwise

=cut

sub isRunning
{
    my ($self, $unit) = @_;

    defined $unit or die( 'parameter $unit is not defined' );
    $unit .= '.service' unless $unit =~ /\.(?:service|socket)$/;
    $self->_exec( $commands{'systemctl'}, 'is-active', $unit ) == 0;
}

=item getUnitFilePath($unit)

 Get full path of the given unit

 Param string $unit Unit name
 Return string Unit path on success, die on failure

=cut

sub getUnitFilePath
{
    my ($self, $unit) = @_;

    defined $unit or die( 'parameter $unit is not defined' );
    $unit .= '.service' unless $unit =~ /\.(?:service|socket)$/;
    $self->_searchUnitFile( $unit );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _isSystemd($unit)

 Is the given service managed by a native systemd service unit file?

 Param string $unit Unit name
 Return bool TRUE if the given service is managed by a systemd service unit file, FALSE otherwise

=cut

sub _isSystemd
{
    my ($self, $unit) = @_;

    $unit .= '.service' unless $unit =~ /\.(?:service|socket)$/;
    local $@;
    eval { $self->_searchUnitFile( $unit ); };
}

=item _searchUnitFile($unit)

 Search the given unit configuration file in all available paths

 Param string $unit Unit name
 Return string unit file path on success, die on failure

=cut

sub _searchUnitFile
{
    my ($self, $unit) = @_;

    return $unitFilePathsCache{$unit} if $unitFilePathsCache{$unit};

    for my $path(@unitFilePaths) {
        my $filepath = File::Spec->join( $path, $unit );
        return $unitFilePathsCache{$unit} = $filepath if -f $filepath;
    }

    die( sprintf( 'Could not find systemd %s unit configuration file', $unit ) );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
