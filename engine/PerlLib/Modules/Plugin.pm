#!/usr/bin/perl

=head1 NAME

 Modules::Plugin - i-MSCP Plugin module

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2014 by internet Multi Server Control Panel
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
#
# @category    i-MSCP
# @copyright   2010-2014 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Modules::Plugin;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Debug;
use iMSCP::Database;
use iMSCP::HooksManager;
use iMSCP::File;
use version;
use JSON;
use parent 'Common::SimpleClass';

# Map action status to next status
my %actionStatusToNextStatus = (
	'enabled' => 'enabled',
	'toinstall' => 'enabled',
	'toenable' => 'enabled',
	'toupdate' => 'enabled',
	'tochange' => 'enabled',
	'todisable' => 'disabled',
	'touninstall' => 'uninstalled'
);

=head1 DESCRIPTION

 This module provide the backend part of the i-MSCP plugin manager.

=head1 PUBLIC METHODS

=over 4

=item process($pluginId)

 Process action according plugin status

 Param int Plugin unique identifier
 Return int 0 on success, other on failure

=cut

sub process($$)
{
	my ($self, $pluginId) = @_;

	my $rs = $self->_loadData($pluginId);
	return $rs if $rs;

	my $status = $self->{'plugin_status'};
	my $pluginName = $self->{'plugin_name'};

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

	my @sql = (
		"UPDATE plugin SET " . ($rs ? 'plugin_error' : 'plugin_status') . " = ? WHERE plugin_id = ?",
		$rs ? (scalar getMessageByType('error') || 'unknown error') : $actionStatusToNextStatus{$status},
		$pluginId
	);
	my $rdata = $self->{'_db'}->doQuery('dummy', @sql);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	$rs;
}

=back

=head1 PRIVATE METHODS

=over 4

=item init()

 Called by getInstance(). Initialize instance of this class.

 Return Modules::Plugin

=cut

sub _init
{
	my $self = $_[0];

 	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();
 	$self->{'_db'} = iMSCP::Database->factory();

	$self;
}

=item _loadData($pluginId)

 Load plugin data

 Param int Plugin unique identifier
 Return int 0 on success, 1 on failure

=cut

sub _loadData($$)
{
	my ($self, $pluginId) = @_;

	my $rdata = $self->{'_db'}->doQuery(
		'plugin_id',
		'SELECT plugin_id, plugin_name, plugin_info, plugin_status FROM plugin WHERE plugin_id = ?',
		$pluginId
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	} elsif(! exists $rdata->{$pluginId}) {
		error("Data for plugin with ID $pluginId were not found in database");
		return 1
	}

	%{$self} = (%{$self}, %{$rdata->{$pluginId}});

	0;
}

=item _install($pluginName)

 Install the given plugin

 Param string Plugin name
 Return int 0 on success, other on failure

=cut

sub _install($$)
{
	my ($self, $pluginName) = @_;

	my $rs = $self->{'hooksManager'}->trigger('onBeforeInstallPlugin', $pluginName);

	$rs ||= $self->_exec($pluginName, 'install');

	$rs ||= $self->{'hooksManager'}->trigger('onAfterInstallPlugin', $pluginName);

	$rs ||= $self->_enable($pluginName);

	$rs;
}

=item _uninstall($pluginName)

 Uninstall the given plugin

 Param string Plugin name
 Return int 0 on success, other on failure

=cut

sub _uninstall($$)
{
	my ($self, $pluginName) = @_;

	my $rs = $self->{'hooksManager'}->trigger('onBeforeUninstallPlugin', $pluginName);

	$rs ||= $self->_exec($pluginName, 'uninstall');

	$rs ||= $self->{'hooksManager'}->trigger('onAfterUninstallPlugin', $pluginName);

	$rs;
}

=item _enable($pluginName)

 Enable the given plugin

 Param string Plugin name
 Return int 0 on success, other on failure

=cut

sub _enable($$)
{
	my ($self, $pluginName) = @_;

	my $rs = $self->{'hooksManager'}->trigger('onBeforeEnablePlugin', $pluginName);

	$rs ||= $self->_exec($pluginName, 'enable');

	$rs ||= $self->{'hooksManager'}->trigger('onAfterEnablePlugin', $pluginName);

	$rs;
}

=item _disable($pluginName)

 Disable the given plugin

 Param string Plugin name
 Return int 0 on success, other on failure

=cut

sub _disable($$)
{
	my ($self, $pluginName) = @_;

	my $rs = $self->{'hooksManager'}->trigger('onBeforeDisablePlugin', $pluginName);

	$rs ||= $self->_exec($pluginName, 'disable');

	$rs ||= $self->{'hooksManager'}->trigger('onAfterDisablePlugin', $pluginName);

	$rs;
}

=item _change($pluginName)

 Change the given plugin

 Param string Plugin name
 Return int 0 on success, other on failure

=cut

sub _change($$)
{
	my ($self, $pluginName) = @_;

	my $rs = $self->_disable($pluginName);

	$rs ||= $self->{'hooksManager'}->trigger('onBeforeChangePlugin', $pluginName);

	$rs ||= $self->_exec($pluginName, 'change');

	my $info = decode_json($self->{'plugin_info'});

	if($info->{'__need_change__'}) {
		$info->{'__need_change__'} = JSON::false;

		$rs = $self->{'_db'}->doQuery(
			'dummy', 'UPDATE plugin SET plugin_info = ? WHERE plugin_name = ?', encode_json($info), $pluginName
		);
		unless(ref $rs eq 'HASH') {
			error($rs);
			$rs = 1;
		} else {
			$rs = 0;
		}
	}

	$rs ||= $self->{'hooksManager'}->trigger('onAfterChangePlugin', $pluginName);

	$rs ||= $self->_enable($pluginName);

	$rs;
}

=item _update($pluginName)

 Update the given plugin

 Param string Plugin name
 Return int 0 on success, other on failure

=cut

sub _update($$)
{
	my ($self, $pluginName) = @_;

	my $rs = $self->_disable($pluginName);

	$rs ||= $self->{'hooksManager'}->trigger('onBeforeUpdatePlugin', $pluginName);

	my $info = decode_json($self->{'plugin_info'});

	$rs ||= $self->_exec($pluginName, 'update', $info->{'version'}, $info->{'__nversion__'});

	if(! $rs && ($info->{'__need_change__'} || qv("v$info->{'__nversion__'}") > qv("v$info->{'version'}"))) {
		$info->{'version'} = $info->{'__nversion__'};
		$info->{'__need_change__'} = JSON::false;

		$rs = $self->{'_db'}->doQuery(
			'dummy', 'UPDATE plugin SET plugin_info = ? WHERE plugin_name = ?', encode_json($info), $pluginName
		);
		unless(ref $rs eq 'HASH') {
			error($rs);
			$rs = 1;
		} else {
			$rs = 0;
		}
	}

	$rs ||= $self->{'hooksManager'}->trigger('onAfterUpdatePlugin', $pluginName);

	$rs ||= $self->_enable($pluginName);

	$rs;
}

=item _run($pluginName)

 Run the given plugin

 Param string Plugin name
 Return int 0 on success, other on failure

=cut

sub _run($$)
{
	my ($self, $pluginName) = @_;

	my $rs = $self->{'hooksManager'}->trigger('onBeforeRunPlugin', $pluginName);

	$rs ||= $self->_exec($pluginName, 'run');

	$rs ||= $self->{'hooksManager'}->trigger('onAfterRunPlugin', $pluginName);

	$rs;
}

=item _exec($pluginName, $pluginMethod, [$fromVersion = undef], [$toVersion = undef])

 Execute the given plugin method

 Param string Plugin name
 Param string Plugin method to execute
 Param string OPTIONAL Version from which the plugin is updated
 Param string OPTIONAL Version to which the plugin is updated
 Return int 0 on success, other on failure

=cut

sub _exec($$$;$$)
{
	my ($self, $pluginName, $pluginMethod, $fromVersion, $toVersion) = @_;

	my $backendPluginFile = "$main::imscpConfig{'ENGINE_ROOT_DIR'}/Plugins/$pluginName.pm";
	my $forceBackendInstall = 0;
	my $rs = 0;

	# When the plugin gets installed, updated or enabled, we install the plugin into the backend plugin directory.
	INSTALL_PLUGIN_BACKEND: {
		if($forceBackendInstall || $pluginMethod ~~ ['install', 'update', 'enable']) {
			my $guiPluginFile = "$main::imscpConfig{'GUI_ROOT_DIR'}/plugins/$pluginName/backend/$pluginName.pm";

			if(-f $guiPluginFile) {
				debug("Installing $pluginName.pm in backend plugin repository");
				my $file = iMSCP::File->new('filename' => $guiPluginFile);

				$rs = $file->copyFile($backendPluginFile, { 'preserve' => 'no' });
				return $rs if $rs;
			} else {
				error("Unable to install backend plugin: File $guiPluginFile not found");
				return 1;
			}
		}
	}

	# We trap any compile time error(s)
	eval { require $backendPluginFile; };

	if($@) { # We got an error due to a compile time error or missing file
		if(-f $backendPluginFile) {
			# Compile time error, we remove the file to force re-installation on next run
			iMSCP::File->new('filename' => $backendPluginFile)->delFile();
		} else {
			$forceBackendInstall = 1;
			goto INSTALL_PLUGIN_BACKEND; # File not found, we try to re-install it from the plugin package
		}

		error($@);
		return 1;
	}

	my $pluginClass = "Plugin::$pluginName";
	my $pluginInstance;

	eval {
		$pluginInstance = $pluginClass->getInstance(
			'hooksManager' => $self->{'hooksManager'}, 'action' => $self->{'action'}
		);
	};

	if($@) {
		iMSCP::File->new('filename' => $backendPluginFile)->delFile();
		error("An unexpected error occured: $@");
		return 1;
	}

	# We execute the action on the plugin only if it implements it
	if($pluginInstance->can($pluginMethod)) {
		debug("Executing ${pluginClass}::${pluginMethod}() action");
		$rs = $pluginInstance->$pluginMethod($fromVersion, $toVersion);

		# Return value from the run() action is ignored by default because it's the responsability of the plugins to set
		# error status for their items. In case a plugin doesn't manage any item, it can force return value by
		# defining the FORCE_RETVAL attribute and set it value to 'yes'
		if($pluginMethod ne 'run' || $pluginInstance->{'FORCE_RETVAL'} && $pluginInstance->{'FORCE_RETVAL'} eq 'yes') {
			return $rs if $rs;
		} else {
			$rs = 0;
		}
	}

	# In case the plugin has been disabled or uninstalled, we remove it.
	if($pluginMethod ~~ ['disable', 'uninstall']) {
		unless($pluginMethod eq 'disable' && $pluginInstance->can('uninstall')) {
			debug("Deleting $pluginName.pm from backend plugin repository");
			$rs = iMSCP::File->new('filename' => $backendPluginFile)->delFile();
		}
	}

	$rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
