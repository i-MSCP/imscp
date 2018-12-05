=head1 NAME

 iMSCP::Provider::Service::Systemd - systemd init provider

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

package iMSCP::Provider::Service::Systemd;

use strict;
use warnings;
use Carp 'croak';
use File::Basename qw/ basename fileparse /;
use File::Spec;
use iMSCP::Boolean;
use iMSCP::Debug qw/ debug getMessageByType /;
use iMSCP::File;
use iMSCP::Dir;
use parent 'iMSCP::Provider::Service::SysVinit';

# Commands used in that package
our %COMMANDS = (
    systemctl => '/bin/systemctl'
);

# Paths in which service units must be searched
# Order is signifiant, specially for the remove action
my @UNITFILEPATHS = (
    '/etc/systemd/system',
    '/usr/local/lib/systemd/system',
    '/lib/systemd/system',
    '/usr/lib/systemd/system'
);

=head1 DESCRIPTION

 systemd init provider.

 See https://www.freedesktop.org/wiki/Software/systemd/

=head1 PUBLIC METHODS

=over 4

=item isEnabled( $unit )

 See iMSCP::Provider::Service::Interface::isEnabled()

=cut

sub isEnabled
{
    my ( $self, $unit ) = @_;

    defined $unit or croak( 'Missing or undefined $unit parameter' );

    # We need to catch STDERR as we do not want croak on failure when
    # command status is other than 0 but no STDERR
    my $ret = $self->_exec( [ $COMMANDS{'systemctl'}, 'is-enabled', $self->resolveUnit( $unit ) ], \my $stdout, \my $stderr );
    croak( $stderr ) if $ret && length $stderr;

    # The indirect state indicates that the unit is not enabled.
    chomp( $stdout );
    return FALSE if $stdout eq 'indirect';

    # The command status 0 indicate that the service is enabled
    $ret == 0;
}

=item enable( $unit )

 See iMSCP::Provider::Service::Interface::enable()

=cut

sub enable
{
    my ( $self, $unit ) = @_;

    defined $unit or croak( 'Missing or undefined $unit parameter' );

    $self->unmask( $unit );

    # We make use of the --force flag to overwrite any conflicting symlinks.
    # This is particularly usefull in case the unit provides an alias that is
    # also provided as a SysVinit script and which has been masked. For instance:
    # - mariadb.service unit that provides the mysql.service unit as alias
    # - mysql SysVinit script which is masked (/etc/systemd/system/mysql.service => /dev/null)
    # In such a case, and without the --force option, systemd would fails to create the symlink
    # for the mysql.service alias as the mysql.service symlink (masked unit) would already exist.
    $self->_exec( [ $COMMANDS{'systemctl'}, '--force', '--quiet', 'enable', $self->resolveUnit( $unit ) ] );
}

=item disable( $unit )

 See iMSCP::Provider::Service::Interface::disable()

=cut

sub disable
{
    my ( $self, $unit ) = @_;

    defined $unit or croak( 'Missing or undefined $unit parameter' );

    $self->_exec( [ $COMMANDS{'systemctl'}, '--quiet', 'disable', $self->resolveUnit( $unit ) ] );
    $self->mask( $unit );
}

=item mask( $unit )

 Mask the given unit
 
 Return void, croak on failure

=cut

sub mask
{
    my ( $self, $unit ) = @_;

    defined $unit or croak( 'Missing or undefined $unit parameter' );

    # Units located in the /etc/systemd/system directory cannot be masked
    unless ( index( $self->resolveUnit( $unit, TRUE ), '/etc/systemd/system/' ) == 0 ) {
        $self->_exec( [ $COMMANDS{'systemctl'}, '--quiet', 'mask', $self->resolveUnit( $unit ) ] );
    }
}

=item unmask( $unit )

 Unmask the given unit
 
 Return void, croak on failure

=cut

sub unmask
{
    my ( $self, $unit ) = @_;

    defined $unit or croak( 'Missing or undefined $unit parameter' );

    $self->_exec( [ $COMMANDS{'systemctl'}, '--quiet', 'unmask', $self->resolveUnit( $unit ) ] );
}

=item remove( $unit )

 See iMSCP::Provider::Service::Interface::remove()

=cut

sub remove
{
    my ( $self, $unit ) = @_;

    defined $unit or croak( 'Missing or undefined $unit parameter' );

    return unless $self->hasService( $unit );

    $self->stop( $unit );
    $self->unmask( $unit );

    # We need check again for existence of the unit because there could have
    # been an orphaned masked unit
    $self->disable( $unit ) if $self->hasService( $unit );

    # Remove drop-in directories if any
    for my $dir ( '/etc/systemd/system/', '/usr/local/lib/systemd/system/' ) {
        my $dropInDir = $dir;
        ( undef, undef, my $suffix ) = fileparse( $unit, qw/ .automount .device .mount .path .scope .service .slice .socket .swap .target .timer / );
        $dropInDir .= $unit . ( $suffix // '.service' ) . '.d';
        next unless -d $dropInDir;
        debug( sprintf( 'Removing the %s drop-in directory', $dropInDir ));
        eval { iMSCP::Dir->new( dirname => $dropInDir )->remove(); };
        !$@ or croak( $@ );
    }

    # Remove unit files if any
    while ( my $unitFilePath = eval { $self->resolveUnit( $unit, TRUE, TRUE ) } ) {
        # We do not want remove units that are shipped by distribution packages
        last unless index( $unitFilePath, '/etc/systemd/system/' ) == 0 || index( $unitFilePath, '/usr/local/lib/systemd/system/' ) == 0;
        debug( sprintf( 'Removing the %s unit', $unitFilePath ));
        iMSCP::File->new( filename => $unitFilePath )->delFile() == 0 or croak(
            getMessageByType( 'error', { amount => 1, remove => TRUE } ) || 'Unknown error'
        );
    }

    $self->daemonReload();
}

=item start( $unit )

 See iMSCP::Provider::Service::Interface::start()

=cut

sub start
{
    my ( $self, $unit ) = @_;

    defined $unit or croak( 'Missing or undefined $unit parameter' );

    $self->_exec( [ $COMMANDS{'systemctl'}, 'start', $self->resolveUnit( $unit ) ] );
}

=item stop( $unit )

 See iMSCP::Provider::Service::Interface::stop()

=cut

sub stop
{
    my ( $self, $unit ) = @_;

    defined $unit or croak( 'Missing or undefined $unit parameter' );

    $self->_exec( [ $COMMANDS{'systemctl'}, 'stop', $self->resolveUnit( $unit ) ] );
}

=item restart( $unit )

 See iMSCP::Provider::Service::Interface::restart()

=cut

sub restart
{
    my ( $self, $unit ) = @_;

    defined $unit or croak( 'Missing or undefined $unit parameter' );

    $self->_exec( [ $COMMANDS{'systemctl'}, 'restart', $self->resolveUnit( $unit ) ] );
}

=item reload( $service )

 See iMSCP::Provider::Service::Interface::reload()

=cut

sub reload
{
    my ( $self, $unit ) = @_;

    defined $unit or croak( 'Missing or undefined $unit parameter' );

    $self->_exec( [ $COMMANDS{'systemctl'}, 'reload-or-restart', $self->resolveUnit( $unit ) ] );
}

=item isRunning( $service )

 See iMSCP::Provider::Service::Interface::isRunning()

=cut

sub isRunning
{
    my ( $self, $unit ) = @_;

    defined $unit or croak( 'Missing or undefined $unit parameter' );

    # We need to catch STDERR as we do not want croak on failure when command
    # status is other than 0 but no STDERR
    my $ret = $self->_exec( [ $COMMANDS{'systemctl'}, 'is-active', $self->resolveUnit( $unit ) ], undef, \my $stderr );
    croak( $stderr ) if $ret && length $stderr;
    $ret == 0;
}

=item hasService( $unit )

 See iMSCP::Provider::Service::Interface::hasService()

=cut

sub hasService
{
    my ( $self, $unit ) = @_;

    defined $unit or croak( 'Missing or undefined $unit parameter' );

    eval { $self->resolveUnit( $unit, FALSE, TRUE ); };
}

=item resolveUnit( $unit [, $withpath =  FALSE [, $nocache = FALSE ] ] )

 Resolves the given unit

 Units can be aliased (have an alternative name), by creating a symlink from
 the new name to the existing name in one of the unit search paths. Due to
 unexpected behaviors when using alias names, this method always resolve the
 alias units. See the following reports for a better understanding of the
 situation:
  - https://github.com/systemd/systemd/issues/7875
  - https://github.com/systemd/systemd/issues/7874

 A fallback for SysVinit scripts is also provided. If $unit is not a native
 systemd unit and that a SysVinit match the $unit name (without the .service
 suffix), its name or path is returned.
 
 Units are resolved only once. However, it is possible to force new resolving by
 passing the $nocache flag.

 Param string $unit Unit name
 Param boolean withpath If TRUE, full unit path will be returned
 Param boolean $nocache OPTIONAL If true, no cache will be used
 Return string real unit file path or name, SysVinit file path or name, croak if the unit can't be resolved

=cut

sub resolveUnit
{
    my ( $self, $unit, $withpath, $nocache ) = @_;

    defined $unit or croak( 'Missing or undefined $unit parameter' );

    CORE::state %resolved;

    if ( $nocache ) {
        delete $resolved{$unit};
    } elsif ( exists $resolved{$unit} ) {
        $resolved{$unit} or croak( sprintf( "Couldn't resolve the %s unit", $unit ));
        return $resolved{$unit}->[$withpath ? 0 : 1];
    }

    ( $unit, undef, my $suffix ) = fileparse( $unit, qw/ .automount .device .mount .path .scope .service .slice .socket .swap .target .timer / );
    my $unitFQ .= length $suffix ? $unit : $unit . '.service';

    my $unitFilePath;
    for my $path ( @UNITFILEPATHS ) {
        $unitFilePath = File::Spec->join( $path, $unitFQ );
        # Either a regular file or character special file
        # (Masked units point to /dev/null)
        undef $unitFilePath unless -f $unitFilePath || -c _;
        last if defined $unitFilePath;
    }

    unless ( $unitFilePath ) {
        # For the SysVinit scripts, we want operate only on services
        if ( grep ( $suffix eq $_, '', '.service') ) {
            if ( $unitFilePath = eval { $self->resolveSysVinitScript( $unit, $nocache ) } ) {
                return $withpath ? $unitFilePath : $unit if $nocache;
                $resolved{$unit} = [ $unitFilePath, $unit ];
                goto &{ resolveUnit };
            }
        }

        $resolved{$unit} = undef unless $nocache;
        croak( sprintf( "Couldn't resolve the %s unit: %s", $unit, $@ ));
    }

    # Resolve the unit, unless it is not a symlink pointing to a regular file
    # (masked unit point to the /dev/null character special file)
    if ( -f _ && -l $unitFilePath ) {
        $unitFilePath = readlink( $unitFilePath ) or croak( sprintf( "Couldn't resolve the %s unit: %s", $unit, $! ));
    }

    return $withpath ? $unitFilePath : basename( $unitFilePath ) if $nocache;

    $resolved{$unit} = [ $unitFilePath, basename( $unitFilePath ) ];
    goto &{ resolveUnit };
}

=item daemonReload

 Reload the systemd manager configuration

 Return void, croak on failure

=cut

sub daemonReload
{
    my ( $self ) = @_;

    $self->_exec( [ $COMMANDS{'systemctl'}, 'daemon-reload' ] );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
