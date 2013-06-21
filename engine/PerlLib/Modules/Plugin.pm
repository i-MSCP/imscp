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

=head1 DESCRIPTION

 This module is responsible to run actions on the plugins according their current status. To each status can correspond
a specific action, which is executed by the module:

 - install status: The install status correspond to the install action
 - uninstall status: The uninstall status correspond to the uninstall action
 - enabled status: The enabled status correspond to the process action
 - disabled status: No action
 - other status: No action

 The module will attempt to run the action on the plugin only if it implements it.

 When the install action is run, the plugin file is automatically installed from the plugin package into the
imscp/engine/Plugins directory, which is the plugins backend repository. The plugin file must be located in a specific
subdirectory of plugin package:

 <PluginName>/backend/<PluginName>.pm

 The plugin packages are located in the imscp/gui/plugins directory.

 When the uninstall action is run, the plugin file is simply deleted from the backend plugins repository.

=head1 PUBLIC METHODS

=over 4

=item loadData()

 Load plugin data

 Return 0 on success, 1 on failure

=cut

sub loadData
{
	my $self = shift;

	my $rdata = iMSCP::Database->factory()->doQuery(
		'plugin_id',
		'SELECT `plugin_id`, `plugin_name`, `plugin_status` FROM `plugin` WHERE `plugin_id` = ?',
		$self->{'pluginId'}
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	unless($rdata->{$self->{'pluginId'}}) {
		error("No plugin has ID: $self->{'pluginId'}");
		return 1
	}

	$self->{$_} = $rdata->{$self->{'pluginId'}}->{$_} for keys %{$rdata->{$self->{'pluginId'}}};

	0;
}

=item process($$)

 Process plugin

 Param int Plugin unique identifier
 Return int 0 on success, other on failure

=cut

sub process
{
	my $self = shift;

	$self->{'pluginId'} = shift;

	my $rs = $self->loadData();
	return $rs if $rs;

	my @sql;

	if($self->{'plugin_status'} eq 'enabled') {
		$rs = $self->_executePlugin('run');
	} elsif($self->{'plugin_status'} eq 'install') {
		$rs = $self->_executePlugin('install');
		@sql = (
			"UPDATE `plugin` SET `plugin_status` = ? WHERE `plugin_id` = ?",
			($rs ? scalar getMessageByType('error') : 'enabled'), $self->{'pluginId'}
		);
	} elsif($self->{'plugin_status'} eq 'uninstall') {
		$rs = $self->_executePlugin('uninstall');
		@sql = (
			"UPDATE `plugin` SET `plugin_status` = ? WHERE `plugin_id` = ?",
			($rs ? scalar getMessageByType('error') : 'disabled'), $self->{'pluginId'}
		);
	}

	if(@sql) {
		my $rdata = iMSCP::Database->factory()->doQuery('dummy', @sql);
		unless(ref $rdata eq 'HASH') {
			error($rdata);
			return 1;
		}
	}

	$rs;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize module instance

 Return Modules::Plugin

=cut

sub _init
{
	my $self = shift;

	$self->{$_} = $self->{'args'}->{$_} for keys %{$self->{'args'}};
	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self;
}

=item _executePlugin($action)

 Execute the given plugin action

 Param string Plugin method to call
 Return int 0 on success, other on failure

=cut

sub _executePlugin($$)
{
	my $self = shift;
	my $action = shift;

	my $backendPluginsDir = "$main::imscpConfig{'ENGINE_ROOT_DIR'}/Plugins";
	my $pluginFile = "$backendPluginsDir/$self->{'plugin_name'}.pm";
	my $rs = 0;

	# The 'install' action is scheduled either when the plugin is activated through the panel
	# interface or when i-MSCP is updated.
	#
	# Each time the action 'install' is scheduled, we try to install the newest version of
	# the plugin as provided in plugin package.
	if($action eq 'install') {
		my $guiPluginDir = "$main::imscpConfig{'GUI_ROOT_DIR'}/plugins";

		if(-f "$guiPluginDir/$self->{'plugin_name'}/backend/$self->{'plugin_name'}.pm") {
			my $file = iMSCP::File->new(
				'filename' => "$guiPluginDir/$self->{'plugin_name'}/backend/$self->{'plugin_name'}.pm"
			);

			$rs = $file->copyFile($pluginFile, { 'preserve' => 'no' });
			return $rs if $rs;
		} else {
			error("Unable to install backend plugin: Plugin file $pluginFile not found");
			return 1;
		}
	}

	require $pluginFile;

	my $pluginClass = "Plugin::$self->{'plugin_name'}";

	# Any backend plugin is a singleton which receive an iMSCP::HooksManager instance
	my $pluginInstance = $pluginClass->getInstance('hooksManager' => $self->{'hooksManager'});

	# If the given action is implemented in the backend plugin, we call it.
	if($pluginInstance->can($action)) {
		$rs = $pluginInstance->$action();
		return $rs if $rs;
	}

	# When the 'uninstall' action is scheduled, we simply remove the plugin file
	if($action eq 'uninstall' && -f $pluginFile) {
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
