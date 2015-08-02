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
use Test::More;

sub newDieOnMissingDirnameOption
{
	local $@;
	eval { iMSCP::Dir->new() };
	$@ && $@ =~ /Option dirname is not defined/;
}

sub modeDieOnMissingModeParameter
{
	local $@;
	eval { iMSCP::Dir->new( dirname => '/tmp/foo' )->mode() };
	$@ && $@ =~ /Missing mode parameter/;
}

sub modeDieOnInexistentDirname
{
	local $@;
	eval { iMSCP::Dir->new( dirname => '/tmp/foo' )->mode( 0555 ) };
	$@ && $@ =~ /Could not set mode/;
}

sub modeSetExpectedModeOnDirname
{
	setupTestEnv();

	iMSCP::Dir->new( dirname => '/tmp/foo' )->mode(02555);
	sprintf('%o', (stat('/tmp/foo'))[2] & 07777) == 2555;
}

sub ownerDieOnMissingOwnerParameter
{
	local $@;
	eval { iMSCP::Dir->new( dirname => '/tmp/foo' )->owner() };
	$@ && $@ =~ /Missing owner parameter/;
}

sub ownerDieOnMissingGroupParameter
{
	local $@;
	eval { iMSCP::Dir->new( dirname => '/tmp/foo' )->owner( 'nobody' ) };
	$@ && $@ =~ /Missing group parameter/;
}

sub ownerDieOnInexistentUser
{
	local $@;
	eval { iMSCP::Dir->new( dirname => '/tmp/foo' )->owner( 'quux', 'nogroup' ) };
	$@ && $@ =~ /inexistent user/;
}

sub ownerDieOnInexistentGroup
{
	local $@;
	eval { iMSCP::Dir->new( dirname => '/tmp/foo' )->owner( 'nobody', 'quux' ) };
	$@ && $@ =~ /inexistent group/;
}

sub ownerSetExpectedOwnerAndGroup
{
	setupTestEnv();

	iMSCP::Dir->new( dirname => '/tmp/foo' )->owner( 'nobody', 'nogroup' );
	(stat('/tmp/foo'))[4] == 65534 && (stat('/tmp/foo'))[5] == 65534;
}

sub getFilesDieIfCannotOpenDirname
{
	local $@;
	eval { iMSCP::Dir->new( dirname => '/tmp/foo/quux' )->getFiles() };
	$@ && $@ =~ /Could not open/;
}

sub getFilesReturnExpectedFilenames
{
	setupTestEnv();

	[ sort iMSCP::Dir->new( dirname => '/tmp/foo' )->getFiles() ];
}

sub getFilesReturnExpectedFilteredFiletypes
{
	setupTestEnv();

	[ sort iMSCP::Dir->new( dirname => '/tmp/foo', fileType => '.php' )->getFiles() ];
}

sub getDirsDieIfCannotOpenDirname
{
	local $@;
	eval { iMSCP::Dir->new( dirname => '/tmp/foo/quux' )->getDirs() };
	$@ && $@ =~ /Could not open/;
}

sub getDirsReturnExpectedDirnames
{
	setupTestEnv();

	[ sort iMSCP::Dir->new( dirname => '/tmp/foo' )->getDirs() ] ;
}

sub getAllDieIfCannotOpenDirname
{
	local $@;
	eval { iMSCP::Dir->new( dirname => '/tmp/foor/quux' )->getAll() };
	$@ && $@ =~ /Could not open/;
}

sub getAllReturnExpectedDirnamesAndFilenames
{
	setupTestEnv();

	[ sort iMSCP::Dir->new( dirname => '/tmp/foo' )->getAll() ];
}

sub isEmptyDieIfCannotOpenDirname
{
	local $@;
	eval { iMSCP::Dir->new( dirname => '/tmp/foo/quux' )->isEmpty() };
	$@ && $@ =~ /Could not open/;
}

sub isEmptyReturnTrueIfDirnameIsEmpty
{
	setupTestEnv();

	iMSCP::Dir->new( dirname => '/tmp/foo/qux' )->isEmpty();
}

sub isEmptyReturnFalseIfDirnameIsNotEmpty
{
	setupTestEnv();

	! iMSCP::Dir->new( dirname => '/tmp/foo' )->isEmpty();
}

sub makeDieIfDirnameAlreadyExistsAsFile
{
	setupTestEnv();

	local $@;
	eval { iMSCP::Dir->new( dirname => '/tmp/foo/bar.txt' )->make() };
	$@ && $@ =~ /File exists/;
}

sub makeCanCreateDir
{
	setupTestEnv();

	local $@;
	eval { iMSCP::Dir->new( dirname => '/tmp/foo/quux' )->make() };
	!@;
}

sub makeCanCreatePath
{
	setupTestEnv();

	local $@;
	eval { iMSCP::Dir->new( dirname => '/tmp/foo/quux/foo/bar/baz/corge/grault' )->make() };
	!@;
}

sub makeSetExpectedOwnerOnNewlyCreatedDirname
{
	setupTestEnv();

	iMSCP::Dir->new( dirname => '/tmp/foo/quux' )->make( { user => 'nobody' } );
	(stat('/tmp/foo/quux'))[4] == 65534;
}

sub makeSetExpectedGroupOnNewlyCreatedDirname
{
	setupTestEnv();

	iMSCP::Dir->new( dirname => '/tmp/foo/quux' )->make( { group => 'nogroup' } );
	(stat('/tmp/foo/quux'))[5] == 65534;
}

sub makeSetExpectedModeOnNewlyCreatedDirname
{
	setupTestEnv();

	iMSCP::Dir->new( dirname => '/tmp/foo/quux' )->make( { mode => 02555 } );
	sprintf('%o', (stat('/tmp/foo/quux'))[2] & 07777) == 2555;
}

sub makeSetExpectedOwnerOnExistentDirname
{
	setupTestEnv();

	iMSCP::Dir->new( dirname => '/tmp/foo' )->make( { user => 'nobody' } );
	(stat('/tmp/foo'))[4] == 65534;
}

sub makeSetExpectedGroupOnExistentDirname
{
	setupTestEnv();

	iMSCP::Dir->new( dirname => '/tmp/foo' )->make( { group => 'nogroup' } );
	(stat('/tmp/foo'))[5] == 65534;
}

sub makeSetExpectedModeOnExistentDirname
{
	setupTestEnv();

	iMSCP::Dir->new( dirname => '/tmp/foo' )->make( { mode => 02555 } );
	sprintf('%o', (stat('/tmp/foo'))[2] & 07777) == 2555;
}

sub removeCanRemoveDir
{
	setupTestEnv();

	local $@;
	eval { iMSCP::Dir->new( dirname => '/tmp/foo/qux' )->remove() };
	!$@;
}

sub removeCanRemovePath
{
	setupTestEnv();

	local $@;
	eval { iMSCP::Dir->new( dirname => '/tmp/foo/bar' )->remove() };
	!$@;
}

sub rcopyDieOnMissingTargetDirParameter
{
	local $@;
	eval { iMSCP::Dir->new( dirname => '/tmp/foo' )->rcopy() };
	$@ && $@ =~ /Missing targetdir parameter/;
}

sub rcopyDieIfCannotOpenDirname
{
	local $@;
	eval { iMSCP::Dir->new( dirname => '/tmp/foo/quux' )->rcopy( '/tmp/bar' ) };
	$@ && $@ =~ /No such file or directory/;
}

sub rcopyCanCopyDirnameToTargetDir
{
	setupTestEnv();
	symlink '/foo/baz', '/foo/quux';

	local $@;
	eval { iMSCP::Dir->new( dirname => '/tmp/foo' )->rcopy( '/tmp/bar' ) };
	!$@;
}

sub rcopyDoNotPreserveFileAttributes
{
	setupTestEnv();
	chown 65534, 65534, '/tmp/foo/bar';
	chmod 02555, '/tmp/foo/bar';

	iMSCP::Dir->new( dirname => '/tmp/foo' )->rcopy( '/tmp/bar' );
	my @stat = stat('/tmp/bar/bar');
	$stat[4] != 65534 && $stat[5] != 65534 && sprintf('%o', $stat[2] & 07777) ne 2555;
}

sub rcopyPreserveFileAttributes
{
	setupTestEnv();
	chown 65534, 65534, '/tmp/foo/bar', '/tmp/foo/baz/foo.txt';
	chmod 02555, '/tmp/foo/bar';
	chmod 0640, '/tmp/foo/baz/foo.txt';

	iMSCP::Dir->new( dirname => '/tmp/foo' )->rcopy( '/tmp/bar', 1);

	my @stat = stat('/tmp/bar/bar');
	my $ret1 = $stat[4] == 65534 && $stat[5] == 65534 && sprintf('%o', $stat[2] & 07777) eq 2555;

	@stat = stat('/tmp/bar/baz/foo.txt');
	my $ret2 = $stat[4] == 65534 && $stat[5] == 65534 && sprintf('%o', $stat[2] & 07777) eq 640;

	$ret1 && $ret2;
}

sub moveDirDieOnMissingDestdirParameter
{
	local $@;
	eval { iMSCP::Dir->new( dirname => '/tmp/foo')->moveDir() };
	$@ && $@ =~ /Missing destdir parameter/;
}

sub moveDirDieOnInexistentDirname
{
	local $@;
	eval { iMSCP::Dir->new( dirname => '/tmp/baz' )->moveDir( '/tmp/bar/foo' ) };
	$@ && $@ =~ /doesn't exits/;
}

sub moveDirCanMoveDirnameToDestDir
{
	setupTestEnv();
	local $@;
	eval { iMSCP::Dir->new( dirname => '/tmp/foo' )->moveDir( '/tmp/bar/foo' ) };
	!$@;
}

my $assetDir;

sub cleanupTestEnv
{
	system 'rm', '-Rf', '/tmp/foo', '/tmp/bar';
}

sub setupTestEnv
{
	cleanupTestEnv();
	system 'cp', '-R', '-f', $assetDir, '/tmp/foo';
	mkdir '/tmp/foo/qux';
	mkdir '/tmp/bar';
}

sub runUnitTests
{
	$assetDir = shift . '/foo';
	cleanupTestEnv();
	plan tests => 40;  # Number of tests planned for execution

	if(require_ok('iMSCP::Dir')) {
		eval {
			# new()
			new_ok 'iMSCP::Dir', [ { dirname => '/tmp/foo' } ], 'iMSCP::Dir::new()';
			ok newDieOnMissingDirnameOption, 'iMSCP::Dir::new() die on missing dirname option';

			# mode()
			ok modeDieOnMissingModeParameter, 'iMSCP::Dir::mode() die on missing mode parameter';
			ok modeDieOnInexistentDirname, 'iMSCP::Dir::mode() die on inexistent dirname';
			ok modeSetExpectedModeOnDirname, 'iMSCP::Dir::mode() set expected mode on dirname';

			# owner()
			ok ownerDieOnMissingOwnerParameter, 'iMSCP::Dir::owner() die on missing owner parameter';
			ok ownerDieOnMissingGroupParameter, 'iMSCP::Dir::owner() die on missing group parameter';
			ok ownerDieOnInexistentUser, 'iMSCP::Dir::owner() die on inexistent user';
			ok ownerDieOnInexistentGroup, 'iMSCP::Dir::owner() die on inexistent group';
			ok ownerSetExpectedOwnerAndGroup, 'iMSCP::Dir::owner() set expected owner and group on dirname';

			# getFiles()
			ok getFilesDieIfCannotOpenDirname, 'iMSCP::Dir::getFiles() die if cannot open dirname';
			is_deeply getFilesReturnExpectedFilenames, [ sort 'bar.txt', 'baz.txt', 'foo.php' ],
				'iMSCP::Dir::getFiles() return expected filenames';
			is_deeply getFilesReturnExpectedFilteredFiletypes, [ 'foo.php' ],
				'iMSCP::Dir::getFiles() return expected filtered file type';

			# getDirs()
			ok getDirsDieIfCannotOpenDirname, 'iMSCP::Dir::getDirs() die if cannot open dirname';
			is_deeply getDirsReturnExpectedDirnames, [ sort 'bar', 'baz', 'foo', 'qux' ],
				'iMSCP::Dir::getDirs() return expected dirnames';

			# getAll()
			ok getAllDieIfCannotOpenDirname, 'iMSCP::Dir::getAll() die if cannot open dirname';
			is_deeply getAllReturnExpectedDirnamesAndFilenames,
				[ sort 'bar', 'baz', 'foo', 'qux', 'bar.txt', 'baz.txt', 'foo.php' ],
				'iMSCP::Dir::getAll() return expected dirnames and filenames';

			# isEmpty()
			ok isEmptyDieIfCannotOpenDirname, 'iMSCP::Dir::isEmpty() die if cannot open dirname';
			ok isEmptyReturnTrueIfDirnameIsEmpty, 'iMSCP::Dir::isEmpty() return true if dirname is empty';
			ok isEmptyReturnFalseIfDirnameIsNotEmpty, 'iMSCP::Dir::isEmpty() return false if dirname is not empty';

			# make()
			ok makeDieIfDirnameAlreadyExistsAsFile, 'iMSCP::Dir::make() die if dirname already exists as file';
			ok makeCanCreateDir, 'iMSCP::Dir::make() can create dirname';
			ok makeCanCreatePath, 'iMSCP::Dir::make() can create dirpath';
			ok makeSetExpectedOwnerOnNewlyCreatedDirname, 'iMSCP::Dir::make() set expected owner on newly created dirname';
			ok makeSetExpectedGroupOnNewlyCreatedDirname, 'iMSCP::Dir::make() set expected group on newly created dirname';
			ok makeSetExpectedModeOnNewlyCreatedDirname, 'iMSCP::Dir::make() set expected mode on newly created dirname';
			ok makeSetExpectedOwnerOnExistentDirname, 'iMSCP::Dir::make() set expected owner on existent dirname';
			ok makeSetExpectedGroupOnExistentDirname, 'iMSCP::Dir::Make() set expected group on existent dirname';
			ok makeSetExpectedModeOnExistentDirname, 'iMSCP::Dir::make() set expected mode on existent dirname';

			# remove()
			ok removeCanRemoveDir, 'iMSCP::Dir::remove() can remove dirname';
			ok removeCanRemovePath, 'iMSCP::Dir::remove() can remove dirpath';

			# rcopy()
			ok rcopyDieOnMissingTargetDirParameter, 'iMSCP::Dir::rcopy() die on missing targetDir parameter';
			ok rcopyDieIfCannotOpenDirname, 'iMSCP::Dir::rcopy() die if cannot open dirname';
			ok rcopyCanCopyDirnameToTargetDir, 'iMSCP::Dir::rcopy() can copy dirname to targetdir';
			ok rcopyDoNotPreserveFileAttributes, 'iMSCP::Dir::rcopy() do not preserve file attributes';
			ok rcopyPreserveFileAttributes, 'iMSCP::Dir::rcopy() preserve file attributes';

			# moveDir()
			ok moveDirDieOnMissingDestdirParameter, 'iMSCP::Dir::moveDir() die on missing destdir parameter';
			ok moveDirDieOnInexistentDirname, 'iMSCP::Dir::moveDir() die on inexistent dirname';
			ok moveDirCanMoveDirnameToDestDir, 'iMSCP::Dir::moveDir() can move dirname to destdir';
		};

		diag sprintf('A test failed unexpectedly: %s', $@) if $@;
		cleanupTestEnv;
	}
}

1;
__END__
