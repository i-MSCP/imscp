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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
#
# @category    i-MSCP
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
# @author      Daniel Andreca <sci2tech@gmail.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Modules::Htgroup;

use strict;
use warnings;

use iMSCP::Debug;
use parent 'Modules::Abstract';

sub _init
{
	my $self = shift;

	$self->{'type'} = 'Htgroup';

	$self;
}

sub loadData
{
	my $self = shift;

	my $sql = "
		SELECT
			`t2`.`id`, `t2`.`ugroup`, `t2`.`status`, `t2`.`users`, `t3`.`domain_name`, `t3`.`domain_admin_id`,
			`t3`.`web_folder_protection`
		FROM
			(
				SELECT * from `htaccess_groups`,
				(
					SELECT IFNULL(
					(
						SELECT
							group_concat(`uname` SEPARATOR ' ')
						FROM
							`htaccess_users`
						WHERE
							`id` regexp (
								CONCAT(
									'^(', (SELECT REPLACE((SELECT `members` FROM `htaccess_groups` WHERE `id` = ?), ',', '|')), ')\$'
								)
							) GROUP BY
								`dmn_id`
					), '') AS `users`
				) AS t1
			) AS t2
		LEFT JOIN
			`domain` AS `t3` ON (`t2`.`dmn_id` = `t3`.`domain_id`)
		WHERE
			`id` = ?
	";

	my $rdata = iMSCP::Database->factory()->doQuery('id', $sql, $self->{'htgroupId'}, $self->{'htgroupId'});

	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	unless(exists $rdata->{$self->{'htgroupId'}}) {
		error("No group in table htaccess_groups has id = $self->{'htgroupId'}");
		return 1;
	}

	unless($rdata->{$self->{'htgroupId'}}->{'domain_name'}) {
		require Data::Dumper;
		Data::Dumper->import();
		local $Data::Dumper::Terse = 1;
		error('Orphan entry: ' . Dumper($rdata->{$self->{'htgroupId'}}));

		my @sql = (
			"UPDATE `htaccess_groups` SET `status` = ? WHERE `id` = ?",
			'Orphan entry: ' . Dumper($rdata->{$self->{'htgroupId'}}),
			$self->{'htgroupId'}
		);

		my $rdata = iMSCP::Database->factory()->doQuery('update', @sql);
		return 1;
	}

	$self->{$_} = $rdata->{$self->{'htgroupId'}}->{$_} for keys %{$rdata->{$self->{'htgroupId'}}};

	0;
}

sub process
{
	my $self = shift;

	$self->{'htgroupId'} = shift;

	my $rs = $self->loadData();
	return $rs if $rs;

	my @sql;

	if($self->{'status'} =~ /^toadd|tochange$/) {
		$rs = $self->add();
		@sql = (
			"UPDATE `htaccess_groups` SET `status` = ? WHERE `id` = ?",
			($rs ? scalar getMessageByType('error') : 'ok'),
			$self->{'id'}
		);
	} elsif($self->{'status'} eq 'todelete') {
		$rs = $self->delete();
		if($rs) {
			@sql = (
				"UPDATE `htaccess_groups` SET `status` = ? WHERE `id` = ?",
				scalar getMessageByType('error'),
				$self->{'id'}
			);
		} else {
			@sql = ("DELETE FROM `htaccess_groups` WHERE `id` = ?", $self->{'id'});
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
	my $self = shift;

	my $groupName =
	my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} .
		($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'domain_admin_id'});

	$self->{'httpd'} = {
		USER => $userName,
		GROUP => $groupName,
		WEB_DIR => "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'domain_name'}",
		HTGROUP_NAME => $self->{'ugroup'},
		HTGROUP_USERS => $self->{'users'},
		HTGROUP_DMN => $self->{'domain_name'},
		WEB_FOLDER_PROTECTION => $self->{'web_folder_protection'}
	};

	0;
}

1;
