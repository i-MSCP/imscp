=head1 NAME

 Modules::Plugin - i-MSCP Plugin module

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2013-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Modules::Plugin;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Database;
use iMSCP::EventManager;
use JSON;
use version;
use parent 'Common::Object';

# Map current status to new status
my %STATUS_TO_NEW_STATUS = (
    enabled     => 'enabled',
    toinstall   => 'enabled',
    toenable    => 'enabled',
    toupdate    => 'enabled',
    tochange    => 'enabled',
    todisable   => 'disabled',
    touninstall => 'uninstalled'
);

=head1 DESCRIPTION

 This module provide the backend side of the i-MSCP plugin manager.

=head1 PUBLIC METHODS

=over 4

=item process($pluginId)

 Process action according plugin status

 Param int Plugin unique identifier
 Return int 0 on success, other on failure

=cut

sub process
{
    my ($self, $pluginId) = @_;

    my $rs = $self->_loadData( $pluginId );
    return $rs if $rs;

    local $@;
    eval { $self->{$_} = decode_json( $self->{$_} ) for qw/ info config config_prev /; };
    unless ($@) {
        $self->{'action'} = 'run';
        my $method = '_run';
        if ($self->{'plugin_status'} =~ /^to(install|change|update|uninstall|enable|disable)$/) {
            $self->{'action'} = $1;
            $method = '_'.$1;
        }
        $rs = $self->$method();
    } else {
        error( sprintf( 'Could not decode plugin JSON property: %s', $@ ) );
        $rs = 1;
    }

    $self->{'eventManager'}->trigger( 'onBeforeSetPluginStatus', $self->{'plugin_name'}, \$self->{'plugin_status'} );
    my @sql = (
        "UPDATE plugin SET ".($rs ? 'plugin_error' : 'plugin_status')." = ? WHERE plugin_id = ?", undef,
        ($rs ? scalar getMessageByType( 'error' ) || 'Unknown error' : $STATUS_TO_NEW_STATUS{$self->{'plugin_status'}}),
        $pluginId
    );
    my $qrs = $self->{'dbh'}->do( @sql );
    unless (defined $qrs) {
        error( $self->{'dbh'}->errstr );
        $rs ||= 1;
    }

    $rs;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Modules::Plugin

=cut

sub _init
{
    my $self = shift;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'dbh'} = iMSCP::Database->factory()->getRawDb();
    $self;
}

=item _loadData($pluginId)

 Load plugin data

 Param int Plugin unique identifier
 Return int 0 on success, 1 on failure

=cut

sub _loadData
{
    my ($self, $pluginId) = @_;

    my $row = $self->{'dbh'}->selectrow_hashref(
        '
            SELECT plugin_id, plugin_name, plugin_info AS info, plugin_config AS config,
                plugin_config_prev AS config_prev, plugin_status
             FROM plugin WHERE plugin_id = ?
         ',
        undef, $pluginId
    );
    if ($self->{'dbh'}->errstr) {
        error( $self->{'dbh'}->errstr );
        return 1;
    } elsif (!%{$row}) {
        error( sprintf( 'Data for plugin with ID %s were not found in database', $pluginId ) );
        return 1
    }

    %{$self} = (%{$self}, %{$row});
    0;
}

=item _install()

 Install the plugin

 Return int 0 on success, other on failure

=cut

sub _install
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'onBeforeInstallPlugin', $self->{'plugin_name'} );
    $rs ||= $self->_call( 'install' );
    $rs ||= $self->{'eventManager'}->trigger( 'onAfterInstallPlugin', $self->{'plugin_name'} );
    $rs ||= $self->_enable();
}

=item _uninstall()

 Uninstall the plugin

 Return int 0 on success, other on failure

=cut

sub _uninstall
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'onBeforeUninstallPlugin', $self->{'plugin_name'} );
    $rs ||= $self->_call( 'uninstall' );
    $rs ||= $self->{'eventManager'}->trigger( 'onAfterUninstallPlugin', $self->{'plugin_name'} );
}

=item _enable()

 Enable the plugin

 Return int 0 on success, other on failure

=cut

sub _enable
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'onBeforeEnablePlugin', $self->{'plugin_name'} );
    $rs ||= $self->_call( 'enable' );
    $rs ||= $self->{'eventManager'}->trigger( 'onAfterEnablePlugin', $self->{'plugin_name'} );
}

=item _disable()

 Disable the plugin

 Return int 0 on success, other on failure

=cut

sub _disable
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'onBeforeDisablePlugin', $self->{'plugin_name'} );
    $rs ||= $self->_call( 'disable' );
    $rs ||= $self->{'eventManager'}->trigger( 'onAfterDisablePlugin', $self->{'plugin_name'} );
}

=item _change()

 Change the plugin

 Return int 0 on success, other on failure

=cut

sub _change
{
    my $self = shift;

    my $rs = $self->_disable();
    $rs ||= $self->{'eventManager'}->trigger( 'onBeforeChangePlugin', $self->{'plugin_name'} );
    $rs ||= $self->_call( 'change' );
    $rs ||= $self->{'eventManager'}->trigger( 'onAfterChangePlugin', $self->{'plugin_name'} );
    return $rs if $rs;

    if ($self->{'info'}->{'__need_change__'}) {
        $self->{'config_prev'} = $self->{'config'};
        $self->{'info'}->{'__need_change__'} = JSON::false;
        my $qrs = $self->{'dbh'}->do(
            'UPDATE plugin SET plugin_info = ?, plugin_config_prev = plugin_config WHERE plugin_id = ?', undef,
            encode_json( $self->{'info'} ), $self->{'plugin_id'}
        );
        unless (defined $qrs) {
            error( $self->{'dbh'}->errstr );
            return 1;
        }
    }

    $self->_enable();
}

=item _update()

 Update the plugin

 Return int 0 on success, other on failure

=cut

sub _update
{
    my $self = shift;

    my $rs = $self->_disable();
    $rs ||= $self->{'eventManager'}->trigger( 'onBeforeUpdatePlugin', $self->{'plugin_name'} );
    $rs ||= $self->_call( 'update', $self->{'info'}->{'version'}, $self->{'info'}->{'__nversion__'} );
    return $rs if $rs;

    $self->{'info'}->{'version'} = $self->{'info'}->{'__nversion__'};
    my $qrs = $self->{'dbh'}->do(
        'UPDATE plugin SET plugin_info = ? WHERE plugin_id = ?', undef, encode_json( $self->{'info'} ),
        $self->{'plugin_id'}
    );
    unless (defined $qrs) {
        error( $self->{'dbh'}->errstr );
        return 1;
    }

    $rs = $self->{'eventManager'}->trigger( 'onAfterUpdatePlugin', $self->{'plugin_name'} );
    return $rs if $rs;

    if ($self->{'info'}->{'__need_change__'}) {
        $rs = $self->{'eventManager'}->trigger( 'onBeforeChangePlugin', $self->{'plugin_name'} );
        $rs ||= $self->_call( 'change' );
        return $rs if $rs;

        $self->{'config_prev'} = $self->{'config'};
        $self->{'info'}->{'__need_change__'} = JSON::false;
        $qrs = $self->{'dbh'}->do(
            'UPDATE plugin SET plugin_info = ?, plugin_config_prev = plugin_config WHERE plugin_id = ?', undef,
            encode_json( $self->{'info'} ), $self->{'plugin_id'}
        );
        unless (defined $qrs) {
            error( $self->{'dbh'}->errstr );
            return 1
        }

        $rs = $self->{'eventManager'}->trigger( 'onAfterChangePlugin', $self->{'plugin_name'} );
        return $rs if $rs;
    }

    $self->_enable();
}

=item _run()

 Run plugin item tasks

 Return int 0 on success, other on failure

=cut

sub _run
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'onBeforeRunPlugin', $self->{'plugin_name'} );
    $rs ||= $self->_call( 'run' );
    $rs ||= $self->{'eventManager'}->trigger( 'onAfterRunPlugin', $self->{'plugin_name'} );
}

=item _call($method [, $fromVersion = undef [, $toVersion = undef ]])

 Call the given plugin method

 Param string $method Name of the method to call on the plugin
 Param string OPTIONAL $fromVersion Version from which the plugin is being updated
 Param string OPTIONAL $toVersion Version to which the plugin is being updated
 Return int 0 on success, other on failure

=cut

sub _call
{
    my ($self, $method, $fromVersion, $toVersion) = @_;

    my $pluginName = $self->{'plugin_name'};
    my $backendPluginFile = "$main::imscpConfig{'GUI_ROOT_DIR'}/plugins/$pluginName/backend/$pluginName.pm";

    local $@;
    eval { require $backendPluginFile; };
    if ($@) {
        error( $@ );
        return 1;
    }

    my $plugin = "Plugin::$pluginName";
    eval {
        # Turn any warning from plugin into exception
        local $SIG{'__WARN__'} = sub { die shift };

        if ($plugin->can( $method )) {
            my $construct = $plugin->can( 'getInstance' ) ? 'getInstance' : 'new';
            $plugin = $plugin->$construct(
                eventManager => $self->{'eventManager'},
                action       => $self->{'action'},
                info         => $self->{'info'},
                config       => $self->{'config'},
                config_prev  => $self->{'config_prev'}
            );
        } else {
            $plugin = undef;
        }
    };
    if ($@) {
        error( sprintf( 'An unexpected error occurred: %s', $@ ) );
        return 1;
    }

    if ($plugin) {
        debug( sprintf( "Calling %s() method on %s", $method, ref $plugin ) );
        my $rs = $plugin->$method( $fromVersion, $toVersion );
        # Return value from the run() action is ignored by default because it's the responsability of the plugins to set
        # error status for their items. In case a plugin doesn't manage any item, it can force return value by defining
        # the FORCE_RETVAL attribute and set it value to 'yes'
        if ($method ne 'run' || (defined $plugin->{'FORCE_RETVAL'} && $plugin->{'FORCE_RETVAL'} eq 'yes')) {
            return $rs;
        }
    }

    0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
