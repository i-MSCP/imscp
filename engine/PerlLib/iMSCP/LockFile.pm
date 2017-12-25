=head1 NAME

 iMSCP::LockFile - Implements file locks for locking files in UNIX.

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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

package iMSCP::LockFile;

use strict;
use warnings;
use Errno qw / ENOENT EWOULDBLOCK /;
use Fcntl qw/ :flock /;
use iMSCP::Debug qw/ debug /;
use parent 'Common::Object';

=head1 DESCRIPTION

 Implements file locks for locking files in UNIX.

 File locking strategy upon based on https://github.com/certbot/certbot/blob/master/certbot/lock.py

=head1 PUBLIC METHODS

=over 4

=item acquire( )

 Acquire the lock file

 Return int 1 if lock file has been acquired, 0 if lock file has not been acquired (non blocking)
 Die on failure

=cut

sub acquire
{
    my ($self) = @_;

    debug( sprintf( 'Acquiring exclusive lock on %s', $self->{'path'} ));

    while ( !$self->{'_fd'} ) {
        open my $fd, '>', $self->{'path'} or die( sprintf( "Couldn't open %s file", $self->{'path'} ));

        eval {
            return 0 unless $self->_tryLock( $fd );
            $self->{'_fd'} = $fd if $self->_lockSuccess( $fd );
            1;
        } or return 0;

        # Close the file if it is not the required one
        close( $fd ) unless $self->{'_fd'};
        die( $@ ) if $@
    }

    1;
}

=item release( )

 Remove, close, and release the lock file

 Die on failure

=cut

sub release
{
    my ($self) = @_;

    # Prevent lock from being released if the process is not the lock owner
    return unless $self->{'_owner'} == $$;

    debug( sprintf( 'Releasing exclusive lock on %s', $self->{'path'} ));

    # It is important the lock file is removed before it's released, otherwise:
    #
    # process A: open lock file
    # process B: release lock file
    # process A: lock file
    # process A: check device and inode
    # process B: delete file
    # process C: open and lock a different file at the same path
    unlink( $self->{'path'} ) or die( sprintf( "Couldn't unlink the %s file: %s", $self->{'path'}, $! ));
    close $self->{'_fd'};
    undef $self->{'_fd'};
}

=back

=head1 PRIVATE METHODS

=over 4

=item

 Initialize instance

 Return iMSCP::LockFile

=cut

sub _init
{
    my ($self) = @_;

    $self->{'path'} ||= '/var/lock/imscp.lock';
    $self->{'non_blocking'} ||= 0;
    $self->{'_fd'} = undef;
    $self->{'_owner'} = $$;
    $self;
}

=item _tryLock( $fd )

 Try to acquire the lock file

 Param int $fd file descriptor of the opened file to lock
 Return int 1 if lock file has been acquired, 0 if lock file has not been acquired (non blocking)
 Die on failure

=cut

sub _tryLock
{
    my ($self, $fd) = @_;

    return 1 if flock( $fd, LOCK_EX | ( $self->{'non_blocking'} ? LOCK_NB : 0 ));

    $!{'EWOULDBLOCK'} or die( sprintf( "Couldn't acquire exclusive lock on %s: %s", $self->{'path'}, $! ));
    debug( sprintf( "A lock on %s is held by another process.", $self->{'path'} ));
    0;
}

=item _lockSuccess( $fd )

 Did we really successfully grab the lock?

 Because we delete the locked file when the lock is released, it is possible
 another process removed and recreated the file between us opening the file and
 acquiring the lock.

 Param int $fd file descriptor of the opened file to lock
 Return TRUE if the lock was successfully acquired, FALSE otherwise
 Die on failure

=cut

sub _lockSuccess
{
    my ($self, $fd) = @_;

    my @stat1 = CORE::stat( $self->{'path'} );
    unless ( @stat1 ) {
        return if $!{'ENOENT'};
        die( sprintf( "Couldn't stats: %s", $! ));
    }

    my @stat2 = CORE::stat( $fd ) or die( sprintf( "Couldn't stats: %s", $! ));

    # If our locked file descriptor and the file on disk refer to
    # the same device and inode, they're the same file.
    $stat1[0] == $stat2[0] && $stat1[1] == $stat2[1];
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
