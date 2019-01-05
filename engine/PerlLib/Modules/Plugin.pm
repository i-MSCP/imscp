=head1 NAME

 Modules::Plugin - i-MSCP Plugin module

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

package Modules::Plugin;

use strict;
use warnings;
use iMSCP::Boolean;
use iMSCP::Database;
use iMSCP::Debug qw/ debug error getMessageByType /;
use iMSCP::EventManager;
use iMSCP::Plugins;
use JSON;
use Try::Tiny;
use version;
use parent 'Common::Object';

=head1 DESCRIPTION

 This module provides the backend side of the i-MSCP plugin manager. It is
 responsible to execute actions on a particular plugin according its state.

=head1 PUBLIC METHODS

=over 4

=item process( \%data )

 Load plugin data and execute action according its state

 Note: Plugin errors, outside those raised by this module are no longer
 returned to the caller. Only the plugin status is updated with the error
 message (since v1.4.4).

 Param hashref \%data Plugin data
 Return int 0 on success, other on failure

=cut

sub process
{
    my ( $self, $data ) = @_;

    try {
        $self->_loadData( $data->{'id'} );

        # Determine plugin action according current plugin state
        if ( $self->{'data'}->{'plugin_status'} eq 'enabled' ) {
            $self->{'action'} = 'run';
        } elsif ( ( $self->{'action'} ) = $self->{'data'}->{'plugin_status'} =~ /^to(install|change|update|uninstall|enable|disable)$/ ) {
            if ( grep ( $_ eq $self->{'action'}, 'update', 'change' ) ) {
                # Determine whether or not there are plugin config changes
                $self->{'hasConfigChanges'} = $self->{'data'}->{'plugin_config'} ne $self->{'data'}->{'plugin_config_prev'};
            }
        } else {
            die( sprintf( 'Unknown plugin status: %s', $self->{'data'}->{'plugin_status'} ));
        }

        # Decode plugin JSON data
        for my $field ( qw/ plugin_info plugin_config plugin_config_prev / ) {
            $self->{'data'}->{$field} = decode_json( $self->{'data'}->{$field} );
        }

        my $rs = $self->can( '_' . $self->{'action'} )->( $self );
        $rs ||= $self->{'eventManager'}->trigger( 'onBeforeSetPluginStatus', $self->{'data'}->{'plugin_name'}, \$self->{'data'}->{'plugin_status'} );

        $self->{'_conn'}->run( fixup = sub {
            $_->do(
                "UPDATE plugin SET @{ [ $rs ? 'plugin_error' : 'plugin_status' ] } = ? WHERE plugin_id = ?",
                undef,
                ( $rs
                    ? getMessageByType( 'error', { amount => 1, remove => TRUE } ) || 'Unknown error'
                    : ( $self->{'data'}->{'plugin_status'} eq 'todisable'
                    ? 'disabled' : ( $self->{'data'}->{'plugin_status'} eq 'touninstall'
                    ? ( $self->{'data'}->{'plugin_info'}->{'__installable__'} ? 'uninstalled' : 'disabled' ) : 'enabled'
                ) ) ),
                $data->{'id'}
            );
        } );
        0;
    } catch {
        error( $_ );
        1;
    };
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Modules::Plugin

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'data'} = {};
    $self->{'_conn'} = iMSCP::Database->factory()->getConnector();
    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'action'} = undef;
    $self->{'plugin'} = undef;
    $self->{'hasConfigChanges'} = FALSE;
    $self;
}

=item _loadData( $pluginId )

 Load plugin data

 Param int Plugin unique identifier
 Return void, die on failure

=cut

sub _loadData
{
    my ( $self, $pluginId ) = @_;

    $self->{'data'} = $self->{'_conn'}->run( fixup => sub {
        $_->selectrow_hashref(
            'SELECT plugin_id, plugin_name, plugin_info, plugin_config, plugin_config_prev, plugin_status FROM plugin WHERE plugin_id = ?',
            undef, $pluginId
        );
    } );
    $self->{'data'} or die( sprintf( 'Data not found for plugin with ID: %d', $pluginId ));
}

=item _install( )

 Install the plugin

 Return int 0 on success, other on failure

=cut

sub _install
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'onBeforeInstallPlugin', $self->{'data'}->{'plugin_name'} );
    $rs ||= $self->_execAction( 'install' );
    $rs ||= $self->{'eventManager'}->trigger( 'onAfterInstallPlugin', $self->{'data'}->{'plugin_name'} );
    $rs ||= $self->_enable();
}

=item _uninstall( )

 Uninstall the plugin

 Return int 0 on success, other on failure

=cut

sub _uninstall
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'onBeforeUninstallPlugin', $self->{'data'}->{'plugin_name'} );
    $rs ||= $self->_execAction( 'uninstall' );
    $rs ||= $self->{'eventManager'}->trigger( 'onAfterUninstallPlugin', $self->{'data'}->{'plugin_name'} );
}

=item _enable( )

 Enable the plugin

 Return int 0 on success, other on failure

=cut

sub _enable
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'onBeforeEnablePlugin', $self->{'data'}->{'plugin_name'} );
    $rs ||= $self->_execAction( 'enable' );
    $rs ||= $self->{'eventManager'}->trigger( 'onAfterEnablePlugin', $self->{'data'}->{'plugin_name'} );
}

=item _disable( )

 Disable the plugin

 Return int 0 on success, other on failure

=cut

sub _disable
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'onBeforeDisablePlugin', $self->{'data'}->{'plugin_name'} );
    $rs ||= $self->_execAction( 'disable' );
    $rs ||= $self->{'eventManager'}->trigger( 'onAfterDisablePlugin', $self->{'data'}->{'plugin_name'} );
}

=item _change( [ $isSubAction = FALSE ] )

 Change the plugin

 Param bool $isSubAction
 Return int 0 on success, other on failure

=cut

sub _change
{
    my ( $self, $isSubAction ) = @_;
    $isSubAction //= FALSE;

    my $rs = 0;
    $rs = $self->_disable() unless $isSubAction;
    $rs ||= $self->{'eventManager'}->trigger( 'onBeforeChangePlugin', $self->{'data'}->{'plugin_name'} );
    $rs ||= $self->_execAction( 'change' );
    $rs ||= $self->{'eventManager'}->trigger( 'onAfterChangePlugin', $self->{'data'}->{'plugin_name'} );
    $rs ||= $self->_enable() unless $isSubAction;
    return $rs if $rs;

    if ( $self->{'hasConfigChanges'} ) {
        try {
            $self->{'data'}->{'plugin_config_prev'} = $self->{'data'}->{'plugin_config'};
            $self->{'_conn'}->run( fixup => sub {
                $_->do( 'UPDATE plugin SET plugin_config_prev = plugin_config WHERE plugin_id = ?', undef, $self->{'data'}->{'plugin_id'} );
            } );
        } catch {
            error( $_ );
            $rs = 1;
        };
    }

    $rs;
}

=item _update( )

 Update the plugin

 Return int 0 on success, other on failure

=cut

sub _update
{
    my ( $self ) = @_;

    my $rs = $self->_disable();
    $rs ||= $self->{'eventManager'}->trigger( 'onBeforeUpdatePlugin', $self->{'data'}->{'plugin_name'} );
    $rs ||= $self->_execAction(
        'update',
        $self->{'data'}->{'plugin_info'}->{'version'} . '.' . $self->{'data'}->{'plugin_info'}->{'build'},
        $self->{'data'}->{'plugin_info'}->{'__nversion__'} . '.' . $self->{'data'}->{'plugin_info'}->{'__nbuild__'}
    );
    return $rs if $rs;

    try {
        @{ $self->{'data'}->{'plugin_info'} }{qw/ version build /} = (
            $self->{'data'}->{'plugin_info'}->{'__nversion__'}, $self->{'data'}->{'plugin_info'}->{'__nbuild__'}
        );
        $self->{'_conn'}->run( fixup => sub {
            $_->do(
                'UPDATE plugin SET plugin_info = ? WHERE plugin_id = ?', undef, encode_json( $self->{'data'}->{'plugin_info'} ),
                $self->{'data'}->{'plugin_id'}
            );
        } )
    } catch {
        error( $_ );
        $rs = 1;
    };

    $rs ||= $self->{'eventManager'}->trigger( 'onAfterUpdatePlugin', $self->{'data'}->{'plugin_name'} );
    $rs ||= $self->_change( TRUE ) if $self->{'hasConfigChanges'};
    $rs ||= $self->_enable();
}

=item _run( )

 Execute plugin run() action

 Return int 0 on success, other on failure

=cut

sub _run
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'onBeforeRunPlugin', $self->{'data'}->{'plugin_name'} );
    $rs ||= $self->_execAction( 'run' );
    $rs ||= $self->{'eventManager'}->trigger( 'onAfterRunPlugin', $self->{'data'}->{'plugin_name'} );
}

=item _execAction( $action [, $fromVersion = undef [, $toVersion = undef ] ] )

 Execute the given plugin action
 
 The plugin is instanciated with the following parameters:
  action      : Plugin action (install,uninstall,enable,disable,update,change)
  config      : Plugin current configuration
  config_prev : Plugin previous configuration
  eventManager: EventManager instance
  info        : Plugin info

 Param string $action Action to execute on the plugin
 Param string $fromVersion Version from which the plugin is being updated (update action only, undefined otherwise)
 Param string $toVersion Version to which the plugin is being updated (update action only, undefined otherwise)
 Return int 0 on success, other on failure

=cut

sub _execAction
{
    my ( $self, $action, $fromVersion, $toVersion ) = @_;

    unless ( defined $self->{'plugin'} ) {
        return 1 if try {
            # Turn any warning from plugin into exception
            local $SIG{'__WARN__'} = sub { die shift };
            my $pluginClass = iMSCP::Plugins->getInstance()->getClass( $self->{'data'}->{'plugin_name'} );
            return 0 unless $pluginClass->can( $action ); # Do not instantiate plugin when not necessary
            $self->{'plugin'} = ( $pluginClass->can( 'getInstance' ) || $pluginClass->can( 'new' ) || die( 'Bad plugin class' ) )->(
                $pluginClass,
                action       => $self->{'action'},
                config       => $self->{'data'}->{'plugin_config'},
                config_prev  => $self->{'data'}->{'plugin_config_prev'},
                eventManager => $self->{'eventManager'},
                info         => $self->{'data'}->{'plugin_info'}
            );
            0;
        } catch {
            error( sprintf( 'An unexpected error occurred: %s', $@ ));
            1;
        };

        return 0 unless defined $self->{'plugin'};
    }

    return 0 unless my $subref = $self->{'plugin'}->can( $action );

    debug( sprintf( "Executing %s( ) action on %s plugin", $action, ref $self->{'plugin'} ));

    my $ret = try {
        $subref->( $self->{'plugin'}, $fromVersion, $toVersion );
    } catch {
        error( $@ );
        1;
    };

    # Return value from the run() action is ignored by default because it's the
    # responsibility of the plugins to set  error status for their items.
    # However a plugin can force return value by setting the FORCE_RETVAL
    #  attribute to TRUE
    ( $action ne 'run' || $self->{'plugin'}->{'FORCE_RETVAL'} ) ? $ret : 0
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
