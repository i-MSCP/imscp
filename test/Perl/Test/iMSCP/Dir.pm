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

package Test::iMSCP::Dir;

use strict;
use warnings;
use Test::More import => [ 'require_ok', 'ok' ];

#
## iMSCP::Dir::getFiles() tests
#

sub getFilesDieOnMissingDirnameParameter
{
	local $@;
	eval { iMSCP::Dir->new()->getFiles() };
	$@ && $@ =~ /Missing 'dirname' parameter/;
}

sub getFilesDieIfCannotOpenDirname
{
	my $assetDir = shift;

	local $@;
	eval { iMSCP::Dir->new( dirname => "$assetDir/tmp/d1" )->getFiles() };
	$@ && $@ =~ /Could not open/;
}

sub getFilesReturnExpectedFilenames
{
	my $assetDir = shift;
	my @expectedFileNames = ( 'f1.php', 'f2.txt', 'f3.txt' );
	my @fileNames = iMSCP::Dir->new( dirname => "$assetDir/files" )->getFiles();

	return 0 unless @fileNames == 3;

	for my $file(@expectedFileNames) {
		if(not grep { $file eq $_ } @fileNames) {
			return 0;
		}
	}

	1;
}

sub getFilesReturnExpectedFilteredFileType
{
	my $assetDir = shift;
	my @expectedFileNames = ( 'f2.txt', 'f3.txt' );
	my @fileNames = iMSCP::Dir->new( dirname => "$assetDir/files", fileType => '.txt' )->getFiles();

	return 0 unless @fileNames == 2;

	for my $file(@expectedFileNames) {
		if(not grep { $file eq $_ } @fileNames) {
			return 0;
		}
	}

	1;
}

#
## iMSCP::Dir::getDirs() tests
#

sub getDirsDieOnMissingDirnameParameter
{
	local $@;
	eval { iMSCP::Dir->new()->getFiles(); };
	$@ && $@ =~ /Missing 'dirname' parameter/;
}

sub getDirsDieIfCannotOpenDirname
{
	my $assetDir = shift;
	local $@;
	eval { iMSCP::Dir->new( dirname => '$assetDir/tmp/d1' )->getDirs() };
	$@ && $@ =~ /Could not open/;
}

sub getDirsReturnExpectedDirnames
{
	my $assetDir = shift;
	my @expectedDirnames = ( 'd1', 'd2', 'd3' );
	my @dirnames = iMSCP::Dir->new( dirname => "$assetDir/files" )->getDirs();

	return 0 unless @dirnames == 3;

	for my $dir(@expectedDirnames) {
		if(not grep { $dir eq $_ } @dirnames) {
			return 0;
		}
	}

	1;
}

## iMSCP::Dir::getAll() tests

sub getAllDieOnMissingDirnameParameter
{
	local $@;
	eval { iMSCP::Dir->new()->getAll() };
	$@ && $@ =~ /Missing 'dirname' parameter/;
}

sub getAllDieIfCannotOpenDirname
{
	my $assetDir = shift;
	local $@;
	eval { iMSCP::Dir->new( dirname => '$assetDir/tmp/d1' )->getAll() };
	$@ && $@ =~ /Could not open/;
}

sub getAllReturnExpectedDirnames
{
	my $assetDir = shift;
	my @expectedDirnames = ( 'f2.txt', 'd1', 'd3', 'f1.php', 'd2', 'f3.txt' );
	my @dirnames = iMSCP::Dir->new( dirname => "$assetDir/files" )->getAll();

	return 0 unless @dirnames == 6;

	for my $dir(@expectedDirnames) {
		if(not grep { $dir eq $_ } @dirnames) {
			return 0;
		}
	}

	1;
}

#
## iMSCP::Dir::isEmpty() tests
#

sub isEmptyDieOnMissingDirnameParameter
{
	local $@;
	eval { iMSCP::Dir->new()->isEmpty() };
	$@ && $@ =~ /Missing 'dirname' parameter/;
}

sub isEmptyDieIfCannotOpenDirname
{
	my $assetDir = shift;
	local $@;
	eval { iMSCP::Dir->new( dirname => '$assetDir/tmp/d1' )->isEmpty() };
	$@ && $@ =~ /Could not open/;
}

sub isEmptyReturnFalseIfDirnameIsNotEmpty
{
	my $assetDir = shift;
	! iMSCP::Dir->new( dirname => "$assetDir/files" )->isEmpty();
}

#
## iMSCP::Dir::make() tests
#

sub makeDieOnMissingDirnameAttribute
{
	local $@;
	eval { iMSCP::Dir->new()->make() };
	$@ && $@ =~ /Attribute 'dirname' is not defined/;
}

sub makeDieIfDirnameAlreadyExistsAsFile
{
	my $assetDir = shift;
	local $@;
	eval { iMSCP::Dir->new( dirname => "$assetDir/tmp/f1" )->make() };
	$@ && $@ =~ /Already exists as file/;
}

sub makeCanCreateDir
{
	local $@;
	eval { iMSCP::Dir->new( dirname => '/tmp/d1' )->make() };
	unless($@) {
		grep { 'd1' eq $_ } iMSCP::Dir->new( dirname => '/tmp' )->getDirs();
	} else {
		0;
	}
}

sub makeCanCreatePath
{
	local $@;
	eval { iMSCP::Dir->new( dirname => '/tmp/d1/d2/d3' )->make() };
	unless($@) {
		if(grep { 'd2' eq $_ } iMSCP::Dir->new( dirname => '/tmp/d1' )->getDirs()) {
			grep { 'd3' eq $_ } iMSCP::Dir->new( dirname => '/tmp/d1/d2' )->getDirs();
		} else {
			0;
		}
	} else {
		0;
	}
}

#
## iMSCP::Dir::remove() tests
#

sub removeDieOnMissingDirnameParameter
{
	local $@;
	eval { iMSCP::Dir->new()->remove() };
	$@ && $@ =~ /Missing 'dirname' parameter/;
}

sub removeCanRemoveDir
{
	local $@;
	eval { iMSCP::Dir->new( dirname => '/tmp/d1/d2/d3' )->remove() };
	unless($@) {
		iMSCP::Dir->new( dirname => '/tmp/d1/d2' )->isEmpty();
	} else {
		0;
	}
}

sub removeCanRemovePath
{
	local $@;
	eval { iMSCP::Dir->new( dirname => '/tmp/d1' )->remove() };
	unless($@) {
		not grep { 'd1' eq $_ } iMSCP::Dir->new( dirname => '/tmp' )->getDirs() ;
	} else {
		0;
	}
}

#
## iMSCP::Dir::rcopy() tests
#

# TODO

#
## iMSCP::Dir::moveDir() tests
#

# TODO

#
## iMSCP::Dir::owner() tests
#

# TODO

#
## iMSCP::Dir::mode() tests
#

# TODO

#
# Run unit tests
#

sub runTests
{
	my $assetDir = shift;

	if(require_ok('iMSCP::Dir')) {
		ok( getFilesDieOnMissingDirnameParameter(), 'iMSCP::Dir::getFiles() die on missing dirname parameter' );
		ok( getFilesDieIfCannotOpenDirname($assetDir), 'iMSCP::Dir::getFiles() die if cannot open dirname' );
		ok( getFilesReturnExpectedFilenames($assetDir), 'iMSCP::Dir::getFiles() return expected filenames' );
		ok( getFilesReturnExpectedFilteredFileType($assetDir), 'iMSCP::Dir::getFiles() return expected filtered file type' );

		ok( getDirsDieOnMissingDirnameParameter(), 'iMSCP::Dir::getDirs() die on missing dirname parameter' );
		ok( getDirsDieIfCannotOpenDirname(), 'iMSCP::Dir::getDirs() die if cannot open dirname' );
		ok( getDirsReturnExpectedDirnames($assetDir), 'iMSCP::Dir::getDirs() return expected dirnames' );

		ok( getAllDieOnMissingDirnameParameter(), 'iMSCP::Dir::getAll() die on missing dirname parameter' );
		ok( getAllDieIfCannotOpenDirname(), 'iMSCP::Dir::getAll() die if cannot open dirname' );
		ok( getAllReturnExpectedDirnames($assetDir), 'iMSCP::Dir::getAll() return expected dirnames and filenames' );

		ok( isEmptyDieOnMissingDirnameParameter(), 'iMSCP::Dir::isEmpty() die on missing dirname parameter' );
		ok( isEmptyDieIfCannotOpenDirname(), 'iMSCP::Dir::isEmpty() die if cannot open dirname' );
		ok( isEmptyReturnFalseIfDirnameIsNotEmpty($assetDir), 'iMSCP::Dir::isEmpty() return false if dirname is not empty' );

		ok( makeDieOnMissingDirnameAttribute(), 'iMSCP::Dir::make() die on missing dirname attribute' );
		ok( makeDieIfDirnameAlreadyExistsAsFile($assetDir), 'iMSCP::Dir::make() die if dirname already exists as file' );
		ok( makeCanCreateDir(), 'iMSCP::Dir::make() can create dir' );
		ok( makeCanCreatePath(), 'iMSCP::Dir::make() can create path' );

		ok( removeDieOnMissingDirnameParameter(), 'iMSCP::Dir::remove() die on missing dirname parameter' );
		ok( removeCanRemoveDir(), 'iMSCP::Dir::remove() can remove dir' );
		ok( removeCanRemovePath(), 'iMSCP::Dir::remove() can remove path' );
	}

	21; # Number of test that must be run in normal context
}

1;
__END__
