#!/usr/bin/perl

# ispCP ω (OMEGA) a Virtual Hosting Control Panel
# Copyright (c) 2007-2008 by ispCP
# http://isp-control.net
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
# The ispCP ω Home Page is at:
#
#    http://isp-control.net
#

use strict;
use warnings;

################################################################################
##                                SUBROUTINES                                 ##
################################################################################

sub ask_hostname {
	push_el(\@main::el, 'ask_hostname()', 'Starting...');

	my ($rs, $rdata) = (undef, undef);
	my $hostname = undef;

	($rs, $hostname) = get_sys_hostname();
	return $rs if ($rs != 0);

	my $qmsg = "\tPlease enter fully qualified hostname. [$hostname]: ";
	print STDOUT $qmsg;

	$rdata = readline(\*STDIN); chop($rdata);

	if (!defined($rdata) || $rdata eq '') {
		$rdata = $hostname;
	}

	if ($rdata =~ /^((([\w][\w-]{0,253}[\w])\.)*)([\w][\w-]{0,253}[\w])\.([a-zA-Z]{2,6})$/) {
		$main::ua{'hostname'} = $rdata;
		$main::ua{'hostname_local'} = ( ($1) ? $1 : $4);
		$main::ua{'hostname_local'} =~ s/^([^.]+).+$/$1/;
	}
	else {
		print STDOUT "\n\tHostname is not a valid domain name!\n";
		return 1;
	}

	push_el(\@main::el, 'ask_hostname()', 'Ending...');
	return 0;
}

sub ask_eth {
	push_el(\@main::el, 'ask_eth()', 'Starting...');

	my ($rs, $rdata) = (undef, undef);
	#my $cmd = "/sbin/ifconfig |awk \'BEGIN{FS=\":\";}{print \$2}\'|awk \'{if(NR==2)print \$1}\' 1>/tmp/ispcp-setup.ip";
	my $cmd = "/sbin/ifconfig |grep -v inet6|grep inet|grep -v 127.0.0.1|awk ' {print \$2}'|head -n 1|awk -F: '{print \$NF}' 1>/tmp/ispcp-setup.ip";

	$rs = sys_command($cmd);
	return ($rs, '') if ($rs != 0);

	($rs, $rdata) = get_file("/tmp/ispcp-setup.ip");
	return ($rs, '') if ($rs != 0);

	chop($rdata);

	$rs = del_file("/tmp/ispcp-setup.ip");
	$rs = sys_command($cmd);
	return $rs if ($rs != 0);

	my $eth = $rdata;
	my $qmsg = "\n\tPlease enter system network address. [$eth]: ";
	print STDOUT $qmsg;

	$rdata = readline(\*STDIN); chop($rdata);

	if (!defined($rdata) || $rdata eq '') {
		$main::ua{'eth_ip'} = $eth;
	}
	else {
		$main::ua{'eth_ip'} = $rdata;
	}

	if (check_eth($main::ua{'eth_ip'}) != 0) {
		return 0;
	}

	push_el(\@main::el, 'ask_eth()', 'Ending...');
	return 1;
}

sub check_eth {

	my ($ip) = @_;
	return 0 if (!($ip  =~ /^(\d+)\.(\d+)\.(\d+)\.(\d+)$/));

	$ip  =~ /^(\d+)\.(\d+)\.(\d+)\.(\d+)$/;
	my ($d1, $d2, $d3, $d4) = ($1, $2, $3, $4);

	return 0 if (($d1 <= 0) || ($d1 >= 255));
	return 0 if (($d2 < 0) || ($d2 > 255));
	return 0 if (($d3 < 0) || ($d3 > 255));
	return 0 if (($d4 <= 0) || ($d4 >= 255));

	return 1;
}

sub ask_db_host {
	push_el(\@main::el, 'ask_db_host()', 'Starting...');

	my ($rs, $rdata) = (undef, undef);
	my $db_host = 'localhost';
	my $qmsg = "\n\tPlease enter SQL server host. [$db_host]: ";

	print STDOUT $qmsg;

	$rdata = readline(\*STDIN); chop($rdata);

	if (!defined($rdata) || $rdata eq '') {
		$main::ua{'db_host'} = $db_host;
	}
	else {
		$main::ua{'db_host'} = $rdata;
	}

	push_el(\@main::el, 'ask_db_host()', 'Ending...');
	return 0;
}

sub ask_db_name {
	push_el(\@main::el, 'ask_db_name()', 'Starting...');

	my ($rs, $rdata) = (undef, undef);
	my $db_name = 'ispcp';
	my $qmsg = "\n\tPlease enter system SQL database. [$db_name]: ";

	print STDOUT $qmsg;

	$rdata = readline(\*STDIN); chop($rdata);

	if (!defined($rdata) || $rdata eq '') {
		$main::ua{'db_name'} = $db_name;
	}
	else {
		$main::ua{'db_name'} = $rdata;
	}

	push_el(\@main::el, 'ask_db_name()', 'Ending...');
	return 0;
}


sub ask_db_user {
	push_el(\@main::el, 'ask_db_user()', 'Starting...');

	my ($rs, $rdata) = (undef, undef);
	my $db_user = 'root';
	my $qmsg = "\n\tPlease enter system SQL user. [$db_user]: ";

	print STDOUT $qmsg;
	$rdata = readline(\*STDIN); chop($rdata);

	if (!defined($rdata) || $rdata eq '') {
		$main::ua{'db_user'} = $db_user;
	}
	else {
		$main::ua{'db_user'} = $rdata;
	}

	push_el(\@main::el, 'ask_db_user()', 'Ending...');
	return 0;
}

sub ask_db_password {
	push_el(\@main::el, 'ask_db_password()', 'Starting...');

	my ($rs, $pass1, $pass2) = (undef, undef, undef);
	my $db_password = 'none';
	my $qmsg = "\n\tPlease enter system SQL password. [$db_password]: ";

	$pass1 = read_password($qmsg);

	if (!defined($pass1) || $pass1 eq '') {
		$main::ua{'db_password'} = '';
	}
	else {
		$qmsg = "\tPlease repeat system SQL password: ";
		$pass2 = read_password($qmsg);

		if ($pass1 eq $pass2) {
			$main::ua{'db_password'} = $pass1;
		}
		else {
			print STDOUT "\n\tPasswords do not match!";
			return 1;
		}
	}

	push_el(\@main::el, 'ask_db_password()', 'Ending...');
	return 0;
}

sub ask_db_ftp_user {
	push_el(\@main::el, 'ask_db_ftp_user()', 'Starting...');

	my ($rs, $rdata) = (undef, undef);
	my $db_user = 'vftp';
	my $qmsg = "\n\tPlease enter ispCP ftp SQL user. [$db_user]: ";

	print STDOUT $qmsg;
	$rdata = readline(\*STDIN); chop($rdata);

	if (!defined($rdata) || $rdata eq '') {
		$main::ua{'db_ftp_user'} = $db_user;
	}
	else {
		$main::ua{'db_ftp_user'} = $rdata;
	}

	push_el(\@main::el, 'ask_db_ftp_user()', 'Ending...');
	return 0;
}

sub ask_db_ftp_password {
	push_el(\@main::el, 'ask_db_ftp_password()', 'Starting...');

	my ($rs, $pass1, $pass2) = (undef, undef, undef);
	my $db_password = undef;
	my $qmsg = "\n\tPlease enter ispCP ftp SQL user password. [auto generate]: ";

	$pass1 = read_password($qmsg);

	if (!defined($pass1) || $pass1 eq '') {
		$db_password = gen_sys_rand_num(18);
		$db_password =~ s/('|")//g;
		$main::ua{'db_ftp_password'} = $db_password;
		print STDOUT "\tispCP ftp SQL user password set to: $db_password\n";
	}
	else {
		$qmsg = "\tPlease repeat ispCP ftp SQL user password: ";
		$pass2 = read_password($qmsg);

		if ($pass1 eq $pass2) {
			$main::ua{'db_ftp_password'} = $pass1;
		}
		else {
			print STDOUT "\n\tPasswords do not match!";
			return 1;
		}
	}

	push_el(\@main::el, 'ask_db_ftp_password()', 'Ending...');
	return 0;
}

sub ask_admin {
	push_el(\@main::el, 'ask_admin()', 'Starting...');

	my ($rs, $rdata) = (undef, undef);
	my $admin = 'admin';
	my $qmsg = "\n\tPlease enter administrator login name. [$admin]: ";
	print STDOUT $qmsg;

	$rdata = readline(\*STDIN); chop($rdata);

	if (!defined($rdata) || $rdata eq '') {
		$main::ua{'admin'} = $admin;
	}
	else {
		$main::ua{'admin'} = $rdata;
	}

	push_el(\@main::el, 'ask_admin()', 'Ending...');
	return 0;
}

sub ask_admin_password {
	push_el(\@main::el, 'ask_admin_password()', 'Starting...');

	my ($rs, $pass1, $pass2) = (undef, undef, undef);
	my $qmsg = "\n\tPlease enter administrator password: ";

	$pass1 = read_password($qmsg);

	if (!defined($pass1) || $pass1 eq '') {
		print STDOUT "\n\tPassword too short!";
		return 1;
	}
	else {
		if (length($pass1) < 5) {
			print STDOUT "\n\tPassword too short!";
			return 1;
		}
		$qmsg = "\tPlease repeat administrator password: ";
		$pass2 = read_password($qmsg);

		if ($pass1 =~ m/[a-zA-Z]/ && $pass1 =~ m/[0-9]/) {

			if ($pass1 eq $pass2) {
				$main::ua{'admin_password'} = $pass1;
			} else {
				print STDOUT "\n\tPasswords do not match!";
				return 1;
			}
		}
		else {
			print STDOUT "\n\tPasswords must contain at least digits and chars!";
			return 1;
		}
	}

	push_el(\@main::el, 'ask_admin_password()', 'Ending...');
	return 0;
}

sub ask_admin_email {
	push_el(\@main::el, 'ask_admin_email()', 'Starting...');

	my ($rs, $rdata) = (undef, undef);
	my $qmsg = "\n\tPlease enter admininistrator email address: ";
	print STDOUT $qmsg;

	$rdata = readline(\*STDIN); chop($rdata);

	if (!defined($rdata) || $rdata eq '') {
		return 1;
	}
	else {
		if ($rdata =~ /^([\w\W]{1,255})\@([\w][\w-]{0,253}[\w]\.)*([\w][\w-]{0,253}[\w])\.([a-zA-Z]{2,6})$/) {
			$main::ua{'admin_email'} = $rdata;
		} else {
			print STDOUT "\n\tE-Mail address not valid!";
			return 1;
		}
	}

	push_el(\@main::el, 'ask_admin_email()', 'Ending...');
	return 0;
}

sub ask_vhost {

	push_el(\@main::el, 'ask_vhost()', 'Starting...');

	my ($rs, $rdata) = (undef, undef);
	my $eth = $main::ua{'eth_ip'};
	my $addr = gethostbyaddr($main::ua{'eth_ip'}, AF_INET);

	if (!$addr) {
		$addr = $main::ua{'hostname'};
	}

	my $vhost = "admin.$addr";
	my $qmsg = "\n\tPlease enter the domain name where ispCP OMEGA will run on [$vhost]: ";

	print STDOUT $qmsg;

	$rdata = readline(\*STDIN);
	chop($rdata);

	if (!defined($rdata) || $rdata eq '') {
		$main::ua{'admin_vhost'} = $vhost;
	}
	else {
		if ($rdata =~ /^([\w][\w-]{0,253}[\w]\.)*([\w][\w-]{0,253}[\w])\.([a-zA-Z]{2,6})$/) {
			$main::ua{'admin_vhost'} = $rdata;
		}
		else {
			print STDOUT "\n\tVhost not valid!";
			return 1;
		}
	}

	push_el(\@main::el, 'ask_vhost()', 'Ending...');
	return 0;
}

sub ask_second_dns {
	push_el(\@main::el, 'ask_php_version()', 'Starting...');

	my $rdata = undef;
	my $qmsg = "\n\tIP of Secondary DNS. (optional) []: ";

	print STDOUT $qmsg;

	$rdata = readline(\*STDIN);
	chop($rdata);

	if (!defined($rdata) || $rdata eq '') {
		$main::ua{'secondary_dns'} = '';
	}
	else {
		if (check_eth($rdata) != 0) {
			$main::ua{'secondary_dns'} = $rdata;
		}
		else {
			print STDOUT "\n\tNo valid IP, please retry!";
			return 1;
		}
	}

	push_el(\@main::el, 'ask_php_version()', 'Ending...');
	return 0;
}

sub ask_mysql_prefix {
	push_el(\@main::el, 'ask_php_version()', 'Starting...');

	my $rdata = undef;
	my $qmsg = "\n\tUse MySQL Prefix.\n\tPossible values: [i]nfront, [b]ehind, [n]one. [none]: ";

	print STDOUT $qmsg;

	$rdata = readline(\*STDIN);
	chop($rdata);

	if (!defined($rdata) || $rdata eq '' || $rdata eq 'none' || $rdata eq 'n') {
		$main::ua{'mysql_prefix'} = 'no';
		$main::ua{'mysql_prefix_type'} = '';
	}
	else {
		if ($rdata eq 'infront' || $rdata eq 'i') {
			$main::ua{'mysql_prefix'} = 'yes';
			$main::ua{'mysql_prefix_type'} = 'infront';
		}
		elsif ($rdata eq 'behind' || $rdata eq 'b') {
			$main::ua{'mysql_prefix'} = 'yes';
			$main::ua{'mysql_prefix_type'} = 'behind';
		}
		else {
			print STDOUT "\n\tNot allowed Value, please retry!";
			return 1;
		}
	}

	push_el(\@main::el, 'ask_php_version()', 'Ending...');
	return 0;
}

# Set up PMA User
sub ask_db_pma_user {
	push_el(\@main::el, 'ask_db_pma_user()', 'Starting...');

	my ($rs, $rdata) = (undef, undef);
	my $db_user = 'pma';

	my $qmsg = "\n\tPlease enter ispCP phpMyAdmin Control user. [$db_user]: ";
	print STDOUT $qmsg;
	$rdata = readline(\*STDIN); chop($rdata);

	if (!defined($rdata) || $rdata eq '') {
		$main::ua{'db_pma_user'} = $db_user;
	} else {
		$main::ua{'db_pma_user'} = $rdata;
	}

	push_el(\@main::el, 'ask_db_pma_user()', 'Ending...');
	return 0;
}

# Set up PMA Passwort
sub ask_db_pma_password {

	my ($rs, $pass1, $pass2) = (undef, undef, undef);
	push_el(\@main::el, 'ask_db_pma_password()', 'Starting...');

	my $db_password = undef;

	my $qmsg = "\n\tPlease enter ispCP phpMyAdmin Control user password. [auto generate]: ";
	$pass1 = read_password($qmsg);

	if (!defined($pass1) || $pass1 eq '') {

		$db_password = gen_sys_rand_num(18);
		$db_password =~ s/('|")//g;
		$main::ua{'db_pma_password'} = $db_password;
		print STDOUT "\tphpMyAdmin Control user password set to: $db_password\n";

	} else {
		$qmsg = "\tPlease repeat ispCP phpMyAdmin Control user password: ";
		$pass2 = read_password($qmsg);

		if ($pass1 eq $pass2) {
			$main::ua{'db_pma_password'} = $pass1;
		} else {
			print STDOUT "\n\tPasswords do not match!";
			return 1;
		}
	}

	push_el(\@main::el, 'ask_db_pma_password()', 'Ending...');
	return 0;
}

# Set up AWStats
sub ask_awstats_on {

	my $rdata = undef;

	push_el(\@main::el, 'ask_awstats_on()', 'Starting...');

	my $qmsg = "\n\tActivate AWStats. [no]: ";

	print STDOUT $qmsg;

	$rdata = readline(\*STDIN);
	chop($rdata);

	if (!defined($rdata) || $rdata eq '') {
		$main::ua{'awstats_on'} = 'no';
	}
	else {
		if ($rdata eq 'yes' || $rdata eq 'y') {
			$main::ua{'awstats_on'} = 'yes';
		}
		elsif ($rdata eq 'no' || $rdata eq 'n') {
			$main::ua{'awstats_on'} = 'no';
		}
		else {
			print STDOUT "\n\tOnly '(y)es' and '(n)o' are allowed!";
			return 1;
		}
	}

	push_el(\@main::el, 'ask_awstats_on()', 'Ending...');

	return 0;
}

# Set up AWStats Static or Dynamic
sub ask_awstats_dyn {

	my $rdata = undef;

	push_el(\@main::el, 'ask_awstats_dyn()', 'Starting...');

	my $qmsg = "\n\tAWStats Mode:\n\tPossible values [d]ynamic and [s]tatic. [dynamic]: ";

	print STDOUT $qmsg;

	$rdata = readline(\*STDIN);
	chop($rdata);

	if (!defined($rdata) || $rdata eq '') {
		$main::ua{'awstats_dyn'} = '0';
	}
	else {
		if ($rdata eq 'dynamic' || $rdata eq 'd') {
			$main::ua{'awstats_dyn'} = '0';
		}
		elsif ($rdata eq 'static' || $rdata eq 's') {
			$main::ua{'awstats_dyn'} = '1';
		}
		else {
			print STDOUT "\n\tOnly '[d]ynamic' or '[s]tatic' are allowed!";
			return 1;
		}
	}

	push_el(\@main::el, 'ask_awstats_dyn()', 'Ending...');
	return 0;
}

# Set up PHP Version
sub ask_php_version {

	my $rdata = undef;

	push_el(\@main::el, 'ask_php_version()', 'Starting...');

	my $qmsg = "\n\tUse which PHP Version? (4 or 5). [5]: ";

	print STDOUT $qmsg;

	$rdata = readline(\*STDIN);
	chop($rdata);

	if (!defined($rdata) || $rdata eq '') {
		$main::ua{'php_version'} = '5';
	}
	else {
		if ($rdata eq 'php4' || $rdata eq '4') {
			$main::ua{'php_version'} = '4';
		}
		elsif ($rdata eq 'php5' || $rdata eq '5') {
			$main::ua{'php_version'} = '5';
		}
		else {
			print STDOUT "\n\tOnly 'php(4)' and 'php(5)' are allowed!";
			return 1;
		}
	}

	push_el(\@main::el, 'ask_php_version()', 'Ending...');
	return 0;
}

# Set up Crontab
sub setup_crontab {

	push_el(\@main::el, 'setup_crontab()', 'Starting...');

	my ($rs, $rdata, $awstats, $rkhunter, $ckrootkit) = (undef, undef, '');
	my $cfg_dir = "$main::cfg{'CONF_DIR'}/cron.d";
	my $bk_dir = "$cfg_dir/backup";
	my $wrk_dir = "$cfg_dir/working";
	my ($cfg_tpl, $cfg, $cmd) = (undef, undef, undef);

	if (! -e "$bk_dir/ispcp") {

		($rs, $cfg_tpl) = get_tpl($cfg_dir, 'ispcp');

		return $rs if ($rs != 0);

		$awstats = "";
		if ($main::cfg{'AWSTATS_ACTIVE'} ne 'yes' || $main::cfg{'AWSTATS_MODE'} eq 1) {
			$awstats = "#";
		}

		$rkhunter = `which rkhunter`;
		$ckrootkit = `which chkrootkit`;

    	$rkhunter =~ s/[ \t\n]$//g;
    	$ckrootkit =~ s/[ \t\n]$//g;

		my %tag_hash = (
						'{LOG_DIR}' => $main::cfg{'LOG_DIR'},
						'{CONF_DIR}' => $main::cfg{'CONF_DIR'},
						'{QUOTA_ROOT_DIR}' => $main::cfg{'QUOTA_ROOT_DIR'},
						'{TRAFF_ROOT_DIR}' => $main::cfg{'TRAFF_ROOT_DIR'},
						'{TOOLS_ROOT_DIR}' => $main::cfg{'TOOLS_ROOT_DIR'},
						'{BACKUP_ROOT_DIR}' => $main::cfg{'BACKUP_ROOT_DIR'},
						'{AWSTATS_ROOT_DIR}' => $main::cfg{'AWSTATS_ROOT_DIR'},
						'{RKHUNTER_LOG}' => $main::cfg{'RKHUNTER_LOG'},
						'{CHKROOTKIT_LOG}' => $main::cfg{'CHKROOTKIT_LOG'},
						'{AWSTATS_ENGINE_DIR}' => $main::cfg{'AWSTATS_ENGINE_DIR'},
						'{AW-ENABLED}' => $awstats,
						'{RK-ENABLED}' => !length($rkhunter)? "#" : "",
						'{RKHUNTER}'   => $rkhunter,
						'{CR-ENABLED}' => !length($ckrootkit)? "#" : "",
						'{CHKROOTKIT}'  => $ckrootkit
					   );

		($rs, $cfg) = prep_tpl(\%tag_hash, $cfg_tpl);
		return $rs if ($rs != 0);

		$rs = store_file("$bk_dir/ispcp", $cfg, $main::cfg{'ROOT_USER'}, $main::cfg{'ROOT_GROUP'}, 0644);
		return $rs if ($rs != 0);

		$cmd = "$main::cfg{'CMD_CP'} -p -f $bk_dir/ispcp $wrk_dir/";

		$rs = sys_command_rs($cmd);
		return $rs if ($rs != 0);
	}

	if ($main::cfg{'ROOT_GROUP'} eq "wheel") {
		$cmd = "$main::cfg{'CMD_CP'} -f $wrk_dir/ispcp /usr/local/etc/ispcp/cron.d/";
	} else {
		$cmd = "$main::cfg{'CMD_CP'} -f $wrk_dir/ispcp /etc/cron.d/";
	}

	$rs = sys_command_rs($cmd);
	return $rs if ($rs != 0);

	push_el(\@main::el, 'setup_crontab()', 'Ending...');
	return 0;
}

# Set up Bind
sub setup_named {
	push_el(\@main::el, 'setup_named()', 'Starting...');

	my ($rs, $rdata) = (undef, undef);
	my $cfg_dir = "$main::cfg{'CONF_DIR'}/bind";
	my $bk_dir = "$cfg_dir/backup";
	my $wrk_dir = "$cfg_dir/working";
	my ($cfg_tpl, $cfg, $cmd) = (undef, undef, undef);

	return 0 if ($main::cfg{'CMD_NAMED'} eq 'no');

	sys_command_rs("$main::cfg{'CMD_NAMED'} stop &> /tmp/ispcp-setup-services.log");

	($rs, $cfg_tpl) = get_file("$cfg_dir/named.conf");
	return $rs if ($rs != 0);

	if ((! -e "$bk_dir/named.conf.ispcp") && (-e $main::cfg{'BIND_CONF_FILE'})) {
		$cmd = "$main::cfg{'CMD_CP'} -p $main::cfg{'BIND_CONF_FILE'} $bk_dir/named.conf.system";
		$rs = sys_command($cmd);
		return $rs if ($rs != 0);

		$cfg = get_file($main::cfg{'BIND_CONF_FILE'});
		return $rs if ($rs != 0);

		$rs = store_file("$bk_dir/named.conf.ispcp", "$cfg$cfg_tpl", $main::cfg{'ROOT_USER'}, $main::cfg{'ROOT_GROUP'}, 0644);
		return $rs if ($rs != 0);
	}

	$cmd = "$main::cfg{'CMD_CP'} -p $bk_dir/named.conf.ispcp $main::cfg{'BIND_CONF_FILE'}";
	$rs = sys_command($cmd);
	return $rs if ($rs != 0);

	$rs = store_file("$wrk_dir/named.conf", "$cfg_tpl", $main::cfg{'ROOT_USER'}, $main::cfg{'ROOT_GROUP'}, 0644);
	return $rs if ($rs != 0);

	sys_command_rs("$main::cfg{'CMD_NAMED'} start &> /tmp/ispcp-setup-services.log");

	push_el(\@main::el, 'setup_named()', 'Ending...');
	return 0;
}

#
# Add Bind CFG
#

sub add_named_cfg_data {
    push_el(\@main::el, 'add_named_cfg_data()', 'Starting...');

    my ($base_vhost) = @_;
    my ($rs, $rdata) = (undef, undef);

    if (!defined($base_vhost) || $base_vhost eq '') {
        push_el(\@main::el, 'add_named_cfg_data()', 'ERROR: Undefined Input Data...');
        return -1;
    }

    #
    # Initial data we need;
    #

    my $conf_dir = $main::cfg{'CONF_DIR'};
    my $sys_cfg = $main::cfg{'BIND_CONF_FILE'};
    my $named_db_dir = $main::cfg{'BIND_DB_DIR'};

    my $tpl_dir = "$conf_dir/bind/parts";
    my $backup_dir = "$conf_dir/bind/backup";
    my $working_dir = "$conf_dir/bind/working";

    my $timestamp = time;
    my $backup_cfg = "$backup_dir/named.conf.$timestamp";
    my $working_cfg = "$working_dir/named.conf";

    #
    #  BEGIN/END tags, and templates needed for this config;
    #

    my ($dta_b, $dta_e, $entry_b, $entry_e, $entry) = ('', '', '', '', '');

    (
     $rs,
     $dta_b,
     $dta_e,
     $entry_b,
     $entry_e,
     $entry
    ) = get_tpl(
                $tpl_dir,
                'cfg_dta_b.tpl',
                'cfg_dta_e.tpl',
                'cfg_entry_b.tpl',
                'cfg_entry_e.tpl',
                'cfg_entry.tpl'
               );

    return $rs if ($rs != 0);

    #
    # Let's construct nedded tags and entries;
    #

    my %tag_hash = (
                    '{DMN_NAME}' => $base_vhost,
                    '{DB_DIR}' => $named_db_dir
                   );

    my ($entry_b_val, $entry_e_val, $entry_val) = ('', '', '');

    (
     $rs,
     $entry_b_val,
     $entry_e_val,
     $entry_val
    ) = prep_tpl(
                 \%tag_hash,
                 $entry_b,
                 $entry_e,
                 $entry
                );

    return $rs if ($rs != 0);

    #
    # Let's get Sytem and Workind config files;
    #

    my ($sys, $working) = ('', '');

    ($rs, $sys) = get_file($sys_cfg);
    return $rs  if ($rs != 0);

    ($rs, $working) = get_file($working_cfg);
    return $rs  if ($rs != 0);

    ($rs, $rdata) = get_tag($dta_b, $dta_e, $working);
    return $rs if ($rs != 0);

    #
    # Is the new domain entry exists ?
    #

    ($rs, $rdata) = get_tag($entry_b_val, $entry_e_val, $working);

    if ($rs == 0) {
        # Yes it exists ! Then we must delete it !
        ($rs, $working) = del_tag($entry_b_val, "$entry_e_val\n", $working);
        return $rs if ($rs != 0);
    }

    ($rs, $rdata) = get_tag($entry_b, $entry_e, $working);
    return $rs if ($rs != 0);

    #
    # Let's contruct the replacement and do it;
    #

    my $entry_repl = "$entry_b_val$entry_val$entry_e_val\n$entry_b$entry_e";

    ($rs, $working) = repl_tag($entry_b, $entry_e, $working, $entry_repl, "add_named_cfg_data");
    return $rs if ($rs != 0);

    #
    # Here we'll backup production config file;
    #

    $rs = sys_command("cp -p $sys_cfg $backup_cfg");
    return $rs if ($rs != 0);

    #
    # Let's save working copy;
    #

    $rs = store_file($working_cfg, $working, $main::cfg{'ROOT_USER'}, $main::cfg{'ROOT_GROUP'}, 0644);
    return $rs if ($rs != 0);

    #
    # Here we'll replace data in production config file with data in working
    # confing file. A little workaround will be done. If working copy data does not exist
    # in production config then we will add it;
    #

    ($rs, $rdata) = get_tag($dta_b, $dta_e, $sys);

    if ($rs == 0) { # YES ! Data is here ! /in production config file/;
        ($rs, $sys) = repl_tag($dta_b, $dta_e, $sys, $working, "add_named_cfg_data");
        return $rs if ($rs != 0);
    }
	elsif ($rs == -5) {
        $sys .= $working;
    }
	else {
        return $rs;
    }

    $rs = store_file($sys_cfg, $sys, $main::cfg{'ROOT_USER'}, $main::cfg{'ROOT_GROUP'}, 0644);
    return $rs if ($rs != 0);

    push_el(\@main::el, 'add_named_cfg_data()', 'Ending...');
    return 0;
}

#
# Add DNS DB Data
#

sub add_named_db_data {
    push_el(\@main::el, 'add_named_db_data()', 'Starting...');

    my ($base_ip, $base_vhost) = @_;
    my ($rs, $rdata) = (undef, undef);
    if (!defined($base_vhost) || $base_vhost eq '') {
        push_el(\@main::el, 'add_named_db_data()', 'ERROR: Undefined Input Data...');
        return -1;
    }

    #
    # Initial data we need;
    #

    my $conf_dir = $main::cfg{'CONF_DIR'};
    my $named_db_dir = $main::cfg{'BIND_DB_DIR'};
    my $sec_dns_ip = $main::cfg{'SECONDARY_DNS'};

    #
    # Any secondary DNS defined;
    #

	if (!$sec_dns_ip) {
		$sec_dns_ip = $base_ip;
	}

    my $tpl_dir = "$conf_dir/bind/parts";
    my $backup_dir = "$conf_dir/bind/backup";
    my $working_dir = "$conf_dir/bind/working";

    my $db_fname = "$base_vhost.db";

    my $sys_cfg = "$named_db_dir/$db_fname";
    my $working_cfg = "$working_dir/$db_fname";

    #
    # Let's get needed tags and templates;
    #

    my ($entry, $dns2_b, $dns2_e) = ('', '', '');

    ($rs, $entry, $dns2_b, $dns2_e) = get_tpl(
                                              $tpl_dir,
                                              'db_master_e.tpl',
                                              'db_dns2_b.tpl',
                                              'db_dns2_e.tpl'
                                             );

    return $rs if ($rs != 0);

    my $seq = 0;

	#
	# RFC 1912
	#

	my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
	my $time2 = sprintf "%4d%02d%02d00",$year+1900,$mon+1,$mday,$seq;

    #
    # Let's prepare them;
    #

    my %tag_hash = (
                    '{DMN_NAME}' => $base_vhost,
                    '{DMN_IP}' => $base_ip,
                    '{BASE_SERVER_IP}' => $base_ip,
                    '{SECONDARY_DNS_IP}' => $sec_dns_ip,
                    '{TIMESTAMP}' => $time2
                   );

    ($rs, $entry, $dns2_b, $dns2_e) = prep_tpl(
                                               \%tag_hash,
                                               $entry,
                                               $dns2_b,
                                               $dns2_e
                                              );

    return $rs if ($rs != 0);

    #
    # Let's store generated data;
    #

    $rs = store_file($working_cfg, $entry, $main::cfg{'ROOT_USER'}, $main::cfg{'ROOT_GROUP'}, 0644);
    return $rs if ($rs != 0);

    $rs = store_file($sys_cfg, $entry, $main::cfg{'ROOT_USER'}, $main::cfg{'ROOT_GROUP'}, 0644);
    return $rs if ($rs != 0);

    push_el(\@main::el, 'add_named_db_data()', 'Ending...');
    return 0;
}

#
# Create PHP directory for Master User (FastCGI)
#

sub setup_php_master_user_dirs {
	push_el(\@main::el, 'setup_php_master_user_dirs()', 'Starting...');

	my ($rs, $rdata) = (undef, undef);
	my $starter_dir = $main::cfg{'PHP_STARTER_DIR'};

	# Create php4 directory for Master User
	$rs = make_dir("$starter_dir/master/php4", $main::cfg{'ROOT_USER'}, $main::cfg{'ROOT_GROUP'}, 0755);
	return $rs if ($rs != 0);

	# Create php5 directory for Master User
	$rs = make_dir("$starter_dir/master/php5", $main::cfg{'ROOT_USER'}, $main::cfg{'ROOT_GROUP'}, 0755);
	return $rs if ($rs != 0);

	push_el(\@main::el, 'setup_php_master_user_dirs()', 'Ending...');
	return 0;
}

sub setup_php {

	my ($rs, $rdata, $cmd, $cfg_tpl, $cfg) = (undef, undef, undef, undef, undef);

	push_el(\@main::el, 'setup_php()', 'Starting...');

	#
	# Configure the fastcgi_ispcp.conf
	#

	my $cfg_dir = "$main::cfg{'CONF_DIR'}/apache";

	my $bk_dir = "$cfg_dir/backup";

	($rs, $cfg_tpl) = get_tpl("$cfg_dir/working", 'fastcgi_ispcp.conf');
	return $rs if ($rs != 0);

	my %tag_hash = (
					'{APACHE_SUEXEC_MIN_UID}' => $main::cfg{'APACHE_SUEXEC_MIN_UID'},
					'{APACHE_SUEXEC_MIN_GID}' => $main::cfg{'APACHE_SUEXEC_MIN_GID'},
					'{APACHE_SUEXEC_USER_PREF}' => $main::cfg{'APACHE_SUEXEC_USER_PREF'},
					'{PHP_STARTER_DIR}' => $main::cfg{'PHP_STARTER_DIR'},
					'{PHP_VERSION}' => $main::cfg{'PHP_VERSION'}
					);

	($rs, $cfg) = prep_tpl(\%tag_hash, $cfg_tpl);
	return $rs if ($rs != 0);

	$rs = store_file("$bk_dir/fastcgi_ispcp.conf.ispcp", $cfg, $main::cfg{'ROOT_USER'}, $main::cfg{'ROOT_GROUP'}, 0644);
	return $rs if ($rs != 0);

	$cmd = "$main::cfg{'CMD_CP'} -p $bk_dir/fastcgi_ispcp.conf.ispcp $main::cfg{'APACHE_MODS_DIR'}/fastcgi_ispcp.conf";
	$rs = sys_command($cmd);
	return $rs if ($rs != 0);

	if ( -e "$main::cfg{'APACHE_MODS_DIR'}/fastcgi.load" && ! -e "$main::cfg{'APACHE_MODS_DIR'}/fastcgi_ispcp.load") {
            $cmd = "$main::cfg{'CMD_CP'} -p $main::cfg{'APACHE_MODS_DIR'}/fastcgi.load $main::cfg{'APACHE_MODS_DIR'}/fastcgi_ispcp.load";
            $rs = sys_command($cmd);
            return $rs if ($rs != 0);

            ($rs, $rdata) = get_file("$main::cfg{'APACHE_MODS_DIR'}/fastcgi_ispcp.load");
            return $rs if ($rs != 0);

            $rdata = "<IfModule !mod_fastcgi.c>\n" . $rdata . "</IfModule>\n";
            $rs = save_file("$main::cfg{'APACHE_MODS_DIR'}/fastcgi_ispcp.load", $rdata);
            return $rs if ($rs != 0);
	}

	#
	# Copy the Master starter dir
	#

	$cfg_dir = "$main::cfg{'CONF_DIR'}/fcgi";

	$bk_dir = "$cfg_dir/backup";

	#my $tpl_dir = "$cfg_dir/parts";
	my $tpl_dir = "$cfg_dir/parts/master";


	## PHP4 Starter
	($rs, $cfg_tpl) = get_tpl($tpl_dir, 'php4-fcgi-starter.tpl');
	return $rs if ($rs != 0);

	%tag_hash = (
					'{PHP_STARTER_DIR}' => $main::cfg{'PHP_STARTER_DIR'},
					'{PHP4_FASTCGI_BIN}' => $main::cfg{'PHP4_FASTCGI_BIN'},
					'{DMN_NAME}' => "master"
					);

	($rs, $cfg) = prep_tpl(\%tag_hash, $cfg_tpl);
	return $rs if ($rs != 0);

	$rs = store_file("$bk_dir/php4-fcgi-starter.ispcp", $cfg, "$main::cfg{'APACHE_SUEXEC_USER_PREF'}"."$main::cfg{'APACHE_SUEXEC_MIN_UID'}", "$main::cfg{'APACHE_SUEXEC_USER_PREF'}". "$main::cfg{'APACHE_SUEXEC_MIN_GID'}", 0755);
	return $rs if ($rs != 0);

	$cmd = "$main::cfg{'CMD_CP'} -p $bk_dir/php4-fcgi-starter.ispcp $main::cfg{'PHP_STARTER_DIR'}/master/php4-fcgi-starter";
	$rs = sys_command($cmd);
	return $rs if ($rs != 0);

	## PHP5 Starter
	($rs, $cfg_tpl) = get_tpl($tpl_dir, 'php5-fcgi-starter.tpl');
	return $rs if ($rs != 0);

	%tag_hash = (
					'{PHP_STARTER_DIR}' => $main::cfg{'PHP_STARTER_DIR'},
					'{PHP5_FASTCGI_BIN}' => $main::cfg{'PHP5_FASTCGI_BIN'},
					'{DMN_NAME}' => "master"
					);

	($rs, $cfg) = prep_tpl(\%tag_hash, $cfg_tpl);
	return $rs if ($rs != 0);

	$rs = store_file("$bk_dir/php5-fcgi-starter.ispcp", $cfg, "$main::cfg{'APACHE_SUEXEC_USER_PREF'}"."$main::cfg{'APACHE_SUEXEC_MIN_UID'}", "$main::cfg{'APACHE_SUEXEC_USER_PREF'}"."$main::cfg{'APACHE_SUEXEC_MIN_GID'}", 0755);
	return $rs if ($rs != 0);

	$cmd = "$main::cfg{'CMD_CP'} -p $bk_dir/php5-fcgi-starter.ispcp $main::cfg{'PHP_STARTER_DIR'}/master/php5-fcgi-starter";
	$rs = sys_command($cmd);
	return $rs if ($rs != 0);

	## php4.ini
	($rs, $cfg_tpl) = get_tpl($tpl_dir, '/php4/php.ini');
	return $rs if ($rs != 0);

	my $other_rk_log = $main::cfg{'OTHER_ROOTKIT_LOG'};

	if ( $other_rk_log ne '' ) {
	   $other_rk_log = ':' .  $other_rk_log;
	}

	%tag_hash = (
					'{WWW_DIR}' => $main::cfg{'ROOT_DIR'},
					'{DMN_NAME}' => "gui",
					'{MAIL_DMN}' => $main::cfg{'BASE_SERVER_VHOST'},
					'{CONF_DIR}' => $main::cfg{'CONF_DIR'},
					'{MR_LOCK_FILE}' => $main::cfg{'MR_LOCK_FILE'},
					'{PEAR_DIR}' => $main::cfg{'PEAR_DIR'},
					'{RKHUNTER_LOG}' => $main::cfg{'RKHUNTER_LOG'},
					'{CHKROOTKIT_LOG}' => $main::cfg{'CHKROOTKIT_LOG'},
					'{OTHER_ROOTKIT_LOG}' => $other_rk_log
					);

	($rs, $cfg) = prep_tpl(\%tag_hash, $cfg_tpl);
	return $rs if ($rs != 0);

    $rs = store_file("$main::cfg{'PHP_STARTER_DIR'}/master/php4/php.ini", $cfg, "$main::cfg{'APACHE_SUEXEC_USER_PREF'}"."$main::cfg{'APACHE_SUEXEC_MIN_UID'}", "$main::cfg{'APACHE_SUEXEC_USER_PREF'}"."$main::cfg{'APACHE_SUEXEC_MIN_GID'}", 0644);

	return $rs if ($rs != 0);

	## php5.ini
	($rs, $cfg_tpl) = get_tpl($tpl_dir, '/php5/php.ini');
	return $rs if ($rs != 0);

	%tag_hash = (
					'{WWW_DIR}' => $main::cfg{'ROOT_DIR'},
					'{DMN_NAME}' => "gui",
					'{MAIL_DMN}' => $main::cfg{'BASE_SERVER_VHOST'},
					'{CONF_DIR}' => $main::cfg{'CONF_DIR'},
					'{MR_LOCK_FILE}' => $main::cfg{'MR_LOCK_FILE'},
					'{PEAR_DIR}' => $main::cfg{'PEAR_DIR'},
					'{RKHUNTER_LOG}' => $main::cfg{'RKHUNTER_LOG'},
					'{CHKROOTKIT_LOG}' => $main::cfg{'CHKROOTKIT_LOG'},
					'{OTHER_ROOTKIT_LOG}' => $other_rk_log
					);

	($rs, $cfg) = prep_tpl(\%tag_hash, $cfg_tpl);
	return $rs if ($rs != 0);

	$rs = store_file("$main::cfg{'PHP_STARTER_DIR'}/master/php5/php.ini", $cfg, "$main::cfg{'APACHE_SUEXEC_USER_PREF'}"."$main::cfg{'APACHE_SUEXEC_MIN_UID'}", "$main::cfg{'APACHE_SUEXEC_USER_PREF'}"."$main::cfg{'APACHE_SUEXEC_MIN_GID'}", 0644);

	return $rs if ($rs != 0);

	push_el(\@main::el, 'setup_php()', 'Ending...');
	return 0;
}

# Set up Apache2
sub setup_httpd {
	push_el(\@main::el, 'setup_httpd()', 'Starting...');

	my ($rs, $rdata) = (undef, undef);
	my $cfg_dir = "$main::cfg{'CONF_DIR'}/apache";
	my $bk_dir = "$cfg_dir/backup";
	my $wrk_dir = "$cfg_dir/working";
	my ($cfg_tpl, $cfg, $cmd) = (undef, undef, undef);
	my %tag_hash = ();

	if ($main::cfg{'CMD_HTTPD'} ne 'no') {
		sys_command_rs("$main::cfg{'CMD_HTTPD'} stop &> /tmp/ispcp-setup-services.log");
	}

	#
	# Apache Master file
	#

	($rs, $cfg_tpl) = get_file("$cfg_dir/00_master.conf");
	return $rs if ($rs != 0);

	%tag_hash = (
					'{BASE_SERVER_IP}' 			=> $main::cfg{'BASE_SERVER_IP'},
					'{BASE_SERVER_VHOST}' 		=> $main::cfg{'BASE_SERVER_VHOST'},
					'{DEFAULT_ADMIN_ADDRESS}' 	=> $main::cfg{'DEFAULT_ADMIN_ADDRESS'},
					'{ROOT_DIR}' 				=> $main::cfg{'ROOT_DIR'},
					'{APACHE_WWW_DIR}'      	=> $main::cfg{'APACHE_WWW_DIR'},
					'{APACHE_USERS_LOG_DIR}' 	=> $main::cfg{'APACHE_USERS_LOG_DIR'},
					'{APACHE_LOG_DIR}' 			=> $main::cfg{'APACHE_LOG_DIR'},
					'{PHP_STARTER_DIR}' 		=> $main::cfg{'PHP_STARTER_DIR'},
					'{PHP_VERSION}'				=> $main::cfg{'PHP_VERSION'},
					'{WWW_DIR}'					=> $main::cfg{'ROOT_DIR'},
					'{DMN_NAME}'				=> 'gui',
					'{CONF_DIR}'				=> $main::cfg{'CONF_DIR'},
					'{MR_LOCK_FILE}'			=> $main::cfg{'MR_LOCK_FILE'},
					'{RKHUNTER_LOG}'			=> $main::cfg{'RKHUNTER_LOG'},
					'{CHKROOTKIT_LOG}'			=> $main::cfg{'CHKROOTKIT_LOG'},
					'{PEAR_DIR}'				=> $main::cfg{'PEAR_DIR'},
					'{OTHER_ROOTKIT_LOG}'		=> $main::cfg{'OTHER_ROOTKIT_LOG'}
					);

	($rs, $cfg) = prep_tpl(\%tag_hash, $cfg_tpl);
	return $rs if ($rs != 0);

	$rs = store_file("$main::cfg{'APACHE_SITES_DIR'}/00_master.conf", $cfg, $main::cfg{'ROOT_USER'}, $main::cfg{'ROOT_GROUP'}, 0644);
	return $rs if ($rs != 0);

	($rs, $cfg_tpl) = get_file("$cfg_dir/01_awstats.conf");
	return $rs if ($rs != 0);

	%tag_hash = (
					'{AWSTATS_ENGINE_DIR}' 		=> $main::cfg{'AWSTATS_ENGINE_DIR'},
					'{AWSTATS_WEB_DIR}' 		=> $main::cfg{'AWSTATS_WEB_DIR'}
					);

	($rs, $cfg) = prep_tpl(\%tag_hash, $cfg_tpl);
	return $rs if ($rs != 0);

	$rs = store_file("$main::cfg{'APACHE_SITES_DIR'}/01_awstats.conf", $cfg, $main::cfg{'ROOT_USER'}, $main::cfg{'ROOT_GROUP'}, 0644);
	return $rs if ($rs != 0);

	$rs = setfmode("$main::cfg{'APACHE_SITES_DIR'}/01_awstats.conf", $main::cfg{'ROOT_USER'}, $main::cfg{'ROOT_GROUP'}, 0644);
	return $rs if ($rs != 0);

    $rs = add_named_cfg_data($main::cfg{'BASE_SERVER_VHOST'});
    return $rs if ($rs != 0);

    $rs = add_named_db_data($main::cfg{'BASE_SERVER_IP'}, $main::cfg{'BASE_SERVER_VHOST'});
    return $rs if ($rs != 0);

	#
	# Default vhost file
	#

	($rs, $cfg_tpl) = get_file("$cfg_dir/httpd.conf");
	return $rs if ($rs != 0);

	%tag_hash = (
					'{HOST_IP}' => $main::cfg{'BASE_SERVER_IP'}
					);

	($rs, $cfg) = prep_tpl(\%tag_hash, $cfg_tpl);
	return $rs if ($rs != 0);

	$rs = store_file("$main::cfg{'APACHE_SITES_DIR'}/ispcp.conf", $cfg, $main::cfg{'ROOT_USER'}, $main::cfg{'ROOT_GROUP'}, 0644);
	return $rs if ($rs != 0);

	#
	# Enable sites
	#

	if ( -e "/usr/sbin/a2ensite" ) {

		sys_command_rs("/usr/sbin/a2ensite ispcp.conf &> /tmp/ispcp-setup-services.log");
		sys_command_rs("/usr/sbin/a2ensite 00_master.conf &> /tmp/ispcp-setup-services.log");

		if ($main::cfg{'AWSTATS_ACTIVE'} eq 'yes' && $main::cfg{'AWSTATS_MODE'} eq 0) {

			sys_command_rs("/usr/sbin/a2ensite 01_awstats.conf &> /tmp/ispcp-setup-services.log");
			$cmd = "$main::cfg{'CMD_CP'} -p $main::cfg{'APACHE_MODS_DIR'}/proxy.conf $bk_dir/proxy.conf";
			$rs = sys_command($cmd);

			#
			# Replace the allowed hosts in mod_proxy
			#

			($rs, $rdata) = get_file("$main::cfg{'APACHE_MODS_DIR'}/proxy.conf");
			return $rs if ($rs != 0);

			$rdata =~ s/#Allow from .example.com/Allow from 127.0.0.1/gi;
			$rs = store_file("$main::cfg{'APACHE_MODS_DIR'}/proxy.conf", $rdata, $main::cfg{'ROOT_USER'}, $main::cfg{'ROOT_GROUP'}, 0644);
			return $rs if ($rs != 0);
		}
	}

	#
	# Disable default site --> 000-default (if not, ispcp does not work)
	#

	if (-e "/usr/sbin/a2dissite") {
		sys_command_rs("/usr/sbin/a2dissite 000-default &> /tmp/ispcp-setup-services.log");
	}

	#
	# start fastcgi, suexec and rewrite mod
	#

	if ( -e "/usr/sbin/a2enmod" ) {
		sys_command_rs("/usr/sbin/a2enmod cgi &> /tmp/ispcp-setup-services.log");
		sys_command_rs("/usr/sbin/a2enmod actions &> /tmp/ispcp-setup-services.log");
		sys_command_rs("/usr/sbin/a2enmod rewrite &> /tmp/ispcp-setup-services.log");
		sys_command_rs("/usr/sbin/a2enmod fastcgi_ispcp &> /tmp/ispcp-setup-services.log");
		sys_command_rs("/usr/sbin/a2enmod suexec &> /tmp/ispcp-setup-services.log");

		if ($main::cfg{'AWSTATS_ACTIVE'} eq 'yes' && $main::cfg{'AWSTATS_MODE'} eq 0) {
			sys_command_rs("/usr/sbin/a2enmod proxy &> /tmp/ispcp-setup-services.log");
			sys_command_rs("/usr/sbin/a2enmod proxy_http &> /tmp/ispcp-setup-services.log");
		}
	}

	#
	# Disable default fastcgi and mod_php4/5, otherwise FastCgiIpcDir is already defined
	#

	if (-e "/usr/sbin/a2dismod") {
		sys_command_rs("/usr/sbin/a2dismod fastcgi &> /tmp/ispcp-setup-services.log");
		sys_command_rs("/usr/sbin/a2dismod php4 &> /tmp/ispcp-setup-services.log");
		sys_command_rs("/usr/sbin/a2dismod php5 &> /tmp/ispcp-setup-services.log");
	}

	#
	# update apache logrotate if AWStats activated
	# (check for apache, apache2)
	#

	my @apache_file = ("apache", "apache2");
	my ($apache_file, $file) = (undef, undef);

	foreach $apache_file (@apache_file) {
		# check if file exists
		if (-f "/etc/logrotate.d/".$apache_file) {
			$file = "/etc/logrotate.d/".$apache_file;
			#print STDOUT "$file \n";

			# get file
			($rs, $rdata) = get_file($file);
			return $rs if ($rs != 0);

			# add code if not exists
			if ($rdata !~ m/awstats_updateall\.pl/i) {
				$rdata =~ s/sharedscripts/sharedscripts\n\tprerotate\n\t\t$main::cfg{'AWSTATS_ROOT_DIR'}\/awstats_updateall.pl now -awstatsprog=$main::cfg{'AWSTATS_ENGINE_DIR'}\/awstats.pl &> \/dev\/null\n\tendscript/gi;
				$rs = store_file($file, $rdata, $main::cfg{'ROOT_USER'}, $main::cfg{'ROOT_GROUP'}, 0644);
				return $rs if ($rs != 0);
			}
		}
	}


	#
	# manage some permission;
	#

	my $gui_root_dir = "$main::cfg{'ROOT_DIR'}/gui";

	$cmd = "$main::cfg{'CMD_CHOWN'} -R $main::cfg{'APACHE_USER'}:$main::cfg{'APACHE_GROUP'} $gui_root_dir";
	$rs = sys_command($cmd);
	return $rs if ($rs != 0);

	sleep(5);

	if ($main::cfg{'CMD_HTTPD'} ne 'no') {
		sys_command_rs("$main::cfg{'CMD_HTTPD'} start &> /tmp/ispcp-setup-services.log");
	}

	push_el(\@main::el, 'setup_httpd()', 'Ending...');
	return 0;
}

# Set up Postfix
sub setup_mta {
	push_el(\@main::el, 'setup_mta()', 'Starting...');

	my ($rs, $rdata) = (undef, undef);
	my $cfg_dir = "$main::cfg{'CONF_DIR'}/postfix";
	my $bk_dir = "$cfg_dir/backup";
	my $wrk_dir = "$cfg_dir/working";
	my $vrl_dir = "$cfg_dir/ispcp";
	my ($cfg_tpl, $cfg, $cmd) = (undef, undef, undef);

	if ($main::cfg{'CMD_MTA'} ne 'no') {
		sys_command_rs("$main::cfg{'CMD_MTA'} stop &> /tmp/ispcp-setup-services.log");
	}

	if (! -e "$bk_dir/main.cf.ispcp") {
            if ( -e "$main::cfg{'POSTFIX_CONF_FILE'}") {
                    $cmd = "$main::cfg{'CMD_CP'} -p $main::cfg{'POSTFIX_CONF_FILE'} $bk_dir/main.cf.system";
                    $rs = sys_command($cmd);
                    return $rs if ($rs != 0);
            }
            if ( -e "$main::cfg{'POSTFIX_MASTER_CONF_FILE'}") {
                    $cmd = "$main::cfg{'CMD_CP'} -p $main::cfg{'POSTFIX_MASTER_CONF_FILE'} $bk_dir/master.cf.system";
                    $rs = sys_command($cmd);
                    return $rs if ($rs != 0);
            }

            ($rs, $cfg_tpl) = get_tpl($cfg_dir, 'main.cf');
            return $rs if ($rs != 0);

            my %tag_hash = (
                            '{MTA_HOSTNAME}' => $main::cfg{'SERVER_HOSTNAME'},
                            '{MTA_LOCAL_DOMAIN}' => "$main::cfg{'SERVER_HOSTNAME'}.local",
                            '{MTA_VERSION}' => $main::cfg{'Version'},
                            '{MTA_TRANSPORT_HASH}' => $main::cfg{'MTA_TRANSPORT_HASH'},
                            '{MTA_LOCAL_MAIL_DIR}' => $main::cfg{'MTA_LOCAL_MAIL_DIR'},
                            '{MTA_LOCAL_ALIAS_HASH}' => $main::cfg{'MTA_LOCAL_ALIAS_HASH'},
                            '{MTA_VIRTUAL_MAIL_DIR}' => $main::cfg{'MTA_VIRTUAL_MAIL_DIR'},
                            '{MTA_VIRTUAL_DMN_HASH}' => $main::cfg{'MTA_VIRTUAL_DMN_HASH'},
                            '{MTA_VIRTUAL_MAILBOX_HASH}' => $main::cfg{'MTA_VIRTUAL_MAILBOX_HASH'},
                            '{MTA_VIRTUAL_ALIAS_HASH}' => $main::cfg{'MTA_VIRTUAL_ALIAS_HASH'},
                            '{MTA_MAILBOX_MIN_UID}' => $main::cfg{'MTA_MAILBOX_MIN_UID'},
                            '{MTA_MAILBOX_UID}' => $main::cfg{'MTA_MAILBOX_UID'},
                            '{MTA_MAILBOX_GID}' => $main::cfg{'MTA_MAILBOX_GID'}
                           );

                ($rs, $cfg) = prep_tpl(\%tag_hash, $cfg_tpl);
                return $rs if ($rs != 0);

                $rs = store_file("$bk_dir/main.cf.ispcp", $cfg, $main::cfg{'ROOT_USER'}, $main::cfg{'ROOT_GROUP'}, 0644);
                return $rs if ($rs != 0);

                $cmd = "$main::cfg{'CMD_CP'} -p $cfg_dir/master.cf $bk_dir/master.cf.ispcp";
                $rs = sys_command($cmd);
                return $rs if ($rs != 0);
	}


	$cmd = "$main::cfg{'CMD_CP'} -p $bk_dir/main.cf.ispcp $main::cfg{'POSTFIX_CONF_FILE'}";
	$rs = sys_command($cmd);
	return $rs if ($rs != 0);

	$cmd = "$main::cfg{'CMD_CP'} -p $bk_dir/master.cf.ispcp $main::cfg{'POSTFIX_MASTER_CONF_FILE'}";
	$rs = sys_command($cmd);
	return $rs if ($rs != 0);

	$rs = setfmode("$main::cfg{'ROOT_DIR'}/engine/messager/ispcp-arpl-msgr", $main::cfg{'MTA_MAILBOX_UID_NAME'}, $main::cfg{'MTA_MAILBOX_GID_NAME'}, 0755);
	return $rs if ($rs != 0);

	$cmd = "$main::cfg{'CMD_CP'} -p $vrl_dir/aliases $vrl_dir/domains $vrl_dir/mailboxes $vrl_dir/transport $vrl_dir/sender-access $main::cfg{'MTA_VIRTUAL_CONF_DIR'}";
	$rs = sys_command($cmd);
	return $rs if ($rs != 0);

	$cmd = "$main::cfg{'CMD_CP'} -p $vrl_dir/aliases $vrl_dir/domains $vrl_dir/mailboxes $vrl_dir/transport $vrl_dir/sender-access $wrk_dir";
	$rs = sys_command($cmd);
	return $rs if ($rs != 0);

	$cmd = "$main::cfg{'CMD_POSTMAP'} $main::cfg{'MTA_VIRTUAL_CONF_DIR'}/aliases $main::cfg{'MTA_VIRTUAL_CONF_DIR'}/domains $main::cfg{'MTA_VIRTUAL_CONF_DIR'}/mailboxes $main::cfg{'MTA_VIRTUAL_CONF_DIR'}/transport $main::cfg{'MTA_VIRTUAL_CONF_DIR'}/sender-access &> /tmp/ispcp-setup-services.log";
	$rs = sys_command($cmd);
	return $rs if ($rs != 0);

	$rs = sys_command("$main::cfg{'CMD_NEWALIASES'} &> /tmp/ispcp-setup-services.log");
	return $rs if ($rs != 0);

	if ($main::cfg{'CMD_MTA'} ne 'no') {
		sys_command_rs("$main::cfg{'CMD_MTA'} start &> /tmp/ispcp-setup-services.log");
	}

	push_el(\@main::el, 'setup_mta()', 'Ending...');
	return 0;
}

# Set up Courier
sub setup_po {
	push_el(\@main::el, 'setup_po()', 'Starting...');

	my ($rs, $rdata) = (undef, undef);
	my $cfg_dir = "$main::cfg{'CONF_DIR'}/courier";
	my $bk_dir = "$cfg_dir/backup";
	my $wrk_dir = "$cfg_dir/working";
	my ($cfg_tpl, $cfg, $cmd) = (undef, undef, undef);

	if ($main::cfg{'CMD_AUTHD'} ne 'no') {
		sys_command_rs("$main::cfg{'CMD_AUTHD'} stop &> /tmp/ispcp-setup-services.log");
	}

	if ($main::cfg{'CMD_IMAP'} ne 'no') {
		sys_command_rs("$main::cfg{'CMD_IMAP'} stop &> /tmp/ispcp-setup-services.log");
	}

	if ($main::cfg{'CMD_POP'} ne 'no') {
		sys_command_rs("$main::cfg{'CMD_POP'} stop &> /tmp/ispcp-setup-services.log");
	}

	if (! -e "$bk_dir/authdaemonrc.system") {

		# Let's backup system configs;
		if ( -e $main::cfg{'AUTHLIB_CONF_DIR'} && $main::cfg{'AUTHLIB_CONF_DIR'}) {
			if ( -e "$main::cfg{'AUTHLIB_CONF_DIR'}/authdaemonrc" ) {
				### first make backup, before updating
				$cmd = "$main::cfg{'CMD_CP'} -p $main::cfg{'AUTHLIB_CONF_DIR'}/authdaemonrc $bk_dir/authdaemonrc.system";
				$rs = sys_command($cmd);
				return $rs if ($rs != 0);

				#### Update authdaemonrc
				($rs, $rdata) = get_file("$main::cfg{'AUTHLIB_CONF_DIR'}/authdaemonrc");
				return $rs if ($rs != 0);
				$rdata =~ s/authmodulelist="/authmodulelist="authuserdb /gi;
				$rs = save_file("$main::cfg{'AUTHLIB_CONF_DIR'}/authdaemonrc", $rdata);
				return $rs if ($rs != 0);
			}
		}
		else {
			if ( -e "$main::cfg{'AUTHLIB_CONF_DIR'}/authdaemonrc" ) {
				### first make backup, before updating
				$cmd = "$main::cfg{'CMD_CP'} -p $main::cfg{'AUTHLIB_CONF_DIR'}/authdaemonrc $bk_dir/authdaemonrc.system";
				$rs = sys_command($cmd);
				return $rs if ($rs != 0);

				#### Update authdaemonrc
				($rs, $rdata) = get_file("$main::cfg{'AUTHLIB_CONF_DIR'}/authdaemonrc");
				return $rs if ($rs != 0);

				$rdata =~ s/authmodulelist="/authmodulelist="authuserdb /gi;
				$rs = save_file("$main::cfg{'AUTHLIB_CONF_DIR'}/authdaemonrc", $rdata);
				return $rs if ($rs != 0);
			}
		}

		if (-e "$main::cfg{'AUTHLIB_CONF_DIR'}/userdb") {
			$cmd = "$main::cfg{'CMD_CP'} -p $main::cfg{'AUTHLIB_CONF_DIR'}/userdb $bk_dir/userdb.system";
			$rs = sys_command($cmd);
			return $rs if ($rs != 0);
		}
	}

	$cmd = "$main::cfg{'CMD_CP'} -p $cfg_dir/userdb $wrk_dir";
	$rs = sys_command($cmd);
	return $rs if ($rs != 0);

	$cmd = "$main::cfg{'CMD_CP'} -p $cfg_dir/userdb $main::cfg{'AUTHLIB_CONF_DIR'}";
	$rs = sys_command($cmd);
	return $rs if ($rs != 0);

	if (exists $main::cfg{'AUTHLIB_CONF_DIR'} && $main::cfg{'AUTHLIB_CONF_DIR'}) {
		$rs = setfmode("$main::cfg{'AUTHLIB_CONF_DIR'}/userdb", $main::cfg{'ROOT_USER'}, $main::cfg{'ROOT_GROUP'}, 0600);
	}
	else {
		$rs = setfmode("$main::cfg{'AUTHLIB_CONF_DIR'}/userdb", $main::cfg{'ROOT_USER'}, $main::cfg{'ROOT_GROUP'}, 0600);
	}
	return $rs if ($rs != 0);

	$rs = sys_command($main::cfg{'CMD_MAKEUSERDB'});
	return $rs if ($rs != 0);

	if ($main::cfg{'CMD_AUTHD'} ne 'no') {
		sys_command_rs("$main::cfg{'CMD_AUTHD'} start &> /tmp/ispcp-setup-services.log");
	}

	if ($main::cfg{'CMD_IMAP'} ne 'no') {
		sys_command_rs("$main::cfg{'CMD_IMAP'} start &> /tmp/ispcp-setup-services.log");
	}

	if ($main::cfg{'CMD_POP'} ne 'no') {
		sys_command_rs("$main::cfg{'CMD_POP'} start &> /tmp/ispcp-setup-services.log");
	}

	push_el(\@main::el, 'setup_po()', 'Ending...');
	return 0;
}

# Set up Proftpd
sub setup_ftpd {
	push_el(\@main::el, 'setup_ftpd()', 'Starting...');

	my ($rs, $rdata) = (undef, undef);
	my $cfg_dir = "$main::cfg{'CONF_DIR'}/proftpd";
	my $bk_dir = "$cfg_dir/backup";
	my ($cfg_tpl, $cfg, $cmd) = (undef, undef, undef);

	if ($main::cfg{'CMD_FTPD'} ne 'no') {
		sys_command_rs("$main::cfg{'CMD_FTPD'} stop &> /tmp/ispcp-setup-services.log");
	}

	if (! -e $main::cfg{'FTPD_CONF_FILE'}) {
		$rs = set_conf_val('FTPD_CONF_FILE', '/etc/proftpd/proftpd.conf');
		return $rs if ($rs != 0);

		$rs = store_conf();
		return $rs if ($rs != 0);
	}

	if (! -e "$bk_dir/proftpd.conf.ispcp") {
		$cmd = "$main::cfg{'CMD_CP'} -p $main::cfg{'FTPD_CONF_FILE'} $bk_dir/proftpd.conf.system";
		$rs = sys_command($cmd);
		return $rs if ($rs != 0);

		($rs, $cfg_tpl) = get_tpl($cfg_dir, 'proftpd.conf');
		return $rs if ($rs != 0);

		my %tag_hash = (
						'{HOST_NAME}' => $main::cfg{'SERVER_HOSTNAME'},
						'{DATABASE_NAME}' => $main::db_name,
						'{DATABASE_HOST}' => $main::db_host,
						'{DATABASE_USER}' => $main::ua{'db_ftp_user'},
						'{DATABASE_PASS}' => $main::ua{'db_ftp_password'}
					   );

		($rs, $cfg) = prep_tpl(\%tag_hash, $cfg_tpl);
		return $rs if ($rs != 0);

		$rs = store_file("$bk_dir/proftpd.conf.ispcp", $cfg, $main::cfg{'ROOT_USER'}, $main::cfg{'ROOT_GROUP'}, 0600);
		return $rs if ($rs != 0);
	}

	$cmd = "$main::cfg{'CMD_CP'} -p $bk_dir/proftpd.conf.ispcp $main::cfg{'FTPD_CONF_FILE'}";
	$rs = sys_command($cmd);
	return $rs if ($rs != 0);

	#
	# To fill ftp_traff.log file with something. ;)
	#

	if (! -e "$main::cfg{'TRAFF_LOG_DIR'}/proftpd") {
		$rs = make_dir("$main::cfg{'TRAFF_LOG_DIR'}/proftpd", $main::cfg{'ROOT_USER'}, $main::cfg{'ROOT_GROUP'}, 0755);
		return $rs if ($rs != 0);
	}
	$rs = store_file("$main::cfg{'TRAFF_LOG_DIR'}$main::cfg{'FTP_TRAFF_LOG'}", "\n", $main::cfg{'ROOT_USER'}, $main::cfg{'ROOT_GROUP'}, 0644);
	return $rs if ($rs != 0);

	#
	# Let's start service;
	#

	if ($main::cfg{'CMD_FTPD'} ne 'no') {
		sys_command_rs("$main::cfg{'CMD_FTPD'} start &> /tmp/ispcp-setup-services.log");
	}

	push_el(\@main::el, 'setup_ftpd()', 'Ending...');
	return 0;
}

# Set up ispCP Daemon
sub setup_ispcpd {
	push_el(\@main::el, 'setup_ispcpd()', 'Starting...');

	my ($rs, $rdata) = (undef, undef);

	sys_command_rs("$main::cfg{'CMD_CHOWN'} $main::cfg{'ROOT_USER'}:$main::cfg{'ROOT_GROUP'} $main::cfg{'CMD_ISPCPD'} $main::cfg{'CMD_ISPCPD'} &> /tmp/ispcp-setup-services.log");
	sys_command_rs("$main::cfg{'CMD_CHOWN'} $main::cfg{'ROOT_USER'}:$main::cfg{'ROOT_GROUP'} $main::cfg{'CMD_ISPCPN'} $main::cfg{'CMD_ISPCPN'} &> /tmp/ispcp-setup-services.log");
	sys_command_rs("$main::cfg{'CMD_CHMOD'} 0755 $main::cfg{'CMD_ISPCPD'} $main::cfg{'CMD_ISPCPD'} &> /tmp/ispcp-setup-services.log");
	sys_command_rs("$main::cfg{'CMD_CHMOD'} 0755 $main::cfg{'CMD_ISPCPN'} $main::cfg{'CMD_ISPCPN'} &> /tmp/ispcp-setup-services.log");

	if ( -x "/usr/sbin/update-rc.d" ) {
		sys_command_rs("/usr/sbin/update-rc.d ispcp_daemon defaults 99 &> /tmp/ispcp-setup-services.log");
		sys_command_rs("/usr/sbin/update-rc.d ispcp_network defaults 99 &> /tmp/ispcp-setup-services.log");
	}
	elsif ( -x "/usr/lib/lsb/install_initd" ) { #LSB 3.1 Core section 20.4 compatibility
		sys_command_rs("/usr/lib/lsb/install_initd $main::cfg{'CMD_ISPCPD'} &> /tmp/ispcp-setup-services.log");
		sys_command_rs("/usr/lib/lsb/install_initd $main::cfg{'CMD_ISPCPN'} &> /tmp/ispcp-setup-services.log");
    }

    if ( ! -e "/etc/init.d/vhcs2_daemon" ) {
        sys_command_rs("$main::cfg{'CMD_ISPCPD'} start &> /tmp/ispcp-setup-services.log");
        sys_command_rs("$main::cfg{'CMD_ISPCPN'} start &> /tmp/ispcp-setup-services.log");

    }

	$rs = del_file("/tmp/ispcp-setup-services.log");
	return $rs if ($rs != 0);

	push_el(\@main::el, 'setup_ispcpd()', 'Ending...');
	return 0;
}

########################################################################

return 1;