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

package Modules::SystemUser;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Execute;

use vars qw/@ISA/;

@ISA = ('Common::SimpleClass', 'Common::SetterClass');
use Common::SimpleClass;
use Common::SetterClass;

sub addSystemUser{

	debug((caller(0))[3].': Starting...');

	my $self	= shift;

	fatal((caller(0))[3].': Please use only instance of class not static calls', 1) if(ref $self ne __PACKAGE__);

	my $userName	= shift || $self->{username} || undef;
	$self->{username} = $userName;

	if(!$userName){
		error((caller(0))[3].': No user name was provided');
		return 1;
	}

	if(!getpwnam($userName)){
		my ($rs, $stdout, $stderr);
		my $comment			= $self->{usercomment} ? "\"$self->{usercomment}\"" : '"iMSCPuser"';
		my $home			= $self->{home} ? '"'.$self->{home}.'"' : "\"$main::imscpConfig{'USER_HOME_DIR'}/$userName\"";
		my $skipGroup		= $self->{skipGroup} || $self->{userGroup} ? '' : '-U';
		my $group			= $self->{userGroup} ? "-g $self->{userGroup}" : '';
		my $createHome		= $self->{skipCreateHome} ? '' : '-m';
		my $systemUser		= $self->{system} ? '-r' : '';
		my $copySkeleton	= $self->{system} ||$self->{skipCreateHome} ? '' : '-k';
		my $skeletonPath	= $self->{system} ||$self->{skipCreateHome} ? '' : "\"$main::imscpConfig{'GUI_ROOT_DIR'}/userHome\"";
		my $shell			= $self->{shell} ? $self->{shell} : '/bin/false';

		my  @cmd = (
			"$main::imscpConfig{'CMD_USERADD'}",
			($^O =~ /bsd$/ ? "\"$userName\"" : ''),	#username bsd way
			"-c", $comment,									#comment
			'-d', $home,									#homedir
			$skipGroup,										#create group with same name and add user to group
			$createHome,									#create home dir
			$copySkeleton, $skeletonPath,					#copy skeleton dir
			$systemUser,									#system account
			'-s', "\"$shell\"",								#shell
			($^O !~ /bsd$/ ? "\"$userName\"" : '')	#username linux way
		);
		$rs = execute("@cmd", \$stdout, \$stderr);
		debug((caller(0))[3].": $stdout") if $stdout;
		error((caller(0))[3].": $stderr") if ($stderr && $rs);
		warning((caller(0))[3].": $stderr") if ($stderr && !$rs);
		return $rs if $rs;
	}

	debug((caller(0))[3].': Ending...');
	0;
}

sub delSystemUser{

	debug((caller(0))[3].': Starting...');

	my $self	= shift;

	fatal((caller(0))[3].': Please use only instance of class not static calls', 1) if(ref $self ne __PACKAGE__);

	my $userName	= shift || $self->{username} || undef;
	$self->{username} = $userName;

	if(!$userName){
		error((caller(0))[3].': No user name was provided');
		return 1;
	}

	if(getpwnam($userName)){
		my ($rs, $stdout, $stderr);
		my  @cmd = (
			"$main::imscpConfig{'CMD_USERDEL'}",
			($^O =~ /bsd$/ ? "\"$userName\"" : ''),
			'-r',
			($^O !~ /bsd$/ ? "\"$userName\"" : '')
		);
		$rs = execute("@cmd", \$stdout, \$stderr);
		debug((caller(0))[3].": $stdout") if $stdout;
		error((caller(0))[3].": $stderr") if ($stderr && $rs);
		warning((caller(0))[3].": $stderr") if ($stderr && !$rs);
		return $rs if ($rs && $rs != 12);
	}

	debug((caller(0))[3].': Ending...');
	0;
}

sub addToGroup{

	debug((caller(0))[3].': Starting...');

	my $self	= shift;

	fatal((caller(0))[3].': Please use only instance of class not static calls', 1) if(ref $self ne __PACKAGE__);

	my $groupName	= shift || $self->{groupname} || undef;
	$self->{groupname} = $groupName;

	my $userName	= shift || $self->{username} || undef;
	$self->{username} = $userName;

	if(!$groupName){
		error((caller(0))[3].': No group name was provided');
		return 1;
	}
	if(!$userName){
		error((caller(0))[3].': No user name was provided');
		return 1;
	}

	if(getgrnam($groupName) && getpwnam($userName)){
		my ($rs, $stdout, $stderr);
		$self->getUserGroups($userName);
		if(!$self->{userGroups}->{$groupName}){
			my $newGroups =  join(',', keys %{$self->{userGroups}}) .",$groupName";
			my  @cmd = (
				"$main::imscpConfig{'CMD_USERGROUP'}",
				($^O =~ /bsd$/ ? "\"$userName\"" : ''),	#bsd way
				'-G', "\"$newGroups\"",
				($^O !~ /bsd$/ ? "\"$userName\"" : ''),	#linux way
			);
			$rs = execute("@cmd", \$stdout, \$stderr);
			debug((caller(0))[3].": $stdout") if $stdout;
			error((caller(0))[3].": $stderr") if ($stderr && $rs);
			warning((caller(0))[3].": $stderr") if ($stderr && !$rs);
			return $rs if $rs;
		}
	}

	debug((caller(0))[3].': Ending...');
	0;
}

sub getUserGroups{

	debug((caller(0))[3].': Starting...');

	my $self	= shift;

	fatal((caller(0))[3].': Please use only instance of class not static calls', 1) if(ref $self ne __PACKAGE__);

	my $userName	= shift || $self->{username} || undef;
	$self->{username} = $userName;

	my ($rs, $stdout, $stderr);
	$rs = execute("id -nG $userName", \$stdout, \$stderr);
	debug((caller(0))[3].": $stdout") if $stdout;
	error((caller(0))[3].": $stderr") if ($stderr && $rs);
	warning((caller(0))[3].": $stderr") if ($stderr && !$rs);
	return $rs if $rs;
	%{$self->{userGroups}} = map { $_ => 1 } split ' ', $stdout;

	debug((caller(0))[3].': Ending...');
	0;
}

sub removeFromGroup{
	debug((caller(0))[3].': Starting...');

	my $self	= shift;

	fatal((caller(0))[3].': Please use only instance of class not static calls', 1) if(ref $self ne __PACKAGE__);

	my $groupName	= shift || $self->{groupname} || undef;
	$self->{groupname} = $groupName;

	my $userName	= shift || $self->{username} || undef;
	$self->{username} = $userName;

	if(!$groupName){
		error((caller(0))[3].': No group name was provided');
		return 1;
	}
	if(!$userName){
		error((caller(0))[3].': No user name was provided');
		return 1;
	}

	if(getpwnam($userName)){
		my ($rs, $stdout, $stderr);
		$self->getUserGroups($userName);
		delete $self->{userGroups}->{$groupName};
		my $newGroups =  join(',', keys %{$self->{userGroups}});
		my  @cmd = (
			"$main::imscpConfig{'CMD_USERGROUP'}",
			($^O =~ /bsd$/ ? "\"$userName\"" : ''),	#bsd way
			'-G', "\"$newGroups\"",
			($^O !~ /bsd$/ ? "\"$userName\"" : ''),	#linux way
		);
		$rs = execute("@cmd", \$stdout, \$stderr);
		debug((caller(0))[3].": $stdout") if $stdout;
		error((caller(0))[3].": $stderr") if ($stderr && $rs);
		warning((caller(0))[3].": $stderr") if ($stderr && !$rs);
		return $rs if $rs;
	}

	debug((caller(0))[3].': Ending...');
	0;
}

1;
