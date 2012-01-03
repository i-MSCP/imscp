#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010 - 2011 by internet Multi Server Control Panel
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
# @category		i-MSCP
# @copyright	2010 - 2012 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @version		SVN: $Id$
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Modules::openssl;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Execute qw/execute/;
use Common::SingletonClass;

use vars qw/@ISA/;
@ISA = ('Common::SingletonClass');
use Common::SingletonClass;

sub _init  {
	my $self  = shift;
	$self->{openssl_path}			= undef;
	$self->{new_cert_path}			= undef;
	$self->{new_cert_name}			= undef;
	$self->{vhost_cert_name}		= undef;
	$self->{cert_selfsigned}		= undef;
	$self->{cert_path}				= undef;
	$self->{intermediate_cert_path}	= undef;
	$self->{key_path}				= undef;
	$self->{key_pass}				= undef;
	$self->{errors}					= '';
	$self->{last_error}				= '';
}

sub ssl_check_intermediate_cert {

	my $self = shift;

	if ($self->{intermediate_cert_path} ne '' && ! -e $self->{intermediate_cert_path} ) {
		error("Intermediate certificate $self->{intermediate_cert_path} do not exists. Exiting...");
		return 1;
	}

	0;
}

sub ssl_check_cert {

	my $self = shift;

	if ( ( $self->{cert_path} eq '' ) || ( !-e "$self->{cert_path}" ) ) {
		error("Certificate $self->{cert_path} do not exists. Exiting...");
		return 1;
	}

	my $CAfile = '';

	if ( $self->{intermediate_cert_path} ne '' ) {
			$CAfile = "-CAfile $self->{intermediate_cert_path}";
	}

	my $cmd = "$self->{openssl_path} verify $CAfile $self->{cert_path}";

	my ($stdout, $stderr);
	my $rs = execute($cmd, \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	error("$stderr") if $stderr;
	#fatal("|$self->{cert_path}|$stdout|$stderr|");
	return 1 if($rs || $stderr);

	if ( $stdout !~ m~$self->{cert_path}:.*OK~ms ){
		error("Certificate $self->{cert_path} is not valid. Exiting...");
		return 1;
	}

	0;
}

sub ssl_check_key {

	my $self = shift;

	if ( ( $self->{key_path} eq '' ) || ( !-e "$self->{key_path}" ) ){
		error("Key $self->{key_path} do not exists. Exiting...");
		return -1;
	}

	my $cmd = "$self->{openssl_path} rsa -in $self->{key_path} -noout -passin pass:\"" . ( $self->{key_pass} ? $self->{key_pass} : 'dummypass' )."\"";

	my ($stdout, $stderr);
	my $rs = execute($cmd, \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	warning("$stderr") if ($stderr && !$rs);
	error("Key is invalid or wrong password".($stderr ? ": $stderr" : '').". Exiting...") if $rs;
	return $rs if $rs;

	0;
}

sub ssl_check_all{

	my $self = shift;

	my $rs = $self->ssl_check_key();
	return $rs if ($rs);

	$rs = $self->ssl_check_intermediate_cert();
	return $rs if ($rs);

	$rs = $self->ssl_check_cert();
	return $rs if ($rs);

	0;
}

sub ssl_export_key {

	my $self = shift;

	my $cmd = "$self->{openssl_path} rsa -in $self->{key_path} -out $self->{new_cert_path}/$self->{new_cert_name}.pem -passin pass:" . ( ( $self->{key_pass} ne '' ) ? $self->{key_pass} : 'dummypass' );

	my ($stdout, $stderr);
	my $rs = execute($cmd, \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	error("Can not save certificate key".($stderr ? ": $stderr" : '').". Exiting...") if $rs;
	return $rs if $rs;

	0;
}

sub ssl_export_cert {

	my $self = shift;

	my $cmd = "$self->{openssl_path} x509 -in $self->{cert_path} -outform PEM >> $self->{new_cert_path}/$self->{new_cert_name}.pem";

	my ($stdout, $stderr);
	my $rs = execute($cmd, \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	warning("$stderr") if ($stderr && !$rs);
	error("Can not save certificate".($stderr ? ": $stderr" : '').". Exiting...") if $rs;
	return $rs if $rs;

	0;
}

sub ssl_export_intermediate_cert {

	my $self = shift;

	return 0 if ( ( $self->{intermediate_cert_path} eq '' ) );

	my $cmd = "$self->{openssl_path} x509 -in $self->{intermediate_cert_path} -outform PEM >> $self->{new_cert_path}/$self->{new_cert_name}.pem 2>/dev/null";

	my ($stdout, $stderr);
	my $rs = execute($cmd, \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	warning("$stderr") if ($stderr && !$rs);
	error("Can not save intermediate certificate".($stderr ? ": $stderr" : '').". Exiting...") if $rs;
	return $rs if $rs;

	0;
}

sub ssl_generate_selsigned_cert{

	my $self = shift;

	my $cmd = "$self->{openssl_path} req -x509 -nodes -days 1825 -subj '/C=/ST=/L=/CN=$self->{vhost_cert_name}' -newkey rsa:1024 -keyout $self->{new_cert_path}/$self->{new_cert_name}.pem -out $self->{new_cert_path}/$self->{new_cert_name}.pem";

	my ($stdout, $stderr);
	my $rs = execute($cmd, \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	warning("$stderr") if ($stderr && !$rs);
	error("Can not save intermediate certificate".($stderr ? ": $stderr" : '').". Exiting...") if $rs;
	return $rs if($rs);

	0;
}

sub ssl_export_all{


	my $self	= shift;
	my $rs		= 0;

	if( $self->{cert_selfsigned} == 0 ){

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
