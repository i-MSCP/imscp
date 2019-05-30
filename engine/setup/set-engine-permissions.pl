#!/usr/bin/perl

=head1 NAME

 set-engine-permissions Set i-MSCP engine permission

=head1 SYNOPSIS

 set-engine-permissions [OPTION]...

=cut

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

use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../PerlLib", "$FindBin::Bin/../PerlVendor";
use File::Basename;
use iMSCP::Boolean;
use iMSCP::Bootstrapper;
use iMSCP::Debug;
use iMSCP::Getopt;
use iMSCP::Rights;
use iMSCP::Servers;
use iMSCP::Packages;
use POSIX 'locale_h';

@{ENV}{qw/ LANG PATH /} = (
    'C.UTF-8',
    '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin'
);
delete $ENV{'LANGUAGE'};
setlocale( LC_MESSAGES, 'C.UTF-8' );

newDebug( 'imscp-set-engine-permissions.log' );

$::execmode = 'backend';
iMSCP::Getopt->parseNoDefault( sprintf( 'Usage: perl %s [OPTION]...', basename( $0 )) . qq {

Set i-MSCP engine permissions.

OPTIONS:
 -s,    --setup           Setup mode.
 -d,    --debug           Enable debug mode.
 -v,    --verbose         Enable verbose mode.
 -x,    --fix-permissions Fix permissions recursively.},
    'setup|s'           => sub { $::execmode = 'setup'; },
    'debug|d'           => \&iMSCP::Getopt::debug,
    'verbose|v'         => \&iMSCP::Getopt::verbose,
    'fix-permissions|x' => \&iMSCP::Getopt::fixPermissions
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
    ( my $sub = $server->can( 'setEnginePermissions' ) ) or next;
    push @items, [ $server, sub { $sub->( $server->factory()); } ];
}

for my $package( iMSCP::Packages->getInstance()->getList() ) {
    ( my $sub = $package->can( 'setEnginePermissions' ) ) or next;
    push @items, [ $package, sub { $sub->( $package->getInstance()); } ];
}

my $totalItems = scalar @items+1;
my $count = 1;

debug( 'Setting base (engine) permissions' );
printf( "Setting base (engine) permissions\t%s\t%s\n", $totalItems, $count ) if $::execmode eq 'setup';

my $rootUName = $::imscpConfig{'ROOT_USER'};
my $rootGName = $::imscpConfig{'ROOT_GROUP'};
my $imscpGName = $::imscpConfig{'IMSCP_GROUP'};
my $confDir = $::imscpConfig{'CONF_DIR'};
my $rootDir = $::imscpConfig{'ROOT_DIR'};

# e.g: /etc/imscp
$rs = setRights(
    $confDir,
    {
        user      => $rootUName,
        group     => $imscpGName,
        dirmode   => '0750',
        filemode  => '0640',
        recursive => 1
    }
);
# e.g: /var/www/imscp
$rs |= setRights(
    $rootDir,
    {
        user  => $rootUName,
        group => $rootGName,
        mode  => '0755'
    }
);
# e.g: /var/www/imscp/daemon
$rs |= setRights(
    "$rootDir/daemon",
    {
        user      => $rootUName,
        group     => $imscpGName,
        mode      => '0750',
        recursive => 1
    }
);
# e.g: /var/www/imscp/engine
$rs |= setRights(
    "$rootDir/engine",
    {
        user      => $rootUName,
        group     => $imscpGName,
        mode      => '0750',
        recursive => 1
    }
);
# e.g: /var/www/virtual
$rs |= setRights( $::imscpConfig{'USER_WEB_DIR'}, {
    user  => $rootUName,
    group => $rootGName,
    mode  => '0755'
} );
# e.g: /var/log/imscp
$rs |= setRights( $::imscpConfig{'LOG_DIR'}, {
    user  => $rootUName,
    group => $imscpGName,
    mode  => '0750'
} );

$count++;

for( @items ) {
    debug( sprintf( 'Setting %s engine permissions', $_->[0] ));
    printf( "Setting %s engine permissions\t%s\t%s\n", $_->[0], $totalItems, $count ) if $::execmode eq 'setup';
    $rs |= $_->[1]->();
    $count++;
}

exit $rs;

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
