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

# Add unix user
sub addSystemUser
{
	my $self = shift;

	fatal('Please use only instance of class not static calls', 1) if ref $self ne __PACKAGE__;

	my $userName = shift || $self->{'username'};
	$self->{'username'} = $userName;

	if(! $userName) {
		error('No username was provided');
		return 1;
	}

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
			'/usr/bin/skill -KILL -vu ' . escapeShell($userName) . '; ',
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

# Delete unix user
sub delSystemUser
{
	my $self = shift;

	fatal('Please use only instance of class not static calls', 1) if ref $self ne __PACKAGE__;

	my $userName = shift || $self->{'username'};
	$self->{'username'} = $userName;

	if(! $userName) {
		error('No username was provided');
		return 1;
	}

	if(getpwnam($userName)) {
		my  @cmd = (
			"$main::imscpConfig{'CMD_USERDEL'}",
			($^O =~ /bsd$/ ? escapeShell($userName) : ''),
			'-r',
			($self->{'force'} ? '-f' : ''),
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

# Add unix user to a specific group
sub addToGroup
{
	my $self = shift;

	fatal('Please use only instance of class not static calls', 1) if ref $self ne __PACKAGE__;

	my $groupName = shift || $self->{'groupname'};
	$self->{'groupname'} = $groupName;

	my $userName = shift || $self->{'username'};
	$self->{'username'} = $userName;

	if(! $groupName) {
		error('No group name was provided');
		return 1;
	}

	if(! $userName) {
		error('No username was provided');
		return 1;
	}

	if(getgrnam($groupName) && getpwnam($userName)) {
		$self->getUserGroups($userName);

		if(! exists $self->{'userGroups'}->{$groupName}) {
			delete $self->{'userGroups'}->{$userName};

			my $newGroups = join(',', keys %{$self->{'userGroups'}});
			$newGroups = ($newGroups ne '') ? "$newGroups,$groupName" : $groupName;

			my  @cmd = (
				$main::imscpConfig{'CMD_USERMOD'},
				($^O =~ /bsd$/ ? escapeShell($userName) : ''),	# bsd way
				'-G', escapeShell($newGroups),
				($^O !~ /bsd$/ ? escapeShell($userName) : ''),	# linux way
			);
			my ($stdout, $stderr);
			my $rs = execute("@cmd", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			warning($stderr) if $stderr && ! $rs;

			return $rs if $rs;
		}
	}

	0;
}

# Retrieve list of all groups to which unix user is part
sub getUserGroups
{
	my $self = shift;

	fatal('Please use only instance of class not static calls', 1) if ref $self ne __PACKAGE__;

	my $userName = shift || $self->{'username'} || undef;
	$self->{'username'} = $userName;

	my ($rs, $stdout, $stderr);

	$rs = execute('/usr/bin/id -nG ' . escapeShell($userName), \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	warning($stderr) if $stderr && ! $rs;
	return $rs if $rs;

	%{$self->{'userGroups'}} = map { $_ => 1 } split ' ', $stdout;

	0;
}

# Remote unix user from a specific group
sub removeFromGroup
{
	my $self = shift;

	fatal(': Please use only instance of class not static calls', 1) if ref $self ne __PACKAGE__;

	my $groupName = shift || $self->{'groupname'} || undef;
	$self->{'groupname'} = $groupName;

	my $userName = shift || $self->{'username'} || undef;
	$self->{'username'} = $userName;

	if(! $groupName){
		error('No group name was provided');
		return 1;
	}

	if(! $userName){
		error('No username was provided');
		return 1;
	}

	if(getpwnam($userName)) {
		my ($rs, $stdout, $stderr);

		$self->getUserGroups($userName);
		delete $self->{'userGroups'}->{$groupName};

		my $newGroups =  join(',', keys %{$self->{'userGroups'}});
		my  @cmd = (
			'/usr/bin/skill -KILL -vu ' . $userName . '; ',
			"$main::imscpConfig{'CMD_USERMOD'}",
			($^O =~ /bsd$/ ? escapeShell($userName) : ''),	# bsd way
			'-G', escapeShell($newGroups),
			($^O !~ /bsd$/ ? escapeShell($userName) : ''),	# linux way
		);
		$rs = execute("@cmd", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		warning($stderr) if $stderr && ! $rs;

		return $rs if $rs;
	}

	0;
}

1;
