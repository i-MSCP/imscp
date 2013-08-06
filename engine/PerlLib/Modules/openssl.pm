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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
#
# @category    i-MSCP
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
# @author      Daniel Andreca <sci2tech@gmail.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Modules::openssl;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Execute;
use parent 'Common::SingletonClass';

sub _init
{
	my $self = shift;

	$self->{'openssl_path'} = undef;
	$self->{'new_cert_path'} = undef;
	$self->{'new_cert_name'} = undef;
	$self->{'vhost_cert_name'} = undef;
	$self->{'cert_selfsigned'} = 0;
	$self->{'cert_path'} = '';
	$self->{'intermediate_cert_path'} = '';
	$self->{'key_path'} = '';
	$self->{'key_pass'} = '';
	$self->{'errors'} = '';
	$self->{'last_error'} = '';

	$self;
}

sub ssl_check_key
{
	my $self = shift;

	if ($self->{'key_path'} eq '' || ! -f $self->{'key_path'}) {
		error("Key $self->{'key_path'} doesn't exist. Exiting...");
		return -1;
	}

	my $password = escapeShell($self->{'key_pass'});
	my $cmd = "$self->{'openssl_path'} rsa -in $self->{'key_path'} -noout -passin pass:$password";

	my ($stdout, $stderr);
	my $rs = execute($cmd, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	warning($stderr) if $stderr && ! $rs;
	error("Invalid private key or password" . ($stderr ? ": $stderr" : '') . '.') if $rs;
	return $rs if $rs;

	0;
}

sub ssl_check_intermediate_cert
{
	my $self = shift;

	if ($self->{'intermediate_cert_path'} ne '' && ! -f $self->{'intermediate_cert_path'}) {
		error("Intermediate SSL certificate $self->{'intermediate_cert_path'} doesn't exist.");
		return 1;
	}

	0;
}

sub ssl_check_cert
{
	my $self = shift;

	if ($self->{'cert_path'} eq '' || ! -f $self->{'cert_path'}) {
		error("SSL certificate $self->{'cert_path'} doesn't exist.");
		return 1;
	}

	my $CAfile = '';

	if ($self->{'intermediate_cert_path'} ne '' ) {
		$CAfile = "-CAfile $self->{'intermediate_cert_path'}";
	}

	my $cmd = "$self->{'openssl_path'} verify $CAfile $self->{'cert_path'}";

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


sub ssl_check_all
{
	my $self = shift;

	my $rs = $self->ssl_check_key();
	return $rs if $rs;

	$rs = $self->ssl_check_intermediate_cert();
	return $rs if $rs;

	$rs = $self->ssl_check_cert();
	return $rs if $rs;

	0;
}

sub ssl_export_key
{
	my $self = shift;

	# TODO Passing the password in such way is not really recommended since the password will appear
	# in result of some commands such as PS
	my $password = escapeShell($self->{'key_pass'});
	my $cmd =
		"$self->{openssl_path} rsa -in $self->{'key_path'} " .
		"-out $self->{'new_cert_path'}/$self->{'new_cert_name'}.pem " .
		"-passin pass:$password";

	my ($stdout, $stderr);
	my $rs = execute($cmd, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error("Unable to export SSL private key" . ($stderr ? ": $stderr." : '.')) if $rs;
	return $rs if $rs;

	0;
}

sub ssl_export_cert
{
	my $self = shift;

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

sub ssl_export_intermediate_cert
{
	my $self = shift;

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

sub ssl_generate_selsigned_cert
{
	my $self = shift;

	my $cmd =
		"$self->{'openssl_path'} req -x509 -nodes -days 1825 " .
		"-subj '/C=/ST=/L=/CN=*.$self->{'vhost_cert_name'}' " .
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

sub ssl_export_all
{
	my $self = shift;
	my $rs = 0;

	if($self->{'cert_selfsigned'}) {
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

1;
