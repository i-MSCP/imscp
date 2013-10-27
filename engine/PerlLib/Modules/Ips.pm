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

package Modules::Ips;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Execute;
use parent 'Modules::Abstract';

sub _init
{
	my $self = shift;

	$self->{'type'} = 'Ips';

	$self;
}

sub process
{
	my $self = shift;

	my $sql = "
		SELECT
			`domain_ip_id` AS `ip_id`, `ip_number`
		FROM
			`domain`
		INNER JOIN
			`server_ips` ON (`domain`.`domain_ip_id` = `server_ips`.`ip_id`)
		WHERE
			`domain_status` != 'todelete'
		UNION
		SELECT
			`alias_ip_id` AS `ip_id`, `ip_number`
		FROM
			`domain_aliasses`
		INNER JOIN
			`server_ips` ON (`domain_aliasses`.`alias_ip_id` = `server_ips`.`ip_id`)
		WHERE
			`alias_status` NOT IN ('todelete', 'ordered')
	";
	my $rdata = iMSCP::Database->factory()->doQuery('ip_number', $sql);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	@{$self->{'IPs'}} = keys %{$rdata};

	$sql = "
		SELECT
			`ip_number`
		FROM
			`ssl_certs`
		LEFT JOIN
			`domain` ON (`ssl_certs`.`id` = `domain`.`domain_id`)
		LEFT JOIN
			`server_ips` ON (`domain`.`domain_ip_id` = `server_ips`.`ip_id`)
		WHERE
			`ssl_certs`.`type` = 'dmn'
		UNION
		SELECT
			`ip_number`
		FROM
			`ssl_certs`
		LEFT JOIN
			`domain_aliasses` ON (`ssl_certs`.`id` = `domain_aliasses`.`alias_id`)
		LEFT JOIN
			`server_ips` ON (`domain_aliasses`.`alias_ip_id` = `server_ips`.`ip_id`)
		WHERE
			`type` = 'als'
		UNION
		SELECT
			`ip_number`
		FROM
			`ssl_certs`
		LEFT JOIN
			`subdomain_alias` ON (`ssl_certs`.`id` = `subdomain_alias`.`subdomain_alias_id`)
		LEFT JOIN
			`domain_aliasses` ON (`subdomain_alias`.`alias_id` = `domain_aliasses`.`alias_id`)
		LEFT JOIN
			`server_ips` ON (`domain_aliasses`.`alias_ip_id` = `server_ips`.`ip_id`)
		WHERE
			`type` = 'alssub'
		UNION
		SELECT
			`ip_number`
		FROM
			`ssl_certs`
		LEFT JOIN
			`subdomain` ON (`ssl_certs`.`id` = `subdomain`.`subdomain_id`)
		LEFT JOIN
			`domain` ON (`subdomain`.`domain_id` = `domain`.`domain_id`)
		LEFT JOIN
			`server_ips` ON (`domain`.`domain_ip_id` = `server_ips`.`ip_id`)
		WHERE
			`type` = 'sub'
	";
	$rdata = iMSCP::Database->factory()->doQuery('ip_number', $sql);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	@{$self->{'sslIPs'}} = keys %{$rdata};

	$self->add();
}

sub buildHTTPDData
{
	my $self = shift;

	$self->{'httpd'} = {
		IPS => $self->{'IPs'},
		SSLIPS => $self->{'sslIPs'}
	};

	0;
}

1;
