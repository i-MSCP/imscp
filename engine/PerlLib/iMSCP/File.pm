#!/usr/bin/perl

=head1 NAME

 iMSCP::File - i-MSCP File library

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2014 by internet Multi Server Control Panel
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
#
# @category    i-MSCP
# @copyright   2010-2014 by i-MSCP | http://i-mscp.net
# @author      Daniel Andreca <sci2tech@gmail.com>
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::File;

use strict;
use warnings;

use iMSCP::Debug;
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

 Return string|undef File content or undef on failure

=cut

sub get
{
	my $self = $_[0];

	unless(defined $self->{'filename'}) {
		error("Attribut 'filename' is not set");
		return undef;
	}

	unless(defined $self->{'fileContent'}) {
		$self->{'fileHandle'} = FileHandle->new($self->{'filename'}, 'r') or delete($self->{'fileHandle'});

		unless(defined $self->{'fileHandle'}) {
			error("Unable to open $self->{'filename'}: $!");
			return undef;
		}

		$self->{'fileContent'} =  do { local $/; readline($self->{'fileHandle'}) };
	}

	$self->{'fileContent'};
}

=item getRFileHandle()

 Get filehandle for reading

 Return FileHandle|undef A filehandle on success or undef on failure

=cut

sub getRFileHandle
{
	my $self = $_[0];

	unless(defined $self->{'filename'}) {
		error("Attribut 'filename' is not set");
		return undef;
	}

	$self->{'fileHandle'}->close() if defined $self->{'fileHandle'};
	$self->{'fileHandle'} = FileHandle->new($self->{'filename'}, 'r') or delete($self->{'fileHandle'});

	unless(defined $self->{'fileHandle'}) {
		error("Unable to open $self->{filename}");
		return undef;
	}

	$self->{'fileHandle'};
}

=item getWFileHandle()

 Get filehandle for writting

 Return FileHandle|undef A filehandle on success or undef on failure

=cut

sub getWFileHandle
{
	my $self = $_[0];

	unless(defined $self->{'filename'}) {
		error("Attribut 'filename' is not set");
		return undef;
	}

	$self->{'fileHandle'}->close() if defined $self->{'fileHandle'};
	$self->{'fileHandle'} = FileHandle->new($self->{'filename'}, 'w') or delete($self->{'fileHandle'});

	unless(defined $self->{'fileHandle'}) {
		error("Unable to open $self->{'filename'}");
		return undef;
	}

	$self->{'fileHandle'};
}

=item set($content)

 Set file content

 Param string $content New file content
 Return int 0

=cut

sub set
{
	my($self, $content) = @_;

	$self->{'fileContent'} = $content // '';

	0;
}

=item save()

 Save file

 Return int 0 on success, 1 on failure

=cut

sub save
{
	my $self = $_[0];

	my $fh = $self->getWFileHandle();

	if($fh) {
		debug("Saving file $self->{'filename'}");

		$self->{'fileContent'} //= '';

		print {$fh} $self->{'fileContent'};
		$fh->close();
	} else {
		error('Unable to save file');
		return 1;
	}

	0;
}

=item delFile()

 Delete file

 Return int 0 on success, 1 on failure

=cut

sub delFile
{
	my $self = $_[0];

	unless(defined $self->{'filename'}) {
		error("Attribut 'filename' is not set");
		return 1;
	}

	debug("Deleting file $self->{'filename'}");

	unless(unlink($self->{'filename'})) {
		error("Unable to delete file $self->{'filename'}: $!");
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

	unless(defined $self->{'filename'}) {
		error('Attribut filename is not set');
		return 1;
	}

	debug(sprintf("Changing mode for $self->{'filename'} to %o", $mode));

	unless (chmod($mode, $self->{'filename'})) {
		error("Unable to change mode for $self->{'filename'}: $!");
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

	unless(defined $self->{'filename'}) {
		error("Attribut 'filename' is not set");
		return 1;
	}

	my $uid = (($owner =~ /^\d+$/) ? $owner : getpwnam($owner)) // -1;
	my $gid = (($group =~ /^\d+$/) ? $group : getgrnam($group)) // -1;

	debug("Changing owner and group for $self->{'filename'} to $uid:$gid");

	unless (chown($uid, $gid, $self->{'filename'})) {
		error("Unable to change owner and group for $self->{'filename'}: $!");
		return 1;
	}

	0;
}

=item copyFile($dest, [\%options = { 'preserve' => 'yes' }])

 Copy file to the given destination

 Param string $dest Destination path
 Param hash $options Options
 Return int 0 on success, 1 on failure

=cut

sub copyFile
{
	my ($self, $dest, $options) = @_;

	$options = { } unless ref $options eq 'HASH';

	unless(defined $self->{'filename'}) {
		error("Attribut 'filename' is not set");
		return 1;
	}

	debug("Copying file $self->{'filename'} to $dest");

	unless(copy($self->{'filename'}, $dest)) {
		error("Unable to copy $self->{'filename'} to $dest: $!");
		return 1;
	}

	$dest .= '/' . basename($self->{'filename'}) if -d $dest;

	if(!defined $options->{'preserve'} || lc($options->{'preserve'}) ne 'no') {
		my $mode = (stat($self->{'filename'}))[2] & 00777;
		my $owner = (stat($self->{'filename'}))[4];
		my $group = (stat($self->{'filename'}))[5];

		debug(sprintf("Changing mode for $dest to %o", $mode));

		unless (chmod($mode, $dest)) {
			error("Unable to change mode for $dest: $!");
			return 1;
		}

		debug(sprintf("Changing owner and group for $dest to %s:%s", $owner, $group));

		unless (chown($owner, $group, $dest)) {
			error("Unable to change owner and group for $dest: $!");
			return 1;
		}
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

	unless(defined $self->{'filename'}) {
		error("Attribut 'filename' is not set");
		return 1;
	}

	debug("Moving file $self->{'filename'} to $dest");

	unless(move($self->{'filename'}, $dest)) {
		error("Unable to move $self->{'filename'} to $dest: $!");
		return 1;
	}

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
	my $self = $_[0];

	$self->{'fileHandle'}->close() if $self->{'fileHandle'};

	0;
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
