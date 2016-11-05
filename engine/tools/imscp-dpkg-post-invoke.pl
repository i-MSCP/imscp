#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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

=head1 NAME

imscp-disable-accounts - Deactivates expired client accounts

=head1 SYNOPSIS

 imscp-dpkg-post-invoke [options]...

=cut

use strict;
use warnings;
use FindBin;
use lib "/var/www/imscp/engine/PerlLib", "/var/www/imscp/engine/PerlVendor"; # FIXME: shouldn't be hardcoded
use File::Basename;
use iMSCP::Debug;
use iMSCP::Bootstrapper;
use iMSCP::Execute;
use iMSCP::Getopt;
use iMSCP::Servers;
use iMSCP::Packages;
use POSIX qw(locale_h);
use locale;

setlocale(LC_ALL, 'C.UTF-8');
$ENV{'LANG'} = 'C.UTF-8';

newDebug('imscp-dpkg-post-invoke.log');

# Parse command line options
iMSCP::Getopt->parseNoDefault(sprintf('Usage: perl %s [OPTION]...', basename($0)) . qq {

Script for process dpkg post invoke tasks

OPTIONS:
 -v,    --verbose       Enable verbose mode.},
 'verbose|v' => sub { setVerbose(@_); }
);

my $bootstrapper = iMSCP::Bootstrapper->getInstance();
exit unless $bootstrapper->lock('/tmp/imscp-dpkg-post-invoke.lock', 'nowait');
$bootstrapper->getInstance()->boot(
    {
        mode            => 'backend',
        nolock          => 1,
        norequirements  => 1,
        config_readonly => 1
    }
);

my $rs = 0;
my @items = ();

for my $server(iMSCP::Servers->getInstance()->getListWithFullNames()) {
    eval "require $server";
    $server = $server->factory();
    push @items, $server if $server->can( 'dpkgPostInvokeTasks' );
}

for my $package(iMSCP::Packages->getInstance()->getListWithFullNames()) {
    eval "require $package";
    $package = $package->getInstance();
    push @items, $package if $package->can( 'dpkgPostInvokeTasks' );
}

for(@items) {
    debug( sprintf( 'Processing %s dpkg post-invoke tasks', ref ) );
    $rs |= $_->dpkgPostInvokeTasks();
}

$bootstrapper->unlock('/tmp/imscp-dpkg-post-invoke.lock');

=head1 AUTHOR

Laurent Declercq <l.declercq@nuxwin.com>

=cut
