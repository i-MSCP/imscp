#!/usr/bin/perl

# ispCP ω (OMEGA) a Virtual Hosting Control Panel
# Copyright (c) 2001-2006 by moleSoftware GmbH
# http://www.molesoftware.com
# Copyright (c) 2006-2007 by isp Control Panel
# http://isp-control.net
#
#
# License:
#    This program is free software; you can redistribute it and/or
#    modify it under the terms of the MPL Mozilla Public License
#    as published by the Free Software Foundation; either version 1.1
#    of the License, or (at your option) any later version.
#
#    This program is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    MPL Mozilla Public License for more details.
#
#    You may have received a copy of the MPL Mozilla Public License
#    along with this program.
#
#    An on-line copy of the MPL Mozilla Public License can be found
#    http://www.mozilla.org/MPL/MPL-1.1.html
#
#
# The ispCP ω Home Page is at:
#
#    http://isp-control.net
#

use FindBin;

use lib "$FindBin::Bin/../engine";
require 'ispcp_common_methods.pl';

use strict;
use warnings;

die ("Undefined Input Data!") if (!defined($ARGV[0]) || !defined($ARGV[1]) || !defined($ARGV[2]));

my ($php_fname, $perl_fname, $perl_fname2) = ($ARGV[0], $ARGV[1], $ARGV[2]);

my $key = gen_sys_rand_num(32);
my $iv  = gen_sys_rand_num(8);

$key =~ s/'/\\'/gi;
$iv =~ s/'/\\'/gi;

# remove \n at the end of lines;

chop($key);
chop($iv);

my ($rs, $php_file, $perl_file) = (undef, undef, undef);

my %tag_hash = (
                    '{KEY}' => $key,
                    '{IV}'  => $iv
                );

# php lib;

$php_file = get_file($php_fname);

($rs, $php_file) = prep_tpl(\%tag_hash, $php_file);

return $rs if ($rs != 0);

$rs = save_file($php_fname, $php_file);

return $rs if ($rs != 0);

# perl lib;

$perl_file = get_file($perl_fname);

($rs, $perl_file) = prep_tpl(\%tag_hash, $perl_file);

return $rs if ($rs != 0);

$rs = save_file($perl_fname, $perl_file);

return $rs if ($rs != 0);


# perl lib for autoresponder;

$rs = save_file($perl_fname2, $perl_file);

return $rs if ($rs != 0);
