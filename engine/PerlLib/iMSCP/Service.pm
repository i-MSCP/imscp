=head1 NAME

 iMSCP::Service - High-level interface for service providers

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
use iMSCP::Debug qw/ debug error getMessageByType /;
use iMSCP::Execute;
use iMSCP::ProgramFinder;
use Module::Load::Conditional qw/ can_load /;
use parent qw/ iMSCP::Common::SingletonClass iMSCP::Providers::Service::Interface /;

$Module::Load::Conditional::FIND_VERSION = 0;
$Module::Load::Conditional::VERBOSE = 0;
$Module::Load::Conditional::FORCE_SAFE_INC = 1;

my %DELAYED_ACTIONS;

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
    my $ret = eval { $self->{'provider'}->enable( $service ) };
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
    my $ret = eval { $self->{'provider'}->disable( $service ) };
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
        $self->{'provider'}->remove( $service ) or die( $self->_getLastError());

        unless ( $self->{'init'} eq 'Sysvinit' ) {
            my $provider = $self->getProvider( $self->{'init'} eq 'Upstart' ? 'Systemd' : 'Upstart' );

            if ( $self->{'init'} eq 'Upstart' ) {
                my $basename = basename( $service, qw / .service .socket .target .timer / );

                for ( qw / service socket target timer / ) {
                    # FIXME protect distribution package units files. Those should not be removed by us
                    my $unitFilePath = eval { $provider->getUnitFilePath( "$basename.$_" ); };
                    if ( defined $unitFilePath ) {
                        iMSCP::File->new( filename => $unitFilePath )->delFile() == 0 or die( $self->_getLastError());
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

    my $ret = eval { $self->{'provider'}->start( $service ) };
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

    my $ret = eval { $self->{'provider'}->stop( $service ) };
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

    my $ret = eval { $self->{'provider'}->restart( $service ); };
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

    my $ret = eval { $self->{'provider'}->reload( $service ); };
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

=item getInitSystem()

 Get init system

 Return string Init system name (lowercase)

=cut

sub getInitSystem()
{
    $_[0]->{'init'};
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

 Param string $providerName OPTIONAL Provider name (Sysvinit|Upstart|Systemd)
 Return iMSCP::Providers::Service::Sysvinit

=cut

sub getProvider
{
    my ($self, $providerName) = @_;

    my $provider = 'iMSCP::Providers::Service::'
        . "@{[ $main::imscpConfig{'DISTRO_FAMILY'} && $main::imscpConfig{'DISTRO_FAMILY'} ? $main::imscpConfig{'DISTRO_FAMILY'}.'::': '' ]}"
        . "@{[$providerName // $self->{'init'}]}";

    unless ( can_load( modules => { $provider => undef } ) ) {
        # Fallback to the base provider
        $provider = "iMSCP::Providers::Service::${providerName}";
        can_load( modules => { $provider => undef } ) or die(
            sprintf( "Couldn't load the `%s' service provider: %s", $provider, $Module::Load::Conditional::ERROR )
        );
    }

    $provider->getInstance();
}

=item registerDelayedAction( $service, $action [, $priority = 0] )

 Register a service action that will be executed in __END__ block.
 
 Only the 'start', 'restart' and 'reload' actions are supported, in following order of precedence:
 
 - restart
 - reload
 - start
 
 Param string $service Service name for which action must be executed
 Param coderef|array $action Action name or an array containing action name and coderef representing action logic
 Param int $priority Priority. Default (0) stands for 'no priority'.
 Return void

=cut

sub registerDelayedAction
{
    my (undef, $service, $action, $priority) = @_;
    $priority //= 0;

    defined $service or die ( 'Missing $service parameter' );
    defined $action or die( 'Missing $action parameter.' );
    $priority =~ /^\d+$/ or die( 'Invalid $priority parameter.' );

    if ( ref $action eq 'ARRAY' ) {
        @{$action} == 2 or die( 'When defined as array, $action must contains both the action name and coderef for action logic.' );
        grep($action->[0], 'restart', 'reload', 'start') or die( 'Unexpected action name. Only start, restart and reload actions can be delayed' );
        ref $action->[1] eq 'CODE' or die( 'Unexpected action coderef.' );
    } else {
        grep($action eq $_, 'restart', 'reload', 'start') or die( 'Unexpected action. Only start, restart and reload actions can be delayed' );
    }

    unless ( $DELAYED_ACTIONS{$service} ) {
        $DELAYED_ACTIONS{$service} = {
            action   => $action,
            priority => $priority
        };

        return;
    }

    # Identical action (coderef), return early
    return if ref $DELAYED_ACTIONS{$service}->{'action'} eq 'ARRAY' && ref $action eq 'ARRAY' && $DELAYED_ACTIONS{$service}->{'action'} eq $action;

    my $oaction = ref $DELAYED_ACTIONS{$service}->{'action'} eq 'ARRAY'
        ? $DELAYED_ACTIONS{$service}->{'action'}->[0] : $DELAYED_ACTIONS{$service}->{'action'};
    my $naction = ref $action eq 'ARRAY' ? $action->[0] : $action;

    # reload action can be replaced by reload or restart action only
    # restart action can be replaced by restart action only
    return if ( $oaction eq 'reload' && !grep($action eq $_, 'restart', 'reload') ) || ( $oaction eq 'restart' && $naction ne 'restart' );

    $DELAYED_ACTIONS{$service} = {
        action   => $action,
        priority => $priority
    };
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return iMSCP::Service, die on failure

=cut

sub _init
{
    my ($self) = @_;

    exists $main::imscpConfig{'DISTRO_FAMILY'} or die( sprintf( 'You must first bootstrap the i-MSCP backend' ));
    $self->{'provider'} = $self->getProvider( $self->{'init'} = _detectInit());
    $self;
}

=item _detectInit( )

 Detect init system

 Return string init system in use

=cut

sub _detectInit
{
    return $main::imscpConfig{'SYSTEM_INIT'} if exists $main::imscpConfig{'SYSTEM_INIT'} && $main::imscpConfig{'SYSTEM_INIT'} ne '';

    if ( -d '/run/systemd/system' ) {
        debug( 'Systemd init system has been detected' );
        return 'Systemd';
    }

    if ( iMSCP::ProgramFinder::find( 'initctl' ) && execute( 'initctl version 2>/dev/null | grep -q upstart' ) == 0 ) {
        debug( 'Upstart init system has been detected' );
        return 'Upstart';
    }

    debug( 'SysVinit init system has been detected' );
    'Sysvinit'
}

=item _getLastError( )

 Get last error

 Return string

=cut

sub _getLastError
{
    getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error';
}

=item _executeDelayedActions( )

 Execute delayed actions

 Return int 0 on success, 1 on failure

=cut

sub _executeDelayedActions
{
    my ($self) = @_;

    return 0 unless %DELAYED_ACTIONS;

    # Sort services by priority (DESC)
    my @services = sort { $DELAYED_ACTIONS{$b}->{'priority'} <=> $DELAYED_ACTIONS{$a}->{'priority'} } keys %DELAYED_ACTIONS;

    for my $service( @services ) {
        my $action = $DELAYED_ACTIONS{$service}->{'action'};

        if ( ref $action eq 'ARRAY' ) {
            eval { $action->[1]->(); };
            if ( $@ ) {
                error( $@ );
                return 1;
            }

            next;
        }

        my $ret = eval { $self->$action( $service ); };
        if ( $@ || $ret ) {
            error( $@ || $self->_getLastError());
            return $ret || 1;
        }
    }
}

=back

=head1 SHUTDOWN TASKS

=over 4

=item END

 Execute delayed actions

=cut

END {
    return unless exists $main::imscpConfig{'DISTRO_FAMILY'};

    __PACKAGE__->getInstance()->_executeDelayedActions();
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
