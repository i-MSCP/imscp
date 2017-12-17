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

iMSCP::Bootstrapper->getInstance()->boot( {
    mode            => 'backend',
    nodatabase      => 1,
    config_readonly => 1
} );

my $passwd = decryptRijndaelCBC( $main::imscpKEY, $main::imscpIV, $main::imscpConfig{'DATABASE_PASSWORD'} );
print output( sprintf( "Your i-MSCP master SQL user is         : \x1b[1m%s\x1b[0m", $main::imscpConfig{'DATABASE_USER'} ), 'info' );
print output( sprintf( "Your i-MSCP master SQL user password is: \x1b[1m%s\x1b[0m", $passwd ), 'info' );
print output( 'Information based on data from your /etc/imscp/imscp.conf file.', 'warn' );

1;
__END__
