#!/bin/bash

# VHCS ω (OMEGA) - Virtual Hosting Control System | Omega Version
# Copyright (c) 2006-2007 by ispCP | http://isp-control.net
#
#
# License:
#    This program is free software; you can redistribute it and/or
#    modify it under the terms of the GPL General Public License
#    as published by the Free Software Foundation; either version 2.0
#    of the License, or (at your option) any later version.
#
#    This program is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GPL General Public License for more details.
#
#    You may have received a copy of the GPL General Public License
#    along with this program.
#
#    An on-line copy of the GPL General Public License can be found
#    http://www.fsf.org/licensing/licenses/gpl.txt
#
########################################################################
#
# This Script only resetts the VHCS Setup, it WON'T uninstall VHCS!!
# Afterwards a new install is possible. Use it, if you had an installation
# error during setup.
#
# Keep attention: The VHCS database will be deleted with all its content!
#
########################################################################

require 'vhcs2_common_code.pl';
use strict;
use warnings;

## Variables
my ($rs, $sql)	= (undef, undef);
my $user_prefix = $main::cfg{'APACHE_SUEXEC_USER_PREF'};
my $master_user = $main::cfg{'APACHE_SUEXEC_MIN_UID'};
my $user_delete	= $main::cfg{'CMD_USERDEL'};
my $database	= $main::cfg{'DATABASE_NAME'};

## MAIN
echo "Re-setting VHCS 2 Setup!";
echo "========================";
my $delete_cmd = "$user_delete $master_user$user_prefix";
$rs = sys_command($delete_cmd);

$sql = "drop database $database";
($rs, $rdata) = doSQL($sql);
if ($rs != 0) {
	echo "An error occured!";
}
echo "done!"
return 1