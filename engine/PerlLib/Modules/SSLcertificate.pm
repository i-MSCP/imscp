=head1 NAME

 Modules::SSLcertificate - i-MSCP SSLcertificate module

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by internet Multi Server Control Panel
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

=head1 DESCRIPTION

 i-MSCP SSLcertificate module.

=head1 PUBLIC METHODS

=over 4

=item getType()

 Get module type

 Return string Module type

=cut

sub getType
{
	'SSLcertificate';
}

=item process($certificateId)

 Process module

 Param int $certificateId SSL certificate unique identifier
 Return int 0 on success, other on failure

=cut

sub process
{
	my ($self, $certificateId) = @_;

	my $rs = $self->_loadData($certificateId);
	return $rs if $rs;

	my @sql;

	if($self->{'status'} ~~ ['toadd', 'tochange']) {
		$rs = $self->add();

		@sql = (
			'UPDATE ssl_certs SET status = ? WHERE cert_id = ?',
			($rs ? scalar getMessageByType('error') || 'Unknown error' : 'ok'), $certificateId
		);
	} elsif($self->{'status'} eq 'todelete') {
		$rs = $self->delete();

		if($rs) {
			@sql = (
				'UPDATE ssl_certs SET status = ? WHERE cert_id = ?', scalar getMessageByType('error'), $certificateId
			);
		} else {
			@sql = ('DELETE FROM ssl_certs WHERE cert_id = ?', $certificateId);
		}
	}

	my $rdata = iMSCP::Database->factory()->doQuery('dummy', @sql);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	$rs;
}

=item add()

 Add SSL certifcate

 Return int 0 on success, other on failure

=cut

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
	my $caBundleContainer;
	if($self->{'ca_bundle'}) {
		$caBundleContainer = File::Temp->new();
		print $caBundleContainer $self->{'ca_bundle'};
	}

	# Create OpenSSL object
	my $openSSL = iMSCP::OpenSSL->new(
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

=item delete()

 Delete SSL certificate

 Return int 0 on success, other on failyre

=cut

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

=item _init()

 Initialize instance

 Return Modules::SSLcertificate

=cut

sub _init
{
	my $self = $_[0];

	$self->{'certsDir'} = "$main::imscpConfig{'GUI_ROOT_DIR'}/data/certs";

	my $rs = iMSCP::Dir->new( dirname => $self->{'certsDir'} )->make(
		{ 'mode' => 0750, 'user' => $main::imscpConfig{'ROOT_USER'}, 'group' => $main::imscpConfig{'ROOT_GROUP'} }
	);
	fatal('Unable to create SSL certificate directory') if $rs;

	$self;
}

=item _loadData($certificateId)

 Load data

 Param int $certificateId SSL certificate unique identifier
 Return int 0 on success, other on failure

=cut

sub _loadData
{
	my ($self, $certificateId) = @_;

	my $certData = iMSCP::Database->factory()->doQuery(
		'cert_id', 'SELECT * FROM ssl_certs WHERE cert_id = ?', $certificateId
	);
	unless(ref $certData eq 'HASH') {
		error($certData);
		return 1;
	}

	unless(exists $certData->{$certificateId}) {
		error("SSL certificate record with ID $certificateId has not been found in database");
		return 1;
	}

	%{$self} = (%{$self}, %{$certData->{$certificateId}});

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
				subdomain_alias_id = ?
		";
	}

	my $rdata = iMSCP::Database->factory()->doQuery('domain_id', $sql, $self->{'domain_id'});
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	unless(exists $rdata->{$self->{'domain_id'}}) {
		error("SSL certificate with ID $certificateId has not been found or is in an inconsistent state");
    	return 1;
	}

	$self->{'domain_name'} = $rdata->{$self->{'domain_id'}}->{'domain_name'};

	0;
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
