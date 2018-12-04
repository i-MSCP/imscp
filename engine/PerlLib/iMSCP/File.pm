=head1 NAME

 iMSCP::File - Library allowing to perform common operations on files

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2017 by internet Multi Server Control Panel
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
use File::Basename qw/ basename /;
use File::Copy qw/ copy mv /;
use File::Spec;
use iMSCP::Debug qw/ error /;
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
    my ($self) = @_;

    return $self->{'fileContent'} if defined $self->{'fileContent'};

    unless ( defined $self->{'filename'} ) {
        error( "Attribute `filename' is not set." );
        return undef;
    }

    my $fh;
    unless ( open( $fh, '<', $self->{'filename'} ) ) {
        error( sprintf( "Couldn't open `%s' file for reading: %s", $self->{'filename'}, $! ));
        return undef;
    }

    local $/;
    $self->{'fileContent'} = <$fh>;
    close( $fh );
    $self->{'fileContent'}
}

=item

 Get file content as scalar reference

 Return scalarref Reference to scalar containing file content

=cut

sub getAsRef
{
    my ($self) = @_;

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
    my ($self, $content) = @_;

    $self->{'fileContent'} = $content // '';
    0;
}

=item save( )

 Save file

 Return int 0 on success, 1 on failure

=cut

sub save
{
    my ($self) = @_;

    unless ( defined $self->{'filename'} ) {
        error( "Attribute `filename' is not set." );
        return undef;
    }

    my $fh;
    unless ( open( $fh, '>', $self->{'filename'} ) ) {
        error( sprintf( "Couldn't open `%s' file for writing: %s", $self->{'filename'}, $! ));
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
    my ($self) = @_;

    unless ( defined $self->{'filename'} ) {
        error( "Attribute `filename' is not set." );
        return 1;
    }

    unless ( unlink( $self->{'filename'} ) ) {
        error( sprintf( "Couldn't delete `%s' file: %s", $self->{'filename'}, $! ));
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
    my ($self, $mode) = @_;

    unless ( defined $self->{'filename'} ) {
        error( "Attribute `filename' is not set." );
        return 1;
    }

    unless ( defined $mode ) {
        error( 'Missing $mode parameter' );
        return 1;
    }

    return 0 if -l $self->{'filename'};

    unless ( chmod( $mode, $self->{'filename'} ) ) {
        error( sprintf( "Couldn't change `%s' file permissions: %s", $self->{'filename'}, $! ));
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
    my ($self, $owner, $group) = @_;

    unless ( defined $self->{'filename'} ) {
        error( "Attribute `filename' is not set." );
        return 1;
    }

    unless ( defined $owner ) {
        error( 'Missing $owner parameter' );
        return 1;
    }

    unless ( defined $group ) {
        error( 'Missing $group parameter' );
        return 1;
    }

    my $uid = ( ( $owner =~ /^\d+$/ ) ? $owner : getpwnam( $owner ) ) // -1;
    my $gid = ( ( $group =~ /^\d+$/ ) ? $group : getgrnam( $group ) ) // -1;

    unless ( lchown( $uid, $gid, $self->{'filename'} ) ) {
        error( sprintf( "Couldn't change `%s' file ownership: %s", $self->{'filename'}, $! ));
        return 1;
    }

    0;
}

=item copyFile( $destination [, \%options = { 'preserve' => 'yes' } ] )

 Copy file to the given destination

 Symlinks are not dereferenced. 
 Permissions are not set on symlink targets.

 Param string $destination Destination path (either a directory or file path)
 Param hash $options Options
  - preserve (yes|no): preserve ownership and permissions (default yes)
 Return int 0 on success, 1 on failure

=cut

sub copyFile
{
    my ($self, $destination, $options) = @_;

    $options = {} unless $options && ref $options eq 'HASH';

    unless ( defined $self->{'filename'} ) {
        error( "Attribute `filename' is not set." );
        return 1;
    }

    unless ( defined $destination && ref \$destination eq 'SCALAR' ) {
        error( '$destination parameter is msissing or invalid.' );
        return 1;
    }

    unless ( copy( $self->{'filename'}, $destination ) ) {
        error( sprintf( "Couldn't copy `%s' file to `%s': %s", $self->{'filename'}, $destination, $! ));
        return 1;
    }

    return 0 if defined $options->{'preserve'} && $options->{'preserve'} eq 'no';

    $destination = File::Spec->catfile( $destination, basename( $self->{'filename'} )) if -d $destination;

    my ($mode, $uid, $gid) = ( lstat( $self->{'filename'} ) )[2, 4, 5];

    unless ( lchown( $uid, $gid, $destination ) ) {
        error( sprintf( "Couldn't change `%s' file ownership: %s", $destination, $! ));
        return 1;
    }

    return if -l $destination; # We do not call chmod on symkink targets

    unless ( chmod( $mode & 07777, $destination ) ) {
        error( sprintf( "Couldn't change `%s' file permissions: %s", $destination, $! ));
        return 1;
    }

    0;
}

=item moveFile( $$destination )

 Move file to the given destination

 Param string $destination Destination path (either a directory or file path)
 Return int 0 on success, 1 on failure

=cut

sub moveFile
{
    my ($self, $destination) = @_;

    unless ( defined $self->{'filename'} ) {
        error( "Attribute `filename' is not set." );
        return 1;
    }

    unless ( defined $destination && ref \$destination eq 'SCALAR' ) {
        error( '$destination parameter is msissing or invalid.' );
        return 1;
    }

    unless ( mv( $self->{'filename'}, $destination ) ) {
        error( sprintf( "Couldn't move `%s' file to `%s': %s", $self->{'filename'}, $destination, $! ));
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
    my ($self) = @_;

    $self->{'filename'} //= undef;
    $self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
