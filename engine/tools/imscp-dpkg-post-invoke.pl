#!/usr/bin/perl

=head1 NAME

 imscp-dpkg-post-invoke.pl - Process dpkg post invoke tasks

=head1 SYNOPSIS

 imscp-dpkg-post-invoke [options]...

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

use strict;
use warnings;
use FindBin;
use lib "/var/www/imscp/engine/PerlLib", "/var/www/imscp/engine/PerlVendor"; # FIXME: shouldn't be hardcoded
use File::Basename;
use iMSCP::Debug;
use iMSCP::Bootstrapper;
use iMSCP::Getopt;
use iMSCP::Servers;
use iMSCP::Packages;

$ENV{'LANG'} = 'C.UTF-8';
$ENV{'PATH'} = '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin';

newDebug( 'imscp-dpkg-post-invoke.log' );

iMSCP::Getopt->parseNoDefault( sprintf( 'Usage: perl %s [OPTION]...', basename( $0 )) . qq {

Process dpkg post invoke tasks

OPTIONS:
 -d,    --debug         Enable debug mode.
 -v,    --verbose       Enable verbose mode.},
    'debug|d'   => \&iMSCP::Getopt::debug,
    'verbose|v' => \&iMSCP::Getopt::verbose
);

setVerbose( iMSCP::Getopt->verbose );

my $bootstrapper = iMSCP::Bootstrapper->getInstance();
exit unless $bootstrapper->lock( '/var/lock/imscp-dpkg-post-invoke.lock', 'nowait' );

$bootstrapper->getInstance()->boot(
    {
        config_readonly => 1,
        mode            => 'backend',
        nolock          => 1
    }
);

my $rs = 0;
my @items = ();

for ( iMSCP::Servers->getInstance()->getListWithFullNames() ) {
    next unless $_->can( 'dpkgPostInvokeTasks' );
    push @items, $_->factory();
}

for ( iMSCP::Packages->getInstance()->getListWithFullNames() ) {
    next unless $_->can( 'dpkgPostInvokeTasks' );
    push @items, $_->getInstance();
}

for( @items ) {
    debug( sprintf( 'Executing %s dpkg post-invoke tasks', ref ));
    $rs |= $_->dpkgPostInvokeTasks();
}

exit $rs;

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut
