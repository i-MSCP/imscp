#!/usr/bin/perl

# ispCP ω (OMEGA) a Virtual Hosting Control Panel
# Copyright (C) 2001-2006 by moleSoftware GmbH - http://www.molesoftware.com
# Copyright (C) 2006-2010 by isp Control Panel - http://ispcp.net
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
# Portions created by the ispCP Team are Copyright (C) 2006-2010 by
# isp Control Panel. All Rights Reserved.
#
# The ispCP ω Home Page is:
#
#    http://isp-control.net
#

use strict;
use warnings;
no warnings 'once';

$main::engine_debug = undef;

require 'ispcp_common_methods.pl';
require 'ispcp-db-keys.pl';

my $rs;

if(-e '/usr/local/etc/ispcp/ispcp.conf'){
	$main::cfg_file = '/usr/local/etc/ispcp/ispcp.conf';
} else {
	$main::cfg_file = '/etc/ispcp/ispcp.conf';
}

$rs = get_conf($main::cfg_file);
die("FATAL: Can't load the ispcp.conf file") if($rs !=0);

if ($main::cfg{'DEBUG'} != 0) {
	$main::engine_debug = '_on_';
}
if($rs){
	print '';
}
if ($main::db_pass_key eq '{KEY}' || $main::db_pass_iv eq '{IV}') {

	print STDOUT "\tGenerating database keys, it may take some time, please ".
		"wait...\n";
	print STDOUT "\tIf it takes to long, please check".
	 "http://www.isp-control.net/documentation/frequently_asked_questions/what".
	 "_does_generating_database_keys_it_may_take_some_time_please_wait..._on_".
	 "setup_mean\n";

	$rs = sys_command(
		"perl $main::cfg{'ROOT_DIR'}/keys/rpl.pl " .
		"$main::cfg{'GUI_ROOT_DIR'}/include/ispcp-db-keys.php " .
		"$main::cfg{'ROOT_DIR'}/engine/ispcp-db-keys.pl " .
		"$main::cfg{'ROOT_DIR'}/engine/messenger/ispcp-db-keys.pl"
	);

	die('FATAL: Error during database keys generation !') if ($rs != 0);

	do 'ispcp-db-keys.pl';
}

$main::lock_file = $main::cfg{'MR_LOCK_FILE'};

$main::log_dir = $main::cfg{'LOG_DIR'};

$main::root_dir = $main::cfg{'ROOT_DIR'};

$main::ispcp = "$main::log_dir/ispcp-rqst-mngr.el";

$main::ispcp_rqst_mngr = "$main::root_dir/engine/ispcp-rqst-mngr";

$main::ispcp_rqst_mngr_el = "$main::log_dir/ispcp-rqst-mngr.el";
$main::ispcp_rqst_mngr_stdout = "$main::log_dir/ispcp-rqst-mngr.stdout";
$main::ispcp_rqst_mngr_stderr = "$main::log_dir/ispcp-rqst-mngr.stderr";

$main::ispcp_dmn_mngr = "$main::root_dir/engine/ispcp-dmn-mngr";

$main::ispcp_dmn_mngr_el = "$main::log_dir/ispcp-dmn-mngr.el";
$main::ispcp_dmn_mngr_stdout = "$main::log_dir/ispcp-dmn-mngr.stdout";
$main::ispcp_dmn_mngr_stderr = "$main::log_dir/ispcp-dmn-mngr.stderr";

$main::ispcp_sub_mngr = "$main::root_dir/engine/ispcp-sub-mngr";

$main::ispcp_sub_mngr_el = "$main::log_dir/ispcp-sub-mngr.el";
$main::ispcp_sub_mngr_stdout = "$main::log_dir/ispcp-sub-mngr.stdout";
$main::ispcp_sub_mngr_stderr = "$main::log_dir/ispcp-sub-mngr.stderr";

$main::ispcp_alssub_mngr = "$main::root_dir/engine/ispcp-alssub-mngr";

$main::ispcp_alssub_mngr_el = "$main::log_dir/ispcp-alssub-mngr.el";
$main::ispcp_alssub_mngr_stdout = "$main::log_dir/ispcp-alssub-mngr.stdout";
$main::ispcp_alssub_mngr_stderr = "$main::log_dir/ispcp-alssub-mngr.stderr";

$main::ispcp_als_mngr = "$main::root_dir/engine/ispcp-als-mngr";

$main::ispcp_als_mngr_el = "$main::log_dir/ispcp-als-mngr.el";
$main::ispcp_als_mngr_stdout = "$main::log_dir/ispcp-als-mngr.stdout";
$main::ispcp_als_mngr_stderr = "$main::log_dir/ispcp-als-mngr.stderr";

$main::ispcp_mbox_mngr = "$main::root_dir/engine/ispcp-mbox-mngr";

$main::ispcp_mbox_mngr_el = "$main::log_dir/ispcp-mbox-mngr.el";
$main::ispcp_mbox_mngr_stdout = "$main::log_dir/ispcp-mbox-mngr.stdout";
$main::ispcp_mbox_mngr_stderr = "$main::log_dir/ispcp-mbox-mngr.stderr";

$main::ispcp_serv_mngr = "$main::root_dir/engine/ispcp-serv-mngr";

$main::ispcp_serv_mngr_el = "$main::log_dir/ispcp-serv-mngr.el";
$main::ispcp_serv_mngr_stdout = "$main::log_dir/ispcp-serv-mngr.stdout";
$main::ispcp_serv_mngr_stderr = "$main::log_dir/ispcp-serv-mngr.stderr";

$main::ispcp_net_interfaces_mngr = "$main::root_dir/engine/tools/ispcp-net-interfaces-mngr";
$main::ispcp_net_interfaces_mngr_el = "$main::log_dir/ispcp-net-interfaces-mngr.el";
$main::ispcp_net_interfaces_mngr_stdout = "$main::log_dir/ispcp-net-interfaces-mngr.log";

#
# htaccess manager variables.
#

$main::ispcp_htaccess_mngr = "$main::root_dir/engine/ispcp-htaccess-mngr";

$main::ispcp_htaccess_mngr_el = "$main::log_dir/ispcp-htaccess-mngr.el";
$main::ispcp_htaccess_mngr_stdout = "$main::log_dir/ispcp-htaccess-mngr.stdout";
$main::ispcp_htaccess_mngr_stderr = "$main::log_dir/ispcp-htaccess-mngr.stderr";

#
# htusers manager variables.
#

$main::ispcp_htusers_mngr = "$main::root_dir/engine/ispcp-htusers-mngr";

$main::ispcp_htusers_mngr_el = "$main::log_dir/ispcp-htusers-mngr.el";
$main::ispcp_htusers_mngr_stdout = "$main::log_dir/ispcp-htusers-mngr.stdout";
$main::ispcp_htusers_mngr_stderr = "$main::log_dir/ispcp-htusers-mngr.stderr";

#
# htgroups manager variables.
#

$main::ispcp_htgroups_mngr = "$main::root_dir/engine/ispcp-htgroups-mngr";

$main::ispcp_htgroups_mngr_el = "$main::log_dir/ispcp-htgroups-mngr.el";
$main::ispcp_htgroups_mngr_stdout = "$main::log_dir/ispcp-htgroups-mngr.stdout";
$main::ispcp_htgroups_mngr_stderr = "$main::log_dir/ispcp-htgroups-mngr.stderr";


$main::ispcp_vrl_traff = "$main::root_dir/engine/messenger/ispcp-vrl-traff";

$main::ispcp_vrl_traff_el = "$main::log_dir/ispcp-vrl-traff.el";
$main::ispcp_vrl_traff_stdout = "$main::log_dir/ispcp-vrl-traff.stdout";
$main::ispcp_vrl_traff_stderr = "$main::log_dir/ispcp-vrl-traff.stderr";

$main::ispcp_vrl_traff_correction_el = "$main::log_dir/ispcp-vrl-traff-correction.el";

$main::ispcp_httpd_logs_mngr_el = "$main::log_dir/ispcp-httpd-logs-mngr.el";
$main::ispcp_httpd_logs_mngr_stdout = "$main::log_dir/ispcp-httpd-logs-mngr.stdout";
$main::ispcp_httpd_logs_mngr_stderr = "$main::log_dir/ispcp-httpd-logs-mngr.stderr";

$main::ispcp_ftp_acc_mngr_el = "$main::log_dir/ispcp-ftp-acc-mngr.el";
$main::ispcp_ftp_acc_mngr_stdout = "$main::log_dir/ispcp-ftp-acc-mngr.stdout";
$main::ispcp_ftp_acc_mngr_stderr = "$main::log_dir/ispcp-ftp-acc-mngr.stderr";

$main::ispcp_bk_task_el = "$main::log_dir/ispcp-bk-task.el";

$main::ispcp_srv_traff_el = "$main::log_dir/ispcp-srv-traff.el";

$main::ispcp_dsk_quota_el = "$main::log_dir/ispcp-dsk-quota.el";

1;
