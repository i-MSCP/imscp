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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
#
# @category		i-MSCP
# @copyright	2010 - 2012 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @version		SVN: $Id$
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Modules::Htaccess;

use strict;
use warnings;
use iMSCP::Debug;
use Data::Dumper;

use vars qw/@ISA/;

@ISA = ('Common::SimpleClass', 'Modules::Abstract');
use Common::SimpleClass;
use Modules::Abstract;

sub _init{
	my $self		= shift;
	$self->{type}	= 'Htaccess';
}

sub loadData{

	my $self = shift;

	my $sql = "
		SELECT
			`t3`.`id`, `t3`.`auth_type`, `t3`.`auth_name`, `t3`.`path`, `t3`.`status`,
			`t3`.`users`, `t3`.`groups`, `t4`.`domain_name`, `t4`.`domain_admin_id`
		FROM
			(
				SELECT * from `htaccess`,
				(
					SELECT IFNULL(
					(
						SELECT group_concat(`uname` SEPARATOR ' ')
						FROM `htaccess_users`
						WHERE `id` regexp (
							CONCAT(
								'^(',
								(
									SELECT REPLACE(
										(SELECT `user_id` FROM `htaccess` WHERE `id` = ?),
										',',
										'|'
									)
								),
								')\$'
							)
						) GROUP BY `dmn_id`
					), '') as `users`
				) as t1,
				(
					SELECT IFNULL(
					(
						SELECT group_concat(`ugroup` SEPARATOR ' ')
						FROM `htaccess_groups`
						WHERE `id` regexp (
							CONCAT(
								'^(',
								(
									SELECT REPLACE(
										(SELECT `group_id` FROM `htaccess` WHERE `id` = ?),
										',',
										'|'
									)
								),
								')\$'
							)
						) GROUP BY `dmn_id`
					), '') as `groups`
				) as t2
			) as t3
		LEFT JOIN
			`domain` AS `t4`
		ON
			`t3`.`dmn_id` = `t4`.`domain_id`
		WHERE
			`t3`.`id` = ?
	";

	my $rdata = iMSCP::Database->factory()->doQuery('id', $sql, $self->{htaccessId}, $self->{htaccessId}, $self->{htaccessId});

	error("$rdata") and return 1 if(ref $rdata ne 'HASH');
	error("No record in table htaccess has id = $self->{htaccessId}") and return 1 unless(exists $rdata->{$self->{htaccessId}});

	unless($rdata->{$self->{htaccessId}}->{domain_name}){
		local $Data::Dumper::Terse = 1;
		error("Orphan entry: ".Dumper($rdata->{$self->{htaccessId}}));
		my @sql = (
			"UPDATE `htaccess` SET `status` = ? WHERE `id` = ?",
			"Orphan entry: ".Dumper($rdata->{$self->{htaccessId}}),
			$self->{htaccessId}
		);
		my $rdata = iMSCP::Database->factory()->doQuery('update', @sql);
		return 1;
	}

	$self->{$_} = $rdata->{$self->{htaccessId}}->{$_} for keys %{$rdata->{$self->{htaccessId}}};

	0;
}

sub process{

	my $self		= shift;
	$self->{htaccessId}	= shift;

	my $rs = $self->loadData();
	return $rs if $rs;

	my @sql;

	if($self->{status} =~ /^toadd|change$/){
		$rs = $self->add();
		@sql = (
			"UPDATE `htaccess` SET `status` = ? WHERE `id` = ?",
			($rs ? scalar getMessageByType('ERROR') : 'ok'),
			$self->{id}
		);
	}elsif($self->{status} =~ /^delete$/){
		$rs = $self->delete();
		if($rs){
			@sql = (
				"UPDATE `htaccess` SET `status` = ? WHERE `id` = ?",
				scalar getMessageByType('ERROR'),
				$self->{id}
			);
		}else {
			@sql = ("DELETE FROM `htaccess` WHERE `id` = ?", $self->{id});
		}
	}

	my $rdata = iMSCP::Database->factory()->doQuery('delete', @sql);
	error("$rdata") and return 1 if(ref $rdata ne 'HASH');

	$rs;
}

sub buildHTTPDData{

	my $self	= shift;

	my $groupName	=
	my $userName	=
						$main::imscpConfig{SYSTEM_USER_PREFIX}.
						($main::imscpConfig{SYSTEM_USER_MIN_UID} + $self->{domain_admin_id});

	my $hDir 		= "$main::imscpConfig{'USER_HOME_DIR'}/$self->{domain_name}";
	my $pathDir 		= "$main::imscpConfig{'USER_HOME_DIR'}/$self->{domain_name}/$self->{path}";
	$pathDir			=~ s~/+~/~g;
	$hDir			=~ s~/+~/~g;

	$self->{httpd} = {
		USER		=> $userName,
		GROUP		=> $groupName,
		AUTH_TYPE	=> $self->{auth_type},
		AUTH_NAME	=> $self->{auth_name},
		AUTH_PATH	=> $pathDir,
		HOME_PATH	=> $hDir,
		DMN_NAME	=> $self->{domain_name},
		HTUSERS		=> $self->{users},
		HTGROUPS	=> $self->{groups},

	};

	0;
}

1;
