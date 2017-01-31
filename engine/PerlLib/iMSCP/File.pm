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
use autouse 'Lchown' => qw/ lchown /;
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
    my $self = shift;

    return $self->{'fileContent'} if defined $self->{'fileContent'};

    unless (defined $self->{'filename'}) {
        error( "Attribut `filename' is not set." );
        return undef;
    }

    my $fh;
    unless (open($fh, '<', $self->{'filename'})) {
        error( sprintf( "Could not open `%s' file for reading: %s", $self->{'filename'}, $! ) );
        return undef;
    }

    local $/;
    $self->{'fileContent'} = <$fh>;
    close($fh);
    $self->{'fileContent'}
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
    my $self = shift;

    unless (defined $self->{'filename'}) {
        error( "Attribut `filename' is not set." );
        return undef;
    }

    my $fh;
    unless (open($fh, '>', $self->{'filename'})) {
        error( sprintf( "Could not open `%s' file for writing: %s", $self->{'filename'}, $! ) );
        return 1;
    }

    $self->{'fileContent'} //= '';
    print {$fh} $self->{'fileContent'};
    close($fh);
    0;
}

=item delFile( )

 Delete file

 Return int 0 on success, 1 on failure

=cut

sub delFile
{
    my $self = shift;

    unless (defined $self->{'filename'}) {
        error( "Attribut `filename' is not set." );
        return 1;
    }

    unless (unlink( $self->{'filename'} )) {
        error( sprintf( "Could not delete `%s' file: %s", $self->{'filename'}, $! ) );
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

    unless (defined $self->{'filename'}) {
        error( "Attribut `filename' is not set." );
        return 1;
    }

    return if -l $self->{'filename'};

    unless (chmod( $mode, $self->{'filename'} )) {
        error( sprintf( "Could not change `%s' file permissions: %s", $self->{'filename'}, $! ) );
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

    unless (defined $self->{'filename'}) {
        error( "Attribut `filename' is not set." );
        return 1;
    }

    my $uid = (($owner =~ /^\d+$/) ? $owner : getpwnam( $owner )) // - 1;
    my $gid = (($group =~ /^\d+$/) ? $group : getgrnam( $group )) // - 1;

    unless (lchown( $uid, $gid, $self->{'filename'} )) {
        error( sprintf( "Could not change `%s' file ownership: %s", $self->{'filename'}, $! ) );
        return 1;
    }

    0;
}

=item copyFile( $dest [, \%options = { 'preserve' => 'yes' } ] )

 Copy file to the given destination

 Symlinks are not dereferenced. 
 Permissions are not set on symlink targets.

 Param string $dest Destination path
 Param hash $options Options
    preserve (yes|no): preserve permissions and ownership (default yes)
 Return int 0 on success, 1 on failure

=cut

sub copyFile
{
    my ($self, $dest, $options) = @_;

    $options = { } unless $options && ref $options eq 'HASH';

    unless (defined $self->{'filename'}) {
        error( "Attribut `filename' is not set." );
        return 1;
    }

    unless (copy( $self->{'filename'}, $dest )) {
        error( sprintf( "Could not copy `%s' file to `%s': %s", $self->{'filename'}, $dest, $! ) );
        return 1;
    }

    return 0 if defined $options->{'preserve'} && $options->{'preserve'} eq 'no';

    $dest = File::Spec->catfile( $dest, basename( $self->{'filename'} ) ) if -d $dest;

    my ($mode, $uid, $gid) = (lstat( $self->{'filename'} ))[2, 4, 5];

    unless (lchown( $uid, $gid, $dest )) {
        error( sprintf( "Could not change `%s' file ownership: %s", $dest, $! ) );
        return 1;
    }

    return if -l $dest; # We do not call chmod on symkink targets

    unless (chmod( $mode & 07777, $dest )) {
        error( sprintf( "Could not change `%s' file permissions: %s", $dest, $! ) );
        return 1;
    }

    0;
}

=item moveFile( $dest )

 Move file to the given destination

 Param string $dest Destination path
 Return int 0 on success, 1 on failure

=cut

sub moveFile
{
    my ($self, $dest) = @_;

    unless (defined $self->{'filename'}) {
        error( "Attribut `filename' is not set." );
        return 1;
    }

    unless (mv( $self->{'filename'}, $dest )) {
        error( sprintf( "Could not move `%s' file to `%s': %s", $self->{'filename'}, $dest, $! ) );
        return 1;
    }

    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize iMSCP::File object

 iMSCP::File

=cut

sub _init
{
    my $self = shift;

    $self->{'filename'} //= undef;
    $self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
