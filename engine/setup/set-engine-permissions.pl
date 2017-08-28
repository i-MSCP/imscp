#!/usr/bin/perl

=head1 NAME

 set-engine-permissions Set i-MSCP engine permission

=head1 SYNOPSIS

 set-engine-permissions [options]...

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
use lib "$FindBin::Bin/../PerlLib", "$FindBin::Bin/../PerlVendor";
use File::Basename;
use iMSCP::Bootstrapper;
use iMSCP::Debug;
use iMSCP::Getopt;
use iMSCP::Rights;
use iMSCP::Servers;
use iMSCP::Packages;

$ENV{'LANG'} = 'C.UTF-8';
$ENV{'PATH'} = '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin';

newDebug( 'imscp-set-engine-permissions.log' );

$main::execmode = 'backend';
iMSCP::Getopt->parseNoDefault( sprintf( 'Usage: perl %s [OPTION]...', basename( $0 )) . qq {

Set i-MSCP engine permissions.

OPTIONS:
 -s,    --setup           Setup mode.
 -d,    --debug           Enable debug mode.
 -v,    --verbose         Enable verbose mode.
 -x,    --fix-permissions Fix permissions recursively.},
    'setup|s'           => sub { $main::execmode = 'setup'; },
    'debug|d'           => \&iMSCP::Getopt::debug,
    'verbose|v'         => \&iMSCP::Getopt::verbose,
    'fix-permissions|x' => \&iMSCP::Getopt::fixPermissions
);

setVerbose( iMSCP::Getopt->verbose );

my $bootstrapper = iMSCP::Bootstrapper->getInstance();
exit unless $bootstrapper->lock( '/var/lock/imscp-set-engine-permissions.lock', 'nowait' );

$bootstrapper->boot(
    {
        mode            => $main::execmode,
        nolock          => 1,
        nodatabase      => 1,
        nokeys          => 1,
        config_readonly => 1
    }
);

my $rs = 0;
my @items = ();

for my $server( iMSCP::Servers->getInstance()->getListWithFullNames() ) {
    ( my $subref = $server->can( 'setEnginePermissions' ) ) or next;
    push @items, [ $server, sub { $subref->( $server->factory()); } ];
}

for my $package( iMSCP::Packages->getInstance()->getListWithFullNames() ) {
    ( my $subref = $package->can( 'setEnginePermissions' ) ) or next;
    push @items, [ $package, sub { $subref->( $package->getInstance()); } ];
}

my $totalItems = scalar @items+1;
my $count = 1;

debug( 'Setting base (engine) permissions' );
printf( "Setting base (engine) permissions\t%s\t%s\n", $totalItems, $count ) if $main::execmode eq 'setup';

my $rootUName = $main::imscpConfig{'ROOT_USER'};
my $rootGName = $main::imscpConfig{'ROOT_GROUP'};
my $imscpGName = $main::imscpConfig{'IMSCP_GROUP'};
my $confDir = $main::imscpConfig{'CONF_DIR'};
my $rootDir = $main::imscpConfig{'ROOT_DIR'};

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
$rs |= setRights(
    $main::imscpConfig{'USER_WEB_DIR'},
    {
        user  => $rootUName,
        group => $rootGName,
        mode  => '0755'
    }
);
# e.g: /var/log/imscp
$rs |= setRights(
    $main::imscpConfig{'LOG_DIR'},
    {
        user  => $rootUName,
        group => $imscpGName,
        mode  => '0750'
    }
);

$count++;

for( @items ) {
    debug( sprintf( 'Setting %s engine permissions', $_->[0] ));
    printf( "Setting %s engine permissions\t%s\t%s\n", $_->[0], $totalItems, $count ) if $main::execmode eq 'setup';
    $rs |= $_->[1]->();
    $count++;
}

exit $rs;

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
