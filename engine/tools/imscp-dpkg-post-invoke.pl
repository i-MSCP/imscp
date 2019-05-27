#!/usr/bin/perl

=head1 NAME

 imscp-dpkg-post-invoke.pl - Process dpkg(1) post-invoke tasks

=head1 SYNOPSIS

 imscp-dpkg-post-invoke [OPTION]...

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
use lib '/var/www/imscp/engine/PerlLib', '/var/www/imscp/engine/PerlVendor';
use File::Basename;
use iMSCP::Boolean;
use iMSCP::Bootstrapper;
use iMSCP::Debug qw/
    debug error getMessageByType getLastError newDebug setDebug setVerbose
/;
use iMSCP::Getopt;
use iMSCP::Servers;
use iMSCP::Packages;
use POSIX 'locale_h';

@{ENV}{qw/ LANG PATH IMSCP_SETUP /} = (
    'C.UTF-8',
    '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
    TRUE
);
delete $ENV{'LANGUAGE'};
setlocale( LC_MESSAGES, 'C.UTF-8' );

# Set execution context
# Need to be setup as some post-invoke tasks could have to write
# in configuration files which are readonly in default (backend) context.
$::execmode = 'setup';

iMSCP::Getopt->parseNoDefault( sprintf( 'Usage: perl %s [OPTION]...', basename( $0 )) . qq{

Execute dpkg(1) post-invoke tasks from i-MSCP servers and packages

OPTIONS:
 -d,    --debug         Enable debug mode.
 -v,    --verbose       Enable verbose mode.},
    'debug|d'   => \&iMSCP::Getopt::debug,
    'verbose|v' => \&iMSCP::Getopt::verbose
);

newDebug( 'imscp-dpkg-post-invoke.log' );
setDebug( iMSCP::Getopt->debug );
setVerbose( iMSCP::Getopt->verbose );

iMSCP::Bootstrapper->getInstance()->boot( {
    config_readonly => FALSE,
    mode            => $::execmode,
    nodatabase      => TRUE,
    nolock          => TRUE,
    nokeys          => TRUE,
    norequirements  => TRUE
} );

eval {
    my $server = $_;

    if ( my $sub = $server->can( 'dpkgPostInvokeTasks' ) ) {
        debug( sprintf( '%s dpkg(1) post-invoke tasks', $server ), FALSE );
        $sub->( $server->factory()) == 0 or die( getMessageByType(
            'error', { amount => 1, remove => TRUE }
        ));
    }

    TRUE;
} or error( $@ ) for iMSCP::Servers->getInstance()->getList();

eval {
    my $package = $_;

    if ( my $sub = $package->can( 'dpkgPostInvokeTasks' ) ) {
        debug( sprintf( '%s dpkg(1) post-invoke tasks', $package ), FALSE );
        $sub->( $package->getInstance()) == 0 or die( getMessageByType(
            'error', { amount => 1, remove => TRUE }
        ));
    }
    TRUE;
} or error( $@ ) for iMSCP::Packages->getInstance()->getList();

exit 1 if getLastError();

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
