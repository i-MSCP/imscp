#!/usr/bin/perl

=head1 NAME

 Modules::Plugin - i-MSCP Plugin module

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2013 by internet Multi Server Control Panel
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
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Modules::Plugin;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Database;
use iMSCP::HooksManager;
use iMSCP::File;
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

 This module represent the backend part of the i-MSCP plugin manager.

 See http://forum.i-mscp.net/Thread-DEV-Plugin-API-documentation-Relation-between-plugin-status-and-actions for more
info about specification.

=head1 PUBLIC METHODS

=over 4

=item loadData($pluginId)

 Load plugin data from database

 Param int Plugin unique identifier
 Return int 0 on success, 1 on failure

=cut

sub loadData($$)
{
	my ($self, $pluginId) = @_;

	my $rdata = iMSCP::Database->factory()->doQuery(
		'plugin_id',
		'SELECT plugin_id, plugin_name, plugin_info, plugin_status FROM plugin WHERE plugin_id = ?',
		$pluginId
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	unless(exists $rdata->{$pluginId}) {
		error("Plugin record with ID $pluginId has not been found in database");
		return 1
	}

	%{$self} = (%{$self}, %{$rdata->{$pluginId}});

	0;
}

=item process($pluginId)

 Process action according plugin status

 Param int Plugin unique identifier
 Return int 0 on success, other on failure

=cut

sub process($$)
{
	my ($self, $pluginId) = @_;

	my $rs = $self->loadData($pluginId);
	return $rs if $rs;

	my $status = $self->{'plugin_status'};
	my $pluginName = $self->{'plugin_name'};

	if($status eq 'enabled') {
		$rs = $self->_run($pluginName);
	} elsif($status eq 'toinstall') {
		$rs = $self->_install($pluginName);
	} elsif($status eq 'tochange') {
		$rs = $self->_change($pluginName);
	} elsif($status eq 'toupdate') {
		$rs = $self->_update($pluginName);
	} elsif($status eq 'touninstall') {
		$rs = $self->_uninstall($pluginName);
	} elsif($status eq 'toenable') {
		$rs = $self->_enable($pluginName);
	} elsif($status eq 'todisable') {
		$rs = $self->_disable($pluginName);
	} else {
		error("$pluginName plugin status is corrupted.");
		return 1;
	}

	my @sql = (
		"UPDATE plugin SET " . (($rs) ? 'plugin_error' : 'plugin_status') . " = ? WHERE plugin_id = ?",
		($rs ? (scalar getMessageByType('error') || 'unknown error') : $actionStatusToNextStatus{$status}), $pluginId
	);
	my $rdata = iMSCP::Database->factory()->doQuery('dummy', @sql);
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
	my $self = shift;

 	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self;
}

=item _install($pluginName)

 Install the given plugin

 Param string Plugin name
 Return int 0 on success, other on failure

=cut

sub _install($$)
{
	my ($self, $pluginName) = @_;

	my $rs ||= $self->{'hooksManager'}->trigger('onBeforeInstallPlugin', $pluginName);

	$rs ||= $self->_exec($pluginName, 'install');

	$rs ||= $self->{'hooksManager'}->trigger('onAfterInstallPlugin', $pluginName);

	$rs ||= $self->_enable($pluginName);

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

	my $rs ||= $self->{'hooksManager'}->trigger('onBeforeEnablePlugin', $pluginName);

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

	my $rs = $self->_disable();

	$rs ||= $self->{'hooksManager'}->trigger('onBeforeUpdatePlugin', $pluginName);

	require JSON;
	JSON->import();

	my $info = decode_json($self->{'plugin_info'});

	$rs ||= $self->_exec($pluginName, 'update', $info->{'version'}, $info->{'__nversion__'});

	unless($rs || $info->{'version'} eq $info->{'__nversion__'}) {
		$info->{'version'} = $info->{'__nversion__'};

		$rs = iMSCP::Database->factory()->doQuery(
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

	$rs ||= $self->_enable();

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
 Param string method to run on the plugin
 Param string OPTIONAL $fromVersion
 Param string OPTIONAL $toVersion
 Return int 0 on success, other on failure

=cut

sub _exec($$$)
{
	my ($self, $pluginName, $pluginMethod) = @_;

	my $pluginFile = "$main::imscpConfig{'ENGINE_ROOT_DIR'}/Plugins/$pluginName.pm";
	my $forceBackendInstall = 0;
	my $rs = 0;

	INSTALL_PLUGIN_BACKEND: {
		if($forceBackendInstall || $pluginMethod ~~ ['install', 'update', 'enable']) {
			my $guiPluginDir = "$main::imscpConfig{'GUI_ROOT_DIR'}/plugins";

			if(-f "$guiPluginDir/$pluginName/backend/$pluginName.pm") {
				debug("Plugin Manager: Installing $pluginName.pm in backend plugin repository");
				my $file = iMSCP::File->new('filename' => "$guiPluginDir/$pluginName/backend/$pluginName.pm");

				$rs = $file->copyFile($pluginFile, { 'preserve' => 'no' });
				return $rs if $rs;
			} else {
				error("Unable to install backend plugin: File $pluginFile not found");
				return 1;
			}
		}
	}

	# We trap any compile time error(s)
	eval { require $pluginFile; };

	if($@) { # We got an error due to a compile time error or missing file
		if(-f $pluginFile) {
			# Compile time error, we remove the file to force re-installation on next run
			iMSCP::File->new('filename' => $pluginFile)->delFile();
		} else {
			$forceBackendInstall = 1;
			goto INSTALL_PLUGIN_BACKEND; # File not found, we try to re-install it from the plugin package
		}

		error($@);
		return 1;
	}

	my $pluginClass = "Plugin::$pluginName";
	my $pluginInstance;

	eval { $pluginInstance = $pluginClass->getInstance('hooksManager' => $self->{'hooksManager'}); };

	if($@) {
		error("Plugin $pluginName has an invalid package name. Must be: $pluginClass");
		return 1;
	}

	# We execute the action on the plugin only if it implements it
	if($pluginInstance->can($pluginMethod)) {
		debug("Plugin Manager: Running $pluginClass::$pluginMethod() action");
		$rs = $pluginInstance->$pluginMethod(@_);

		# Return value from run() action is ignored by default because it's the responsability of the plugins to set
		# error status for their items. In case a plugin doesn't manage any item, it can force return value by
		# defining the FORCE_RETVAL attribute and set it to 'yes'
		if(
			$pluginMethod ne 'run' || defined $pluginInstance->{'FORCE_RETVAL'} &&
			$pluginInstance->{'FORCE_RETVAL'} eq 'yes'
		) {
			return $rs if $rs;
		} else {
			$rs = 0;
		}
	}

	# When these method are run, we remove the backend part of the plugin from the backend plugins directory
	if($pluginMethod ~~ ['disable', 'uninstall']) {
		debug("Plugin Manager: Deleting $pluginName.pm from plugin repository");
		my $file = iMSCP::File->new('filename' => $pluginFile);
		$rs = $file->delFile();
	}

	$rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
