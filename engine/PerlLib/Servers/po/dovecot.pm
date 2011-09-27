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

package Servers::po::dovecot;

use strict;
use warnings;
use iMSCP::Debug;

use vars qw/@ISA/;

@ISA = ('Common::SingletonClass');
use Common::SingletonClass;

sub _init{
	debug('Starting...');

	my $self		= shift;
	$self->{cfgDir}	= "$main::imscpConfig{'CONF_DIR'}/dovecot";
	$self->{bkpDir}	= "$self->{cfgDir}/backup";
	$self->{wrkDir}	= "$self->{cfgDir}/working";

	my $conf		= "$self->{cfgDir}/dovecot.data";

	tie %self::dovecotConfig, 'iMSCP::Config','fileName' => $conf;

	debug('Ending...');
	0;
}

sub preinstall{
	debug('Starting...');

	use Servers::po::dovecot::installer;

	my $self	= shift;
	my $rs		= Servers::po::dovecot::installer->new()->registerHooks();

	debug('Ending...');
	$rs;
}

sub install{
	debug('Starting...');

	use Servers::po::dovecot::installer;

	my $self	= shift;
	my $rs		= Servers::po::dovecot::installer->new()->install();

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

sub restart{
	debug('Starting...');

	my $self = shift;
	my ($rs, $stdout, $stderr);

	use iMSCP::Execute;

	# Reload config
	$rs = execute("$self::dovecotConfig{'CMD_DOVECOT'} restart", \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	warning("$stderr") if $stderr && !$rs;
	error("$stderr") if $stderr && $rs;
	return $rs if $rs;

	debug('Ending...');
	0;
}

sub DESTROY{
	debug('Starting...');

	my $endCode	= $?;
	my $self	= Servers::po::dovecot->new();
	my $rs		= 0;
	$rs			= $self->restart() if $self->{restart} && $self->{restart} eq 'yes';

	debug('Ending...');
	$? = $endCode || $rs;
}

1;
