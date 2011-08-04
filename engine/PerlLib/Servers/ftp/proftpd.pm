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

package Servers::ftp::proftpd;

use strict;
use warnings;
use iMSCP::Debug;

use vars qw/@ISA/;

@ISA = ('Common::SingletonClass');
use Common::SingletonClass;

sub _init{
	my $self	= shift;

	debug((caller(0))[3].': Starting...');

	$self->{cfgDir} = "$main::imscpConfig{'CONF_DIR'}/proftpd";
	$self->{bkpDir} = "$self->{cfgDir}/backup";
	$self->{wrkDir} = "$self->{cfgDir}/working";

	$self->{commentChar} = '#';

	#$self->{$_} = $main::imscpConfig{$_} foreach(keys %main::imscpConfig);

	tie %self::proftpdConfig, 'iMSCP::Config','fileName' => "$self->{cfgDir}/proftpd.data";
	$self->{$_} = $self::proftpdConfig{$_} foreach(keys %self::proftpdConfig);
}

sub preinstall{
	debug((caller(0))[3].': Starting...');

	use Servers::ftp::proftpd::installer;

	my $self	= shift;
	my $rs		= 0;

	debug((caller(0))[3].': Ending...');
	$rs;
}

sub install{
	debug((caller(0))[3].': Starting...');

	use Servers::ftp::proftpd::installer;

	my $self	= shift;
	my $rs		= Servers::ftp::proftpd::installer->new()->install();

	debug((caller(0))[3].': Ending...');
	$rs;
}

sub postinst{
	debug((caller(0))[3].': Starting...');

	my $self	= shift;
	my $rs		= $self->restart();

	debug((caller(0))[3].': Ending...');
	$rs;
}

sub registerPreHook{
	debug((caller(0))[3].': Starting...');

	my $self		= shift;
	my $fname		= shift;
	my $callback	= shift;

	my $installer	= Servers::ftp::proftpd::installer->new();

	push (@{$installer->{preCalls}->{fname}}, $callback)
		if (ref $callback eq 'CODE' && $installer->can($fname));

	push (@{$self->{preCalls}->{fname}}, $callback)
		if (ref $callback eq 'CODE' && $self->can($fname));

	debug((caller(0))[3].': Ending...');
	0;
}

sub registerPostHook{
	debug((caller(0))[3].': Starting...');

	my $self		= shift;
	my $fname		= shift;
	my $callback	= shift;

	debug((caller(0))[3].": Attaching to $fname...");

	my $installer	= Servers::ftp::proftpd::installer->new();

	push (@{$installer->{postCalls}->{$fname}}, $callback)
		if (ref $callback eq 'CODE' && $installer->can($fname));

	push (@{$self->{postCalls}->{$fname}}, $callback)
		if (ref $callback eq 'CODE' && $self->can($fname));

	debug((caller(0))[3].': Ending...');
	0;
}

sub restart{
	debug((caller(0))[3].': Starting...');

	my $self			= shift;
	my ($rs, $stdout, $stderr);

	use iMSCP::Execute;

	# Reload config
	$rs = execute("$self->{CMD_FTPD} restart", \$stdout, \$stderr);
	debug((caller(0))[3].": $stdout") if $stdout;
	error((caller(0))[3].": $stderr") if $stderr;
	return $rs if $rs;

	debug((caller(0))[3].': Ending...');
	0;
}

1;
