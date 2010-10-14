# ispCP ω (OMEGA) a Virtual Hosting Control Panel
# Copyright (C) 2006-2010 by isp Control Panel - http://ispcp.net
#
# Author: Laurent Declercq <laurent.declercq@ispcp.net>
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

package PerlLib::Dialog::Asks;
use strict;
no strict 'refs';
use warnings;

use Term::ReadKey;
use Term::ANSIColor qw(:constants colored);
$Term::ANSIColor::AUTORESET = 1;
use Text::Wrap qw(wrap);
$Text::Wrap::columns = 72;

use base 'Exporter';

our @EXPORT = (
	'setAsk', 'printAsk', 'printNotice','printConfirm', 'printWarning',
	'printError'
);

our $VALUE = '';

our $HOSTNAME = 'Please enter a fully qualified hostname. The default value is [%s]: ';
our $HOSTNAME_WARNING = '%s is not a fully qualified hostname! Be aware you cannot use this domain for websites.';
our $HOSTNAME_CONFIRM = 'Are you sure you want to use this hostname? [Y/n]: ';
our $HOSTNAME_ERROR = 'Hostname is not a valid domain name!';

our $ETH = 'Please enter the system network address [%s]: ';
our $ETH_ERROR = 'Ip address not valid, please retry!';

our $VHOST = 'Please enter the domain name from where ispCP OMEGA will be reachable [%s]: ';
our $VHOST_ERROR = 'Vhost name is not valid!';

our $DB_HOST = 'Please enter SQL server host [localhost]: ';
our $DB_HOST_ERROR = 'Wrong SQL hostname! See RFC 1123 for more information...';

our $DB_NAME = 'Please enter system SQL database [ispcp]: ';

our $DB_USER = 'Please enter system SQL user [root]: ';

our $DB_PASSWORD = 'Please enter system SQL password [none]: ';
our $DB_PASSWORD_CONFIRM = 'Please repeat system SQL password: ';
our $DB_PASSWORD_ERROR = 'Passwords do not match!';

our $DB_FTP_USER = 'Please enter ispCP ftp SQL user. [vftp]: ';
our $DB_FTP_USER_ERROR = 'Ftp SQL user must not be identical to the system SQL user!';

our $DB_FTP_PASSWORD = 'Please enter ispCP ftp SQL user password. [auto generate]: ';
our $DB_FTP_PASSWORD_CONFIRM = 'Please repeat ispCP ftp SQL user password: ';
our $DB_FTP_PASSWORD_NOTICE = 'ispCP ftp SQL user password set to: %s';
our $DB_FTP_PASSWORD_ERROR = 'Passwords do not match!';

our $ADMIN = 'Please enter administrator login name. [admin]: ';

our $ADMIN_PASSWORD = 'Please enter administrator password:';
our $ADMIN_PASSWORD_CONFIRM = 'Please repeat administrator password: ';
our $ADMIN_PASSWORD_ERROR_1 = 'Password cannot be empty!';
our $ADMIN_PASSWORD_ERROR_2 = 'Password too short!';
our $ADMIN_PASSWORD_ERROR_3 = 'Passwords do not match!';
our $ADMIN_PASSWORD_ERROR_4 = 'Passwords must contain at least digits and chars!';

our $ADMIN_EMAIL = 'Please enter administrator e-mail address: ';
our $ADMIN_EMAIL_ERROR = 'E-mail address not valid!';

our $SECOND_DNS = 'IP of Secondary DNS. (optional) []:';
our $SECOND_DNS_ERROR = 'Ip address not valid, please retry!';

our $RESOLVER = 'Do you want allow the system resolver to use the local nameserver sets by ispCP ? [Y/n]: ';
our $RESOLVER_ERROR = 'You entered an unrecognized value!';

our $MYSQL_PREFIX = 'Use MySQL Prefix ? Possible values: [i]nfront, [b]ehind, [n]one. [none]: ';
our $MYSQL_PREFIX_ERROR = 'Not allowed Value, please retry!';

our $DB_PMA_USER = 'Please enter ispCP phpMyAdmin Control user [%s]: ';
our $DB_PMA_USER_ERROR_1 = 'PhpMyAdmin Control user must not be identical to system SQL user!';
our $DB_PMA_USER_ERROR_2 = 'PhpMyAdmin Control user must not be identical to ftp SQL user!';

our $DB_PMA_PASSWORD = 'Please enter ispCP PhpMyAdmin Control user password [auto generate]: ';
our $DB_PMA_PASSWORD_CONFIRM = 'Please repeat ispCP PhpMyAdmin Control user password: ';
our $DB_PMA_PASSWORD_NOTICE = 'PhpMyAdmin Control user password set to: %s';
our $DB_PMA_PASSWORD_ERROR = 'Passwords do not match';

our $FASTCGI = 'FastCGI Version: [f]cgid or fast[c]gi. [fcgid]:';
our $FASTCGI_ERROR = 'Only \'[f]cgid\' or \'fast[c]gi\' are allowed!';

our $TIMEZONE = 'Server\'s Timezone [%s]:';
our $TIMEZONE_ERROR = '%s is not a valid Timezone! The continent and the city both must start with a capital letter, e.g. Europe/London';

our $AWSTATS_ON = 'Activate AWStats [no]: ';
our $AWSTATS_ON_ERROR = 'Only \'(y)es\' and \'(n)o\' are allowed!';

our $AWSTATS_DYN = 'AWStats Mode: tPossible values [d]ynamic and [s]tatic. [dynamic]:';
our $AWSTATS_DYN_ERROR = 'Only \'[d]ynamic\' or \'[s]tatic\' are allowed!';

our $DIAL = '';

sub setAsk {
	my $ask = uc(shift||'');
	$DIAL = $ask if defined ${$ask};
}

sub printAsk {
	spacer();
	$VALUE = shift||$VALUE;
	pr(${$DIAL});
}

sub printConfirm {
	$VALUE = shift||$VALUE;
	spacer();
	pr(${"$DIAL\_CONFIRM"});
}

sub printNotice {
	$VALUE = shift||$VALUE;
	spacer();
	pr(colored(['bold blue'], '[NOTICE] ') . ${"$DIAL\_NOTICE"});
	spacer();
}

sub printWarning {
	$VALUE = shift||$VALUE;
	spacer();
	pr(colored(['bold yellow'], '[WARNING] ') . ${"$DIAL\_WARNING"});
	spacer();
}

sub printError {
	$VALUE = shift||$VALUE;
	my $errNb = shift;

	my $DIAL_ERROR;

	if(defined $errNb) {
		$DIAL_ERROR = "${DIAL}_ERROR_${errNb}";
	} else {
		$DIAL_ERROR ="${DIAL}_ERROR";
	}

	spacer();
	pr(colored(['bold red'], "[ERROR] ") . ${$DIAL_ERROR});
	spacer();
}

sub pr {
	if($VALUE ne '') {
		printf wrap("\t", "\t", shift), $VALUE;
	} else {
		print wrap("\t", "\t", shift);
	}

	$VALUE = '';
}

sub spacer { print "\n"; }


1;

__END__
