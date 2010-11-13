#!/usr/bin/perl

# ispCP ω (OMEGA) a Virtual Hosting Control Panel
# Copyright (C) 2001-2006 by moleSoftware GmbH - http://www.molesoftware.com
# Copyright (C) 2006-2010 by isp Control Panel - http://i-mscp.net
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

# Hide the "used only once: possible typo" warnings
no warnings 'once';

$main::engine_debug = undef;

require 'ispcp_common_methods.pl';
require 'i-mscp-db-keys.pl';

################################################################################
# Load ispCP configuration from the i-mscp.conf file

if(-e '/usr/local/etc/i-mscp/i-mscp.conf'){
	$main::cfg_file = '/usr/local/etc/i-mscp/i-mscp.conf';
} else {
	$main::cfg_file = '/etc/i-mscp/i-mscp.conf';
}

my $rs = get_conf($main::cfg_file);
die("FATAL: Can't load the i-mscp.conf file") if($rs != 0);

################################################################################
# Enable debug mode if needed
if ($main::cfg{'DEBUG'} != 0) {
	$main::engine_debug = '_on_';
}

################################################################################
# Generating ispCP Db key and initialization vector if needed
#
if ($main::db_pass_key eq '{KEY}' || $main::db_pass_iv eq '{IV}') {

	print STDOUT "\tGenerating database keys, it may take some time, please ".
		"wait...\n";

	print STDOUT "\tIf it takes to long, please check: ".
	 "http://www.isp-control.net/documentation/frequently_asked_questions/what".
	 "_does_generating_database_keys_it_may_take_some_time_please_wait..._on_".
	 "setup_mean\n";

	$rs = sys_command(
		"perl $main::cfg{'ROOT_DIR'}/keys/rpl.pl " .
		"$main::cfg{'GUI_ROOT_DIR'}/include/i-mscp-db-keys.php " .
		"$main::cfg{'ROOT_DIR'}/engine/i-mscp-db-keys.pl " .
		"$main::cfg{'ROOT_DIR'}/engine/messenger/i-mscp-db-keys.pl"
	);

	die('FATAL: Error during database keys generation!') if ($rs != 0);

	do 'i-mscp-db-keys.pl';
}

################################################################################
# Lock file system variables
#
$main::lock_file = $main::cfg{'MR_LOCK_FILE'};
$main::fh_lock_file = undef;

$main::log_dir = $main::cfg{'LOG_DIR'};
$main::root_dir = $main::cfg{'ROOT_DIR'};

$main::i-mscp = "$main::log_dir/i-mscp-rqst-mngr.el";

################################################################################
# ispcp_rqst_mngr variables
#
$main::ispcp_rqst_mngr = "$main::root_dir/engine/i-mscp-rqst-mngr";
$main::ispcp_rqst_mngr_el = "$main::log_dir/i-mscp-rqst-mngr.el";
$main::ispcp_rqst_mngr_stdout = "$main::log_dir/i-mscp-rqst-mngr.stdout";
$main::ispcp_rqst_mngr_stderr = "$main::log_dir/i-mscp-rqst-mngr.stderr";

################################################################################
# ispcp_dmn_mngr variables
#
$main::ispcp_dmn_mngr = "$main::root_dir/engine/i-mscp-dmn-mngr";
$main::ispcp_dmn_mngr_el = "$main::log_dir/i-mscp-dmn-mngr.el";
$main::ispcp_dmn_mngr_stdout = "$main::log_dir/i-mscp-dmn-mngr.stdout";
$main::ispcp_dmn_mngr_stderr = "$main::log_dir/i-mscp-dmn-mngr.stderr";

################################################################################
# ispcp_sub_mngr variables
#
$main::ispcp_sub_mngr = "$main::root_dir/engine/i-mscp-sub-mngr";
$main::ispcp_sub_mngr_el = "$main::log_dir/i-mscp-sub-mngr.el";
$main::ispcp_sub_mngr_stdout = "$main::log_dir/i-mscp-sub-mngr.stdout";
$main::ispcp_sub_mngr_stderr = "$main::log_dir/i-mscp-sub-mngr.stderr";

################################################################################
# ispcp_alssub_mngr variables
#
$main::ispcp_alssub_mngr = "$main::root_dir/engine/i-mscp-alssub-mngr";
$main::ispcp_alssub_mngr_el = "$main::log_dir/i-mscp-alssub-mngr.el";
$main::ispcp_alssub_mngr_stdout = "$main::log_dir/i-mscp-alssub-mngr.stdout";
$main::ispcp_alssub_mngr_stderr = "$main::log_dir/i-mscp-alssub-mngr.stderr";

################################################################################
# ispcp_als_mngr variables
#
$main::ispcp_als_mngr = "$main::root_dir/engine/i-mscp-als-mngr";
$main::ispcp_als_mngr_el = "$main::log_dir/i-mscp-als-mngr.el";
$main::ispcp_als_mngr_stdout = "$main::log_dir/i-mscp-als-mngr.stdout";
$main::ispcp_als_mngr_stderr = "$main::log_dir/i-mscp-als-mngr.stderr";

################################################################################
# ispcp_mbox_mngr variables
#
$main::ispcp_mbox_mngr = "$main::root_dir/engine/i-mscp-mbox-mngr";
$main::ispcp_mbox_mngr_el = "$main::log_dir/i-mscp-mbox-mngr.el";
$main::ispcp_mbox_mngr_stdout = "$main::log_dir/i-mscp-mbox-mngr.stdout";
$main::ispcp_mbox_mngr_stderr = "$main::log_dir/i-mscp-mbox-mngr.stderr";

################################################################################
# ispcp_serv_mngr variables
#
$main::ispcp_serv_mngr = "$main::root_dir/engine/i-mscp-serv-mngr";
$main::ispcp_serv_mngr_el = "$main::log_dir/i-mscp-serv-mngr.el";
$main::ispcp_serv_mngr_stdout = "$main::log_dir/i-mscp-serv-mngr.stdout";
$main::ispcp_serv_mngr_stderr = "$main::log_dir/i-mscp-serv-mngr.stderr";

################################################################################
# ispcp_net_interfaces_mngr variables
#
$main::ispcp_net_interfaces_mngr = "$main::root_dir/engine/tools/i-mscp-net-interfaces-mngr";
$main::ispcp_net_interfaces_mngr_el = "$main::log_dir/i-mscp-net-interfaces-mngr.el";
$main::ispcp_net_interfaces_mngr_stdout = "$main::log_dir/i-mscp-net-interfaces-mngr.log";

################################################################################
# ispcp_htaccess_mngr variables
#
$main::ispcp_htaccess_mngr = "$main::root_dir/engine/i-mscp-htaccess-mngr";
$main::ispcp_htaccess_mngr_el = "$main::log_dir/i-mscp-htaccess-mngr.el";
$main::ispcp_htaccess_mngr_stdout = "$main::log_dir/i-mscp-htaccess-mngr.stdout";
$main::ispcp_htaccess_mngr_stderr = "$main::log_dir/i-mscp-htaccess-mngr.stderr";

################################################################################
# ispcp_htusers_mngr variables
#
$main::ispcp_htusers_mngr = "$main::root_dir/engine/i-mscp-htusers-mngr";
$main::ispcp_htusers_mngr_el = "$main::log_dir/i-mscp-htusers-mngr.el";
$main::ispcp_htusers_mngr_stdout = "$main::log_dir/i-mscp-htusers-mngr.stdout";
$main::ispcp_htusers_mngr_stderr = "$main::log_dir/i-mscp-htusers-mngr.stderr";

################################################################################
# ispcp_htgroups_mngr variables
#
$main::ispcp_htgroups_mngr = "$main::root_dir/engine/i-mscp-htgroups-mngr";
$main::ispcp_htgroups_mngr_el = "$main::log_dir/i-mscp-htgroups-mngr.el";
$main::ispcp_htgroups_mngr_stdout = "$main::log_dir/i-mscp-htgroups-mngr.stdout";
$main::ispcp_htgroups_mngr_stderr = "$main::log_dir/i-mscp-htgroups-mngr.stderr";


################################################################################
# ispcp_vrl_traff variables
#
$main::ispcp_vrl_traff = "$main::root_dir/engine/messenger/i-mscp-vrl-traff";
$main::ispcp_vrl_traff_el = "$main::log_dir/i-mscp-vrl-traff.el";
$main::ispcp_vrl_traff_stdout = "$main::log_dir/i-mscp-vrl-traff.stdout";
$main::ispcp_vrl_traff_stderr = "$main::log_dir/i-mscp-vrl-traff.stderr";

################################################################################
# ispcp_httpd_logs variables
#
$main::ispcp_httpd_logs_mngr_el = "$main::log_dir/i-mscp-httpd-logs-mngr.el";
$main::ispcp_httpd_logs_mngr_stdout = "$main::log_dir/i-mscp-httpd-logs-mngr.stdout";
$main::ispcp_httpd_logs_mngr_stderr = "$main::log_dir/i-mscp-httpd-logs-mngr.stderr";

################################################################################
# ispcp_ftp_acc_mngr variables
# hu ???
$main::ispcp_ftp_acc_mngr_el = "$main::log_dir/i-mscp-ftp-acc-mngr.el";
$main::ispcp_ftp_acc_mngr_stdout = "$main::log_dir/i-mscp-ftp-acc-mngr.stdout";
$main::ispcp_ftp_acc_mngr_stderr = "$main::log_dir/i-mscp-ftp-acc-mngr.stderr";

$main::ispcp_bk_task_el = "$main::log_dir/i-mscp-bk-task.el";
$main::ispcp_srv_traff_el = "$main::log_dir/i-mscp-srv-traff.el";
$main::ispcp_dsk_quota_el = "$main::log_dir/i-mscp-dsk-quota.el";

1;
