#!/usr/bin/perl

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
# @author      Daniel Andreca <sci2tech@gmail.com>
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Modules::User;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::Execute;
use iMSCP::Database;
use iMSCP::SystemGroup;
use iMSCP::SystemUser;
use iMSCP::Rights;
use iMSCP::File;
use iMSCP::Ext2Attributes qw(setImmutable clearImmutable);
use parent 'Modules::Abstract';

sub _init
{
	my $self = shift;

	$self->{$_} = $self->{'args'}->{$_} for keys %{$self->{'args'}};

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self->{'type'} = 'User';

	$self;
}

sub loadData
{
	my $self = shift;

	# TODO User module shouldn't known about domain table
	my $rdata = iMSCP::Database->factory()->doQuery(
		'admin_id',
		'
			SELECT
				`admin_id`, `admin_name`, `admin_status`, `domain_name`, domain_traffic_limit
			FROM
				`admin`
			LEFT JOIN
				`domain` ON (`domain_admin_id` = `admin_id`)
			WHERE
				admin_id = ?
		',
		$self->{'userId'}
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	unless($rdata->{$self->{'userId'}}) {
		error("No such user $self->{'userId'}");
		return 1
	}

	$self->{$_} = $rdata->{$self->{'userId'}}->{$_} for keys %{$rdata->{$self->{'userId'}}};

	0;
}

sub process
{
	my $self = shift;

	$self->{'userId'} = shift;

	my $rs = $self->loadData();
	return $rs if $rs;

	my @sql;
	my $rdata;

	if($self->{'admin_status'} =~ /^(toadd|change)$/) {
		$rs = $self->add();
		@sql = (
			"UPDATE `admin` SET `admin_status` = ? WHERE `admin_id` = ?",
			($rs ? scalar getMessageByType('error') : 'ok'), $self->{'userId'}
		);
	} elsif($self->{'admin_status'} eq 'delete') {
		$rs = $self->delete();
		if($rs) {
			@sql = (
				"UPDATE `admin` SET `admin_status` = ? WHERE `admin_id` = ?",
				scalar getMessageByType('error'), $self->{'userId'}
			)
		} else {
			@sql = ('DELETE FROM `admin` WHERE `admin_id` = ?', $self->{'userId'});
		}
	}

	if(@sql) {
		$rdata = iMSCP::Database->factory()->doQuery('dummy', @sql);
		unless(ref $rdata eq 'HASH') {
			error($rdata);
			return 1;
		}
	}

	$rs;
}

sub add
{
	my $self = shift;

	my $groupName =
	my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} .
		($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'admin_id'});
	my $password = '';
	my $comment = 'iMSCP virtual user';
	# TODO change to  "$main::imscpConfig{'USER_HOME_DIR'}/$userName";
	my $home = "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'domain_name'}";
	my $skeletonPath = $self->{'skeletonPath'} || '/dev/null';
	my $shell = '/bin/false';

	my $rs = $self->{'hooksManager'}->trigger(
		'onBeforeAddImscpUnixUser', $userName, \$password, $groupName, \$comment, \$home, \$skeletonPath, \$shell
	);
	return $rs if $rs;

	# Creating i-MSCP unix user group
	$rs = iMSCP::SystemGroup->new()->addSystemGroup($groupName);
	return $rs if $rs;

	clearImmutable($home) if -d $home; # Transitional code - Will be removed ASAP

	# Creating i-MSCP unix user
	$rs = iMSCP::SystemUser->new(
		'password' => $password, 'comment' => $comment, 'home' => $home, 'skeletonPath' => $skeletonPath,
		'group' => $groupName, 'shell' => $shell
	)->addSystemUser(
		$userName
	);
	return $rs if $rs;

	my $uid = scalar getpwnam($userName);
	my $gid = scalar getgrnam($groupName);

	# Updating admin.admin_sys_uid and admin.admin_sys_gid columns
	my @sql = (
		"UPDATE `admin` SET `admin_sys_uid` = ?, `admin_sys_gid` = ? WHERE `admin_id` = ?", $uid, $gid, $self->{'userId'}
	);
	my $rdata = iMSCP::Database->factory()->doQuery('update', @sql);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	$self->{'hooksManager'}->trigger(
		'onAfterAddImscpUnixUser', $userName, $password, $groupName, $comment, $home, $skeletonPath, $shell
	);

	# Run the preaddUser(), addUser() and postaddUser() methods on servers/addons that implement them
	$rs = $self->SUPER::add();
	return $rs if $rs;
}

sub delete
{
	my $self = shift;

	my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} .
		($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'admin_id'});

	my $rs = $self->{'hooksManager'}->trigger('onBeforeDeleteImscpUnixUser', $userName);
	return $rs if $rs;

	# Run the predelUser(), delUser() and postdelUser() methods on servers/addons that implement them
	$rs = $self->SUPER::delete();
	return $rs if $rs;

	my $user = iMSCP::SystemUser->new();
	$user->{'force'} = 'yes';

	$rs = $user->delSystemUser($userName);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('onAfterDeleteImscpUnixUser', $userName);
}

sub buildHTTPDData
{
	my $self = shift;

	my $groupName =
	my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} .
		($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'admin_id'});

	$self->{'httpd'} = {
		USER => $userName,
		GROUP => $groupName,
		BASE_SERVER_VHOST_PREFIX => $main::imscpConfig{'BASE_SERVER_VHOST_PREFIX'},
		BASE_SERVER_VHOST => $main::imscpConfig{'BASE_SERVER_VHOST'},
		BWLIMIT => $self->{'domain_traffic_limit'}
	};

	0;
}

sub buildFTPDData
{
	my $self = shift;

	my $groupName =
	my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} .
		($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'admin_id'});

	$self->{'ftpd'} = {
		USER_ID => $self->{'admin_id'},
		USERNAME => $self->{'admin_name'},
		USER => $userName,
		GROUP => $groupName
	};

	0;
}

1;
