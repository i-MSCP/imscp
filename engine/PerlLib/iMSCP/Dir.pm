#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright 2010-2014 by internet Multi Server Control Panel
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
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::Dir;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::File;
use File::Path qw/mkpath remove_tree/;
use File::Copy;
use parent 'Common::SimpleClass';
use vars qw/$AUTOLOAD/;

sub _init
{
	my $self = shift;

	$self->{$_} = $self->{'args'}->{$_} for keys %{$self->{'args'}};

	$self;
}

sub getFiles
{
	my $self = shift;

	if(! $self->{'files'}) {
		$self->{'files'} = [];
		$self->_get();
		$self->{'fileType'} = '' unless $self->{'fileType'};

		for (@{$self->{'dirContent'}}) {
			push(@{$self->{'files'}}, $_) if -f "$self->{'dirname'}/$_" && /$self->{'fileType'}$/;
		}
	}

	my @files = $self->{'files'} ? @{$self->{'files'}} : ();

	debug("Return @{$self->{'files'}}");

	wantarray ? @files : join(' ', @files);
}

sub getDirs
{
	my $self = shift;

	unless(defined $self->{'dirs'}) {
		$self->{'dirs'} = [];
		$self->_get();

		for (@{$self->{'dirContent'}}) {
			next if $_ eq '.' || $_ eq '..';
			push(@{$self->{'dirs'}}, $_) if -d "$self->{'dirname'}/$_";
		}
	}

	debug("Return @{$self->{'dirs'}}");

	wantarray ? @{$self->{'dirs'}} : join(' ', @{$self->{'dirs'}});
}

sub getAll
{
	my $self = shift;

	my @all = ($self->getDirs(), $self->getFiles());

	debug("Return @all");

	wantarray ? @all : join(' ', @all);
}

sub isEmpty
{
	my $self = shift;
	my $dirname = shift || $self->{'dirname'};

	unless(defined $dirname) {
		error("Attribut 'dirname' is not set");
		return 1;
	}

	$self->{'dirname'} = $dirname;

	unless(opendir(DIRH, $self->{'dirname'})) {
		fatal("Unable to open directory $self->{'dirname'}: $!");
	}

	for (readdir DIRH) {
		next if $_ eq '.' || $_ eq '..';
		closedir(DIRH);
		return 0;
	}

	closedir(DIRH);

	1;
}

sub mode
{
	my $self = shift;
	my $mode = shift;
	my $dirname = shift || $self->{'dirname'};

	unless(defined $dirname) {
		error("Attribut 'dirname' is not set");
		return 1;
	}

	$self->{'dirname'} = $dirname;

	debug(sprintf("Changing mode for $self->{'dirname'} to %o", $mode));

	unless (chmod($mode, $self->{'dirname'})) {
		error("Unable to change mode for $self->{'dirname'}: $!");
		return 1;
	}

	0;
}

sub owner
{
	my $self = shift;
	my $owner = shift;
	my $group = shift;
	my $dirname	= shift || $self->{'dirname'};

	unless(defined $dirname) {
		error("Attribut 'dirname' is not set");
		return 1;
	}

	$self->{'dirname'} = $dirname;

	my $uid = ($owner =~ /^\d+$/) ? $owner : getpwnam($owner);
	$uid = -1 unless defined $uid;

	my $gid = ($group =~ /^\d+$/) ? $group : getgrnam($group);
	$gid = -1 unless defined $gid;

	debug("Changing owner and group for $self->{'dirname'} to $uid:$gid");

	unless (chown($uid, $gid, $self->{'dirname'})) {
		error("Unable to change owner and group for $self->{'dirname'}: $!");
		return 1;
	}

	0;
}

sub make
{
	my $self = shift;
	my $options = shift || {};
	my $rs = 0;

	$options = {} if ref $options ne 'HASH';

	unless(defined $self->{'dirname'}) {
		error("Attribut 'dirname' is not set");
		return 1;
	}

	if (-f $self->{'dirname'}) {
		warning("Directory $self->{'dirname'} already exists as file. Removing file first...");

		unless(unlink $self->{'dirname'}) {
			error("Unable to remove file $self->{'dirname'}: $!");
			return 1;
		 }
	}

	unless(-d $self->{'dirname'}) {
		debug("Creating directory $self->{'dirname'}");
		my $err;
		my @createdDirs = mkpath($self->{'dirname'}, { 'error' => \$err});

		if (@$err) {
			for my $diag (@$err) {
				my ($directory, $message) = %$diag;

				if ($directory eq '') {
					error("General error: $message");
				} else {
					error("Unable to create directory $directory: $message");
				}
			}

			return 1;
		}

		for (@createdDirs) {
			$rs = $self->mode($options->{'mode'}, $_) if defined $options->{'mode'};
			return $rs if $rs;

			if(defined $options->{'user'} || defined $options->{'group'}) {
				$rs = $self->owner($options->{'user'} || -1, $options->{'group'} || -1, $_);
				return $rs if $rs;
			}
		}
	} else {
		debug("Directory $self->{'dirname'} already exists. Setting its permissions...");

		if(defined $options->{'mode'}) {
			$rs = $self->mode($options->{'mode'});
			return $rs if $rs;
		}

		if(defined $options->{'user'} || defined $options->{'group'}) {
			$rs = $self->owner($options->{'user'} || -1, $options->{'group'} || -1, $self->{'dirname'});
			return $rs if $rs;
		}
	}

	0;
}

sub remove
{
	my $self = shift;

	unless(defined $self->{'dirname'}) {
		error("Attribut 'dirname' is not set");
		return 1;
	}

	if (-d $self->{'dirname'}) {

		debug("Removing directory $self->{'dirname'}");

		my $err;
		remove_tree($self->{'dirname'}, { 'error' => \$err });

		if (@$err) {
			for my $diag (@$err) {
				my ($directory, $message) = %$diag;

				if ($directory eq '') {
					error("General error: $message");
				} else {
					error("Unable to delete directory $directory: $message");
				}
			}

			return 1;
		}
	}

	0;
}

sub rcopy
{
	my $self = shift;
	my $destDir = shift;
	my $options = shift;
	my $rs = 0;

	$options = {} if ref $options ne 'HASH';

	unless(defined $self->{'dirname'}) {
		error("Attribut 'dirname' is not set");
		return 1;
	}

	my $dh;

	unless(opendir $dh, $self->{'dirname'}) {
		error("Unable to open directory $self->{'dirname'}: $!");
		return 1;
	}

	for my $entry (readdir $dh) {
		next if($entry eq '.' or $entry eq '..');
		my $source = "$self->{'dirname'}/$entry";
		my $destination = "$destDir/$entry";

		if (-d $source) {
			next if $options->{'excludeDir'} && $source =~ /$options->{'excludeDir'}/;
			my $opts = {};

			if(! $options->{'preserve'} || lc($options->{'preserve'}) ne 'no') {
				my $mode = (stat($source))[2] & 00777;
				my $user = (stat($source))[4];
				my $group = (stat($source))[5];
				$opts = { 'user' => $user, 'mode' => $mode, 'group' => $group }
			}

			debug("Copying directory $source to $destination");

			my $directory = iMSCP::Dir->new();
			$directory->{'dirname'} = $destination;

			$rs = $directory->make($opts);
			return $rs if $rs;

			$directory->{'dirname'} = $source;

			$rs = $directory->rcopy($destination, $options);
			return $rs if $rs;
		} else {
			error($options->{'excludeFile'}) if $options->{'excludeFile'};

			next if $options->{'excludeFile'} && $source =~ /$options->{'excludeFile'}/;

			debug("Copying file $self->{'dirname'}/$entry to $destDir/$entry");

			my $file = iMSCP::File->new('filename' => $source);
			$rs = $file->copyFile($destination, $options);
			return $rs if $rs;
		}
	}

	closedir $dh;

	0;
}

sub moveDir
{
	my $self = shift;
	my $dest = shift;

	unless(defined $self->{'dirname'}) {
		error("Attribut 'dirname' is not set");
		return 1;
	}

	unless(-d $self->{'dirname'}) {
		error("Directory $self->{'dirname'} doesn't exits!");
		return 1;
	}

	debug("Moving directory $self->{'dirname'} to $dest");

	unless(move($self->{'dirname'}, $dest)) {
		error("Unable to move $self->{'dirname'} to $dest: $!");
		return 1;
	}

	0;
}

sub _get
{
	my $self = shift;

	unless(defined $self->{'dirContent'}) {
		debug("Opening directory $self->{'dirname'}");

		$self->{'dirContent'} = ();

		unless(opendir(DIRH, $self->{'dirname'})) {
			fatal("Unable to open directory $self->{'dirname'}: $!");
		}

		@{$self->{'dirContent'}} = readdir(DIRH);

		closedir(DIRH);
	}
}

1;
