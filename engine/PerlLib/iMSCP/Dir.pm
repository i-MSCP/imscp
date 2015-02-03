# i-MSCP - internet Multi Server Control Panel
# Copyright 2010-2015 by internet Multi Server Control Panel
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
# @copyright   2010-2015 by i-MSCP | http://i-mscp.net
# @author      Daniel Andreca <sci2tech@gmail.com>
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::Dir;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::File;
use File::Path qw/mkpath remove_tree/;
use File::Copy;
use parent 'Common::Object';
use vars qw/$AUTOLOAD/;

sub getFiles
{
	my $self = $_[0];

	unless(defined $self->{'files'}) {
		$self->{'files'} = [];
		$self->_get();

		if($self->{'fileType'}) {
			for (@{$self->{'dirContent'}}) {
				push(@{$self->{'files'}}, $_) if -f "$self->{'dirname'}/$_" && /$self->{'fileType'}$/;
			}
		} else {
			for (@{$self->{'dirContent'}}) {
				push(@{$self->{'files'}}, $_) if -f "$self->{'dirname'}/$_";
			}
		}
	}

	wantarray ? @{$self->{'files'}} : join ' ', @{$self->{'files'}};
}

sub getDirs
{
	my $self = $_[0];

	unless(defined $self->{'dirs'}) {
		$self->{'dirs'} = [];
		$self->_get();

		for (@{$self->{'dirContent'}}) {
			if($_ ne '.' && $_ ne '..' && -d "$self->{'dirname'}/$_") {
				push @{$self->{'dirs'}}, $_;
			}
		}
	}

	wantarray ? @{$self->{'dirs'}} : join ' ', @{$self->{'dirs'}};
}

sub getAll
{
	my $self = $_[0];

	my @all = ($self->getDirs(), $self->getFiles());

	wantarray ? @all : join ' ', @all;
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
		fatal(sprintf('Unable to open %s directory: %s', $self->{'dirname'}, $!));
	}

	for (readdir DIRH) {
		if($_ ne '.' && $_ ne '..') {
			closedir(DIRH);
			return 0;
		}
	}

	closedir(DIRH);

	1;
}

sub mode
{
	my ($self, $mode, $dirname) = @_;

	$dirname ||= $self->{'dirname'};

	unless(defined $dirname) {
		error("Attribut 'dirname' is not set");
		return 1;
	}

	$self->{'dirname'} = $dirname;

	debug(sprintf('Changing mode for %s to %s', $self->{'dirname'}, $mode));

	unless (chmod($mode, $self->{'dirname'})) {
		error(sprintf('Unable to change mode for %s: %s', $self->{'dirname'}, $!));
		return 1;
	}

	0;
}

sub owner
{
	my ($self, $owner, $group, $dirname) = @_;

	$dirname ||= $self->{'dirname'};

	unless(defined $dirname) {
		error("Attribut 'dirname' is not set");
		return 1;
	}

	$self->{'dirname'} = $dirname;

	my $uid = ($owner =~ /^\d+$/) ? $owner : getpwnam($owner);
	$uid = -1 unless defined $uid;

	my $gid = ($group =~ /^\d+$/) ? $group : getgrnam($group);
	$gid = -1 unless defined $gid;

	debug(sprintf('Changing owner and group for %s to %s:%s', $self->{'dirname'}, $uid, $gid));

	unless (chown($uid, $gid, $self->{'dirname'})) {
		error(sprintf('Unable to change owner and group for %s: %s', $self->{'dirname'}, $!));
		return 1;
	}

	0;
}

sub make
{
	my ($self, $options) = @_;

	$options = { } unless defined $options && ref $options eq 'HASH';

	unless(defined $self->{'dirname'}) {
		error("Attribut 'dirname' is not set");
		return 1;
	}

	if (-f $self->{'dirname'}) {
		error(sprintf('Unable to create directory: %s already exists as file.', $self->{'dirname'}));
		return 1;
	}

	unless(-d $self->{'dirname'}) {
		debug(sprintf('Creating directory %s', $self->{'dirname'}));

		my $errors;
		my @createdDirs = mkpath($self->{'dirname'}, { 'error' => \$errors});

		if (@{$errors}) {
			for my $diag (@{$errors}) {
				my ($directory, $message) = %{$diag};

				if ($directory eq '') {
					error(sprintf('General error: %s', $message));
				} else {
					error(sprintf('Unable to create %s directory: %s', $directory, $message));
				}
			}

			return 1;
		}

		for (@createdDirs) {
			my $rs = $self->mode($options->{'mode'}, $_) if defined $options->{'mode'};
			return $rs if $rs;

			if(defined $options->{'user'} || defined $options->{'group'}) {
				$rs = $self->owner($options->{'user'} || -1, $options->{'group'} || -1, $_);
				return $rs if $rs;
			}
		}
	} else {
		debug(sprintf('Directory %s already exists. Setting its permissions...', $self->{'dirname'}));

		if(defined $options->{'mode'}) {
			my $rs = $self->mode($options->{'mode'});
			return $rs if $rs;
		}

		if(defined $options->{'user'} || defined $options->{'group'}) {
			my $rs = $self->owner($options->{'user'} || -1, $options->{'group'} || -1, $self->{'dirname'});
			return $rs if $rs;
		}
	}

	0;
}

sub remove
{
	my $self = $_[0];

	unless(defined $self->{'dirname'}) {
		error("Attribut 'dirname' is not set");
		return 1;
	}

	if (-d $self->{'dirname'}) {
		debug(sprintf('Removing directory %s', $self->{'dirname'}));

		my $err;
		remove_tree($self->{'dirname'}, { 'error' => \$err });

		if (@$err) {
			for my $diag (@$err) {
				my ($directory, $message) = %$diag;

				if ($directory eq '') {
					error(sprintf('General error: %s', $message));
				} else {
					error(sprintf('Unable to delete directory %s: %s', $directory, $message));
				}
			}

			return 1;
		}
	}

	0;
}

sub rcopy
{
	my ($self, $destDir, $options) = @_;

	my $rs = 0;

	$options = { } if ref $options ne 'HASH';

	unless(defined $self->{'dirname'}) {
		error("Attribut 'dirname' is not set");
		return 1;
	}

	unless(opendir DIRH, $self->{'dirname'}) {
		error(sprintf('Unable to open directory %s:', $self->{'dirname'}, $!));
		return 1;
	}

	my $excludeDir = ($options->{'excludeDir'}) ? qr/$options->{'excludeDir'}/ : undef;
	my $excludeFile = ($options->{'excludeFile'}) ? qr/$options->{'excludeFile'}/ : undef;

	for my $entry (readdir DIRH) {
		if($entry ne '.' && $entry ne '..') {
			my $source = "$self->{'dirname'}/$entry";
			my $destination = "$destDir/$entry";

			if (-d $source) {
				unless($excludeDir && $source =~ /$excludeDir/) {
					my $opts = { };

					unless($options->{'preserve'} && $options->{'preserve'} eq 'no') {
						my (undef, undef, $mode, undef, $uid, $gid) = lstat($source);
						$mode = $mode & 07777;
						$opts = { 'user' => $uid, 'mode' => $mode, 'group' => $gid }
					}

					debug(sprintf('Copying directory %s to %s', $source, $destination));

					my $directory = iMSCP::Dir->new();
					$directory->{'dirname'} = $destination;

					$rs = $directory->make($opts);
					return $rs if $rs;

					$directory->{'dirname'} = $source;

					$rs = $directory->rcopy($destination, $options);
					return $rs if $rs;
				}
			} elsif(! $excludeFile || $source !~ /$excludeFile}/) {
				debug(sprintf('Copying file %s to %s', "$self->{'dirname'}/$entry", "$destDir/$entry"));

				my $file = iMSCP::File->new('filename' => $source);
				$rs = $file->copyFile($destination, $options);
				return $rs if $rs;
			}
		}
	}

	closedir DIRH;

	0;
}

sub moveDir
{
	my ($self, $dest) = @_;

	unless(defined $self->{'dirname'}) {
		error("Attribut 'dirname' is not set");
		return 1;
	}

	unless(-d $self->{'dirname'}) {
		error(sprintf("Directory %s doesn't exits", $self->{'dirname'}));
		return 1;
	}

	debug(sprintf('Moving directory %s to %s', $self->{'dirname'}, $dest));

	unless(move($self->{'dirname'}, $dest)) {
		error(sprintf('Unable to move %s to %s: %s', $self->{'dirname'}, $dest, $!));
		return 1;
	}

	0;
}

sub _get
{
	my $self = $_[0];

	unless(defined $self->{'dirContent'}) {
		debug(sprintf('Opening directory %s', $self->{'dirname'}));

		$self->{'dirContent'} = ();

		unless(opendir(DIRH, $self->{'dirname'})) {
			fatal(sprintf('Unable to open directory %s: %s', $self->{'dirname'}, $!));
		}

		@{$self->{'dirContent'}} = readdir(DIRH);

		closedir(DIRH);
	}
}

1;
__END__
