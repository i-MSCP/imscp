=head1 NAME

 iMSCP::File - Library allowing to perform common operations on files

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

package iMSCP::File;

use strict;
use warnings;
use iMSCP::Debug;
use File::Copy;
use File::Basename;
use parent 'Common::Object';

=head1 DESCRIPTION

 Library allowing to perform common operations on files.

=head1 PUBLIC METHODS

=over 4

=item get()

 Get file content

 Return string|undef File content or undef on failure

=cut

sub get
{
    my $self = shift;

    unless (defined $self->{'filename'}) {
        error( "Attribut `filename' is not set" );
        return undef;
    }

    return $self->{'fileContent'} if defined $self->{'fileContent'};

    my $fh;
    unless(open($fh, '<', $self->{'filename'})) {
        error( sprintf( 'Could not open %s for reading: %s', $self->{'filename'}, $! ) );
        return undef;
    }

    local $/;
    $self->{'fileContent'} = <$fh>;
}

=item set($content)

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

=item save()

 Save file

 Return int 0 on success, 1 on failure

=cut

sub save
{
    my $self = shift;

    unless (defined $self->{'filename'}) {
        error( "Attribut 'filename' is not set" );
        return undef;
    }
    
    my $fh;
    unless(open($fh, '>', $self->{'filename'})) {
        error( sprintf( 'Could not open %s for writing: %s', $self->{'filename'}, $! ) );
        return 1;
    }

    $self->{'fileContent'} //= '';
    print {$fh} $self->{'fileContent'};
    0;
}

=item delFile()

 Delete file

 Return int 0 on success, 1 on failure

=cut

sub delFile
{
    my $self = shift;

    unless (defined $self->{'filename'}) {
        error( "Attribut `filename' is not set" );
        return 1;
    }

    unless (unlink( $self->{'filename'} )) {
        error( sprintf( 'Could not delete file %s: %s', $self->{'filename'}, $! ) );
        return 1;
    }

    0;
}

=item mode($mode)

 Change file mode bits

 Param int $mode New file mode (octal number)
 Return int 0 on success, 1 on failure

=cut

sub mode
{
    my ($self, $mode) = @_;

    unless (defined $self->{'filename'}) {
        error( "Attribut `filename' is not set" );
        return 1;
    }

    unless (chmod( $mode, $self->{'filename'} )) {
        error( sprintf( 'Could not change mode for %s: %s', $self->{'filename'}, $! ) );
        return 1;
    }

    0;
}

=item owner($owner, $group)

 Change file owner and group

 Param int|string $owner Either an username or user id
 Param int|string $group Either a groupname or group id
 Return int 0 on success, 1 on failure

=cut

sub owner
{
    my ($self, $owner, $group) = @_;

    unless (defined $self->{'filename'}) {
        error( "Attribut `filename' is not set" );
        return 1;
    }

    my $uid = (($owner =~ /^\d+$/) ? $owner : getpwnam( $owner )) // -1;
    my $gid = (($group =~ /^\d+$/) ? $group : getgrnam( $group )) // -1;

    unless (chown( $uid, $gid, $self->{'filename'} )) {
        error( sprintf( 'Could not change owner and group for %s: %s', $self->{'filename'}, $! ) );
        return 1;
    }

    0;
}

=item copyFile( $dest [, \%options = { 'preserve' => 'yes' } ] )

 Copy file to the given destination

 Param string $dest Destination path
 Param hash $options Options
 Return int 0 on success, 1 on failure

=cut

sub copyFile
{
    my ($self, $dest, $options) = @_;

    $options = { } unless ref $options eq 'HASH';

    unless (defined $self->{'filename'}) {
        error( "Attribut `filename' is not set" );
        return 1;
    }
    unless (copy( $self->{'filename'}, $dest )) {
        error( sprintf( 'Could not copy %s to %s: %s', $self->{'filename'}, $dest, $! ) );
        return 1;
    }

    $dest .= '/'.basename( $self->{'filename'} ) if -d $dest;

    return 0 unless !defined $options->{'preserve'} || $options->{'preserve'} ne 'no';

    my (undef, undef, $mode, undef, $uid, $gid) = lstat( $self->{'filename'} );
    $mode = $mode & 07777;

    unless (chmod( $mode, $dest )) {
        error( sprintf( 'Could not change mode for %s: %s', $dest, $! ) );
        return 1;
    }
    unless (chown( $uid, $gid, $dest )) {
        error( sprintf( 'Could not change owner and group for %s: %s', $dest, $! ) );
        return 1;
    }

    0;
}

=item moveFile($dest)

 Move file to the given destination

 Param string $dest Destination path
 Return int 0 on success, 1 on failure

=cut

sub moveFile
{
    my ($self, $dest) = @_;

    unless (defined $self->{'filename'}) {
        error( "Attribut `filename' is not set" );
        return 1;
    }

    unless (move( $self->{'filename'}, $dest )) {
        error( sprintf( 'Could not move %s to %s: %s', $self->{'filename'}, $dest, $! ) );
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
