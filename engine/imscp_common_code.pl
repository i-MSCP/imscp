#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
#
# Copyright (C) 2001-2006 by moleSoftware GmbH - http://www.molesoftware.com
# Copyright (C) 2006-2010 by isp Control Panel - http://ispcp.net
# Copyright (C) 2010-2015 by internet Multi Server Control Panel - http://i-mscp.net
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
#
# Portions created by the ispCP Team are Copyright (C) 2006-2010 by
# isp Control Panel. All Rights Reserved.
#
# Portions created by the i-MSCP Team are Copyright (C) 2010-2015 by
# internet Multi Server Control Panel. All Rights Reserved.
#
# The i-MSCP Home Page is:
#
#    http://i-mscp.net
#

BEGIN {
	my %needed 	= (
		'DBI'=> '',
		DBD::mysql => '',
		MIME::Entity => '',
		Crypt::CBC => '',
		Crypt::PasswdMD5 => '',
		MIME::Base64 => '',
		File::Basename => '',
		File::Path => '',
		File::Temp => 'qw(tempdir)',
		File::Copy::Recursive => 'qw(rcopy)',
		Net::LibIDN => 'qw/idn_to_ascii idn_to_unicode/'
	);

	my ($mod, $mod_err, $mod_missing) = ('', '_off_', '');

	for $mod (keys %needed) {
		if (eval "require $mod") {
			eval "use $mod $needed{$mod}";
		} else {
			print STDERR "\n[FATAL] Module [$mod] WAS NOT FOUND !\n" ;

			$mod_err = '_on_';

			if ($mod_missing eq '') {
				$mod_missing .= $mod;
			} else {
				$mod_missing .= ", $mod";
			}
		}
	}

	if ($mod_err eq '_on_') {
		print STDERR "\nModules [$mod_missing] WAS NOT FOUND in your system...\n";
		exit 1;
	} else {
		$| = 1;
	}
}

use strict;
use warnings;

# Hide the "used only once: possible typo" warnings
no warnings 'once';

$main::engine_debug = undef;

require 'imscp_common_methods.pl';

# Load i-MSCP configuration from the imscp.conf file

if(-f '/usr/local/etc/imscp/imscp.conf'){
	$main::cfg_file = '/usr/local/etc/imscp/imscp.conf';
} else {
	$main::cfg_file = '/etc/imscp/imscp.conf';
}

my $rs = get_conf($main::cfg_file);
die('FATAL: Unable to load imscp.conf file.') if $rs;

# Enable debug mode if needed

if ($main::cfg{'DEBUG'}) {
	$main::engine_debug = '_on_';
}

# Load i-MSCP Db key and initialization vector

my $key_file = "$main::cfg{'CONF_DIR'}/imscp-db-keys";
our $db_pass_key = '{KEY}';
our $db_pass_iv = '{IV}';

require "$key_file" if -f $key_file;

# Check for i-MSCP Db key and initialization vector

if ($db_pass_key eq '{KEY}' || $db_pass_iv eq '{IV}') {
	print STDERR ("Key file not found at $main::cfg{'CONF_DIR'}/imscp-db-keys. Run i-MSCP setup script to fix.");
	exit 1;
}

$main::db_pass_key = $db_pass_key;
$main::db_pass_iv = $db_pass_iv;

die('FATAL: Unable to load database parameters') if setup_db_vars();

# Lock file system variables

$main::lock_file = '/tmp/imscp.lock';
$main::fh_lock_file = undef;

$main::log_dir = $main::cfg{'LOG_DIR'};
$main::root_dir = $main::cfg{'ROOT_DIR'};

$main::imscp = "$main::log_dir/imscp-rqst-mngr.el";


# imscp-serv-traff variable

$main::imscp_srv_traff_el = "$main::log_dir/imscp-srv-traff.el";

# Software installer log variables

$main::imscp_pkt_mngr = "$main::root_dir/engine/imscp-pkt-mngr";
$main::imscp_pkt_mngr_el = "$main::log_dir/imscp-pkt-mngr.el";
$main::imscp_pkt_mngr_stdout = "$main::log_dir/imscp-pkt-mngr.stdout";
$main::imscp_pkt_mngr_stderr = "$main::log_dir/imscp-pkt-mngr.stderr";

$main::imscp_sw_mngr = "$main::root_dir/engine/imscp-sw-mngr";
$main::imscp_sw_mngr_el = "$main::log_dir/imscp-sw-mngr.el";
$main::imscp_sw_mngr_stdout = "$main::log_dir/imscp-sw-mngr.stdout";
$main::imscp_sw_mngr_stderr = "$main::log_dir/imscp-sw-mngr.stderr";

1;
