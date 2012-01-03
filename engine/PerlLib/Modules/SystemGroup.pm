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

package Modules::SystemGroup;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Execute;

use vars qw/@ISA/;

@ISA = ('Common::SimpleClass');
use Common::SimpleClass;

sub addSystemGroup{


	my $self	= shift;

	fatal('Please use only instance of class not static calls', 1) if(ref $self ne __PACKAGE__);

	my $groupName	= shift || $self->{groupname} || undef;
	$self->{groupname} = $groupName;

	if(!$groupName){
		error('No group name was provided');
		return 1;
	}

	if(!getgrnam($groupName)){
		my ($rs, $stdout, $stderr);
		my $systemGroup		= $self->{system} ? '-r' : '';

		my  @cmd = (
			"$main::imscpConfig{'CMD_GROUPADD'}",
			($^O !~ /bsd$/ ? "$systemGroup" : ''),	#system group
			"\"$groupName\""							#group name
		);
		$rs = execute("@cmd", \$stdout, \$stderr);
		debug("$stdout") if $stdout;
		error("$stderr") if ($stderr && $rs);
		warning("$stderr") if ($stderr && !$rs);
		return $rs if $rs;
	}

	0;
}

sub delSystemGroup{

	my $self	= shift;

	fatal('Please use only instance of class not static calls', 1) if(ref $self ne __PACKAGE__);

	my $groupName	= shift || $self->{groupname} || undef;
	$self->{groupname} = $groupName;

	if(!$groupName){
		error('No group name was provided');
		return 1;
	}

	if(getgrnam($groupName)){
		my ($rs, $stdout, $stderr);
		my  @cmd = (
			"$main::imscpConfig{'CMD_GROUPDEL'}",
			"\"$groupName\""
		);
		$rs = execute("@cmd", \$stdout, \$stderr);
		debug("$stdout") if $stdout;
		error("$stderr") if ($stderr && $rs);
		warning("$stderr") if ($stderr && !$rs);
		return $rs if $rs;
	}

	0;
}

1;
