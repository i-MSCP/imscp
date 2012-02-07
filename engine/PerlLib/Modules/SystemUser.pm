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

package Modules::SystemUser;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Execute;

use vars qw/@ISA/;

@ISA = ('Common::SimpleClass');
use Common::SimpleClass;

sub addSystemUser{


	my $self	= shift;

	fatal('Please use only instance of class not static calls', 1) if(ref $self ne __PACKAGE__);

	my $userName	= shift || $self->{username} || undef;
	$self->{username} = $userName;

	if(!$userName){
		error('No user name was provided');
		return 1;
	}

	my ($rs, $stdout, $stderr);
	my $comment			= $self->{comment} ? "\"$self->{comment}\"" : '"iMSCPuser"';
	my $home			= $self->{home} ? '"'.$self->{home}.'"' : "\"$main::imscpConfig{'USER_HOME_DIR'}/$userName\"";
	my $skipGroup		= $self->{skipGroup} || $self->{group} ? '' : '-U';
	my $group			= $self->{group} ? "-g \"$self->{group}\"" : '';
	my $createHome		= $self->{skipCreateHome} ? '' : '-m';
	my $systemUser		= $self->{system} ? '-r' : '';
	my $copySkeleton	= $self->{system} || $self->{skipCreateHome} ? '' : '-k';
	my $skeletonPath	= $self->{system} || $self->{skipCreateHome} ? '' : "\"$main::imscpConfig{'GUI_ROOT_DIR'}/data/user_home\"";
	my $shell			= $self->{shell} ? $self->{shell} : '/bin/false';


	my @cmd;

	if(!getpwnam($userName)){
		@cmd = (
			$main::imscpConfig{'CMD_USERADD'},
			($^O =~ /bsd$/ ? "\"$userName\"" : ''),	#username bsd way
			"-c", $comment,							#comment
			'-d', $home,							#homedir
			$skipGroup,								#create group with same name and add user to group
			$group,
			$createHome,							#create home dir
			$copySkeleton, $skeletonPath,			#copy skeleton dir
			$systemUser,							#system account
			'-s', "\"$shell\"",						#shell
			($^O !~ /bsd$/ ? "\"$userName\"" : '')	#username linux way
		);

	} else {
		@cmd = (
			'skill -KILL -vu ' . $userName . '; ',
			$main::imscpConfig{'CMD_USERGROUP'},
			($^O =~ /bsd$/ ? "\"$userName\"" : ''),	#username bsd way
			"-c", $comment,							#comment
			'-d', $home,							#homedir
			$skipGroup,								#create group with same name and add user to group
			$group,
			'-s', "\"$shell\"",						#shell
			($^O !~ /bsd$/ ? "\"$userName\"" : '')	#username linux way
		);
	}

	$rs = execute("@cmd", \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	error("$stderr") if ($stderr && $rs);
	debug("$stderr") if ($stderr && !$rs);
	return $rs if $rs;

	0;
}

sub delSystemUser{

	my $self	= shift;

	fatal('Please use only instance of class not static calls', 1) if(ref $self ne __PACKAGE__);

	my $userName	= shift || $self->{username} || undef;
	$self->{username} = $userName;

	if(!$userName){
		error('No user name was provided');
		return 1;
	}

	if(getpwnam($userName)){
		my ($rs, $stdout, $stderr);
		my  @cmd = (
			"$main::imscpConfig{'CMD_USERDEL'}",
			($^O =~ /bsd$/ ? "\"$userName\"" : ''),
			'-r',
			($self->{force} ? "-f" : ''),
			($^O !~ /bsd$/ ? "\"$userName\"" : '')
		);
		$rs = execute("@cmd", \$stdout, \$stderr);
		debug("$stdout") if $stdout;
		error("$stderr") if ($stderr && $rs && $rs != 12);
		warning("$stderr") if ($stderr && !$rs);
		return $rs if ($rs && $rs != 12);
	}

	0;
}

sub addToGroup{


	my $self	= shift;

	fatal('Please use only instance of class not static calls', 1) if(ref $self ne __PACKAGE__);

	my $groupName	= shift || $self->{groupname} || undef;
	$self->{groupname} = $groupName;

	my $userName	= shift || $self->{username} || undef;
	$self->{username} = $userName;

	if(!$groupName){
		error('No group name was provided');
		return 1;
	}
	if(!$userName){
		error('No user name was provided');
		return 1;
	}

	if(getgrnam($groupName) && getpwnam($userName)){
		my ($rs, $stdout, $stderr);
		$self->getUserGroups($userName);
		if(!$self->{userGroups}->{$groupName}){
			my $newGroups =  join(',', keys %{$self->{userGroups}}) .",$groupName";
			my  @cmd = (
				'skill -KILL -vu ' . $userName . '; ',
				"$main::imscpConfig{'CMD_USERGROUP'}",
				($^O =~ /bsd$/ ? "\"$userName\"" : ''),	#bsd way
				'-G', "\"$newGroups\"",
				($^O !~ /bsd$/ ? "\"$userName\"" : ''),	#linux way
			);
			$rs = execute("@cmd", \$stdout, \$stderr);
			debug("$stdout") if $stdout;
			error("$stderr") if ($stderr && $rs);
			warning("$stderr") if ($stderr && !$rs);
			return $rs if $rs;
		}
	}

	0;
}

sub getUserGroups{


	my $self	= shift;

	fatal('Please use only instance of class not static calls', 1) if(ref $self ne __PACKAGE__);

	my $userName	= shift || $self->{username} || undef;
	$self->{username} = $userName;

	my ($rs, $stdout, $stderr);
	$rs = execute("id -nG $userName", \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	error("$stderr") if ($stderr && $rs);
	warning("$stderr") if ($stderr && !$rs);
	return $rs if $rs;
	%{$self->{userGroups}} = map { $_ => 1 } split ' ', $stdout;

	0;
}

sub removeFromGroup{

	my $self	= shift;

	fatal(': Please use only instance of class not static calls', 1) if(ref $self ne __PACKAGE__);

	my $groupName	= shift || $self->{groupname} || undef;
	$self->{groupname} = $groupName;

	my $userName	= shift || $self->{username} || undef;
	$self->{username} = $userName;

	if(!$groupName){
		error('No group name was provided');
		return 1;
	}
	if(!$userName){
		error('No user name was provided');
		return 1;
	}

	if(getpwnam($userName)){
		my ($rs, $stdout, $stderr);
		$self->getUserGroups($userName);
		delete $self->{userGroups}->{$groupName};
		my $newGroups =  join(',', keys %{$self->{userGroups}});
		my  @cmd = (
			'skill -KILL -vu ' . $userName . '; ',
			"$main::imscpConfig{'CMD_USERGROUP'}",
			($^O =~ /bsd$/ ? "\"$userName\"" : ''),	#bsd way
			'-G', "\"$newGroups\"",
			($^O !~ /bsd$/ ? "\"$userName\"" : ''),	#linux way
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
