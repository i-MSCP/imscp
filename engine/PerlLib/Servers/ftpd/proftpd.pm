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
# @copyright	2010 - 2011 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @version		SVN: $Id$
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::ftpd::proftpd;

use strict;
use warnings;
use iMSCP::Debug;

use vars qw/@ISA/;

@ISA = ('Common::SingletonClass');
use Common::SingletonClass;

sub _init{
	my $self	= shift;

	debug('Starting...');

	$self->{cfgDir} = "$main::imscpConfig{'CONF_DIR'}/proftpd";
	$self->{bkpDir} = "$self->{cfgDir}/backup";
	$self->{wrkDir} = "$self->{cfgDir}/working";
	$self->{tplDir} = "$self->{cfgDir}/parts";

	$self->{commentChar} = '#';

	tie %self::proftpdConfig, 'iMSCP::Config','fileName' => "$self->{cfgDir}/proftpd.data";
	$self->{$_} = $self::proftpdConfig{$_} foreach(keys %self::proftpdConfig);
}

sub preinstall{
	debug('Starting...');

	use Servers::ftpd::proftpd::installer;

	my $self	= shift;
	my $rs		= 0;

	debug('Ending...');
	$rs;
}

sub install{
	debug('Starting...');

	use Servers::ftpd::proftpd::installer;

	my $self	= shift;
	my $rs		= Servers::ftpd::proftpd::installer->new()->install();

	debug('Ending...');
	$rs;
}

sub postinstall{
	debug('Starting...');

	my $self	= shift;
	$self->{restart} = 'yes';

	debug('Ending...');
	0;
}

sub registerPreHook{
	debug('Starting...');

	my $self		= shift;
	my $fname		= shift;
	my $callback	= shift;

	my $installer	= Servers::ftpd::proftpd::installer->new();

	push (@{$installer->{preCalls}->{fname}}, $callback)
		if (ref $callback eq 'CODE' && $installer->can($fname));

	push (@{$self->{preCalls}->{fname}}, $callback)
		if (ref $callback eq 'CODE' && $self->can($fname));

	debug('Ending...');
	0;
}

sub registerPostHook{
	debug('Starting...');

	my $self		= shift;
	my $fname		= shift;
	my $callback	= shift;

	debug("Attaching to $fname...");

	my $installer	= Servers::ftpd::proftpd::installer->new();

	push (@{$installer->{postCalls}->{$fname}}, $callback)
		if (ref $callback eq 'CODE' && $installer->can($fname));

	push (@{$self->{postCalls}->{$fname}}, $callback)
		if (ref $callback eq 'CODE' && $self->can($fname));

	debug('Ending...');
	0;
}

sub restart{
	debug('Starting...');

	my $self = shift;
	my ($rs, $stdout, $stderr);

	use iMSCP::Execute;

	# Reload config
	$rs = execute("$self->{CMD_FTPD} restart", \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	warning("$stderr") if $stderr && !$rs;
	error("$stderr") if $stderr && $rs;
	return $rs if $rs;

	debug('Ending...');
	0;
}

sub addDmn{
	debug('Starting...');

	use iMSCP::File;
	use iMSCP::Templator;

	my $self	= shift;
	my $data	= shift;
	my $rs		= 0;

	my $errmsg = {
		'FILE_NAME'	=> 'You must supply a file name!',
		'PATH'		=> 'you must supply mount point!'
	};

	foreach(keys %{$errmsg}){
		error("$errmsg->{$_}") unless $data->{$_};
		return 1 unless $data->{$_};
	}

	iMSCP::File->new(
		filename => "$self::proftpdConfig{FTPD_CONF_DIR}/$data->{FILE_NAME}"
	)->copyFile( "$self->{bkpDir}/$data->{FILE_NAME}.".time ) and $rs = 1
	if -f "$self::proftpdConfig{FTPD_CONF_DIR}/$data->{FILE_NAME}";

	my $file	= iMSCP::File->new( filename => "$self->{tplDir}/proftpd.conf.tpl");
	my $content	= $file->get();

	if(!$content){
		error("Can not read $self->{tplDir}/proftpd.conf.tpl");
		return 1;
	}

	$content	= process({PATH => $data->{PATH}}, $content);
	$file	= iMSCP::File->new( filename => "$self->{wrkDir}/$data->{FILE_NAME}");

	$file->set($content);

	$rs |=	$file->save();
	$rs |=	$file->mode(0644);
	$rs |=	$file->owner(
				$main::imscpConfig{'ROOT_USER'},
				$main::imscpConfig{'ROOT_GROUP'}
			);
	$rs |= $file->copyFile("$self::proftpdConfig{FTPD_CONF_DIR}/$data->{FILE_NAME}");

	debug('Ending...');
	$rs;
}

sub delDmn{
	debug('Starting...');

	use iMSCP::File;
	use iMSCP::Templator;

	my $self	= shift;
	my $data	= shift;
	my $rs		=0 ;

	my $errmsg = {
		'FILE_NAME'	=> 'You must supply a file name!'
	};

	foreach(keys %{$errmsg}){
		error("$errmsg->{$_}") unless $data->{$_};
		return 1 unless $data->{$_};
	}

	iMSCP::File->new(
		filename => "$self::proftpdConfig{FTPD_CONF_DIR}/$data->{FILE_NAME}"
	)->delFile() and $rs = 1;

	debug('Ending...');
	$rs;
}

sub addSub{
	my $self = shift;
	return $self->addDmn(@_);
}

sub delSub{
	my $self = shift;
	return $self->delDmn(@_);
}


END{
	debug('Starting...');

	my $endCode	= $?;
	my $self	= Servers::ftpd::proftpd->new();
	my $rs		= 0;
	$rs			= $self->restart() if $self->{restart} && $self->{restart} eq 'yes';

	debug('Ending...');
	$? = $endCode || $rs;
}

1;
