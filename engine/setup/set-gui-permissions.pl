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

$main::execmode = '';
iMSCP::Getopt->parseNoDefault( sprintf( 'Usage: perl %s [OPTION]...', basename( $0 ) ).qq {

Set i-MSCP gui permissions.

OPTIONS
 -s,    --setup         Setup mode.
 -v,    --verbose       Enable verbose mode},
    'setup|s'   => sub { $main::execmode = 'setup' },
    'verbose|v' => sub { setVerbose( @_ ); }
);

iMSCP::Bootstrapper->getInstance()->boot(
    { norequirements => 'yes', nolock => 'yes', nodatabase => 'yes', nokeys => 'yes' }
);

my $rs = 0;
my @toProcess = ();

for(iMSCP::Servers->getInstance()->get()) {
    my $package = "Servers::$_";
    eval "require $package";
    if ($@) {
        error( $@ );
        $rs = 1;
        next;
    }

    $package = $package->factory();
    push @toProcess, [ $_, $package ] if $package->can( 'setGuiPermissions' );;
}

for(iMSCP::Packages->getInstance()->get()) {
    my $package = "Package::$_";
    eval "require $package";
    if ($@) {
        error( $@ );
        $rs = 1;
        next;
    }

    $package = $package->getInstance();
    push @toProcess, [ $_, $package ] if $package->can( 'setGuiPermissions' );
}

my $totalItems = @toProcess;
my $count = 1;

for(@toProcess) {
    my ($package, $instance) = @{$_};
    debug( sprintf( 'Setting %s (gui) permissions', $package ) );
    printf( "Setting %s (gui) permissions\t%s\t%s\n", $package, $totalItems, $count ) if $main::execmode eq 'setup';
    $rs |= $instance->setGuiPermissions();
    $count++;
}

exit $rs;

=head1 AUTHOR

Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
