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
use Hash::Merge qw/ merge /;
use iMSCP::Database;
use iMSCP::Debug qw/ debug getMessageByType /;
use iMSCP::EventManager;
use iMSCP::Plugins;
use JSON;
use LWP::Simple qw/ $ua get /;
use version;
use parent 'Common::Object';

=head1 DESCRIPTION

 This module provides the backend side of the i-MSCP plugin manager. It is
 responsible to execute one or many actions on a particular plugin according
 its current state.
 
 The plugin is instantiated with the following parameters:
  action      : Plugin master action
  config      : Plugin current configuration
  config_prev : Plugin previous configuration
  eventManager: EventManager instance
  info        : Plugin info

=head1 PUBLIC METHODS

=over 4

=item process( $pluginId )

 Load plugin data and execute action according its current state

 Param int Plugin unique identifier
 Return int 0 on success, die on failure

=cut

sub process
{
    my ($self, $pluginId) = @_;

    $self->{'pluginId'} = $pluginId;

    eval {
        $self->_loadData( $pluginId );

        my $method;
        if ( $self->{'pluginStatus'} eq 'enabled' ) {
            $self->{'pluginAction'} = 'run';
            $method = '_run'
        } elsif ( $self->{'pluginStatus'} =~ /^to(install|change|update|uninstall|enable|disable)$/ ) {
            $self->{'pluginAction'} = $1;
            $method = '_' . $self->{'pluginAction'};
        } else {
            die( sprintf( 'Unknown plugin status: %s', $self->{'pluginStatus'} ));
        }

        $self->$method();
        $self->{'eventManager'}->trigger(
            'onBeforeSetPluginStatus', $self->{'pluginName'}, \$self->{'pluginStatus'}
        ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
    };

    return 0 unless $@ || $self->{'pluginAction'} ne 'run';

    my %pluginNextStateMap = (
        toinstall   => 'enabled',
        toenable    => 'enabled',
        toupdate    => 'enabled',
        tochange    => 'enabled',
        todisable   => 'disabled',
        touninstall => 'uninstalled'
    );

    local $self->{'dbh'}->{'RaiseError'} = 1;
    $self->{'dbh'}->do(
        "UPDATE plugin SET " . ( $@ ? 'plugin_error' : 'plugin_status' ) . " = ? WHERE plugin_id = ?",
        undef, ( $@ ? $@ : $pluginNextStateMap{$self->{'pluginStatus'}} ), $self->{'pluginId'}
    );

    return 0 if defined $main::execmode && $main::execmode eq 'setup';

    my $cacheIds = 'iMSCP_Plugin_Manager_Metadata';
    $cacheIds .= ";$self->{'pluginInfo'}->{'require_cache_flush'}" if $self->{'pluginInfo'}->{'require_cache_flush'};
    my $httpScheme = $main::imscpConfig{'BASE_SERVER_VHOST_PREFIX'};
    my $url = "${httpScheme}127.0.0.1:" . ( $httpScheme eq 'http://'
        ? $main::imscpConfig{'BASE_SERVER_VHOST_HTTP_PORT'} : $main::imscpConfig{'BASE_SERVER_VHOST_HTTPS_PORT'}
    ) . "/fcache.php?ids=$cacheIds";
    get( $url ) or warn( "Couldn't trigger flush of frontEnd cache" );
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

    $ua->timeout( 5 );
    $ua->agent( 'i-MSCP/1.6 (+https://i-mscp.net/)' );
    $ua->ssl_opts(
        verify_hostname => 0,
        SSL_verify_mode => 0x00
    );
    $self->{'dbh'} = iMSCP::Database->factory()->getRawDb();
    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    @{$self}{qw/ pluginId pluginAction pluginInstance pluginName pluginInfo pluginConfig pluginConfigPrev pluginStatus /} = undef;
    $self;
}

=item _loadData( $pluginId )

 Load plugin data

 Param int Plugin unique identifier
 Return void on success, die on failure

=cut

sub _loadData
{
    my ($self, $pluginId) = @_;

    local $self->{'dbh'}->{'RaiseError'} = 1;
    my $row = $self->{'dbh'}->selectrow_hashref(
        'SELECT plugin_name, plugin_info, plugin_config, plugin_config_prev, plugin_status FROM plugin WHERE plugin_id = ?', undef, $pluginId
    );
    $row or die( sprintf( 'Data not found for plugin with ID %d', $pluginId ));
    $self->{'pluginName'} = $row->{'plugin_name'};
    $self->{'pluginInfo'} = decode_json( $row->{'plugin_info'} );
    $self->{'pluginConfig'} = decode_json( $row->{'plugin_config'} );
    $self->{'pluginConfigPrev'} = decode_json( $row->{'plugin_config_prev'} );
    $self->{'pluginStatus'} = $row->{'plugin_status'};
}

=item _install( )

 Install the plugin

 Return void on success, die on failure

=cut

sub _install
{
    my ($self) = @_;

    $self->{'eventManager'}->trigger( 'onBeforeInstallPlugin', $self->{'pluginName'} ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
    );
    $self->_executePluginAction( 'install' );
    $self->{'eventManager'}->trigger( 'onAfterInstallPlugin', $self->{'pluginName'} ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
    );
    $self->_enable();
}

=item _uninstall( )

 Uninstall the plugin

 Return void on success, die on failure

=cut

sub _uninstall
{
    my ($self) = @_;

    $self->{'eventManager'}->trigger( 'onBeforeUninstallPlugin', $self->{'pluginName'} ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
    );
    $self->_executePluginAction( 'uninstall' );
    $self->{'eventManager'}->trigger( 'onAfterUninstallPlugin', $self->{'pluginName'} ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
    );
}

=item _enable( )

 Enable the plugin

 Return void on success, die on failure

=cut

sub _enable
{
    my ($self) = @_;

    $self->{'eventManager'}->trigger( 'onBeforeEnablePlugin', $self->{'pluginName'} ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
    );
    $self->_executePluginAction( 'enable' );
    $self->{'eventManager'}->trigger( 'onAfterEnablePlugin', $self->{'pluginName'} ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
    );
}

=item _disable( )

 Disable the plugin

 Return void on success, die on failure

=cut

sub _disable
{
    my ($self) = @_;

    $self->{'eventManager'}->trigger( 'onBeforeDisablePlugin', $self->{'pluginName'} ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
    );
    $self->_executePluginAction( 'disable' );
    $self->{'eventManager'}->trigger( 'onAfterDisablePlugin', $self->{'pluginName'} ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
    );
}

=item _change( )

 Change the plugin

 Return void on success, die on failure

=cut

sub _change
{
    my ($self) = @_;

    $self->_disable();
    $self->{'eventManager'}->trigger( 'onBeforeChangePlugin', $self->{'pluginName'} ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
    );
    $self->_executePluginAction( 'change' );
    $self->{'eventManager'}->trigger( 'onAfterChangePlugin', $self->{'pluginName'} ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
    );

    if ( $self->{'pluginInfo'}->{'__need_change__'} ) {
        $self->{'pluginConfigPrev'} = $self->{'pluginConfig'};
        $self->{'pluginInfo'}->{'__need_change__'} = JSON::false;
        local $self->{'dbh'}->{'RaiseError'} = 1;
        $self->{'dbh'}->do(
            'UPDATE plugin SET plugin_info = ?, plugin_config_prev = plugin_config WHERE plugin_id = ?',
            undef, encode_json( $self->{'pluginInfo'} ), $self->{'pluginId'}
        );
    }

    $self->_enable();
}

=item _update( )

 Update the plugin

 Return void on success, die on failure

=cut

sub _update
{
    my ($self) = @_;

    $self->_disable();
    $self->{'eventManager'}->trigger( 'onBeforeUpdatePlugin', $self->{'pluginName'} ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
    );
    $self->_executePluginAction( 'update' );
    $self->{'pluginInfo'}->{'version'} = $self->{'pluginInfo'}->{'__nversion__'};
    {
        local $self->{'dbh'}->{'RaiseError'} = 1;
        $self->{'dbh'}->do(
            'UPDATE plugin SET plugin_info = ? WHERE plugin_id = ?', undef, encode_json( $self->{'pluginInfo'} ), $self->{'pluginId'}
        );
    }
    $self->{'eventManager'}->trigger( 'onAfterUpdatePlugin', $self->{'pluginName'} ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
    );

    if ( $self->{'pluginInfo'}->{'__need_change__'} ) {
        $self->{'eventManager'}->trigger( 'onBeforeChangePlugin', $self->{'pluginName'} ) == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
        );
        $self->_executePluginAction( 'change' );
        $self->{'pluginConfigPrev'} = $self->{'pluginConfig'};
        $self->{'pluginInfo'}->{'__need_change__'} = JSON::false;
        {
            local $self->{'dbh'}->{'RaiseError'} = 1;
            $self->{'dbh'}->do(
                'UPDATE plugin SET plugin_info = ?, plugin_config_prev = plugin_config WHERE plugin_id = ?',
                undef, encode_json( $self->{'pluginInfo'} ), $self->{'pluginId'}
            );
        }
        $self->{'eventManager'}->trigger( 'onAfterChangePlugin', $self->{'pluginName'} ) == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
        );
    }

    $self->_enable();
}

=item _run( )

 Run plugin item tasks

 Return void on success, die on failure

=cut

sub _run
{
    my ($self) = @_;

    $self->{'eventManager'}->trigger( 'onBeforeRunPlugin', $self->{'pluginName'} ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
    );
    $self->_executePluginAction( 'run' );
    $self->{'eventManager'}->trigger( 'onAfterRunPlugin', $self->{'pluginName'} ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
    );
}

=item _executePluginAction( $action )

 Execute the given plugin action

 Param string $action Action to execute on the plugin
 Return void on success, die on failure

=cut

sub _executePluginAction
{
    my ($self, $action) = @_;

    unless ( $self->{'pluginInstance'} ) {
        local $SIG{'__WARN__'} = sub { die shift }; # Turn any warning from plugin into exception
        my $pluginClass = iMSCP::Plugins->getInstance()->getClass( $self->{'pluginName'} );
        return undef unless $pluginClass->can( $action ); # Do not instantiate plugin when not necessary

        $self->{'pluginInstance'} = (
            $pluginClass->can( 'getInstance' ) || $pluginClass->can( 'new' ) || die( 'Bad plugin class' )
        )->(
            $pluginClass,
            action       => $self->{'pluginAction'},
            config       => $self->{'pluginConfig'},
            config_prev  => ( $self->{'pluginAction'} =~ /^(?:change|update)$/
                # On plugin change/update, make sure that prev config also contains any new parameter
                ? merge( $self->{'pluginConfigPrev'}, $self->{'pluginConfig'} ) : $self->{'pluginConfigPrev'} ),
            eventManager => $self->{'eventManager'},
            info         => $self->{'pluginInfo'}
        );
    }

    my $subref = $self->{'pluginInstance'}->can( $action ) or return;
    debug( sprintf( "Executing %s( ) action on %s", $action, ref $self->{'pluginInstance'} ));

    local $@;
    eval {
        $subref->( $self->{'pluginInstance'}, ( $action eq 'update'
                ? ( $self->{'pluginInfo'}->{'version'}, $self->{'pluginInfo'}->{'__nversion__'} ) : () )
        ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
    };

    # Return value from the run() action is ignored by default. However a
    # plugin can force return value by setting the FORCE_RETVAL attribute to a
    # TRUE value
    die if $@ && ( $action ne 'run' || $self->{'pluginInstance'}->{'FORCE_RETVAL'} )
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
