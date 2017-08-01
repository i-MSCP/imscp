=head1 NAME

 iMSCP::Mount - Library for mounting/unmounting file systems

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

package iMSCP::Mount;

use strict;
use warnings;
use Errno qw / EINVAL /;
use File::Spec;
use File::stat ();
use iMSCP::Debug;
use iMSCP::File;
use iMSCP::Syscall;
use Scalar::Defer;
use parent 'Exporter';

our @EXPORT_OK = qw/ addMountEntry getMounts isMountpoint mount setPropagationFlag removeMountEntry umount /;

# These are the fs-independent mount-flags (see sys/mount.h)
# See http://man7.org/linux/man-pages/man2/mount.2.html for a description of these flags
use constant {
    # These are the fs-independent mount-flags
    MS_RDONLY      => 1, # Mount read-only.
    MS_NOSUID      => 2, # Ignore suid and sgid bits.
    MS_NODEV       => 4, # Disallow access to device special files.
    MS_NOEXEC      => 8, # Disallow program execution.
    MS_SYNCHRONOUS => 16, # Writes are synced at once.
    MS_REMOUNT     => 32, # Alter flags of a mounted FS.
    MS_MANDLOCK    => 64, # Allow mandatory locks on an FS.
    MS_DIRSYNC     => 128, # Directory modifications are synchronous.
    MS_NOATIME     => 1024, # Do not update access times.
    MS_NODIRATIME  => 2048, # Do not update directory access times.
    MS_BIND        => 4096, # Bind directory at different place.
    MS_MOVE        => 8192, # Move a subtree.
    MS_REC         => 16384, # Recursive loopback.
    MS_SILENT      => 32768, # Be quiet.
    MS_POSIXACL    => 1 << 16, # VFS does not apply the umask.
    MS_UNBINDABLE  => 1 << 17, # Change to unbindable.
    MS_PRIVATE     => 1 << 18, # Change to private.
    MS_SLAVE       => 1 << 19, # Change to slave.
    MS_SHARED      => 1 << 20, # Change to shared.
    MS_RELATIME    => 1 << 21, # Update atime relative to mtime/ctime.
    MS_KERNMOUNT   => 1 << 22, # This is a kern_mount call.
    MS_I_VERSION   => 1 << 23, # Update inode I_version field.
    MS_STRICTATIME => 1 << 24, # Always perform atime updates.
    MS_LAZYTIME    => 1 << 25 # Update the time lazily. (since Linux 4.0)
};
use constant {
    # Flags that can be altered by MS_REMOUNT (see sys/mount.h)
    MS_RMT_MASK     => ( MS_RDONLY | MS_SYNCHRONOUS | MS_MANDLOCK | MS_I_VERSION ),

    # Magic mount flag number. Has to be or-ed to the flag values. (see sys/mount.h)
    MS_MGC_VAL      => 0xc0ed0000, # Magic flag number to indicate "new" flags
    MS_MGC_MSK      => 0xffff0000, # Magic flag number mask

    # Possible value for FLAGS parameter of `umount2' (see sys/mount.h)
    MNT_FORCE       => 1,
    MNT_DETACH      => 2,
    MNT_EXPIRE      => 4,
    UMOUNT_NOFOLLOW => 8
};

# Mount options
# List taken from libmount/src/optmap.c (util-linux 2.25.2)
my %MOUNT_FLAGS = (
    defaults      => sub { 0 },
    bind          => sub { $_[0] | MS_BIND },
    rbind         => sub { $_[0] | MS_BIND | MS_REC },
    ro            => sub { $_[0] | MS_RDONLY },
    rw            => sub { $_[0] & ~MS_RDONLY },
    exec          => sub { $_[0] & ~MS_NOEXEC },
    noexec        => sub { $_[0] | MS_NOEXEC },
    suid          => sub { $_[0] & ~MS_NOSUID },
    nosuid        => sub { $_[0] | MS_NOSUID },
    dev           => sub { $_[0] & ~MS_NODEV },
    nodev         => sub { $_[0] | MS_NODEV },
    sync          => sub { $_[0] | MS_SYNCHRONOUS },
    async         => sub { $_[0] & ~MS_SYNCHRONOUS },
    dirsync       => sub { $_[0] | MS_DIRSYNC },
    remount       => sub { $_[0] | MS_REMOUNT },
    silent        => sub { $_[0] | MS_SILENT },
    loud          => sub { $_[0] & ~MS_SILENT },
    move          => sub { $_[0] | MS_MOVE },
    mand          => sub { $_[0] | MS_MANDLOCK },
    nomand        => sub { $_[0] & ~MS_MANDLOCK },
    atime         => sub { $_[0] & ~MS_NOATIME },
    noatime       => sub { $_[0] | MS_NOATIME },
    iversion      => sub { $_[0] | MS_I_VERSION },
    noiversion    => sub { $_[0] & ~MS_I_VERSION },
    diratime      => sub { $_[0] & ~MS_NODIRATIME },
    nodiratime    => sub { $_[0] | MS_NODIRATIME },
    relatime      => sub { $_[0] | MS_RELATIME },
    norelatime    => sub { $_[0] & ~MS_RELATIME },
    strictatime   => sub { $_[0] | MS_STRICTATIME },
    nostrictatime => sub { $_[0] & ~MS_STRICTATIME },
    lazytime      => sub { $_[0] & ~MS_LAZYTIME }
);

# Propagation flags
# List taken from libmount/src/optmap.c (util-linux 2.25.2)
my %PROPAGATION_FLAGS = (
    unbindable  => sub { $_[0] | MS_UNBINDABLE },
    runbindable => sub { $_[0] | MS_UNBINDABLE | MS_REC },
    private     => sub { $_[0] | MS_PRIVATE },
    rprivate    => sub { $_[0] | MS_PRIVATE | MS_REC },
    slave       => sub { $_[0] | MS_SLAVE },
    rslave      => sub { $_[0] | MS_SLAVE | MS_REC },
    shared      => sub { $_[0] | MS_SHARED },
    rshared     => sub { $_[0] | MS_SHARED | MS_REC }
);

# Lazy-load mount entries
my $MOUNTS = lazy
    {
        -f '/proc/self/mounts' or die( "Couldn't load mount entries. File /proc/self/mounts not found." );
        open my $fh, '<', '/proc/self/mounts' or die( sprintf( "Couldn't read /proc/self/mounts file: %s", $! ));
        my $entries;
        while ( my $entry = <$fh> ) {
            my $fsFile = ( split /\s+/, $entry )[1];
            $entries->{$fsFile =~ s/\\040\(deleted\)$//r}++;
        }
        close( $fh );
        $entries;
    };

# FH object to i-MSCP fstab-like file
my $iMSCP_FSTAB_FH;

=head1 DESCRIPTION

 Library for mounting/unmounting file systems.

=head1 PUBLIC FUNCTIONS

 Get list of mounts

 Return List of mounts (duplicate mounts are discarded)

=over 4

=item getMounts( )

=cut

sub getMounts
{
    reverse sort keys %{$MOUNTS};
}

=item mount( \%fields )

 Create a new mount, or remount an existing mount, or/and change the propagation type of an existing mount

 Param hashref \%fields Hash describing filesystem to mount:
  - fs_spec         : Field describing the block special device or remote filesystem to be mounted
  - fs_file         : Field describing the mount point for the filesystem
  - fs_vfstype      : Field describing the type of the filesystem
  - fs_mntops       : Field describing the mount options associated with the filesystem
  - ignore_failures : Flag allowing to ignore mount operation failures
 Return int 0 on success, other on failure

=cut

sub mount( $ )
{
    my ($fields) = @_;
    $fields = {} unless defined $fields && ref $fields eq 'HASH';

    for( qw/ fs_spec fs_file fs_vfstype fs_mntops / ) {
        next if defined $fields->{$_};
        error( sprintf( "`%s' field is not defined", $_ ));
        return 1;
    }

    force $MOUNTS; # Force loading of mount entries if not already done
    my $fsSpec = File::Spec->canonpath( $fields->{'fs_spec'} );
    my $fsFile = File::Spec->canonpath( $fields->{'fs_file'} );
    my $fsVfstype = $fields->{'fs_vfstype'};

    debug( "$fsSpec $fsFile $fsVfstype $fields->{'fs_mntops'}" );

    my ($mflags, $pflags, $data) = _parseOptions( $fields->{'fs_mntops'} );
    $mflags |= MS_MGC_VAL unless $mflags & MS_MGC_MSK;

    my @mountArgv;

    if ( $mflags & MS_BIND ) {
        # Create a bind mount or remount an existing bind mount
        push @mountArgv, [ $fsSpec, $fsFile, $fsVfstype, $mflags, $data ];

        # If MS_REMOUNT was not specified, and if there are mountflags other
        # than MS_BIND and MS_REC, schedule an additional mount(2) call to
        # change mountflags on existing mount. This is needed since mountflags
        # other than MS_BIND and MS_REC are ignored in first call.
        if ( !( $mflags & MS_REMOUNT ) && ( $mflags & ~( MS_BIND | MS_REC ) ) ) {
            push @mountArgv, [ $fsSpec, $fsFile, $fsVfstype, MS_REMOUNT | $mflags, $data ];
        }
    } elsif ( $fsSpec ne 'none' ) {
        # Create a new mount or remount an existing mount
        push @mountArgv, [ $fsSpec, $fsFile, $fsVfstype, $mflags, $data ];
    }

    # Change the propagation type of an existing mount
    push @mountArgv, [ 'none', $fsFile, 0, $pflags, 0 ] if $pflags;

    # Process the mount(2) calls
    for( @mountArgv ) {
        unless ( syscall( &iMSCP::Syscall::SYS_mount, @{$_} ) == 0 || $fields->{'ignore_failures'} ) {
            error( sprintf( 'Error while executing mount(%s): %s', join( ', ', @{$_} ), $! || 'Unknown error' ));
            return 1;
        }
    }

    $MOUNTS->{$fsFile}++ unless $mflags & MS_REMOUNT;
    0;
}

=item umount( $fsFile [, $recursive = TRUE ] )

 Umount the given file system

 Note: When umount operation is recursive, any mount below the given mount (or directory) will be umounted.

 Param string $fsFile Mount point of file system to umount
 Param bool $recursive Whether or not umount operation must be recursive (default: true)
 Return int 0 on success, other on failure

=cut

sub umount( $;$ )
{
    my ($fsFile, $recursive) = @_;

    unless ( defined $fsFile ) {
        error( '$fsFile parameter is not defined' );
        return 1;
    }

    $recursive //= 1; # Operation is recursive by default
    $fsFile = File::Spec->canonpath( $fsFile );

    return 0 if $fsFile eq '/'; # Prevent umounting root fs

    unless ( $recursive ) {
        return 0 unless $MOUNTS->{$fsFile};

        do {
            debug( $fsFile );
            unless ( syscall( &iMSCP::Syscall::SYS_umount2, $fsFile,
                MNT_DETACH ) == 0 || $!{'EINVAL'} || $!{'ENOENT'} ) {
                error( sprintf( "Error while executing umount(%s): %s", $fsFile, $! || 'Unknown error' ));
                return 1;
            }
            ( $MOUNTS->{$fsFile} > 1 ) ? $MOUNTS->{$fsFile}-- : delete $MOUNTS->{$fsFile};
        } while $MOUNTS->{$fsFile};

        return 0;
    }

    for( reverse sort keys %{$MOUNTS} ) {
        next unless /^\Q$fsFile\E(\/|$)/;

        do {
            debug( $_ );
            unless ( syscall( &iMSCP::Syscall::SYS_umount2, $_, MNT_DETACH ) == 0 || $!{'EINVAL'} || $!{'ENOENT'} ) {
                error( sprintf( "Error while executing umount(%s): %s", $_, $! || 'Unknown error' ));
                return 1;
            }
            ( $MOUNTS->{$_} > 1 ) ? $MOUNTS->{$_}-- : delete $MOUNTS->{$_};
        } while $MOUNTS->{$_};
    }

    0;
}

=item setPropagationFlag( $fsFile [, $flag = 'private' ] )

 Change the propagation type of an existing mount

 Parameter string $fsFile Mount point
 Parameter string $flag Propagation flag as string (private,slave,shared,unbindable,rprivate,rslave,rshared,runbindable)

=cut

sub setPropagationFlag($;$)
{
    my ($fsFile, $pflag) = @_;
    $pflag ||= 'private';

    unless ( defined $fsFile ) {
        error( '$fsFile parameter is not defined' );
        return 1;
    }

    $fsFile = File::Spec->canonpath( $fsFile );

    debug( "$fsFile $pflag" );

    ( undef, $pflag ) = _parseOptions( $pflag );
    unless ( $pflag ) {
        error( 'Invalid propagation flags' );
        return 1;
    }

    my $src = 'none';
    unless ( syscall( &iMSCP::Syscall::SYS_mount, $src, $fsFile, 0, $pflag, 0 ) == 0 ) {
        error( sprintf( 'Error while changing propagation flag on %s: %s', $fsFile, $! || 'Unknown error' ));
        return 1;
    }

    0;
}

=item isMountpoint( $path )

 Is the given path a mountpoint or bind mount?
 
 See also mountpoint(1)

 Param string $path Path to test
 Return bool TRUE if $path look like a mount point, FALSE otherwise

=cut

sub isMountpoint($)
{
    my $path = shift;

    unless ( defined $path ) {
        error( '$path parameter is not defined' );
        return 1;
    }

    $path = File::Spec->canonpath( $path );

    return 1 if $MOUNTS->{$path};
    return 0 unless -d $path;

    my $st = File::stat::populate( CORE::stat( _ ));
    my $st2 = File::stat::stat( "$path/.." );
    ( $st->dev != $st2->dev ) || ( $st->dev == $st2->dev && $st->ino == $st2->ino );
}

=item addMountEntry( $entry )

 Add the given mount entry in the i-MSCP fstab-like file

 Param string $entry Fstab-like entry to add
 Return int 0 on success, other on failure

=cut

sub addMountEntry( $ )
{
    my $entry = shift;

    unless ( defined $entry ) {
        error( '$entry parameter is not defined' );
        return 1;
    }

    my $rs = removeMountEntry( $entry, 0 ); # Avoid duplicate entries
    return $rs if $rs;

    my $fileContent = $iMSCP_FSTAB_FH->getAsRef();
    ${$fileContent} .= "$entry\n";
    $iMSCP_FSTAB_FH->save();
}

=item removeMountEntry( $entry [, $saveFile = true ] )

 Remove the given mount entry from the i-MSCP fstab-like file

 Param string|regexp $entry String or regexp representing Fstab-like entry to remove
 Param boolean $saveFile Flag indicating whether or not file must be saved
 Return int 0 on success, other on failure

=cut

sub removeMountEntry( $;$ )
{
    my ($entry, $saveFile) = @_;
    $saveFile //= 1;

    unless ( defined $entry ) {
        error( '$entry parameter is not defined' );
        return 1;
    }

    unless ( $iMSCP_FSTAB_FH ) {
        $iMSCP_FSTAB_FH = iMSCP::File->new( filename => "$main::imscpConfig{'CONF_DIR'}/mounts/mounts.conf" );
    }

    my $fileContent = $iMSCP_FSTAB_FH->getAsRef();
    unless ( defined $fileContent ) {
        error( sprintf( "Couldn't read %s file", $iMSCP_FSTAB_FH->{'filename'} ));
        return 1;
    }

    $entry = quotemeta( $entry ) unless ref $entry eq 'Regexp';
    ${$fileContent} =~ s/^$entry\n//gm;
    $saveFile ? $iMSCP_FSTAB_FH->save() : 0;
}

=back

=head1 PRIVATE FUNCTIONS

=over 4

=item _parseOptions( $options )

 Parse mountflags, propagation flags and data

 Param string $options String containing options, each comma separated
 Return list List containing mount flags, propagation flags and data

=cut

sub _parseOptions( $ )
{
    my $options = shift;

    # Turn options string into option list
    my @options = map { s/\s+//gr } split ',', $options;

    # Parse mount flags (excluding any propagation flag)
    my ($mflags, @roptions) = ( 0 );
    for ( @options ) {
        push( @roptions, $_ ) && next unless exists $MOUNT_FLAGS{$_};
        $mflags = $MOUNT_FLAGS{$_}->( $mflags );
    }

    # Parse propagation flags
    my ($pflags, @data) = ( 0 );
    for ( @roptions ) {
        push( @data, $_ ) && next unless exists $PROPAGATION_FLAGS{$_};
        $pflags = $PROPAGATION_FLAGS{$_}->( $pflags );
    }

    ( $mflags, $pflags, ( @data ) ? join ',', @data : 0 );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
