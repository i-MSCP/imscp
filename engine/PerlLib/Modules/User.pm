#!/usr/bin/perl

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
# @author      Daniel Andreca <sci2tech@gmail.com>
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Modules::User;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

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
	my $self = $_[0];

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self->{'type'} = 'User';

	$self;
}

sub loadData
{
	my $self = $_[0];

	my $rdata = iMSCP::Database->factory()->doQuery(
		'admin_id',
		'
			SELECT
				admin_id, admin_name, admin_sys_name, admin_sys_uid, admin_sys_gname, admin_sys_gid, admin_status
			FROM
				admin
			WHERE
				admin_id = ?
		',
		$self->{'userId'}
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	unless(exists $rdata->{$self->{'userId'}}) {
		error("User record with ID $self->{'userId'} has not been found in database");
		return 1
	}

	%{$self} = (%{$self}, %{$rdata->{$self->{'userId'}}});

	0;
}

sub process
{
	my $self = $_[0];

	$self->{'userId'} = $_[1];

	my $rs = $self->loadData();
	return $rs if $rs;

	my @sql;

	if($self->{'admin_status'} ~~ ['toadd', 'tochange']) {
		$rs = $self->add();

		@sql = (
			'UPDATE admin SET admin_status = ? WHERE admin_id = ?',
			($rs ? scalar getMessageByType('error') : 'ok'), $self->{'userId'}
		);
	} elsif($self->{'admin_status'} eq 'todelete') {
		$rs = $self->delete();

		if($rs) {
			@sql = (
				'UPDATE admin SET admin_status = ? WHERE admin_id = ?',
				scalar getMessageByType('error'), $self->{'userId'}
			)
		} else {
			@sql = ('DELETE FROM admin WHERE admin_id = ?', $self->{'userId'});
		}
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

sub add
{
	my $self = $_[0];

	my $userName =
	my $groupName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} .
		($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'admin_id'});
	my $password = '';
	my $comment = 'i-MSCP Web User';
	my $homedir = "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'admin_name'}";
	my $skeletonPath = $self->{'skeletonPath'} || '/dev/null';
	my $shell = '/bin/false';

	my ($oldUserName, undef, $userUid, $userGid) = getpwuid($self->{'admin_sys_uid'});

	my $rs = $self->{'hooksManager'}->trigger(
        'onBeforeAddImscpUnixUser', $self->{'admin_id'}, $userName, \$password, $groupName, \$comment, \$homedir,
		\$skeletonPath, \$shell, $userUid, $userGid
	);
	return $rs if $rs;

	clearImmutable($homedir) if -d $homedir;

	if(! $oldUserName || $userUid == 0) {
		# Creating i-MSCP unix user
		$rs = iMSCP::SystemUser->new(
			'password' => $password,
			'comment' => $comment,
			'home' => $homedir,
			'skeletonPath' => $skeletonPath,
			'shell' => $shell
		)->addSystemUser($userName);
		return $rs if $rs;

		$userUid = getpwnam($userName);
		$userGid = getgrnam($groupName);
	} else {
		# Modifying existents i-MSCP unix user
		my @cmd = (
			"$main::imscpConfig{'CMD_PKILL'} -KILL -u", escapeShell($oldUserName), ';',
			"$main::imscpConfig{'CMD_USERMOD'}",
			'-c', escapeShell($comment), # New comment
			'-d', escapeShell($homedir), # New homedir
			'-l', escapeShell($userName), # New login
			'-m', # Move current homedir content to new homedir
			'-s', escapeShell($shell), #  New Shell
			escapeShell($self->{'admin_sys_name'}) # Old username
		);
		my($stdout, $stderr);
		$rs = execute("@cmd", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		debug($stderr) if $stderr && $rs;
		return $rs if $rs;

		# Modifying existents i-MSCP unix group
		@cmd = (
			$main::imscpConfig{'CMD_GROUPMOD'},
			'-n', escapeShell($groupName), # New group name
			escapeShell($self->{'admin_sys_gname'}) # Current group name
		);
		$rs = execute("@cmd", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		debug($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	# Updating admin.admin_sys_name, admin.admin_sys_uid, admin.admin_sys_gname and admin.admin_sys_gid columns
	my @sql = (
		'
			UPDATE
				admin
			SET
				admin_sys_name = ?, admin_sys_uid = ?, admin_sys_gname = ?, admin_sys_gid = ?
			WHERE
				admin_id = ?
		',
		$userName, $userUid, $groupName, $userGid, $self->{'userId'}
	);
	my $rdata = iMSCP::Database->factory()->doQuery('dummy', @sql);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	$self->{'admin_sys_name'} = $userName;
	$self->{'admin_sys_uid'} = $userUid;
	$self->{'admin_sys_gname'} = $groupName;
	$self->{'admin_sys_gid'} = $userGid;

	$self->{'hooksManager'}->trigger(
		'onAfterAddImscpUnixUser', $self->{'admin_id'}, $userName, $password, $groupName, $comment, $homedir,
		$skeletonPath, $shell, $userUid, $userGid
	);

	# Run the preaddUser(), addUser() and postaddUser() methods on servers/packages that implement them
	$self->SUPER::add();
}

sub delete
{
	my $self = $_[0];

	my $userName =
	my $groupName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} .
		($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'admin_id'});

	my $rs = $self->{'hooksManager'}->trigger('onBeforeDeleteImscpUnixUser', $userName);
	return $rs if $rs;

	# Run the predeleteUser(), deleteUser() and postdeleteUser() methods on servers/packages that implement them
	$rs = $self->SUPER::delete();
	return $rs if $rs;

	$rs = iMSCP::SystemUser->new('force' => 'yes')->delSystemUser($userName);
	return $rs if $rs;

	# Only needed to cover the case where the admin added other users to the unix group
	$rs = iMSCP::SystemGroup->getInstance()->delSystemGroup($groupName);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('onAfterDeleteImscpUnixUser', $userName);
}


sub _getHttpdData
{
	my $self = $_[0];

	my $groupName =
	my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} .
		($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'admin_id'});

	$self->{'httpd'} = {
		USER => $userName,
		GROUP => $groupName,
	};

	0;
}

sub _getFtpdData
{
	my $self = $_[0];

	my $groupName =
	my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} .
		($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'admin_id'});

	$self->{'ftpd'} = {
		USER_ID => $self->{'admin_id'},
		USER_SYS_UID => $self->{'admin_sys_uid'},
		USER_SYS_GID => $self->{'admin_sys_gid'},
		USERNAME => $self->{'admin_name'},
		USER => $userName,
		GROUP => $groupName
	};

	0;
}

1;
