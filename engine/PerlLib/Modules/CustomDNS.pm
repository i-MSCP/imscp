=head1 NAME

 Modules::CustomDNS - i-MSCP CustomDNS module

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2015-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Modules::CustomDNS;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Database;
use parent 'Modules::Abstract';

=head1 DESCRIPTION

 i-MSCP CustomDNS module.

=head1 PUBLIC METHODS

=over 4

=item getType()

 Get module type

 Return string Module type

=cut

sub getType
{
	'CustomDNS';
}

=item process($domainId)

 Process module

 Note: Even if a DNS resource record is invalid, we always return 0 (success).
 It is the responsability of customers to fix their DNS resource records.

 Param string $domainId Domain unique identifier (domain type + domain id)
 Return int 0 on success, other on failure

=cut

sub process
{
	my ($self, $domainId) = @_;

	(my $domainType, $domainId) = split '_', $domainId;

	unless ($domainType && $domainId) {
		error('Bad input data...');
		return 1;
	}

	my $condition = $domainType eq 'domain' ? "domain_id = $domainId AND alias_id = 0" : "alias_id = $domainId";

	my $rs = $self->_loadData($domainType, $domainId);
	return $rs if $rs;

	$rs = $self->add();

	if($rs) {
		my $errorStr = getMessageByType('error');
		my $qrs = $self->{'db'}->doQuery(
			'u', "UPDATE domain_dns SET domain_dns_status = ? WHERE $condition",
				$errorStr ? $errorStr : 'Invalid DNS resource record'
		);
		unless(ref $qrs eq 'HASH') {
			error($qrs);
			return 1;
		}
	} else {
		my $dbh = $self->{'db'}->getRawDb();

		$self->{'db'}->startTransaction();

		eval {
			$dbh->do(
				"
					UPDATE domain_dns SET domain_dns_status = 'ok'
					WHERE $condition AND domain_dns_status NOT IN('todisable', 'todelete')
				"
			);

			$dbh->do(
				"
					UPDATE domain_dns SET domain_dns_status = 'disabled'
					WHERE $condition AND domain_dns_status = 'todisable'
				",
			);

			$dbh->do("DELETE FROM domain_dns WHERE $condition AND domain_dns_status = 'todelete'");
			$dbh->commit();
		};

		if($@) {
			$dbh->rollback();
			$self->{'db'}->endTransaction();
			error($@);
			return 1;
		}

		$self->{'db'}->endTransaction();
	}

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

item init()

 Initialize instance

 Return Modules::CustomDNS

=cut

sub _init
{
	my $self = shift;

	$self->{'db'} = iMSCP::Database->factory();
	$self->{'domain_name'} = undef;
	$self->{'dns_records'} = [];
	$self;
}

=item _loadData($domainType, $domainId)

 Load data

 Param string domainType Domain Type ( alias|domain )
 Param int $domainId Domain unique identifier
 Return int 0 on success, other on failure

=cut

sub _loadData
{
	my ($self, $domainType, $domainId) = @_;

	my $condition = $domainType eq 'domain' ? "t1.domain_id = $domainId AND t1.alias_id = 0" : "t1.alias_id = $domainId";

	$self->{'db'}->set('FETCH_MODE', 'arrayref');

	my $rows = $self->{'db'}->doQuery(
		undef,
		"
			SELECT t1.domain_dns, t1.domain_class, t1.domain_type, t1.domain_text, t1.domain_dns_status,
				IFNULL(t3.alias_name, t2.domain_name) AS domain_name, t4.ip_number
			FROM domain_dns AS t1
			LEFT JOIN domain AS t2 USING(domain_id)
			LEFT JOIN domain_aliasses AS t3 USING(alias_id)
			LEFT JOIN server_ips AS t4 ON (IFNULL(t3.alias_ip_id, t2.domain_ip_id) = t4.ip_id)
			WHERE $condition
		"
	);

	unless(ref $rows eq 'ARRAY') {
		error($rows);
		return 1;
	}

	unless(@{$rows} && defined($rows->[0]->[5])) {
		error(sprintf('Custom DNS records for %s with ID %s were not found or are orphaned', $domainType, $domainId));
		return 1;
	}

	$self->{'domain_name'} = $rows->[0]->[5];
	$self->{'domain_ip'} = $rows->[0]->[6];

	# Filter DNS records which must be disabled or deleted
	for my $record(@{$rows}) {
		push @{$self->{'dns_records'}}, [ (@{$record})[0..3] ] unless grep(
			$_ eq $record->[4], ( 'todisable', 'todelete' )
		);
	}

	0;
}

=item _getNamedData($action)

 Data provider method for named servers

 Param string $action Action
 Return hash Hash containing module data

=cut

sub _getNamedData
{
	my ($self, $action) = @_;

	return %{$self->{'named'}} if $self->{'named'};

	$self->{'named'} = {
		DOMAIN_NAME => $self->{'domain_name'},
		DOMAIN_IP => $self->{'domain_ip'},
		DNS_RECORDS => [ @{$self->{'dns_records'}} ]
	};
	%{$self->{'named'}};
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
