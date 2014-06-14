#!/usr/bin/perl

=head1 NAME

iMSCP::OpenSSL - i-MSCP OpenSSL library

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
#
# @category    i-MSCP
# @copyright   2010-2014 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::OpenSSL;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::File;
use iMSCP::Execute;
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
	my $self = $_[0];

	if ($self->{'private_key_container_path'} eq '') {
		error('Path to SSL private key container file is not set');
		return 1;
	} elsif(! -f $self->{'private_key_container_path'}) {
		error("SSL private key container $self->{'private_key_container_path'} doesn't exists");
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
		"$self->{'openssl_path'} rsa",
		'-in', escapeShell($self->{'private_key_container_path'}),
		'-noout',
		'-passin', ($passphraseFile)  ? escapeShell("file:$passphraseFile") : 'pass:dummypass'
	);

	my ($stdout, $stderr);
	my $rs = execute("@cmd", \$stdout, \$stderr);
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
	my $self = $_[0];

	if ($self->{'certificate_container_path'} eq '') {
		error('Path to SSL certificate container file is not set');
		return 1;
	} elsif(! -f $self->{'certificate_container_path'}) {
		error("SSL certificate container $self->{'certificate_container_path'} doesn't exists");
		return 1;
	}

	my $caBundle = 0;

	if ($self->{'ca_bundle_container_path'} ne '' ) {
	    if (-f $self->{'ca_bundle_container_path'}) {
			$caBundle = 1;
		} else {
			error("SSL CA Bundle container $self->{'ca_bundle_container_path'} doesn't exists");
			return 1;
		}
	}

	my @cmd = (
		"$self->{'openssl_path'} verify",
		($caBundle) ? ('-CAfile', escapeShell($self->{'ca_bundle_container_path'})) : '',
		escapeShell($self->{'certificate_container_path'})
	);

	my ($stdout, $stderr);
	my $rs = execute("@cmd", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	debug($stderr) if $stderr;
	return 1 if $rs || $stderr;

	if ($stdout !~ /$self->{'certificate_container_path'}:.*OK/ms ){
		debug("SSL certificate $self->{'certificate_container_path'} is not valid");
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
	my $self = $_[0];

	my $rs = $self->validatePrivateKey();
	$rs ||= $self->validateCertificate();
}

=item importPrivateKey()

 Import private key in certificate chain container

 Return int 0 on success, other on failure

=cut

sub importPrivateKey
{
	my $self = $_[0];

	my $passphraseFile;

	if($self->{'private_key_passphrase'} ne '') {
		# Create temporary file for private key passphrase
		$passphraseFile = File::Temp->new();

		# Write SSL private key passphrase into temporary file, which is only readable by root
		print $passphraseFile $self->{'private_key_passphrase'};
	}

	my @cmd = (
		"$self->{'openssl_path'} rsa",
		'-in', escapeShell($self->{'private_key_container_path'}),
		'-out', escapeShell("$self->{'certificate_chains_storage_dir'}/$self->{'certificate_chain_name'}.pem"),
		'-passin', ($passphraseFile)  ? escapeShell("file:$passphraseFile") : 'pass:dummypass'
	);

	my ($stdout, $stderr);
	my $rs = execute("@cmd", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error("Unable to import SSL private key" . (($stderr) ? ": $stderr" : '')) if $rs;

	$rs;
}

=item importCertificate()

 Import certificate in certificate chain container

 Return int 0 on success, other on failure

=cut

sub importCertificate
{
	my $self = $_[0];

	my $file = iMSCP::File->new('filename' => $self->{'certificate_container_path'});

	my $certificate = $file->get();
	unless(defined $certificate) {
		error("Unable to read $self->{'certificate_container_path'}");
		return 1;
	}

	$certificate =~ s/^(?:\015?\012)+|(?:\015?\012)+$//g;

	my $rs = $file->set("$certificate\n");
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	my @cmd = (
		$main::imscpConfig{'CMD_CAT'},
		escapeShell($self->{'certificate_container_path'}),
		'>>', escapeShell("$self->{'certificate_chains_storage_dir'}/$self->{'certificate_chain_name'}.pem")
	);

	my ($stdout, $stderr);
	$rs = execute("@cmd", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	warning($stderr) if $stderr && ! $rs;
	error("Unable to import SSL certificate" . (($stderr) ? ": $stderr" : '')) if $rs;

	$rs;
}

=item importCaBundle()

 Import the CA Bundle in certificate chain container

 Return int 0 on success, other on failure

=cut

sub ImportCaBundle
{
	my $self = $_[0];

	if($self->{'ca_bundle_container_path'} ne '') {
		my $file = iMSCP::File->new('filename' => $self->{'ca_bundle_container_path'});

		my $caBundle = $file->get();
		unless(defined $caBundle) {
			error("Unable to read $self->{'ca_bundle_container_path'}");
			return 1;
		}

		$caBundle =~ s/^(?:\015?\012)+|(?:\015?\012)+$//g;

		my $rs = $file->set("$caBundle\n");
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		my @cmd = (
			$main::imscpConfig{'CMD_CAT'},
			escapeShell($self->{'ca_bundle_container_path'}),
			'>>', escapeShell("$self->{'certificate_chains_storage_dir'}/$self->{'certificate_chain_name'}.pem")
		);

		my ($stdout, $stderr);
		$rs = execute("@cmd", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		warning($stderr) if $stderr && ! $rs;
		error("Unable to import CA Bundle" . (($stderr) ? ": $stderr" : '')) if $rs;
		return $rs if $rs;
	}

	0;
}

=items createSelfSignedCertificate($commonName, $wildcardSSL = false)

 Generate a self-signed SSL certificate

 Param string $commonName Common Name
 Param bool $wildcardSSL OPTIONAL Does a wildcard SSL certificate must be generated (default FALSE)
 Return int 0 on success, other on failure

=cut

sub createSelfSignedCertificate
{
	my ($self, $commonName, $wildcardSSL) = @_;

	my $commonName = ($wildcardSSL) ? '*.' . $commonName : $commonName;

	my @cmd = (
		"$self->{'openssl_path'} req -x509 -nodes -days 365 ",
		'-subj', escapeShell("/C=/ST=/L=/CN=$commonName"),
		'-newkey rsa:2048',
		'-keyout',  escapeShell("$self->{'certificate_chains_storage_dir'}/$self->{'certificate_chain_name'}.pem"),
		'-out', escapeShell("$self->{'certificate_chains_storage_dir'}/$self->{'certificate_chain_name'}.pem")
	);

	my ($stdout, $stderr);
	my $rs = execute("@cmd", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	debug($stderr) if $stderr && ! $rs;
	error('Unable to generate self-signed certificate' . (($stderr) ? ": $stderr" : '')) if $rs;

	$rs
}

=item createCertificateChain()

 Create certificate chain (import private key, certificate and CA Bundle)

 Return int 0 on success, other on failure

=cut

sub createCertificateChain
{
	my $self = $_[0];

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
	my $self = $_[0];

	# Path to the openssl binary
	$self->{'openssl_path'} = '' unless defined $self->{'openssl_path'};

	# Full path to the certificate chains storage directory
	$self->{'certificate_chains_storage_dir'} = '' unless defined $self->{'certificate_chains_storage_dir'};

	# Certificate chain name
	$self->{'certificate_chain_name'} = '' unless defined $self->{'certificate_chain_name'};

	# Full path to the private key container
	$self->{'private_key_container_path'} = '' unless defined $self->{'private_key_container_path'};

	# Private key passphrase if any
	$self->{'private_key_passphrase'} = '' unless defined $self->{'private_key_passphrase'};

	# Full path to the SSL certificate container
	$self->{'certificate_container_path'} = '' unless defined $self->{'certificate_container_path'};;

	# Full path to the CA Bundle container (Container which contain one or many intermediate certificates)
	$self->{'ca_bundle_container_path'} = '' unless defined $self->{'ca_bundle_container_path'};;

	$self;
}

=back

=head1 AUTHORS

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
