#!/usr/bin/perl
# Copyright (C) 2016-2019 Laurent Declercq <l.declercq@nuxwin.com>
#
# This library is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public
# License as published by the Free Software Foundation; either
# version 2.1 of the License, or (at your option) any later version.
#
# This library is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
# Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public
# License along with this library; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301 USA

use strict;
use warnings;
use lib '/var/www/imscp/engine/PerlLib';
use iMSCP::Boolean;
use iMSCP::Bootstrapper;
use iMSCP::Debug qw/ setDebug setVerbose /;
use iMSCP::Mount qw/ umount /;

exit unless iMSCP::Bootstrapper->getInstance()->lock(
    '/var/lock/imscp-mountall.lock', TRUE
);

iMSCP::Bootstrapper->getInstance()->boot( {
    mode            => 'backend',
    nokeys          => TRUE,
    nodatabase      => TRUE,
    config_readonly => TRUE
} );

setDebug( FALSE );
setVerbose( TRUE );
umount( $::imscpConfig{'USER_WEB_DIR'} );

1;
__END__
