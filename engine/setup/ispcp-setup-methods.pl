#!/usr/bin/perl

# ispCP ω (OMEGA) a Virtual Hosting Control Panel
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
# The Original Code is "ispCP ω (OMEGA) a Virtual Hosting Control Panel".
#
# The Initial Developer of the Original Code is ispCP Team.
# Portions created by Initial Developer are Copyright (C) 2006-2010 by
# isp Control Panel. All Rights Reserved.
#
# The ispCP ω Home Page is:
#
#    http://isp-control.net
#

use strict;
use warnings;

#
## Ask subroutines - Begin
#
sub ask_hostname {

	push_el(\@main::el, 'ask_hostname()', 'Starting...');

	my ($rs, $rdata) = (undef, undef);

	my $hostname = undef;

	($rs, $hostname) = get_sys_hostname();
	return $rs if ($rs != 0);

	my $qmsg = "\n\tPlease enter a fully qualified hostname. [$hostname]: ";
	print STDOUT $qmsg;

	chomp($rdata = readline \*STDIN);

	if (!defined($rdata) || $rdata eq '') {
		$rdata = $hostname;
	}

	if ($rdata =~ /^(((([\w][\w-]{0,253}){0,1}[\w])\.)*)([\w][\w-]{0,253}[\w])\.([a-zA-Z]{2,6})$/) {

		if ($rdata =~ /^([\w][\w-]{0,253}[\w])\.([a-zA-Z]{2,6})$/) {

			my $wmsg = colored(
				['bold yellow'], "\tWARNING:") .
				 " $rdata is not a \"fully qualified hostname\". " .
				 "Be aware you cannot use this domain for websites.";

			print STDOUT $wmsg;
		}

		$main::ua{'hostname'} = $rdata;
		$main::ua{'hostname_local'} = ( ($1) ? $1 : $4);
		$main::ua{'hostname_local'} =~ s/^([^.]+).+$/$1/;

	} else {

		print STDOUT colored(['bold red'], "\n\tERROR:") .
			" Hostname is not a valid domain name!\n";

		return 1;
	}

	push_el(\@main::el, 'ask_hostname()', 'Ending...');

	0;
}

sub ask_eth {

	push_el(\@main::el, 'ask_eth()', 'Starting...');

	my ($rs, $rdata, $warn_msg) = (undef, undef, '');

	# TODO: Replace ifconfig, grep, awk with paths in ispcp.conf
	my $cmd =
		"/sbin/ifconfig |grep -v inet6|grep inet|grep -v 127.0.0.1|awk ' " .
		"{print \$2}'|head -n 1|awk -F: '{print \$NF}' 1>/tmp/ispcp-setup.ip";

	# FIXME: No error correction, if /tmp/ispcp-setup.ip not readable
	$rs = sys_command($cmd);

	unless(!$rs) {
		$warn_msg = colored(
			['bold red'], "\n\tERROR: ") .
			'External command $cmd returned an error status on eth lookup!'."\n";
		return ($rs, $warn_msg);
	}

	($rs, $rdata) = get_file('/tmp/ispcp-setup.ip');

	unless(!$rs){
		$warn_msg = colored(['bold red'], "\n\tERROR: ") .
			'Unable to get the file /tmp/ispcp-setup.ip!'. "\n";
		return ($rs, $warn_msg);
	}

	chop($rdata);

	$rs = del_file('/tmp/ispcp-setup.ip');

	unless(!$rs) {
		$warn_msg = colored(['bold red'], "\n\tERROR: ") .
			'Unable to delete /tmp/ispcp-setup.ip'. "\n";
		return ($rs, $warn_msg);
	}

	my $eth = $rdata;
	my $qmsg = "\n\tPlease enter system network address. [$eth]: ";
	print STDOUT $qmsg;

	chomp($rdata = readline \*STDIN);

	if (!defined($rdata) || $rdata eq '') {
		$main::ua{'eth_ip'} = $eth;
	} else {
		$main::ua{'eth_ip'} = $rdata;
	}

	return 1 if(check_eth($main::ua{'eth_ip'}));

	push_el(\@main::el, 'ask_eth()', 'Ending...');

	return (0, '');
}

sub ask_db_host {

	push_el(\@main::el, 'ask_db_host()', 'Starting...');

	my ($rs, $rdata) = (undef, undef);
	my $db_host = 'localhost';
	my $qmsg = "\n\tPlease enter SQL server host. [$db_host]: ";

	print STDOUT $qmsg;

	chomp($rdata = readline \*STDIN);

	if (!defined($rdata) || $rdata eq '') {
		$main::ua{'db_host'} = $db_host;
	} else {
		$main::ua{'db_host'} = $rdata;
	}

	push_el(\@main::el, 'ask_db_host()', 'Ending...');

	0;
}

sub ask_db_name {

	push_el(\@main::el, 'ask_db_name()', 'Starting...');

	my ($rs, $rdata) = (undef, undef);
	my $db_name = 'ispcp';
	my $qmsg = "\n\tPlease enter system SQL database. [$db_name]: ";

	print STDOUT $qmsg;

	chomp($rdata = readline \*STDIN);

	if (!defined($rdata) || $rdata eq '') {
		$main::ua{'db_name'} = $db_name;
	} else {
		$main::ua{'db_name'} = $rdata;
	}

	push_el(\@main::el, 'ask_db_name()', 'Ending...');

	0;
}

sub ask_db_user {

	push_el(\@main::el, 'ask_db_user()', 'Starting...');

	my ($rs, $rdata) = (undef, undef);
	my $db_user = 'root';
	my $qmsg = "\n\tPlease enter system SQL user. [$db_user]: ";

	print STDOUT $qmsg;

	chomp($rdata = readline \*STDIN);

	if (!defined($rdata) || $rdata eq '') {
		$main::ua{'db_user'} = $db_user;
	} else {
		$main::ua{'db_user'} = $rdata;
	}

	push_el(\@main::el, 'ask_db_user()', 'Ending...');

	0;
}

sub ask_db_password {

	push_el(\@main::el, 'ask_db_password()', 'Starting...');

	my ($rs, $pass1, $pass2) = (undef, undef, undef);
	my $db_password = 'none';
	my $qmsg = "\n\tPlease enter system SQL password. [$db_password]: ";

	$pass1 = read_password($qmsg);

	if (!defined($pass1) || $pass1 eq '') {

		$main::ua{'db_password'} = '';

	} else {

		$qmsg = "\tPlease repeat system SQL password: ";
		$pass2 = read_password($qmsg);

		if ($pass1 eq $pass2) {

			$main::ua{'db_password'} = $pass1;

		} else {

			print STDOUT colored(['bold red'], "\n\tERROR: ") .
				'Passwords do not match!';

			return 1;
		}
	}

	push_el(\@main::el, 'ask_db_password()', 'Ending...');

	0;
}

sub ask_db_ftp_user {

	push_el(\@main::el, 'ask_db_ftp_user()', 'Starting...');

	my ($rs, $rdata) = (undef, undef);
	my $db_user = 'vftp';
	my $qmsg = "\n\tPlease enter ispCP ftp SQL user. [$db_user]: ";

	print STDOUT $qmsg;

	chomp($rdata = readline \*STDIN);

	if (!defined($rdata) || $rdata eq '') {

		$main::ua{'db_ftp_user'} = $db_user;

	} elsif( $rdata eq $main::ua{'db_user'}) {

		$qmsg = colored(['bold red'], "\n\tERROR: ") .
			'Ftp SQL user must not be identical to system SQL user!';

		print STDOUT $qmsg;

		return 1;

	} else {
		$main::ua{'db_ftp_user'} = $rdata;
	}

	push_el(\@main::el, 'ask_db_ftp_user()', 'Ending...');

	0;
}

sub ask_db_ftp_password {

	push_el(\@main::el, 'ask_db_ftp_password()', 'Starting...');

	my ($rs, $pass1, $pass2) = (undef, undef, undef);

	my $db_password = undef;

	my $qmsg = "\n\tPlease enter ispCP ftp SQL user password. [auto generate]: ";

	$pass1 = read_password($qmsg);

	if (!defined($pass1) || $pass1 eq '') {

		$db_password = gen_sys_rand_num(18);
		$db_password =~ s/('|"|`|#|;)//g;
		$main::ua{'db_ftp_password'} = $db_password;

		print STDOUT "\tispCP ftp SQL user password set to: $db_password\n";

	} else {

		$qmsg = "\tPlease repeat ispCP ftp SQL user password: ";
		$pass2 = read_password($qmsg);

		if ($pass1 eq $pass2) {

			$main::ua{'db_ftp_password'} = $pass1;

		} else {

			print STDOUT colored(['bold red'], "\n\tERROR: ") .
				'Passwords do not match!';

			return 1;
		}
	}

	push_el(\@main::el, 'ask_db_ftp_password()', 'Ending...');

	0;
}

sub ask_admin {

	push_el(\@main::el, 'ask_admin()', 'Starting...');

	my ($rs, $rdata) = (undef, undef);
	my $admin = 'admin';

	my $qmsg = "\n\tPlease enter administrator login name. [$admin]: ";
	print STDOUT $qmsg;

	chomp($rdata = readline \*STDIN);

	if (!defined($rdata) || $rdata eq '') {
		$main::ua{'admin'} = $admin;
	} else {
		$main::ua{'admin'} = $rdata;
	}

	push_el(\@main::el, 'ask_admin()', 'Ending...');

	0;
}

sub ask_admin_password {

	push_el(\@main::el, 'ask_admin_password()', 'Starting...');

	my ($rs, $pass1, $pass2) = (undef, undef, undef);

	my $qmsg = "\n\tPlease enter administrator password: ";

	$pass1 = read_password($qmsg);

	if (!defined($pass1) || $pass1 eq '') {
		print STDOUT colored(['bold red'], "\n\tERROR:") .
			 ' Password cannot be empty!';

		return 1;

	} else {

		if (length($pass1) < 5) {
			print STDOUT colored(['bold red'], "\n\tERROR:") .
				'Password too short!';

			return 1;
		}

		$qmsg = "\tPlease repeat administrator password: ";
		$pass2 = read_password($qmsg);

		if ($pass1 =~ m/[a-zA-Z]/ && $pass1 =~ m/[0-9]/) {

			if ($pass1 eq $pass2) {
				$main::ua{'admin_password'} = $pass1;
			} else {
				print STDOUT colored(['bold red'], "\n\tERROR:") .
					' Passwords do not match!';

				return 1;
			}

		} else {
			print STDOUT colored(['bold red'], "\n\tERROR:") .
				' Passwords must contain at least digits and chars!';

			return 1;
		}
	}

	push_el(\@main::el, 'ask_admin_password()', 'Ending...');

	0;
}

sub ask_admin_email {

	push_el(\@main::el, 'ask_admin_email()', 'Starting...');

	my ($rs, $rdata) = (undef, undef);

	my $qmsg = "\n\tPlease enter administrator e-mail address: ";
	print STDOUT $qmsg;

	chomp($rdata = readline \*STDIN);

	if (!defined($rdata) || $rdata eq '') {

		return 1;

	} else {

		# Note About the mail validation
		#
		# The RFC 2822 list quite a few characters that can be
		# used in an email address. However, in practice, the
		# mail client accept a limited version of this list.
		#
		# This regular expression allows the character list in
		# the local part of the email. Regarding the domain part,
		# the syntax is much more strict.
		#
		# Local part:
		#
		#  Validation is a limited version of the syntax allowed by the RFC 2822.
		#
		# Domain part:
		#
		# The syntax is much more strict:
		#
		# - The dash characters are forbidden in the beginning and end of line;
		# - The underscore is prohibited.
		# - It requires at least one second level domain in accordance with
		#   standards set by the RFC 952 and 1123.
		# - It allows only IPv4 domain literal
		if ($rdata =~
        	/^
				# Local part :
				# Optional segment for the local part
				(?:[-!#\$%&'*+\/=?^`{|}~\w]+\.)*
				# Segment required for the local part
				[-!#\$%&'*+\/=?^`{|}~\w]+
				# Separator
				@
				# Domain part
				(?:
				# As common form ( ex. local@domain.tld ) :
					(?:
						[a-z0-9](?:
						(?:[.](?!-))?[-a-z0-9]*[a-z0-9](?:(?:(?<!-)[.](?!-))?[-a-z0-9])*)?
						)+
						(?<!-)[.][a-z0-9]{2,6}
						|
						# As IPv4 domain literal ( ex. local@[192.168.0.130] )
						(?:\[(?:(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\.){3}(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\])
					)
			$/x
		) {
			$main::ua{'admin_email'} = $rdata;
		} else {
			print STDOUT colored(['bold red'], "\n\tERROR:") .
				' E-mail address not valid!';

			return 1;
		}
	}

	push_el(\@main::el, 'ask_admin_email()', 'Ending...');

	0;
}

sub ask_vhost {

	push_el(\@main::el, 'ask_vhost()', 'Starting...');

	my ($rs, $rdata) = (undef, undef);
	# Standard IP with dot to binary data (expected by gethostbyaddr() as first argument )
	my $iaddr = inet_aton($main::ua{'eth_ip'});
	my $addr = gethostbyaddr($iaddr, &AF_INET);

	# gethostbyaddr() returns a short host name with a suffix ( hostname.local )
	# if the host name ( for the current interface ) is not set in /etc/hosts
	# file. In this case, or if the returned value isn't FQHN, we use the long
	# host name who's provided by the system hostname command.
	if(!defined($addr) or
		($addr =~/^[\w][\w-]{0,253}[\w]\.local$/) ||
		!($addr =~ /^([\w][\w-]{0,253}[\w])\.([\w][\w-]{0,253}[\w])\.([a-zA-Z]{2,6})$/) ) {

		$addr = $main::ua{'hostname'};
	}

	# Todo [INTERNAL DISCUSSION] : It's a not good idea to remove hostname part
	# of the long hostname to purpose admin.domain.tld instead of admin.hostname.domain.tld ?
	my $vhost = "admin.$addr";
	my $qmsg = "\n\tPlease enter the domain name where ispCP OMEGA will run on [$vhost]: ";

	print STDOUT $qmsg;

	chomp($rdata = readline \*STDIN);

	if (!defined($rdata) || $rdata eq '') {
		$main::ua{'admin_vhost'} = $vhost;
	} else {

		if ($rdata =~ /^([\w][\w-]{0,253}[\w]\.)*([\w][\w-]{0,253}[\w])\.([a-zA-Z]{2,6})$/) {
			$main::ua{'admin_vhost'} = $rdata;
		} else {
			print STDOUT colored(['bold red'], "\n\tERROR:") .
				' Vhost not valid!';

			return 1;
		}
	}

	push_el(\@main::el, 'ask_vhost()', 'Ending...');

	0;
}

sub ask_second_dns {

	push_el(\@main::el, 'ask_second_dns()', 'Starting...');

	my $rdata = undef;
	my $qmsg = "\n\tIP of Secondary DNS. (optional) []: ";

	print STDOUT $qmsg;

	chomp($rdata = readline \*STDIN);

	if (!defined($rdata) || $rdata eq '') {
		$main::ua{'secondary_dns'} = '';

	} else {

		unless (check_eth($rdata)) {
			$main::ua{'secondary_dns'} = $rdata;
		} else {
			print STDOUT colored(['bold red'], "\n\tERROR:") .
				' No valid IP, please retry!';

			return 1;
		}
	}

	push_el(\@main::el, 'ask_second_dns()', 'Ending...');

	0;
}

sub ask_mysql_prefix {

	push_el(\@main::el, 'ask_mysql_prefix()', 'Starting...');

	my $rdata = undef;
	my $qmsg = "\n\tUse MySQL Prefix.\n\tPossible values: [i]nfront, [b]ehind, [n]one. [none]: ";

	print STDOUT $qmsg;

	chomp($rdata = readline \*STDIN);

	if (!defined($rdata) || $rdata eq '' || $rdata eq 'none' || $rdata eq 'n') {

		$main::ua{'mysql_prefix'} = 'no';
		$main::ua{'mysql_prefix_type'} = '';

	} else {

		if ($rdata eq 'infront' || $rdata eq 'i') {

			$main::ua{'mysql_prefix'} = 'yes';
			$main::ua{'mysql_prefix_type'} = 'infront';

		} elsif ($rdata eq 'behind' || $rdata eq 'b') {

			$main::ua{'mysql_prefix'} = 'yes';
			$main::ua{'mysql_prefix_type'} = 'behind';

		} else {

			print STDOUT colored(['bold red'], "\n\tERROR:") .
			' Not allowed Value, please retry!';

			return 1;
		}
	}

	push_el(\@main::el, 'ask_mysql_prefix()', 'Ending...');

	0;
}

sub ask_db_pma_user {

	push_el(\@main::el, 'ask_db_pma_user()', 'Starting...');

	my ($rs, $rdata) = (undef, undef);
	my $db_user = $main::cfg{'PMA_USER'};

	if(defined &update_engine) {
		$main::ua{'db_user'} = $main::cfg{'DATABASE_USER'};
	}

	my $qmsg = "\n\tPlease enter ispCP phpMyAdmin Control user. [$db_user]: ";
	print STDOUT $qmsg;

	chomp($rdata = readline \*STDIN);

	if (!defined($rdata) || $rdata eq '') {

		$main::ua{'db_pma_user'} = $db_user;

	} elsif( $rdata eq $main::ua{'db_user'}) {

		$qmsg = colored(['bold red'], "\n\tERROR:") .
			' phpMyAdmin Control user must not be identical to system SQL user!';

		print STDOUT $qmsg;

		return 1;

	} elsif ($rdata eq $main::ua{'db_ftp_user'}) {

		$qmsg = colored(['bold red'], "\n\tERROR:") .
			' phpMyAdmin Control user must not be identical to ftp SQL user!';

		print STDOUT $qmsg;

		return 1;

	} else {

		$main::ua{'db_pma_user'} = $rdata;
	}

	push_el(\@main::el, 'ask_db_pma_user()', 'Ending...');

	0;
}

sub ask_db_pma_password {

	push_el(\@main::el, 'ask_db_pma_password()', 'Starting...');

	my ($rs, $pass1, $pass2) = (undef, undef, undef);

	my $db_password = undef;

	my $qmsg = "\n\tPlease enter ispCP phpMyAdmin Control user password. [auto generate]: ";
	$pass1 = read_password($qmsg);

	if (!defined($pass1) || $pass1 eq '') {

		$db_password = gen_sys_rand_num(18);
		$db_password =~ s/('|"|`|#|;)//g;
		$main::ua{'db_pma_password'} = $db_password;
		print STDOUT "\tphpMyAdmin Control user password set to: $db_password\n";

	} else {

		$qmsg = "\tPlease repeat ispCP phpMyAdmin Control user password: ";
		$pass2 = read_password($qmsg);

		if ($pass1 eq $pass2) {

			$main::ua{'db_pma_password'} = $pass1;

		} else {

			print STDOUT colored(['bold red'], "\n\tERROR:") .
				' Passwords do not match!';

			return 1;
		}
	}

	push_el(\@main::el, 'ask_db_pma_password()', 'Ending...');

	0;
}

sub ask_fastcgi {

	push_el(\@main::el, 'ask_fastcgi()', 'Starting...');

	my $rdata = undef;

	my $qmsg = "\n\tFastCGI Version: [f]cgid or fast[c]gi. [fcgid]: ";

	print STDOUT $qmsg;

	chomp($rdata = readline \*STDIN);

	if (!defined($rdata) || $rdata eq '') {

		$main::ua{'php_fastcgi'} = 'fcgid';

	} else {

		if ($rdata eq 'fcgid' || $rdata eq 'f') {

			$main::ua{'php_fastcgi'} = 'fcgid';

		} elsif ($rdata eq 'fastcgi' || $rdata eq 'c') {

			$main::ua{'php_fastcgi'} = 'fastcgi';

		} else {

			print STDOUT colored(['bold red'], "\n\tERROR:") .
				 " Only '[f]cgid' or 'fast[c]gi' are allowed!";

			return 1;
		}
	}

	push_el(\@main::el, 'ask_fastcgi()', 'Ending...');

	0;
}

sub ask_awstats_on {

	push_el(\@main::el, 'ask_awstats_on()', 'Starting...');

	my $rdata = undef;
	my $qmsg = "\n\tActivate AWStats. [no]: ";

	print STDOUT $qmsg;

	chomp($rdata = readline \*STDIN);

	if (!defined($rdata) || $rdata eq '') {

		$main::ua{'awstats_on'} = 'no';

	} else {

		if ($rdata eq 'yes' || $rdata eq 'y') {

			$main::ua{'awstats_on'} = 'yes';

		} elsif ($rdata eq 'no' || $rdata eq 'n') {

			$main::ua{'awstats_on'} = 'no';

		} else {

			print STDOUT colored(['bold red'], "\n\tERROR:") .
				" Only '(y)es' and '(n)o' are allowed!";
			return 1;
		}
	}

	push_el(\@main::el, 'ask_awstats_on()', 'Ending...');

	0;
}

sub ask_awstats_dyn {

	push_el(\@main::el, 'ask_awstats_dyn()', 'Starting...');

	my $rdata = undef;
	my $qmsg = "\n\tAWStats Mode:\n\tPossible values [d]ynamic and [s]tatic. [dynamic]: ";

	print STDOUT $qmsg;

	chomp($rdata = readline \*STDIN);

	if (!defined($rdata) || $rdata eq '') {

		$main::ua{'awstats_dyn'} = '0';

	} else {

		if ($rdata eq 'dynamic' || $rdata eq 'd') {

			$main::ua{'awstats_dyn'} = '0';

		} elsif ($rdata eq 'static' || $rdata eq 's') {

			$main::ua{'awstats_dyn'} = '1';

		} else {

			print STDOUT colored(['bold red'], "\n\tERROR:") .
				 " Only '[d]ynamic' or '[s]tatic' are allowed!";

			return 1;
		}
	}

	push_el(\@main::el, 'ask_awstats_dyn()', 'Ending...');

	0;
}

#
## Ask subroutines - End
#

#
## Setup / Update subroutines - Begin
#

# IspCP crontab setup / update
# Built, store and install the ispCP crontab file
sub setup_crontab {

	push_el(\@main::el, 'setup_crontab()', 'Starting...');

	my ($rs, $rdata, $cmd) = (undef, undef, undef);

	my $cfg_tpl = undef;
	my $cfg = \$cfg_tpl;

	my ($awstats, $rkhunter, $chkrootkit) = ('', undef, undef);

	# Directories paths
	my $cfg_dir = $main::cfg{'CONF_DIR'} . '/cron.d';
	my $bk_dir = $cfg_dir . '/backup';
	my $wrk_dir = $cfg_dir . '/working';
	my $prod_dir = undef;

	# Determines the path of production directory
	if ($main::cfg{'ROOT_GROUP'} eq 'wheel') {

		$prod_dir = '/usr/local/etc/ispcp/cron.d';

	} else {

		$prod_dir = '/etc/cron.d'
	}

	# Dedicated tasks for Install or Updates process - Begin

	# Update :
	if(defined &update_engine) {

		my $timestamp = time();

		# Saving the current production file if it exists
		if(-e  "$prod_dir/ispcp") {
			$cmd = "$main::cfg{'CMD_CP'} -p $prod_dir/ispcp $bk_dir/ispcp.$timestamp";
			$rs = sys_command_rs($cmd);
			return $rs if ($rs != 0);
		}
	}

	# Dedicated tasks for Install or Updates process - End

	# Building new configuration file - Begin

	# Loading the template from /etc/ispcp/cron.d/ispcp
	($rs, $cfg_tpl) = get_file("$cfg_dir/ispcp");
	return $rs if ($rs != 0);

	# Awstats cron task preparation (On|Off) according status in ispcp.conf
	if ($main::cfg{'AWSTATS_ACTIVE'} ne 'yes' || $main::cfg{'AWSTATS_MODE'} eq 1) {
		$awstats = '#';
	}

	# Search and cleaning path for rkhunter and chkrootkit programs
	($rkhunter = `which rkhunter`) =~ s/\s$//g;
	($chkrootkit = `which chkrootkit`) =~ s/\s$//g;

	# Tags preparation
	my %tags_hash = (
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
		'{RK-ENABLED}' => !length($rkhunter) ? '#' : '',
		'{RKHUNTER}' => $rkhunter,
		'{CR-ENABLED}' => !length($chkrootkit) ? '#' : '',
		'{CHKROOTKIT}' => $chkrootkit
	);

	# Building the new file
	($rs, $$cfg) = prep_tpl(\%tags_hash, $cfg_tpl);
	return $rs if ($rs != 0);

	# Building new configuration file - End

	# Storage and installation of new file - Begin

	# Store the new file in the working directory
	$rs = store_file(
		"$wrk_dir/ispcp",
		$$cfg,
		$main::cfg{'ROOT_USER'},
		$main::cfg{'ROOT_GROUP'},
		0644
	);
	return $rs if ($rs != 0);

	# Install the new file in production directory
	$cmd = "$main::cfg{'CMD_CP'} -pf $wrk_dir/ispcp $prod_dir/";
	$rs = sys_command_rs($cmd);
	return $rs if ($rs != 0);

	# Storage and installation of new file - End

	push_el(\@main::el, 'setup_crontab()', 'Ending...');

	0;
}

# IspCP named main configuration setup / update
# Built, store and install main named configuration file
# @TODO Change related Makefile
sub setup_named {

	push_el(\@main::el, 'setup_named()', 'Starting...');

	# Do not generate cfg files if the service is disabled
	return 0 if($main::cfg{'CMD_NAMED'} =~ /^no$/i);

	my ($rs, $rdata, $cmd) = (undef, undef, undef);
	my ($cfg_tpl, $cfg) = (undef, undef);

	my $cfg_dir = "$main::cfg{'CONF_DIR'}/bind";
	my $bk_dir = "$cfg_dir/backup";
	my $wrk_dir = "$cfg_dir/working";

	# Dedicated tasks for Install or Updates process - Begin

	# Install:
	if(!defined &update_engine) {

		# Saving the system main configuration file if it exists
		if(-e $main::cfg{'BIND_CONF_FILE'} && !-e "$bk_dir/named.conf.system") {
			$cmd = "$main::cfg{'CMD_CP'} -p $main::cfg{'BIND_CONF_FILE'} $bk_dir/named.conf.system";
			$rs = sys_command_rs($cmd);
			return $rs if ($rs != 0);
		}

	# Update:
	} else {

		# Saving the current main production file if it exists
		if(-e $main::cfg{'BIND_CONF_FILE'}) {
			my $timestamp = time();

			$cmd = "$main::cfg{'CMD_CP'} -p $main::cfg{'BIND_CONF_FILE'} $bk_dir/named.conf.$timestamp";
			$rs = sys_command_rs($cmd);
			return $rs if ($rs != 0);
		}
	}

	# Dedicated tasks for Install or Updates process - Begin

	# Building of new configuration file - Begin

	# Loading the system main configuration file from
	# /etc/ispcp/bind/backup/named.conf.system if it exists
	if(-e "$bk_dir/named.conf.system") {

		($rs, $cfg) = get_file("$bk_dir/named.conf.system");
		return $rs if($rs != 0);

		# Adjusting the configuration if needed
		$cfg =~ s/listen-on ((.*) )?{ 127.0.0.1; };/listen-on $1 { any; };/;

		$cfg .= "\n";

	# eg. Centos, Fedora did not file by default
	} else {

		push_el(\@main::el, 'add_named_db_data()', "WARNING: Can't find the parent file for named...");
		$cfg = '';
	}

	#

	# Loading the template from /etc/ispcp/bind/named.conf
	($rs, $cfg_tpl) = get_file("$cfg_dir/named.conf");
	return $rs if($rs != 0);

	# Building of new file
	$cfg .= $cfg_tpl;

	# Building of new configuration file - End

	# Storage and installation of new file - Begin

	# Storage of new file in the working directory
	$rs = store_file(
		"$wrk_dir/named.conf",
		$cfg,
		$main::cfg{'ROOT_USER'},
		$main::cfg{'ROOT_GROUP'},
		0644
	);
	return $rs if ($rs != 0);

	# Install the new file in the production directory
	$cmd = "$main::cfg{'CMD_CP'} -pf $wrk_dir/named.conf $main::cfg{'BIND_CONF_FILE'}";
	$rs = sys_command_rs($cmd);
	return $rs if ($rs != 0);

	# Storage and installation of new file - End

	push_el(\@main::el, 'setup_named()', 'Ending...');

	0;
}

# IspCP php main configuration setup / update
# Built, store and install all system php related configuration files
# Enable required modules and disable unused
sub setup_php {

	push_el(\@main::el, 'setup_php()', 'Starting...');

	# Do not generate cfg files if the service is disabled
	return 0 if($main::cfg{'APACHE_CMD'} =~ /^no$/i);

	my ($rs, $cmd) = (undef, undef);

	# Service log file path
	my $services_log_path = undef;

	my $cfg_tpl = undef;
	my $cfg = \$cfg_tpl;

	# Directories paths
	my $cfg_dir = "$main::cfg{'CONF_DIR'}/apache";
	my $bk_dir = "$cfg_dir/backup";
	my $wrk_dir = "$cfg_dir/working";

	# Dedicated tasks for the Install or Updates process - Begin

	# Install:
	if(!defined &update_engine) {
		$services_log_path = "/tmp/ispcp-setup-services.log";

	# Update:
	} else {

		$services_log_path = "/tmp/ispcp-update-services.log";

		my $timestamp = time();

		foreach(qw/fastcgi_ispcp.conf fastcgi_ispcp.load fcgid_ispcp.conf fcgid_ispcp.load/) {

			# Saving the current production file if it exists
			if(-e "$main::cfg{'APACHE_MODS_DIR'}/$_") {
				$cmd = "$main::cfg{CMD_CP} -p $main::cfg{'APACHE_MODS_DIR'}/$_ $bk_dir/$_.$timestamp";
				$rs = sys_command_rs($cmd);
				return $rs if($rs != 0);
			}
		}
	}

	# Dedicated tasks for the Install or Updates process - End

	# Building, storage and installation of new files - Begin

	# Tags preparation
	my %tags_hash = (
		fastcgi => {
			'{APACHE_SUEXEC_MIN_UID}' => $main::cfg{'APACHE_SUEXEC_MIN_UID'},
			'{APACHE_SUEXEC_MIN_GID}' => $main::cfg{'APACHE_SUEXEC_MIN_GID'},
			'{APACHE_SUEXEC_USER_PREF}' => $main::cfg{'APACHE_SUEXEC_USER_PREF'},
			'{PHP_STARTER_DIR}' => $main::cfg{'PHP_STARTER_DIR'},
			'{PHP_VERSION}' => $main::cfg{'PHP_VERSION'}
		},
		fcgid => {
			'{PHP_VERSION}' => $main::cfg{'PHP_VERSION'}
		}
	);

	# fastcgi_ispcp.conf / fcgid_ispcp.conf
	foreach(qw/fastcgi fcgid/) {

		# Loading the template from /etc/ispcp/apache
		($rs, $cfg_tpl) = get_file("$cfg_dir/$_\_ispcp.conf");
		return $rs if ($rs != 0);

		# Building the new configuration file
		($rs, $$cfg) = prep_tpl($tags_hash{$_}, $cfg_tpl);
		return $rs if ($rs != 0);

		# Store the new file
		$rs = store_file(
			"$wrk_dir/$_\_ispcp.conf",
			$$cfg,
			$main::cfg{'ROOT_USER'},
			$main::cfg{'ROOT_GROUP'},
			0644
		);
		return $rs if ($rs != 0);

		# Install the new file
		$cmd = "$main::cfg{'CMD_CP'} -pf $wrk_dir/$_\_ispcp.conf $main::cfg{'APACHE_MODS_DIR'}/";
		$rs = sys_command_rs($cmd);
		return $rs if($rs != 0);
	}

	# fastcgi_ispcp.load / fcgid_ispcp.load
	foreach(qw/fastcgi fcgid/) {

		next if(! -e "$main::cfg{'APACHE_MODS_DIR'}/$_.load");

		# Loading the system configuration file
		($rs, $$cfg) = get_file("$main::cfg{'APACHE_MODS_DIR'}/$_.load");
		return $rs if ($rs != 0);

		# Building the new configuration file
		$$cfg = "<IfModule !mod_$_.c>\n" . $$cfg . "</IfModule>\n";

		# Store the new file
		$rs = store_file(
			"$wrk_dir/$_\_ispcp.load",
			$$cfg,
			$main::cfg{'ROOT_USER'},
			$main::cfg{'ROOT_GROUP'},
			0644
		);
		return $rs if ($rs != 0);

		# Install the new file
		$cmd = "$main::cfg{'CMD_CP'} -pf $wrk_dir/$_\_ispcp.load $main::cfg{'APACHE_MODS_DIR'}/";
		$rs = sys_command_rs($cmd);
		return $rs if($rs != 0);
	}

	# Building, storage and installation of new files - End

	# Enable required modules and disable unused - Begin

	if(-e '/usr/sbin/a2enmod' && -e '/usr/sbin/a2dismod' ) {

		# Disable php4/5 modules
		sys_command_rs("/usr/sbin/a2dismod php4 &> $services_log_path");
		sys_command_rs("/usr/sbin/a2dismod php5 &> $services_log_path");

		# Enable actions modules
		sys_command_rs("/usr/sbin/a2enmod actions &> $services_log_path");

		if(! -e '/etc/SuSE-release') {

			if ($main::cfg{'PHP_FASTCGI'} eq 'fastcgi') {

				# Ensures that the unused ispcp fcgid module loader is disabled
				sys_command_rs("/usr/sbin/a2dismod ispcp_fcgid &> $services_log_path");

				# Enable fastcgi module
				sys_command_rs("/usr/sbin/a2enmod fastcgi_ispcp &> $services_log_path");

			} else {

				# Ensures that the unused ispcp fastcgi ispcp module loader is disabled
				sys_command_rs("/usr/sbin/a2dismod ispcp_fastcgi &> $services_log_path");

				# Enable ispcp fastcgi loader
				sys_command_rs("/usr/sbin/a2enmod fcgid_ispcp &> $services_log_path");
			}

			# Disable default  fastcgi/fcgid modules loaders to avoid conflicts with ispcp loaders
			sys_command_rs("/usr/sbin/a2dismod fastcgi &> $services_log_path");
			sys_command_rs("/usr/sbin/a2dismod fcgid &> $services_log_path");

		}
	}

	# Enable required modules and disable unused - End

	push_el(\@main::el, 'setup_php()', 'Ending...');

	0;
}

# IspCP httpd main vhost setup / update
# Build, store and install ispCP main vhost configuration file
# Enable required modules (cgid, rewrite, suexec)
sub setup_httpd_main_vhost {

	push_el(\@main::el, 'setup_httpd_main_vhost()', 'Starting...');

	# Do not generate cfg files if the service is disabled
	return 0 if($main::cfg{'APACHE_CMD'} =~ /^no$/i);

	my ($rs, $cmd) = (undef, undef);

	my $cfg_tpl = undef;
	my $cfg = \$cfg_tpl;

	# Log file path
	my $services_log_path = undef;

	# Directories paths
	my $cfg_dir = "$main::cfg{'CONF_DIR'}/apache";
	my $bk_dir = "$cfg_dir/backup";
	my $wrk_dir = "$cfg_dir/working";

	# Dedicated tasks for the Install or Updates process - Begin

	# Install:
	if(!defined &update_engine) {

		$services_log_path = "/tmp/ispcp-setup-services.log";

	# Update:
	} else {
		$services_log_path = "/tmp/ispcp-update-services.log";

		# Saving the current production file if it exists
		if(-e "$main::cfg{'APACHE_SITES_DIR'}/ispcp.conf") {

			my $timestamp = time();

			$cmd = "$main::cfg{'CMD_CP'} -p $main::cfg{'APACHE_SITES_DIR'}/ispcp.conf $bk_dir/ispcp.conf.$timestamp";
			$rs = sys_command_rs($cmd);
			return $rs if($rs != 0);
		}
	}

	# Dedicated tasks for the Install or Updates process - End

	# Building, storage and installation of new file - Begin

	# Loading the template from /etc/ispcp/apache/
	($rs, $cfg_tpl) = get_file("$cfg_dir/httpd.conf");
	return $rs if ($rs != 0);

	# Tags preparation
	my %tags_hash = (
		'{HOST_IP}' => $main::cfg{'BASE_SERVER_IP'}
	);

	# Building the new file
	($rs, $$cfg) = prep_tpl(\%tags_hash, $cfg_tpl);
	return $rs if ($rs != 0);

	# Store the new file in working directory
	$rs = store_file(
		"$wrk_dir/ispcp.conf",
		$$cfg,
		$main::cfg{'ROOT_USER'},
		$main::cfg{'ROOT_GROUP'},
		0644
	);
	return $rs if ($rs != 0);

	# Install the new file in production directory
	$cmd = "$main::cfg{'CMD_CP'} -pf $wrk_dir/ispcp.conf $main::cfg{'APACHE_SITES_DIR'}/";
	$rs = sys_command_rs($cmd);
	return $rs if($rs != 0);

	# Building, storage and installation of new file - End

	# Enable required modules - Begin

	if(-e "/usr/sbin/a2enmod") {

		# We use cgid instead of cgi because we working with MPM.
		# FIXME: Check if it's ok for all dists. (Lenny, opensuse OK)
		sys_command("/usr/sbin/a2enmod cgid &> $services_log_path");

		sys_command("/usr/sbin/a2enmod rewrite &> $services_log_path");
		sys_command("/usr/sbin/a2enmod suexec &> $services_log_path");
	}

	# Enable required modules - End

	# Enable main vhost configuration file - Begin

	if(-e "/usr/sbin/a2ensite") {
		sys_command("/usr/sbin/a2ensite ispcp.conf &> $services_log_path");
	}

	# Enable main vhost configuration file - End

	push_el(\@main::el, 'setup_httpd_main_vhost()', 'Ending...');

	0;
}

# IspCP awstats vhost setup / update
# Build, store and install awstats vhost configuration file
# Change proxy module configuration file if it exits
# Enable proxy module
sub setup_awstats_vhost {

	push_el(\@main::el, 'setup_awstats_vhost()', 'Starting...');

	# Do not generate cfg files if the service is disabled
	return 0 if($main::cfg{'AWSTATS_ACTIVE'} =~ /^no$/i);

	my ($rs, $cmd) = (undef, undef);

	my ($path, $file) = (undef, undef);

	my $cfg_tpl = undef;
	my $cfg = \$cfg_tpl;

	# Log file path
	my $services_log_path = undef;

	# Directories paths
	my $cfg_dir = "$main::cfg{'CONF_DIR'}/apache";
	my $bk_dir = "$cfg_dir/backup";
	my $wrk_dir = "$cfg_dir/working";

	# Dedicated tasks for Install or Updates process - Begin

	# Install:
	if(!defined &update_engine) {
		$services_log_path = "/tmp/ispcp-setup-services.log";

		# Saving more system cfg files changed by ispCP
		foreach (
			map {/(.*\/)(.*)$/ && $1.':'.$2}
			'/etc/logrotate.d/apache',
			'/etc/logrotate.d/apache2',
			"$main::cfg{'APACHE_MODS_DIR'}/proxy.conf"
		) {
				($path, $file) = split /:/ ;
				next if(!-e $path.$file);

				$cmd = "$main::cfg{'CMD_CP'} -p $path$file $bk_dir/$file.system";
				$rs = sys_command_rs($cmd);
				return $rs if($rs != 0);
		}

	# Update:
	} else {
		$services_log_path = '/tmp/ispcp-update-services.log';

		my $timestamp = time;

		# Saving more production files if they exist
		foreach (
			map {/(.*\/)(.*)$/ && $1.':'.$2}
			'/etc/logrotate.d/apache',
			'/etc/logrotate.d/apache2',
			"$main::cfg{'APACHE_MODS_DIR'}/proxy.conf",
			"$main::cfg{'APACHE_SITES_DIR'}/01_awstats.conf"
		) {
				($path, $file)= split /:/;
				next if(!-e $path.$file);

				$cmd = "$main::cfg{'CMD_CP'} -p $path$file $bk_dir/$file.$timestamp";
				$rs = sys_command_rs($cmd);
				return $rs if($rs != 0);
		}
	}

	# Dedicated tasks for Install or Updates process - End

	# Building, storage and installation of new file - Begin

	# Tags preparation
	my %tags_hash = (
		'{AWSTATS_ENGINE_DIR}' => $main::cfg{'AWSTATS_ENGINE_DIR'},
		'{AWSTATS_WEB_DIR}' => $main::cfg{'AWSTATS_WEB_DIR'}
	);

	# Loading the template from /etc/ispcp/apache
	($rs, $cfg_tpl) = get_file("$cfg_dir/01_awstats.conf");
	return $rs if($rs != 0);

	# Building the new file
	($rs, $$cfg) = prep_tpl(\%tags_hash, $cfg_tpl);
	return $rs if ($rs != 0);

	# Store the new file in working directory
	$rs = store_file(
		"$wrk_dir/01_awstats.conf",
		$$cfg,
		$main::cfg{'ROOT_USER'},
		$main::cfg{'ROOT_GROUP'},
		0644
	);
	return $rs if ($rs != 0);

	# Install the new file in production directory
	$cmd = "$main::cfg{'CMD_CP'} -pf $wrk_dir/01_awstats.conf $main::cfg{'APACHE_SITES_DIR'}/";
	$rs = sys_command_rs($cmd);
	return $rs if($rs != 0);

	# Building, storage and installation of new file - End

	if ($main::cfg{'AWSTATS_ACTIVE'} eq 'yes' && $main::cfg{'AWSTATS_MODE'} eq 0) {

		# Change the proxy module configuration file if it exists - Begin

		if(-e "$bk_dir/proxy.conf.system") {

			($rs, $$cfg) = get_file("$bk_dir/proxy.conf.system");
			return $rs if($rs != 0);

			# Replace the allowed hosts in mod_proxy if nedeed
			$$cfg =~ s/#Allow from .example.com/Allow from 127.0.0.1/gi;

			# Store the new file in working directory
			$rs = store_file(
				"$wrk_dir/proxy.conf",
				$$cfg,
				$main::cfg{'ROOT_USER'},
				$main::cfg{'ROOT_GROUP'},
				0644
			);
			return $rs if ($rs != 0);

			# Install the new file in production directory
			$cmd = "$main::cfg{'CMD_CP'} -pf $wrk_dir/proxy.conf $main::cfg{'APACHE_MODS_DIR'}/";
			$rs = sys_command_rs($cmd);
			return $rs if($rs != 0);
		}

		# Enable required modules
		if(-e '/usr/sbin/a2enmod') {
			sys_command_rs("/usr/sbin/a2enmod proxy &> $services_log_path");
			sys_command_rs("/usr/sbin/a2enmod proxy_http &> $services_log_path");
		}

		# Change and enable required proxy module - End

		# Enable awstats vhost - Begin

		if(-e '/usr/sbin/a2ensite') {

			sys_command("/usr/sbin/a2ensite 01_awstats.conf &> $services_log_path");
		}

		# Enable awstats vhost - End

		# Update Apache logrotate file - Begin

		# FIXME: check for openSUSE and other dists...
		# Todo create dedicated directory for backup logrotate configuration file
		foreach(qw/apache apache2/) {

			next if(! -e "$bk_dir/$_.system");

			($rs, $$cfg) = get_file("$bk_dir/$_.system");
			return $rs if ($rs != 0);

			# Add code if not exists
			if ($$cfg !~ /awstats_updateall\.pl/i) {

				# Building the new file
				$$cfg =~ s/sharedscripts/sharedscripts\n\tprerotate\n\t\t$main::cfg{'AWSTATS_ROOT_DIR'}\/awstats_updateall.pl now -awstatsprog=$main::cfg{'AWSTATS_ENGINE_DIR'}\/awstats.pl &> \/dev\/null\n\tendscript/gi;

				# Store the new file in working directory
				$rs = store_file(
					"$wrk_dir/$_",
					$$cfg,
					$main::cfg{'ROOT_USER'},
					$main::cfg{'ROOT_GROUP'},
					0644
				);
				return $rs if ($rs != 0);

				# Install the new file in production directory
				$cmd = "$main::cfg{'CMD_CP'} -pf $wrk_dir/$_ /etc/logrotate.d/";
				$rs = sys_command_rs($cmd);
				return $rs if($rs != 0);
			}
		}

		# Update Apache logrotate file - End
	}

	push_el(\@main::el, 'setup_awstats_vhost()', 'Starting...');

	0;
}

# IspCP Postfix setup / update
# Build, store and install Postfix configuration file
sub setup_mta {

	push_el(\@main::el, 'setup_mta()', 'Starting...');

	# Do not generate cfg files if the service is disabled
	return 0 if($main::cfg{'CMD_MTA'} =~ /^no$/i);

	my ($rs, $cmd) = (undef, undef);

	my $cfg_tpl = undef;
	my $cfg = \$cfg_tpl;

	my $services_log_path = undef;

	my ($path, $file) = (undef, undef);

	# Directories paths
	my $cfg_dir = "$main::cfg{'CONF_DIR'}/postfix";
	my $bk_dir = "$cfg_dir/backup";
	my $wrk_dir = "$cfg_dir/working";
	my $vrl_dir = "$cfg_dir/ispcp";

	# Dedicated tasks for the Install or Updates process - Begin

	# Install
	if(!defined &update_engine) {
		$services_log_path = "/tmp/ispcp-setup-services.log";

		# Savings all system configuration files if they exist
		foreach (
			map {/(.*\/)(.*)$/ && $1.':'.$2}
			$main::cfg{'POSTFIX_CONF_FILE'},
			$main::cfg{'POSTFIX_MASTER_CONF_FILE'}
		) {
			($path, $file) = split /:/;

			next if(!-e $path.$file);

			$cmd = "$main::cfg{'CMD_CP'} -p $path$file  $bk_dir/$file.system";
			$rs = sys_command_rs($cmd);
			return $rs if ($rs != 0);
		}

	# Update
	} else {

		$services_log_path = "/tmp/ispcp-update-services.log";

		my $timestamp = time;

		# Saving all current production files
		foreach (
			map {/(.*\/)(.*)$/ && $1.':'.$2}
			$main::cfg{'POSTFIX_CONF_FILE'},
			$main::cfg{'POSTFIX_MASTER_CONF_FILE'},
			$main::cfg{'MTA_VIRTUAL_CONF_DIR'}.'/aliases',
			$main::cfg{'MTA_VIRTUAL_CONF_DIR'}.'/domains',
			$main::cfg{'MTA_VIRTUAL_CONF_DIR'}.'/mailboxes',
			$main::cfg{'MTA_VIRTUAL_CONF_DIR'}.'/transport',
			$main::cfg{'MTA_VIRTUAL_CONF_DIR'}.'/sender-access'
		) {
			($path, $file) = split /:/;

			next if(!-e $path.$file);

			$cmd = "$main::cfg{'CMD_CP'} -p $path$file  $bk_dir/$file.$timestamp";
			$rs = sys_command_rs($cmd);
			return $rs if ($rs != 0);
		}
	}

	# Dedicated tasks for the Install or Updates process - End

	# Building, storage and installation of new file - Begin

	# main.cf - Begin

	# Tags preparation
	my %tags_hash = (
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

	# Loading the template from /etc/ispcp/postfix/
	($rs, $cfg_tpl) = get_file("$cfg_dir/main.cf");
	return $rs if ($rs != 0);

	# Building the new file
	($rs, $$cfg) = prep_tpl(\%tags_hash, $cfg_tpl);
	return $rs if ($rs != 0);

	# Store the new file in working directory
	$rs = store_file(
		"$wrk_dir/main.cf",
		$$cfg,
		$main::cfg{'ROOT_USER'},
		$main::cfg{'ROOT_GROUP'},
		0644
	);
	return $rs if ($rs != 0);

	# Install the new file in production directory
	$cmd = "$main::cfg{'CMD_CP'} -pf $wrk_dir/main.cf $main::cfg{'POSTFIX_CONF_FILE'}";
	$rs = sys_command_rs($cmd);
	return $rs if($rs != 0);

	# main.cf - End

	# master.cf - Begin

	# Store the file in working directory
	$cmd = "$main::cfg{'CMD_CP'} -pf $cfg_dir/master.cf $wrk_dir/";
	$rs = sys_command($cmd);
	return $rs if ($rs != 0);

	# Install the file in production dir
	$cmd = "$main::cfg{'CMD_CP'} -pf $cfg_dir/master.cf $main::cfg{'POSTFIX_MASTER_CONF_FILE'}";
	$rs = sys_command($cmd);
	return $rs if ($rs != 0);

	# master.cf - End

	# Virtuals related files - Begin

	foreach(qw/aliases domains mailboxes transport sender-access/) {

		# Store the new files in working directory
		$cmd = "$main::cfg{'CMD_CP'} -pf $vrl_dir/$_ $wrk_dir/";
		$rs = sys_command($cmd);
		return $rs if ($rs != 0);

		# Install the files in production directory
		$cmd = "$main::cfg{'CMD_CP'} -pf $wrk_dir/$_ $main::cfg{'MTA_VIRTUAL_CONF_DIR'}/";
		$rs = sys_command($cmd);
		return $rs if ($rs != 0);
	}

	# Create / update Btree databases for all lookup tables
	$cmd = "$main::cfg{'CMD_POSTMAP'} $main::cfg{'MTA_VIRTUAL_CONF_DIR'}/{aliases,domains,mailboxes,transport,sender-access} &> $services_log_path";
	$rs = sys_command($cmd);
	return $rs if ($rs != 0);

	# Rebuild the database for the mail aliases file - Begin

	$rs = sys_command("$main::cfg{'CMD_NEWALIASES'} &> $services_log_path");
	return $rs if ($rs != 0);

	# Rebuild the database for the mail aliases file - End

	# Virtuals related files - End

	# Building, storage and installation of new file - End

	# Set ARPL messenger owner, group and permissions - Begin

	$rs = setfmode(
		"$main::cfg{'ROOT_DIR'}/engine/messenger/ispcp-arpl-msgr",
		$main::cfg{'MTA_MAILBOX_UID_NAME'},
		$main::cfg{'MTA_MAILBOX_GID_NAME'},
		0755
	);
	return $rs if ($rs != 0);

	# Set ARPL messenger owner, group and permissions - End

	push_el(\@main::el, 'setup_mta()', 'Ending...');

	0;
}

# IspCP Courier setup / update
# Build, store and install Courier, related configuration
# files (authdaemonrc userdb)
# Creates userdb.dat from the contents of userdb
sub setup_po {

	push_el(\@main::el, 'setup_po()', 'Starting...');

	# Do not generate cfg files if the service is disabled
	return 0 if($main::cfg{'CMD_AUTHD'} =~ /^no$/i);

	my ($rs, $cmd, $rdata) = (undef, undef, undef);

	my $services_log_path = undef;

	# Directories paths
	my $cfg_dir = "$main::cfg{'CONF_DIR'}/courier";
	my $bk_dir ="$cfg_dir/backup";
	my $wrk_dir = "$cfg_dir/working";

	# Dedicated tasks for the Install or Updates process - Begin

	# Install:
	if(!defined &update_engine) {

		$services_log_path = "/tmp/ispcp-setup-services.log";

		# Saving all system configuration files if they exist
		foreach (qw/authdaemonrc userdb/) {

			next if(!-e "$main::cfg{'AUTHLIB_CONF_DIR'}/$_");

			$cmd = "$main::cfg{'CMD_CP'} -p $main::cfg{'AUTHLIB_CONF_DIR'}/$_ $bk_dir/$_.system";
			$rs = sys_command_rs($cmd);
			return $rs if ($rs != 0);
		}

	# Update:
	} else {

		$services_log_path = "/tmp/ispcp-update-services.log";

		my $timestamp = time;

		# Saving all current production files if they exist
		foreach (qw/authdaemonrc userdb/) {

			next if(!-e "$main::cfg{'AUTHLIB_CONF_DIR'}/$_");

			$cmd = "$main::cfg{'CMD_CP'} -p $main::cfg{'AUTHLIB_CONF_DIR'}/$_ $bk_dir/$_.$timestamp";
			$rs = sys_command_rs($cmd);
			return $rs if ($rs != 0);
		}

	}

	# Dedicated tasks for the Install or Updates process - End

	# Building, storage and installation of new file - Begin

	# authdaemonrc - Begin

	# Loading the system file from /etc/ispcp/backup
	($rs, $rdata) = get_file("$bk_dir/authdaemonrc.system");
	return $rs if ($rs != 0);

	# Building the new file
	# FIXME: Sould be review...
	$rdata =~ s/authmodulelist="/authmodulelist="authuserdb /gi;

	# Store the new file in working directory
	$rs = store_file(
		"$wrk_dir/authdaemonrc",
		$rdata,
		$main::cfg{'ROOT_USER'},
		$main::cfg{'ROOT_GROUP'},
		0660
	);
	return $rs if ($rs != 0);

	# Install the new file in production directory
	$cmd = "$main::cfg{'CMD_CP'} -pf $wrk_dir/authdaemonrc $main::cfg{'AUTHLIB_CONF_DIR'}/";
	$rs = sys_command($cmd);
	return $rs if ($rs != 0);

	# authdaemonrc - End

	# userdb - Begin

	# Store the new file in working directory
	$cmd = "$main::cfg{'CMD_CP'} -pf $cfg_dir/userdb $wrk_dir/";
	$rs = sys_command($cmd);
	return $rs if ($rs != 0);

	# Install the new file in production directory
	$cmd = "$main::cfg{'CMD_CP'} -pf $wrk_dir/userdb $main::cfg{'AUTHLIB_CONF_DIR'}";
	$rs = sys_command($cmd);
	return $rs if ($rs != 0);

	# Set permissions for the production file
	$rs = setfmode(
		"$main::cfg{'AUTHLIB_CONF_DIR'}/userdb",
		$main::cfg{'ROOT_USER'},
		$main::cfg{'ROOT_GROUP'},
		0600
	);
	return $rs if($rs != 0);

	# userdb - End

	# Building, storage and installation of new file - End

	# Creates userdb.dat from the contents of userdb
	$rs = sys_command($main::cfg{'CMD_MAKEUSERDB'});
	return $rs if ($rs != 0);

	push_el(\@main::el, 'setup_po()', 'Ending...');

	0;
}

# IspCP Proftpd setup / update
# Build, store and install Proftpd main configuration files
# Create Ftpd Sql account if needed
sub setup_ftpd {

	push_el(\@main::el, 'setup_ftpd()', 'Starting...');

	# Do not generate cfg files if the service is disabled
	return 0 if($main::cfg{'CMD_FTPD'} =~ /^no$/i);

	my ($rs, $cmd, $rdata, $sql) = (undef, undef, undef, undef);

	my $cfg_tpl = undef;
	my $cfg = \$cfg_tpl;

	my $warn_msg = undef;

	# Directories paths
	my $cfg_dir = "$main::cfg{'CONF_DIR'}/proftpd";
	my $bk_dir = "$cfg_dir/backup";
	my $wrk_dir = "$cfg_dir/working";

	my $working_file = undef;

	# Sets the path to the configuration file - Begin

	if (!-e $main::cfg{'FTPD_CONF_FILE'}) {

		$rs = set_conf_val('FTPD_CONF_FILE', '/etc/proftpd/proftpd.conf');
		return $rs if ($rs != 0);

		$rs = store_conf();
		return $rs if ($rs != 0);
	}

	# Sets the path to the configuration file - End

	# Dedicated tasks for Install or Updates process - Begin

	# Install:
	if(!defined &update_engine) {

		# Saving the system configuration file if it exist
		if(-e $main::cfg{'FTPD_CONF_FILE'}) {
			$cmd = "$main::cfg{'CMD_CP'} -p $main::cfg{'FTPD_CONF_FILE'} $bk_dir/proftpd.conf.system";
			$rs = sys_command_rs($cmd);
			return $rs if($rs != 0);
		}

	# Update:
	} else {

		my $timestamp = time;

		# Saving the current production files if it exits
		if(-e $main::cfg{'FTPD_CONF_FILE'}) {
			$cmd = "$main::cfg{'CMD_CP'} -p $main::cfg{'FTPD_CONF_FILE'} $bk_dir/proftpd.conf.$timestamp";
			$rs = sys_command_rs($cmd);
			return $rs if($rs != 0);
		}

		# Get the current user and password for SQL connection and check it - Begin

		if(-e "$wrk_dir/proftpd.conf" ) {

			$working_file = "$wrk_dir/proftpd.conf";

		} elsif(-e "$main::cfg{'CONF_DIR'}/proftpd/backup/proftpd.conf.ispcp") {

			$working_file = "$main::cfg{'CONF_DIR'}/proftpd/backup/proftpd.conf.ispcp";

		} elsif(-e '/etc/proftpd.conf.bak') {

			$working_file = '/etc/proftpd.conf.bak';
		}

		# Loading working configuration file from /etc/ispcp/working/
		($rs, $rdata) = get_file($working_file);

		unless($rs) {

			if($rdata =~ /^SQLConnectInfo(?: |\t)+.*?(?: |\t)+(.*?)(?: |\t)+(.*?)\n/im) {

				# Check the database connection with current ids
				$rs = check_sql_connection($1, $2);

				# If the connection is successful, we can use these identifiers
				unless($rs) {
					$main::ua{'db_ftp_user'} = $1;
					$main::ua{'db_ftp_password'} = $2;
				} else {
					$warn_msg = "\n\tWARNING: Unable to connect to the database with authentication information" .
						"\n\tfound in your proftpd.conf file! We will create a new Ftpd Sql account.\n";
				}
			}

		} else {

			$warn_msg = "\n\tWARNING: Unable to find the Proftpd configuration file!" .
				"\n\tWe will create a new one.";
		}

		# Get the current user and password for SQL connection and check it - End

		# We ask the database ftp user and password, and we create new Sql ftp user account if needed
		if(!defined($main::ua{'db_ftp_user'}) || !defined($main::ua{'db_ftp_password'})) {

			print defined($warn_msg) ? $warn_msg :  "\n\tWARNING: Unable to retrieve your current username and/or" .
				"\n\tpassword for the Ftpd Sql account! We will create a new Ftpd Sql account.\n";

			do {
				$rs = ask_db_ftp_user();
			} while ($rs);

			do {
				$rs = ask_db_ftp_password();
			} while ($rs);

			# Setup of new Sql ftp user - Begin

			# First, we reset the db connection
			$main::db = undef;

			# Sets the dsn
			@main::db_connect = (
				"DBI:mysql:mysql:$main::db_host",
				$main::db_user,
				$main::db_pwd
			);

			# We ensure that news data doesn't exist in database - Begin

			$sql = "
				DELETE FROM
					tables_priv
				WHERE
					Host = '$main::cfg{'SERVER_HOSTNAME'}'
				AND
					Db = '$main::db_name'
				AND
					User = '$main::ua{'db_ftp_user'}'
				;
			";

			($rs, $rdata) = doSQL($sql);
			return $rs if ($rs != 0);

			$sql = "
				DELETE FROM
					user
				WHERE
					Host = '$main::db_host'
				AND
					User = '$main::ua{'db_ftp_user'}'
				;
			";

			($rs, $rdata) = doSQL($sql);
			return $rs if ($rs != 0);

			$sql = "FLUSH PRIVILEGES";
			($rs, $rdata) = doSQL($sql);
			return $rs if ($rs != 0);

			# We ensure that news data doesn't exist in database - End

			# Inserting new data into the database - Begin

			foreach(qw/ftp_group ftp_users quotalimits quotatallies/) {

				$sql = "
					GRANT SELECT,INSERT,UPDATE,DELETE ON
						$main::db_name.$_
					TO
						'$main::ua{'db_ftp_user'}'\@'$main::db_host'
					IDENTIFIED BY
						'$main::ua{'db_ftp_password'}'
					;
				";

				($rs, $rdata) = doSQL($sql);
				return $rs if ($rs != 0);
			}

			# Inserting new data into the database - End
		}
	}

	# Dedicated tasks for the Install or Updates process - End

	# Building, storage and installation of new file - Begin

	# Tags preparation
	my %tags_hash = (
		'{HOST_NAME}' => $main::cfg{'SERVER_HOSTNAME'},
		'{DATABASE_NAME}' => $main::db_name,
		'{DATABASE_HOST}' => $main::db_host,
		'{DATABASE_USER}' => $main::ua{'db_ftp_user'},
		'{DATABASE_PASS}' => $main::ua{'db_ftp_password'},
		'{FTPD_MIN_UID}' => $main::cfg{'APACHE_SUEXEC_MIN_UID'},
		'{FTPD_MIN_GID}' => $main::cfg{'APACHE_SUEXEC_MIN_GID'}
	);

	# Loading the template from /etc/ispcp/proftpd/
	($rs, $cfg_tpl) = get_file("$cfg_dir/proftpd.conf");
	return $rs if ($rs != 0);

	# Building the new file
	($rs, $$cfg) = prep_tpl(\%tags_hash, $cfg_tpl);
	return $rs if ($rs != 0);

	# Store the new file in working directory
	$rs = store_file(
		"$wrk_dir/proftpd.conf",
		$$cfg,
		$main::cfg{'ROOT_USER'},
		$main::cfg{'ROOT_GROUP'},
		0600
	);
	return $rs if ($rs != 0);

	# Install the new file in production directory
	$cmd = "$main::cfg{'CMD_CP'} -pf $wrk_dir/proftpd.conf $main::cfg{'FTPD_CONF_FILE'}";
	$rs = sys_command($cmd);
	return $rs if ($rs != 0);

	# Building, storage and installation of new file - End

	#
	# To fill ftp_traff.log file with something.
	#

	if (! -e "$main::cfg{'TRAFF_LOG_DIR'}/proftpd") {
		$rs = make_dir(
			"$main::cfg{'TRAFF_LOG_DIR'}/proftpd",
			$main::cfg{'ROOT_USER'},
			$main::cfg{'ROOT_GROUP'},
			0755
		);
		return $rs if ($rs != 0);
	}

	if(! -e "$main::cfg{'TRAFF_LOG_DIR'}$main::cfg{'FTP_TRAFF_LOG'}") {

		$rs = store_file(
			"$main::cfg{'TRAFF_LOG_DIR'}$main::cfg{'FTP_TRAFF_LOG'}",
			"\n",
			$main::cfg{'ROOT_USER'},
			$main::cfg{'ROOT_GROUP'},
			0644
		);
		return $rs if ($rs != 0);
	}

	push_el(\@main::el, 'setup_ftpd()', 'Ending...');

	0;
}

#  IspCP Daemon, network setup / update
#  Install or update the ispCP daemon and network init scripts
sub setup_ispcp_daemon_network {

	push_el(\@main::el, 'setup_ispcp_daemon_network()', 'Starting...');

	my ($rs, $rdata) = (undef, undef);

	my $filename = undef;

	my $services_log = (!defined &update_engine) ?
		'/tmp/ispcp-setup-services.log' : '/tmp/ispcp-update-services.log';


	foreach ($main::cfg{'CMD_ISPCPD'}, $main::cfg{'CMD_ISPCPN'}) {

		# Do not process if the service is disabled
		next if(/^no$/i);

		($filename) = /.*\/(.*)$/;

		$rs = sys_command_rs(
			"$main::cfg{'CMD_CHOWN'} $main::cfg{'ROOT_USER'}:$main::cfg{'ROOT_GROUP'} $_ &> $services_log"
		);
		return $rs if($rs != 0);

		$rs = sys_command_rs("$main::cfg{'CMD_CHMOD'} 0755 $_ &> $services_log");
		return $rs if($rs != 0);

		# Services installation / update (Debian, Ubuntu)
		# Todo Check it for Debian Squeeze
		if(-x '/usr/sbin/update-rc.d') {

			# Update task - The links should be removed first to be updated
			if(defined &update_engine) {
				sys_command_rs(
					"/usr/sbin/update-rc.d -f $filename remove &> $services_log"
				);
			}

			# ispcp_network should be stopped before the MySQL server (due to the
			# interfaces deletion process)
			if($filename eq 'ispcp_network') {
				sys_command_rs(
					"/usr/sbin/update-rc.d $filename defaults 99 20 &> $services_log"
				);
			} else {
				sys_command_rs(
					"/usr/sbin/update-rc.d $filename defaults 99 &> $services_log"
				);
			}

		# LSB 3.1 Core section 20.4 compatibility (ex. OpenSUSE > 10.1)
		} elsif(-x '/usr/lib/lsb/install_initd') {

			# Update task
			if(-x '/usr/lib/lsb/remove_initd' && defined &update_engine) {
				sys_command_rs("/usr/lib/lsb/remove_initd $_ &> $services_log");
			}

			sys_command_rs("/usr/lib/lsb/install_initd $_ &> $services_log");
			return $rs if ($rs != 0);
		}
	}

	push_el(\@main::el, 'setup_ispcp_daemon_network()', 'Ending...');

	0;
}

# IspCP GUI apache vhost setup / update
# Build, store and install ispCP GUI vhost configuration file
sub setup_gui_httpd {

	push_el(\@main::el, 'setup_gui_httpd()', 'Starting...');

	my ($rs, $cmd) = (undef, undef);

	my $cfg_tpl = undef;
	my $cfg = \$cfg_tpl;

	# Services log file path
	my $services_log_path = undef;

	# Directories paths
	my $cfg_dir = "$main::cfg{'CONF_DIR'}/apache";
	my $bk_dir = "$cfg_dir/backup";
	my $wrk_dir = "$cfg_dir/working";

	# Install:
	if(!defined &update_engine) {

		   $services_log_path = "/tmp/ispcp-setup-services.log";

	# Update:
	} else {

		$services_log_path = "/tmp/ispcp-update-services.log";

		my $timestamp = time();

		# Saving the current production file if it exists
		if(-e "$main::cfg{'APACHE_SITES_DIR'}/00_master.conf") {
			$cmd = "$main::cfg{'CMD_CP'} -p $main::cfg{'APACHE_SITES_DIR'}/00_master.conf $bk_dir/00_master.conf.$timestamp";

			$rs = sys_command_rs($cmd);
			return $rs if($rs != 0);
		}
	}

	# Building new configuration file - Begin

	# Loading the template from /etc/ispcp/apache
	($rs, $cfg_tpl) = get_file("$cfg_dir/00_master.conf");
	return $rs if($rs != 0);

	# Tags preparation
	my %tags_hash = (
		'{BASE_SERVER_IP}' => $main::cfg{'BASE_SERVER_IP'},
		'{BASE_SERVER_VHOST}' => $main::cfg{'BASE_SERVER_VHOST'},
		'{DEFAULT_ADMIN_ADDRESS}' => $main::cfg{'DEFAULT_ADMIN_ADDRESS'},
		'{ROOT_DIR}' => $main::cfg{'ROOT_DIR'},
		'{APACHE_WWW_DIR}' => $main::cfg{'APACHE_WWW_DIR'},
		'{APACHE_USERS_LOG_DIR}' => $main::cfg{'APACHE_USERS_LOG_DIR'},
		'{APACHE_LOG_DIR}' => $main::cfg{'APACHE_LOG_DIR'},
		'{PHP_STARTER_DIR}' => $main::cfg{'PHP_STARTER_DIR'},
		'{PHP_VERSION}' => $main::cfg{'PHP_VERSION'},
		'{WWW_DIR}' => $main::cfg{'ROOT_DIR'},
		'{DMN_NAME}' => 'gui',
		'{CONF_DIR}' => $main::cfg{'CONF_DIR'},
		'{MR_LOCK_FILE}' => $main::cfg{'MR_LOCK_FILE'},
		'{RKHUNTER_LOG}' => $main::cfg{'RKHUNTER_LOG'},
		'{CHKROOTKIT_LOG}' => $main::cfg{'CHKROOTKIT_LOG'},
		'{PEAR_DIR}' => $main::cfg{'PEAR_DIR'},
		'{OTHER_ROOTKIT_LOG}' => $main::cfg{'OTHER_ROOTKIT_LOG'},
		'{APACHE_SUEXEC_USER_PREF}' => $main::cfg{'APACHE_SUEXEC_USER_PREF'},
		'{APACHE_SUEXEC_MIN_UID}' => $main::cfg{'APACHE_SUEXEC_MIN_UID'},
		'{APACHE_SUEXEC_MIN_GID}' => $main::cfg{'APACHE_SUEXEC_MIN_GID'}
	);

	($rs, $$cfg) = prep_tpl(\%tags_hash, $cfg_tpl);
	return $rs if ($rs != 0);

	# Building new configuration file - End

	# Storage and installation of new file - Begin

	$rs = store_file(
		"$wrk_dir/00_master.conf",
		$$cfg,
		$main::cfg{'ROOT_USER'},
		$main::cfg{'ROOT_GROUP'},
		0644
	);
	return $rs if ($rs != 0);

	$cmd = "$main::cfg{'CMD_CP'} -pf $wrk_dir/00_master.conf $main::cfg{'APACHE_SITES_DIR'}/";

	$rs = sys_command_rs($cmd);
	return $rs if($rs != 0);

	# Storage and installation of new file - End

	# Disable 000-default vhost  - Begin

	if (-e "/usr/sbin/a2dissite") {
		sys_command_rs("/usr/sbin/a2dissite 000-default &> $services_log_path");
	}

	# Disable 000-default vhost  - End

	#
	## Disable the default NameVirtualHost directive - Begin
	#

	my $rdata = undef;

	if(-e '/etc/apache2/ports.conf') {

		# Loading the file
		($rs, $rdata) = get_file('/etc/apache2/ports.conf');
		return $rs if($rs != 0);

		# Disable the default NameVirtualHost directive
		$rdata =~ s/^NameVirtualHost \*:80/#NameVirtualHost \*:80/gmi;

		# Saving the modified file
		$rs = save_file('/etc/apache2/ports.conf', $rdata);
		return $rs if($rs != 0);
	}

	#
	## Disable the default NameVirtualHost directive - End
	#

	# Enable GUI vhost - Begin

	if (-e "/usr/sbin/a2ensite") {
		sys_command("/usr/sbin/a2ensite 00_master.conf &> $services_log_path");
	}

	# Enable GUI vhost - End

	push_el(\@main::el, 'setup_gui_httpd()', 'Ending...');

	0;
}

# ispCP GUI PHP configuration files - Setup / Update
# Create gui fcgi directory
# Build, store and install gui php related files (starter script, php.ini)
sub setup_gui_php {

	push_el(\@main::el, 'setup_gui_php()', 'Starting...');

	my ($rs, $cmd) = (undef, undef);

	my $cfg_tpl = undef;
	my $cfg =  \$cfg_tpl;

	my %tags_hash = ();

	my $cfg_dir = "$main::cfg{'CONF_DIR'}/fcgi";
	my $bk_dir = "$cfg_dir/backup";
	my $wrk_dir = "$cfg_dir/working";

	# Install:
	if(!defined &update_engine) {

		# Nothing todo here

	# Update:
	} else {

		my $timestamp = time();

		foreach(qw{php5-fcgi-starter php5/php.ini}) {

			if(-e "$main::cfg{'PHP_STARTER_DIR'}/master/$_") {
				my (undef, $file) = split('/');
				$file = $_ if(!defined $file);

				$cmd = "$main::cfg{'CMD_CP'} -p $main::cfg{'PHP_STARTER_DIR'}/master/$_ $bk_dir/master.$file.$timestamp";

				$rs = sys_command_rs($cmd);
				return $rs if($rs != 0);
			}
		}
	}

	# Create the fcgi directory for gui user if it doesn't exists - Begin

	$rs = make_dir(
		"$main::cfg{'PHP_STARTER_DIR'}/master/php5",
		$main::cfg{'ROOT_USER'},
		$main::cfg{'ROOT_GROUP'},
		0755
	);
	return $rs if ($rs != 0);

	# Create the fcgi directory for gui user if it doesn't exists - End

	# PHP5 Starter script - Begin

	# Loading the template from /etc/ispcp/fcgi/parts/master
	($rs, $cfg_tpl) = get_file("$cfg_dir/parts/master/php5-fcgi-starter.tpl");
	return $rs if ($rs != 0);

	# Tags preparation
	%tags_hash = (
		'{PHP_STARTER_DIR}' => $main::cfg{'PHP_STARTER_DIR'},
		'{PHP5_FASTCGI_BIN}' => $main::cfg{'PHP5_FASTCGI_BIN'},
		'{GUI_ROOT_DIR}' => $main::cfg{'GUI_ROOT_DIR'},
		'{DMN_NAME}' => 'master'
	);

	# Building the new file
	($rs, $$cfg) = prep_tpl(\%tags_hash, $cfg_tpl);
	return $rs if ($rs != 0);

	# Store the new file in working directory
	$rs = store_file(
		"$wrk_dir/master.php5-fcgi-starter",
		$$cfg,
		$main::cfg{'APACHE_SUEXEC_USER_PREF'} . $main::cfg{'APACHE_SUEXEC_MIN_UID'},
		$main::cfg{'APACHE_SUEXEC_USER_PREF'} . $main::cfg{'APACHE_SUEXEC_MIN_GID'},
		0755
	);
	return $rs if ($rs != 0);

	# Install the new file
	$cmd = "$main::cfg{'CMD_CP'} -pf $wrk_dir/master.php5-fcgi-starter $main::cfg{'PHP_STARTER_DIR'}/master/php5-fcgi-starter";

	$rs = sys_command($cmd);
	return $rs if ($rs != 0);

	# PHP5 Starter script - End

	# PHP5 php.ini file - Begin

	# Loading the template from /etc/ispcp/fcgi/parts/master/php5
	($rs, $cfg_tpl) = get_file("$cfg_dir/parts/master/php5/php.ini");
	return $rs if ($rs != 0);

	# Tags preparation
	%tags_hash = (
		'{WWW_DIR}' => $main::cfg{'ROOT_DIR'},
		'{DMN_NAME}' => 'gui',
		'{MAIL_DMN}' => $main::cfg{'BASE_SERVER_VHOST'},
		'{CONF_DIR}' => $main::cfg{'CONF_DIR'},
		'{MR_LOCK_FILE}' => $main::cfg{'MR_LOCK_FILE'},
		'{PEAR_DIR}' => $main::cfg{'PEAR_DIR'},
		'{RKHUNTER_LOG}' => $main::cfg{'RKHUNTER_LOG'},
		'{CHKROOTKIT_LOG}' => $main::cfg{'CHKROOTKIT_LOG'},
		'{OTHER_ROOTKIT_LOG}' => ($main::cfg{'OTHER_ROOTKIT_LOG'} ne '') ? ":$main::cfg{'OTHER_ROOTKIT_LOG'}" : ''
	);

	# Building the new file
	($rs, $$cfg) = prep_tpl(\%tags_hash, $cfg_tpl);
	return $rs if ($rs != 0);

	# Store the new file in working directory
	$rs = store_file(
		"$wrk_dir/master.php.ini",
		$$cfg,
		$main::cfg{'APACHE_SUEXEC_USER_PREF'} . $main::cfg{'APACHE_SUEXEC_MIN_UID'},
		$main::cfg{'APACHE_SUEXEC_USER_PREF'} . $main::cfg{'APACHE_SUEXEC_MIN_GID'},
		0644
	);
	return $rs if ($rs != 0);

	# Install the new file
	$cmd = "$main::cfg{'CMD_CP'} -pf $wrk_dir/master.php.ini $main::cfg{'PHP_STARTER_DIR'}/master/php5/php.ini";
	$rs = sys_command($cmd);
	return $rs if ($rs != 0);

	# PHP5 php.ini file - End

	push_el(\@main::el, 'setup_gui_php()', 'Ending...');

	0;
}

# IspCP GUI pma configuration file and pma slq controluser - Setup / Update
# Build, store and install ispCP GUI pma configuration file (config.inc.php)
sub setup_gui_pma {

	push_el(\@main::el, 'setup_gui_pma()', 'Starting...');

	my ($rs, $sql) = (undef, undef);

	my $cfg_file = undef;
	my $cfg =  \$cfg_file;

	my ($pma_sql_user, $pma_sql_password) = (undef, undef);
	my $hostname = undef;

	my $cfg_dir = "$main::cfg{'GUI_ROOT_DIR'}/tools/pma/";

	# Gets the pma configuration file
	($rs, $cfg_file) = get_file($cfg_dir . 'config.inc.php');
	return $rs if ($rs != 0);

	# Install
	if(!defined &update_engine) {

		$pma_sql_user = $main::ua{'db_pma_user'};
		$pma_sql_password = $main::ua{'db_pma_password'};
		$hostname = $main::ua{'db_host'};

	# Update:
	} else {

		if($cfg_file =~ /\{(?:HOSTNAME|PMA_USER|PMA_PASS|BLOWFISH|TMP_DIR)\}/) {

			print STDOUT colored(['bold yellow'], "\n\n\tWARNING: ") .
				"Your PMA configuration file should be rebuilded !\n";

			# Gets the new pma controluser username
			do {
				$rs = ask_db_pma_user();
			} while ($rs == 1);

			# Gets the new pma controluser password
			do {
				$rs = ask_db_pma_password();
			} while ($rs == 1);

			$pma_sql_user = $main::ua{'db_pma_user'};
			$pma_sql_password = $main::ua{'db_pma_password'};
			$hostname = $main::cfg{'DATABASE_HOST'}
		}
	}

	#
	# Create or update the PMA user if needed
	#

	if(defined $pma_sql_user && defined $pma_sql_password) {

		$main::db = undef;

		@main::db_connect = (
			"DBI:mysql:mysql:$main::db_host",
			$main::db_user,
			$main::db_pwd
		);

		#
		## We ensure the new user is not already registered and we remove the
		## old user if one exist
		#

		my $i = 0;

		foreach($main::cfg{'PMA_USER'}, $pma_sql_user) {

			if($main::cfg{'PMA_USER'} eq $pma_sql_user && $i == 0) {
				$i++;
				next;
			}

			$sql = "
				DELETE FROM
					tables_priv
				WHERE
					Host = '$hostname'
				AND
					Db = 'mysql' AND User = '$_'
				;
			";

			($rs, undef) = doSQL($sql);
			return $rs if ($rs != 0);

			$sql = "
				DELETE FROM
					user
				WHERE
					Host = '$hostname'
				AND
					User = '$_'
				;
			";

			($rs, undef) = doSQL($sql);
			return $rs if ($rs != 0);

			$sql = "
				DELETE FROM
					columns_priv
				WHERE
					Host = '$hostname'
				AND
					User = '$_'
				;
			";

			($rs, undef) = doSQL($sql);
			return $rs if ($rs != 0);
		}

		#
		## Flush Db privileges
		#

		($rs, undef) = doSQL('FLUSH PRIVILEGES');
		return $rs if ($rs != 0);

		#
		## Adds the new pma controluser
		#

		$sql = "
			GRANT USAGE ON
				mysql.*
			TO
				'$pma_sql_user'\@'$hostname'
			IDENTIFIED BY
				'$pma_sql_password'
			;
		";

		($rs, undef) = doSQL($sql);
		return $rs if ($rs != 0);

		#
		## Sets the rights for the pma control user
		#

		$sql = "
			GRANT SELECT (
				Host, User, Select_priv, Insert_priv,
				 Update_priv, Delete_priv, Create_priv,
				 Drop_priv, Reload_priv, Shutdown_priv,
				 Process_priv, File_priv, Grant_priv,
				 References_priv, Index_priv, Alter_priv,
				 Show_db_priv, Super_priv, Create_tmp_table_priv,
				 Lock_tables_priv, Execute_priv, Repl_slave_priv,
				 Repl_client_priv
			)
			ON
				mysql.user
			TO
				'$pma_sql_user'\@'$hostname'
			;
		";

		($rs, undef) = doSQL($sql);
		return $rs if ($rs != 0);

		$sql = "
			GRANT SELECT ON
				mysql.db
			TO
				'$pma_sql_user'\@'$hostname'
			;
		";

		($rs, undef) = doSQL($sql);
		return $rs if ($rs != 0);

		$sql = "
			GRANT SELECT ON
				mysql.host
			TO
				'$pma_sql_user'\@'$hostname'
			;
		";

		($rs, undef) = doSQL($sql);
		return $rs if ($rs != 0);

		$sql = "
			GRANT SELECT
				(Host, Db, User, Table_name, Table_priv, Column_priv)
			ON
				mysql.tables_priv
			TO
				'$pma_sql_user'\@'$hostname'
			;
		";

		($rs, undef) = doSQL($sql);
		return $rs if ($rs != 0);

		#
		## Insert pma user and password to config file
		## together with some other information
		#

		my $blowfish = gen_sys_rand_num(31);
		$blowfish =~ s/'/\\'/gi;

		# Tags preparation
		my %tag_hash = (
			'{PMA_USER}' => $pma_sql_user,
			'{PMA_PASS}' => $pma_sql_password,
			'{HOSTNAME}' => $hostname,
			'{TMP_DIR}'  => "$main::cfg{'GUI_ROOT_DIR'}/phptmp",
			'{BLOWFISH}' => $blowfish
		);

		# Building the file
		($rs, $$cfg) = prep_tpl(\%tag_hash, $cfg_file);
		return $rs if ($rs != 0);

		# Install the new file
		$rs = store_file(
			"$cfg_dir/config.inc.php",
			$$cfg,
			"$main::cfg{'APACHE_SUEXEC_USER_PREF'}$main::cfg{'APACHE_SUEXEC_MIN_UID'}",
			"$main::cfg{'APACHE_GROUP'}",
			0440
		);
		return $rs if ($rs != 0);

		#
		## Update the ispcp.conf file
		#

		$rs = set_conf_val('PMA_USER', $pma_sql_user);
		return $rs if ($rs != 0);

		$rs = store_conf();
		return $rs if ($rs != 0);

	}

	push_el(\@main::el, 'setup_gui_pma()', 'Ending...');

	0;
}

# IspCP Gui named configuration
# Add Gui named cfg data in main configuration file
# Building GUI named dns record's file
sub setup_gui_named {

	push_el(\@main::el, 'setup_gui_named()', 'Starting...');

	my $rs = undef;

	# Add GUI named cfg data
	$rs = setup_gui_named_cfg_data($main::cfg{'BASE_SERVER_VHOST'});
	return $rs if($rs != 0);

	# Building GUI named dns records file
	$rs = setup_gui_named_db_data(
		$main::cfg{'BASE_SERVER_IP'},
		$main::cfg{'BASE_SERVER_VHOST'}
	);
	return $rs if($rs != 0);

	push_el(\@main::el, 'setup_gui_named()', 'Ending...');

	0;
}

# IspCP Gui named cfg file Setup / Update
# Add Gui named cfg data in main configuration file
sub setup_gui_named_cfg_data {

	push_el(\@main::el, 'setup_gui_named_cfg_data()', 'Starting...');

	my ($base_vhost) = @_;

	my ($rs, $rdata, $cmd, $cfg) = (undef, undef, undef, undef);

	# Named directories paths
	my $cfg_dir = $main::cfg{'CONF_DIR'};
	my $tpl_dir = "$cfg_dir/bind/parts";
	my $bk_dir = "$cfg_dir/bind/backup";
	my $wrk_dir = "$cfg_dir/bind/working";
	my $db_dir = $main::cfg{'BIND_DB_DIR'};

	if (!defined($base_vhost) || $base_vhost eq '') {

		push_el(
			\@main::el,
			'setup_gui_named_cfg_data()',
			'FATAL: Undefined Input Data...'
		);
		return 1;
	}

	# Saving the current production file if it exists
	if(-e $main::cfg{'BIND_CONF_FILE'}) {

		my $timestamp = time();

		$cmd = "$main::cfg{'CMD_CP'} -p $main::cfg{'BIND_CONF_FILE'} $bk_dir/named.conf.$timestamp";
		$rs = sys_command_rs($cmd);
		return $rs if ($rs != 0);
	}

	#
	## Building of new configuration file - Begin
	#

	# Loading all needed templates from /etc/ispcp/bind/parts
	my ($entry_b, $entry_e, $entry) = ('', '', '');

	(	$rs,
		$entry_b,
		$entry_e,
		$entry
	) = get_tpl(
		$tpl_dir,
		'cfg_entry_b.tpl',
		'cfg_entry_e.tpl',
		'cfg_entry.tpl'
	);
	return $rs if ($rs != 0);

	# Preparation tags
	my %tags_hash = (
		'{DMN_NAME}' => $base_vhost,
		'{DB_DIR}' => $db_dir
	);

	# Replacement tags
	my ($entry_b_val, $entry_e_val, $entry_val) = ('', '', '');

	(	$rs,
		$entry_b_val,
		$entry_e_val,
		$entry_val
	) = prep_tpl(
		\%tags_hash,
		$entry_b,
		$entry_e,
		$entry
	);
	return $rs if ($rs != 0);

	# Loading working file from /etc/ispcp/bind/working/named.conf
	($rs, $cfg) = get_file("$wrk_dir/named.conf");
	return $rs if ($rs != 0);

	# Building the new configuration file
	my $entry_repl = "$entry_b_val$entry_val$entry_e_val\n$entry_b$entry_e";

	($rs, $cfg) = repl_tag(
		$entry_b,
		$entry_e,
		$cfg,
		$entry_repl,
		'setup_gui_named_cfg_data'
	);
	return $rs if ($rs != 0);

	#
	## Building the configuration file - End
	#

	#
	## Storage and installation of new file - Begin
	#

	# Store the new builded file in the working directory
	$rs = store_file(
		"$wrk_dir/named.conf",
		$cfg,
		$main::cfg{'ROOT_USER'},
		$main::cfg{'ROOT_GROUP'},
		0644
	);
	return $rs if ($rs != 0);

	# Install the new file in the production directory
	$cmd = "$main::cfg{'CMD_CP'} -pf $wrk_dir/named.conf $main::cfg{'BIND_CONF_FILE'}";
	$rs = sys_command_rs($cmd);
	return $rs if ($rs != 0);

	#
	## Storage and installation of new file - End
	#

	push_el(\@main::el, 'setup_gui_named_cfg_data()', 'Ending...');

	0;
}

# IspCP Gui named dns record's Setup / Update
# Building GUI named dns record's file
sub setup_gui_named_db_data {

	push_el(\@main::el, 'setup_gui_named_db_data()', 'Starting...');

	my ($base_ip, $base_vhost) = @_;

	# Slave DNS  - Address IP
	my $sec_dns_ip = $main::cfg{'SECONDARY_DNS'};

	my ($rs, $cmd) = (undef, undef);

	# SOA Record - Serial number related data
	my ($serial, $otime, $ctime, $rev_nbr) = (undef, '', undef, undef);

	# Directories paths
	my $cfg_dir = "$main::cfg{'CONF_DIR'}/bind";
	my $tpl_dir = "$cfg_dir/parts";
	my $bk_dir = "$cfg_dir/backup";
	my $wrk_dir = "$cfg_dir/working";
	my $db_dir = $main::cfg{'BIND_DB_DIR'};

	# Zone file name
	my $db_fname = "$base_vhost.db";

	# Named zone files paths
	my $sys_cfg = "$db_dir/$db_fname";
	my $wrk_cfg = "$wrk_dir/$db_fname";
	my $bk_cfg = "$bk_dir/$db_fname";

	if (!defined($base_vhost) || $base_vhost eq '') {
		push_el(
			\@main::el,
			'add_named_db_data()',
			'FATAL: Undefined Input Data...'
		);

		return 1;
	}

	#
	## Dedicated tasks for the Install or Updates process - Begin
	#

	# Update
	if (defined &update_engine) {

		my $timestamp = time();

		# Saving the current production file if it exists
		if(-e $sys_cfg) {

			$cmd = "$main::cfg{'CMD_CP'} -p $sys_cfg $bk_cfg.$timestamp";
			$rs = sys_command_rs($cmd);
			return $rs if ($rs != 0);
		}

		#
		## Get the time and revision number data in SOA record - Begin
		#

		# First, loading the current working db file
		($rs, $_) = get_file($wrk_cfg);
		return $rs if($rs != 0);

		# Extraction of old time data and revision number
		unless ( ($otime, $rev_nbr) = /^.+?(\d{8})(\d{2}).*?;/s ) {
			push_el(
				\@main::el,
				'add_named_db_data()',
				"FATAL: Can't retrieve the serial number in the master domain SOA record..."
			);

			return 1;
		}

		#
		## Get the time and revision number data in SOA record - End
		#
	}

	#
	## Dedicated tasks for the Install or Updates process - End
	#

	#
	## Building the serial for SOA record (according RFC 1912) - Begin
	#

	# Get the current time in human readable format
	my (undef, undef, undef, $mday, $mon, $year) = localtime;
	$ctime = sprintf '%4d%02d%02d', $year+1900, $mon+1, $mday;

	# Building the new serial
	$serial = $ctime . (($otime eq $ctime) ? ++$rev_nbr : '00');

	#
	## Building the serial for SOA record (according RFC 1912) - End
	#

	#
	## Building new configuration file - Begin
	#

	# Loading the template from /etc/ispcp/bind/parts
	my $entry = '';
	($rs, $entry) = get_tpl($tpl_dir, 'db_master_e.tpl');
	return $rs if ($rs != 0);

	# Tags preparation
	my %tags_hash = (
		'{DMN_NAME}' => $base_vhost,
		'{DMN_IP}' => $base_ip,
		'{BASE_SERVER_IP}' => $base_ip,
		'{SECONDARY_DNS_IP}' => ($sec_dns_ip) ? $sec_dns_ip : $base_ip ,
		'{TIMESTAMP}' => $serial
	);

	# Replacement tags
	($rs, $entry) = prep_tpl( \%tags_hash, $entry);
	return $rs if ($rs != 0);

	#
	## Building new configuration file - End
	#

	#
	## Storage and installation of new file - Begin
	#

	# Store the new builded file in the working directory
	$rs = store_file(
		$wrk_cfg,
		$entry,
		$main::cfg{'ROOT_USER'},
		$main::cfg{'ROOT_GROUP'},
		0644
	);
	return $rs if ($rs != 0);

	# Install the new file in the production directory
	$cmd = "$main::cfg{'CMD_CP'} -pf $wrk_cfg $db_dir/";
	$rs = sys_command_rs($cmd);
	return $rs if ($rs != 0);

	#
	## Storage and installation of new file - End
	#

	push_el(\@main::el, 'setup_gui_named_db_data()', 'Ending...');

	0;
}

# If the /etc/default/rkhunter file exists :
# During update, remove the old log files
# For both, disable the daily runs of the default rkhunter cron task
sub setup_rkhunter {

	push_el(\@main::el, 'setup_rkhunter()', 'Starting...');

	my ($rs, $rdata, $cmd) = (undef, undef, undef);

	if(-e '/etc/default/rkhunter') {

		if(defined &update_engine) {

			# Deleting files that can cause problems
			$cmd = "$main::cfg{'CMD_RM'} -f $main::cfg{'RKHUNTER_LOG'}*";
			$rs = sys_command_rs($cmd);
			return $rs if($rs != 0);
		}

		($rs, $rdata) = get_file('/etc/default/rkhunter');
		return $rs if($rs != 0);

		# Disable the daily runs of the default rkhunter cron task
		$rdata =~ s/CRON_DAILY_RUN="yes"/CRON_DAILY_RUN="no"/gmi;

		# Saving the modified file
		$rs = save_file('/etc/default/rkhunter', $rdata);
		return $rs if($rs != 0);
	}

	push_el(\@main::el, 'setup_rkhunter()', 'Ending...');

	0;
}

# Remove all's empty files in ispCP configuration directories
sub setup_cleanup {

	push_el(\@main::el, 'setup_cleanup()', 'Ending...');

	my ($rs, $cmd) = (undef, undef);

	$cmd = "$main::cfg{'CMD_RM'} -f $main::cfg{'CONF_DIR'}/*/*/empty-file";
	$rs = sys_command_rs($cmd);
	return $rs if($rs != 0);

	push_el(\@main::el, 'setup_cleanup()', 'Ending...');

	0;
}

#
## Setup / Update subroutines - End
#

#
## Others subroutines - Begin
#

# Get and return the fully qualified hostname
sub get_sys_hostname {

	push_el(\@main::el, 'get_sys_hostname()', 'Starting...');

	my ($rs, $rdata) = (undef, undef);

	my $cmd = "$main::cfg{'CMD_HOSTNAME'} 1>/tmp/ispcp-setup.hostname";

	$rs = sys_command($cmd);
	return ($rs, '') if ($rs != 0);

	($rs, $rdata) = get_file("/tmp/ispcp-setup.hostname");
	return ($rs, '') if ($rs != 0);

	chomp($rdata);

	# If hostname contains no dot -> use "hostname -f"
    if ($rdata !~ /\./) {

		my $cmd = "$main::cfg{'CMD_HOSTNAME'} -f 1>/tmp/ispcp-setup.hostname";

		$rs = sys_command($cmd);
		return ($rs, '') if ($rs != 0);

		($rs, $rdata) = get_file("/tmp/ispcp-setup.hostname");
		return ($rs, '') if ($rs != 0);

		chomp($rdata);
    }

	$rs = del_file("/tmp/ispcp-setup.hostname");
	return ($rs, '') if ($rs != 0);

	push_el(\@main::el, 'get_sys_hostname()', 'Ending...');

	return (0, $rdata);
}

# Check ip
sub check_eth {

	return 0 if(
		shift =~ /^(\d+)\.(\d+)\.(\d+)\.(\d+)$/ &&
		($1 >  0) && ($1 <  255) && ($2 >= 0) && ($2 <= 255) &&
		($3 >= 0) && ($3 <= 255) && ($4 >  0) && ($4 <  255)
	);

	1;
}

# Check Sql connection
# This subroutine can check the connections Sql
#
# [param: string Sql username]
# [param: string Sql plaintext password]
#
sub check_sql_connection {

	push_el(\@main::el, 'sql_check_connections()', 'Starting...');

	my ($user, $password) = @_;
	my ($rs, $rdata) = (undef, undef);

	# First, we reset db connection
	$main::db = undef;

	# If we received username and password, we redefine the dsn
	if(defined $user && defined $password ) {
		@main::db_connect = (
			"DBI:mysql:$main::db_name:$main::db_host",
			$user,
			$password
		);
	}

	($rs, $rdata) = doSQL('show databases;');
	return $rs if ($rs != 0);

	# We reset the connection and restore the previous DSN
	$main::db = undef;

	@main::db_connect = (
		"DBI:mysql:$main::db_name:$main::db_host",
		$main::db_user,
		$main::db_pwd
	);

	push_el(\@main::el, 'sql_check_connections()', 'Ending...');

	0;
}

# Starting preinstallation script
# Note : In the future, a preinst script will automatically
# install the required packages and will also perform other
# tasks as rename, move directories that may be helpful as
# part of an update.
sub preinst {

	push_el(\@main::el, 'preinst()', 'Starting...');

	my $task = shift;

	my ($rs, $cmd) = (undef, undef);

	my $mime_type = mimetype("$main::cfg{'ROOT_DIR'}/engine/setup/preinst");

	($mime_type =~ /(shell|perl|php)/) ||
		exit_msg('ERROR: Unable to determine the mimetype of preinstallation script.');

	$cmd = "$main::cfg{'CMD_'.uc($1)} preinst $task";
	$rs = sys_command_rs($cmd);
	return $rs if($rs != 0);

	push_el(\@main::el, 'preinst()', 'Ending...');

	0;
}

# Starting postinstallation script
# The postinst is the ideal place to perform tasks Post Installation.
# For example, the script 'postinst' who's provided for the openSUSE
# distribution can perform administrative tasks that are not supported
# by the scripts that are common to all distributions.
sub postinst {

	push_el(\@main::el, 'postinst()', 'Starting...');

	my $task = shift;

	my ($rs, $cmd) = (undef, undef);

	my $mime_type = mimetype("$main::cfg{'ROOT_DIR'}/engine/setup/postinst");

	($mime_type =~ /(shell|perl|php)/) ||
		exit_msg('ERROR: Unable to determine the mimetype of postinstallation script.');

	$cmd = "$main::cfg{'CMD_'.uc($1)} postinst $task";
	$rs = sys_command_rs($cmd);
	return $rs if($rs != 0);

	push_el(\@main::el, 'postinst()', 'Ending...');

	0;
}

# Format a string for it to be placed on the right
# of another string. The first string should not end
# with EOL.
#
# param: string $msg the message to be placed on right
# param: int $left_msg_lenght: lenght of left message
# return: string right formated message
sub str_to_right {

	my ($msg, $left_msg_lenght) = @_;

	my ($wchar) = GetTerminalSize();

	# 8 chars are a normal tabwidht in bash
	my $sep = ($wchar - ($left_msg_lenght + 8));

	return sprintf('%'.$sep."s\n", $msg);
}

# Exit with an optional error message
#
# If the message is the '[failed]' string, it will be
# re-formatted and an additional message will be displayed.
#
# [param: string error message]
# [param: int exit code]
#
sub exit_msg {

	push_el(\@main::el, 'exit_msg()', 'Starting...');

	my ($msg, $code) = @_;

	if (!defined($code) || $code <= 0 ) {
		$code = 1;
	}

	if (defined($msg) && $msg ne '' ) {

		if($msg =~ /\[failed\]/) {

		$msg = colored(['bold red'], $msg) . "\n";

		my $final_msg = "\n\t" . colored(['red'], 'FATAL:')  .
			" An error was occured during update process!\n" .
			"\tCorrect it and re-run this program." .
			"\n\n\tYou can find help at http://isp-control.net/forum\n\n";

		print STDERR $msg, $final_msg;

		} else {
			print STDERR "\n\t$msg\n";
		}
	}

	push_el(\@main::el, 'exit_msg()', 'Ending...');

	exit $code;
}

# Starting services
sub start_services {

	push_el(\@main::el, 'start_services()', 'Starting...');

	foreach(
		qw/CMD_ISPCPN CMD_ISPCPD
		CMD_NAMED
		CMD_HTTPD CMD_FTPD
		CMD_MTA CMD_AUTHD
		CMD_POP CMD_POP_SSL
		CMD_IMAP CMD_IMAP_SSL/
	) {
		if( $main::cfg{$_} !~ /^no$/i && -e $main::cfg{$_}) {

			sys_command("$main::cfg{$_} start &>/tmp/ispcp-update-services.log");
			print STDOUT BOLD BLACK '.' if(defined &update_engine);
			sleep 1;
		}
	}

	push_el(\@main::el, 'start_services()', 'Ending...');

	0;
}

# Stopping services
# Stop all service who are marked as 'no' in ispcp.conf
sub stop_services {

	push_el(\@main::el, 'stop_services()', 'Starting...');

	foreach(
		qw/CMD_ISPCPN CMD_ISPCPD
		CMD_NAMED
		CMD_HTTPD CMD_FTPD
		CMD_MTA CMD_AUTHD
		CMD_POP CMD_POP_SSL
		CMD_IMAP CMD_IMAP_SSL/
	) {
		if( $main::cfg{$_} !~ /^no$/i && -e $main::cfg{$_}) {

			sys_command("$main::cfg{$_} stop &>/tmp/ispcp-update-services.log");
			print STDOUT BOLD BLACK '.' if(defined &update_engine);
			sleep 1;
		}
	}

	push_el(\@main::el, 'stop_services()', 'Ending...');

	0;
}

#
## Others Setup / Update subroutines - End
#

1;
