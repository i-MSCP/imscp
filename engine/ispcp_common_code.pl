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

use strict;
use warnings;

$main::engine_debug = undef;

require 'ispcp_common_methods.pl';
require 'ispcp-db-keys.pl';

my $rs;

$main::cfg_file = '/etc/ispcp/ispcp.conf';

$rs = get_conf($main::cfg_file);

return $rs if ($rs != 0);

if ($main::cfg{'DEBUG'} != 0) {
	$main::engine_debug = '_on_';
}

if ($main::db_pass_key eq '{KEY}' || $main::db_pass_iv eq '{IV}') {

	print STDOUT "\tGenerating database keys, it may take some time, please wait...\n";
	print STDOUT "\tIf it takes to long, please check http://www.isp-control.net/documentation/frequently_asked_questions/what_does_generating_database_keys_it_may_take_some_time_please_wait..._on_setup_mean\n";

	$rs = sys_command("perl $main::cfg{'ROOT_DIR'}/keys/rpl.pl $main::cfg{'GUI_ROOT_DIR'}/include/ispcp-db-keys.php $main::cfg{'ROOT_DIR'}/engine/ispcp-db-keys.pl $main::cfg{'ROOT_DIR'}/engine/messager/ispcp-db-keys.pl");

	return $rs if ($rs != 0);

	do 'ispcp-db-keys.pl';
	get_conf();
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

#
# htuser manager variables.
#

$main::ispcp_htuser_mngr = "$main::root_dir/engine/ispcp-htuser-mngr";

$main::ispcp_htuser_mngr_el = "$main::log_dir/ispcp-htuser-mngr.el";
$main::ispcp_htuser_mngr_stdout = "$main::log_dir/ispcp-htuser-mngr.stdout";
$main::ispcp_htuser_mngr_stderr = "$main::log_dir/ispcp-htuser-mngr.stderr";


$main::ispcp_vrl_traff = "$main::root_dir/engine/messager/ispcp-vrl-traff";

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

########################################################################

return 1;
