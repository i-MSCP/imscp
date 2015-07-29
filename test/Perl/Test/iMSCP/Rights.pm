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

package Test::iMSCP::Rights;

use strict;
use warnings;
use Test::More;

sub setRightsDieOnMissingOptions
{
	local $@;
	eval { iMSCP::Rights::setRights( '/tmp/foo' ) };
	$@ && $@ =~ /Expects at least one option/;
}

sub setRightsDieOnUnallowedMixedOptions
{
	local $@;
	eval { iMSCP::Rights::setRights( '/tmp/foo', { dirmode => '0555', mode => '0555' }) };
	my $ret1 = $@;

	undef $@;

	eval { iMSCP::Rights::setRights( '/tmp/foo', { filemode => '0555', mode => '0555' }) };
	my $ret2 = $@;

	$ret1 && $ret1 =~ /Unallowed mixed options/ && $ret2 && $ret2 =~ /Unallowed mixed options/;
}

sub setRightsDieOnInexistentTarget
{
	local $@;
	eval { iMSCP::Rights::setRights( '/tmp/foo', { mode => '0555' } ) };
	my $ret1 = $@;

	undef $@;

	eval { iMSCP::Rights::setRights( '/tmp/foo', { mode => '0555' => recursive => 1 } ) };
	my $ret2 = $@;

	$ret1 && $ret1 =~ /No such file or directory/ && $ret2 && $ret2 =~ /No such file or directory/;
}

sub setRightsDieOnInexistentUser
{
	local $@;
	eval { iMSCP::Rights::setRights( '/tmp/foo', { user => 'bar' } ) };
	$@ && $@ =~ /inexistent user/;
}

sub setRightsDieOnInexistentGroup
{
	local $@;
	eval { iMSCP::Rights::setRights( '/tmp/foo', { group => 'bar' } ) };
	$@ && $@ =~ /inexistent group/;
}

sub setRightsSetExpectedMode
{
	setupTestEnv();
	iMSCP::Rights::setRights( '/tmp/foo', { mode => '02555' } );
	sprintf('%o', (lstat('/tmp/foo'))[2] & 07777) == 2555;
}

sub setRightsSetExpectedModeRecursively
{
	setupTestEnv();
	iMSCP::Rights::setRights( '/tmp/foo', { mode => '0555', recursive => 1 } );
	sprintf('%o', (lstat('/tmp/foo'))[2] & 07777) == 555 &&
	sprintf('%o', (lstat('/tmp/foo/bar'))[2] & 07777) == 555 &&
	sprintf('%o', (lstat('/tmp/foo/baz/foo.txt'))[2] & 07777) == 555;
}

sub setRightsSetExpectedDirmode
{
	setupTestEnv();
	iMSCP::Rights::setRights( '/tmp/foo', { dirmode => '02555' } );
	sprintf('%o', (lstat('/tmp/foo'))[2] & 07777) == 2555 &&
	sprintf('%o', (lstat('/tmp/foo/bar'))[2] & 07777) == 2555 &&
	sprintf('%o', (lstat('/tmp/foo/baz'))[2] & 07777) == 2555;
}

sub setRightsSetExpectedFilemode
{
	setupTestEnv();
	iMSCP::Rights::setRights( '/tmp/foo', { filemode => '0400' } );
	sprintf('%o', (lstat('/tmp/foo/bar.txt'))[2] & 07777) == 400 &&
	sprintf('%o', (lstat('/tmp/foo/baz.txt'))[2] & 07777) == 400 &&
	sprintf('%o', (lstat('/tmp/foo/bar/foo.txt'))[2] & 07777) == 400;
}

sub setRightsSetExpectedUser
{
	setupTestEnv();
	iMSCP::Rights::setRights( '/tmp/foo', { user => 'nobody' } );
	getpwuid((lstat('/tmp/foo'))[4]) eq 'nobody';
}

sub setRightsSetExpectedUserRecursively
{
	setupTestEnv();
	iMSCP::Rights::setRights( '/tmp/foo', { user => 'nobody', recursive => 1 } );
	getpwuid((lstat('/tmp/foo'))[4]) eq 'nobody' && getpwuid((lstat('/tmp/foo/bar/foo.txt'))[4]) eq 'nobody';
}

sub setRightsSetExpectedGroup
{
	setupTestEnv();
	iMSCP::Rights::setRights( '/tmp/foo', { group => 'nogroup' } );
	getgrgid((lstat('/tmp/foo'))[5]) eq 'nogroup';
}

sub setRightsSetExpectedGroupRecursively
{
	setupTestEnv();
	iMSCP::Rights::setRights( '/tmp/foo', { group => 'nogroup', recursive => 1 } );
	getgrgid((lstat('/tmp/foo'))[5]) eq 'nogroup' && getgrgid((lstat('/tmp/foo/bar/foo.txt'))[5]) eq 'nogroup';
}

my $assetDir;

sub cleanupTestEnv
{
	system 'rm', '-Rf', '/tmp/foo';
}

sub setupTestEnv
{
	cleanupTestEnv();
	system 'cp', '-R', '-f', $assetDir, '/tmp/foo';
}

sub runUnitTests
{
	$assetDir = shift . '/foo';

	cleanupTestEnv();

	plan tests => 14; # Number of tests planned for execution

	if(require_ok('iMSCP::Rights')) {
		ok setRightsDieOnMissingOptions, 'iMSCP::Rights::setRights() die on missing option';
		ok setRightsDieOnUnallowedMixedOptions, 'iMSCP::Rights::setRights() die on unallowed mixed options';
		ok setRightsDieOnInexistentTarget, 'iMSCP::Rights() die on inexistent target';
		ok setRightsDieOnInexistentUser, 'iMSCP::Rights::setRights() die on inexistent user';
		ok setRightsDieOnInexistentGroup, 'iMSCP::Rights::setRights() die on inexistent group';
		ok setRightsSetExpectedMode, 'iMSCP::Rights::setRights() set expected mode';
		ok setRightsSetExpectedModeRecursively, 'iMSCP::Rights::setRights() set exepcted mode recursively';
		ok setRightsSetExpectedDirmode, 'iMSCP::Rights::setRights() set expected dirmode';
		ok setRightsSetExpectedFilemode, 'iMSCP::Rights::setRights() set expected filemode';
		ok setRightsSetExpectedUser, 'iMSCP::Rights::setRights() set expected user';
		ok setRightsSetExpectedUserRecursively, 'iMSCP::Rights::setRights() set expected user recursively';
		ok setRightsSetExpectedGroup, 'iMSCP::Rights::setRights() set expected group';
		ok setRightsSetExpectedGroupRecursively, 'iMSCP::Rights::setRights() set expected group recursively';

		cleanupTestEnv;
	}
}

1;
__END__
