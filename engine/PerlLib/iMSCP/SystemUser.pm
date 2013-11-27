#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2013 by internet Multi Server Control Panel
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
# @category    i-MSCP
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
# @author      Daniel Andreca <sci2tech@gmail.com>
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::SystemUser;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Execute;
use parent 'Common::SimpleClass';

# Initialize instance
sub _init
{
	my $self = shift;

	$self->{$_} = $self->{'args'}->{$_} for keys %{$self->{'args'}};

	$self;
}

# Add the given unix user
sub addSystemUser
{
	my $self = shift;
	my $userName = shift || $self->{'username'};

	if(! $userName) {
		error('Username is missing');
		return 1;
	}

	$self->{'username'} = $userName;

	my $password = $self->{'password'} ? '-p ' . escapeShell($self->{'password'}) : '';
	my $comment	= $self->{'comment'} ? $self->{'comment'} : 'iMSCPuser';
	my $home = $self->{'home'} ? $self->{'home'} : "$main::imscpConfig{'USER_WEB_DIR'}/$userName";
	my $skipGroup = $self->{'skipGroup'} || $self->{'group'} ? '' : '-U';
	my $group = $self->{'group'} ? '-g ' . escapeShell($self->{'group'}) : '';
	my $createHome = $self->{'skipCreateHome'} ? '' : '-m';
	my $systemUser = $self->{'system'} ? '-r' : '';
	my $copySkeleton = $self->{'system'} || $self->{'skipCreateHome'} ? '' : '-k';
	my $skeletonPath = $self->{'system'} || $self->{'skipCreateHome'}
		? '' : $self->{'skeletonPath'} || '/etc/skel';
	my $shell = $self->{'shell'} ? $self->{'shell'} : '/bin/false';

	my @cmd;

	if(! getpwnam($userName)) { # Creating new user
		@cmd = (
			$main::imscpConfig{'CMD_USERADD'},
			($^O =~ /bsd$/ ? escapeShell($userName) : ''),	# username bsd way
			$password,										# Password
			'-c', escapeShell($comment),					# comment
			'-d', escapeShell($home),						# homedir
			$skipGroup,										# create group with same name and add user to group
			$group,											# user initial connexion group
			$createHome,									# create home dir
			$copySkeleton, escapeShell($skeletonPath),		# copy skeleton dir
			$systemUser,									# system account
			'-s', escapeShell($shell),						# shell
			($^O !~ /bsd$/ ? escapeShell($userName) : '')	# username linux way
		);
	} else { # Modify existent user
		@cmd = (
			"$main::imscpConfig{'CMD_PKILL'} -KILL -f -u " . escapeShell($userName) . '; ',
			$main::imscpConfig{'CMD_USERMOD'},
			($^O =~ /bsd$/ ? escapeShell($userName) : ''),	# username bsd way
			$password,										# Password
			'-c', escapeShell($comment),					# comment
			'-d', escapeShell($home),						# homedir
			'-m',											# Move current home content in new home if needed
			'-s', escapeShell($shell),						# shell
			($^O !~ /bsd$/ ? escapeShell($userName) : '')	# username linux way
		);
	}

	my ($stdout, $stderr);
	my $rs = execute("@cmd", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs && $rs != 12;
	debug($stderr) if $stderr && ! $rs;
	return $rs if $rs && $rs != 12;

	0;
}

# Delete the given unix user
sub delSystemUser
{
	my $self = shift;
	my $userName = shift || $self->{'username'};

	if(! $userName) {
		error('Username is missing');
		return 1;
	}

	$self->{'username'} = $userName;

	if(getpwnam($userName)) {
		my  @cmd = (
			"$main::imscpConfig{'CMD_PKILL'} -KILL -f -u " . escapeShell($userName) . '; ',
			$main::imscpConfig{'CMD_USERDEL'},
			($^O =~ /bsd$/ ? escapeShell($userName) : ''),
			($self->{'keepHome'} ? '' : '-r'),
			(($self->{'force'} && ! $self->{'keepHome'}) ? '-f' : ''),
			($^O !~ /bsd$/ ? escapeShell($userName) : '')
		);

		my ($stdout, $stderr);
		my $rs = execute("@cmd", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs && $rs != 12;
		debug($stderr) if $stderr && ! $rs;
		return $rs if $rs && $rs != 12;
	}

	0;
}

# Add the given unix user to the given unix group
sub addToGroup
{
	my $self = shift;
	my $groupName = shift || $self->{'groupname'};
	my $userName = shift || $self->{'username'};

	if(! $groupName) {
		error('Group name is missing');
		return 1;
	}

	if(! $userName) {
		error('Username is missing');
		return 1;
	}

	$self->{'groupname'} = $groupName;
	$self->{'username'} = $userName;

	if(getgrnam($groupName) && getpwnam($userName)) {
		my (@cmd, $rs, $stdout, $stderr);

		if($^O =~ /bsd$/) { # bsd way
			$self->getUserGroups($userName);

			if(! exists $self->{'userGroups'}->{$groupName}) {
				delete $self->{'userGroups'}->{$userName};

				my $newGroups = join(',', keys %{$self->{'userGroups'}});
				$newGroups = ($newGroups ne '') ? "$newGroups,$groupName" : $groupName;

				@cmd = ($main::imscpConfig{'CMD_USERMOD'}, escapeShell($userName), '-G', escapeShell($newGroups));
				$rs = execute("@cmd", \$stdout, \$stderr);
				debug($stdout) if $stdout;
				error($stderr) if $stderr && $rs;
				debug($stderr) if $stderr && ! $rs;
				return $rs if $rs;
			}
		} else { # Linux way
			@cmd = ($main::imscpConfig{'CMD_GPASSWD'}, '-a', escapeShell($userName), escapeShell($groupName));
			$rs = execute("@cmd", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs && $rs != 3;
			debug($stderr) if $stderr && ! $rs;
			return $rs if $rs && $rs != 3;
		}
	}

	0;
}

# Remove the given unix user from the given unix group
sub removeFromGroup
{
	my $self = shift;
	my $groupName = shift || $self->{'groupname'} || undef;
	my $userName = shift || $self->{'username'} || undef;

	if(! $groupName){
		error('Group name is missing');
		return 1;
	}

	if(! $userName){
		error('Username is missing');
		return 1;
	}

	$self->{'groupname'} = $groupName;
	$self->{'username'} = $userName;

	if(getpwnam($userName) && getgrnam($groupName)) {
		my (@cmd, $rs, $stdout, $stderr);

		if($^O =~ /bsd$/) { # bsd way
			$self->getUserGroups($userName);

			delete $self->{'userGroups'}->{$groupName};
			delete $self->{'userGroups'}->{$userName};

			my $newGroups =  join(',', keys %{$self->{'userGroups'}});

			@cmd = ($main::imscpConfig{'CMD_USERMOD'}, escapeShell($userName), '-G', escapeShell($newGroups));
			$rs = execute("@cmd", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			debug($stderr) if $stderr && ! $rs;
			return $rs if $rs;
		} else {
			@cmd = ($main::imscpConfig{'CMD_GPASSWD'}, '-d', escapeShell($userName), escapeShell($groupName));
			$rs = execute("@cmd", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs && $rs != 3;
			debug($stderr) if $stderr && ! $rs;
			return $rs if $rs && $rs != 3;
		}
	}

	0;
}

# Retrieve list of all groups to which unix user is part
sub getUserGroups
{
	my $self = shift;
	my $userName = shift || $self->{'username'} || undef;

	if(! $userName) {
		error('Username is missing');
		return 1;
	}

	$self->{'username'} = $userName;

	my ($rs, $stdout, $stderr);
	$rs = execute("$main::imscpConfig{'CMD_ID'} -nG " . escapeShell($userName), \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	debug($stderr) if $stderr && ! $rs;
	return $rs if $rs;

	%{$self->{'userGroups'}} = map { $_ => 1 } split ' ', $stdout;

	0;
}

1;
