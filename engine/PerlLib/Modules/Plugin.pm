=head1 NAME

 Modules::Plugin - i-MSCP Plugin module

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
use iMSCP::Debug qw/ debug error getMessageByType /;
use iMSCP::Database;
use iMSCP::EventManager;
use iMSCP::Plugins;
use JSON;
use version;
use parent 'Common::Object';

=head1 DESCRIPTION

 This module provides the backend side of the i-MSCP plugin manager. It is
 responsible to execute actions on a particular plugin according its state.
 
 The plugin is instantiated with the following parameters:
  action      : Plugin action
  config      : Plugin current configuration
  config_prev : Plugin previous configuration
  eventManager: EventManager instance
  info        : Plugin info

=head1 PUBLIC METHODS

=over 4

=item process( $pluginId )

 Load plugin data and execute action according its state

 Note: Plugin errors, outside those raised by this module are no longer
 returned to the caller. Only the plugin status is updated with the error
 message (since v1.4.4).

 Param int Plugin unique identifier
 Return int 0 on success, other on failure

=cut

sub process
{
    my ( $self, $pluginId ) = @_;

    my $rs = $self->_loadData( $pluginId );
    return $rs if $rs;

    # Determine plugin action according current plugin state
    my $action;
    if ( $self->{'data'}->{'plugin_status'} eq 'enabled' ) {
        $self->{'pluginAction'} = 'run';
        $action = '_run'
    } elsif ( $self->{'data'}->{'plugin_status'} =~ /^to(install|change|update|uninstall|enable|disable)$/ ) {
        $self->{'pluginAction'} = $1;
        $action = '_' . $1;

        if ( grep ( $_ eq $action, 'update', 'change' ) ) {
            # Determine whether or not there are plugin config changes
            $self->{'pluginHasConfigChanges'} = $self->{'data'}->{'plugin_config'} ne $self->{'data'}->{'plugin_config_prev'};
        }
    } else {
        error( sprintf( 'Unknown plugin status: %s', $self->{'data'}->{'plugin_status'} ));
        return 1;
    }

    # Decode plugin JSON data
    for my $field ( qw/ plugin_info plugin_config plugin_config_prev / ) {
        eval { $self->{'data'}->{$field} = decode_json( $self->{'data'}->{$field} ); };
        if ( $@ ) {
            error( sprintf( "Couldn't decode '%s' plugin '%s' JSON data: %s", $self->{'data'}->{'plugin_name'}, $field =~ s/^plugin_//r, $@ ));
            return 1;
        }
    }

    $rs = $self->$action();
    $rs ||= $self->{'eventManager'}->trigger( 'onBeforeSetPluginStatus', $self->{'data'}->{'plugin_name'}, \$self->{'data'}->{'plugin_status'} );

    eval {
        local $self->{'dbh'}->{'RaiseError'} = TRUE;
        $self->{'dbh'}->do(
            "UPDATE plugin SET @{ [ $rs ? 'plugin_error' : 'plugin_status' ] } = ? WHERE plugin_id = ?",
            undef,
            ( $rs
                ? getMessageByType( 'error', { amount => 1, remove => TRUE } ) || 'Unknown error'
                : ( $self->{'data'}->{'plugin_status'} eq 'todisable'
                        ? 'disabled'
                        : ( $self->{'data'}->{'plugin_status'} eq 'touninstall'
                            ? ( $self->{'data'}->{'plugin_info'}->{'__installable__'} ? 'uninstalled' : 'disabled' )
                            : 'enabled'
                        )
                )
            ),
            $pluginId
        );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
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
    $self->{'dbh'} = iMSCP::Database->factory()->getRawDb();
    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'pluginAction'} = undef;
    $self->{'pluginInstance'} = undef;
    $self->{'pluginHasConfigChanges'} = FALSE;
    $self;
}

=item _loadData( $pluginId )

 Load plugin data

 Param int Plugin unique identifier
 Return int 0 on success, 1 on failure

=cut

sub _loadData
{
    my ( $self, $pluginId ) = @_;

    eval {
        local $self->{'dbh'}->{'RaiseError'} = TRUE;
        ( $self->{'data'} = $self->{'dbh'}->selectrow_hashref(
            'SELECT plugin_id, plugin_name, plugin_info, plugin_config, plugin_config_prev, plugin_status FROM plugin WHERE plugin_id = ?',
            undef,
            $pluginId
        ) ) or die( sprintf( 'Data not found for plugin with ID: %d', $pluginId ));
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item _install( )

 Install the plugin

 Return int 0 on success, other on failure

=cut

sub _install
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'onBeforeInstallPlugin', $self->{'data'}->{'plugin_name'} );
    $rs ||= $self->execAction( 'install' );
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
    $rs ||= $self->execAction( 'uninstall' );
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
    $rs ||= $self->execAction( 'enable' );
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
    $rs ||= $self->execAction( 'disable' );
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
    $rs ||= $self->execAction( 'change' );
    $rs ||= $self->{'eventManager'}->trigger( 'onAfterChangePlugin', $self->{'data'}->{'plugin_name'} );
    $rs ||= $self->_enable() unless $isSubAction;
    return $rs if $rs;

    if ( $self->{'pluginHasConfigChanges'} ) {
        eval {
            $self->{'data'}->{'plugin_config_prev'} = $self->{'data'}->{'plugin_config'};
            local $self->{'dbh'}->{'RaiseError'} = TRUE;
            $self->{'dbh'}->do( 'UPDATE plugin SET plugin_config_prev = plugin_config WHERE plugin_id = ?', undef, $self->{'data'}->{'plugin_id'} );
        };
        if ( $@ ) {
            error( $@ );
            return 1;
        }
    }

    0;
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
    $rs ||= $self->execAction(
        'update',
        $self->{'data'}->{'plugin_info'}->{'version'} . '.' . $self->{'data'}->{'plugin_info'}->{'build'},
        $self->{'data'}->{'plugin_info'}->{'__nversion__'} . '.' . $self->{'data'}->{'plugin_info'}->{'__nbuild__'}
    );
    return $rs if $rs;

    eval {
        $self->{'data'}->{'plugin_info'}->{'version'} = $self->{'data'}->{'plugin_info'}->{'__nversion__'};
        $self->{'data'}->{'plugin_info'}->{'build'} = $self->{'data'}->{'plugin_info'}->{'__nbuild__'};

        local $self->{'dbh'}->{'RaiseError'} = TRUE;
        $self->{'dbh'}->do(
            'UPDATE plugin SET plugin_info = ? WHERE plugin_id = ?',
            undef,
            encode_json( $self->{'data'}->{'plugin_info'} ),
            $self->{'data'}->{'plugin_id'}
        );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $rs = $self->{'eventManager'}->trigger( 'onAfterUpdatePlugin', $self->{'data'}->{'plugin_name'} );
    $rs ||= $self->_change( TRUE ) if $self->{'pluginHasConfigChanges'};
    $rs ||= $self->_enable();
}

=item _run( )

 Run plugin item tasks

 Return int 0 on success, other on failure

=cut

sub _run
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'onBeforeRunPlugin', $self->{'data'}->{'plugin_name'} );
    $rs ||= $self->execAction( 'run' );
    $rs ||= $self->{'eventManager'}->trigger( 'onAfterRunPlugin', $self->{'data'}->{'plugin_name'} );
}

=item execAction( $action [, $fromVersion = undef [, $toVersion = undef ] ] )

 Execute the given plugin action

 Param string $action Action to execute on the plugin
 Param string $fromVersion Version from which the plugin is being updated
 Param string $toVersion Version to which the plugin is being updated
 Return int 0 on success, other on failure

=cut

sub execAction
{
    my ( $self, $action, $fromVersion, $toVersion ) = @_;

    unless ( $self->{'pluginInstance'} ) {
        $self->{'pluginInstance'} = eval {
            # Turn any warning from plugin into exception
            local $SIG{'__WARN__'} = sub { die shift };
            my $pluginClass = iMSCP::Plugins->getInstance()->getClass( $self->{'data'}->{'plugin_name'} );
            return undef unless $pluginClass->can( $action ); # Do not instantiate plugin when not necessary
            ( $pluginClass->can( 'getInstance' ) || $pluginClass->can( 'new' ) || die( 'Bad plugin class' ) )->(
                $pluginClass,
                action       => $self->{'pluginAction'},
                config       => $self->{'data'}->{'plugin_config'},
                config_prev  => $self->{'data'}->{'plugin_config_prev'},
                eventManager => $self->{'eventManager'},
                info         => $self->{'data'}->{'plugin_info'}
            );
        };
        if ( $@ ) {
            error( sprintf( 'An unexpected error occurred: %s', $@ ));
            return 1;
        }

        return 0 unless $self->{'pluginInstance'};
    }

    return 0 unless my $subref = $self->{'pluginInstance'}->can( $action );

    debug( sprintf( "Executing %s( ) action on %s", $action, ref $self->{'pluginInstance'} ));
    my $rs = eval { $subref->( $self->{'pluginInstance'}, $fromVersion, $toVersion ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    # Return value from the run() action is ignored by default because it's the responsability of the plugins to set
    # error status for their items. However a plugin can force return value by setting the FORCE_RETVAL attribute to
    # a TRUE
    ( $action ne 'run' || $self->{'pluginInstance'}->{'FORCE_RETVAL'} ) ? $rs : 0
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
