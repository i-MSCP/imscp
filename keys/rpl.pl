#!/usr/bin/perl
 
# ispCP ω (OMEGA) a Virtual Hosting Control Panel
# Copyright (C) 2001-2006 by moleSoftware GmbH - http://www.molesoftware.com
# Copyright (C) 2006-2009 by isp Control Panel - http://ispcp.net
#
# Version: $Id$
#
# The contents of this file are subject to the Mozilla Public License
# Version 1.1 (the "License"); you may not use this file except in
# compliance with the License. You may obtain a copy of the License at
# http://www.mozilla.org/MPL/
#
# Software distributed under the License is distributed on an "AS IS"
# basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
# License for the specific language governing rights and limitations
# under the License.
#
# The Original Code is "VHCS - Virtual Hosting Control System".
#
# The Initial Developer of the Original Code is moleSoftware GmbH.
# Portions created by Initial Developer are Copyright (C) 2001-2006
# by moleSoftware GmbH. All Rights Reserved.
# Portions created by the ispCP Team are Copyright (C) 2006-2009 by
# isp Control Panel. All Rights Reserved.
#
# The ispCP ω Home Page is:
#
#    http://isp-control.net
#

use FindBin;

use lib "$FindBin::Bin/../engine";
require 'ispcp_common_methods.pl';

use strict;
use warnings;
$SIG{'INT'} = 'IGNORE';

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
