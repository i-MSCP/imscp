#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright 2010 - 2012 by internet Multi Server Control Panel
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
# This package provides a class that is responsible to install all dependencies
# (libraries, tools and softwares) required by i-MSCP on Ubuntu operating systems.
#

package library::ubuntu_autoinstall;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Execute qw/execute/;

use vars qw/@ISA/;
@ISA = ('Common::SingletonClass', 'library::debian_autoinstall');
use Common::SingletonClass;
use library::debian_autoinstall;

# Initializer.
#
# @param self $self iMSCP::debian_autoinstall instance
# return int 0
sub _init {
	debug('Starting...');

	my $self = shift;

	$self->{nonfree} = 'multiverse';

	debug('Ending...');

	0;
}

1;
