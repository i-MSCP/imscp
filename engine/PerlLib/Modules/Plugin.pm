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
	'enabled' => 'enabled',
	'toinstall' => 'enabled',
	'toenable' => 'enabled',
	'toupdate' => 'enabled',
	'tochange' => 'enabled',
	'todisable' => 'disabled',
	'touninstall' => 'uninstalled'
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

	my $rs = $self->_loadData($pluginId);
	return $rs if $rs;

	my $status = $self->{'plugin_status'};
	my $pluginName = $self->{'plugin_name'};

	eval { $self->{$_} = decode_json($self->{$_}) for qw/info config config_prev/; };
	unless($@) {
		if($status eq 'enabled') {
			$self->{'action'} = 'run';
			$rs = $self->_run($pluginName);
		} elsif($status eq 'toinstall') {
			$self->{'action'} = 'install';
			$rs = $self->_install($pluginName);
		} elsif($status eq 'tochange') {
			$self->{'action'} = 'change';
			$rs = $self->_change($pluginName);
		} elsif($status eq 'toupdate') {
			$self->{'action'} = 'update';
			$rs = $self->_update($pluginName);
		} elsif($status eq 'touninstall') {
			$self->{'action'} = 'uninstall';
			$rs = $self->_uninstall($pluginName);
		} elsif($status eq 'toenable') {
			$self->{'action'} = 'enable';
			$rs = $self->_enable($pluginName);
		} elsif($status eq 'todisable') {
			$self->{'action'} = 'disable';
			$rs = $self->_disable($pluginName);
		} else {
			error("$pluginName plugin status is corrupted.");
			return 1;
		}
	} else {
		error(sprintf('ould not decode plugin JSON property: %s', $@));
		$rs = 1;
	}

	$self->{'eventManager'}->trigger('onBeforeSetPluginStatus', $pluginName, \$status);
	my @sql = (
		"UPDATE plugin SET " . ($rs ? 'plugin_error' : 'plugin_status') . " = ? WHERE plugin_id = ?",
		$rs ? (scalar getMessageByType('error') || 'Unknown error') : $STATUS_TO_NEW_STATUS{$status},
		$pluginId
	);
	my $qrs = $self->{'db'}->doQuery('dummy', @sql);
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
			SELECT plugin_id, plugin_name, plugin_info AS info, plugin_config AS config, plugin_config_prev AS config_prev,
		 		plugin_status
		 	FROM plugin
		 	WHERE plugin_id = ?
		 ',
		$pluginId
	);
	unless(ref $row eq 'HASH') {
		error($row);
		return 1;
	} elsif(! exists $row->{$pluginId}) {
		error(sprintf('Data for plugin with ID %s were not found in database', $pluginId));
		return 1
	}

	%{$self} = (%{$self}, %{$row->{$pluginId}});
	0;
}

=item _install($pluginName)

 Install the given plugin

 Param string Plugin name
 Return int 0 on success, other on failure

=cut

sub _install
{
	my ($self, $pluginName) = @_;

	my $rs = $self->{'eventManager'}->trigger('onBeforeInstallPlugin', $pluginName);
	$rs ||= $self->_call($pluginName, 'install');
	$rs ||= $self->{'eventManager'}->trigger('onAfterInstallPlugin', $pluginName);
	$rs ||= $self->_enable($pluginName);
}

=item _uninstall($pluginName)

 Uninstall the given plugin

 Param string Plugin name
 Return int 0 on success, other on failure

=cut

sub _uninstall
{
	my ($self, $pluginName) = @_;

	my $rs = $self->{'eventManager'}->trigger('onBeforeUninstallPlugin', $pluginName);
	$rs ||= $self->_call($pluginName, 'uninstall');
	$rs ||= $self->{'eventManager'}->trigger('onAfterUninstallPlugin', $pluginName);;
}

=item _enable($pluginName)

 Enable the given plugin

 Param string Plugin name
 Return int 0 on success, other on failure

=cut

sub _enable
{
	my ($self, $pluginName) = @_;

	my $rs = $self->{'eventManager'}->trigger('onBeforeEnablePlugin', $pluginName);
	$rs ||= $self->_call($pluginName, 'enable');
	$rs ||= $self->{'eventManager'}->trigger('onAfterEnablePlugin', $pluginName);
}

=item _disable($pluginName)

 Disable the given plugin

 Param string Plugin name
 Return int 0 on success, other on failure

=cut

sub _disable
{
	my ($self, $pluginName) = @_;

	my $rs = $self->{'eventManager'}->trigger('onBeforeDisablePlugin', $pluginName);
	$rs ||= $self->_call($pluginName, 'disable');
	$rs ||= $self->{'eventManager'}->trigger('onAfterDisablePlugin', $pluginName);
}

=item _change($pluginName)

 Change the given plugin

 Param string Plugin name
 Return int 0 on success, other on failure

=cut

sub _change
{
	my ($self, $pluginName) = @_;

	my $rs = $self->_disable($pluginName);
	$rs ||= $self->{'eventManager'}->trigger('onBeforeChangePlugin', $pluginName);
	$rs ||= $self->_call($pluginName, 'change');
	$rs ||= $self->{'eventManager'}->trigger('onAfterChangePlugin', $pluginName);
	return $rs if $rs;

	if($self->{'info'}->{'__need_change__'}) {
		$self->{'info'}->{'__need_change__'} = JSON::false;
		my $qrs = $self->{'db'}->doQuery(
			'u', 'UPDATE plugin SET plugin_info = ?, plugin_config_prev = ? WHERE plugin_name = ?',
			encode_json($self->{'info'}), encode_json($self->{'config'}), $pluginName
		);
		unless(ref $qrs eq 'HASH') {
			error($qrs);
			return 1;
		}
	}

	$self->_enable($pluginName);
}

=item _update($pluginName)

 Update the given plugin

 Param string Plugin name
 Return int 0 on success, other on failure

=cut

sub _update
{
	my ($self, $pluginName) = @_;

	my $rs = $self->_disable($pluginName);
	$rs ||= $self->{'eventManager'}->trigger('onBeforeUpdatePlugin', $pluginName);
	$rs ||= $self->_call($pluginName, 'update', $self->{'info'}->{'version'}, $self->{'info'}->{'__nversion__'});
	return $rs if $rs;

	$self->{'info'}->{'version'} = $self->{'info'}->{'__nversion__'};
	my $qrs = $self->{'db'}->doQuery(
		'u', 'UPDATE plugin SET plugin_info = ? WHERE plugin_name = ?', encode_json($self->{'info'}), $pluginName
	);
	unless(ref $qrs eq 'HASH') {
		error($qrs);
		return 1;
	}

	$rs = $self->{'eventManager'}->trigger('onAfterUpdatePlugin', $pluginName);
	return $rs if $rs;

	if($self->{'info'}->{'__need_change__'}) {
		$rs = $self->{'eventManager'}->trigger('onBeforeChangePlugin', $pluginName);
		$rs ||= $self->_call($pluginName, 'change');
		return $rs if $rs;

		$self->{'info'}->{'__need_change__'} = JSON::false;
		$qrs = $self->{'db'}->doQuery(
			'u', 'UPDATE plugin SET plugin_info = ?, plugin_config_prev = ? WHERE plugin_name = ?',
			encode_json($self->{'info'}), encode_json($self->{'config'}), $pluginName
		);
		unless(ref $qrs eq 'HASH') {
			error($qrs);
			return 1
		}

		$rs = $self->{'eventManager'}->trigger('onAfterChangePlugin', $pluginName);
		return $rs if $rs;
	}

	$self->_enable($pluginName);
}

=item _run($pluginName)

 Run the given plugin

 Param string Plugin name
 Return int 0 on success, other on failure

=cut

sub _run
{
	my ($self, $pluginName) = @_;

	my $rs = $self->{'eventManager'}->trigger('onBeforeRunPlugin', $pluginName);
	$rs ||= $self->_call($pluginName, 'run');
	$rs ||= $self->{'eventManager'}->trigger('onAfterRunPlugin', $pluginName);
}

=item _call($name, $method [, $fromVersion = undef [, $toVersion = undef ]])

 Execute the given plugin method

 Param string $name Plugin name
 Param string $method Name of the method to call on the plugin
 Param string OPTIONAL $fromVersion Version from which the plugin is being updated
 Param string OPTIONAL $toVersion Version to which the plugin is being updated
 Return int 0 on success, other on failure

=cut

sub _call
{
	my ($self, $name, $method, $fromVersion, $toVersion) = @_;

	my $backendPluginFile = "$main::imscpConfig{'GUI_ROOT_DIR'}/plugins/$name/backend/$name.pm";

	# Catch any compile time error
	eval { require $backendPluginFile; };
	if($@) { # We got an error due to a compile time error or missing file
		error($@);
		return 1;
	}

	my $plugin = "Plugin::$name";

	eval {
		# Turn any warning from plugin into exception
		local $SIG{__WARN__} = sub { die shift };

		if($plugin->can($method)) {
			$plugin = $plugin->getInstance(
				'eventManager' => $self->{'eventManager'},
				'action' => $self->{'action'},
				'info' => $self->{'info'},
				'config' => $self->{'config'},
				'config_prev' => $self->{'config_prev'}
			);
		} else {
			$plugin = undef;
		}
	};

	if($@) {
		error(sprintf('An unexpected error occurred: %s', $@));
		return 1;
	}

	if($plugin) {
		debug(sprintf("Executing %s::%s() action", ref $plugin, $method));
		my $rs = $plugin->$method($fromVersion, $toVersion);

		# Return value from the run() action is ignored by default because it's the responsability of the plugins to set
		# error status for their items. In case a plugin doesn't manage any item, it can force return value by defining
		# the FORCE_RETVAL attribute and set it value to 'yes'
		if($method ne 'run' || defined $plugin->{'FORCE_RETVAL'} && $plugin->{'FORCE_RETVAL'} eq 'yes') {
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
