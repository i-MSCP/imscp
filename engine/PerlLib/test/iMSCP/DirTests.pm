package iMSCP::DirTests;

use lib '/usr/local/src/imscp/engine/PerlLib';
use Test::Simple tests => 20;
use iMSCP::Dir;

# TODO make this configurable
my $assetsDir = '/usr/local/src/imscp/engine/PerlLib/test/TestAsset';

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
	local $@;
	eval { iMSCP::Dir->new( dirname => "$assetsDir/tmp/d1" )->getFiles() };
	$@ && $@ =~ /Could not open/;
}

sub getFilesReturnExpectedFilenames
{
	@expectedFileNames = ( 'f1.php', 'f2.txt', 'f3.txt' );
	@fileNames = iMSCP::Dir->new( dirname => "$assetsDir/files" )->getFiles();
	@fileNames == @expectedFileNames;
}

sub getFilesReturnExpectedFilteredFileType
{
	@expectedFileNames = ( 'f2.txt', 'f3.txt' );
	@fileNames = iMSCP::Dir->new( dirname => "$assetsDir/files", fileType => '.txt' )->getFiles();
	@fileNames == @expectedFileNames;
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
	local $@;
	eval { iMSCP::Dir->new( dirname => '$assetsDir/tmp/d1' )->getDirs() };
	$@ && $@ =~ /Could not open/;
}

sub getDirsReturnExpectedDirnames
{
	@expectedDirnames = ( 'd1', 'd2', 'd3' );
	@dirnames = iMSCP::Dir->new( dirname => "$assetsDir/files" )->getDirs();
	@dirnames == @expectedDirnames;
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
	local $@;
	eval { iMSCP::Dir->new( dirname => '$assetsDir/tmp/d1' )->getAll() };
	$@ && $@ =~ /Could not open/;
}

sub getAllReturnExpectedDirnames
{
	@expectedDirnames = ( 'f2.txt', 'd1', 'd3', 'f1.php', 'd2', 'f3.txt' );
	@dirnames = iMSCP::Dir->new( dirname => "$assetsDir/files" )->getAll();
	@dirnames == @expectedDirnames;
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
	local $@;
	eval { iMSCP::Dir->new( dirname => '$assetsDir/tmp/d1' )->isEmpty() };
	$@ && $@ =~ /Could not open/;
}

sub isEmptyReturnFalseIfDirnameIsNotEmpty
{
	! iMSCP::Dir->new( dirname => "$assetsDir/files" )->isEmpty();
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
	local $@;
	eval { iMSCP::Dir->new( dirname => "$assetsDir/tmp/f1" )->make() };
	$@ && $@ =~ /Already exists as file/;
}

sub makeCanCreateDir
{
	local $@;
	eval { iMSCP::Dir->new( dirname => '/tmp/d1' )->make() };
	unless($@) {
		@dirnames = iMSCP::Dir->new( dirname => '/tmp' )->getDirs();
		grep { 'd1' eq $_ } @dirnames ;
	} else {
		0;
	}
}

sub makeCanCreatePath
{
	local $@;
	eval { iMSCP::Dir->new( dirname => '/tmp/d1/d2/d3' )->make() };
	unless($@) {
		@expectedDirnames = ( 'd1' );
		@dirnames = iMSCP::Dir->new( dirname => '/tmp/d1' )->getDirs();

		if(@dirnames == @expectedDirnames) {
			@expectedDirnames = ( 'd2' );
			@dirnames = iMSCP::Dir->new( dirname => '/tmp/d1/d2' )->getDirs();
			@dirnames == @expectedDirnames;
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
		@dirnames = iMSCP::Dir->new( dirname => '/tmp' )->getDirs();
		not grep { 'd1' eq $_ } @dirnames ;
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
# Run tests
#

sub runTests
{
	ok( getFilesDieOnMissingDirnameParameter(), 'iMSCP::Dir::getFiles() die on missing dirname parameter' );
	ok( getFilesDieIfCannotOpenDirname(), 'iMSCP::Dir::getFiles() die if cannot open dirname' );
	ok( getFilesReturnExpectedFilenames(), 'iMSCP::Dir::getFiles() return expected filenames' );
	ok( getFilesReturnExpectedFilteredFileType(), 'iMSCP::Dir::getFiles() return expected filtered file type' );

	ok( getDirsDieOnMissingDirnameParameter(), 'iMSCP::Dir::getDirs() die on missing dirname parameter' );
	ok( getDirsDieIfCannotOpenDirname(), 'iMSCP::Dir::getDirs() die if cannot open dirname' );
	ok( getDirsReturnExpectedDirnames(), 'iMSCP::Dir::getDirs() return expected dirnames' );

	ok( getAllDieOnMissingDirnameParameter(), 'iMSCP::Dir::getAll() die on missing dirname parameter' );
	ok( getAllDieIfCannotOpenDirname(), 'iMSCP::Dir::getAll() die if cannot open dirname' );
	ok( getAllReturnExpectedDirnames(), 'iMSCP::Dir::getAll() return expected dirnames and filenames' );

	ok( isEmptyDieOnMissingDirnameParameter(), 'iMSCP::Dir::isEmpty() die on missing dirname parameter' );
	ok( isEmptyDieIfCannotOpenDirname(), 'iMSCP::Dir::isEmpty() die if cannot open dirname' );
	ok( isEmptyReturnFalseIfDirnameIsNotEmpty(), 'iMSCP::Dir::isEmpty() return false if dirname is not empty' );

	ok( makeDieOnMissingDirnameAttribute(), 'iMSCP::Dir::make() die on missing dirname attribute' );
	ok( makeDieIfDirnameAlreadyExistsAsFile(), 'iMSCP::Dir::make() die if dirname already exists as file' );
	ok( makeCanCreateDir(), 'iMSCP::Dir::make() can create dir' );
	ok( makeCanCreatePath(), 'iMSCP::Dir::make() can create path' );

	ok( removeDieOnMissingDirnameParameter(), 'iMSCP::Dir::remove() die on missing dirname parameter' );
	ok( removeCanRemoveDir(), 'iMSCP::Dir::remove() can remove dir' );
	ok( removeCanRemovePath(), 'iMSCP::Dir::remove() can remove path' );
}

# TODO Should be run from test processor
runTests();

1;
__END__
