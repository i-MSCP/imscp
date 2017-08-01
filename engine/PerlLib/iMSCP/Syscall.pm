=head1 NAME

 iMSCP::Syscall - Load required Perl header files to perform syscalls

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

package iMSCP::Syscall;

use strict;
use warnings;
no warnings qw / portable /;
use iMSCP::Debug;

{
    my $unload = sub {
        delete @INC{qw< asm/unistd.ph asm/unistd_32.ph asm/unistd_64.ph bits/syscall.ph syscall.ph sys/syscall.ph _h2ph_pre.ph >};
    };

    # We need to force loading in case the header files have been already loaded from elsewhere
    $unload->();
    local $@;
    eval {
        require 'syscall.ph';
        1
    } || eval {
        require 'sys/syscall.ph';
        1
    };
    fatal( sprintf( "Couldn't load required Perl header files to perform syscalls: %s", $@ )) if $@;
    # We need to force unload to not disturb other modules
    $unload->();
}

=head1 DESCRIPTION

 Load required Perl header files to perform syscalls.

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
