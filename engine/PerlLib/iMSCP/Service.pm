=head1 NAME

 iMSCP::Service - High-level interface for init providers

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

package iMSCP::Service;

use strict;
use warnings;
use Carp 'croak';
use File::Basename;
use iMSCP::Boolean;
use iMSCP::Debug qw/ debug getMessageByType /;
use iMSCP::Dir;
use iMSCP::Execute 'execute';
use iMSCP::LsbRelease;
use iMSCP::ProgramFinder;
use Module::Load::Conditional 'can_load';
use parent qw/ Common::SingletonClass iMSCP::Provider::Service::Interface /;

$Module::Load::Conditional::FIND_VERSION = FALSE;
$Module::Load::Conditional::VERBOSE = FALSE;
$Module::Load::Conditional::FORCE_SAFE_INC = TRUE;

=head1 DESCRIPTION

 High-level interface for init providers.

=head1 PUBLIC METHODS

=over 4

=item isEnabled( $service )

 See iMSCP::Provider::Service::Interface::isEnabled()

=cut

sub isEnabled
{
    my ( $self, $service ) = @_;

    $self->{'provider'}->isEnabled( $service );
}

=item enable( $service )

 See iMSCP::Provider::Service::Interface::enable()

=cut

sub enable
{
    my ( $self, $service ) = @_;

    defined $service or croak( 'Missing or undefined $service parameter' );

    eval { $self->{'provider'}->enable( $service ); };
    !$@ or croak( sprintf( "Couldn't enable the %s service: %s", $service, $@ ));
}

=item disable( $service )

 See iMSCP::Provider::Service::Interface::disable()

=cut

sub disable
{
    my ( $self, $service ) = @_;

    defined $service or croak( 'Missing or undefined $service parameter' );

    eval { $self->{'provider'}->disable( $service ); };
    !$@ or croak( sprintf( "Couldn't disable the %s service: %s", $service, $@ ));
}

=item remove( $service )

 See iMSCP::Provider::Service::Interface::remove()
 
 Because we want to remove service files, independently of the current
 init system, this method reimplement some parts of the systemd and
 Upstart init providers. Calling the remove() method on these providers
 when they are not the current init system would lead to a failure.

=cut

sub remove
{
    my ( $self, $service ) = @_;

    defined $service or croak( 'Missing or undefined $service parameter' );

    eval {
        $self->{'provider'}->remove( $service );

        unless ( $self->{'init'} eq 'Systemd' ) {
            my $provider = $self->getProvider( 'Systemd' );

            # Remove drop-in files if any
            for my $dir ( '/etc/systemd/system/', '/usr/local/lib/systemd/system/' ) {
                my $dropInDir = $dir;
                ( undef, undef, my $suffix ) = fileparse(
                    $service, qw/ .automount .device .mount .path .scope .service .slice .socket .swap .timer /
                );
                $dropInDir .= $service . ( $suffix ? '' : '.service' ) . '.d';
                next unless -d $dropInDir;
                debug( sprintf( "Removing the %s systemd drop-in directory", $dropInDir ));
                iMSCP::Dir->new( dirname => $dropInDir )->remove();
            }

            # Remove systemd unit files if any
            while ( my $unitFilePath = eval { $provider->resolveUnit( $service, TRUE, TRUE ) } ) {
                # We do not want remove units that are shipped by distribution packages
                last unless index( $unitFilePath, '/etc/systemd/system/' ) == 0 || index( $unitFilePath, '/usr/local/lib/systemd/system/' ) == 0;
                debug( sprintf( 'Removing the %s unit', $unitFilePath ));
                iMSCP::File->new( filename => $unitFilePath )->remove();
            }
        }

        unless ( $self->{'init'} eq 'Upstart' ) {
            my $provider = $self->getProvider( 'Upstart' );
            for my $type ( qw/ conf override / ) {
                if ( my $jobFilePath = eval { $provider->resolveJob( $service, $type, TRUE ); } ) {
                    debug( sprintf( "Removing the %s upstart file", $jobFilePath ));
                    iMSCP::File->new( filename => $jobFilePath )->remove();
                }
            }
        }
    };
    !$@ or croak( sprintf( "Couldn't remove the %s service: %s", basename( $service, '.service' ), $@ ));
}

=item start( $service )

 See iMSCP::Provider::Service::Interface::start()

=cut

sub start
{
    my ( $self, $service ) = @_;

    defined $service or croak( 'Missing or undefined $service parameter' );

    eval { $self->{'provider'}->start( $service ); };
    !$@ or croak( sprintf( "Couldn't start the %s service: %s", $service, $@ ));
}

=item stop( $service )

 See iMSCP::Provider::Service::Interface::stop()

=cut

sub stop
{
    my ( $self, $service ) = @_;

    defined $service or croak( 'Missing or undefined $service parameter' );

    eval { $self->{'provider'}->stop( $service ); };
    !$@ or croak( sprintf( "Couldn't stop the %s service: %s", $service, $@ ));
}

=item restart( $service )

 See iMSCP::Provider::Service::Interface::restart()

=cut

sub restart
{
    my ( $self, $service ) = @_;

    defined $service or croak( 'Missing or undefined $service parameter' );

    eval { $self->{'provider'}->restart( $service ); };
    !$@ or croak( sprintf( "Couldn't restart the %s service: %s", $service, $@ ));
}

=item reload( $service )

 See iMSCP::Provider::Service::Interface::reload()

=cut

sub reload
{
    my ( $self, $service ) = @_;

    defined $service or croak( 'Missing or undefined $service parameter' );

    eval { $self->{'provider'}->reload( $service ); };
    !$@ or croak( sprintf( "Couldn't reload the %s service: %s", $service, $@ ));
}

=item isRunning( $service )

 See iMSCP::Provider::Service::Interface::isRunning()

=cut

sub isRunning
{
    my ( $self, $service ) = @_;

    defined $service or croak( 'Missing or undefined $service parameter' );

    $self->{'provider'}->isRunning( $service );
}

=item hasService( $service )

 See iMSCP::Provider::Service::Interface::hasService()

=cut

sub hasService
{
    my ( $self, $service ) = @_;

    defined $service or croak( 'Missing or undefined $service parameter' );

    $self->{'provider'}->hasService( $service );
}

=item getInitSystem()

 Get init system in use, that is, the program running with PID 1

 Return string Init system name

=cut

sub getInitSystem( )
{
    $_[0]->{'init'};
}

=item isSysVinit( )

 Is SysVinit used as init system, that is, the program running with PID 1?

 Return boolean TRUE if SysVinit is the current init system, FALSE otherwise

=cut

sub isSysVinit
{
    $_[0]->{'init'} eq 'SysVinit';
}

=item isUpstart( )

 Is upstart used as init system, that is, the program running with PID 1?

 Return boolean TRUE if Upstart is is the current init system, FALSE otherwise

=cut

sub isUpstart
{
    $_[0]->{'init'} eq 'Upstart';
}

=item isSystemd( )

 Is systemd used as init system, that is, the program running with PID 1?

 Return boolean TRUE if systemd is the current init system, FALSE otherwise

=cut

sub isSystemd
{
    $_[0]->{'init'} eq 'Systemd';
}

=item getProvider( [ $providerName = $self->{'init'} ] )

 Get service provider instance

 Param string $providerName OPTIONAL Provider name (Systemd|SysVinit|Upstart)
 Return iMSCP::Provider::Service::Interface, croak on failure

=cut

sub getProvider
{
    my ( $self, $providerName ) = @_;

    $providerName //= $self->{'init'};

    my $id = iMSCP::LsbRelease->getInstance->getId( 'short' );
    $id = 'Debian' if grep ( lc $id eq $_, 'devuan', 'ubuntu' );
    my $provider = "iMSCP::Provider::Service::${id}::${providerName}";

    unless ( can_load( modules => { $provider => undef } ) ) {
        # Fallback to the base provider
        $provider = "iMSCP::Provider::Service::${providerName}";
        can_load( modules => { $provider => undef } ) or croak(
            sprintf( "Couldn't load the '%s' service provider: %s", $provider, $Module::Load::Conditional::ERROR )
        );
    }

    $provider->getInstance();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return iMSCP::Service, croak on failure

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'init'} = _detectInit();
    $self->{'provider'} = $self->getProvider();
    $self;
}

=item _detectInit( )

 Detect init system

 Detection of initialization system on various distributions is kind of a black
 art as there are too many factors implied. Here, we assume one of the Systemd,
 Upstart or SysVinit initialization system. We don't provide init provider for
 other initialization systems yet (eg, OpenRC, Nosh...). The current detection
 heuristic is left as simple as it can and as such, is far from perfect. While
 it works pretty well for Debian based distributions, it could fail on other
 distributions.

 Return string init system in use

=cut

sub _detectInit
{
    return 'Systemd' if -d '/run/systemd/system';
    return 'Upstart' if iMSCP::ProgramFinder::find( 'initctl' ) && execute( 'initctl version 2>/dev/null | grep -q upstart' ) == 0;
    'SysVinit';
}

=item _getLastError( )

 Get last error

 Return string

=cut

sub _getLastError
{
    getMessageByType( 'error', { amount => 1, remove => TRUE } ) || 'Unknown error';
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
