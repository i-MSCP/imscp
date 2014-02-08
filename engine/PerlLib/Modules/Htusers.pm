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

package Modules::Htusers;

use strict;
use warnings;

use iMSCP::Debug;
use parent 'Modules::Abstract';

sub _init
{
	my $self = $_[0];

	$self->{'type'} = 'Htuser';

	$self;
}

sub loadData
{
	my $self = $_[0];

	my $sql = "
		SELECT
			`t1`.`uname`, `t1`.`upass`, `t1`.`status`, `t1`.`id`, `t2`.`domain_name`, `t2`.`domain_admin_id`,
			`t2`.`web_folder_protection`
		FROM
			`htaccess_users` AS `t1`
		INNER JOIN
			`domain` AS `t2` ON (`t1`.`dmn_id` = `t2`.`domain_id`)
		WHERE
			`t1`.`id` = ?
	";

	my $rdata = iMSCP::Database->factory()->doQuery('id', $sql, $self->{'htuserId'});
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	unless(exists $rdata->{$self->{'htuserId'}}) {
		error("Htuser record with ID '$self->{'htuserId'}' has not been found in database");
		return 1;
	}

	unless(exists $rdata->{$self->{'htuserId'}}->{'domain_name'}) {
		require Data::Dumper;
		Data::Dumper->import();

		local $Data::Dumper::Terse = 1;
		error('Orphan entry: ' . Dumper($rdata->{$self->{'htuserId'}}));

		my @sql = (
			"UPDATE `htaccess_users` SET `status` = ? WHERE `id` = ?",
			'Orphan entry: ' . Dumper($rdata->{$self->{'htuserId'}}),
			$self->{'htuserId'}
		);
		my $rdata = iMSCP::Database->factory()->doQuery('update', @sql);

		return 1;
	}

	%{$self} = (%{$self}, %{$rdata->{$self->{'htuserId'}}});

	0;
}

sub process
{
	my $self = $_[0];

	$self->{'htuserId'} = $_[1];

	my $rs = $self->loadData();
	return $rs if $rs;

	my @sql;

	if($self->{'status'} =~ /^toadd|tochange$/) {
		$rs = $self->add();

		@sql = (
			"UPDATE `htaccess_users` SET `status` = ? WHERE `id` = ?",
			($rs ? scalar getMessageByType('error') : 'ok'),
			$self->{'id'}
		);
	} elsif($self->{'status'} eq 'todelete') {
		$rs = $self->delete();

		if($rs) {
			@sql = (
				"UPDATE `htaccess_users` SET `status` = ? WHERE `id` = ?",
				scalar getMessageByType('error'),
				$self->{'id'}
			);
		} else {
			@sql = ("DELETE FROM `htaccess_users` WHERE `id` = ?", $self->{'id'});
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

	$self->{'httpd'} = {
		DOMAIN_ADMIN_ID => $self->{'domain_admin_id'},
		USER => $userName,
		GROUP => $groupName,
		WEB_DIR => "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'domain_name'}",
		HTUSER_NAME	=> $self->{'uname'},
		HTUSER_PASS	=> $self->{'upass'},
		HTUSER_DMN	=> $self->{'domain_name'},
		WEB_FOLDER_PROTECTION => $self->{'web_folder_protection'}
	};

	0;
}

1;
