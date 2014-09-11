#!/usr/bin/perl

=head1 NAME

 Modules::Ips - i-MSCP Ips module

=cut

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

package Modules::Ips;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Database;
use parent 'Modules::Abstract';

=head1 DESCRIPTION

 i-MSCP IPs module.

=head1 PUBLIC METHODS

=over 4

=item getType()

 Get module type

 Return string Module type

=cut

sub getType
{
	'Ips';
}

=item process()

 Process module

 Return int 0 on success, other on failure

=cut

sub process
{
	my $self = $_[0];

	my $rs = $self->_loadData();
    return $rs if $rs;

	$self->add();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _loadData()

 Load data

 Return int 0 on success, other on failure

=cut

sub _loadData
{
	my $self = $_[0];

	my $db = iMSCP::Database->factory();

	my $rdata = $db->doQuery(
		'ip_number',
		"
			SELECT
				domain_ip_id AS ip_id, ip_number
			FROM
				domain
			INNER JOIN
				server_ips ON (domain.domain_ip_id = server_ips.ip_id)
			WHERE
				domain_status != 'todelete'
			UNION
			SELECT
				alias_ip_id AS ip_id, ip_number
			FROM
				domain_aliasses
			INNER JOIN
				server_ips ON (domain_aliasses.alias_ip_id = server_ips.ip_id)
			WHERE
				alias_status NOT IN ('todelete', 'ordered')
		"
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	@{$self->{'ipaddrs'}} = keys %{$rdata};

	$rdata = $db->doQuery(
		'ip_number',
		"
			SELECT
				ip_number
			FROM
				ssl_certs
			INNER JOIN
				domain ON (ssl_certs.domain_id = domain.domain_id)
			INNER JOIN
				server_ips ON (domain.domain_ip_id = server_ips.ip_id)
			WHERE
				ssl_certs.domain_type = 'dmn'

			UNION

			SELECT
				ip_number
			FROM
				ssl_certs
			INNER JOIN
				domain_aliasses ON (ssl_certs.domain_id = domain_aliasses.alias_id)
			INNER JOIN
				server_ips ON (domain_aliasses.alias_ip_id = server_ips.ip_id)
			WHERE
				ssl_certs.domain_type = 'als'

			UNION

			SELECT
				ip_number
			FROM
				ssl_certs
			INNER JOIN
				subdomain_alias ON (ssl_certs.domain_id = subdomain_alias.subdomain_alias_id)
			INNER JOIN
				domain_aliasses ON (subdomain_alias.alias_id = domain_aliasses.alias_id)
			INNER JOIN
				server_ips ON (domain_aliasses.alias_ip_id = server_ips.ip_id)
			WHERE
				ssl_certs.domain_type = 'alssub'

			UNION

			SELECT
				ip_number
			FROM
				ssl_certs
			INNER JOIN
				subdomain ON (ssl_certs.domain_id = subdomain.subdomain_id)
			INNER JOIN
				domain ON (subdomain.domain_id = domain.domain_id)
			INNER JOIN
				server_ips ON (domain.domain_ip_id = server_ips.ip_id)
			WHERE
				ssl_certs.domain_type = 'sub'
		"
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	@{$self->{'ssl_ipaddrs'}} = keys %{$rdata};

	0;
}

=item _getHttpdData($action)

 Data provider method for Httpd servers

 Param string $action Action
 Return hash Hash containing module data

=cut

sub _getHttpdData
{
	my ($self, $action) = @_;

	unless($self->{'httpd'}) {
		$self->{'httpd'} = {
			IPS => $self->{'ipaddrs'},
			SSL_IPS => $self->{'ssl_ipaddrs'}
		};
	}

	%{$self->{'httpd'}};
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
