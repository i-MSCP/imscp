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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
#
# @category		i-MSCP
# @copyright	2010 - 2012 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @version		SVN: $Id$
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Modules::Abstract;

use strict;
use warnings;
use iMSCP::Debug;
use Data::Dumper;

use vars qw/@ISA/;

@ISA = ('Common::SimpleClass');
use Common::SimpleClass;

sub _init{
	fatal('Developer must define own function for module');
}

sub loadData{
	fatal('Developer must define own function for module');
}

sub process{
	fatal('Developer must define own function for module')
}

sub add{

	my $self		= shift;
	$self->{mode}	= 'add';
	my $rs = $self->runAllSteps();

	$rs;
}

sub delete{

	use iMSCP::Servers;
	use iMSCP::Addons;

	my $self		= shift;
	$self->{mode}	= 'del';
	my $rs 			= $self->runAllSteps();

	$rs;
}

sub restore{
	0;
}

sub disable{

	use iMSCP::Servers;
	use iMSCP::Addons;

	my $self		= shift;
	$self->{mode}	= 'disable';
	my $rs = $self->runAllSteps();

	$rs;
}

sub runAllSteps{

	use iMSCP::Servers;
	use iMSCP::Addons;

	my $self		= shift;
	my $rs = 0;

	@{$self->{Addons}}	= iMSCP::Addons->new()->get();
	unless(scalar @{$self->{Addons}}){
		error("Can not get addons list");
		return 1;
	}
	@{$self->{Servers}}	= iMSCP::Servers->new()->get();
	unless(scalar @{$self->{Servers}}){
		error("Can not get servers list");
		return 1;
	}

	for(@{$self->{Servers}}, 'Addon'){
		next if $_ eq 'noserver.pm';
		my $fname = "build".uc($_)."Data";
		$fname =~ s/\.pm//i;
		$rs = eval "\$self->$fname();";
		error("$@") if($@);
		return 1 if($@)
	}

	$rs |= $self->runStep("pre$self->{mode}$self->{type}",	'Servers');
	$rs |= $self->runStep("pre$self->{mode}$self->{type}",	'Addons');
	$rs |= $self->runStep("$self->{mode}$self->{type}", 	'Servers');
	$rs |= $self->runStep("$self->{mode}$self->{type}",		'Addons');
	$rs |= $self->runStep("post$self->{mode}$self->{type}",	'Servers');
	$rs |= $self->runStep("post$self->{mode}$self->{type}",	'Addons');

	$rs;
}

sub runStep{

	my $self	= shift;
	my $func	= shift;
	my $type	= shift;
	my $rs		= 0;

	my ($file, $class, $instance);

	for (@{$self->{$type}}){
		s/\.pm//;
		$file	= "$type/$_.pm";
		$class	= "${type}::$_";
		require $file;
		$instance	= $class->factory();
		if($type eq 'Addons'){
			debug("Calling addon $_ function $func")
				if $instance->can($func) && exists $self->{AddonsData};
			$rs |= $instance->$func($self->{AddonsData})
					if $instance->can($func) && exists $self->{AddonsData};
		} else {
			debug("Calling server $_ function $func")
				if $instance->can($func) && exists $self->{$_};
			$rs |= $instance->$func($self->{$_})
					if $instance->can($func) && exists $self->{$_};
		}
	}

	$rs;
}


sub testCert{
	use iMSCP::File;
	use Modules::openssl;

	my $self		= shift;
	my $dmn_name	= shift;
	my $rs			= 0;
	my $certPath	= "$main::imscpConfig{GUI_ROOT_DIR}/data/certs";
	my $certFile	= "$certPath/$dmn_name.pem";

	Modules::openssl->new()->{openssl_path}				= $main::imscpConfig{'CMD_OPENSSL'};
	Modules::openssl->new()->{cert_path}				= $certFile;
	Modules::openssl->new()->{intermediate_cert_path}	= $certFile;
	Modules::openssl->new()->{key_path}					= $certFile;
	Modules::openssl->new()->ssl_check_all();
}

sub buildHTTPDData{ 0; }

sub buildMTAData{ 0; }

sub buildNAMEDData{ 0; }

sub buildFTPDData{ 0; }

sub buildPOData{ 0; }

sub buildCRONData{ 0; }

sub buildADDONData{ 0; }

1;
