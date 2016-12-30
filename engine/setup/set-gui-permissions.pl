#!/usr/bin/perl

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

=head1 NAME

set-engine-permissions Set i-MSCP GUI permission

=head1 SYNOPSIS

 set-engine-permissions [options]...

=cut

use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/..", "$FindBin::Bin/../PerlLib", "$FindBin::Bin/../PerlVendor";
use File::Basename;
use iMSCP::Bootstrapper;
use iMSCP::Debug;
use iMSCP::Getopt;
use iMSCP::Servers;
use iMSCP::Packages;
use POSIX qw(locale_h);
use locale;

setlocale( LC_ALL, 'C.UTF-8' );
$ENV{'LANG'} = 'C.UTF-8';

newDebug( 'imscp-set-gui-permissions.log' );

$main::execmode = 'backend';
iMSCP::Getopt->parseNoDefault( sprintf( 'Usage: perl %s [OPTION]...', basename( $0 ) ).qq {

Set i-MSCP gui permissions.

OPTIONS
 -s,    --setup         Setup mode.
 -d,    --debug         Enable debug mode.
 -v,    --verbose       Enable verbose mode},
    'setup|s'   => sub { $main::execmode = 'setup' },
    'debug|d'   => sub { iMSCP::Getopt->debug( @_ ) },
    'verbose|v' => sub { setVerbose( @_ ); }
);

iMSCP::Bootstrapper->getInstance()->boot(
    {
        mode            => $main::execmode,
        norequirements  => 1,
        nolock          => 1,
        nodatabase      => 1,
        nokeys          => 1,
        config_readonly => 1
    }
);

use Data::Dumper;

my $rs = 0;
my @items = ();

for my $server(iMSCP::Servers->getInstance()->getListWithFullNames()) {
    eval "require $server";
    $server = $server->factory();
    push @items, $server if $server->can( 'setGuiPermissions' );
}

for my $package (iMSCP::Packages->getInstance()->getListWithFullNames()) {
    eval "require $package";
    $package = $package->getInstance();
    push @items, $package if $package->can( 'setGuiPermissions' );
}

my $totalItems = scalar @items;
my $count = 1;
for(@items) {
    debug( sprintf( 'Setting %s frontEnd permissions', ref ) );
    printf( "Setting %s frontEnd permissions\t%s\t%s\n", ref, $totalItems, $count ) if $main::execmode eq 'setup';
    $rs |= $_->setGuiPermissions();
    $count++;
}

exit $rs;

=head1 AUTHOR

Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
