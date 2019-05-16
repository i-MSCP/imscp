=head1 NAME

 iMSCP::File - Library allowing to perform common operations on files

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2019 by internet Multi Server Control Panel
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

package iMSCP::File;

use strict;
use warnings;
use autouse Lchown => qw/ lchown /;
use Fcntl ':mode';
use File::Basename 'basename';
use File::Copy qw/ copy mv /;
use File::Spec;
use iMSCP::Debug 'error';
use parent 'Common::Object';

=head1 DESCRIPTION

 Library allowing to perform common operations on files.

=head1 PUBLIC METHODS

=over 4

=item get( )

 Get file content

 Return string|undef File content or undef on failure

=cut

sub get
{
    my ( $self ) = @_;

    return $self->{'fileContent'} if defined $self->{'fileContent'};

    unless ( defined $self->{'filename'} ) {
        error( "The 'filename' attribute isn't defined." );
        return undef;
    }

    my $fh;
    unless ( open( $fh, '<', $self->{'filename'} ) ) {
        error( sprintf(
            "Couldn't open the '%s' file for reading: %s", $self->{'filename'}, $!
        ));
        return undef;
    }

    local $/;
    $self->{'fileContent'} = <$fh>;
    close( $fh );
    $self->{'fileContent'}
}

=item getAsRef( )

 Get file content as scalar reference

 Return scalarref Reference to scalar containing file content

=cut

sub getAsRef
{
    my ( $self ) = @_;

    return \$self->{'fileContent'} if defined $self->{'fileContent'};

    $self->{'fileContent'} = $self->get();

    return undef unless defined $self->{'fileContent'};

    \$self->{'fileContent'};
}

=item set( $content )

 Set file content

 Param string $content New file content
 Return int 0

=cut

sub set
{
    my ( $self, $content ) = @_;

    $self->{'fileContent'} = $content // '';
    0;
}

=item save( )

 Save file

 Return int 0 on success, 1 on failure

=cut

sub save
{
    my ( $self ) = @_;

    unless ( defined $self->{'filename'} ) {
        error( "The 'filename' attribute isn't defined." );
        return undef;
    }

    my $fh;
    unless ( open( $fh, '>', $self->{'filename'} ) ) {
        error( sprintf(
            "Couldn't open the '%s' file for writing: %s",
            $self->{'filename'},
            $!
        ));
        return 1;
    }

    $self->{'fileContent'} //= '';
    print { $fh } $self->{'fileContent'};
    close( $fh );
    0;
}

=item delFile( )

 Delete file

 Return int 0 on success, 1 on failure

=cut

sub delFile
{
    my ( $self ) = @_;

    unless ( defined $self->{'filename'} ) {
        error( "The 'filename' attribute isn't defined." );
        return 1;
    }

    unless ( unlink( $self->{'filename'} ) ) {
        error( sprintf(
            "Couldn't delete the '%s' file: %s",
            $self->{'filename'},
            $!
        ));
        return 1;
    }

    0;
}

=item mode( $mode )

 Change file mode bits

 This routine doesn't operates on symlinks. They are ignored silently.

 Param int $mode New file mode (octal number)
 Return int 0 on success, 1 on failure

=cut

sub mode
{
    my ( $self, $mode ) = @_;

    unless ( defined $self->{'filename'} ) {
        error( "The 'filename' attribute isn't defined." );
        return 1;
    }

    unless ( defined $mode ) {
        error( "The '\$mode' parameter isn't defined." );
        return 1;
    }

    return 0 if -l $self->{'filename'};

    unless ( chmod( $mode, $self->{'filename'} ) ) {
        error( sprintf(
            "Couldn't change the '%s' file permissions: %s",
            $self->{'filename'},
            $!
        ));
        return 1;
    }

    0;
}

=item owner( $owner, $group )

 Change file owner and group

 Symlinks are not dereferenced.

 Param int|string $owner Either an username or userid
 Param int|string $group Either a groupname or groupid
 Return int 0 on success, 1 on failure

=cut

sub owner
{
    my ( $self, $owner, $group ) = @_;

    unless ( defined $self->{'filename'} ) {
        error( "The 'filename' attribute isn't defined." );
        return 1;
    }

    unless ( defined $owner ) {
        error( "The '\$owner' isn't defined" );
        return 1;
    }

    unless ( defined $group ) {
        error( "The '\$group' isn't defined" );
        return 1;
    }

    my $uid = ( $owner =~ /^\d+$/ ? $owner : getpwnam( $owner ) ) // -1;
    my $gid = ( $group =~ /^\d+$/ ? $group : getgrnam( $group ) ) // -1;

    unless ( lchown( $uid, $gid, $self->{'filename'} ) ) {
        error( sprintf(
            "Couldn't change the '%s' file ownership: %s",
            $self->{'filename'},
            $!
        ));
        return 1;
    }

    0;
}

=item copyFile( $dst [, \%options = { 'preserve' => 'yes', reuse_last_stat_call => FALSE } ] )

 Copy file to the given destination

 Symlinks are not dereferenced.
 Permissions are not set on symlink targets.

 Param string $dst Destination path (either a directory or file path)
 Param hash $options Options
  - preserve (yes|no): preserve ownership and permissions (default yes)
  - reuse_last_stat_call: Flag indicating whether or not last stat() call be be
    reused instead of making new one (default: FALSE.
 Return int 0 on success, 1 on failure

=cut

sub copyFile
{
    my ( $self, $dst, $options ) = @_;

    $options = {} unless ref $options eq 'HASH';

    unless ( defined $self->{'filename'} ) {
        error( "The 'filename' isn't defined." );
        return 1;
    }

    unless ( defined $dst ) {
        error( "The '\$dst' parameter isn't defined." );
        return 1;
    }

    my ( $mode, $uid, $gid ) = ( $options->{'reuse_last_stat_call'}
        ? lstat( _ ) : lstat( $self->{'filename'} )
    )[2, 4, 5];

    if ( S_ISDIR $mode ) {
        $dst = File::Spec->catfile( $dst, basename( $self->{'filename'} ));
    }

    if ( S_ISLNK $mode ) {
        my $lnkTarget = readlink( $self->{'filename'} );

        unless ( symlink( $lnkTarget, $dst ) ) {
            error( sprintf(
                "Couldn't copy the '%s' symlink to '%s': %s",
                $self->{'filename'},
                $dst,
                $!
            ));
            return 1;
        }
    } elsif ( !copy( $self->{'filename'}, $dst ) ) {
        error( sprintf(
            "Couldn't copy the '%s' file to '%s': %s",
            $self->{'filename'},
            $dst,
            $!
        ));
        return 1;
    }

    return 0 if defined $options->{'preserve'} && $options->{'preserve'} eq 'no';

    unless ( lchown( $uid, $gid, $dst ) ) {
        error( sprintf(
            "Couldn't change the '%s' file ownership: %s", $dst, $!
        ));
        return 1;
    }

    return 0 if S_ISLNK $mode; # We do not call chmod on symlinks

    unless ( chmod( $mode & 07777, $dst ) ) {
        error( sprintf(
            "Couldn't change  the '%s' file permissions: %s", $dst, $!
        ));
        return 1;
    }

    0;
}

=item moveFile( $dst )

 Move file to the given destination

 Param string dst Destination path (either a directory or file path)
 Return int 0 on success, 1 on failure

=cut

sub moveFile
{
    my ( $self, $dst ) = @_;

    unless ( defined $self->{'filename'} ) {
        error( "The 'filename' attribute' isn't defined." );
        return 1;
    }

    unless ( defined $dst ) {
        error( "The '\$dst' parameter isn't defined." );
        return 1;
    }

    unless ( mv( $self->{'filename'}, $dst ) ) {
        error( sprintf(
            "Couldn't move the '%s' file to '%s': %s",
            $self->{'filename'},
            $dst, $!
        ));
        return 1;
    }

    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize iMSCP::File object

 iMSCP::File

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'filename'} //= undef;
    $self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
