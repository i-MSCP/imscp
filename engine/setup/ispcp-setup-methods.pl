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

# Note to dev:
#
# It's important for the recovery process that all the subroutines defined here
# are idempotent. That wants mean that if a subroutine is called again and
# again, the final result should be the same. For example, if an error occurs
# and the program ends, and then the script is run again, the final result
# should be identical as if the script was run once.

use strict;
use warnings;
use version 0.74;
use DateTime;
use DateTime::TimeZone;
use Net::LibIDN qw/idn_to_ascii idn_to_unicode/;
use feature 'state';

# Hide the 'used only once: possible typo' warnings
no warnings 'once';

# Ensuring that the log directory exists
my $rs = makepath(
	"$main::cfg{'LOG_DIR'}/setup", $main::cfg{'ROOT_USER'},
	$main::cfg{'ROOT_GROUP'}, 0755
);

die("Unable to create ispCP log directory $!\n") unless $rs == 0;

# Always dump the log at end
END {
	@main::el = reverse(@main::el);
	dump_el(\@main::el, $main::logfile);
}

################################################################################
##                              Ask subroutines                                #
################################################################################

################################################################################
# Ask for system hostname
#
# @return int 0 on success, -1 otherwise
#
sub ask_hostname {

	push_el(\@main::el, 'ask_hostname()', 'Starting...');

	my $hostname = get_sys_hostname();
	return -1 if ($rs != 0);

	print "\n\tPlease enter a fully qualified hostname. [$hostname]: ";
	chomp(my $rdata = <STDIN>);

	$rdata = $hostname if $rdata eq '';

	# Checking hostname
	if(isValidHostname($rdata)) {
		my @labels = split '\.', $rdata;

		# Checking for fully qualified hostname
		if(@labels < 3) {
			print colored( ['bold yellow'], "\n\t[WARNING] ") .
				"$rdata is not a 'fully qualified hostname'.\n\t" .
				"Be aware you cannot use this domain for websites.\n";

			print "\n\tAre you sure you want to use this hostname? [Y/n]: ";
			chomp(my $retVal = <STDIN>);

			if($retVal ne '' && $retVal !~ /^(?:yes|y)$/i) {
				return -1;
			}
		}

		$main::ua{'hostname'} = $rdata;
		$main::ua{'hostname_local'} = shift(@labels);
	} else {
		print colored(['bold red'], "\n\t[ERROR] ") .
			"Hostname is not a valid domain name!\n";

		return -1;
	}

	push_el(\@main::el, 'ask_hostname()', 'Ending...');

	0;
}

################################################################################
# Ask for Ip address
#
# @return int 0 on success, -1 otherwise. Exit on unrecoverable error
# @todo Admin should be able to choose another Network Card
#
sub ask_eth {

	push_el(\@main::el, 'ask_eth()', 'Starting...');

	my $ipAddr = getEthAddr();

	print "\n\tPlease enter the system network address. [$ipAddr]: ";
	chomp(my $rdata = <STDIN>);

	$main::ua{'eth_ip'} = (!defined $rdata || $rdata eq '') ? $ipAddr : $rdata;

	if(!isValidAddr($main::ua{'eth_ip'})) {
		print colored(['bold red'], "\n\t[ERROR] ") .
			"Ip address not valid, please retry!\n";

		return -1;
	}

	push_el(\@main::el, 'ask_eth()', 'Ending...');

	0;
}

################################################################################
# Ask for ispCP Frontend vhost name
#
# @return int 0 on success, -1 otherwise
#
sub ask_vhost {

	push_el(\@main::el, 'ask_vhost()', 'Starting...');

	my $vhost = idn_to_unicode('admin.' . get_sys_hostname(), 'utf8');

	print "\n\tPlease enter the domain name from where ispCP OMEGA will " .
		"be\n\treachable [$vhost]: ";
	chomp(my $rdata = <STDIN>);

	if ($rdata eq '') {
		$main::ua{'admin_vhost'} = $vhost;
	} elsif (isValidHostname($rdata)) {
		$main::ua{'admin_vhost'} = $rdata;
	} else {
		print colored(['bold red'], "\n\t[ERROR] ") .
			"Vhost name is not valid!\n";

		return -1;
	}

	push_el(\@main::el, 'ask_vhost()', 'Ending...');

	0;
}

################################################################################
# Ask for SQL hostname
#
# @return 0 on success, -1 on failure
#
sub ask_db_host {

	push_el(\@main::el, 'ask_db_host()', 'Starting...');

	print "\n\tPlease enter SQL server host. [localhost]: ";
	chomp(my $rdata = <STDIN>);

	$rdata = ($rdata eq '') ? 'localhost' : $rdata;

	if($rdata ne 'localhost' && !isValidHostname($rdata)) {
		print colored(['bold red'], "\n\t[ERROR] ") .
			"Wrong SQL hostname! See RFC 1123 for more information...\n";

		return -1;
	}

	$main::ua{'db_host'} = $rdata;

	push_el(\@main::el, 'ask_db_host()', "Ending...");

	0;
}

################################################################################
# Ask for ispCP database name
#
# @return void
# @todo: Add check on input data
#
sub ask_db_name {

	push_el(\@main::el, 'ask_db_name()', 'Starting...');

	print "\n\tPlease enter system SQL database. [ispcp]: ";
	chomp(my $rdata = <STDIN>);

	$main::ua{'db_name'} = ($rdata eq '') ? 'ispcp' : $rdata;

	push_el(\@main::el, 'ask_db_name()', 'Ending...');
}

################################################################################
# Ask for ispCP SQL user
#
# @return void
# @todo: Add check on input data (only ASCII (recommended) and maxlength 16)
#
sub ask_db_user {

	push_el(\@main::el, 'ask_db_user()', 'Starting...');

	print "\n\tPlease enter system SQL user. [root]: ";
	chomp(my $rdata = <STDIN>);

	$main::ua{'db_user'} = ($rdata eq '') ? 'root' : $rdata;

	push_el(\@main::el, 'ask_db_user()', 'Ending...');
}

################################################################################
# Ask for ispCP SQL password
#
# @return int 0 on success, -1 otherwise
#
sub ask_db_password {

	push_el(\@main::el, 'ask_db_password()', 'Starting...');

	my $pass1 = read_password("\n\tPlease enter system SQL password. [none]: ");

	if (!defined $pass1 || $pass1 eq '') {
		$main::ua{'db_password'} = '';
	} else {
		my $pass2 = read_password("\tPlease repeat system SQL password: ");

		if ($pass1 eq $pass2) {
			$main::ua{'db_password'} = $pass1;
		} else {
			print colored(['bold red'], "\n\t[ERROR] ") .
				"Passwords do not match!\n";

			return -1;
		}
	}

	push_el(\@main::el, 'ask_db_password()', 'Ending...');

	0;
}

################################################################################
# Ask for database Ftp user name
#
# @return int 0 on success, -1 otherwise
#
sub ask_db_ftp_user {

	push_el(\@main::el, 'ask_db_ftp_user()', 'Starting...');

	print "\n\tPlease enter ispCP ftp SQL user. [vftp]: ";
	chomp(my $rdata = <STDIN>);

	if ($rdata eq '') {
		$main::ua{'db_ftp_user'} = 'vftp';
	} elsif($rdata eq $main::ua{'db_user'}) {
		print colored(['bold red'], "\n\t[ERROR] ") .
			"Ftp SQL user must not be identical to the system SQL user!\n";

		return -1;
	} else {
		$main::ua{'db_ftp_user'} = $rdata;
	}

	push_el(\@main::el, 'ask_db_ftp_user()', 'Ending...');

	0;
}

################################################################################
# Ask for database Ftp user password
#
# @return int 0 on success, -1 otherwise
#
sub ask_db_ftp_password {

	push_el(\@main::el, 'ask_db_ftp_password()', 'Starting...');

	my ($rs, $pass1, $pass2, $dbPassword);

	$pass1 = read_password(
		"\n\tPlease enter ispCP ftp SQL user password. [auto generate]: "
	);

	if (!defined $pass1  || $pass1 eq '') {
		$dbPassword = gen_sys_rand_num(18);
		$dbPassword =~ s/('|"|`|#|;)//g;
		$main::ua{'db_ftp_password'} = $dbPassword;

		print "\tispCP ftp SQL user password set to: $dbPassword\n";
	} else {
		$pass2 = read_password("\tPlease repeat ispCP ftp SQL user password: ");

		if ($pass1 eq $pass2) {
			$main::ua{'db_ftp_password'} = $pass1;
		} else {
			print colored(['bold red'], "\n\t[ERROR] ") .
				"Passwords do not match!\n";

			return -1;
		}
	}

	push_el(\@main::el, 'ask_db_ftp_password()', 'Ending...');

	0;
}

################################################################################
# Ask for ispCP Frontend first admin name
#
# @return void
# @todo: Add check on user input data
#
sub ask_admin {

	push_el(\@main::el, 'ask_admin()', 'Starting...');

	print "\n\tPlease enter administrator login name. [admin]: ";
	chomp(my $rdata = <STDIN>);

	$main::ua{'admin'} = ($rdata eq '') ? 'admin' : $rdata;

	push_el(\@main::el, 'ask_admin()', 'Ending...');
}

################################################################################
# Ask for ispCP Frontend first admin password
#
# @return int 0 on success, -1 otherwise
#
sub ask_admin_password {

	push_el(\@main::el, 'ask_admin_password()', 'Starting...');

	my $pass1 = read_password("\n\tPlease enter administrator password: ");

	if (!defined $pass1 || $pass1 eq '') {
		print colored(['bold red'], "\n\t[ERROR] ") .
			 "Password cannot be empty!\n";

		return -1;
	} else {
		if (length $pass1 < 5) {
			print colored(['bold red'], "\n\t[ERROR] ") .
				"Password too short!\n";

			return -1;
		}

		my $pass2 = read_password("\tPlease repeat administrator password: ");

		if ($pass1 =~ m/[a-zA-Z]/ && $pass1 =~ m/[0-9]/) {
			if ($pass1 eq $pass2) {
				$main::ua{'admin_password'} = $pass1;
			} else {
				print colored(['bold red'], "\n\t[ERROR] ") .
					"Passwords do not match!\n";

				return -1;
			}
		} else {
			print colored(['bold red'], "\n\t[ERROR] ") .
				"Passwords must contain at least digits and chars!\n";

			return -1;
		}
	}

	push_el(\@main::el, 'ask_admin_password()', 'Ending...');

	0;
}

################################################################################
# Ask for ispCP Frontend first admin email
#
# @return int 0 on success, -1 otherwise
#
sub ask_admin_email {

	push_el(\@main::el, 'ask_admin_email()', 'Starting...');

	print "\n\tPlease enter administrator e-mail address: ";
	chomp(my $rdata = <STDIN>);

	if($rdata eq '' || !isValidEmail($rdata)) {
		print colored(['bold red'], "\n\t[ERROR] ") .
			"E-mail address not valid!\n";

		return -1;
	}

	$main::ua{'admin_email'} = $rdata;

	push_el(\@main::el, 'ask_admin_email()', 'Ending...');

	0;
}

################################################################################
# Ask for slave DNS
#
# @return int 0 on success, -1 otherwise
#
sub ask_second_dns {

	push_el(\@main::el, 'ask_second_dns()', 'Starting...');

	print "\n\tIP of Secondary DNS. (optional) []: ";
	chomp(my $rdata = <STDIN>);

	if (!defined $rdata || $rdata eq '') {
		$main::ua{'secondary_dns'} = '';
	} elsif(isValidAddr($rdata)) {
		$main::ua{'secondary_dns'} = $rdata;
	} else {
		print colored(['bold red'], "\n\t[ERROR] ") .
			"Ip address not valid, please retry!\n";

		return -1;
	}

	push_el(\@main::el, 'ask_second_dns()', 'Ending...');

	0;
}

################################################################################
# Ask for adding nameserver in the resolv.conf file
#
# @return int 0 on success, -1 otherwise
# @todo finish implementation
#
sub ask_resolver {

	push_el(\@main::el, 'ask_resolver()', 'Starting...');

	print "\n\tDo you want allow the system resolver to use the " .
	"local nameserver\n\tsets by ispCP ? [Y/n]: ";
	chomp(my $rdata = <STDIN>);

	if ($rdata eq '' || $rdata =~ /^(?:(y|yes)|(n|no))$/i) {
		$main::ua{'resolver'} = ! defined $2 ? 'yes' : 'no';
	} else {
		print colored(['bold red'], "\n\t[ERROR] ") .
			"You entered an unrecognized value!\n";

		return -1;
	}

	push_el(\@main::el, 'ask_resolver()', 'Ending...');

	0;
}

################################################################################
# Ask for MySQL prefix
#
# @return int 0 on success, -1 otherwise
#
sub ask_mysql_prefix {

	push_el(\@main::el, 'ask_mysql_prefix()', 'Starting...');

	print "\n\tUse MySQL Prefix.\n\tPossible values: " .
		"[i]nfront, [b]ehind, [n]one. [none]: ";
	chomp(my $rdata = <STDIN>);

	if ($rdata eq '' || $rdata eq 'none' || $rdata eq 'n') {
		$main::ua{'mysql_prefix'} = 'no';
		$main::ua{'mysql_prefix_type'} = '';
	} elsif ($rdata eq 'infront' || $rdata eq 'i') {
		$main::ua{'mysql_prefix'} = 'yes';
		$main::ua{'mysql_prefix_type'} = 'infront';
	} elsif ($rdata eq 'behind' || $rdata eq 'b') {
		$main::ua{'mysql_prefix'} = 'yes';
		$main::ua{'mysql_prefix_type'} = 'behind';
	} else {
		print colored(['bold red'], "\n\t[ERROR] ") .
			"Not allowed Value, please retry!\n";

		return -1;
	}

	push_el(\@main::el, 'ask_mysql_prefix()', 'Ending...');

	0;
}

################################################################################
# Ask for PhpMyAdmin control user name
#
# @return int 0 on success, -1 otherwise
#
sub ask_db_pma_user {

	push_el(\@main::el, 'ask_db_pma_user()', 'Starting...');

	if(defined &update_engine) {
		$main::ua{'db_user'} = $main::cfg{'DATABASE_USER'};
	}

	print "\n\tPlease enter ispCP phpMyAdmin Control user. " .
		"[$main::cfg{'PMA_USER'}]: ";
	chomp(my $rdata = <STDIN>);

	if ($rdata eq '') {
		$main::ua{'db_pma_user'} = $main::cfg{'PMA_USER'}
	} elsif($rdata eq $main::ua{'db_user'}) {
		print colored(['bold red'], "\n\t[ERROR] ") .
			"PhpMyAdmin Control user must not be identical to system SQL user!\n";

		return 1;
	} elsif ($rdata eq $main::ua{'db_ftp_user'}) {
		print colored(['bold red'], "\n\t[ERROR] ") .
			"PhpMyAdmin Control user must not be identical to ftp SQL user!\n";

		return -1;
	} else {
		$main::ua{'db_pma_user'} = $rdata;
	}

	push_el(\@main::el, 'ask_db_pma_user()', 'Ending...');

	0;
}

################################################################################
# Ask for PhpMyAdmin control user password
#
# @return int 0 on success, -1 otherwise
#
sub ask_db_pma_password {

	push_el(\@main::el, 'ask_db_pma_password()', 'Starting...');

	my $pass1 = read_password(
		"\n\tPlease enter ispCP PhpMyAdmin Control user password. " .
		"[auto generate]: "
	);

	if (!defined $pass1 || $pass1 eq '') {
		my $dbPassword = gen_sys_rand_num(18);
		$dbPassword =~ s/('|"|`|#|;)//g;
		$main::ua{'db_pma_password'} = $dbPassword;

		print "\tPhpMyAdmin Control user password set to: $dbPassword\n";
	} else {
		my $pass2 = read_password(
			"\tPlease repeat ispCP PhpMyAdmin Control user password: "
		);

		if ($pass1 eq $pass2) {
			$main::ua{'db_pma_password'} = $pass1;
		} else {
			print colored(['bold red'], "\n\t[ERROR] ") .
				"Passwords do not match!\n";

			return -1;
		}
	}

	push_el(\@main::el, 'ask_db_pma_password()', 'Ending...');

	0;
}

################################################################################
# Ask for Apache fastCGI module (fcgid|fastcgi)
#
# @return int 0 on success, -1 otherwise
#
sub ask_fastcgi {

	push_el(\@main::el, 'ask_fastcgi()', 'Starting...');

	print "\n\tFastCGI Version: [f]cgid or fast[c]gi. [fcgid]: ";
	chomp(my $rdata = <STDIN>);

	if ($rdata eq '' || $rdata eq 'fcgid' || $rdata eq 'f') {
		$main::ua{'php_fastcgi'} = 'fcgid';
	} elsif ($rdata eq 'fastcgi' || $rdata eq 'c') {
		$main::ua{'php_fastcgi'} = 'fastcgi';
	} else {
		print colored(['bold red'], "\n\t[ERROR] ") .
			"Only '[f]cgid' or 'fast[c]gi' are allowed!\n";

		return -1
	}

	push_el(\@main::el, 'ask_fastcgi()', 'Ending...');

	0;
}

################################################################################
# Ask for default timezone
#
# @return int 0 on success, -1 otherwise
#
sub ask_timezone {

	push_el(\@main::el, 'ask_timezone()', 'Starting...');

	# Get the user's default timezone
	my ($sec, $min, $hour, $mday, $mon, $year, @misc) = localtime;
	my $datetime  = DateTime->new(
		year => $year + 1900, month => $mon, day => $mday, hour => $hour,
		minute => $min, second => $sec, time_zone => 'local'
	);

	my $timezone_name = $datetime->time_zone_long_name();

	print "\n\tServer's Timezone [$timezone_name]: ";
	chomp(my $rdata = <STDIN>);

	# Copy $timezone_name to $rdata if $rdata is empty
	$rdata = $timezone_name if !defined $rdata || $rdata eq '';

	# DateTime::TimeZone::is_olson exits with die if the given data is not valid
	# eval catches the die() and keeps this program alive
	eval {
		my $timezone = DateTime::TimeZone->new(name => $rdata);
		$timezone->is_olson;
	};

	my $error = ($@) ? 1 : 0; # $@ contains the die() message

	if ($error == 1) {
		print colored(['bold red'], "\n\t[ERROR] ") .
			"$rdata is not a valid Timezone!" .
			"\n\tThe continent and the city both must start with a capital " .
			"letter, e.g. Europe/London\n";

		return -1;
	} else {
		$main::ua{'php_timezone'} = $rdata;
	}

	push_el(\@main::el, 'ask_timezone()', 'Ending...');

	0;
}

################################################################################
# Ask for Awstats (On|Off)
#
# @return int 0 on success, -1 otherwise
#
sub ask_awstats_on {

	push_el(\@main::el, 'ask_awstats_on()', 'Starting...');

	print "\n\tActivate AWStats. [no]: ";
	chomp(my $rdata = <STDIN>);

	if ($rdata eq '' || $rdata eq 'no' || $rdata eq 'n') {
		$main::ua{'awstats_on'} = 'no';
	} elsif ($rdata eq 'yes' || $rdata eq 'y') {
		$main::ua{'awstats_on'} = 'yes';
	} else {
		print colored(['bold red'], "\n\t[ERROR] ") .
			"Only '(y)es' and '(n)o' are allowed!\n";

			return -1;
	}

	push_el(\@main::el, 'ask_awstats_on()', 'Ending...');

	0;
}

################################################################################
# Ask for Awstats usage (Dynamic|static)
#
# @return int 0 on success, -1 otherwise
#
sub ask_awstats_dyn {

	push_el(\@main::el, 'ask_awstats_dyn()', 'Starting...');

	print "\n\tAWStats Mode:\n\tPossible values [d]ynamic and " .
		"[s]tatic. [dynamic]: ";
	chomp(my $rdata = <STDIN>);

	if ($rdata eq '' || $rdata eq 'dynamic' || $rdata eq 'd') {
		$main::ua{'awstats_dyn'} = '0';
	} elsif ($rdata eq 'static' || $rdata eq 's') {
		$main::ua{'awstats_dyn'} = '1';
	} else {
		print colored(['bold red'], "\n\t[ERROR] ") .
			 "Only '[d]ynamic' or '[s]tatic' are allowed!\n";

		return -1;
	}

	push_el(\@main::el, 'ask_awstats_dyn()', 'Ending...');

	0;
}

################################################################################
#                             Validations subroutines                          #
################################################################################

################################################################################
# Validates a hostname
#
# This subroutine validates a hostname according the RFC 1123.
#
# For now, the rule is as follow:
#
# 1. A host name is composed of series of labels concatenated with dots
# 2. The entire hostname (including the delimiting dots) has a maximum of 255
# characters.
# 3. A (host name) label can start or end with a letter or a number
# 4. A (host name) label MUST NOT start or end with a '-' (dash)
# 5. A (host name) label can be up to 63 characters
#
# Note:
#
# This subroutine can also validates an internationalized domain name. To resume,
# before any validation all unicode string in the hostname are transformed into
# an ASCII string. See the RFC 3492 (updated by RFC 5891) for more information
# about the algorithm.
#
# @param string $hostname Hostname to be validated
# @return 1 if the hostname is valid, 0 otherwise

sub isValidHostname {

	push_el(\@main::el, 'isValidHostname()', 'Starting...');

	my $hostname = shift;

	if(!defined $hostname) {
		push_el(\@main::el, 'isValidHostname()', 'Missing argument `hostname`!');

		return 0;
	}

	# Build tld and label regexp (is executed only the first time)
	state $tldRegExp = qr /^[a-z]{2,6}$/o;
	state $labelRegExp = qr /^([0-9a-z]+(-+[0-9a-z]+)*|[a-z0-9]+)$/io;

	# Before any validation, we should converts $hostname which might contain
	# characters outside the range allowed in DNS names to Punycode
	$hostname = idn_to_ascii($hostname, 'utf-8');
	return 0 if !defined $hostname; # idn_to_ascii() return undef on error

	# Checking hostname length (RFC 1123 section 2.1)
	return 0 if length $hostname > 255;

	# Split the hostname per labels
	my @labels = split '\.', $hostname;

	# We should have a least two labels
	return 0 if(@labels < 2);

	# Retrieve the top level domain
	my $tld = pop @labels;

	# Checking top level domain syntax
	return 0 unless defined $tld && $tld =~ $tldRegExp;

	for (@labels) {
		return 0 if $_ eq '' || length > 63 || $_ !~ $labelRegExp;
	}

	push_el(\@main::el, 'isValidHostname()', 'Ending...');

	1;
}

################################################################################
# Validates a mail address
#
# Validates an email address according a restricted application of the RFCs 5321
# 5322, 1123 and 3492 (updated by 5891).
#
# For now, the rule is as follow:
#
# 1. Only 7bit ASCII characters are allowed for email local-part
# 2. local-part can be either a dot-atom or quoted-string*
# 3. The domain part should follow the RFC 1123 specifications
# 4. The domain part can also be an IDN or an Internet domain literal that is a
# dotted-decimal host address surrounded by square brackets**
#
# * Not Yet Implemented
# ** For now, only IPv4 address are honored.
#
# Note: The obsolete syntax is not honored.
#
# @param string $email Email address to be validated
# @return 1 if the email address is valid, 0 otherwise
#
# @todo domain literal (IPv6)
#
sub isValidEmail {

	push_el(\@main::el, 'isValidEmail()', 'Starting...');

	my $email = shift;

	if(!defined $email) {
		push_el(\@main::el, 'isValidEmail()', 'Missing argument `email`!');

		return 0;
	}

	# Checking e-mail address length  - RFC 5321 section 4.5.3.1
	return 0 if (my $emailLength = length $email) > 254;

	# split email address on local-part and domain part
	my $i = rindex $email, '@';

	# The delimiter '@' or one email part was not found ?
	return 0 if($i == -1 || $i == 0 || $emailLength == ++($i));

	my ($localPart, $domain) = (substr($email, 0, --$i), substr($email, ++$i));

	my $rs = _isValidEmailUser($localPart);
	$rs &&= _isValidEmailDomain($domain);

	return 0 if !$rs;

	push_el(\@main::el, 'isValidEmail()', 'Ending...');

	1;
}

################################################################################
# Validates an email local-part
#
# See isValidEmail() for more information about honored RFC specifications.
#
# @access private
# @param string $email Email local-part
# @return 1 if the local-part is valid, 0 otherwise
# @Todo quoted string (RFC 5322 section 3.2.4)
#
sub _isValidEmailUser {

	push_el(\@main::el, 'isValidEmailUser()', 'Starting...');

	my $localPart = shift;

	if(!defined $localPart) {
		push_el(
			\@main::el, 'isValidEmailUser()', 'Missing argument `local-part`!'
		);

		return 0;
	}

	# local-part must be 64 char or less (RFC 5321 section 4.5.3.1.1.)
	return 0 if length $localPart > 64;

	# Build dot-atom regexp (RFC 5322 section 3.2.3)
	state $atext = quotemeta(
		join '', grep !/[<>()\[\]\\\.,;:\@"]/, map chr, 33..126
	);
	state $atomRegExp = qr/^(?:[$atext]+|[$atext]+(?:\.[$atext]+)+)$/o;

	# Always executed
	return 0 if $localPart !~ $atomRegExp;

	push_el(\@main::el, 'isValidEmailUser()', 'Ending...');

	1;
}

################################################################################
# Validates an email domain part
#
# See the documentation of both isValidEmail and isValidHostname() subroutines
# for more information about honored RFC specifications.
#
# @access private
# @param string $email Email Hostname
# @return 1 if the hostname is valid, 0 otherwise
#
sub _isValidEmailDomain {

	push_el(\@main::el, 'isValidEmailDomain()', 'Starting...');

	my $domain = shift;

	if(!defined $domain) {
		push_el(
			\@main::el, 'isValidEmailDomain()', 'Missing argument `domain`!'
		);

		return 0;
	}

	# Build regExp - dotted- decimal host address surrounded by square brackets
	# (is executed only the first time)
	state $ipRegExp = qr /^
		(?:\[(?:(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\.){3}
		(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\])
	$/xo;

	# Always executed
	return 0 if !isValidHostname($domain) && $domain !~ $ipRegExp;

	push_el(\@main::el, 'isValidEmailDomain()', 'Ending...');

	1;
}

################################################################################
# Validates an IpV4 address
#
# @param string $addr IpV4 address (dot-decimal notation)
# @return int 1 on success, 0 otherwise
#
sub isValidAddr {

	push_el(\@main::el, 'isValidAddr()', 'Starting...');

	my $addr = shift;

	if(!defined $addr) {
		push_el(\@main::el, 'isValidAddr()', 'Missing argument `addr`!');

		return 0;
	}

	# Build regExp - dotted- decimal IPv4 (is executed only the first time)
	state $regExp = qr/^
		(?:(?:(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\.){3}
		(?:[01]?\d{1,2}|2[0-4]\d|25[0-5]))
	$/xo;

	# Always executed
	return 0 if $addr !~ $regExp;

	push_el(\@main::el, 'isValidAddr()', 'Ending...');

	1;
}

################################################################################
#                                Check subroutines                             #
################################################################################

################################################################################
# Check Sql connection
#
# This subroutine can be used to check an MySQL server connection with different
# login credentials.
#
# @param string $user SQL username
# @param string $password SQL user password
# [@param string $dbName SQL database to use]
# [@param string $dbHost MySQL server hostname]
# @return int 0 on success, other on failure
#
sub check_sql_connection {

	push_el(\@main::el, 'check_sql_connection()', 'Starting...');

	my($userName, $password, $dbName, $dbHost) = @_;

	if(!defined $userName && !defined $password) {
		push_el(
			\@main::el, 'check_sql_connection()',
			'[ERROR] Undefined login credential!'
		);

		return -1;
	}

	$dbName = $main::db_name if !defined $dbName;
	$dbHost = $main::db_host if !defined $dbHost;

	# Converting to ASCII (Punycode)
	$dbHost = idn_to_ascii($dbHost, 'utf-8');

	# Define the DSN
	@main::db_connect = (
		"DBI:mysql:$dbName:$dbHost", $userName, $password
	);

	# Forcing reconnection
	$main::db = undef;

	push_el(
		\@main::el, 'check_sql_connection()',
		"Checking MySQL server connection with the following DSN: @main::db_connect"
	);

	my($rs, $rdata) = doSQL('SHOW DATABASES;');
	return $rs if ($rs != 0);

	push_el(\@main::el, 'check_sql_connection()', 'Ending...');

	0;
}

################################################################################
##                             Utils subroutines                               #
################################################################################

################################################################################
# Get and return the fully qualified system hostname
#
# @return string System hostname on success
#
sub get_sys_hostname {

	push_el(\@main::el, 'get_sys_hostname()', 'Starting...');

	state $hostname;

	if(!defined $hostname) {
		# Standard IP with dot to binary data (expected by gethostbyaddr() as first
		# argument )
		my $iaddr = inet_aton(getEthAddr());
		$hostname = gethostbyaddr($iaddr, &AF_INET);

		# gethostbyaddr() returns a short host name with a suffix ( hostname.local )
		# if the host name ( for the current interface ) is not set in /etc/hosts
		# file. In this case, or if the returned value isn't FQHN, we use the long
		# host name who's provided by the system hostname command.
		if(!defined $hostname or
			($hostname =~/^[\w][\w-]{0,253}[\w]\.local$/) ||
			!($hostname =~ /^([\w][\w-]{0,253}[\w])\.([\w][\w-]{0,253}[\w])\.([a-zA-Z]{2,6})$/) ) {

			chomp(my $hostname = `$main::cfg{'CMD_HOSTNAME'} -f $main::rlogfile`);

			if(getCmdExitValue() != 0) {
				exit_msg(
					-1, colored(['bold red'], "[ERROR] ") . 'Unable to found ' .
		        "your system hostname!\n"
				);
			}
		}

		# Converting to unicode
		$hostname = idn_to_unicode($hostname, 'utf-8');
	}

	push_el(\@main::el, 'get_sys_hostname()', 'Ending...');

	return $hostname;
}

################################################################################
# Get the ip (IpV4) assigned to the first Network Interface (eg. eth0)
#
# @return string Ip in dot-decimal notation on success or exit on failure
#
sub getEthAddr {

	push_el(\@main::el, 'getEthAddr()', 'Starting...');

	if(!defined $main::ua{'eth_ip'}) {
		# @todo IO::Interface
		chomp(
			$main::ua{'eth_ip'} =
				`$main::cfg{'CMD_IFCONFIG'}|$main::cfg{'CMD_GREP'} -v inet6|
				$main::cfg{'CMD_GREP'} inet|$main::cfg{'CMD_GREP'} -v 127.0.0.1|
				$main::cfg{'CMD_AWK'} '{print \$2}'|head -n 1|
				$main::cfg{'CMD_AWK'} -F: '{print \$NF}'`
		);

		if(getCmdExitValue() != 0) {
			exit_msg(
				-1, colored(['bold red'], "\n\t[ERROR] ") . 'External command ' .
			 "returned an error on network\n\tinterface cards lookup!\n"
			);
		}
	}

	push_el(\@main::el, 'getEthAddr()', 'Ending...');

	return $main::ua{'eth_ip'};
}

################################################################################
# Convenience subroutine to print a title
#
# @param string $title title to be printed (without EOL)
# @return void
#
sub title {
	my $title = shift;
	print colored(['bold'], "\t$title\n");
}

################################################################################
# Convenience subroutine  to print a subtitle
#
# @param string $subtitle subtitle to be printed (without EOL)
# @return void
#
sub subtitle {
	my $subtitle = shift;
	print "\t $subtitle";

	# saving cursor position
	system('tput sc');

	$main::dyn_length = 0 if(defined $main::dyn_length);
	$main::subtitle_length = length $subtitle;
}

################################################################################
# Convenience subroutine to insert a new line
# @return void
#
sub spacer {
	print "\n";
}

################################################################################
# Can be used in a loop to reflect the action progression
#
# @return void
#
sub progress {
	print '.';
	# Saving cursor position;
	system('tput sc');
	$main::dyn_length++;
}

################################################################################
# Print status string
#
# Note: Should be always called after the subtitle subroutine
#
# @param int $status Action status
# [@param string $exitOnError If set to 'exit_on_error', the program will end up
# if the exit status is a non-zero value]
#
sub print_status {

	my ($status, $exitOnError) = @_;
	my $length = $main::subtitle_length;

	if(defined $main::dyn_length && $main::dyn_length != 0) {
		$length = $length+$main::dyn_length;
		$main::dyn_length = 0;
	}

	my ($termWidth) = GetTerminalSize();
	my $statusString = ($status == 0)
		? colored(['green'], 'Done') : colored(['red'], 'Failed');

	$statusString = sprintf('%'.($termWidth-($length+1)).'s', $statusString);

	# Restoring cursor position
	system('tput rc && tput ed');

	print colored(['bold'], "$statusString\n");

	if(defined $exitOnError && $exitOnError eq 'exit_on_error' && $status != 0) {
		exit_msg($status);
	}
}

################################################################################
# Exit with a message
#
# [@param int $exitCode exit code (default set to 1)]
# [@param: string $userMsg Optional user message]
# @return void
#
sub exit_msg {

	push_el(\@main::el, 'exit_msg()', 'Starting...');

	my ($exitCode, $userMsg) = @_;
	my $msg = '';

	if (!defined $exitCode) {
		$exitCode = 1;
	}

	if($exitCode != 0) {
		my $context = defined &setup_engine ? 'setup' : 'update';

		$msg = "\n\t" . colored(['red bold'], '[FATAL] ')  .
			"An error occurred during $context process!\n" .
			"\tCorrect it and re-run this program.\n\n\tLog files are stored " .
			"in $main::cfg{'LOG_DIR'}/setup\n\tYou can also find help at ".
			 "http://isp-control.net/forum\n\n";
	}

	if(defined $userMsg && $userMsg ne '') {
		$msg = "\n\t$userMsg\n" . $msg;
	}

	print STDERR $msg;

	push_el(\@main::el, 'exit_msg()', 'Ending...');

	exit $exitCode;
}

################################################################################
#                             Hooks subroutines                                #
################################################################################

# Common behavior for the preinst and postinst scripts
#
# The main script will only end if the  maintainer scripts ends with an exit
# status equal to 2.
#

################################################################################
# Implements the hook for the maintainers pre-installation scripts
#
# Hook that can be used by distribution maintainers to perform any required
# tasks before that the actions of the main process are executed. This hook
# allow to add a specific script named `preinst` that will be run before the
# both setup and update process actions. This hook is automatically called after
# that all services are shutting down.
#
# Note:
#
#  The `preinst` script can be written in PERL, PHP or SHELL (POSIX compliant),
#  and must be copied in the engine/setup directory during the make process. A
#  shared library for the scripts that are written in SHELL is available in the
#  engine/setup directory.
#
# @param mixed Argument that will be be passed to the maintainer script
# @return int 0 on success, other otherwise
#
sub preinst {

	push_el(\@main::el, 'preinst()', 'Starting...');

	my $task = shift;
	my $mime_type = mimetype("$main::cfg{'ROOT_DIR'}/engine/setup/preinst");

	($mime_type =~ /(shell|perl|php)/) ||
		exit_msg(
			1, '[err] Unable to determine the mimetype of the `preinst` script!'
		);

	my $rs = sys_command_rs("$main::cfg{'CMD_'.uc($1)} preinst $task");
	return $rs if($rs != 0);

	push_el(\@main::el, 'preinst()', 'Ending...');

	0;
}

################################################################################
# Implements the hook for the maintainers post-installation scripts
#
# Hook that can be used by distribution maintainers to perform any required
# tasks after that the actions of the main process are executed. This hook
# allow to add a specific script named `postinst` that will be run after the
# both setup and update process actions. This hook is automatically called
# before the set_permissions() subroutine call and so, before that all services
# are restarting.
#
# Note:
#
#  The `postinst` script can be written in PERL, PHP or SHELL (POSIX compliant),
#  and must be copied in the engine/setup directory during the make process. A
#  shared library for the scripts that are written in SHELL is available in the
#  engine/setup directory.
#
# @param mixed Argument that will be be passed to the maintainer script
# @return int 0 on success, other otherwise
#
sub postinst {

	push_el(\@main::el, 'postinst()', 'Starting...');

	my $task = shift;
	my $mime_type = mimetype("$main::cfg{'ROOT_DIR'}/engine/setup/postinst");

	($mime_type =~ /(shell|perl|php)/) ||
		exit_msg(
			1, '[err] Unable to determine the mimetype of the `postinst` script!'
		);

	my $rs = sys_command_rs("$main::cfg{'CMD_'.uc($1)} postinst $task");
	return $rs if($rs != 0);

	push_el(\@main::el, 'postinst()', 'Ending...');

	0;
}

################################################################################
#                               others subroutines                             #
################################################################################

################################################################################
# Starting services
#
# This subroutine start all services managed by ispCP and that are not marked as
# 'no' in the main ispCP configuration file (ispcp.conf).
#
sub start_services {

	push_el(\@main::el, 'start_services()', 'Starting...');

	for (
		qw/CMD_ISPCPN CMD_ISPCPD CMD_NAMED CMD_HTTPD CMD_FTPD CMD_MTA CMD_AUTHD
		CMD_POP CMD_POP_SSL CMD_IMAP CMD_IMAP_SSL/
	) {
		if( $main::cfg{$_} !~ /^no$/i && -e $main::cfg{$_}) {
			sys_command("$main::cfg{$_} start $main::rlogfile");
			progress();
		}
	}

	push_el(\@main::el, 'start_services()', 'Ending...');
}

################################################################################
# Stopping services
#
# This subroutines stop all the services managed by ispCP.
#
sub stop_services {

	push_el(\@main::el, 'stop_services()', 'Starting...');

	for (
		qw/CMD_ISPCPN CMD_ISPCPD CMD_NAMED CMD_HTTPD CMD_FTPD CMD_MTA CMD_AUTHD
		CMD_POP CMD_POP_SSL CMD_IMAP CMD_IMAP_SSL/
	) {
		if(-e $main::cfg{$_}) {
			sys_command("$main::cfg{$_} stop $main::rlogfile");
			progress();
		}
	}

	push_el(\@main::el, 'stop_services()', 'Ending...');
}

################################################################################
# Set engine and gui permissions
#
# @return int 0 on success, other on failure
#
sub set_permissions {

	push_el(\@main::el, 'set_permissions()', 'Starting...');

	for (qw/engine gui/) {
		subtitle("Set $_ permissions:");

		my $rs = sys_command(
			"$main::cfg{'CMD_SHELL'} " .
			"$main::cfg{'ROOT_DIR'}/engine/setup/set-$_-permissions.sh " .
			"$main::rlogfile"
		);

		print_status($rs, 'exit_on_error');
	}

	push_el(\@main::el, 'set_permissions()', 'Ending...');

	0;
}

################################################################################
# Remove some unneeded files
#
# This subroutine process the following tasks:
# - Delete .prev log files and their rotations not longer needed since r2251
# - Delete setup/update log files created in /tmp
# - Delete empty files in ispCP configuration directories
#
# @return int 1 on success, other on failure
#
sub setup_cleanup {

	push_el(\@main::el, 'setup_cleanup()', 'Starting...');

	my $rs = sys_command(
		"$main::cfg{'CMD_RM'} -f $main::cfg{'LOG_DIR'}/*-traf.log.prev* " .
		"/tmp/ispcp-update-* /tmp/ispcp-setup-* " .
		"$main::cfg{'CONF_DIR'}/*/*/empty-file"
	);
	return $rs if($rs != 0);

	push_el(\@main::el, 'setup_cleanup()', 'Ending...');

	0;
}
################################################################################
#                        Setup/Update low level subroutines                    #
################################################################################

################################################################################
# ispCP crontab file - (Setup / Update)
#
# This subroutine built, store and install the ispCP crontab file
#
sub setup_crontab {

	push_el(\@main::el, 'setup_crontab()', 'Starting...');

	my ($rs, $cfgTpl);
	my $cfg = \$cfgTpl;

	my $awstats = '';
	my ($rkhunter, $chkrootkit);

	# Directories paths
	my $cfgDir = $main::cfg{'CONF_DIR'} . '/cron.d';
	my $bkpDir = $cfgDir . '/backup';
	my $wrkDir = $cfgDir . '/working';
	my $prodDir;

	# Retrieving production directory path
	if ($main::cfg{'ROOT_GROUP'} eq 'wheel') {
		$prodDir = '/usr/local/etc/ispcp/cron.d';
	} else {
		$prodDir = '/etc/cron.d';
	}

	# Saving the current production file if it exists
	if(-e "$prodDir/ispcp") {
		$rs = sys_command(
			"$main::cfg{'CMD_CP'} -p $prodDir/ispcp $bkpDir/ispcp." . time
		);
		return $rs if ($rs != 0);
	}

	## Building new configuration file

	# Loading the template from /etc/ispcp/cron.d/ispcp
	($rs, $cfgTpl) = get_file("$cfgDir/ispcp");
	return $rs if ($rs != 0);

	# Awstats cron task preparation (On|Off) according status in ispcp.conf
	if ($main::cfg{'AWSTATS_ACTIVE'} ne 'yes' || $main::cfg{'AWSTATS_MODE'} eq 1) {
		$awstats = '#';
	}

	# Search and cleaning path for rkhunter and chkrootkit programs
	# @todo review this s...
	($rkhunter = `which rkhunter`) =~ s/\s$//g;
	($chkrootkit = `which chkrootkit`) =~ s/\s$//g;

	# Building the new file
	($rs, $$cfg) = prep_tpl(
		{
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
		},
		$cfgTpl
	);
	return $rs if ($rs != 0);

	## Storage and installation of new file

	# Storing new file in the working directory
	$rs = store_file(
		"$wrkDir/ispcp", $$cfg, $main::cfg{'ROOT_USER'},
		$main::cfg{'ROOT_GROUP'}, 0644
	);
	return $rs if ($rs != 0);

	# Install the new file in production directory
	$rs = sys_command("$main::cfg{'CMD_CP'} -fp $wrkDir/ispcp $prodDir/");
	return $rs if ($rs != 0);

	push_el(\@main::el, 'setup_crontab()', 'Ending...');

	0;
}

################################################################################
# ispCP named main configuration - (Setup / Update)
#
# This subroutine built, store and install the main named configuration file
#
# @return int 0 on success, other on failure
#
sub setup_named {

	push_el(\@main::el, 'setup_named()', 'Starting...');

	# Do not generate configuration files if the service is disabled
	return 0 if($main::cfg{'CMD_NAMED'} =~ /^no$/i);

	my ($rs, $rdata, $cfgTpl, $cfg);

	my $cfgDir = "$main::cfg{'CONF_DIR'}/bind";
	my $bkpDir = "$cfgDir/backup";
	my $wrkDir = "$cfgDir/working";

	# Setup:
	if(defined &setup_engine) {
		# Saving the system main configuration file
		if(-e $main::cfg{'BIND_CONF_FILE'} && !-e "$bkpDir/named.conf.system") {
			$rs = sys_command(
				"$main::cfg{'CMD_CP'} -p $main::cfg{'BIND_CONF_FILE'} " .
				"$bkpDir/named.conf.system"
			);
			return $rs if ($rs != 0);
		}
	# Update:
	} else {
		# Saving the current main production file if it exists
		if(-e $main::cfg{'BIND_CONF_FILE'}) {
			$rs = sys_command(
				"$main::cfg{'CMD_CP'} -p $main::cfg{'BIND_CONF_FILE'} " .
				"$bkpDir/named.conf." . time
			);
			return $rs if ($rs != 0);
		}
	}

	## Building new configuration file

	# Loading the system main configuration file from
	# /etc/ispcp/bind/backup/named.conf.system if it exists
	if(-e "$bkpDir/named.conf.system") {
		($rs, $cfg) = get_file("$bkpDir/named.conf.system");
		return $rs if($rs != 0);

		# Adjusting the configuration if needed
		$cfg =~ s/listen-on ((.*) )?{ 127.0.0.1; };/listen-on $1 { any; };/;
		$cfg .= "\n";
	# eg. Centos, Fedora did not file by default
	} else {
		push_el(
			\@main::el, 'setup_named()',
			"[WARNING] Can't find the parent file for named..."
		);

		$cfg = '';
	}

	# Loading the template from /etc/ispcp/bind/named.conf
	($rs, $cfgTpl) = get_file("$cfgDir/named.conf");
	return $rs if($rs != 0);

	# Building new file
	$cfg .= $cfgTpl;

	## Storage and installation of new file

	# Storing new file in the working directory
	$rs = store_file(
		"$wrkDir/named.conf", $cfg, $main::cfg{'ROOT_USER'},
		$main::cfg{'ROOT_GROUP'}, 0644
	);
	return $rs if ($rs != 0);

	# Install the new file in the production directory
	$rs = sys_command(
		"$main::cfg{'CMD_CP'} -pf $wrkDir/named.conf " .
		"$main::cfg{'BIND_CONF_FILE'}"
	);
	return $rs if ($rs != 0);

	push_el(\@main::el, 'setup_named()', 'Ending...');

	0;
}

################################################################################
# ispCP Apache fastCGI modules configuration - (Setup / Update)
#
# This subroutine do the following tasks:
#  - Built, store and install all system php related configuration files
#  - Enable required modules and disable unused
#
# @return int 0 on success, other on failure
#
sub setup_fastcgi_modules {

	push_el(\@main::el, 'setup_php()', 'Starting...');

	# Do not generate configuration files if the service is disabled
	return 0 if($main::cfg{'CMD_HTTPD'} =~ /^no$/i);

	my ($rs, $cfgTpl);
	my $cfg = \$cfgTpl;

	# Directories paths
	my $cfgDir = "$main::cfg{'CONF_DIR'}/apache";
	my $bkpDir = "$cfgDir/backup";
	my $wrkDir = "$cfgDir/working";

	# Saving the current production file if they exists
	for (qw/fastcgi_ispcp.conf fastcgi_ispcp.load fcgid_ispcp.conf fcgid_ispcp.load/) {
		if(-e "$main::cfg{'APACHE_MODS_DIR'}/$_") {
			$rs = sys_command(
				"$main::cfg{CMD_CP} -p $main::cfg{'APACHE_MODS_DIR'}/$_ " .
				"$bkpDir/$_." . time
			);
			return $rs if($rs != 0);
		}
	}

	## Building, storage and installation of new files

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
	for (qw/fastcgi fcgid/) {
		# Loading the template from the /etc/ispcp/apache directory
		($rs, $cfgTpl) = get_file("$cfgDir/${_}_ispcp.conf");
		return $rs if ($rs != 0);

		# Building the new configuration file
		($rs, $$cfg) = prep_tpl($tags_hash{$_}, $cfgTpl);
		return $rs if ($rs != 0);

		# Storing the new file
		$rs = store_file(
			"$wrkDir/${_}_ispcp.conf", $$cfg, $main::cfg{'ROOT_USER'},
			$main::cfg{'ROOT_GROUP'}, 0644
		);
		return $rs if ($rs != 0);

		# Installing the new file
		$rs = sys_command(
			"$main::cfg{'CMD_CP'} -pf $wrkDir/${_}_ispcp.conf " .
			"$main::cfg{'APACHE_MODS_DIR'}/"
		);
		return $rs if($rs != 0);
	}

	# fastcgi_ispcp.load / fcgid_ispcp.load
	for (qw/fastcgi fcgid/) {
		next if(! -e "$main::cfg{'APACHE_MODS_DIR'}/$_.load");

		# Loading the system configuration file
		($rs, $$cfg) = get_file("$main::cfg{'APACHE_MODS_DIR'}/$_.load");
		return $rs if ($rs != 0);

		# Building the new configuration file
		$$cfg = "<IfModule !mod_$_.c>\n" . $$cfg . "</IfModule>\n";

		# Store the new file
		$rs = store_file(
			"$wrkDir/${_}_ispcp.load", $$cfg, $main::cfg{'ROOT_USER'},
			$main::cfg{'ROOT_GROUP'}, 0644
		);
		return $rs if ($rs != 0);

		# Install the new file
		$rs = sys_command(
			"$main::cfg{'CMD_CP'} -pf $wrkDir/${_}_ispcp.load " .
			"$main::cfg{'APACHE_MODS_DIR'}/"
		);
		return $rs if($rs != 0);
	}

	## Enable required modules and disable unused

	# Debian like distributions only:
	# Note for distributions maintainers:
	# For others distributions, you must use the a post-installation scripts
	if(! -e '/etc/SuSE-release' && -e '/usr/sbin/a2enmod') {
		# Disable php4/5 modules if enabled
		sys_command("/usr/sbin/a2dismod php4 php5 $main::rlogfile");

		# Enable actions modules
		$rs = sys_command("/usr/sbin/a2enmod actions $main::rlogfile");
		return $rs if($rs != 0);

		if ($main::cfg{'PHP_FASTCGI'} eq 'fastcgi') {
			# Ensures that the unused ispcp fcgid module loader is disabled
			$rs = sys_command(
				"/usr/sbin/a2dismod fcgid_ispcp $main::rlogfile"
			);
			return $rs if($rs != 0);

			# Enable fastcgi module
			$rs = sys_command(
				"/usr/sbin/a2enmod fastcgi_ispcp $main::rlogfile"
			);
			return $rs if($rs != 0);
		} else {
			# Ensures that the unused ispcp fastcgi ispcp module loader is
			# disabled
			$rs = sys_command(
				"/usr/sbin/a2dismod fastcgi_ispcp $main::rlogfile"
			);
			return $rs if($rs != 0);

			# Enable ispcp fastcgi loader
			$rs = sys_command(
				"/usr/sbin/a2enmod fcgid_ispcp $main::rlogfile"
			);
			return $rs if($rs != 0);
		}

		# Disable default  fastcgi/fcgid modules loaders to avoid conflicts
		# with ispcp loaders
		$rs = sys_command("/usr/sbin/a2dismod fastcgi fcgid $main::rlogfile");
		return $rs if($rs != 0);
	}

	push_el(\@main::el, 'setup_php()', 'Ending...');

	0;
}

################################################################################
# ispCP httpd main vhost - (Setup / Update)
#
# This subroutine do the following tasks:
#  - Built, store and install ispCP main vhost configuration file
#  - Enable required modules (cgid, rewrite, suexec)
#
# @return int 0 on success, other on failure
#
sub setup_httpd_main_vhost {

	push_el(\@main::el, 'setup_httpd_main_vhost()', 'Starting...');

	# Do not generate configuration files if the service is disabled
	return 0 if $main::cfg{'CMD_HTTPD'} =~ /^no$/i;

	my ($rs, $cfgTpl);
	my $cfg = \$cfgTpl;

	# Directories paths
	my $cfgDir = "$main::cfg{'CONF_DIR'}/apache";
	my $bkpDir = "$cfgDir/backup";
	my $wrkDir = "$cfgDir/working";

	# Saving the current production file if it exists
	if(-e "$main::cfg{'APACHE_SITES_DIR'}/ispcp.conf") {
		my $rs = sys_command(
			"$main::cfg{'CMD_CP'} -p $main::cfg{'APACHE_SITES_DIR'}/" .
			"ispcp.conf $bkpDir/ispcp.conf.". time
		);
		return $rs if($rs != 0);
	}

	## Building, storage and installation of new file

	# Using alternative syntax for piped logs scripts when possible
	# The alternative syntax does not involve the Shell (from Apache 2.2.12)
	my $pipeSyntax = '|';

	if(`$main::cfg{'CMD_HTTPD'} -v` =~ m!Apache/([\d.]+)! &&
		version->new($1) >= version->new('2.2.12')) {
		$pipeSyntax .= '|';
	}

	# Loading the template from /etc/ispcp/apache/
	($rs, $cfgTpl) = get_file("$cfgDir/httpd.conf");
	return $rs if ($rs != 0);

	# Building the new file
	($rs, $$cfg) = prep_tpl(
		{
			'{APACHE_WWW_DIR}' => $main::cfg{'APACHE_WWW_DIR'},
			'{ROOT_DIR}' => $main::cfg{'ROOT_DIR'},
			'{PIPE}' => $pipeSyntax
		},
		$cfgTpl
	);
	return $rs if ($rs != 0);

	# Storing the new file in working directory
	$rs = store_file(
		"$wrkDir/ispcp.conf", $$cfg, $main::cfg{'ROOT_USER'},
		$main::cfg{'ROOT_GROUP'}, 0644
	);
	return $rs if ($rs != 0);

	# Installing the new file in production directory
	$rs = sys_command(
		"$main::cfg{'CMD_CP'} -pf $wrkDir/ispcp.conf " .
		"$main::cfg{'APACHE_SITES_DIR'}/"
	);
	return $rs if($rs != 0);

	## Enable required modules

	# Debian like distributions only:
	# Note for distributions maintainers:
	# For others distributions, you must use the a post-installation scripts
	if(! -e '/etc/SuSE-release' && -e '/usr/sbin/a2enmod') {
		$rs = sys_command("/usr/sbin/a2enmod cgid $main::rlogfile");
		return $rs if($rs != 0);

		# Enabling mod rewrite
		$rs = sys_command("/usr/sbin/a2enmod rewrite $main::rlogfile");
		return $rs if($rs != 0);

		# Enabling mod suexec
		$rs = sys_command("/usr/sbin/a2enmod suexec $main::rlogfile");
		return $rs if($rs != 0);

		## Enabling main vhost configuration file
		$rs = sys_command("/usr/sbin/a2ensite ispcp.conf $main::rlogfile");
		return $rs if($rs != 0);
	}

	push_el(\@main::el, 'setup_httpd_main_vhost()', 'Ending...');

	0;
}

################################################################################
# ispCP awstats vhost - (Setup / Update)
#
# This subroutine do the following tasks:
#  - Built, store and install awstats vhost configuration file
#  - Change proxy module configuration file if it exits
#  - Enable proxy module
#
# @return int 0 on success, other on failure
#
sub setup_awstats_vhost {

	push_el(\@main::el, 'setup_awstats_vhost()', 'Starting...');

	# Do not generate configuration files if the service is disabled
	return 0 if($main::cfg{'AWSTATS_ACTIVE'} =~ /^no$/i);

	my ($rs, $path, $file, $cfgTpl);
	my $cfg = \$cfgTpl;

	# Directories paths
	my $cfgDir = "$main::cfg{'CONF_DIR'}/apache";
	my $bkpDir = "$cfgDir/backup";
	my $wrkDir = "$cfgDir/working";

	# Setup:
	if(defined &setup_engine) {
		# Saving some system configuration files
		for (
			map {/(.*\/)(.*)$/ && $1.':'.$2}
			'/etc/logrotate.d/apache', # Still needed ?
			'/etc/logrotate.d/apache2',
			"$main::cfg{'APACHE_MODS_DIR'}/proxy.conf"
		) {
			($path, $file) = split /:/ ;
			next if(!-e $path.$file);

			if(!-e "$bkpDir/$file.system") {
				$rs = sys_command(
					"$main::cfg{'CMD_CP'} -p $path$file $bkpDir/$file.system"
				);
				return $rs if($rs != 0);
			}
		}
	# Update:
	} else {
		my $timestamp = time;

		# Saving more production files if they exist
		for (
			map {/(.*\/)(.*)$/ && $1.':'.$2}
			'/etc/logrotate.d/apache',
			'/etc/logrotate.d/apache2',
			"$main::cfg{'APACHE_MODS_DIR'}/proxy.conf",
			"$main::cfg{'APACHE_SITES_DIR'}/01_awstats.conf"
		) {
			($path, $file)= split /:/;
			next if(!-e $path.$file);

			$rs = sys_command(
				"$main::cfg{'CMD_CP'} -p $path$file $bkpDir/$file.$timestamp"
			);
			return $rs if($rs != 0);
		}
	}

	## Building, storage and installation of new file

	# Tags preparation
	my %tags_hash = (
		'{AWSTATS_ENGINE_DIR}' => $main::cfg{'AWSTATS_ENGINE_DIR'},
		'{AWSTATS_WEB_DIR}' => $main::cfg{'AWSTATS_WEB_DIR'}
	);

	# Loading the template from /etc/ispcp/apache
	($rs, $cfgTpl) = get_file("$cfgDir/01_awstats.conf");
	return $rs if($rs != 0);

	# Building the new file
	($rs, $$cfg) = prep_tpl(\%tags_hash, $cfgTpl);
	return $rs if ($rs != 0);

	# Store the new file in working directory
	$rs = store_file(
		"$wrkDir/01_awstats.conf", $$cfg, $main::cfg{'ROOT_USER'},
		$main::cfg{'ROOT_GROUP'}, 0644
	);
	return $rs if ($rs != 0);

	# Install the new file in production directory
	$rs = sys_command(
		"$main::cfg{'CMD_CP'} -pf $wrkDir/01_awstats.conf " .
		"$main::cfg{'APACHE_SITES_DIR'}/"
	);
	return $rs if($rs != 0);

	if ($main::cfg{'AWSTATS_ACTIVE'} eq 'yes' && $main::cfg{'AWSTATS_MODE'} eq 0) {
		## Change the proxy module configuration file if it exists
		if(-e "$bkpDir/proxy.conf.system") {
			($rs, $$cfg) = get_file("$bkpDir/proxy.conf.system");
			return $rs if($rs != 0);

			# Replace the allowed hosts in mod_proxy if needed
			# @todo Squeeze - All is commented / Check if it work like this
			$$cfg =~ s/#Allow from .example.com/Allow from 127.0.0.1/gi;

			# Store the new file in working directory
			$rs = store_file(
				"$wrkDir/proxy.conf", $$cfg, $main::cfg{'ROOT_USER'},
				$main::cfg{'ROOT_GROUP'}, 0644
			);
			return $rs if ($rs != 0);

			# Install the new file in production directory
			$rs = sys_command(
				"$main::cfg{'CMD_CP'} -pf $wrkDir/proxy.conf " .
				"$main::cfg{'APACHE_MODS_DIR'}/"
			);
			return $rs if($rs != 0);
		}

		# Debian like distributions only:
		# Note for distributions maintainers:
		# For others distributions, you must use the a post-installation scripts
		if(! -e '/etc/SuSE-release' && -e '/usr/sbin/a2enmod') {
			# Enable required modules
			sys_command("/usr/sbin/a2enmod proxy $main::rlogfile");
			sys_command("/usr/sbin/a2enmod proxy_http $main::rlogfile");

			# Enable awstats vhost
			sys_command("/usr/sbin/a2ensite 01_awstats.conf $main::rlogfile");
		}

		## Update Apache logrotate file

		# if the distribution provides an apache or apache2 log rotation file,
		# update it with the awstats information. If not, use the ispcp file.
		# log rotation should be never executed twice. Therefore it is sane to
		# define it two times in different scopes.
		for (qw/apache apache2/) {
			next if(! -e "$bkpDir/$_.system");

			($rs, $$cfg) = get_file("$bkpDir/$_.system");
			return $rs if ($rs != 0);

			# Add code if not exists
			if ($$cfg !~ /awstats_updateall\.pl/i) {
				# Building the new file
				$$cfg =~ s/sharedscripts/sharedscripts\n\tprerotate\n\t\t$main::cfg{'AWSTATS_ROOT_DIR'}\/awstats_updateall.pl now -awstatsprog=$main::cfg{'AWSTATS_ENGINE_DIR'}\/awstats.pl &> \/dev\/null\n\tendscript/gi;

				# Store the new file in working directory
				$rs = store_file(
					"$wrkDir/$_", $$cfg, $main::cfg{'ROOT_USER'},
					$main::cfg{'ROOT_GROUP'}, 0644
				);
				return $rs if ($rs != 0);

				# Install the new file in production directory
				$rs = sys_command(
					"$main::cfg{'CMD_CP'} -pf $wrkDir/$_ /etc/logrotate.d/"
				);
				return $rs if($rs != 0);
			}
		}
	}

	push_el(\@main::el, 'setup_awstats_vhost()', 'Starting...');

	0;
}

################################################################################
# ispCP Postfix - (Setup / Update)
#
# This subroutine built, store and install Postfix configuration files:
# - main.cf
# - master.cf
# - aliases, domains, mailboxes, transport, sender-access lookup tables
# - ARPL messenger
#
# @return int 0 on success, other on failure
#
sub setup_mta {

	push_el(\@main::el, 'setup_mta()', 'Starting...');

	# Do not generate configuration files if the service is disabled
	return 0 if($main::cfg{'CMD_MTA'} =~ /^no$/i);

	my ($rs, $cfgTpl, $path, $file);
	my $cfg = \$cfgTpl;

	# Directories paths
	my $cfgDir = "$main::cfg{'CONF_DIR'}/postfix";
	my $bkpDir = "$cfgDir/backup";
	my $wrkDir = "$cfgDir/working";
	my $vrlDir = "$cfgDir/ispcp";

	# Install
	if(!defined &update_engine) {
		# Saving all system configuration files if they exists
		for (
			map {/(.*\/)(.*)$/ && $1.':'.$2}
			$main::cfg{'POSTFIX_CONF_FILE'},
			$main::cfg{'POSTFIX_MASTER_CONF_FILE'}
		) {
			($path, $file) = split /:/;

			next if (!-e $path.$file || -e "$bkpDir/$file.system");

			$rs = sys_command(
					"$main::cfg{'CMD_CP'} -p $path$file  $bkpDir/$file.system"
			);
			return $rs if ($rs != 0);
		}
	# Update
	} else {
		my $timestamp = time;

		# Saving all current production files
		for (
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

			$rs = sys_command(
				"$main::cfg{'CMD_CP'} -p $path$file $bkpDir/$file.$timestamp"
			);
			return $rs if ($rs != 0);
		}
	}

	## Building, storage and installation of new file

	# main.cf

	# Loading the template from /etc/ispcp/postfix/
	($rs, $cfgTpl) = get_file("$cfgDir/main.cf");
	return $rs if ($rs != 0);

	# Converting to ASCII (Punycode)
	my $hostname = idn_to_ascii($main::cfg{'SERVER_HOSTNAME'}, 'utf-8');

	# Building the file
	($rs, $$cfg) = prep_tpl(
		{
			'{MTA_HOSTNAME}' => $hostname,
			'{MTA_LOCAL_DOMAIN}' => "$hostname.local",
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
			'{MTA_MAILBOX_GID}' => $main::cfg{'MTA_MAILBOX_GID'},
			'{PORT_POSTGREY}' => $main::cfg{'PORT_POSTGREY'}
		},
		$cfgTpl
	);
	return $rs if ($rs != 0);

	# Storing the new file in working directory
	$rs = store_file(
		"$wrkDir/main.cf", $$cfg, $main::cfg{'ROOT_USER'},
		$main::cfg{'ROOT_GROUP'}, 0644
	);
	return $rs if ($rs != 0);

	# Installing the new file in production directory
	$rs = sys_command(
		"$main::cfg{'CMD_CP'} -pf $wrkDir/main.cf " .
		"$main::cfg{'POSTFIX_CONF_FILE'}"
	);
	return $rs if($rs != 0);

	# master.cf

	# Storing the new file in the working directory
	$rs = sys_command("$main::cfg{'CMD_CP'} -pf $cfgDir/master.cf $wrkDir/");
	return $rs if ($rs != 0);

	# Installing the new file in the production dir
	$rs = sys_command(
		"$main::cfg{'CMD_CP'} -pf $cfgDir/master.cf " .
		"$main::cfg{'POSTFIX_MASTER_CONF_FILE'}"
	);
	return $rs if ($rs != 0);

	## Lookup tables files

	for (qw/aliases domains mailboxes transport sender-access/) {
		# Storing the new files in the working directory
		$rs = sys_command("$main::cfg{'CMD_CP'} -pf $vrlDir/$_ $wrkDir/");
		return $rs if ($rs != 0);

		# Install the files in the production directory
		$rs = sys_command(
			"$main::cfg{'CMD_CP'} -pf $wrkDir/$_ " .
			"$main::cfg{'MTA_VIRTUAL_CONF_DIR'}/"
		);
		return $rs if ($rs != 0);

		# Creating/updating Btree databases for all lookup tables
		$rs = sys_command(
			"$main::cfg{'CMD_POSTMAP'} $main::cfg{'MTA_VIRTUAL_CONF_DIR'}/$_ " .
			"$main::rlogfile"
		);
		return $rs if ($rs != 0);
	}

	# Rebuilding the database for the mail aliases file - Begin
	$rs = sys_command("$main::cfg{'CMD_NEWALIASES'} $main::rlogfile");
	return $rs if ($rs != 0);

	## Setting ARPL messenger owner, group and permissions

	$rs = setfmode(
		"$main::cfg{'ROOT_DIR'}/engine/messenger/ispcp-arpl-msgr",
		$main::cfg{'MTA_MAILBOX_UID_NAME'}, $main::cfg{'MTA_MAILBOX_GID_NAME'},
		0755
	);
	return $rs if ($rs != 0);

	push_el(\@main::el, 'setup_mta()', 'Ending...');

	0;
}

################################################################################
# ispCP Courier - (Setup / Update)
#
# This subroutine do the following tasks:
#  - Built, store and install Courier, related configuration files
#  - Creates userdb.dat file from the contents of the userdb file
#
# @return int 0 on success, other on failure
#
sub setup_po {

	push_el(\@main::el, 'setup_po()', 'Starting...');

	# Do not generate configuration files if the service is disabled
	return 0 if($main::cfg{'CMD_AUTHD'} =~ /^no$/i);

	my ($rs, $rdata);

	# Directories paths
	my $cfgDir = "$main::cfg{'CONF_DIR'}/courier";
	my $bkpDir ="$cfgDir/backup";
	my $wrkDir = "$cfgDir/working";

	# Install:
	if(!defined &update_engine) {
		# Saving all system configuration files if they exists
		for (qw/authdaemonrc userdb/) {
			if(-e "$main::cfg{'AUTHLIB_CONF_DIR'}/$_" && !-e "$bkpDir/$_.system") {
				$rs = sys_command(
					"$main::cfg{'CMD_CP'} -p $main::cfg{'AUTHLIB_CONF_DIR'}/$_ " .
					"$bkpDir/$_.system"
				);
				return $rs if ($rs != 0);
			}
		}
	# Update:
	} else {
		my $timestamp = time;

		# Saving all current production files if they exist
		for (qw/authdaemonrc userdb/) {
			next if(!-e "$main::cfg{'AUTHLIB_CONF_DIR'}/$_");

			$rs = sys_command(
				"$main::cfg{'CMD_CP'} -p $main::cfg{'AUTHLIB_CONF_DIR'}/$_ " .
				"$bkpDir/$_.$timestamp"
			);
			return $rs if ($rs != 0);
		}
	}

	## Building, storage and installation of new file

	# authdaemonrc file

	# Loading the system file from /etc/ispcp/backup
	($rs, $rdata) = get_file("$bkpDir/authdaemonrc.system");
	return $rs if ($rs != 0);

	# Building the new file (Adding the authuserdb module if needed)
	if($rdata !~ /^\s*authmodulelist="(?:.*)?authuserdb.*"$/gm) {
		$rdata =~ s/(authmodulelist=")/$1authuserdb /gm;
	}

	# Storing the new file in the working directory
	$rs = store_file(
		"$wrkDir/authdaemonrc", $rdata, $main::cfg{'ROOT_USER'},
		$main::cfg{'ROOT_GROUP'}, 0660
	);
	return $rs if ($rs != 0);

	# Installing the new file in the production directory
	$rs = sys_command(
		"$main::cfg{'CMD_CP'} -pf $wrkDir/authdaemonrc " .
		"$main::cfg{'AUTHLIB_CONF_DIR'}/"
	);
	return $rs if ($rs != 0);

	# userdb file

	# Storing the new file in the working directory
	$rs = sys_command("$main::cfg{'CMD_CP'} -pf $cfgDir/userdb $wrkDir/");
	return $rs if ($rs != 0);

	# After build this file is world readable which is is bad
	# Permissions are inherited by production file
	$rs = setfmode(
		"$wrkDir/userdb", $main::cfg{'ROOT_USER'},
		$main::cfg{'ROOT_GROUP'}, 0600
	);
	return $rs if($rs != 0);

	# Installing the new file in the production directory
	$rs = sys_command(
		"$main::cfg{'CMD_CP'} -pf $wrkDir/userdb " .
		"$main::cfg{'AUTHLIB_CONF_DIR'}"
	);
	return $rs if ($rs != 0);

	# Creating/Updating userdb.dat file from the contents of the userdb file
	$rs = sys_command($main::cfg{'CMD_MAKEUSERDB'});
	return $rs if ($rs != 0);

	push_el(\@main::el, 'setup_po()', 'Ending...');

	0;
}

################################################################################
# ispCP Proftpd - (Setup / Update)
#
# This subroutine do the following tasks:
#  - Built, store and install Proftpd main configuration files
#  - Create Ftpd SQL account if needed
#
# @return int 0 on success, other on failure
#
sub setup_ftpd {

	push_el(\@main::el, 'setup_ftpd()', 'Starting...');

	# Do not generate configuration files if the service is disabled
	return 0 if($main::cfg{'CMD_FTPD'} =~ /^no$/i);

	my ($rs, $rdata, $sql, $cfgTpl);
	my $cfg = \$cfgTpl;

	my $warnMsg;
	my $wrkFile;

	# Directories paths
	my $cfgDir = "$main::cfg{'CONF_DIR'}/proftpd";
	my $bkpDir = "$cfgDir/backup";
	my $wrkDir = "$cfgDir/working";

	# Converting to ASCII (Punycode)
	my $dbHost = idn_to_ascii($main::db_host, 'utf-8');

	## Sets the path to the configuration file

	if (!-e $main::cfg{'FTPD_CONF_FILE'}) {
		$rs = set_conf_val('FTPD_CONF_FILE', '/etc/proftpd/proftpd.conf');
		return $rs if ($rs != 0);

		$rs = store_conf();
		return $rs if ($rs != 0);
	}

	# Install:
	if(!defined &update_engine) {
		# Saving the system configuration file if it exist
		if(-e $main::cfg{'FTPD_CONF_FILE'} && !-e "$bkpDir/proftpd.conf.system") {
			$rs = sys_command(
				"$main::cfg{'CMD_CP'} -p $main::cfg{'FTPD_CONF_FILE'} " .
				"$bkpDir/proftpd.conf.system"
			);
			return $rs if($rs != 0);
		}
	# Update:
	} else {
		my $timestamp = time;

		# Saving the current production files if it exits
		if(-e $main::cfg{'FTPD_CONF_FILE'}) {
			$rs = sys_command(
				"$main::cfg{'CMD_CP'} -p $main::cfg{'FTPD_CONF_FILE'} " .
				"$bkpDir/proftpd.conf.$timestamp"
			);
			return $rs if($rs != 0);
		}

		## Get the current user and password for SQL connection and check it

		if(-e "$wrkDir/proftpd.conf" ) {
			$wrkFile = "$wrkDir/proftpd.conf";
		} elsif(-e "$main::cfg{'CONF_DIR'}/proftpd/backup/proftpd.conf.ispcp") {
			$wrkFile = "$main::cfg{'CONF_DIR'}/proftpd/backup/proftpd.conf.ispcp";
		} elsif(-e '/etc/proftpd.conf.bak') {
			$wrkFile = '/etc/proftpd.conf.bak';
		}

		# Loading working configuration file from /etc/ispcp/working/
		($rs, $rdata) = get_file($wrkFile);

		unless($rs) {
			if($rdata =~ /^SQLConnectInfo(?: |\t)+.*?(?: |\t)+(.*?)(?: |\t)+(.*?)\n/im) {
				# Check the database connection with current ids
				# @todo Check Check Check
				$rs = check_sql_connection($1, $2);

				# If the connection is successful, we can use these identifiers
				unless($rs) {
					$main::ua{'db_ftp_user'} = $1;
					$main::ua{'db_ftp_password'} = $2;
				} else {
					$warnMsg = "\n\t[WARNING] Unable to connect to the " .
						"database with authentication information\n\tfound in " .
						"your proftpd.conf file! We will create a new Ftpd " .
						"Sql account.\n";
				}
			}
		} else {
			$warnMsg = colored(['bold yellow'], "\n\t[WARNING] ") .
				"Unable to find the Proftpd configuration file!" .
				"\n\tThe program will create a new.";
		}

		# We ask the database ftp user and password, and we create new SQL ftp
		# user account if needed
		if(!defined $main::ua{'db_ftp_user'} || !defined $main::ua{'db_ftp_password'}) {
			print defined $warnMsg
				? $warnMsg
				: colored(['bold yellow'], "\n\t[WARNING]") .
					"Unable to retrieve your current username and/or" .
					"\n\tpassword for the Ftpd SQL account! We will " .
					"create a new Ftpd Sql account.\n";

			# Ask for proftpd SQL username
			do {$rs = ask_db_ftp_user();} while ($rs);

			# Ask for proftpd SQL user password
			do {$rs = ask_db_ftp_password();} while ($rs);

			## Setup of new SQL ftp user

			# Setting new DSN
			@main::db_connect = (
				"DBI:mysql:mysql:$main::db_host", $main::db_user, $main::db_pwd
			);

			# Forcing reconnection
			$main::db = undef;

			## We ensure that news data doesn't exist in database

			($rs) = doSQL(
				qq/
					DELETE FROM `tables_priv`
					WHERE `Host` = '$dbHost'
					AND `Db` = '$main::db_name'
					AND `User` = '$main::ua{'db_ftp_user'}';
				/
			);
			return $rs if ($rs != 0);

			($rs) = doSQL(
				qq/
					DELETE FROM `user`
					WHERE `Host` = '$dbHost'
					AND `User` = '$main::ua{'db_ftp_user'}';
				/
			);
			return $rs if ($rs != 0);

			($rs) = doSQL('FLUSH PRIVILEGES');
			return $rs if ($rs != 0);

			## Inserting new data into the database

			for (qw/ftp_group ftp_users quotalimits quotatallies/) {
				($rs) = doSQL(
					qq/
						GRANT SELECT,INSERT,UPDATE,DELETE ON `$main::db_name`.`$_`
						TO '$main::ua{'db_ftp_user'}'\@'$dbHost'
						IDENTIFIED BY '$main::ua{'db_ftp_password'}';
					/
				);
				return $rs if ($rs != 0);
			}
		}
	}

	## Building, storage and installation of new file

	# Loading the template from /etc/ispcp/proftpd/
	($rs, $cfgTpl) = get_file("$cfgDir/proftpd.conf");
	return $rs if ($rs != 0);

	# Building the new file
	($rs, $$cfg) = prep_tpl(
		{
		'{HOST_NAME}' => idn_to_ascii($main::cfg{'SERVER_HOSTNAME'}, 'utf-8'),
		'{DATABASE_NAME}' => $main::db_name,
		'{DATABASE_HOST}' => $dbHost,
		'{DATABASE_USER}' => $main::ua{'db_ftp_user'},
		'{DATABASE_PASS}' => $main::ua{'db_ftp_password'},
		'{FTPD_MIN_UID}' => $main::cfg{'APACHE_SUEXEC_MIN_UID'},
		'{FTPD_MIN_GID}' => $main::cfg{'APACHE_SUEXEC_MIN_GID'}
		},
		$cfgTpl
	);
	return $rs if ($rs != 0);

	# Store the new file in working directory
	$rs = store_file(
		"$wrkDir/proftpd.conf", $$cfg, $main::cfg{'ROOT_USER'},
		$main::cfg{'ROOT_GROUP'}, 0600
	);
	return $rs if ($rs != 0);

	# Install the new file in production directory
	$rs = sys_command(
		"$main::cfg{'CMD_CP'} -pf $wrkDir/proftpd.conf " .
		"$main::cfg{'FTPD_CONF_FILE'}"
	);
	return $rs if ($rs != 0);

	## To fill ftp_traff.log file with something

	if (! -e "$main::cfg{'TRAFF_LOG_DIR'}/proftpd") {
		$rs = make_dir(
			"$main::cfg{'TRAFF_LOG_DIR'}/proftpd", $main::cfg{'ROOT_USER'},
			$main::cfg{'ROOT_GROUP'}, 0755
		);
		return $rs if ($rs != 0);
	}

	if(! -e "$main::cfg{'TRAFF_LOG_DIR'}$main::cfg{'FTP_TRAFF_LOG'}") {
		$rs = store_file(
			"$main::cfg{'TRAFF_LOG_DIR'}$main::cfg{'FTP_TRAFF_LOG'}", "\n",
			$main::cfg{'ROOT_USER'}, $main::cfg{'ROOT_GROUP'}, 0644
		);
		return $rs if ($rs != 0);
	}

	push_el(\@main::el, 'setup_ftpd()', 'Ending...');

	0;
}

################################################################################
# ispCP Daemon, network - (Setup / Update)
#
# This subroutine install or update the ispCP daemon and network init scripts
#
# @return int 0 on success, other on failure
#
sub setup_ispcp_daemon_network {

	push_el(\@main::el, 'setup_ispcp_daemon_network()', 'Starting...');

	my ($rs, $rdata, $fileName);

	for ($main::cfg{'CMD_ISPCPD'}, $main::cfg{'CMD_ISPCPN'}) {
		# Do not process if the service is disabled
		next if(/^no$/i);

		($fileName) = /.*\/(.*)$/;

		$rs = sys_command_rs(
			"$main::cfg{'CMD_CHOWN'} $main::cfg{'ROOT_USER'}:" .
			"$main::cfg{'ROOT_GROUP'} $_ $main::rlogfile"
		);
		return $rs if($rs != 0);

		$rs = sys_command("$main::cfg{'CMD_CHMOD'} 0755 $_ $main::rlogfile");
		return $rs if($rs != 0);

		# Services installation / update (Debian, Ubuntu)
		# Todo Check it for Debian Squeeze
		if(-x '/usr/sbin/update-rc.d') {
			# Update task - The links should be removed first to be updated
			if(defined &update_engine) {
				sys_command(
					"/usr/sbin/update-rc.d -f $fileName remove $main::rlogfile"
				);
			}

			# ispcp_network should be stopped before the MySQL server (due to the
			# interfaces deletion process)
			if($fileName eq 'ispcp_network') {
				sys_command(
					"/usr/sbin/update-rc.d $fileName defaults 99 20 $main::rlogfile"
				);
			} else {
				sys_command(
					"/usr/sbin/update-rc.d $fileName defaults 99 $main::rlogfile"
				);
			}

		# LSB 3.1 Core section 20.4 compatibility (ex. OpenSUSE > 10.1)
		} elsif(-x '/usr/lib/lsb/install_initd') {
			# Update task
			if(-x '/usr/lib/lsb/remove_initd' && defined &update_engine) {
				sys_command("/usr/lib/lsb/remove_initd $_ $main::rlogfile");
			}

			sys_command("/usr/lib/lsb/install_initd $_ $main::rlogfile");
			return $rs if ($rs != 0);
		}
	}

	push_el(\@main::el, 'setup_ispcp_daemon_network()', 'Ending...');

	0;
}

################################################################################
# ispCP GUI apache vhost - (Setup / Update)
#
# This subroutine built, store and install ispCP GUI vhost configuration file.
#
# @return int 0 on success, other on failure
#
sub setup_gui_httpd {

	push_el(\@main::el, 'setup_gui_httpd()', 'Starting...');

	my ($rs, $cmd, $cfgTpl);
	my $cfg = \$cfgTpl;

	# Directories paths
	my $cfgDir = "$main::cfg{'CONF_DIR'}/apache";
	my $bkpDir = "$cfgDir/backup";
	my $wrkDir = "$cfgDir/working";

	# Saving the current production file if it exists
	if(-e "$main::cfg{'APACHE_SITES_DIR'}/00_master.conf") {
		$cmd = "$main::cfg{'CMD_CP'} -p $main::cfg{'APACHE_SITES_DIR'}/" .
		"00_master.conf $bkpDir/00_master.conf." . time;

		$rs = sys_command($cmd);
		return $rs if($rs != 0);
	}

	# Loading the template from /etc/ispcp/apache
	($rs, $cfgTpl) = get_file("$cfgDir/00_master.conf");
	return $rs if($rs != 0);

	# Building the new file
	($rs, $$cfg) = prep_tpl(
		{
			'{BASE_SERVER_IP}' => $main::cfg{'BASE_SERVER_IP'},
			'{BASE_SERVER_VHOST}' => idn_to_ascii($main::cfg{'BASE_SERVER_VHOST'}, 'utf-8'),
			'{DEFAULT_ADMIN_ADDRESS}' => $main::cfg{'DEFAULT_ADMIN_ADDRESS'}, #TODO Punycode
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
		},
		$cfgTpl
	);
	return $rs if ($rs != 0);

	# Storing the new file
	$rs = store_file(
		"$wrkDir/00_master.conf", $$cfg, $main::cfg{'ROOT_USER'},
		$main::cfg{'ROOT_GROUP'}, 0644
	);
	return $rs if ($rs != 0);

	# Installing the new file
	$rs = sys_command(
		"$main::cfg{'CMD_CP'} -pf $wrkDir/00_master.conf " .
		"$main::cfg{'APACHE_SITES_DIR'}/"
	);
	return $rs if($rs != 0);

	## Disable 000-default vhost (Debian like distributions)
	if (-e "/usr/sbin/a2dissite") {
		sys_command_rs("/usr/sbin/a2dissite 000-default $main::rlogfile");
	}

	# Disable the default NameVirtualHost directive
	# (Debian like distributions)
	if(-e '/etc/apache2/ports.conf') {
		# Loading the file
		($rs, my $rdata) = get_file('/etc/apache2/ports.conf');
		return $rs if($rs != 0);

		# Disable the default NameVirtualHost directive
		$rdata =~ s/^NameVirtualHost \*:80/#NameVirtualHost \*:80/gmi;

		# Saving the modified file
		$rs = save_file('/etc/apache2/ports.conf', $rdata);
		return $rs if($rs != 0);
	}

	# Enable GUI vhost (Debian like distributions)
	if (-e "/usr/sbin/a2ensite") {
		sys_command("/usr/sbin/a2ensite 00_master.conf $main::rlogfile");
	}

	push_el(\@main::el, 'setup_gui_httpd()', 'Ending...');

	0;
}

################################################################################
# ispCP GUI PHP configuration files - (Setup / Update)
#
# This subroutine do the following tasks:
#  - Create the master fcgi directory
#  - Built, store and install gui php related files (starter script, php.ini...)
#
# @return int 0 on success, other on failure
#
sub setup_gui_php {

	push_el(\@main::el, 'setup_gui_php()', 'Starting...');

	my ($rs, $cfgTpl);
	my $cfg = \$cfgTpl;

	my $cfgDir = "$main::cfg{'CONF_DIR'}/fcgi";
	my $bkpDir = "$cfgDir/backup";
	my $wrkDir = "$cfgDir/working";

	my $timestamp = time;

	# Saving files if they exists
	for ('php5-fcgi-starter', 'php5/php.ini', 'php5/browscap.ini') {
		if(-e "$main::cfg{'PHP_STARTER_DIR'}/master/$_") {
			my (undef, $file) = split('/');
			$file = $_ if(!defined $file);

			$rs = sys_command(
				"$main::cfg{'CMD_CP'} -p $main::cfg{'PHP_STARTER_DIR'}/" .
				"master/$_ $bkpDir/master.$file.$timestamp"
			);
			return $rs if($rs != 0);
		}
	}

	## Create the fcgi directories tree for the GUI if it doesn't exists

	$rs = make_dir(
		"$main::cfg{'PHP_STARTER_DIR'}/master/php5", $main::cfg{'ROOT_USER'},
		$main::cfg{'ROOT_GROUP'}, 0755
	);
	return $rs if ($rs != 0);

	## PHP5 Starter script

	# Loading the template from /etc/ispcp/fcgi/parts/master
	($rs, $cfgTpl) = get_file("$cfgDir/parts/master/php5-fcgi-starter.tpl");
	return $rs if ($rs != 0);

	# Building the new file
	($rs, $$cfg) = prep_tpl(
		{
			'{PHP_STARTER_DIR}' => $main::cfg{'PHP_STARTER_DIR'},
			'{PHP5_FASTCGI_BIN}' => $main::cfg{'PHP5_FASTCGI_BIN'},
			'{GUI_ROOT_DIR}' => $main::cfg{'GUI_ROOT_DIR'},
			'{DMN_NAME}' => 'master'
		},
		$cfgTpl
	);
	return $rs if ($rs != 0);

	# Storing the new file in the working directory
	$rs = store_file(
		"$wrkDir/master.php5-fcgi-starter", $$cfg,
		$main::cfg{'APACHE_SUEXEC_USER_PREF'} . $main::cfg{'APACHE_SUEXEC_MIN_UID'},
		$main::cfg{'APACHE_SUEXEC_USER_PREF'} . $main::cfg{'APACHE_SUEXEC_MIN_GID'},
		0755
	);
	return $rs if ($rs != 0);

	# Install the new file
	$rs = sys_command(
		"$main::cfg{'CMD_CP'} -pf $wrkDir/master.php5-fcgi-starter " .
		"$main::cfg{'PHP_STARTER_DIR'}/master/php5-fcgi-starter"
	);
	return $rs if ($rs != 0);

	## PHP5 php.ini file

	# Loading the template from /etc/ispcp/fcgi/parts/master/php5
	($rs, $cfgTpl) = get_file("$cfgDir/parts/master/php5/php.ini");
	return $rs if ($rs != 0);

	# Building the new file
	($rs, $$cfg) = prep_tpl(
		{
			'{WWW_DIR}' => $main::cfg{'ROOT_DIR'},
			'{DMN_NAME}' => 'gui',
			'{MAIL_DMN}' => idn_to_ascii($main::cfg{'BASE_SERVER_VHOST'}, 'utf-8'),
			'{CONF_DIR}' => $main::cfg{'CONF_DIR'},
			'{MR_LOCK_FILE}' => $main::cfg{'MR_LOCK_FILE'},
			'{PEAR_DIR}' => $main::cfg{'PEAR_DIR'},
			'{RKHUNTER_LOG}' => $main::cfg{'RKHUNTER_LOG'},
			'{CHKROOTKIT_LOG}' => $main::cfg{'CHKROOTKIT_LOG'},
			'{OTHER_ROOTKIT_LOG}' => ($main::cfg{'OTHER_ROOTKIT_LOG'} ne '')
				? ":$main::cfg{'OTHER_ROOTKIT_LOG'}" : '',
			'{PHP_STARTER_DIR}' => $main::cfg{'PHP_STARTER_DIR'},
			'{PHP_TIMEZONE}' => $main::cfg{'PHP_TIMEZONE'}
		},
		$cfgTpl
	);
	return $rs if ($rs != 0);

	# Store the new file in working directory
	$rs = store_file(
		"$wrkDir/master.php.ini", $$cfg,
		$main::cfg{'APACHE_SUEXEC_USER_PREF'} . $main::cfg{'APACHE_SUEXEC_MIN_UID'},
		$main::cfg{'APACHE_SUEXEC_USER_PREF'} . $main::cfg{'APACHE_SUEXEC_MIN_GID'},
		0644
	);
	return $rs if ($rs != 0);

	# Install the new file
	$rs = sys_command(
		"$main::cfg{'CMD_CP'} -pf $wrkDir/master.php.ini " .
		"$main::cfg{'PHP_STARTER_DIR'}/master/php5/php.ini"
	);
	return $rs if ($rs != 0);

	## PHP Browser Capabilities support file

	# Store the new file in working directory
	$rs = sys_command(
		"$main::cfg{'CMD_CP'} -pf $cfgDir/parts/master/php5/browscap.ini " .
		"$wrkDir/browscap.ini"
	);
	return $rs if ($rs != 0);

	# Set file permissions
	$rs = setfmode(
		"$wrkDir/browscap.ini",
		$main::cfg{'APACHE_SUEXEC_USER_PREF'} . $main::cfg{'APACHE_SUEXEC_MIN_UID'},
		$main::cfg{'APACHE_SUEXEC_USER_PREF'} . $main::cfg{'APACHE_SUEXEC_MIN_GID'},
		0644
	);
	return $rs if ($rs != 0);

	# Install the new file
	$rs = sys_command(
		"$main::cfg{'CMD_CP'} -pf $wrkDir/browscap.ini " .
		"$main::cfg{'PHP_STARTER_DIR'}/master/php5/browscap.ini"
	);
	return $rs if ($rs != 0);

	push_el(\@main::el, 'setup_gui_php()', 'Ending...');

	0;
}

################################################################################
# ispCP GUI pma configuration file and pma SQL control user - (Setup / Update)
#
# This subroutine built, store and install the PhpMyAdmin configuration file
#
# @return int 0 on success, -1 otherwise
#
sub setup_gui_pma {

	push_el(\@main::el, 'setup_gui_pma()', 'Starting...');

	my $cfgDir = "$main::cfg{'CONF_DIR'}/pma";
	my $bkpDir = "$cfgDir/backup";
	my $wrkDir = "$cfgDir/working";
	my $prodDir = "$main::cfg{'GUI_ROOT_DIR'}/tools/pma";

	# Converting to ASCII (Punycode)
	my $dbHost = idn_to_ascii($main::cfg{'DATABASE_HOST'}, 'utf-8');

	my ($rs, $blowfishSecret, $ctrlUser, $ctrlUserPwd, $cfgFile);

	# Saving the current production file if it exists
	if(-e "$prodDir/config.inc.php") {
		$rs = sys_command(
			"$main::cfg{'CMD_CP'} -p $prodDir/config.inc.php " .
			"$bkpDir/config.inc.php." . time
		);
		return -1 if($rs != 0);
	}

	# Setup:
	if(defined &setup_engine) {
		$ctrlUser = $main::ua{'db_pma_user'};
		$ctrlUserPwd = $main::ua{'db_pma_password'};
	# Update:
	} elsif(-e "$wrkDir/config.inc.php") {
		# Gets the pma configuration file
		($rs, $cfgFile) = get_file("$cfgDir/working/config.inc.php");
		return -1 if ($rs != 0);

		# Retrieving the needed values from the working file
		($blowfishSecret, $ctrlUser, $ctrlUserPwd) = map {
			$cfgFile =~ /\['$_'\]\s*=\s*'(.+)'/
		} qw /blowfish_secret controluser controlpass/;
	# Update recovery
	} else {
		print colored(['bold yellow'], "\n\n\tWARNING: ") .
			"Unable to found your working PMA configuration file !\n" .
			"\tA new one will be created.\n";

			# Ask for pma control username
			do {$rs = ask_db_pma_user();} while ($rs);

			# Ask for control user password
			do {$rs = ask_db_pma_password();} while ($rs);

			$ctrlUser = $main::ua{'db_pma_user'};
			$ctrlUserPwd = $main::ua{'db_pma_password'};
	}

	# Getting blowfish secret
	if(!defined $blowfishSecret) {
		$blowfishSecret = gen_sys_rand_num(31);
		$blowfishSecret =~ s/'/\\'/gi;
	}

	## Building the new file

	# Getting the template file
	($rs, $cfgFile) = get_file("$cfgDir/config.inc.tpl");
	return -1 if ($rs != 0);

	($rs, $cfgFile) = prep_tpl(
		{
			'{PMA_USER}' => $ctrlUser,
			'{PMA_PASS}' => $ctrlUserPwd,
			'{HOSTNAME}' => $dbHost,
			'{TMP_DIR}'  => "$main::cfg{'GUI_ROOT_DIR'}/phptmp",
			'{BLOWFISH}' => $blowfishSecret
		},
		$cfgFile
	);
	return -1 if ($rs != 0);

	# Storing the file in the working directory
	$rs = store_file(
		"$cfgDir/working/config.inc.php", $cfgFile, "$main::cfg{'ROOT_USER'}",
		"$main::cfg{'ROOT_GROUP'}", 0640
	);
	return -1 if ($rs != 0);

	# Installing the file in the production directory
	# Note: permission are set by the set-gui-permissions.sh script
	$rs = sys_command(
		"$main::cfg{'CMD_CP'} -f $cfgDir/working/config.inc.php $prodDir/"
	);
	return -1 if ($rs != 0);

	## Creating SQL control user account if needed

	if (defined $main::ua{'db_pma_user'}) {
		# Setting DSN
		@main::db_connect = (
			"DBI:mysql:mysql:$dbHost", $main::db_user, $main::db_pwd
		);

		# Forcing reconnection
		$main::db = undef;

		## We ensure the new user is not already registered and we remove the
		## old user if one exist

		my $i = 0;

		for ($main::cfg{'PMA_USER'}, $ctrlUserPwd) {
			if($main::cfg{'PMA_USER'} eq $ctrlUser && $i == 0) {
				$i++;
				next;
			}

			($rs) = doSQL(
				qq /
					DELETE FROM `tables_priv`
					WHERE `Host` = '$dbHost'
					AND `Db` = 'mysql' AND `User` = '$_';
				/
			);
			return -1 if ($rs != 0);

			($rs) = doSQL(
				qq /
					DELETE FROM `user`
					WHERE `Host` = '$dbHost'
					AND `User` = '$_';
				/
			);
			return -1 if ($rs != 0);

			($rs) = doSQL(
				qq /
					DELETE FROM `columns_priv`
					WHERE `Host` = '$dbHost'
					AND `User` = '$_';
				/
			);
			return -1 if ($rs != 0);
		}

		# Flushing privileges
		($rs) = doSQL('FLUSH PRIVILEGES');
		return -1 if ($rs != 0);

		# Adding the new pma control user
		($rs) = doSQL(
			qq/
				GRANT USAGE ON `mysql`.*
				TO '$ctrlUser'\@'$dbHost'
				IDENTIFIED BY '$ctrlUserPwd' ;
			/
		);
		return -1 if ($rs != 0);

		## Sets the rights for the pma control user

		($rs) = doSQL(
			qq/
				GRANT SELECT ON `mysql`.`db`
				TO '$ctrlUser'\@'$dbHost';
			/
		);
		return -1 if ($rs != 0);

		($rs) = doSQL(
			qq/
				GRANT SELECT (
					Host, User, Select_priv, Insert_priv, Update_priv, Delete_priv,
					Create_priv, Drop_priv, Reload_priv, Shutdown_priv, Process_priv,
					File_priv, Grant_priv, References_priv, Index_priv, Alter_priv,
					Show_db_priv, Super_priv, Create_tmp_table_priv,
					Lock_tables_priv, Execute_priv, Repl_slave_priv,
					Repl_client_priv
				)
				ON `mysql`.`user`
				TO '$ctrlUser'\@'$dbHost';
			/
		);
		return -1 if ($rs != 0);

		($rs) = doSQL(
			qq/
				GRANT SELECT ON mysql.host
				TO '$ctrlUser'\@'$dbHost';
			/
		);
		return -1 if ($rs != 0);

		($rs) = doSQL(
			qq/
				GRANT SELECT
					(Host, Db, User, Table_name, Table_priv, Column_priv)
				ON mysql.tables_priv
				TO '$ctrlUser'\@'$dbHost';
			/
		);
		return -1 if ($rs != 0);

		# Update the ispcp.conf file, reset the DSN and force reconnection on
		# the next query

		$rs = set_conf_val('PMA_USER', $ctrlUser);
		return -1 if ($rs != 0);

		$rs = store_conf();
		return -1 if ($rs != 0);
	}

	push_el(\@main::el, 'setup_gui_pma()', 'Ending...');

	0;
}

################################################################################
# ispCP Gui named configuration - (Setup / Update)
#
# This subroutine do the following tasks:
#  - Add Gui named cfg data in main Bind9 configuration file
#  - Built GUI named DNS record's file
#
# @return int 0 on success, other on failure
#
sub setup_gui_named {

	push_el(\@main::el, 'setup_gui_named()', 'Starting...');

	# Converting to ASCII (Punycode)
	my $baseServerVhost = idn_to_ascii($main::cfg{'BASE_SERVER_VHOST'}, 'utf-8');

	# Add GUI Bind9 cfg data
	my $rs = setup_gui_named_cfg_data($baseServerVhost);
	return $rs if($rs != 0);

	# Building GUI Bind9 DNS records file
	$rs = setup_gui_named_db_data(
		$main::cfg{'BASE_SERVER_IP'}, $baseServerVhost
	);
	return $rs if($rs != 0);

	push_el(\@main::el, 'setup_gui_named()', 'Ending...');

	0;
}

################################################################################
# ispCP Gui named cfg file - (Setup / Update)
#
# This subroutine do the following tasks:
#  - Add Gui named cfg data in main configuration file
#
# @return int 0 on success, other on failure
#
sub setup_gui_named_cfg_data {

	push_el(\@main::el, 'setup_gui_named_cfg_data()', 'Starting...');

	# If IDN, $base_vhost is already to ASCII (Punycode)
	my ($baseVhost) = @_;

	my ($rs, $rdata, $cfg);

	# Named directories paths
	my $cfgDir = $main::cfg{'CONF_DIR'};
	my $tpl_dir = "$cfgDir/bind/parts";
	my $bkpDir = "$cfgDir/bind/backup";
	my $wrkDir = "$cfgDir/bind/working";
	my $dbDir = $main::cfg{'BIND_DB_DIR'};

	if (!defined $baseVhost || $baseVhost eq '') {
		push_el(
			\@main::el, 'setup_gui_named_cfg_data()',
			'[FATAL] Undefined Input Data...'
		);
		return 1;
	}

	# Saving the current production file if it exists
	if(-e $main::cfg{'BIND_CONF_FILE'}) {
		$rs = sys_command(
			"$main::cfg{'CMD_CP'} -p $main::cfg{'BIND_CONF_FILE'} " .
			"$bkpDir/named.conf." . time
		);
		return $rs if ($rs != 0);
	}

	## Building of new configuration file

	# Loading all needed templates from /etc/ispcp/bind/parts
	my ($entry_b, $entry_e, $entry) = ('', '', '');

	($rs, $entry_b, $entry_e, $entry) = get_tpl(
		$tpl_dir, 'cfg_entry_b.tpl', 'cfg_entry_e.tpl', 'cfg_entry.tpl'
	);
	return $rs if ($rs != 0);

	# Preparation tags
	my %tags_hash = ('{DMN_NAME}' => $baseVhost, '{DB_DIR}' => $dbDir);

	# Replacement tags
	my ($entry_b_val, $entry_e_val, $entry_val) = ('', '', '');

	($rs, $entry_b_val, $entry_e_val, $entry_val) = prep_tpl(
		\%tags_hash, $entry_b, $entry_e, $entry
	);
	return $rs if ($rs != 0);

	# Loading working file from /etc/ispcp/bind/working/named.conf
	($rs, $cfg) = get_file("$wrkDir/named.conf");
	return $rs if ($rs != 0);

	# Building the new configuration file
	my $entry_repl = "$entry_b_val$entry_val$entry_e_val\n$entry_b$entry_e";

	($rs, $cfg) = repl_tag(
		$entry_b, $entry_e, $cfg, $entry_repl, 'setup_gui_named_cfg_data'
	);
	return $rs if ($rs != 0);

	## Storage and installation of new file - Begin

	# Store the new builded file in the working directory
	$rs = store_file(
		"$wrkDir/named.conf", $cfg, $main::cfg{'ROOT_USER'},
		$main::cfg{'ROOT_GROUP'}, 0644
	);
	return $rs if ($rs != 0);

	# Install the new file in the production directory
	$rs = sys_command(
		"$main::cfg{'CMD_CP'} -pf " .
		"$wrkDir/named.conf $main::cfg{'BIND_CONF_FILE'}"
	);
	return $rs if ($rs != 0);

	push_el(\@main::el, 'setup_gui_named_cfg_data()', 'Ending...');

	0;
}

################################################################################
# ispCP Gui named dns record's - (Setup / Update)
#
# This subroutine does the following tasks:
#  - Build GUI named dns record's file
#
# @return int 0 on success, other on failure
#
sub setup_gui_named_db_data {

	push_el(\@main::el, 'setup_gui_named_db_data()', 'Starting...');

	# If IDN, $baseVhost is already to ASCII (Punycode)
	my ($baseIp, $baseVhost) = @_;

	if (!defined $baseVhost || $baseVhost eq '') {
		push_el(
			\@main::el, 'add_named_db_data()', 'FATAL: Undefined Input Data...'
		);

		return 1;
	}

	my ($rs, $wrkFileContent, $entries);

	# Slave DNS  - Address IP
	my $secDnsIp = $main::cfg{'SECONDARY_DNS'};

	# Directories paths
	my $cfgDir = "$main::cfg{'CONF_DIR'}/bind";
	my $bkpDir = "$cfgDir/backup";
	my $wrkDir = "$cfgDir/working";
	my $dbDir = $main::cfg{'BIND_DB_DIR'};

	# Zone file name
	my $dbFname = "$baseVhost.db";

	# Named zone files paths
	my $sysCfg = "$dbDir/$dbFname";
	my $wrkCfg = "$wrkDir/$dbFname";
	my $bkpCfg = "$bkpDir/$dbFname";

	## Dedicated tasks for Install or Updates process

	if (defined &update_engine) {
		# Saving the current production file if it exists
		if(-e $sysCfg) {
			$rs = sys_command("$main::cfg{'CMD_CP'} -p $sysCfg $bkpCfg." . time);
			return $rs if ($rs != 0);
		}

		# Load the current working db file
		($rs, $wrkFileContent) = get_file($wrkCfg);

		if($rs != 0) {
			push_el(
				\@main::el, 'add_named_db_data()',
				"[WARNING] $baseVhost: Working db file not found!. " .
				'Re-creation from scratch is needed...'
			);

			$wrkFileContent = \$entries;
		}
	} else {
		$wrkFileContent = \$entries;
	}

	## Building new configuration file

	# Loading the template from /etc/ispcp/bind/parts
	($rs, $entries) = get_file("$cfgDir/parts/db_master_e.tpl");
	return $rs if ($rs != 0);

	# Replacement tags
	($rs, $entries) = prep_tpl(
		{
			'{DMN_NAME}' => $baseVhost,
			'{DMN_IP}' => $baseIp,
			'{BASE_SERVER_IP}' => $baseIp,
			'{SECONDARY_DNS_IP}' => ($secDnsIp ne '') ? $secDnsIp : $baseIp
		},
		$entries
	);
	return $rs if ($rs != 0);

	# Create or Update serial number according RFC 1912
	$rs = getSerialNumber(\$baseVhost, \$entries, \$wrkFileContent);
	return $rs if($rs != 0);

	## Store and install

	# Store the file in the working directory
	$rs = store_file(
		$wrkCfg, $entries, $main::cfg{'ROOT_USER'}, $main::cfg{'ROOT_GROUP'},
		0644
	);
	return $rs if ($rs != 0);

	# Install the file in the production directory
	$rs = sys_command("$main::cfg{'CMD_CP'} -pf $wrkCfg $dbDir/");
	return $rs if ($rs != 0);

	push_el(\@main::el, 'setup_gui_named_db_data()', 'Ending...');

	0;
}

################################################################################
# Setup rkhunter - (Setup / Update)
#
# This subroutine process the following tasks:
#
#  - update rkhunter database files (only during setup process)
#  - Debian specific: Updates the configuration file and cron task, and
#  remove default unreadable created log file
#
# @return int 0 on success, other on failure
#
sub setup_rkhunter {

	push_el(\@main::el, 'setup_rkhunter()', 'Starting...');

	my ($rs, $rdata);

	# Deleting any existent log files
	$rs = sys_command("$main::cfg{'CMD_RM'} -f $main::cfg{'RKHUNTER_LOG'}*");
	return $rs if($rs != 0);

	# Updates the rkhunter configuration provided by Debian like distributions
	# to disable the default cron task (ispCP provides its own cron job for
	# rkhunter)
	if(-e '/etc/default/rkhunter') {
		# Get the file as a string
		($rs, $rdata) = get_file('/etc/default/rkhunter');
		return $rs if($rs != 0);

		# Disable cron task default
		$rdata =~ s@CRON_DAILY_RUN="yes"@CRON_DAILY_RUN="no"@gmi;

		# Saving the modified file
		$rs = save_file('/etc/default/rkhunter', $rdata);
		return $rs if($rs != 0);
	}

	# Update weekly cron task provided by Debian like distributions to avoid
	# creation of unreadable log file
	if(-e '/etc/cron.weekly/rkhunter') {
		# Get the rkhunter file content
		($rs, $rdata) = get_file('/etc/cron.weekly/rkhunter');
		return $rs if($rs != 0);

		# Adds `--nolog`option to avoid unreadable log file
		$rdata =~ s/(--versioncheck)/$1 --nolog/g;
		$rdata =~ s/(--update)/$1 --nolog/g;

		# Saving the modified file
		$rs = save_file('/etc/cron.weekly/rkhunter', $rdata);
		return $rs if($rs != 0);
	}

	# Updates rkhunter database files (Only during setup process)
	# @todo Review this s...
	if(defined &setup_engine) {
		if (sys_command("which rkhunter > /dev/null") == 0 ) {
			# Here, we run the command with `--nolog` option to avoid creation
			# of unreadable log file. The log file will be created later by an
			# ispCP cron task
			$rs = sys_command("rkhunter --update --nolog -q");
			return $rs if($rs != 0);
		}
	}

	push_el(\@main::el, 'setup_rkhunter()', 'Ending...');

	0;
}

################################################################################
#                           High Level Subroutines                             #
################################################################################

################################################################################
# Executes all the subroutines to setup/update all services
#
# @return void
# todo make all subroutine called here idempotent
#
sub setup_services_cfg {

	push_el(\@main::el, 'setup_services_cfg()', 'Starting...');

	##  Dedicated tasks for setup process
	if(defined &setup_engine) {
		# For 'rpm' package the user/group creation is supported by maintenance
		# scripts
		if (!defined($ARGV[0]) || $ARGV[0] ne '-rpm') {
			subtitle('ispCP users and groups:');
			print_status(setup_system_users(), 'exit_on_error');
		}

		for (
			[\&setup_system_dirs, 'ispCP directories:'],
			[\&setup_config, 'ispCP configuration file:'],
			[\&setup_ispcp_database, 'ispCP database:'],
			[\&setup_default_language_table, 'ispCP default language table:'],
			[\&setup_default_sql_data, 'ispCP default SQL data:'],
			[\&setup_hosts, 'ispCP system hosts file:']
		) {
			subtitle($_->[1]);
			print_status(&{$_->[0]}, 'exit_on_error');
		}
	}

	# Common tasks (Setup/Update)
	for (
		[\&setup_crontab, 'ispCP Crontab file:'],
		[\&setup_named, 'ispCP Bind9 main configuration file:'],
		[\&setup_fastcgi_modules, 'ispCP Apache fastCGI modules configuration'],
		[\&setup_httpd_main_vhost, 'ispCP Apache main vhost file:'],
		[\&setup_awstats_vhost, 'ispCP Apache AWStats vhost file:'],
		[\&setup_mta, 'ispCP Postfix configuration files:'],
		[\&setup_po, 'ispCP Courier-Authentication:'],
		[\&setup_ftpd, 'ispCP ProFTPd configuration file:'],
		[\&setup_ispcp_daemon_network, 'ispCP init scripts:']
	) {
		subtitle($_->[1]);
		print_status(&{$_->[0]}, 'exit_on_error');
	}

	push_el(\@main::el, 'setup_services_cfg()', 'Ending...');
}

################################################################################
# Executes all the subroutines to build all GUI related configuration files
#
# @return void
#
sub setup_gui_cfg {

	push_el(\@main::el, 'rebuild_gui_cfg()', 'Starting...');

	for (
		[\&setup_gui_named, 'ispCP GUI Bind9 configuration:'],
		[\&setup_gui_php, 'ispCP GUI fastCGI/PHP configuration:'],
		[\&setup_gui_httpd, 'ispCP GUI vhost file:'],
		[\&setup_gui_pma, 'ispCP PMA configuration file:']
	) {
		subtitle($_->[1]);
		print_status(&{$_->[0]}, 'exit_on_error');
	}

	push_el(\@main::el, 'rebuild_gui_cfg()', 'Ending...');
}

################################################################################
# Run all update additional task such as rkhunter configuration
#
# @return void
#
sub additional_tasks{

	push_el(\@main::el, 'additional_tasks()', 'Starting...');

	subtitle('ispCP Rkhunter configuration:');
	my $rs = setup_rkhunter();
	print_status($rs, 'exit_on_error');

	subtitle('ispCP System cleanup:');
	setup_cleanup();
	print_status(0);

	push_el(\@main::el, 'additional_tasks()', 'Ending...');
}

1;
