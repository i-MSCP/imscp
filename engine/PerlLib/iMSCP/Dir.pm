=head1 NAME

 iMSCP::Dir - Package which allow to perform operation on directories

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by Laurent Declercq <l.declercq@nuxwin.com>
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

package iMSCP::Dir;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::File;
use File::Path qw/mkpath remove_tree/;
use File::Copy;
use parent 'Common::Object';

=head1 DESCRIPTION

 Package which allow to perform operation on directories

=head1 PUBLIC METHODS

=over 4

=item getFiles([ $dirname ])

 Get list of files inside directory

 Param string $dirname OPTIONAL Directory - Default $self->{'dirname'}
 Return array representing list files or die on failure

=cut

sub getFiles
{
	my $self = shift;
	my $dirname = shift // $self->{'dirname'};

	defined $dirname or die("Missing 'dirname' parameter");
	opendir my $dh, $dirname or die(sprintf('Could not open %s: %s', $dirname, $!));
	my @files = grep { $_ ne '.' && $_ ne '..' && -f "$self->{'dirname'}/$_" } readdir $dh;
	closedir $dh;

	($self->{'fileType'}) ? grep(/$self->{'fileType'}$/, @files) : @files;
}

=item getDirs([ $dirname ])

 Get list of directories inside directory

 Param string $dirname OPTIONAL Directory - Default $self->{'dirname'}
 Return array representing list of directories or die on failure

=cut

sub getDirs
{
	my $self = shift;
	my $dirname = shift // $self->{'dirname'};

	defined $dirname or die("Missing 'dirname' parameter");
	opendir my $dh, $dirname or die(sprintf('Could not open %s: %s', $dirname, $!));
	my @files = grep { $_ ne '.' && $_ ne '..' && -d "$dirname/$_" } readdir $dh;
	closedir $dh;

	@files;
}

=item getAll([ $dirname ])

 Get list of files and directories inside directory

 Param string $dirname OPTIONAL Directory - Default $self->{'dirname'}
 Return list of files and directories or die on failure

=cut

sub getAll
{
	my $self = shift;
	my $dirname = shift // $self->{'dirname'};

	defined $dirname or die("Missing 'dirname' parameter");
	opendir my $dh, $dirname or die(sprintf('Could not open %s: %s', $dirname, $!));
	my @files = grep { $_ ne '.' && $_ ne '..' } readdir $dh;
	closedir $dh;

	@files;
}

=item isEmpty([ $dirname ])

 Is directory empty?

 Param string $dirname OPTIONAL Directory - Default $self->{'dirname'}
 Return bool TRUE if the given directory is empty, FALSE otherwise - die on failure

=cut

sub isEmpty
{
	my $self = shift;
	my $dirname = shift // $self->{'dirname'};

	defined $dirname or die("Missing 'dirname' parameter");
	opendir my $dh, $dirname or die(sprintf('Could not open %s: %s', $dirname, $!));

	for my $file(readdir $dh) {
		if($file ne '.' && $file ne '..') {
			closedir $dh;
			return 0;
		}
	}

	closedir $dh;

	1;
}

=item mode($mode [, $dirname ])

 Set directory mode

 Param string $mode Directory mode
 Param string $dirname OPTIONAL Directory (default $self->{'dirname'})
 Return int 0 on success or die on failure

=cut

sub mode
{
	my ($self, $mode, $dirname) = @_;

	$dirname //= $self->{'dirname'};

	defined $mode or die("Missing 'mode' parameter");
	defined $dirname or die("Missing 'dirname' parameter");
	debug(sprintf('Changing mode for %s to %s', $dirname, $mode));
	chmod $mode, $dirname or die(sprintf('Could not change mode for %s: %s', $dirname, $!));

	0;
}

=item owner($owner, $group, [, $dirname ])

 Set directory owner and group

 Param string $owner Owner
 Param string $group Group
 Param string $dirname OPTIONAL Directory (default $self->{'dirname'})
 Return int 0 on success, die on failure

=cut

sub owner
{
	my ($self, $owner, $group, $dirname) = @_;

	$dirname //= $self->{'dirname'};

	defined $owner or die("Mistting 'owner' parameter");
	defined $group or die("Mistting 'group' parameter");
	defined $dirname or die("Missing 'dirname' parameter");

	my $uid = ($owner =~ /^\d+$/) ? $owner : getpwnam($owner) // -1;
	my $gid = ($group =~ /^\d+$/) ? $group : getgrnam($group) // -1;

	debug(sprintf('Changing owner and group for %s to %s: %s', $dirname, $uid, $gid));
	chown $uid, $gid, $self->{'dirname'} or die(sprintf('Could not change owner and group for %s: %s', $dirname, $!));

	0;
}

=item make([ \%options ])

 Create directory

 Param hash \%options OPTIONAL Options:
    mode:  Directory mode
    user:  Directory owner
    group: Directory group
 Return int 0 on success, die on failure

=cut

sub make
{
	my ($self, $options) = @_;

	$options = { } unless defined $options && ref $options eq 'HASH';

	defined $self->{'dirname'} or die("Attribute 'dirname' is not defined");
	! -f $self->{'dirname'} or die(sprintf('Could not create %s: Already exists as file.', $self->{'dirname'}));

	unless(-d $self->{'dirname'}) {
		debug($self->{'dirname'});
		my @createdDirs = mkpath($self->{'dirname'}, { error => \my $errStack });

		if(@{$errStack}) {
			my $errorStr = '';

			for my $diag (@{$errStack}) {
				my ($file, $message) = %{$diag};
				$errorStr .= ($file eq '') ? "general error: $message\n" : "problem unlinking $file: $message\n";
			}

			die(sprintf('Could not create %s: %s', $errorStr));
		}

		for my $dir(@createdDirs) {
			if(defined $options->{'mode'}) {
				$self->mode($options->{'mode'}, $dir);
			}

			if(defined $options->{'user'} || defined $options->{'group'}) {
				$self->owner($options->{'user'} // -1, $options->{'group'} // -1, $dir);
			}
		}
	} else {
		debug(sprintf('%s already exists. Setting its permissions...', $self->{'dirname'}));

		if(defined $options->{'mode'}) {
			$self->mode($options->{'mode'});
		}

		if(defined $options->{'user'} || defined $options->{'group'}) {
			$self->owner($options->{'user'} // -1, $options->{'group'} // -1, $self->{'dirname'});
		}
	}

	0;
}

=item remove([ $dirname ])

 Remove directory recursively

 Param string $dirname OPTIONAL Directory (default $self->{'dirname'})
 Return int 0 on success, die on failure

=cut

sub remove
{
	my $self = shift;
	my $dirname = shift // $self->{'dirname'};

	defined $dirname or die("Missing 'dirname' parameter");

	if (-d $dirname) {
		debug($dirname);
		remove_tree($dirname, { error => \my $errStack });

		if(@{$errStack}) {
			my $errorStr = '';

			for my $diag (@{$errStack}) {
				my ($file, $message) = %{$diag};
				$errorStr .= ($file eq '') ? "general error: $message\n" : "problem unlinking $file: $message\n";
			}

			die(sprintf('Could not delete %s: %s', $errorStr));
		}
	}

	0;
}

=item rcopy($destDir [, \%options ])

 Copy directory recusively

 Note: Symlinks are not followed.

 Param string $destDir Destination directory
 Param hash \%options OPTIONAL Options:
   excludeDir:  String representing a regexp for excluding a list of directories from copy
   excludeFile: String representing a regexp for excluding a list of files from copy
   preserve:    If true, copy file attributes (uid, gid and mode)
 Return int 0 on success, die on failure

=cut

sub rcopy
{
	my ($self, $destDir, $options) = @_;

	$options = { } unless defined $options && ref $options eq 'HASH';

	defined $self->{'dirname'} or die("Attribute 'dirname' is not defined");

	my $excludeDir = (defined $options->{'excludeDir'}) ? qr/$options->{'excludeDir'}/ : undef;
	my $excludeFile = (defined $options->{'excludeFile'}) ? qr/$options->{'excludeFile'}/ : undef;

	opendir my $dh, $self->{'dirname'} or die(sprintf('Could not open: %s', $self->{'dirname'}, $!));

	for my $entry (readdir $dh) {
		if($entry ne '.' && $entry ne '..') {
			my $src = "$self->{'dirname'}/$entry";
			my $dst = "$destDir/$entry";

			if (-d $src) {
				unless($excludeDir && $src =~ /$excludeDir/) {
					my $opts = {};

					if($options->{'preserve'}) {
						my (undef, undef, $mode, undef, $uid, $gid) = lstat($src);
						$opts = { user => $uid, mode => $mode & 07777, group => $gid }
					}

					debug(sprintf('%s to %s', $src, $dst));
					iMSCP::Dir->new( dirname => $dst )->make($opts);
					iMSCP::Dir->new( dirname => $src )->rcopy($dst, $options);
				}
			} elsif(!$excludeFile || $src !~ /$excludeFile}/) {
				debug(sprintf('%s to %s', "$self->{'dirname'}/$entry", "$destDir/$entry"));
				(iMSCP::File->new( filename => $src )->copyFile( $dst, $options ) == 0) or die(sprintf(
					'Could not copy file %s into %s: %s', $src, $dst, getLastError()
				));
			}
		}
	}

	closedir $dh;

	0;
}

=item moveDir($destDir)

 Move directory

 Param string $destDir Destination directory
 Return int 0 on success, die on failure

=cut

sub moveDir
{
	my ($self, $destDir) = @_;

	defined $self->{'dirname'} or die("Attribut 'dirname' is not defined");
	-d $self->{'dirname'} or die(sprintf("Directory %s doesn't exits", $self->{'dirname'}));
	debug(sprintf('%s to %s', $self->{'dirname'}, $destDir));
	move $self->{'dirname'}, $destDir or die(sprintf('Could not move %s to %s: %s', $self->{'dirname'}, $destDir, $!));

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize object

 iMSCP::Dir

=cut

sub _init
{
	my $self = shift;

	$self->{'dirname'} //= undef;

	$self;
}

=back

=head1 AUTHOR

Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
