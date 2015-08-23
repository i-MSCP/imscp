=head1 NAME

 Modules::Plugin - i-MSCP Plugin module

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2013-2015 by Laurent Declercq <l.declercq@nuxwin.com>
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
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use iMSCP::Debug;
use iMSCP::Database;
use iMSCP::EventManager;
use Carp;
use JSON;
use version;
use parent 'Common::Object';

# Map current status to new status
my %STATUS_TO_NEW_STATUS = (
	'enabled' => 'enabled',
	'toinstall' => 'enabled',
	'toenable' => 'enabled',
	'toupdate' => 'enabled',
	'tochange' => 'enabled',
	'todisable' => 'disabled',
	'touninstall' => 'uninstalled'
);

=head1 DESCRIPTION

 i-MSCP Plugin module.

=head1 PUBLIC METHODS

=over 4

=item process($pluginId)

 Process action according plugin status

 Param int Plugin unique identifier
 Return int 0 on success, 1 on failure

=cut

sub process
{
	my ($self, $pluginId) = @_;

	my $rs = $self->_loadData($pluginId);
	return $rs if $rs;

	my $pluginName = $self->{'plugin_name'};
	my $pluginStatus = $self->{'plugin_status'};

	local $@;
	eval {
		$self->{$_} = decode_json($self->{$_}) for qw/info config config_prev/;

		if($pluginStatus eq 'enabled') {
			$self->{'action'} = 'run';
		} elsif($pluginStatus eq 'toinstall') {
			$self->{'action'} = 'install';
		} elsif($pluginStatus eq 'tochange') {
			$self->{'action'} = 'change';
		} elsif($pluginStatus eq 'toupdate') {
			$self->{'action'} = 'update';
		} elsif($pluginStatus eq 'touninstall') {
			$self->{'action'} = 'uninstall';
		} elsif($pluginStatus eq 'toenable') {
			$self->{'action'} = 'enable';
		} elsif($pluginStatus eq 'todisable') {
			$self->{'action'} = 'disable';
		} else {
			croak(sprintf('Unknown status %s', $pluginName, $pluginStatus));
		}

		my $method = '_' . $self->{'action'};
		$self->$method($pluginName);
		$self->{'eventManager'}->trigger('onBeforeSetPluginStatus', $pluginName, \$pluginStatus);
	};

	if($@) {
		error(sprintf('Could not process the %s plugin: %s', $pluginName, $@));
		$rs = 1;
	}

	my @sql = (
		'UPDATE plugin SET ' . ($rs ? 'plugin_error' : 'plugin_status') . ' = ? WHERE plugin_id = ?',
		$rs ? getMessageByType('error') : $STATUS_TO_NEW_STATUS{$pluginStatus}, $pluginId
	);
	my $qrs = $self->{'db'}->doQuery('u', @sql);
	unless(ref $qrs eq 'HASH') {
		error($qrs);
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
	$self->{'db'} = iMSCP::Database->factory();
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

	my $row = $self->{'db'}->doQuery(
		'plugin_id',
		'
			SELECT
		 		plugin_id, plugin_name, plugin_info AS info, plugin_config AS config, plugin_config_prev AS config_prev,
		 		plugin_status
		 	FROM
		 		plugin
		 	WHERE
		 		plugin_id = ?
		 ',
		$pluginId
	);
	unless(ref $row eq 'HASH') {
		error($row);
		return 1;
	} elsif(! exists $row->{$pluginId}) {
		error(sprintf('Data for plugin with ID %s were not found in database', $pluginId));
		return 1;
	}

	%{$self} = (%{$self}, %{$row->{$pluginId}});

	0;
}

=item _install($pluginName)

 Install the given plugin

 Param string Plugin name
 Croak on failure

=cut

sub _install
{
	my ($self, $pluginName) = @_;

	$self->{'eventManager'}->trigger('onBeforeInstallPlugin', $pluginName);
	$self->_call($pluginName, 'install');
	$self->{'eventManager'}->trigger('onAfterInstallPlugin', $pluginName);
	$self->_enable($pluginName);
}

=item _uninstall($pluginName)

 Uninstall the given plugin

 Param string Plugin name
 Croak on failure

=cut

sub _uninstall
{
	my ($self, $pluginName) = @_;

	$self->{'eventManager'}->trigger('onBeforeUninstallPlugin', $pluginName);
	$self->_call($pluginName, 'uninstall');
	$self->{'eventManager'}->trigger('onAfterUninstallPlugin', $pluginName);
}

=item _enable($pluginName)

 Enable the given plugin

 Param string Plugin name
 Croak on failure

=cut

sub _enable
{
	my ($self, $pluginName) = @_;

	$self->{'eventManager'}->trigger('onBeforeEnablePlugin', $pluginName);
	$self->_call($pluginName, 'enable');
	$self->{'eventManager'}->trigger('onAfterEnablePlugin', $pluginName);
}

=item _disable($pluginName)

 Disable the given plugin

 Param string Plugin name
 Croak on failure

=cut

sub _disable
{
	my ($self, $pluginName) = @_;

	$self->{'eventManager'}->trigger('onBeforeDisablePlugin', $pluginName);
	$self->_call($pluginName, 'disable');
	$self->{'eventManager'}->trigger('onAfterDisablePlugin', $pluginName);
}

=item _change($pluginName)

 Change the given plugin

 Param string Plugin name
 Croak on failure

=cut

sub _change
{
	my ($self, $pluginName) = @_;

	$self->_disable($pluginName);
	$self->{'eventManager'}->trigger('onBeforeChangePlugin', $pluginName);
	$self->_call($pluginName, 'change');
	$self->{'eventManager'}->trigger('onAfterChangePlugin', $pluginName);

	if($self->{'info'}->{'__need_change__'}) {
		$self->{'info'}->{'__need_change__'} = JSON::false;
		my $qrs = $self->{'db'}->doQuery(
			'u', 'UPDATE plugin SET plugin_info = ?, plugin_config_prev = ? WHERE plugin_name = ?',
			encode_json($self->{'info'}), encode_json($self->{'config'}), $pluginName
		);
		ref $qrs eq 'HASH' or die($qrs);
	}

	$self->_enable($pluginName);
}

=item _update($pluginName)

 Update the given plugin

 Param string Plugin name
 Croak on failure

=cut

sub _update
{
	my ($self, $pluginName) = @_;

	$self->_disable($pluginName);
	$self->{'eventManager'}->trigger('onBeforeUpdatePlugin', $pluginName);
	$self->_call($pluginName, 'update', $self->{'info'}->{'version'}, $self->{'info'}->{'__nversion__'});
	$self->{'info'}->{'version'} = $self->{'info'}->{'__nversion__'};

	my $qrs = $self->{'db'}->doQuery(
		'u', 'UPDATE plugin SET plugin_info = ? WHERE plugin_name = ?', encode_json($self->{'info'}), $pluginName
	);
	ref $qrs eq 'HASH' or croak($qrs);

	$self->{'eventManager'}->trigger('onAfterUpdatePlugin', $pluginName);

	if($self->{'info'}->{'__need_change__'}) {
		$self->{'eventManager'}->trigger('onBeforeChangePlugin', $pluginName);
		$self->_call($pluginName, 'change');
		$self->{'info'}->{'__need_change__'} = JSON::false;
		$qrs = $self->{'db'}->doQuery(
			'u', 'UPDATE plugin SET plugin_info = ?, plugin_config_prev = ? WHERE plugin_name = ?',
			encode_json($self->{'info'}), encode_json($self->{'config'}), $pluginName
		);
		ref $qrs eq 'HASH' or croak($qrs);
		$self->{'eventManager'}->trigger('onAfterChangePlugin', $pluginName);
	}

	$self->_enable($pluginName);
}

=item _run($pluginName)

 Run the given plugin

 Param string Plugin name
 Croak on failure

=cut

sub _run
{
	my ($self, $pluginName) = @_;

	$self->{'eventManager'}->trigger('onBeforeRunPlugin', $pluginName);
	$self->_call($pluginName, 'run');
	$self->{'eventManager'}->trigger('onAfterRunPlugin', $pluginName);
}

=item _call($name, $method [, $fromVersion = undef [, $toVersion = undef ]])

 Execute the given plugin method

 Param string $name Plugin name
 Param string $method Name of the method to call on the plugin
 Param string OPTIONAL $fromVersion Version from which the plugin is being updated
 Param string OPTIONAL $toVersion Version to which the plugin is being updated
 Croak on failure

=cut

sub _call
{
	my ($self, $name, $method, $fromVersion, $toVersion) = @_;

	local $@;
	eval {
		my $backendPluginFile = "$main::imscpConfig{'GUI_ROOT_DIR'}/plugins/$name/backend/$name.pm";
		my $plugin = "Plugin::$name";

		require $backendPluginFile;

		# Turn any warning from plugin into exception
		local $SIG{__WARN__} = sub { croak shift };

		if($plugin->can($method)) {
			$plugin = $plugin->getInstance(
				'eventManager' => $self->{'eventManager'}, 'action' => $self->{'action'}, 'info' => $self->{'info'},
				'config' => $self->{'config'}, 'config_prev' => $self->{'config_prev'}
			);

			debug(sprintf('Executing %s::%s() action', ref $plugin, $method));
			my $rs = $plugin->$method($fromVersion, $toVersion);

			# Return value from the run() action is ignored by default because it's the responsability of the plugins to set
			# error status for their items. In case a plugin doesn't manage any item, it can force return value by defining
			# the FORCE_RETVAL attribute with a TRUE value
			if($method ne 'run' || $plugin->{'FORCE_RETVAL'}) {
				!$rs or croak(getMessageByType('error', { amount => 1, remove => 1 }) || 'Unknown error');
			}
		}
	};

	!$@ or croak($@);
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
