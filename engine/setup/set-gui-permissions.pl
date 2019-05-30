#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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

 set-engine-permissions [OPTION]...

=cut

use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../PerlLib", "$FindBin::Bin/../PerlVendor";
use File::Basename;
use iMSCP::Boolean;
use iMSCP::Bootstrapper;
use iMSCP::Debug;
use iMSCP::EventManager;
use iMSCP::Getopt;
use iMSCP::Servers;
use iMSCP::Packages;
use POSIX 'locale_h';

@{ENV}{qw/ LANG PATH /} = (
    'C.UTF-8',
    '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin'
);
delete $ENV{'LANGUAGE'};
setlocale( LC_MESSAGES, 'C.UTF-8' );

newDebug( 'imscp-set-gui-permissions.log' );

$::execmode = 'backend';
iMSCP::Getopt->parseNoDefault( sprintf( 'Usage: perl %s [OPTION]...', basename( $0 )) . qq {

Set i-MSCP gui permissions.

OPTIONS
 -s,    --setup         Setup mode.
 -d,    --debug         Enable debug mode.
 -v,    --verbose       Enable verbose mode},
    'setup|s'   => sub { $::execmode = 'setup'; },
    'debug|d'   => \&iMSCP::Getopt::debug,
    'verbose|v' => \&iMSCP::Getopt::verbose
);

setVerbose( iMSCP::Getopt->verbose );

my $bootstrapper = iMSCP::Bootstrapper->getInstance();
exit unless $bootstrapper->lock( '/var/lock/imscp-set-engine-permissions.lock', 'nowait' );

$bootstrapper->boot( {
    mode            => $::execmode,
    nolock          => TRUE,
    nodatabase      => TRUE,
    nokeys          => TRUE,
    config_readonly => TRUE
} );

my $rs = 0;
my @items = ();

for my $server( iMSCP::Servers->getInstance()->getList() ) {
    ( my $sub = $server->can( 'setGuiPermissions' ) ) or next;
    push @items, [ $server, sub { $sub->( $server->factory()); } ];
}

for my $package( iMSCP::Packages->getInstance()->getList() ) {
    ( my $sub = $package->can( 'setGuiPermissions' ) ) or next;
    push @items, [ $package, sub { $sub->( $package->getInstance()); } ];
}

iMSCP::EventManager->getInstance()->trigger( 'beforeSetGuiPermissions' );

my $totalItems = scalar @items;
my $count = 1;
for( @items ) {
    debug( sprintf( 'Setting %s frontEnd permissions', $_->[0] ));
    printf( "Setting %s frontEnd permissions\t%s\t%s\n", $_->[0], $totalItems, $count ) if $::execmode eq 'setup';
    $rs |= $_->[1]->();
    $count++;
}

iMSCP::EventManager->getInstance()->trigger( 'afterSetGuiPermissions' );

exit $rs;

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
