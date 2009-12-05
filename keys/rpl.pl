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

die ("Bad number of arguments!") if (scalar(@ARGV) != 3);

map {s/'/\\'/g, chop}
	my $key = gen_sys_rand_num(32),
	my $iv = gen_sys_rand_num(8);

# Tags preparation
my %tags_hash = (
	'{KEY}' => $key,
	'{IV}'  => $iv
);

my ($rs, $file) = (undef, undef);

foreach(@ARGV)
{
	# Loading the template file
	($rs, $file) = get_file($_);
	die("FATAL: Unable to load the template $_ file") if($rs != 0);

	# Building the new file
	($rs, $file) = prep_tpl(\%tags_hash, $file);
	die("FATAL: Unable to builds the new $_ file") if($rs != 0);

	# Saving the new file
	$rs = save_file($_, $file);
	die("FATAL: Unable to save the new $_ file") if($rs != 0);
}

exit 0;
