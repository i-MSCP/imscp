=head1 NAME

 iMSCP::Mount - Library for mounting/unmounting file systems

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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

# TODO Make direct syscalls instead of calling mount(8), e.g:
# syscall(&SYS_mount, ...)

package iMSCP::Mount;

use strict;
use warnings;
use File::Spec;
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::Execute;
use iMSCP::File;
use parent 'Exporter';
our @EXPORT_OK = qw/ mount umount addMountEntry removeMountEntry /;

=head1 DESCRIPTION

 Library for mounting/unmounting file systems.

=head1 FUNCTIONS

=over 4

=item mount(\%fields)

 Mount a file system

 Param hashref \%fields Hash describing filesystem to mount:
  - fs_spec:    Field describing the block special device or remote filesystem to be mounted
  - fs_file:    Field describing the mount point for the filesystem
  - fs_vfstype: Field describing the type of the filesystem
  - fs_mntops:  Field describing the mount options associated with the filesystem
 Return int 0 on success, other on failure

=cut

sub mount
{
    my $fields = shift;

    $fields = { } unless defined $fields && ref $fields eq 'HASH';

    for(qw/ fs_spec fs_file fs_spec fs_vfstype fs_mntops /) {
        next if defined $fields->{$_};
        error( sprintf( '`%s` field is not defined', $_ ) );
        return 1;
    }

    # Do not propagate changes made on this element outside of this scope
    local $fields->{'fs_mntops'} = $fields->{'fs_mntops'};

    my $fsSpec = File::Spec->canonpath( $fields->{'fs_spec'} );
    my $fsFile = File::Spec->canonpath( $fields->{'fs_file'} );

    my $rs = execute(
        'cat /proc/mounts | awk \'{print $2}\' | grep -q \'^'.quotemeta( $fsFile ).'$\'', undef, \ my $stderr
    );
    error( sprintf( 'Could not check mount point: %s', $stderr ) ) if $stderr;
    return 0 unless $rs;

    if (-f $fsSpec) {
        unless (-f $fsFile) {
            my $rs = iMSCP::File->new( filename => $fsFile )->save();
            return $rs if $rs;
        }
    } elsif (!-d $fsFile) {
        my $rs = iMSCP::Dir->new( dirname => $fsFile )->make();
        return $rs if $rs;
    }

    # Propagation flags (private, slave, shared, unbindable, rprivate, rslave, rshared, runbindable) passed as mount
    # options are not supported until util-linux 2.23. Thus, because we support Debian Wheezy, Ubuntu Precise/Trusty
    # which have older util-linux version, we must process them by additional mount(8) calls
    my (@propagationFlags) = $fields->{'fs_mntops'} =~ /,?(\br?(?:private|shared|slave|unbindable)\b)/g;
    $fields->{'fs_mntops'} =~ s/(\br?(?:private|shared|slave|unbindable)\b)(?:,|$)//g if @propagationFlags;

    my @commands;
    if ($fields->{'fs_mntops'} =~ /\bbind\b/) {
        # Passing mount options along with the `bind` mount option is not supported until mount(8) v2.27. Thus, we must
        # process them with an additional mount(8) call
        push @commands, [ 'mount', '--bind', $fsSpec, $fsFile ];
        if (index( $fields->{'fs_mntops'}, ',' ) != -1) {
            push @commands, [ 'mount', '-o', "remount,$fields->{'fs_mntops'}", $fsSpec, $fsFile ];
        }
    } else {
        push @commands, [ 'mount', '-t', $fields->{'fs_vfstype'}, '-o', $fields->{'fs_mntops'}, $fsSpec, $fsFile ];
    }

    push @commands, [ 'mount', "--make-$_", $fsFile ] for @propagationFlags;

    for(@commands) {
        $rs = execute( $_, \ my $stdout, \ $stderr );
        error( sprintf( 'Error while mounting %s on %s: %s', $fsFile, $fsFile, $stderr || 'Unknown error' ) ) if $rs;
        return $rs if $rs;
    }

    0;
}

=item umount($fsFile)

 Umount the given file system

 Note: In case of a partial mount point, any file systems below this mount point will be umounted.

 Param string $fsFile mount point of file system to umount
 Return int 0 on success, other on failure

=cut

sub umount
{
    my $fsFile = shift;

    unless (defined $fsFile) {
        error( '$fsFile parameter is not defined' );
        return 1;
    }

    # We do not trap errors here (expected for dangling mounts)
    execute(
        'cat /proc/mounts | awk \'{print $2}\' | grep --colour=never \'^'.quotemeta( File::Spec->canonpath( $fsFile ) ).
            '\(/\|$\)\' | sort -r | xargs -r umount -l',
        \ my $stdout,
        \ my $stderr
    );

    0;
}

=item addMountEntry($entry)

 Add the given mount entry in the i-MSCP fstab-like file

 Param string $entry Fstab-like entry to add
 Return int 0 on success, other on failure

=cut

sub addMountEntry
{
    my $entry = shift;

    unless (defined $entry) {
        error( '$entry parameter is not defined' );
        return 1;
    }

    my $rs = removeMountEntry( $entry );
    return $rs if $rs;

    my $fh;
    unless (open $fh, '>>', "$main::imscpConfig{'CONF_DIR'}/mounts/mounts.conf") {
        error( sprintf( 'Could not open `%s` file: %s', "$main::imscpConfig{'CONF_DIR'}/mounts/mounts.conf", $! ) );
    }

    print {$fh} "$entry\n";
    close $fh;
    0;
}

=item removeMountEntry($entry)

 Remove the given mount entry from the i-MSCP fstab-like file

 Param string|regexp $entry String or regexp representing Fstab-like entry to remove
 Return int 0 on success, other on failure

=cut

sub removeMountEntry
{
    my $entry = shift;

    unless (defined $entry) {
        error( '$entry parameter is not defined' );
        return 1;
    }

    my $file = "$main::imscpConfig{'CONF_DIR'}/mounts/mounts.conf";
    $entry = quotemeta( $entry ) unless ref $entry eq 'Regexp';
    eval {
        local ($@, $_, $SIG{'__WARN__'}, $^I, @ARGV) = (undef, undef, sub { die shift }, '', $file);
        while(<>) {
            s/^$entry\n//;
            print;
        }
    };
    if ($@) {
        error( sprintf( 'Could not remove entry matching with `%s` in `%s` file: %s', $entry, $file, $! ) );
        return 1;
    }

    0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
