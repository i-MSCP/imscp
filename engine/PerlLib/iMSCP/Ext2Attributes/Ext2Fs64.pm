=head1 NAME

 iMSCP::Ext2Attributes::Ext2Fs64 - Package providing Ioctl command values (64-bit constants) and Inode flags values

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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

package iMSCP::Ext2Attributes::Ext2Fs64;

use strict;
use warnings;
use parent qw( Exporter );
use vars qw( @EXPORT );

@EXPORT = qw(
    EXT2_IOC_GETFLAGS EXT2_IOC_SETFLAGS EXT2_SECRM_FL EXT2_UNRM_FL EXT2_COMPR_FL EXT2_SYNC_FL EXT2_IMMUTABLE_FL
    EXT2_APPEND_FL EXT2_NODUMP_FL EXT2_NOATIME_FL
    );

=head1 DESCRIPTION

 This package provides Ioctl command values and Inode flags values used by the iMSCP::Ext2Attributes package.

 See the iMSCP::Ext2Attributes for more information.

=cut

# <include/linux/ext2_fs.h> (64-bit values)
use constant EXT2_IOC_GETFLAGS => 0x80086601;
use constant EXT2_IOC_SETFLAGS => 0x40086602;

# <include/linux/ext2_fs.h> - Inode flags (GETFLAGS/SETFLAGS)
use constant EXT2_SECRM_FL => 0x00000001; # Secure deletion (s)
use constant EXT2_UNRM_FL => 0x00000002; # Undelete (u)
use constant EXT2_COMPR_FL => 0x00000004; # Compress file (c)
use constant EXT2_SYNC_FL => 0x00000008; # Synchronous updates (S)
use constant EXT2_IMMUTABLE_FL => 0x00000010; # Immutable file (i)
use constant EXT2_APPEND_FL => 0x00000020; # writes to file may only append (a)
use constant EXT2_NODUMP_FL => 0x00000040; # do not dump file (d)
use constant EXT2_NOATIME_FL => 0x00000080; # do not update atime (A)
# Reserved for compression usage... */
#use constant EXT2_DIRTY_FL				=> 0x00000100; (Z)
#use constant EXT2_COMPRBLK_FL			=> 0x00000200; # One or more compressed clusters
#use constant EXT2_NOCOMPR_FL			=> 0x00000400; # Don't compress (X)
#use constant EXT2_ECOMPR_FL			=> 0x00000800; # Compression error (E)
# End compression flags --- maybe not all used */
#use constant EXT2_INDEX_FL				=> 0x00001000; # hash-indexed directory (I)
#use constant EXT2_IMAGIC_FL			=> 0x00002000; # AFS directory
#use constant EXT2_JOURNAL_DATA_FL		=> 0x00004000; # file data should be journaled (j)
#use constant EXT2_NOTAIL_FL			=> 0x00008000; # file tail should not be merged (t)
#use constant EXT2_DIRSYNC_FL			=> 0x00010000; # dirsync behaviour (directories only) (D)
#use constant EXT2_TOPDIR_FL			=> 0x00020000; # Top of directory hierarchies (T)
#use constant EXT2_RESERVED_FL			=> 0x80000000; # reserved for ext2 lib

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
