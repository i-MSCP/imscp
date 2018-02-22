=head1 NAME

 iMSCP::Service - High-level interface for service providers

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

package iMSCP::Service;

use strict;
use warnings;
use iMSCP::Debug qw/ debug getMessageByType /;
use iMSCP::EventManager;
use iMSCP::Execute;
use iMSCP::LsbRelease;
use iMSCP::ProgramFinder;
use Module::Load::Conditional qw/ check_install can_load /;
use parent qw/ Common::SingletonClass iMSCP::Provider::Service::Interface /;

$Module::Load::Conditional::FIND_VERSION = 0;
$Module::Load::Conditional::VERBOSE = 0;

=head1 DESCRIPTION

 High-level interface for service providers.

=head1 PUBLIC METHODS

=over 4

=item isEnabled( $service )

 Does the given service is enabled?

 Return TRUE if the given service is enabled, FALSE otherwise

=cut

sub isEnabled
{
    my ($self, $service) = @_;

    defined $service or die( 'parameter $service is not defined' );
    $self->{'provider'}->isEnabled( $service );
}

=item enable( $service )

 Enable the given service

 Param string $service Service name
 Return bool TRUE on success, die on failure

=cut

sub enable
{
    my ($self, $service) = @_;

    defined $service or die( 'parameter $service is not defined' );
    local $@;
    my $ret = eval {
        $self->{'eventManager'}->trigger( 'onBeforeEnableService', $service ) == 0
            && $self->{'provider'}->enable( $service )
            && $self->{'eventManager'}->trigger( 'onAfterEnableService', $service ) == 0;
    };
    $ret && !$@ or die( sprintf( "Couldn't enable the `%s' service: %s", $service, $@ || $self->_getLastError()));
    $ret;
}

=item disable( $service )

 Disable the given service

 Param string $service Service name
 Return bool TRUE on success, die on failure

=cut

sub disable
{
    my ($self, $service) = @_;

    defined $service or die( 'parameter $service is not defined' );
    local $@;
    my $ret = eval {
        $self->{'eventManager'}->trigger( 'onBeforeDisableService', $service ) == 0
            && $self->{'provider'}->disable( $service )
            && $self->{'eventManager'}->trigger( 'onAfterDisableService', $service ) == 0;
    };
    $ret && !$@ or die( sprintf( "Couldn't disable the `%s' service: %s", $service, $@ || $self->_getLastError()));
    $ret;
}

=item remove( $service )

 Remove the given service

 Param string $service Service name
 Return bool TRUE on success, die on failure

=cut

sub remove
{
    my ($self, $service) = @_;

    defined $service or die( 'parameter $service is not defined' );

    eval {
        $self->{'eventManager'}->trigger( 'onBeforeRemoveService', $service ) == 0 or die( $self->_getLastError());
        $self->{'provider'}->remove( $service ) or die( $self->_getLastError());

        unless ( $self->{'init'} eq 'sysvinit' ) {
            my $provider = $self->getProvider( ( $self->{'init'} eq 'upstart' ) ? 'systemd' : 'upstart' );

            if ( $self->{'init'} eq 'upstart' ) {
                for( qw / service socket / ) {
                    my $unitFilePath = eval { $provider->getUnitFilePath( "$service.$_" ); };
                    if ( defined $unitFilePath ) {
                        iMSCP::File->new( filename => $unitFilePath )->delFile() == 0 or die(
                            $self->_getLastError()
                        );
                    }
                }
            } else {
                for ( qw / conf override / ) {
                    my $jobfilePath = eval { $provider->getJobFilePath( $service, $_ ); };
                    if ( defined $jobfilePath ) {
                        iMSCP::File->new( filename => $jobfilePath )->delFile() == 0 or die( $self->_getLastError());
                    }
                }
            }
        }

        $self->{'eventManager'}->trigger( 'onAfterRemoveService', $service ) == 0 or die( $self->_getLastError());
    };
    !$@ or die( sprintf( "Couldn't remove the `%s' service: %s", $service, $@ ));
    1;
}

=item start( $service )

 Start the given service

 Param string $service Service name
 Return bool TRUE on success, die on failure

=cut

sub start
{
    my ($self, $service) = @_;

    defined $service or die( 'parameter $service is not defined' );

    my $ret = eval {
        $self->{'eventManager'}->trigger( 'onBeforeStartService', $service ) == 0
            && $self->{'provider'}->start( $service )
            && $self->{'eventManager'}->trigger( 'onAfterStartService', $service ) == 0;
    };
    $ret && !$@ or die( sprintf( "Couldn't start the `%s' service: %s", $service, $@ || $self->_getLastError()));
    $ret;
}

=item stop( $service )

 Stop the given service

 Param string $service Service name
 Return bool TRUE on success, die on failure

=cut

sub stop
{
    my ($self, $service) = @_;

    defined $service or die( 'parameter $service is not defined' );

    my $ret = eval {
        $self->{'eventManager'}->trigger( 'onBeforeStopService', $service ) == 0
            && $self->{'provider'}->stop( $service )
            && $self->{'eventManager'}->trigger( 'onAfterStopService', $service ) == 0
    };
    $ret && !$@ or die( sprintf( "Couldn't stop the `%s' service: %s", $service, $@ || $self->_getLastError()));
    $ret;
}

=item restart( $service )

 Restart the given service

 Param string $service Service name
 Return bool TRUE on success, die on failure

=cut

sub restart
{
    my ($self, $service) = @_;

    defined $service or die( 'parameter $service is not defined' );

    my $ret = eval {
        $self->{'eventManager'}->trigger( 'onBeforeRestartService', $service ) == 0
            && $self->{'provider'}->restart( $service )
            && $self->{'eventManager'}->trigger( 'onAfterRestartService', $service ) == 0;
    };
    $ret && !$@ or die( sprintf( "Couldn't restart the `%s' service: %s", $service, $@ || $self->_getLastError()));
    $ret;
}

=item reload( $service )

 Reload the given service

 Param string $service Service name
 Return bool TRUE on success, die on failure

=cut

sub reload
{
    my ($self, $service) = @_;

    defined $service or die( 'parameter $service is not defined' );

    my $ret = eval {
        $self->{'eventManager'}->trigger( 'onBeforeReloadService', $service ) == 0
            && $self->{'provider'}->reload( $service )
            && $self->{'eventManager'}->trigger( 'onAfterReloadService', $service ) == 0;
    };
    $ret && !$@ or die( sprintf( "Couldn't reload the `%s' service: %s", $service, $@ || $self->_getLastError()));
    $ret;
}

=item isRunning( $service )

 Is the given service running?

 Param string $service Service name
 Return bool TRUE if the given service is running, FALSE otherwise

=cut

sub isRunning
{
    my ($self, $service) = @_;

    defined $service or die( 'parameter $service is not defined' );
    eval { $self->{'provider'}->isRunning( $service ); };
}

=item hasService( $service )

 Does the given service exists?

 Return bool TRUE if the given service exits, FALSE otherwise

=cut

sub hasService
{
    my ($self, $service) = @_;

    defined $service or die( 'parameter $service is not defined' );
    $self->{'provider'}->hasService( $service );
}

=item isSysvinit( )

 Is sysvinit used as init system?

 Return bool TRUE if sysvinit is used as init system, FALSE otherwise

=cut

sub isSysvinit
{
    $_[0]->{'init'} eq 'sysvinit';
}

=item isUpstart( )

 Is upstart used as init system?

 Return bool TRUE if upstart is used as init system, FALSE otherwise

=cut

sub isUpstart
{
    $_[0]->{'init'} eq 'upstart';
}

=item isSystemd( )

 Is systemd used as init system?

 Return bool TRUE if systemd is used as init system, FALSE otherwise

=cut

sub isSystemd
{
    $_[0]->{'init'} eq 'systemd';
}

=item getProvider( [ $providerName = $self->{'init'} ] )

 Get service provider instance

 Param string $providerName OPTIONAL Provider name (sysvinit|upstart|systemd)
 Return iMSCP::Provider::Service::Sysvinit

=cut

sub getProvider
{
    my ($self, $providerName) = @_;

    $providerName = ucfirst( lc( $providerName // $self->{'init'} ));
    my $id = iMSCP::LsbRelease->getInstance->getId( 'short' );
    $id = 'Debian' if grep( lc $_ eq lc $id, 'Devuan', 'Ubuntu' );
    my $provider = "iMSCP::Provider::Service::${id}::${providerName}";
    unless ( check_install( module => $provider ) ) {
        $provider = "iMSCP::Provider::Service::${providerName}"; # Fallback to the base provider
    }
    can_load( modules => { $provider => undef } ) or die(
        sprintf( "Couldn't load the `%s' service provider: %s", $provider, $Module::Load::Conditional::ERROR )
    );
    $provider->getInstance();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return iMSCP::Service

=cut

sub _init
{
    my ($self) = @_;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'init'} = _detectInit();
    $self->{'provider'} = $self->getProvider( $self->{'init'} );
    $self;
}

=item _detectInit( )

 Detect init system

 Return string init system in use

=cut

sub _detectInit
{
    if ( -d '/run/systemd/system' ) {
        debug( 'Systemd init system has been detected' );
        return 'systemd';
    }

    if ( iMSCP::ProgramFinder::find( 'initctl' )
        && execute( 'initctl version 2>/dev/null | grep -q upstart' ) == 0
    ) {
        debug( 'Upstart init system has been detected' );
        return 'upstart';
    }

    debug( 'SysVinit init system has been detected' );
    'sysvinit'
}

=item _getLastError( )

 Get last error

 Return string

=cut

sub _getLastError
{
    getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error';
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
