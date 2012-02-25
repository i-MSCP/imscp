#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010 - 2011 by internet Multi Server Control Panel
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
# @category		i-MSCP
# @copyright	2010 - 2012 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @version		SVN: $Id$
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Modules::User;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Execute;
use iMSCP::Database;
use Data::Dumper;

use vars qw/@ISA/;

@ISA = ('Common::SimpleClass', 'Modules::Abstract');
use Common::SimpleClass;
use Modules::Abstract;

sub _init{
	my $self		= shift;
	$self->{type}	= 'User';
}

sub loadData{

	my $self = shift;

	my $sql = "
		SELECT
			*
		FROM
			`domain`
		LEFT JOIN
			`admin`
		ON
			`domain_admin_id` = `admin_id`
		WHERE
			`domain_admin_id` = ?
		";

	my $database = iMSCP::Database->factory();
	my $rdata = $database->doQuery('domain_admin_id', $sql, $self->{usrId});

	error("$rdata") and return 1 if(ref $rdata ne 'HASH');
	error("No such user $self->{usrId}") and return 1 unless(exists $rdata->{$self->{usrId}});

	$self->{$_} = $rdata->{$self->{usrId}}->{$_} for keys %{$rdata->{$self->{usrId}}};

	0;
}

sub process{

	my $self		= shift;
	$self->{usrId}	= shift;

	my $rs = $self->loadData();
	return $rs if $rs;

	my @sql;
	my $rdata;

	if($self->{domain_status} =~ /^(toadd|change|toenable)$/){
		$rs = $self->add();
		@sql = (
			"UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?",
			scalar getMessageByType('ERROR'),
			$self->{domain_id}
		)if $rs;
	}elsif($self->{domain_status} =~ /^delete$/){
		$rs = $self->delete();
		@sql = (
			"UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?",
			scalar getMessageByType('ERROR'),
			$self->{domain_id}
		)if $rs;
	}

	if(scalar @sql){
		$rdata = iMSCP::Database->factory()->doQuery('delete', @sql);
		error("$rdata") and return 1 if(ref $rdata ne 'HASH');
	}

	$rs;
}

sub add{

	use Modules::SystemGroup;
	use Modules::SystemUser;
	use iMSCP::Rights;
	use Servers::httpd;

	my $self = shift;

	error('Data not defined') if ! $self->{domain_admin_id};
	return 1  if ! $self->{domain_admin_id};

	my $rs = 0;

	my $groupName	=
	my $userName	=
			$main::imscpConfig{SYSTEM_USER_PREFIX}.
			($main::imscpConfig{SYSTEM_USER_MIN_UID} + $self->{domain_admin_id});

	$rs = Modules::SystemGroup->new()->addSystemGroup($groupName);
	return $rs if $rs;

	my $user = Modules::SystemUser->new();
	$user->{comment}	= "iMSCP virtual user";
	$user->{home}		= "$main::imscpConfig{'USER_HOME_DIR'}/$self->{domain_name}";
	$user->{group}		= $groupName;
	$user->{shell} 		= '/bin/false';
	$rs = $user->addSystemUser($userName);
	return $rs if $rs;

	my $httpdGroup = (
		Servers::httpd->factory()->can('getRunningGroup')
		?
		Servers::httpd->factory()->getRunningGroup()
		:
		'-1'
	);
	my $rootUser	= $main::imscpConfig{ROOT_USER};
	my $rootGroup	= $main::imscpConfig{ROOT_GROUP};

	$rs |= iMSCP::Dir->new(
		dirname => "$main::imscpConfig{'USER_HOME_DIR'}/$self->{domain_name}"
	)->make({
			mode	=> 0750,
			user	=> $userName,
			group	=> $httpdGroup
	});

	$rs |= $self->oldEngineCompatibility();

	$rs |= iMSCP::Dir->new(
		dirname => "$main::imscpConfig{'USER_HOME_DIR'}/$self->{domain_name}/logs"
	)->make({
			mode	=> 0750,
			user	=> $userName,
			group	=> $groupName
	});

	$rs |= iMSCP::Dir->new(
		dirname => "$main::imscpConfig{'USER_HOME_DIR'}/$self->{domain_name}/backups"
	)->make({
			mode	=> 0755,
			user	=> $rootUser,
			group	=> $rootGroup
	});

	$self->{mode}	= 'add';
	$rs |= $self->runAllSteps();

	$rs;
}

sub delete{

	use Modules::SystemGroup;
	use Modules::SystemUser;
	use iMSCP::Rights;

	my $self = shift;
	my $rs = 0;

	error('Data not defined') if ! $self->{domain_admin_id};
	return 1  if ! $self->{domain_admin_id};

	$self->{mode}	= 'del';
	$rs = $self->runAllSteps();

	my $userName	=
			$main::imscpConfig{SYSTEM_USER_PREFIX}.
			($main::imscpConfig{SYSTEM_USER_MIN_UID} + $self->{domain_admin_id});

	my $user = Modules::SystemUser->new();
	$user->{force} = 'yes';

	$rs |= $user->delSystemUser($userName);

	$rs;
}

sub oldEngineCompatibility{

	use Modules::SystemGroup;
	use Modules::SystemUser;
	use Servers::httpd;
	use iMSCP::Rights;

	my $self		= shift;
	my $rs			= 0;
	my $userName	=
	my $groupName	=
			$main::imscpConfig{SYSTEM_USER_PREFIX}.
			($main::imscpConfig{SYSTEM_USER_MIN_UID} + $self->{domain_admin_id});

	my $uid			= scalar getpwnam($userName);
	my $gid			= scalar getgrnam($groupName);

	my @sql = ("UPDATE `domain` SET `domain_uid` = ?, `domain_gid` = ? WHERE `domain_name` = ?", $uid, $gid, $self->{domain_name});
	my $rdata = iMSCP::Database->factory()->doQuery('update', @sql);
	error("$rdata") if(ref $rdata ne 'HASH');

	@sql = (
		"UPDATE
			`ftp_users`
		SET
			`uid` = ?,
			`gid` = ?
		WHERE
			`homedir` LIKE '$main::imscpConfig{USER_HOME_DIR}/$self->{domain_name}/%'
		OR
			`homedir` = '$main::imscpConfig{USER_HOME_DIR}/$self->{domain_name}'
		",
		$uid,
		$gid
	);
	$rdata = iMSCP::Database->factory()->doQuery('update', @sql);
	error("$rdata") if(ref $rdata ne 'HASH');

	@sql = ("UPDATE `ftp_group` SET `gid` = ? WHERE `groupname` = ?", $uid, $self->{domain_name});
	$rdata = iMSCP::Database->factory()->doQuery('update', @sql);
	error("$rdata") if(ref $rdata ne 'HASH');

	my $httpdGroup = (
		Servers::httpd->factory()->can('getRunningGroup')
		?
		Servers::httpd->factory()->getRunningGroup()
		:
		$groupName
	);

	my $hDir = "$main::imscpConfig{USER_HOME_DIR}/$self->{domain_name}";
	my ($stdout, $stderr);

	my $cmd	= "$main::imscpConfig{'CMD_CHOWN'} -R $userName:$httpdGroup $hDir";
	$rs		|= execute($cmd, \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	error("$stderr") if $stderr;

	$rs |= setRights(
		"$hDir/domain_disable_page",
		{
			user		=> $main::imscpConfig{ROOT_USER},
			group		=> $httpdGroup,
			filemode	=> '0640',
			dirmode		=> '0710',
			recursive	=> 'yes'
		}
	) if -d "$hDir/domain_disable_page";

	$rs |= setRights(
		"$hDir/backups",
		{
			user		=> $main::imscpConfig{ROOT_USER},
			group		=> $main::imscpConfig{ROOT_GROUP},
			filemode	=> '0640',
			dirmode		=> '0750',
			recursive	=> 'yes'
		}
	) if -d "$hDir/backups";

	0;
}

sub buildHTTPDData{

	my $self	= shift;
	my $groupName	=
	my $userName	=
			$main::imscpConfig{SYSTEM_USER_PREFIX}.
			($main::imscpConfig{SYSTEM_USER_MIN_UID} + $self->{domain_admin_id});
	my $hDir 		= "$main::imscpConfig{USER_HOME_DIR}/$self->{domain_name}";
	$hDir			=~ s~/+~/~g;

	my $sql = "SELECT * FROM `config` WHERE `name` LIKE 'PHPINI%'";
	my $rdata = iMSCP::Database->factory()->doQuery('name', $sql);
	error("$rdata") and return 1 if(ref $rdata ne 'HASH');

	$sql			= "SELECT * FROM `php_ini` WHERE `domain_id` = ?";
	my $phpiniData	= iMSCP::Database->factory()->doQuery('domain_id', $sql, $self->{domain_id});
	error("$phpiniData") and return 1 if(ref $phpiniData ne 'HASH');

	$self->{httpd} = {
		DMN_NAME					=> $self->{domain_name},
		DOMAIN_NAME					=> $self->{domain_name},
		HOME_DIR					=> $hDir,
		PEAR_DIR					=> $main::imscpConfig{PEAR_DIR},
		PHP_TIMEZONE				=> $main::imscpConfig{PHP_TIMEZONE},
		USER						=> $userName,
		GROUP						=> $groupName,
		BASE_SERVER_VHOST_PREFIX	=> $main::imscpConfig{BASE_SERVER_VHOST_PREFIX},
		BASE_SERVER_VHOST			=> $main::imscpConfig{BASE_SERVER_VHOST},
		BWLIMIT						=> $self->{domain_traffic_limit},
		DISABLE_FUNCTIONS			=> (exists $phpiniData->{$self->{domain_id}} ? $phpiniData->{$self->{domain_id}}->{disable_functions} : $rdata->{PHPINI_DISABLE_FUNCTIONS}->{value}),
		MAX_EXECUTION_TIME			=> (exists $phpiniData->{$self->{domain_id}} ? $phpiniData->{$self->{domain_id}}->{max_execution_time} : $rdata->{PHPINI_MAX_EXECUTION_TIME}->{value}),
		MAX_INPUT_TIME				=> (exists $phpiniData->{$self->{domain_id}} ? $phpiniData->{$self->{domain_id}}->{max_input_time} : $rdata->{PHPINI_MAX_INPUT_TIME}->{value}),
		MEMORY_LIMIT				=> (exists $phpiniData->{$self->{domain_id}} ? $phpiniData->{$self->{domain_id}}->{memory_limit} : $rdata->{PHPINI_MEMORY_LIMIT}->{value}),
		ERROR_REPORTING				=> (exists $phpiniData->{$self->{domain_id}} ? $phpiniData->{$self->{domain_id}}->{error_reporting} : $rdata->{PHPINI_ERROR_REPORTING}->{value}),
		DISPLAY_ERRORS				=> (exists $phpiniData->{$self->{domain_id}} ? $phpiniData->{$self->{domain_id}}->{display_errors} : $rdata->{PHPINI_DISPLAY_ERRORS}->{value}),
		REGISTER_GLOBALS			=> (exists $phpiniData->{$self->{domain_id}} ? $phpiniData->{$self->{domain_id}}->{register_globals} : $rdata->{PHPINI_REGISTER_GLOBALS}->{value}),
		POST_MAX_SIZE				=> (exists $phpiniData->{$self->{domain_id}} ? $phpiniData->{$self->{domain_id}}->{post_max_size} : $rdata->{PHPINI_POST_MAX_SIZE}->{value}),
		UPLOAD_MAX_FILESIZE			=> (exists $phpiniData->{$self->{domain_id}} ? $phpiniData->{$self->{domain_id}}->{upload_max_filesize} : $rdata->{PHPINI_UPLOAD_MAX_FILESIZE}->{value}),
		ALLOW_URL_FOPEN				=> (exists $phpiniData->{$self->{domain_id}} ? $phpiniData->{$self->{domain_id}}->{allow_url_fopen} : $rdata->{PHPINI_ALLOW_URL_FOPEN}->{value}),
		PHPINI_OPEN_BASEDIR			=> (exists $phpiniData->{$self->{domain_id}}->{PHPINI_OPEN_BASEDIR} ? ':'.$phpiniData->{$self->{domain_id}}->{PHPINI_OPEN_BASEDIR} : $rdata->{PHPINI_OPEN_BASEDIR}->{value} ? ':'.$rdata->{PHPINI_OPEN_BASEDIR}->{value} : '')
	};

	0;
}
1;
