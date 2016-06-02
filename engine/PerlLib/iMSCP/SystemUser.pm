=head1 NAME

 iMSCP::SystemUser - i-MSCP library that allows to add/update/elete UNIX users

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by internet Multi Server Control Panel
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

package iMSCP::SystemUser;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Execute;
use parent 'Common::Object';

=head1 DESCRIPTION

 i-MSCP library that allows to add/update/elete UNIX users.

=head1 PUBLIC METHODS

=over 4

=item addSystemUser([ $username = $self->{'username'} ])

 Add UNIX user

 Param string $username Username
 Return int 0 on success, other on failure

=cut

sub addSystemUser
{
    my $self = shift;
    my $username = shift || $self->{'username'};

    unless (defined $username) {
        error( '$username parameter is not defined' );
        return 1;
    }

    $self->{'username'} = $username;

    my $password = $self->{'password'} ? '-p '.escapeShell( $self->{'password'} ) : '';
    my $comment = $self->{'comment'} ? $self->{'comment'} : 'iMSCPuser';
    my $home = $self->{'home'} ? $self->{'home'} : "$main::imscpConfig{'USER_WEB_DIR'}/$username";
    my $skipGroup = $self->{'skipGroup'} || $self->{'group'} ? '' : '-U';
    my $group = $self->{'group'} ? '-g '.escapeShell( $self->{'group'} ) : '';
    my $createHome = $self->{'skipCreateHome'} ? '' : '-m';
    my $systemUser = $self->{'system'} ? '-r' : '';
    my $copySkeleton = $self->{'system'} || $self->{'skipCreateHome'} ? '' : '-k';
    my $skeletonPath = $self->{'system'} || $self->{'skipCreateHome'} ? '' : $self->{'skeletonPath'} || '/etc/skel';
    my $shell = $self->{'shell'} ? $self->{'shell'} : '/bin/false';

    my @cmd;
    unless (getpwnam( $username )) { # Creating new user
        @cmd = (
            'useradd',
            $^O =~ /bsd$/ ? escapeShell( $username ) : '', # username bsd way
            $password, # Password
            '-c', escapeShell( $comment ), # comment
            '-d', escapeShell( $home ), # homedir
            $skipGroup, # create group with same name and add user to group
            $group, # user initial connexion group
            $createHome, # create home dir
            $copySkeleton, escapeShell( $skeletonPath ), # copy skeleton dir
            $systemUser, # system account
            '-s', escapeShell( $shell ), # shell
            $^O !~ /bsd$/ ? escapeShell( $username ) : ''    # username linux way
        );
    } else { # Modify existent user
        @cmd = (
            'pkill -KILL -u', escapeShell( $username ), ';',
            'usermod',
            ($^O =~ /bsd$/ ? escapeShell( $username ) : ''), # username bsd way
            $password, # Password
            '-c', escapeShell( $comment ), # comment
            '-d', escapeShell( $home ), # homedir
            '-m', # Move current home content in new home if needed
            '-s', escapeShell( $shell ), # shell
            $^O !~ /bsd$/ ? escapeShell( $username ) : ''    # username linux way
        );
    }

    my $rs = execute( "@cmd", \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr && $rs && $rs != 12;
    debug( $stderr ) if $stderr && !$rs;
    return $rs if $rs && $rs != 12;
    0;
}

=item delSystemUser([ $username = $self->{'username'} ])

 Delete UNIX user

 Param string $username Username
 Return int 0 on success, other on failure

=cut

sub delSystemUser
{
    my $self = shift;
    my $username = shift || $self->{'username'};

    unless (defined $username) {
        error( '$username parameter is not defined' );
        return 1;
    }

    $self->{'username'} = $username;

    return 0 unless getpwnam( $username );

    my @cmd = (
        'pkill -KILL -u', escapeShell( $username ), ';',
        'userdel',
            $^O =~ /bsd$/ ? escapeShell( $username ) : '',
            $self->{'keepHome'} ? '' : '-r',
            $self->{'force'} && !$self->{'keepHome'} ? '-f' : '',
            $^O !~ /bsd$/ ? escapeShell( $username ) : ''
    );
    my $rs = execute( "@cmd", \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr && $rs && $rs != 12;
    debug( $stderr ) if $stderr && !$rs;
    return $rs if $rs && $rs != 12;
    0;
}

=item addToGroup([ $groupname =  $self->{'groupname'} [, $username = $self->{'username'} ] ])

 Add given UNIX user to the given UNIX group

 Param string $groupname Group name
 Param string $username Username
 Return int 0 on success, other on failure

=cut

sub addToGroup
{
    my $self = shift;
    my $groupname = shift || $self->{'groupname'};
    my $username = shift || $self->{'username'};

    unless (defined $groupname) {
        error( '$groupname parameter is not defined' );
        return 1;
    }
    unless (defined $username) {
        error( '$username parameter is not defined' );
        return 1;
    }

    $self->{'groupname'} = $groupname;
    $self->{'username'} = $username;

    return 0 unless getgrnam( $groupname ) && getpwnam( $username );

    if ($^O =~ /bsd$/) {
        # bsd
        $self->getUserGroups( $username );

        return 0 unless exists $self->{'userGroups'}->{$groupname};

        delete $self->{'userGroups'}->{$username};

        my $newGroups = join( ',', keys %{$self->{'userGroups'}} );
        $newGroups = ($newGroups ne '') ? "$newGroups,$groupname" : $groupname;
        my @cmd = ('usermod', escapeShell( $username ), '-G', escapeShell( $newGroups ));
        my $rs = execute( "@cmd", \my $stdout, \my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr ) if $stderr && $rs;
        debug( $stderr ) if $stderr && !$rs;
        return $rs;
    }

    # Linux
    my @cmd = ('gpasswd', '-a', escapeShell( $username ), escapeShell( $groupname ));
    my $rs = execute( "@cmd", \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr && $rs && $rs != 3;
    debug( $stderr ) if $stderr && !$rs;
    return $rs if $rs && $rs != 3;
    0;
}

=item addToGroup([ $groupname = $self->{'groupname'} [, $username = $self->{'username'} ] ])

 Remove given UNIX user from the given UNIX group

 Param string $groupname Group name
 Param string $username Username
 Return int 0 on success, other on failure

=cut

sub removeFromGroup
{
    my $self = shift;
    my $groupname =  shift || $self->{'groupname'};
    my $username = shift || $self->{'username'};

    unless (defined $groupname) {
        error( '$groupname parameter is not defined' );
        return 1;
    }
    unless (defined $username) {
        error( '$username parameter is not defined' );
        return 1;
    }

    $self->{'groupname'} = $groupname;
    $self->{'username'} = $username;

    return 0 unless getpwnam( $username ) && getgrnam( $groupname );

    if ($^O =~ /bsd$/) {
        # bsd way
        $self->getUserGroups( $username );

        delete $self->{'userGroups'}->{$groupname};
        delete $self->{'userGroups'}->{$username};

        my $newGroups = join( ',', keys %{$self->{'userGroups'}} );
        my @cmd = ('usermod', escapeShell( $username ), '-G', escapeShell( $newGroups ));
        my $rs = execute( "@cmd", \my $stdout, \my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr ) if $stderr && $rs;
        debug( $stderr ) if $stderr && !$rs;
        return $rs;
    }

    my @cmd = ('gpasswd', '-d', escapeShell( $username ), escapeShell( $groupname ));
    my $rs = execute( "@cmd", \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr && $rs && $rs != 3;
    debug( $stderr ) if $stderr && !$rs;
    return $rs if $rs && $rs != 3;
    0;
}


=item addToGroup( [ $username = $self->{'username'} ] )

 Get list of group to wich given UNIX user belongs

 Param string $username Username
 Return int 0 on success, other on failure

=cut

sub getUserGroups
{
    my $self = shift;
    my $username = shift || $self->{'username'};

    unless (defined $username) {
        error( '$username parameter is not defined' );
        return 1;
    }

    $self->{'username'} = $username;

    my $rs = execute( 'id -nG '.escapeShell( $username ), \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr && $rs;
    debug( $stderr ) if $stderr && !$rs;
    return $rs if $rs;

    %{$self->{'userGroups'}} = map { $_ => 1 } split ' ', $stdout;
    0;
}

=back

=head1 AUTHOR

 i-MSCP Team <team@i-mscp.net>

=cut

1;
__END__
