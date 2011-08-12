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

package iMSCP::SO;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Execute qw/execute/;

use vars qw/@ISA/;
@ISA = ("Common::SingletonClass");
use Common::SingletonClass;

sub getSO{
	debug((caller(0))[3].': Starting...');

	my $self = shift;
	my ($rs, $stdout, $stderr);

	$rs = execute("lsb_release -si", \$stdout, \$stderr);
	debug((caller(0))[3].": Distribution is $stdout") if $stdout;
	error((caller(0))[3].": Can not guess operating system = $stderr") if $stderr;
	return $rs if $rs;
	$self->{Distribution} = $stdout;

	$rs = execute("lsb_release -sr", \$stdout, \$stderr);
	debug((caller(0))[3].": Version is $stdout") if $stdout;
	error((caller(0))[3].": Can not guess operating system = $stderr") if $stderr;
	return $rs if $rs;
	$self->{Version} = $stdout;

	$rs = execute("lsb_release -sc", \$stdout, \$stderr);
	debug((caller(0))[3].": Codename is $stdout") if $stdout;
	error((caller(0))[3].": Can not guess operating system = $stderr") if $stderr;
	return $rs if $rs;
	$self->{CodeName} = $stdout;

	debug ((caller(0))[3].": Found $self->{Distribution} $self->{Version} $self->{CodeName}");

	debug((caller(0))[3].': Ending...');
	0;
}

1;
