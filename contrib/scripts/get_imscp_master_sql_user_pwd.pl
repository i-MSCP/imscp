#!/usr/bin/perl
# Copyright (C) 2016-2017 Laurent Declercq <l.declercq@nuxwin.com>
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

# Print current i-MSCP master SQL user password

use strict;
use warnings;
use lib '/var/www/imscp/engine/PerlLib';
use iMSCP::Bootstrapper;
use iMSCP::Crypt qw/ decryptRijndaelCBC /;
use iMSCP::Debug qw/ output /;

iMSCP::Bootstrapper->getInstance()->boot(
    {
        mode            => 'backend',
        nodatabase      => 1,
        config_readonly => 1
    }
);

my $passwd = decryptRijndaelCBC( $main::imscpDBKey, $main::imscpDBiv, $main::imscpConfig{'DATABASE_PASSWORD'} );
print output( sprintf( 'Current i-MSCP master SQL password is: %s', $passwd ), 'info' );

1;
__END__
