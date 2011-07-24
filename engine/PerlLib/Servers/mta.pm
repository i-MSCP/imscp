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
# @version		SVN: $Id: httpd.pm 4856 2011-07-11 08:48:54Z sci2tech $
# @link			http://i-mscp.net i-MSCP Home Site
# @license      http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::mta;

use strict;
use warnings;
use iMSCP::Debug;

use vars qw/@ISA/;
@ISA = ('Common::SimpleClass');
use Common::SimpleClass;

sub factory{
	my $self	= shift;
	my $server	= shift;
	my $file	= "Servers/mta/$server.pm";
	my $class	= "Servers::mta::$server";

	debug((caller(0))[3].': Starting...');

	require $file;

	debug((caller(0))[3].': Ending...');

	return $class->new();
}

1;
