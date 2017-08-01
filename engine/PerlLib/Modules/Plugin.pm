=head1 NAME

 Modules::Plugin - i-MSCP Plugin module

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

package Modules::Plugin;

use strict;
use warnings;
use autouse 'Hash::Merge' => qw/ merge /;
use iMSCP::Debug qw/ debug error getMessageByType /;
use iMSCP::Database;
use iMSCP::EventManager;
use iMSCP::Plugins;
use JSON;
use version;
use parent 'Common::Object';

=head1 DESCRIPTION

 This module provides the backend side of the i-MSCP plugin manager. It is
 responsible to execute one or many actions on a particular plugin according
 its state.
 
 The plugin is instantiated with the following parameters:
  action      : Plugin master action
  config      : Plugin current configuration
  config_prev : Plugin previous configuration
  eventManager: EventManager instance
  info        : Plugin info data

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
    my ($self, $pluginId) = @_;

    my $rs = $self->_loadData( $pluginId );
    return $rs if $rs;

    local $@;
    eval {
        $self->{'pluginData'}->{$_} = decode_json( $self->{'pluginData'}->{$_} ) for qw/ info config config_prev /;
    };
    if ( $@ ) {
        error( sprintf( "Couldn't decode plugin JSON object: %s", $@ ));
        return 1;
    }

    my $action;
    if ( $self->{'pluginData'}->{'plugin_status'} eq 'enabled' ) {
        $self->{'pluginAction'} = 'run';
        $action = '_run'
    } elsif ( $self->{'pluginData'}->{'plugin_status'} =~ /^to(install|change|update|uninstall|enable|disable)$/ ) {
        $self->{'pluginAction'} = $1;
        $action = '_' . $1;
    } else {
        error( sprintf( 'Unknown plugin status: %s', $self->{'pluginData'}->{'plugin_status'} ));
        return 1;
    }

    $rs = $self->$action();
    $rs ||= $self->{'eventManager'}->trigger(
        'onBeforeSetPluginStatus', $self->{'pluginData'}->{'plugin_name'}, \$self->{'pluginData'}->{'plugin_status'}
    );

    eval {
        my %plugin_next_state_map = (
            enabled     => 'enabled',
            toinstall   => 'enabled',
            toenable    => 'enabled',
            toupdate    => 'enabled',
            tochange    => 'enabled',
            todisable   => 'disabled',
            touninstall => ( $self->{'pluginData'}->{'info'}->{'__installable__'} ) ? 'uninstalled' : 'disabled'
        );

        local $self->{'dbh'}->{'RaiseError'} = 1;
        $self->{'dbh'}->do(
            "UPDATE plugin SET " . ( $rs ? 'plugin_error' : 'plugin_status' ) . " = ? WHERE plugin_id = ?",
            undef,
            ( $rs
                ? getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
                : $plugin_next_state_map{$self->{'pluginData'}->{'plugin_status'}}
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
    my ($self) = @_;

    $self->{'dbh'} = iMSCP::Database->factory()->getRawDb();
    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'pluginAction'} = undef;
    $self->{'pluginData'} = {};
    $self->{'pluginInstance'} = undef;
    $self;
}

=item _loadData( $pluginId )

 Load plugin data

 Param int Plugin unique identifier
 Return int 0 on success, 1 on failure

=cut

sub _loadData
{
    my ($self, $pluginId) = @_;

    local $@;
    my $pluginData = eval {
        local $self->{'dbh'}->{'RaiseError'} = 1;
        $self->{'dbh'}->selectrow_hashref(
            '
                SELECT plugin_id, plugin_name, plugin_info AS info, plugin_config AS config,
                    plugin_config_prev AS config_prev, plugin_status
                FROM plugin
                WHERE plugin_id = ?
             ',
            undef,
            $pluginId
        );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }
    unless ( $pluginData ) {
        error( sprintf( 'Data not found for plugin (ID %d)', $pluginId ));
        return 1
    }

    $self->{'pluginData'} = $pluginData;
    0;
}

=item _install( )

 Install the plugin

 Return int 0 on success, other on failure

=cut

sub _install
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'onBeforeInstallPlugin', $self->{'pluginData'}->{'plugin_name'} );
    $rs ||= $self->_executePluginAction( 'install' );
    $rs ||= $self->{'eventManager'}->trigger( 'onAfterInstallPlugin', $self->{'pluginData'}->{'plugin_name'} );
    $rs ||= $self->_enable();
}

=item _uninstall( )

 Uninstall the plugin

 Return int 0 on success, other on failure

=cut

sub _uninstall
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'onBeforeUninstallPlugin', $self->{'pluginData'}->{'plugin_name'} );
    $rs ||= $self->_executePluginAction( 'uninstall' );
    $rs ||= $self->{'eventManager'}->trigger( 'onAfterUninstallPlugin', $self->{'pluginData'}->{'plugin_name'} );
}

=item _enable( )

 Enable the plugin

 Return int 0 on success, other on failure

=cut

sub _enable
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'onBeforeEnablePlugin', $self->{'pluginData'}->{'plugin_name'} );
    $rs ||= $self->_executePluginAction( 'enable' );
    $rs ||= $self->{'eventManager'}->trigger( 'onAfterEnablePlugin', $self->{'pluginData'}->{'plugin_name'} );
}

=item _disable( )

 Disable the plugin

 Return int 0 on success, other on failure

=cut

sub _disable
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'onBeforeDisablePlugin', $self->{'pluginData'}->{'plugin_name'} );
    $rs ||= $self->_executePluginAction( 'disable' );
    $rs ||= $self->{'eventManager'}->trigger( 'onAfterDisablePlugin', $self->{'pluginData'}->{'plugin_name'} );
}

=item _change( )

 Change the plugin

 Return int 0 on success, other on failure

=cut

sub _change
{
    my ($self) = @_;

    my $rs = $self->_disable();
    $rs ||= $self->{'eventManager'}->trigger( 'onBeforeChangePlugin', $self->{'pluginData'}->{'plugin_name'} );
    $rs ||= $self->_executePluginAction( 'change' );
    $rs ||= $self->{'eventManager'}->trigger( 'onAfterChangePlugin', $self->{'pluginData'}->{'plugin_name'} );
    return $rs if $rs;

    if ( $self->{'pluginData'}->{'info'}->{'__need_change__'} ) {
        $self->{'pluginData'}->{'config_prev'} = $self->{'pluginData'}->{'config'};
        $self->{'pluginData'}->{'info'}->{'__need_change__'} = JSON::false;

        local $@;
        eval {
            local $self->{'dbh'}->{'RaiseError'} = 1;
            $self->{'dbh'}->do(
                'UPDATE plugin SET plugin_info = ?, plugin_config_prev = plugin_config WHERE plugin_id = ?',
                undef,
                encode_json( $self->{'pluginData'}->{'info'} ),
                $self->{'pluginData'}->{'plugin_id'}
            );
        };
        if ( $@ ) {
            error( $@ );
            return 1;
        }
    }

    $self->_enable();
}

=item _update( )

 Update the plugin

 Return int 0 on success, other on failure

=cut

sub _update
{
    my ($self) = @_;

    my $rs = $self->_disable();
    $rs ||= $self->{'eventManager'}->trigger( 'onBeforeUpdatePlugin', $self->{'pluginData'}->{'plugin_name'} );
    $rs ||= $self->_executePluginAction(
        'update', $self->{'pluginData'}->{'info'}->{'version'}, $self->{'pluginData'}->{'info'}->{'__nversion__'}
    );
    return $rs if $rs;

    $self->{'pluginData'}->{'info'}->{'version'} = $self->{'pluginData'}->{'info'}->{'__nversion__'};

    local $@;
    eval {
        local $self->{'dbh'}->{'RaiseError'} = 1;
        $self->{'dbh'}->do(
            'UPDATE plugin SET plugin_info = ? WHERE plugin_id = ?',
            undef,
            encode_json( $self->{'pluginData'}->{'info'} ),
            $self->{'pluginData'}->{'plugin_id'}
        );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $rs = $self->{'eventManager'}->trigger( 'onAfterUpdatePlugin', $self->{'pluginData'}->{'plugin_name'} );
    return $rs if $rs;

    if ( $self->{'pluginData'}->{'info'}->{'__need_change__'} ) {
        $rs = $self->{'eventManager'}->trigger( 'onBeforeChangePlugin', $self->{'pluginData'}->{'plugin_name'} );
        $rs ||= $self->_executePluginAction( 'change' );
        return $rs if $rs;

        $self->{'pluginData'}->{'config_prev'} = $self->{'pluginData'}->{'config'};
        $self->{'pluginData'}->{'info'}->{'__need_change__'} = JSON::false;

        eval {
            local $self->{'dbh'}->{'RaiseError'} = 1;
            $self->{'dbh'}->do(
                'UPDATE plugin SET plugin_info = ?, plugin_config_prev = plugin_config WHERE plugin_id = ?',
                undef,
                encode_json( $self->{'pluginData'}->{'info'} ),
                $self->{'pluginData'}->{'plugin_id'}
            );
        };
        if ( $@ ) {
            error( $@ );
            return 1
        }

        $rs = $self->{'eventManager'}->trigger( 'onAfterChangePlugin', $self->{'pluginData'}->{'plugin_name'} );
        return $rs if $rs;
    }

    $self->_enable();
}

=item _run( )

 Run plugin item tasks

 Return int 0 on success, other on failure

=cut

sub _run
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'onBeforeRunPlugin', $self->{'pluginData'}->{'plugin_name'} );
    $rs ||= $self->_executePluginAction( 'run' );
    $rs ||= $self->{'eventManager'}->trigger( 'onAfterRunPlugin', $self->{'pluginData'}->{'plugin_name'} );
}

=item _executePluginAction( $action [, $fromVersion = undef [, $toVersion = undef ] ] )

 Execute the given plugin action

 Param string $action Action to execute on the plugin
 Param string OPTIONAL $fromVersion Version from which the plugin is being updated
 Param string OPTIONAL $toVersion Version to which the plugin is being updated
 Return int 0 on success, other on failure

=cut

sub _executePluginAction
{
    my ($self, $action, $fromVersion, $toVersion) = @_;

    local $@;

    unless ( $self->{'pluginInstance'} ) {
        $self->{'pluginInstance'} = eval {
            # Turn any warning from plugin into exception
            local $SIG{'__WARN__'} = sub { die shift };
            my $pluginClass = iMSCP::Plugins->getInstance()->getClass( $self->{'pluginData'}->{'plugin_name'} );
            return undef unless $pluginClass->can( $action ); # Do not instantiate plugin when not necessary
            ( $pluginClass->can( 'getInstance' ) || $pluginClass->can( 'new' ) || die( 'Bad plugin class' ) )->(
                $pluginClass,
                action       => $self->{'pluginAction'},
                config       => $self->{'pluginData'}->{'config'},
                config_prev  => ( ( $self->{'pluginAction'} =~ /^(?:change|update)$/ )
                    # On plugin change/update, make sure that prev config also contains any new parameter
                    ? merge( $self->{'pluginData'}->{'config_prev'}, $self->{'pluginData'}->{'config'} )
                    : $self->{'pluginData'}->{'config_prev'} ),
                eventManager => $self->{'eventManager'},
                info         => $self->{'pluginData'}->{'info'}
            );
        };
        if ( $@ ) {
            error( sprintf( 'An unexpected error occurred: %s', $@ ));
            return 1;
        }

        return 0 unless $self->{'pluginInstance'};
    }

    my $subref = $self->{'pluginInstance'}->can( $action );
    return 0 unless $subref;

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
