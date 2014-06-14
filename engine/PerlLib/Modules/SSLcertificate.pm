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

package Modules::SSLcertificate;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Debug;
use iMSCP::File;
use iMSCP::Dir;
use iMSCP::OpenSSL;
use File::Temp;
use parent 'Modules::Abstract';

sub loadData
{
	my $self = $_[0];

	my $certData = iMSCP::Database->factory()->doQuery(
		'cert_id', 'SELECT * FROM ssl_certs WHERE cert_id = ?', $self->{'cert_id'}
	);
	unless(ref $certData eq 'HASH') {
		error($certData);
		return 1;
	}

	unless(exists $certData->{$self->{'cert_id'}}) {
		error("SSL certificate record with ID $self->{'cert_id'} has not been found in database");
		return 1;
	}

	%{$self} = (%{$self}, %{$certData->{$self->{'cert_id'}}});

	my $sql;

	if($self->{'domain_type'} eq 'dmn') {
		$sql = 'SELECT domain_name, domain_id FROM domain WHERE domain_id = ?';
	} elsif($self->{'domain_type'} eq 'als') {
		$sql = 'SELECT alias_name AS domain_name, alias_id AS domain_id FROM domain_aliasses WHERE alias_id = ?';
	} elsif($self->{'domain_type'} eq 'sub') {
		$sql = "
			SELECT
				CONCAT(subdomain_name, '.', domain_name) AS domain_name, subdomain_id AS domain_id
			FROM
				subdomain
			INNER JOIN
				domain USING(domain_id)
			WHERE
				subdomain_id = ?
		";
	} else {
		$sql = "
			SELECT
				CONCAT(subdomain_alias_name, '.', alias_name) AS domain_name, subdomain_alias_id AS domain_id
			FROM
				subdomain_alias
			INNER JOIN
				domain_aliasses USING(alias_id)
			WHERE
				`subdomain_alias_id` = ?
		";
	}

	my $rdata = iMSCP::Database->factory()->doQuery('domain_id', $sql, $self->{'domain_id'});
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	unless(exists $rdata->{$self->{'domain_id'}}) {
		# Delete orphaned SSL certificate
		#my $rdata = iMSCP::Database->factory()->doQuery(
		#	'dummy', 'DELETE FROM ssl_certs WHERE cert_id = ?', $self->{'cert_id'}
		#);

		warning("SSL certificate record with ID $self->{'cert_id'} is orphaned and therefore, has been removed.");
		return 5;
	}

	$self->{'domain_name'} = $rdata->{$self->{'domain_id'}}->{'domain_name'};

	0;
}

sub process
{
	my $self = $_[0];

	$self->{'cert_id'} = $_[1];

	my $rs = $self->loadData();
	return 0 if $rs == 5; # Orphaned SSL certificate has been removed
	return $rs if $rs;

	my @sql;

	if($self->{'status'} ~~ ['toadd', 'tochange']) {
		$rs = $self->add();

		@sql = (
			'UPDATE ssl_certs SET status = ? WHERE cert_id = ?',
			($rs ? scalar getMessageByType('error') : 'ok'), $self->{'cert_id'}
		);
	} elsif($self->{'status'} eq 'todelete') {
		$rs = $self->delete();

		if($rs) {
			@sql = (
				'UPDATE ssl_certs SET status = ? WHERE cert_id = ?',
				scalar getMessageByType('error'), $self->{'cert_id'}
			);
		} else {
			@sql = ('DELETE FROM ssl_certs WHERE cert_id = ?', $self->{'cert_id'});
		}
	}

	my $rdata = iMSCP::Database->factory()->doQuery('dummy', @sql);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	$rs;
}

sub add
{
	my $self = $_[0];

	# Private key
	my $privateKeyContainer = File::Temp->new();
	print $privateKeyContainer $self->{'private_key'};

	# Certificate
	my $certificateContainer = File::Temp->new();
	print $certificateContainer $self->{'certificate'};

	# CA Bundle (intermediate certificate(s))
	if($self->{'ca_bundle'}) {
		my $caBundleContainer = File::Temp->new();
		print $caBundleContainer $self->{'ca_bundle'};
	}

	# Create OpenSSL object
	my $openSSL = iMSCP::OpenSSL->new(
		'openssl_path' => $main::imscpConfig{'CMD_OPENSSL'},
		'certificate_chains_storage_dir' => $self->{'certsDir'},
		'certificate_chain_name' => $self->{'domain_name'},
		'private_key_container_path' => $privateKeyContainer,
		'certificate_container_path' => $certificateContainer,
		'ca_bundle_container_path' => (defined $caBundleContainer) ? $caBundleContainer : ''
	);

	# Check certificate chain
	my $rs = $openSSL->validateCertificateChain();
	return $rs if $rs;

	# Create certificate chain (private key, certificate and CA bundle)
	$openSSL->createCertificateChain();
}

sub delete
{
	my $self = $_[0];

	my $certFile = "$self->{'certsDir'}/$self->{'domain_name'}.pem";

	if(-f $certFile) {
	    iMSCP::File->new('filename' => $certFile)->delFile();
    } else {
	    0;
    }
}

sub _init
{
	my $self = $_[0];

	$self->{'type'} = 'SSLcertificate';
	$self->{'certsDir'} = "$main::imscpConfig{'GUI_ROOT_DIR'}/data/certs";

	my $rs = iMSCP::Dir->new('dirname' => $self->{'certsDir'})->make(
		{ 'mode' => 0750, 'user' => $main::imscpConfig{'ROOT_USER'}, 'group' => $main::imscpConfig{'ROOT_GROUP'} }
	);
	return $rs if $rs;

	$self;
}

1;
