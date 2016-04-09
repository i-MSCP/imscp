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

=item mount(\%options)

 Mount a file system according the given options

 Param hash \%options Hash describing mount option:
   fs_spec: This option describes the block special device or remote filesystem to be mounted.
   fs_file: This option describes the mount point for the filesystem.
   fs_vfstype: This option describes the type of the filesystem.
   fs_mntops: This option describes the mount options associated with the filesystem.
 Return int 0 on success, other on failure

=cut

sub mount
{
    my $options = shift;

    $options = { } unless defined $options && ref $options eq 'HASH';

    for(qw/ fs_spec fs_file fs_spec fs_vfstype fs_mntops /) {
        next if defined $options->{$_};
        error( sprintf( 'The %s option is not defined', $_ ) );
        return 1;
    }

    my $fsSpec = File::Spec->canonpath( $options->{'fs_spec'} );
    my $fsFile = File::Spec->canonpath( $options->{'fs_file'} );

    return 0 unless execute( "mount 2>/dev/null | grep -q ".escapeShell( " on $fsFile " ) );

    if (-f $fsSpec) {
        my $rs = iMSCP::File->new( filename => $fsFile )->save();
        return $rs if $rs;
    } elsif (!-d $fsFile) {
        my $rs = iMSCP::Dir->new( dirname => $fsFile )->make();
        return $rs if $rs;
    }

    my @cmdArgs = (
        '-t', $options->{'fs_vfstype'},
        '-o', escapeShell( $options->{'fs_mntops'} ),
        escapeShell( $fsSpec ),
        escapeShell( $fsFile )
    );

    my $rs = execute( "mount @cmdArgs", \my $stdout, \my $stderr );
    error( sprintf( 'Could not mount %s on %s: %s', $fsFile, $fsFile, $stderr || 'Unknown error' ) ) if $rs;
    return $rs if $rs;

    return 0 unless $options->{'fs_mntops'} =~ /(r?(?:shared|private|slave|unbindable))/;

    # handle shared subtrees operations
    $rs = execute( "mount --make-$1 $fsFile", \$stdout, \$stderr );
    error( sprintf( 'Could not make %s a %s subtree: %s', $fsFile, $1, $stderr || 'Unknown error' ) ) if $rs;
    $rs;
}

=item umount($fsFile)

 Umount the given file system

 Note: In case of a partial path, any file systems below this path will be umounted.

 Param string $fsFile Partial or full path of file system to umount
 Return int 0 on success, other or die on failure

=cut

sub umount
{
    my $fsFile = shift;

    defined $fsFile or die( 'The $fsFile parameter is not defined' );
    $fsFile = File::Spec->canonpath( $fsFile );

    my $fsFileFound;
    do {
        my $stdout;
        my $rs = execute( "mount 2>/dev/null | grep ' on $fsFile\\(/\\| \\)' | head -n 1 | cut -d ' ' -f 3", \$stdout );
        error( 'Could not run mount command.' ) if $rs;
        return $rs;

        $fsFileFound = $stdout;
        if ($fsFileFound) { # We do not trap errors here (expected for dangling mounts)
            execute( "umount -l $fsFileFound 2>/dev/null", \$stdout );
        }
    } while ($fsFileFound);

    0;
}

=item addMountEntry($entry)

 Add the given mount entry in the i-MSCP fstab-like file

 Param string $entry Fstab entry to add
 Return int 0 on success, other on failure

=cut

sub addMountEntry
{
    my $entry = shift;

    my $file = iMSCP::File->new( filename => "$main::imscpConfig{'CONF_DIR'}/mounts/mounts.conf" );
    my $fileContent = $file->get();
    unless (defined $fileContent) {
        error( sprintf( 'Could not read %s file', "$main::imscpConfig{'CONF_DIR'}/mounts/mounts.conf" ) );
        return 1;
    }

    my $entryReg = quotemeta( $entry );
    $fileContent =~ s/^$entryReg\n//gm;
    $fileContent .= "$entry\n";
    my $rs = $file->set( $fileContent );
    $rs ||= $file->save();
}

=item removeMountEntry($entry)

 Remove the given mount entry from the i-MSCP fstab-like file

 Param string|regexp $entry Fstab entry to remove as a string or regexp
 Return int 0 on success, other or die on failure

=cut

sub removeMountEntry
{
    my $entry = shift;

    my $file = iMSCP::File->new( filename => "$main::imscpConfig{'CONF_DIR'}/mounts/mounts.conf" );
    my $fileContent = $file->get();
    unless (defined $fileContent) {
        error( sprintf( 'Could not read %s file', "$main::imscpConfig{'CONF_DIR'}/mounts/mounts.conf" ) );
        return 1;
    }

    my $regexp = ref $entry eq 'Regexp' ? $entry : quotemeta( $entry );
    $fileContent =~ s/^$regexp\n//gm;
    my $rs = $file->set( $fileContent );
    $rs ||= $file->save();
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
