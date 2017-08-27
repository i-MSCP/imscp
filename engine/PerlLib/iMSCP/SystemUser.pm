=head1 NAME

 iMSCP::SystemUser - i-MSCP library for management of UNIX users

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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
use iMSCP::Debug qw/ debug error /;
use iMSCP::Execute qw/ execute /;
use iMSCP::Ext2Attributes qw/ clearImmutable isImmutable setImmutable /;
use parent 'Common::Object';

=head1 DESCRIPTION

 i-MSCP library for management of UNIX users.

=head1 PUBLIC METHODS

=over 4

=item addSystemUser( [ $username = $self->{'username'} [, $newGroupname = undef ] ] )

 Add UNIX user

 Param string $username Username
 Param string $username $newGroupname New group name of the user's initial login group (user update only)
 Return int 0 on success, other on failure

=cut

sub addSystemUser
{
    my ($self, $username, $newGroupname ) = @_;

    $username //= $self->{'username'};
    my $oldUsername = $self->{'username'} // $username;

    unless ( defined $username ) {
        error( 'Missing $username parameter' );
        return 1;
    }

    if ( $username eq $main::imscpConfig{'ROOT_USER'} ) {
        error( sprintf( '%s user is prohibited', $main::imscpConfig{'ROOT_USER'} ));
        return 1;
    }

    $self->{'username'} = $username;

    my $home = $self->{'home'} // "$main::imscpConfig{'USER_WEB_DIR'}/$username";
    my $isImmutableHome = -d $home && isImmutable( $home );

    clearImmutable( $home ) if $isImmutableHome;

    my @userProps = getpwnam( $oldUsername );
    my @commands;

    unless ( @userProps ) {
        push @commands,
            [
                [
                    '/usr/sbin/useradd',
                    ( defined $self->{'password'} ? ( '-p', $self->{'password'} ) : () ),
                    '-c', $self->{'comment'} // 'i-MSCP user',
                    '-d', $home,
                    ( $self->{'skipCreateHome'} ? () : '-m' ),
                    ( $self->{'system'} || $self->{'skipCreateHome'}
                        ? () : ( '-k', $self->{'skeletonPath'} // '/etc/skel' )
                    ),
                    ( $self->{'skipGroup'} || defined $self->{'group'} ? () : '-U' ),
                    ( !$self->{'skipGroup'} && defined $self->{'group'} ? ( '-g', $self->{'group'} ) : () ),
                    ( $self->{'system'} ? '-r' : () ),
                    '-s', ( $self->{'shell'} // '/bin/false' ),
                    $username
                ],
                [ 0, 12 ]
            ];
    } else {
        if ( $userProps[2] == 0 ) {
            error( sprintf( '%s user modification is prohibited', $main::imscpConfig{'ROOT_USER'} ));
            return 1;
        }

        # If we attempt to modify user' login or home, we must ensure
        # that there is no process running for the user
        if ( $username ne $oldUsername || $home ne $userProps[7] ) {
            push @commands, [ [ '/usr/bin/pkill', '-KILL', '-u', $userProps[2] ], [ 0, 1 ] ];
            $isImmutableHome = -d $userProps[7] && isImmutable( $userProps[7] );
            clearImmutable( $userProps[7] ) if $isImmutableHome;
        }

        my $usermodCmd = [
            '/usr/sbin/usermod',
            ( defined $self->{'password'} ? ( '-p', $self->{'password'} ) : () ),
            ( defined $self->{'comment'} && $self->{'comment'} ne $userProps[6]
                ? ( '-c', $self->{'comment'} // 'iMSCP user' ) : () ),
            ( defined $self->{'group'} && ( ( $self->{'group'} =~ /^(\d+)$/ && $1 != $userProps[3] )
                    || getgrnam( $self->{'group'} ) ne $userProps[3] )
                ? ( '-g', $self->{'group'} ) : () ),
            ( defined $self->{'home'} && $self->{'home'} ne $userProps[7]
                ? ( '-d', $self->{'home'} // "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'username'}", '-m' ) : () ),
            ( defined $self->{'shell'} && $self->{'shell'} ne $userProps[8] ? ( '-s', $self->{'shell'} ) : () ),
            ( $username ne $oldUsername ? ( '-l', $username ) : () ),
            $oldUsername,
        ];

        push @commands, [ $usermodCmd, [ 0 ] ] if @{$usermodCmd} > 2;
    }

    for( @commands ) {
        my $rs = execute( $_->[0], \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        unless ( grep($_ == $rs, @{$_->[1]}) ) {
            error( $stderr || 'Unknown error' );
            return $rs;
        }
    }

    if ( @userProps && $oldUsername ne $username && defined $newGroupname ) {
        my $rs = execute(
            [ '/usr/sbin/groupmod', '-n', $newGroupname, scalar getgrgid( $userProps[3] ) ], \ my $stdout, \ my $stderr
        );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs && $rs;
    }

    setImmutable( $home ) if $isImmutableHome;
    0;
}

=item delSystemUser( [ $username = $self->{'username'} ] )

 Delete UNIX user

 Param string $username Username
 Return int 0 on success, other on failure

=cut

sub delSystemUser
{
    my ($self, $username) = @_;
    $username //= $self->{'username'};

    unless ( defined $username ) {
        error( '$username parameter is not defined' );
        return 1;
    }

    if ( $username eq $main::imscpConfig{'ROOT_USER'} ) {
        error( sprintf( '%s user deletion is prohibited', $main::imscpConfig{'ROOT_USER'} ));
        return 1;
    }

    $self->{'username'} = $username;

    return 0 unless my @userProps = getpwnam( $username );

    clearImmutable( $userProps[7] ) if -d $userProps[7] && isImmutable( $userProps[7] );

    my @commands = (
        # Delete user' CRON(8) jobs
        [ [ '/usr/bin/crontab', '-r', '-u', $username ], [ 0, 1 ] ],
        # Delete any user' AT(1) jobs
        [ [ '/usr/bin/find', '/var/spool/cron/atjobs', '-type', 'f', '-user', $username, '-delete' ], [ 0 ] ],
        # Remove user' LPQ(1) jobs
        ( -x '/usr/bin/lprm' ? [ [ '/usr/bin/lprm', $username ], [ 0 ] ] : () ),
        # Kill user' processes
        [ [ '/usr/bin/pkill', '-KILL', '-u', $username ], [ 0, 1 ] ],
        # Remove user
        [
            [
                '/usr/sbin/userdel',
                ( $self->{'keepHome'} ? '' : '-r' ),
                ( $self->{'force'} && !$self->{'keepHome'} ? '-f' : '' ),
                $username
            ],
            [ 0, 6, 12 ]
        ]
    );

    for( @commands ) {
        my $rs = execute( $_->[0], \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        unless ( grep($_ == $rs, @{$_->[1]}) ) {
            error( $stderr || 'Unknown error' );
            return $rs;
        }
    }

    0;
}

=item addToGroup( [ $groupname = $self->{'groupname'} [, $username = $self->{'username'} ] ] )

 Add given UNIX user to the given UNIX group

 Param string $groupname Group name
 Param string $username Username
 Return int 0 on success, other on failure

=cut

sub addToGroup
{
    my ($self, $groupname, $username) = @_;

    $groupname //= $self->{'groupname'};
    $username //= $self->{'username'};

    unless ( defined $groupname ) {
        error( 'Missing $groupname parameter' );
        return 1;
    }
    unless ( defined $username ) {
        error( 'Missing $username parameter' );
        return 1;
    }

    $self->{'groupname'} = $groupname;
    $self->{'username'} = $username;

    unless ( getgrnam( $groupname ) && getpwnam( $username ) ) {
        error( 'Invalid group or username' );
        return 1;
    }

    my $rs = execute( [ '/usr/bin/gpasswd', '-a', $username, $groupname ], \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs && $rs != 3;
    return $rs if $rs && $rs != 3;
    0;
}

=item removeFromGroup( [ $groupname = $self->{'groupname'} [, $username = $self->{'username'} ] ] )

 Remove given UNIX user from the given UNIX group

 Param string $groupname Group name
 Param string $username Username
 Return int 0 on success, other on failure

=cut

sub removeFromGroup
{
    my ($self, $groupname, $username) = @_;
    $groupname //= $self->{'groupname'};
    $username //= $self->{'username'};

    unless ( defined $groupname ) {
        error( 'Missing $groupname parameter' );
        return 1;
    }
    unless ( defined $username ) {
        error( 'Missing $username parameter' );
        return 1;
    }

    $self->{'groupname'} = $groupname;
    $self->{'username'} = $username;

    return 0 unless getpwnam( $username ) && getgrnam( $groupname );

    my $rs = execute( [ '/usr/bin/gpasswd', '-d', $username, $groupname ], \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs && $rs != 3;
    return $rs if $rs && $rs != 3;
    0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
