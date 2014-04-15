#!/usr/bin/perl

=head1 NAME

 iMSCP::Ext2Attributes - Package providing access to Linux file system extended attributes

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2014 by internet Multi Server Control Panel
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
#
# @category    i-MSCP
# @copyright   2010-2014 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

# TODO check compatibility with BSD* systems (should normally be)
# http://fxr.watson.org/fxr/source/fs/ext2/ioctl.c?v=linux-2.6

package iMSCP::Ext2Attributes;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Execute;
use File::Find qw( finddepth );
use parent qw( Exporter );
use vars qw( @EXPORT_OK );

@EXPORT_OK = qw(
	setSecureDeletion clearSecureDeletion isSecureDelection
	setUndelete clearUndelete isUndelete
	setCompress clearCompress isCompress
	setSynchronousUpdate cleanSynchronousUpdate isSynchronousUpdate
	setImmutable clearImmutable isImmutable
	setAppendOnly clearAppendOnly isAppendOnly
	setNoDump clearNoDump isNoDump
	setNoAtime clearNoAtime isNoAtime
);

my $isSupported;

BEGIN
{
	chomp(my $bitness = `getconf LONG_BIT`);
	my $module = "iMSCP::Ext2Attributes::Ext2Fs$bitness";

	eval "require $module";

	unless($@) {
		$module->import();
	} else {
		$isSupported = 0;

		no strict 'refs';

		my $dummy = sub { 'dummy' };

		*{__PACKAGE__.'::EXT2_SECRM_FL'} = $dummy;
		*{__PACKAGE__.'::EXT2_UNRM_FL'} = $dummy;
		*{__PACKAGE__.'::EXT2_COMPR_FL'} = $dummy;
		*{__PACKAGE__.'::EXT2_SYNC_FL'} = $dummy;
		*{__PACKAGE__.'::EXT2_IMMUTABLE_FL'} = $dummy;
		*{__PACKAGE__.'::EXT2_APPEND_FL'} = $dummy;
		*{__PACKAGE__.'::EXT2_NODUMP_FL'} = $dummy;
		*{__PACKAGE__.'::EXT2_NOATIME_FL'} = $dummy;
		*{__PACKAGE__.'::EXT2_IOC_GETFLAGS'} = $dummy;
		*{__PACKAGE__.'::EXT2_IOC_SETFLAGS'} = $dummy;
	}
}

=head1 DESCRIPTION

 This package provides access to the Ext2, Ext3, Ext4 and reiserfs filesystem extended attributes.

=cut

my %constants = (
	SecureDeletion => EXT2_SECRM_FL,
	Undelete => EXT2_UNRM_FL,
	Compress => EXT2_COMPR_FL,
	SynchronousUpdate => EXT2_SYNC_FL,
	Immutable => EXT2_IMMUTABLE_FL,
	AppendOnly => EXT2_APPEND_FL,
	NoDump => EXT2_NODUMP_FL,
	NoAtime => EXT2_NOATIME_FL
);

=head1 FUNCTIONS

=over 4

=item setSecureDeletion($filename, [$recursive])

 This function takes a filename and attempts to set its secure deletion flag.
 If a second arguement is passed with true value, and $filename is a directory, this function will operate recursively.

=item clearSecureDeletion($filename, [$recursive])

 This function takes a filename and removes the secure deletion flag if it is present.
 If a second arguement is passed with true value, and $filename is a directory, this function will operate recursively.

=item isSecureDeletion($filename)

This function takes a filename and returns true if the secure deletion flag is set and false if it isn't.

=item setUndelete($filename, [$recursive])

 This function takes a filename and attempts to set its undelete flag.
 If a second arguement is passed with true value, and $filename is a directory, this function will operate recursively.

=item clearUndelete($filename, [$recursive])

 This function takes a filename and removes the undelete flag if it is present.
 If a second arguement is passed with true value, and $filename is a directory, this function will operate recursively.

=item isUndelete

This function takes a filename and returns true if the undelete flag is set and false if it isn't.

=item setCompress($filename, [$recursive])

 This function takes a filename and attempts to set its compress flag.
 If a second arguement is passed with true value, and $filename is a directory, this function will operate recursively.

=item clearCompress($filename, [$recursive])

 This function takes a filename and removes the compress flag if it is present.
 If a second arguement is passed with true value, and $filename is a directory, this function will operate recursively.

=item isCompress($filename)

This function takes a filename and returns true if the compress flag is set and false if it isn't.

=item setSynchronousUpdate($filename, [$recursive])

 This function takes a filename and attempts to set its synchronous updates flag.
 If a second arguement is passed with true value, and $filename is a directory, this function will operate recursively.

=item clearSynchronousUpdate($filename, [$recursive])

 This function takes a filename and removes the synchronous updates flag if it is present.
 If a second arguement is passed with true value, and $filename is a directory, this function will operate recursively.

=item isSynchronousUpdate($filename)

This function takes a filename and returns true if the synchronous updates flag is set and false if it isn't.

=item setImmutable($filename, [$recursive])

 This function takes a filename and attempts to set its immutable flag.
 If a second arguement is passed with true value, and $filename is a directory, this function will operate recursively.

=item clearImmutable($filename, [$recursive])

 This function takes a filename and removes the immutable flag if it is present.
 If a second arguement is passed with true value, and $filename is a directory, this function will operate recursively.

=item isImmutable

This function takes a filename and returns true if the immutable flag is set and false if it isn't.

=item setAppendOnly($filename, [$recursive])

 This function takes a filename and attempts to set its appendable flag.
 If a second arguement is passed with true value, and $filename is a directory, this function will operate recursively.

=item clearAppendOnly($filename, [$recursive])

 This function takes a filename and removes the appendable flag if it is present.
 If a second arguement is passed with true value, and $filename is a directory, this function will operate recursively.

=item isAppendOnly($filename)

 This function takes a filename and returns true if the append only flag is set and false if it isn't.

=item setNoAtime($filename)

 This function takes a filename and attempts to set its noatime flag.
 If a second arguement is passed with true value, and $filename is a directory, this function will operate recursively.

=item isNoAtime($filename)

 This function takes a filename and returns true if the noatime flag is set and false if it isn't.

=item clearNoAtime($filename, [$recursive])

 This function takes a filename and removes the only noatime flag if it is present.
 If a second arguement is passed with true value, and $filename is a directory, this function will operate recursively.

=cut

for my $functName (keys %constants) {
	my $set = sub {
		my ($filename, $recursive) = @_;

		return 0 unless _isSupported();

		debug("Setting '$functName' extended attribute on $filename" . ($recursive ? ' recursively' : ''));

		if($recursive) {
			File::Find::finddepth(
				sub {
					my $flags = _getAttributes($_);
					_setAttributes($_, $flags | $constants{$functName}) if defined $flags;
				},
				$filename
			);
		} else {
			my $flags = _getAttributes($filename);
			_setAttributes($filename, $flags | $constants{$functName}) if defined $flags;
		}

		0;
	};

	my $clear = sub {
		my ($filename, $recursive) = @_;

		return 0 unless _isSupported();

		debug("Clearing '$functName' extended attribute on $filename" . ($recursive ? ' recursively' : ''));

		if($recursive) {
			File::Find::finddepth(
				sub {
					my $flags = _getAttributes($_);
					_setAttributes($_, $flags & ~$constants{$functName});
				},
				$filename
			);
		} else {
			my $flags = _getAttributes($filename);
			_setAttributes($filename, $flags & ~$constants{$functName}) if defined $flags;
		}

		0;
	};

	my $is = sub {
		my $filename = $_[0];

		return 0 unless _isSupported();

		my $flags = _getAttributes($filename);

		(defined $flags && $flags & $constants{$functName}) ? 1 : 0;
	};

	no strict 'refs';

	*{__PACKAGE__ . '::set' . $functName } = $set;
	*{__PACKAGE__ . '::clear' . $functName } = $clear;
	*{__PACKAGE__ . '::is' . $functName } = $is;
}

sub _getAttributes
{
	my $filename = $_[0];

	open my $fh, $filename or fatal("Unable to open $filename: $!");
	my $ret = pack 'i', 0;
	ioctl($fh, EXT2_IOC_GETFLAGS, $ret) or fatal("Unable to get extended attributes: $!");
	close $fh;
	unpack 'i', $ret;
}

sub _setAttributes
{
	my ($filename, $flags) = @_;

	open my $fh, $filename or fatal("Unable to open $filename: $!");
	my $flag = pack 'i', $flags;
	ioctl($fh, EXT2_IOC_SETFLAGS, $flag) or fatal("Unable to set extended attribute: $!");
	close $fh;
}

sub _isSupported
{
	unless(defined $isSupported) {
		my ($stdout, $stderr);
		my $rs = execute(
			"$main::imscpConfig{'CMD_DF'} -TP " . escapeShell($main::imscpConfig{'USER_WEB_DIR'}), \$stdout, \$stderr
		);
		fatal($stderr) if $stderr && $rs;

		my %filePartitionInfo;
		@filePartitionInfo{
			('mount', 'fstype', 'size', 'used', 'free', 'percent', 'disk')
		} = split /\s+/, (split "\n", $stdout)[1];

		if($filePartitionInfo{'fstype'} =~ /^(?:ext[2-4]|reiserfs)$/) {
			$isSupported = 1;
		} else {
			$isSupported = 0;
		}
	}

	$isSupported;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
