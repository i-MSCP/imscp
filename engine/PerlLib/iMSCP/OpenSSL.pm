=head1 NAME

iMSCP::OpenSSL - i-MSCP OpenSSL library

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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

package iMSCP::OpenSSL;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::File;
use iMSCP::Execute;
use iMSCP::TemplateParser;
use File::Temp;
use parent 'Common::Object';

=head1 DESCRIPTION

 Library allowing to check and import SSL certificates in single container (PEM).

=head1 PUBLIC METHODS

=over 4

=item validatePrivateKey()

 Validate private key

 Return int 0 on success, other on failure

=cut

sub validatePrivateKey
{
	my $self = shift;

	if ($self->{'private_key_container_path'} eq '') {
		error('Path to SSL private key container file is not set');
		return 1;
	}

	unless(-f $self->{'private_key_container_path'}) {
		error(sprintf("SSL private key container %s doesn't exists", $self->{'private_key_container_path'}));
		return 1;
	}

	my $passphraseFile;
	if($self->{'private_key_passphrase'} ne '') {
		# Create temporary file for private key passphrase
		$passphraseFile = File::Temp->new();
		# Write SSL private key passphrase into temporary file, which is only readable by root
		print $passphraseFile $self->{'private_key_passphrase'};
	}

	my @cmd = (
		'openssl rsa', '-in', escapeShell($self->{'private_key_container_path'}), '-noout',
		$passphraseFile ? ('-passin', escapeShell("file:$passphraseFile")) : ''
	);

	my $rs = execute("@cmd", \my $stdout, \my $stderr);
	debug($stdout) if $stdout;
	debug($stderr) if $stderr;
	$rs;
}

=item validateCertificate()

 Validate certificate

 If a CA Bundle (intermediate certificate(s)) is set, the whole certificate chain will be checked

 Return int 0 on success, other on failure

=cut

sub validateCertificate
{
	my $self = shift;

	if ($self->{'certificate_container_path'} eq '') {
		error('Path to SSL certificate container file is not set');
		return 1;
	}

	unless(-f $self->{'certificate_container_path'}) {
		error(sprintf("SSL certificate container %s doesn't exists", $self->{'certificate_container_path'}));
		return 1;
	}

	my $caBundle = 0;
	if ($self->{'ca_bundle_container_path'} ne '' ) {
		if (-f $self->{'ca_bundle_container_path'}) {
			$caBundle = 1;
		} else {
			error(sprintf("SSL CA Bundle container %s doesn't exists", $self->{'ca_bundle_container_path'}));
			return 1;
		}
	}

	my @cmd = (
		'openssl verify', $caBundle ? ('-CAfile', escapeShell($self->{'ca_bundle_container_path'})) : '',
		'-purpose sslserver', escapeShell($self->{'certificate_container_path'})
	);

	my $rs = execute("@cmd", \my $stdout, \my $stderr);
	debug($stdout) if $stdout;
	debug($stderr) if $stderr;
	return 1 if $rs || $stderr;

	if ($stdout !~ /$self->{'certificate_container_path'}:.*OK/ms ){
		debug(sprintf('SSL certificate %s is not valid', $self->{'certificate_container_path'}));
		return 1;
	}

	0;
}

=item validateCertificateChain()

 Validate certificate chain

 Return int 0 on success, other on failure

=cut

sub validateCertificateChain
{
	my $self = shift;

	my $rs = $self->validatePrivateKey();
	$rs ||= $self->validateCertificate();
}

=item importPrivateKey()

 Import private key in certificate chain container

 Return int 0 on success, other on failure

=cut

sub importPrivateKey
{
	my $self = shift;

	my $passphraseFile;
	if($self->{'private_key_passphrase'} ne '') {
		# Create temporary file for private key passphrase
		$passphraseFile = File::Temp->new();
		# Write SSL private key passphrase into temporary file, which is only readable by root
		print $passphraseFile $self->{'private_key_passphrase'};
	}

	my @cmd = (
		'openssl rsa', '-in', escapeShell($self->{'private_key_container_path'}),
		'-out', escapeShell("$self->{'certificate_chains_storage_dir'}/$self->{'certificate_chain_name'}.pem"),
		$passphraseFile ? ('-passin', escapeShell("file:$passphraseFile")) : ''
	);

	my $rs = execute("@cmd", \my $stdout, \my $stderr);
	debug($stdout) if $stdout;
	error('Could not import SSL private key' . ($stderr ? ": $stderr" : '')) if $rs;
	$rs;
}

=item importCertificate()

 Import certificate in certificate chain container

 Return int 0 on success, other on failure

=cut

sub importCertificate
{
	my $self = shift;

	my $file = iMSCP::File->new( filename => $self->{'certificate_container_path'} );
	my $certificate = $file->get();
	unless(defined $certificate) {
		error(sprintf('Could not read %s file', $self->{'certificate_container_path'}));
		return 1;
	}

	$certificate =~ s/^(?:\015?\012)+|(?:\015?\012)+$//g;

	my $rs = $file->set("$certificate\n");
	$rs ||= $file->save();
	return $rs if $rs;

	my @cmd = (
		'cat', escapeShell($self->{'certificate_container_path'}),
		'>>', escapeShell("$self->{'certificate_chains_storage_dir'}/$self->{'certificate_chain_name'}.pem")
	);

	$rs = execute("@cmd", \my $stdout, \my $stderr);
	debug($stdout) if $stdout;
	debug($stderr) if $stderr && !$rs;
	error('Could not import SSL certificate' . ($stderr ? ": $stderr" : '')) if $rs;
	$rs;
}

=item importCaBundle()

 Import the CA Bundle in certificate chain container if any

 Return int 0 on success, other on failure

=cut

sub ImportCaBundle
{
	my $self = shift;

	return 0 if $self->{'ca_bundle_container_path'} eq '';

	my $file = iMSCP::File->new( filename => $self->{'ca_bundle_container_path'} );
	my $caBundle = $file->get();
	unless(defined $caBundle) {
		error(sprintf('Could not read %s file', $self->{'ca_bundle_container_path'}));
		return 1;
	}

	$caBundle =~ s/^(?:\015?\012)+|(?:\015?\012)+$//g;

	my $rs = $file->set("$caBundle\n");
	$rs ||= $file->save();
	return $rs if $rs;

	my @cmd = (
		'cat', escapeShell($self->{'ca_bundle_container_path'}),
		'>>', escapeShell("$self->{'certificate_chains_storage_dir'}/$self->{'certificate_chain_name'}.pem")
	);

	$rs = execute("@cmd", \my $stdout, \my $stderr);
	debug($stdout) if $stdout;
	warning($stderr) if $stderr && !$rs;
	error('Could not import CA Bundle' . ($stderr ? ": $stderr" : '')) if $rs;
	$rs;
}

=items createSelfSignedCertificate(\%data)

 Generate a self-signed SSL certificate

 Param hash \%data Certificate data (common_name, email, wildcard = false)
 Param bool $wildcardSSL OPTIONAL Does a wildcard SSL certificate must be generated (default FALSE)
 Return int 0 on success, other on failure

=cut

sub createSelfSignedCertificate
{
	my ($self, $data) = @_;

	ref $data eq 'HASH' or die('Wrong $data parameter. Hash expected');
	$data->{'common_name'} or die('Missing common_name parameter');
	$data->{'email'} or die('Missing email parameter');

	my $openSSLConffileTpl = "$main::imscpConfig{'CONF_DIR'}/openssl/openssl.cnf.tpl";
	my $commonName = $data->{'wildcard'} ? '*.' . $data->{'common_name'} : $data->{'common_name'};

	# Load openssl configuration template file for self-signed SSL certificates
	my $openSSLConffileTplContent = iMSCP::File->new( filename => $openSSLConffileTpl )->get();
	unless(defined $openSSLConffileTplContent) {
		error(sprintf('Could not load %s openssl configuration template file', $openSSLConffileTpl));
		return 1;
	}

	my $openSSLConffile = File::Temp->new();
	# Write openssl configuration file into temporary file
	print $openSSLConffile process({
		'COMMON_NAME' => $commonName,
		'EMAIL_ADDRESS' => $data->{'email'},
		'ALT_NAMES' => $data->{'wildcard'} ? "DNS.1 = $commonName\n" : "DNS.1 = $commonName\nDNS.2 = www.$commonName\n"
	}, $openSSLConffileTplContent);

	my @cmd = (
		'openssl req -x509 -nodes -days 365', '-config', escapeShell($openSSLConffile), '-newkey rsa',
		'-keyout',  escapeShell("$self->{'certificate_chains_storage_dir'}/$self->{'certificate_chain_name'}.pem"),
		'-out', escapeShell("$self->{'certificate_chains_storage_dir'}/$self->{'certificate_chain_name'}.pem")
	);

	my $rs = execute("@cmd", \my $stdout, \my $stderr);
	debug($stdout) if $stdout;
	debug($stderr) if $stderr && !$rs;
	error('Could not to generate self-signed certificate' . ($stderr ? ": $stderr" : '')) if $rs;
	$rs
}

=item createCertificateChain()

 Create certificate chain (import private key, certificate and CA Bundle)

 Return int 0 on success, other on failure

=cut

sub createCertificateChain
{
	my $self = shift;

	my $rs = $self->importPrivateKey();
	$rs ||= $self->importCertificate();
	$rs ||= $self->ImportCaBundle();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return iMSCP::OpenSSL

=cut

sub _init
{
	my $self = shift;

	# Full path to the certificate chains storage directory
	$self->{'certificate_chains_storage_dir'} = '' unless $self->{'certificate_chains_storage_dir'};
	# Certificate chain name
	$self->{'certificate_chain_name'} = '' unless $self->{'certificate_chain_name'};
	# Full path to the private key container
	$self->{'private_key_container_path'} = '' unless $self->{'private_key_container_path'};
	# Private key passphrase if any
	$self->{'private_key_passphrase'} = '' unless $self->{'private_key_passphrase'};
	# Full path to the SSL certificate container
	$self->{'certificate_container_path'} = '' unless $self->{'certificate_container_path'};
	# Full path to the CA Bundle container (Container which contain one or many intermediate certificates)
	$self->{'ca_bundle_container_path'} = '' unless $self->{'ca_bundle_container_path'};
	$self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
