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


#####################################################################################
# Package description:
#
# Package that allows to get distribution information. For now:
# distribution name, distribution code name, and distribution version.
#
# Note: Only distributions that provides lsb_release tool are supported.

package iMSCP::SO;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Execute qw/execute/;

use vars qw/@ISA/;
@ISA = ('Common::SingletonClass');
use Common::SingletonClass;


sub _init{
	my $self = shift;
	fatal('Can not guess operating system') if ($self->getSO);
}

# Gets information about distribution.
#
# When this method is called, the following public attributes are populated:
#
# - Distribution : Contains the distribution name
# - CodeName :  Contains the distribution code name
# - Version :  Contains the distribution version
#
# @param self $self iMSCP::SO instance
# @return int 0 on success, other on failure

sub getSO{

	my $self = shift;
	my ($rs, $stdout, $stderr);

	fatal('Not a Debian like system') if(execute('which apt-get', \$stdout, \$stderr));

	if(execute('which lsb_release', \$stdout, \$stderr)){
		$rs = execute('apt-get -y install lsb-release', \$stdout, \$stderr);
		debug("$stdout") if $stdout;
		error("$stderr") if $stderr;
		return $rs if $rs;
	}

	# Retrieves distribution name
	$rs = execute('lsb_release -si', \$stdout, \$stderr);
	debug("Distribution is $stdout") if $stdout;
	error("Can not guess operating system: $stderr") if $stderr;
	return $rs if $rs;
	$self->{Distribution} = $stdout;

	# Retrieves distribution code name
	$rs = execute('lsb_release -sr', \$stdout, \$stderr);
	debug("Version is $stdout") if $stdout;
	error("Can not guess operating system: $stderr") if $stderr;
	return $rs if $rs;
	$self->{Version} = $stdout;

	# Retrieves distribution version
	$rs = execute('lsb_release -sc', \$stdout, \$stderr);
	debug("Codename is $stdout") if $stdout;
	error("Can not guess operating system: $stderr") if $stderr;
	return $rs if $rs;
	$self->{CodeName} = $stdout;

	debug ("Found $self->{Distribution} $self->{Version} $self->{CodeName}");
	0;
}

1;
