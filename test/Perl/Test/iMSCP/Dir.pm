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

	[ sort iMSCP::Dir->new( dirname => '/tmp/foo', fileType => '\.php' )->getFiles() ];
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
			ok newDieOnMissingDirnameOption, 'new() die on missing dirname option';

			# mode()
			ok modeDieOnMissingModeParameter, 'mode() die on missing mode parameter';
			ok modeDieOnInexistentDirname, 'mode() die on inexistent dirname';
			ok modeSetExpectedModeOnDirname, 'mode() set expected mode on dirname';

			# owner()
			ok ownerDieOnMissingOwnerParameter, 'owner() die on missing owner parameter';
			ok ownerDieOnMissingGroupParameter, 'iowner() die on missing group parameter';
			ok ownerDieOnInexistentUser, 'owner() die on inexistent user';
			ok ownerDieOnInexistentGroup, 'owner() die on inexistent group';
			ok ownerSetExpectedOwnerAndGroup, 'owner() set expected owner and group on dirname';

			# getFiles()
			ok getFilesDieIfCannotOpenDirname, 'getFiles() die if cannot open dirname';
			is_deeply getFilesReturnExpectedFilenames, [ sort 'bar.txt', 'baz.txt', 'foo.php' ],
				'getFiles() return expected filenames';
			is_deeply getFilesReturnExpectedFilteredFiletypes, [ 'foo.php' ],
				'getFiles() return expected filtered file type';

			# getDirs()
			ok getDirsDieIfCannotOpenDirname, 'getDirs() die if cannot open dirname';
			is_deeply getDirsReturnExpectedDirnames, [ sort 'bar', 'baz', 'foo', 'qux' ],
				'getDirs() return expected dirnames';

			# getAll()
			ok getAllDieIfCannotOpenDirname, 'getAll() die if cannot open dirname';
			is_deeply getAllReturnExpectedDirnamesAndFilenames,
				[ sort 'bar', 'baz', 'foo', 'qux', 'bar.txt', 'baz.txt', 'foo.php' ],
				'getAll() return expected dirnames and filenames';

			# isEmpty()
			ok isEmptyDieIfCannotOpenDirname, 'isEmpty() die if cannot open dirname';
			ok isEmptyReturnTrueIfDirnameIsEmpty, 'isEmpty() return true if dirname is empty';
			ok isEmptyReturnFalseIfDirnameIsNotEmpty, 'isEmpty() return false if dirname is not empty';

			# make()
			ok makeDieIfDirnameAlreadyExistsAsFile, 'make() die if dirname already exists as file';
			ok makeCanCreateDir, 'make() can create dirname';
			ok makeCanCreatePath, 'make() can create dirpath';
			ok makeSetExpectedOwnerOnNewlyCreatedDirname, 'make() set expected owner on newly created dirname';
			ok makeSetExpectedGroupOnNewlyCreatedDirname, 'make() set expected group on newly created dirname';
			ok makeSetExpectedModeOnNewlyCreatedDirname, 'make() set expected mode on newly created dirname';
			ok makeSetExpectedOwnerOnExistentDirname, 'make() set expected owner on existent dirname';
			ok makeSetExpectedGroupOnExistentDirname, 'Make() set expected group on existent dirname';
			ok makeSetExpectedModeOnExistentDirname, 'make() set expected mode on existent dirname';

			# remove()
			ok removeCanRemoveDir, 'remove() can remove dirname';
			ok removeCanRemovePath, 'remove() can remove dirpath';

			# rcopy()
			ok rcopyDieOnMissingTargetDirParameter, 'rcopy() die on missing targetDir parameter';
			ok rcopyDieIfCannotOpenDirname, 'rcopy() die if cannot open dirname';
			ok rcopyCanCopyDirnameToTargetDir, 'rcopy() can copy dirname to targetdir';
			ok rcopyDoNotPreserveFileAttributes, 'rcopy() do not preserve file attributes';
			ok rcopyPreserveFileAttributes, 'rcopy() preserve file attributes';

			# moveDir()
			ok moveDirDieOnMissingDestdirParameter, 'moveDir() die on missing destdir parameter';
			ok moveDirDieOnInexistentDirname, 'moveDir() die on inexistent dirname';
			ok moveDirCanMoveDirnameToDestDir, 'moveDir() can move dirname to destdir';
		};

		diag sprintf('A test failed unexpectedly: %s', $@) if $@;
		cleanupTestEnv;
	}
}

1;
__END__
