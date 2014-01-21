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
# @author      Daniel Andreca <sci2tech@gmail.com>
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
use parent 'Common::SingletonClass';


=head1 DESCRIPTION

 Library allowing to check and export SSL certificates

=head1 PUBLIC METHODS

=over 4

=item

 Check the SSL private key

 Return int 0 on success, other on failure

=cut

sub ssl_check_key
{
	my $self = $_[0];

	if ($self->{'key_path'} eq '') {
		error('Path to SSL private key container file is not set.');
		return 1;
	} elsif(! -f $self->{'key_path'}) {
		error("File $self->{'key_path'} doesn't exist.");
		return -1;
	}

	my $keyPassword = (($self->{'key_pass'} ne '') ? $self->{'key_pass'} : 'dummypass') . "\n";
	my $keyPaswordFile = File::Temp->new();

	# Write key password into temporary file, which is only readable by root
	print $keyPaswordFile $keyPassword;

	my ($stdout, $stderr);
	my $rs = execute(
		"$self->{'openssl_path'} rsa -in $self->{'key_path'} -noout -passin file:$keyPaswordFile", \$stdout, \$stderr
	);
	debug($stdout) if $stdout;
	warning($stderr) if $stderr && ! $rs;
	error("Invalid private key or password" . ($stderr ? ": $stderr" : '') . '.') if $rs;
	return $rs if $rs;

	0;
}

=item

 Check CA Bundle (intermediate(s) certificats)

 Return int 0 on success, other on failure

=cut

sub ssl_check_intermediate_cert
{
	my $self = $_[0];

	if ($self->{'intermediate_cert_path'} ne '' && ! -f $self->{'intermediate_cert_path'}) {
		error("Intermediate SSL certificate $self->{'intermediate_cert_path'} doesn't exist.");
		return 1;
	}

	0;
}

=item

 Check SSL certificat

 Note: If a CA Bundle (intermediate(s) certificates) is set, the whole chain will be checked

 Return int 0 on success, other on failure

=cut

sub ssl_check_cert
{
	my $self = $_[0];

	if ($self->{'cert_path'} eq '') {
		error('Path to SSL certificat container file is not set.');
		return 1;
	} elsif(! -f $self->{'cert_path'}) {
		error("File $self->{'cert_path'} doesn't exist.");
		return 1;
	}

	my $caBundle = '';

	if ($self->{'intermediate_cert_path'} ne '' ) {
		$caBundle = "-CAfile $self->{'intermediate_cert_path'}";
	}

	my $cmd = "$self->{'openssl_path'} verify $caBundle $self->{'cert_path'}";

	my ($stdout, $stderr);
	my $rs = execute($cmd, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr;
	return 1 if $rs || $stderr;

	if ($stdout !~ /$self->{'cert_path'}:.*OK/ms ){
		error("SSL certificate $self->{'cert_path'} is not valid.");
		return 1;
	}

	0;
}

=item

 Check all SSL objects (Private key, CA bundle, certificate)

 Return int 0 on success, other on failure

=cut

sub ssl_check_all
{
	my $self = $_[0];

	my $rs = $self->ssl_check_key();
	return $rs if $rs;

	$rs = $self->ssl_check_intermediate_cert();
	return $rs if $rs;

	$rs = $self->ssl_check_cert();
	return $rs if $rs;

	0;
}

=item

 Export SSL private key into a new container (.pem file)

 Return int 0 on success, other on failure

=cut

sub ssl_export_key
{
	my $self = $_[0];

	my $keyPassword = (($self->{'key_pass'} ne '') ? $self->{'key_pass'} : 'dummypass') . "\n";
	my $keyPaswordFile = File::Temp->new();

	# Write key password into temporary file, which is only readable by root
	print $keyPaswordFile $keyPassword;

	my $cmd =
		"$self->{openssl_path} rsa -in $self->{'key_path'} -out $self->{'new_cert_path'}/$self->{'new_cert_name'}.pem" .
		" -passin file:$keyPaswordFile";

	my ($stdout, $stderr);
	my $rs = execute($cmd, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error("Unable to export SSL private key" . ($stderr ? ": $stderr." : '.')) if $rs;
	return $rs if $rs;

	0;
}

=item ssl_export_cert()

 Export SSL certificat

 Return int 0 on success, other on failure

=cut

sub ssl_export_cert
{
	my $self = $_[0];

	my $cmd =
		"$self->{'openssl_path'} x509 -in $self->{'cert_path'} " .
		"-outform PEM >> $self->{'new_cert_path'}/$self->{'new_cert_name'}.pem";

	my ($stdout, $stderr);
	my $rs = execute($cmd, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	warning($stderr) if $stderr && ! $rs;
	error("Unable to export SSL certificate" . ($stderr ? ": $stderr." : '.')) if $rs;
	return $rs if $rs;

	0;
}

=item ssl_export_intermediate_cert()

 Export the CA Bundle

 Return 0 on success, other on failure

=cut

sub ssl_export_intermediate_cert
{
	my $self = $_[0];

	return 0 if $self->{'intermediate_cert_path'} eq '';

	my $cmd =
		"$self->{'openssl_path'} x509 " .
		"-in $self->{'intermediate_cert_path'} " .
		"-outform PEM >> $self->{'new_cert_path'}/$self->{'new_cert_name'}.pem 2>/dev/null";

	my ($stdout, $stderr);
	my $rs = execute($cmd, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	warning($stderr) if $stderr && ! $rs;
	error("Unable to save intermediate certificate" . ($stderr ? ": $stderr." : '.')) if $rs;
	return $rs if $rs;

	0;
}

=items ssl_generate_selsigned_cert($wildcardSSL = TRUE)

 Generate a self-signed SSL certificate

 Return int 0 on success, other on failure

=cut

sub ssl_generate_selsigned_cert
{
	my $self = shift;
	my $wildcardSSL = shift || 1;

	my $commonName = ($wildcardSSL) ? '*.' .  $self->{'common_name'} : $self->{'common_name'};

	my $cmd =
		"$self->{'openssl_path'} req -x509 -nodes -days 365 " .
		"-subj '/C=/ST=/L=/CN=$commonName' " .
		"-newkey rsa:2048 " .
		"-keyout $self->{'new_cert_path'}/$self->{'new_cert_name'}.pem " .
		"-out $self->{'new_cert_path'}/$self->{'new_cert_name'}.pem";

	my ($stdout, $stderr);
	my $rs = execute($cmd, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	debug($stderr) if $stderr && ! $rs;
	error("Unable to generate self-signed certificate" . ($stderr ? ": $stderr." : '.')) if $rs;
	return $rs if($rs);

	0;
}

=item

 Export all SSL objects (Private key, CA Bundle, certificat) into one container (.pem file)

 Return int 0 on success, other on failure

=cut

sub ssl_export_all
{
	my $self = $_[0];

	my $rs = 0;

	if($self->{'cert_selfsigned'} eq 'yes') {
		$rs = $self->ssl_generate_selsigned_cert();
		return $rs if $rs;
	} else {
		$rs = $self->ssl_export_key();
		return $rs if $rs;

		$rs = $self->ssl_export_cert();
		return $rs if $rs;

		$rs = $self->ssl_export_intermediate_cert();
		return $rs if $rs;
	}

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance.

 Return iMSCP::OpenSSL

=cut

sub _init
{
	my $self = $_[0];

	# Should contain the path to the openssl binary
	$self->{'openssl_path'} = '';

	# Directory path into which the new certificat must be stored
	$self->{'new_cert_path'} = '';

	# Name of new certificat file (exluding extension)
	$self->{'new_cert_name'} = '';

	# SSL common name (apply to self-signed certificat only)
	$self->{'common_name'} = '';

	# Tells whether or not we intend to generate a self-signed SSL certificat (no|yes)
	$self->{'cert_selfsigned'} = 'no';

	# Full path to the SSL certificat container file
	$self->{'cert_path'} = '';

	# Full path to the CA Bundle container file (Container which contain one or many intermediate certificates)
	$self->{'intermediate_cert_path'} = '';

	# Full path to the private key container file
	$self->{'key_path'} = '';

	# Private key password if any
	$self->{'key_pass'} = '';

	$self;
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
