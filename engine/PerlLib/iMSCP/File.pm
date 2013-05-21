#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2013 by internet Multi Server Control Panel
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
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
# @author      Daniel Andreca <sci2tech@gmail.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::File;

use strict;
use warnings;

use iMSCP::Debug;
use FileHandle;
use File::Copy;
use File::Basename;
use parent 'Common::SimpleClass';
use vars qw/$AUTOLOAD/;

sub AUTOLOAD
{
	my $self = shift;
	my $name = $AUTOLOAD;
	$name =~ s/.*:://;
	return if $name eq 'DESTROY';

	$self->{$name} = shift if @_;

	unless (exists $self->{$name}) {
		error("Unable to find '$name'.");
		return undef;
	}

	$self->{$name};
}

sub _init
{
	my $self = shift;

	$self->{$_} = $self->{'args'}->{$_} for keys %{$self->{'args'}};

	$self;
}

sub mode
{
	my $self = shift;
	my $fileMode = shift;

	unless(defined $self->{'filename'}) {
		error('Attribut filename is not set');
		return 1;
	}

	debug(sprintf("Changing mode for $self->{'filename'} to %o", $fileMode));

	unless (chmod($fileMode, $self->{'filename'})) {
		error("Unable to change mode for $self->{'filename'}: $!");
		return 1;
	}

	0;
}

sub owner
{
	my $self = shift;
	my $fileOwner = shift;
	my $fileGroup = shift;

	unless(defined $self->{'filename'}) {
		error("Attribut 'filename' is not set");
		return 1;
	}

	my $uid = ($fileGroup =~ /^\d+$/) ? $fileOwner : getpwnam($fileOwner);
	$uid = -1 unless defined $uid;

	my $gid = ($fileGroup =~ /^\d+$/) ? $fileGroup : getgrnam($fileGroup);
	$gid = -1 unless defined $gid;

	debug("Changing owner and group for $self->{'filename'} to $uid:$gid");

	unless (chown($uid, $gid, $self->{'filename'})) {
		error("Unable to change owner and group for $self->{'filename'}: $!");
		return 1;
	}

	0;
}

sub get
{
	my $self = shift;
	my @lines;

	unless(defined $self->{'filename'}) {
		error("Attribut 'filename' is not set");
		return undef;
	}

	unless(defined $self->{'fileContent'}) {

		$self->{'fileHandle'} = FileHandle->new($self->{'filename'}, 'r') or delete($self->{'fileHandle'});

		unless(defined $self->{'fileHandle'}) {
			error("Unable to open $self->{'filename'}");
			return undef;
		}

		my $fh = $self->{'fileHandle'};
		$self->{'fileContent'} =  do { local $/; <$fh> };
	}

	$self->{'fileContent'};
}

sub getRFileHandle
{
	my $self = shift;

	unless(defined $self->{'filename'}) {
		error("Attribut 'filename' is not set");
		return undef;
	}

	$self->{'fileHandle'} = FileHandle->new($self->{'filename'}, 'r') or delete($self->{'fileHandle'});

	unless(defined $self->{'fileHandle'}) {
		error("Unable to open $self->{filename}");
		return undef;
	}

	$self->{'fileHandle'};
}

sub getWFileHandle
{
	my $self = shift;

	unless(defined $self->{'filename'}) {
		error("Attribut 'filename' is not set");
		return undef;
	}

	$self->{'fileHandle'} = FileHandle->new($self->{'filename'}, 'w') or delete($self->{'fileHandle'});

	unless(defined $self->{'fileHandle'}) {
		error("Unable to open $self->{'filename'}");
		return undef;
	}

	$self->{'fileHandle'};
}

sub copyFile
{
	my $self = shift;
	my $dest = shift;
	my $option = shift;

	$option = {} if(ref $option ne 'HASH');

	unless(defined $self->{'filename'}) {
		error("Attribut 'filename' is not set");
		return undef;
	}

	unless(-f $self->{'filename'}) {
		error("File $self->{'filename'} doesn't exits");
		return 1;
	}

	debug("Copying file $self->{'filename'} to $dest");

	unless(copy($self->{'filename'}, $dest)) {
		error("Unable to copy $self->{'filename'} to $dest: $!");
		return 1;
	}

	if(-d $dest) {
		my ($name, $path, $suffix) = fileparse($self->{'filename'});
		$dest .= "/$name$suffix";
	}

	if(! $option->{'preserve'} || lc($option->{'preserve'}) ne 'no') {

		my $fileMode = (stat($self->{'filename'}))[2] & 00777;
		my $owner = (stat($self->{'filename'}))[4];
		my $group = (stat($self->{'filename'}))[5];

		debug(sprintf("Changing mode for $dest to %o", $fileMode));

		unless (chmod($fileMode, $dest)) {
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

sub moveFile
{
	my $self = shift;
	my $dest = shift;

	unless(defined $self->{'filename'}) {
		error("Attribut 'filename' is not set");
		return undef;
	}

	unless(-f $self->{'filename'}) {
		error("File $self->{'filename'} doesn't exits");
		return 1;
	}

	debug("Moving file $self->{'filename'} to $dest");

	unless(move($self->{'filename'}, $dest)) {
		error("Unable to move $self->{'filename'} to $dest: $!");
		return 1;
	}

	0;
}

sub delFile
{
	my $self= shift;

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

sub save
{
	my $self = shift;

	unless(defined $self->{'filename'}) {
		error("Attribut 'filename' is not set");
		return 1;
	}

	debug("Saving file $self->{'filename'}");

	$self->{'fileHandle'}->close() if defined $self->{'fileHandle'};
	$self->{'fileHandle'} = FileHandle->new($self->{'filename'}, 'w');

	unless(defined $self->{'fileHandle'}) {
		error("Unable to open file $self->{'filename'}");
		return 1;
	}

	$self->{'fileContent'} = '' unless $self->{'fileContent'};

	print {$self->{'fileHandle'}} $self->{'fileContent'};

	$self->{'fileHandle'}->close();

	0;
}

sub set
{
	my $self = shift;
	my $content = shift || '';

	$self->{'fileContent'} = $content;

	0;
}

sub DESTROY
{
	my $self = shift;

	$self->{'fileHandle'}->close() if $self->{'fileHandle'};

	0;
}

1;
