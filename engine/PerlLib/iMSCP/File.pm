=head1 NAME

 iMSCP::File - i-MSCP File library

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by internet Multi Server Control Panel
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
use Carp;
use FileHandle;
use File::Copy;
use File::Basename;
use parent 'Common::Object';

=head1 DESCRIPTION

 i-MSCP File library. Library allowing to perform common operations on files.

=head1 PUBLIC METHODS

=over 4

=item get()

 Get file content

 Return string|undef File content, croak or die on failure

=cut

sub get
{
	my $self = shift;

	defined $self->{'filename'} or croak("Attribut 'filename' is not set");
	$self->{'fileHandle'} = FileHandle->new($self->{'filename'}, 'r') or delete($self->{'fileHandle'});
	defined $self->{'fileHandle'} or croak(sprintf('Could not open %s: %s', $self->{'filename'}, $!));
	$self->{'fileContent'} =  do { local $/; readline($self->{'fileHandle'}) };
	$self->{'fileContent'};
}

=item getRFileHandle()

 Get filehandle for reading

 Return FileHandle|undef A filehandle on success, croak or die on failure

=cut

sub getRFileHandle
{
	my $self = shift;

	defined $self->{'filename'} or croak("Attribut 'filename' is not set");
	$self->{'fileHandle'}->close() if defined $self->{'fileHandle'};
	$self->{'fileHandle'} = FileHandle->new($self->{'filename'}, 'r') or delete($self->{'fileHandle'});
	defined $self->{'fileHandle'} or die(sprintf('Could not open %s', $self->{'filename'}));
	$self->{'fileHandle'};
}

=item getWFileHandle()

 Get filehandle for writting

 Return FileHandle|undef A filehandle on success, croak or die on failure

=cut

sub getWFileHandle
{
	my $self = shift;

	defined $self->{'filename'} or croak("Attribut 'filename' is not set");
	$self->{'fileHandle'}->close() if defined $self->{'fileHandle'};
	$self->{'fileHandle'} = FileHandle->new($self->{'filename'}, 'w') or delete($self->{'fileHandle'});
	defined $self->{'fileHandle'} or die(sprintf('Could not open %s', $self->{'filename'}));
	$self->{'fileHandle'};
}

=item set($content)

 Set file content

 Param string $content New file content
 Return string

=cut

sub set
{
	my ($self, $content) = @_;

	$self->{'fileContent'} = $content // '';
}

=item save()

 Save file

 Return int 0 on success, croak or die on failure

=cut

sub save
{
	my $self = shift;

	my $fh = $self->getWFileHandle();
	$self->{'fileContent'} //= '';
	print {$fh} $self->{'fileContent'};
	$fh->close();
	0;
}

=item delFile()

 Delete file

 Return int 0 on success, die on failure

=cut

sub delFile
{
	my $self = shift;

	defined $self->{'filename'} or croak("Attribut 'filename' is not set");
	unlink($self->{'filename'}) or die(printf('Could not delete file %s: %s', $self->{'filename'}, $!));
	0;
}

=item mode($mode)

 Change file mode bits

 Param int $mode New file mode (octal number)
 Return int 0 on success, die on failure

=cut

sub mode
{
	my ($self, $mode) = @_;

	defined $self->{'filename'} or croak("Attribut 'filename' is not set");
	chmod($mode, $self->{'filename'}) or die(sprintf('Could not change mode for %s: %s', $self->{'filename'}, $!));
	0;
}

=item owner($owner, $group)

 Change file owner and group

 Param int|string $owner Either an username or user id
 Param int|string $group Either a groupname or group id
 Return int 0 on success, die on failure

=cut

sub owner
{
	my ($self, $owner, $group) = @_;

	defined $self->{'filename'} or croak("Attribut 'filename' is not set");
	my $uid = (($owner =~ /^\d+$/) ? $owner : getpwnam($owner)) // -1;
	my $gid = (($group =~ /^\d+$/) ? $group : getgrnam($group)) // -1;
	chown($uid, $gid, $self->{'filename'}) or die(sprintf(
		'Could not change owner and group for %s: %s', $self->{'filename'}, $!
	));
	0;
}

=item copyFile($dest, [\%options = { 'preserve' => 'yes' }])

 Copy file to the given destination

 Param string $dest Destination path
 Param hash $options Options
 Return int 0 on success, die on failure

=cut

sub copyFile
{
	my ($self, $dest, $options) = @_;

	$options = { } unless ref $options eq 'HASH';
	defined $self->{'filename'} or croak("Attribut 'filename' is not set");
	copy($self->{'filename'}, $dest) or die(sprintf('Could not copy %s to %s: %s', $self->{'filename'}, $dest, $!));
	$dest .= '/' . basename($self->{'filename'}) if -d $dest;

	if(!defined $options->{'preserve'} || lc($options->{'preserve'}) ne 'no') {
		my (undef, undef, $mode, undef, $uid, $gid) = lstat($self->{'filename'});
		$mode = $mode & 07777;
		chmod($mode, $dest) or die(sprintf('Could not change mode for %s: %s', $dest, $!));
		chown($uid, $gid, $dest) or die(sprintf('Could not change owner and group for %s: %s', $dest, $!));
	}

	0;
}

=item moveFile($dest)

 Move file to the given destination

 Param string $dest Destination path
 Return int 0 on success, die on failure

=cut

sub moveFile
{
	my ($self, $dest) = @_;

	defined $self->{'filename'} or croak("Attribut 'filename' is not set");
	move($self->{'filename'}, $dest) or die(sprintf(
		'Could not move %s to %s: %s', $self->{'filename'}, $dest, $!
	));
	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item DESTROY()

 Close last filehandle opened when instance get destroyed

=cut

sub DESTROY
{
	my $self = shift;

	$self->{'fileHandle'}->close() if $self->{'fileHandle'};
}

=back

=head1 AUTHORS

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
