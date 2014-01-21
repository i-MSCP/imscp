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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
#
# @category    i-MSCP
# @copyright   2010-2014 by i-MSCP | http://i-mscp.net
# @author      Daniel Andreca <sci2tech@gmail.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Modules::Htaccess;

use strict;
use warnings;

use iMSCP::Debug;
use parent 'Modules::Abstract';

sub _init
{
	my $self = $_[0];

	$self->{'type'} = 'Htaccess';

	$self;
}

sub loadData
{
	my $self = $_[0];

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
								'^(', (SELECT REPLACE((SELECT `user_id` FROM `htaccess` WHERE `id` = ?), ',', '|')), ')\$'
							)
						) GROUP BY `dmn_id`
					), '') AS `users`
				) AS `t1`,
				(
					SELECT IFNULL(
					(
						SELECT group_concat(`ugroup` SEPARATOR ' ')
						FROM `htaccess_groups`
						WHERE `id` regexp (
							CONCAT(
								'^(', (SELECT REPLACE((SELECT `group_id` FROM `htaccess` WHERE `id` = ?), ',', '|')), ')\$'
							)
						) GROUP BY `dmn_id`
					), '') AS `groups`
				) AS `t2`
			) AS `t3`
		INNER JOIN
			`domain` AS `t4` ON (`t3`.`dmn_id` = `t4`.`domain_id`)
		WHERE
			`t3`.`id` = ?
	";

	my $rdata = iMSCP::Database->factory()->doQuery(
		'id', $sql, $self->{'htaccessId'}, $self->{'htaccessId'}, $self->{'htaccessId'}
	);

	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	unless(exists $rdata->{$self->{'htaccessId'}}) {
		error("Htaccess record with ID '$self->{'htaccessId'}' has not been found in database");
		return 1;
	}

	unless(exists $rdata->{$self->{'htaccessId'}}->{'domain_name'}) {
		require Data::Dumper;
		Data::Dumper->import();
		local $Data::Dumper::Terse = 1;
		error("Orphan entry: " . Dumper($rdata->{$self->{'htaccessId'}}));

		my @sql = (
			"UPDATE `htaccess` SET `status` = ? WHERE `id` = ?",
			'Orphan entry: ' . Dumper($rdata->{$self->{'htaccessId'}}),
			$self->{'htaccessId'}
		);
		my $rdata = iMSCP::Database->factory()->doQuery('dummy', @sql);

		return 1;
	}

	%{$self} = (%{$self}, %{$rdata->{$self->{'htaccessId'}}});

	0;
}

sub process
{
	my $self = $_[0];

	$self->{'htaccessId'} = $_[1];

	my $rs = $self->loadData();
	return $rs if $rs;

	my @sql;

	if($self->{'status'} =~ /^toadd|tochange$/) {
		$rs = $self->add();

		@sql = (
			"UPDATE `htaccess` SET `status` = ? WHERE `id` = ?",
			($rs ? scalar getMessageByType('error') : 'ok'),
			$self->{'id'}
		);
	} elsif($self->{'status'} eq 'todelete') {
		$rs = $self->delete();

		if($rs) {
			@sql = (
				"UPDATE `htaccess` SET `status` = ? WHERE `id` = ?",
				scalar getMessageByType('error'),
				$self->{'id'}
			);
		} else {
			@sql = ("DELETE FROM `htaccess` WHERE `id` = ?", $self->{'id'});
		}
	}

	my $rdata = iMSCP::Database->factory()->doQuery('dummy', @sql);

	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	$rs;
}

sub buildHTTPDData
{
	my $self = $_[0];

	my $groupName =
	my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} .
		($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'domain_admin_id'});

	my $hDir = "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'domain_name'}";
	my $pathDir = "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'domain_name'}/$self->{'path'}";
	$pathDir =~ s~/+~/~g;
	$hDir =~ s~/+~/~g;

	$self->{'httpd'} = {
		USER => $userName,
		GROUP => $groupName,
		AUTH_TYPE => $self->{'auth_type'},
		AUTH_NAME => $self->{'auth_name'},
		AUTH_PATH => $pathDir,
		HOME_PATH => $hDir,
		DOMAIN_NAME => $self->{'domain_name'},
		HTUSERS => $self->{'users'},
		HTGROUPS => $self->{'groups'}
	};

	0;
}

1;
