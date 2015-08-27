=head1 NAME

 iMSCP::Mount - Library for mounting/unmounting file systems

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

package iMSCP::Mount;

use strict;
use warnings;
use File::Spec;
use iMSCP::Dir;
use iMSCP::Execute;
use iMSCP::File;

our @EXPORT_OK = qw( mount umount );

=head1 DESCRIPTION

 Library for mounting/unmounting file systems.

=head1 FUNCTIONS

=over 4

=item mount(\%options)

 Mount the file system as specified in the given mount options

 Param hash \%options Hash describing mount option:
   fs_spec: This option describes the block special device or remote filesystem to be mounted.
   fs_file: This  option  describes the mount point for the filesystem.
   fs_vfstype: This option describes the type of the filesystem.
   fs_mntops: This option describes the mount options associated with the filesystem.
 Return int 0 on success, die on failure

=cut

sub mount
{
 	my $options = shift;

	$options = { } unless defined $options && ref $options eq 'HASH';
	defined $options->{$_} or die(sprintf('The %s option is not defined', $_)) for qw/
		fs_spec fs_file fs_spec fs_vfstype fs_mntops
	/;

	my $fsSpec = File::Spec->canonpath($options->{'fs_spec'});
	my $fsFile = File::Spec->canonpath($options->{'fs_file'});

	if (execute("mount 2>/dev/null | grep -q " . escapeShell(" on $fsFile "))) {
		if (-f $fsSpec) {
			iMSCP::File->new( filename => $fsFile )->save();
		} elsif (! -d $fsFile) {
			iMSCP::Dir->new( dirname => $fsFile )->make();
		}

		my @cmdArgs = (
			'-t', $options->{'fs_vsftype'},
			'-o', escapeShell($options->{'fs_mntops'}),
			escapeShell($fsSpec),
			escapeShell($fsFile)
		);

		my ($stdout, $stderr);
		execute("mount @cmdArgs", \$stdout, \$stderr) == 0 or die(sprintf(
			'Could not mount %s on %s: %s', $options->{'fs_spec'}, $fsFile, $stderr || 'Unknown error'
		));
	}

	if($options->{'fs_mntops'} =~ /(r(?:shared|private|slave|unbindable))/) { # handle shared subtrees operations
		my ($stdout, $stderr);
		execute("mount --make-$1 $fsFile", \$stdout, \$stderr) == 0 or die(sprintf(
			'Could not make %s a %s subtree: %s', $fsFile, $1, $stderr || 'Unknown error'
		));
	}

	0;
}

=item umount($fsFile)

 Umount the given file system

 Note: In case of a partial path, any file systems below this path will be umounted.

 Param string $fsFile Partial or full path of file system to umount
 Return int 0 on success, die on failure

=cut

sub umount
{
	my $fsFile = shift;

	defined $fsFile or die('The $fsFile parameter is not defined');
	$fsFile = File::Spec->canonpath($fsFile);

	my $fsFileFound;
	do {
		my $stdout;
		execute("mount 2>/dev/null | grep ' on $fsFile\\(/\\| \\)' | head -n 1 | cut -d ' ' -f 3", \$stdout) == 0 or die(
			'Could not run mount command.'
		);

		$fsFileFound = $stdout;
		if ($fsFileFound) { # We do not trap errors here (expected for dangling mounts)
			execute("umount -l $fsFileFound 2>/dev/null", \$stdout, \$stderr);
		}
	} while ($fsFileFound);

	0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
