# ispCP ω (OMEGA) a Virtual Hosting Control Panel
# Copyright (C) 2006-2010 by isp Control Panel - http://i-mscp.net
#
# Author: Laurent Declercq <laurent.declercq@i-mscp.net>
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

package PerlLib::Dialog::Query;
use strict;
use warnings;
no strict qw /refs vars/;
use Term::ANSIColor qw(:constants colored);
$Term::ANSIColor::AUTORESET = 1;
use Text::Wrap qw(wrap);
$Text::Wrap::columns = 72;
use base 'Exporter';

@EXPORT = (
	'setQuery', 'printQuery', 'printNotice','printConfirm', 'printWarning',
	'printError'
);

$VALUE = '';

$HOSTNAME = 'Please enter a fully qualified hostname [%s]: ';
$HOSTNAME_WARNING = '%s is not a fully qualified hostname! Be aware that you cannot use this domain for websites.';
$HOSTNAME_CONFIRM = 'Are you sure you want to use this hostname? [Y/n]: ';
$HOSTNAME_ERROR = 'The hostname is not a valid domain name!';

$ETH = 'Please enter the system network address [%s]: ';
$ETH_ERROR = 'IP address not valid, please retry!';

$VHOST = 'Please enter the domain name where i-MSCP will be reachable on [%s]: ';
$VHOST_ERROR = 'The domain name is not valid!';

$DB_HOST = 'Please enter SQL server hostname [localhost]: ';
$DB_HOST_ERROR = 'Wrong SQL hostname! See RFC 1123 for more information...';

$DB_NAME = 'Please enter i-MSCP SQL database name [imscp]: ';

$DB_USER = 'Please enter i-MSCP SQL user name [root]: ';

$DB_PASSWORD = 'Please enter i-MSCP SQL password [none]: ';
$DB_PASSWORD_CONFIRM = 'Please repeat i-MSCP SQL password: ';
$DB_PASSWORD_ERROR = 'Passwords do not match!';

$DB_FTP_USER = 'Please enter i-MSCP FTP SQL user [vftp]: ';
$DB_FTP_USER_ERROR = 'FTP SQL user must not be identical to the i-MSCP SQL user!';

$DB_FTP_PASSWORD = 'Please enter i-MSCP FTP SQL user password [auto generate]: ';
$DB_FTP_PASSWORD_CONFIRM = 'Please repeat i-MSCP ftp SQL user password: ';
$DB_FTP_PASSWORD_NOTICE = 'i-MSCP ftp SQL user password set to: %s';
$DB_FTP_PASSWORD_ERROR = 'Passwords do not match!';

$ADMIN = 'Please enter administrator login name [admin]: ';

$ADMIN_PASSWORD = 'Please enter administrator password: ';
$ADMIN_PASSWORD_CONFIRM = 'Please repeat administrator password: ';
$ADMIN_PASSWORD_ERROR_1 = 'Password cannot be empty!';
$ADMIN_PASSWORD_ERROR_2 = 'Password is too short!';
$ADMIN_PASSWORD_ERROR_3 = 'Passwords do not match!';
$ADMIN_PASSWORD_ERROR_4 = 'Password must contain at least digits and chars!';

$ADMIN_EMAIL = 'Please enter administrator e-mail address: ';
$ADMIN_EMAIL_ERROR = 'E-mail address not valid!';

$SECOND_DNS = 'Secondary DNS server address IP (optional) []: ';
$SECOND_DNS_ERROR = 'IP address not valid, please retry!';

$RESOLVER = 'Do you want allow the system resolver to use the local nameserver? [Y/n]: ';
$RESOLVER_ERROR = 'You entered an unrecognized value, please retry!';

$MYSQL_PREFIX = 'Use MySQL Prefix? Possible values: [i]nfront, [b]ehind, [n]one. [none]: ';
$MYSQL_PREFIX_ERROR = 'You entered an unrecognized value, please retry!';

$DB_PMA_USER = 'Please enter i-MSCP PMA control user [%s]: ';
$DB_PMA_USER_ERROR_1 = 'i-MSCP PMA control user must not be identical to system SQL user!';
$DB_PMA_USER_ERROR_2 = 'i-MSCP PMA control user must not be identical to FTP SQL user!';

$DB_PMA_PASSWORD = 'Please enter i-MSCP PMA control user password [auto generate]: ';
$DB_PMA_PASSWORD_CONFIRM = 'Please repeat i-MSCP PMA control user password: ';
$DB_PMA_PASSWORD_NOTICE = 'PMA control user password set to: %s';
$DB_PMA_PASSWORD_ERROR = 'Passwords do not match!';

$FASTCGI = 'Please select a Fast CGI module: [f]cgid or fast[c]gi. [fcgid]: ';
$FASTCGI_ERROR = 'Only \'[f]cgid\' or \'fast[c]gi\' are allowed!';

$TIMEZONE = 'Please enter Server\'s Timezone [%s]: ';
$TIMEZONE_ERROR = '\'%s\' is not a valid timezone! The continent and the city, both must start with a capital letter, e.g. Europe/London';

$AWSTATS_ON = 'Should AWStats be activated? [no]: ';
$AWSTATS_ON_ERROR = 'Only \'[y]es\' and \'[n]o\' are allowed!';

$AWSTATS_DYN = 'Please select AWStats mode: Possible values [d]ynamic and [s]tatic. [dynamic]:';
$AWSTATS_DYN_ERROR = 'Only \'[d]ynamic\' or \'[s]tatic\' are allowed!';

$DIAL = '';

sub setQuery {
	my $ask = uc(shift||'');
	$DIAL = $ask if defined ${$ask};
}

sub printQuery {
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

	# Term::ReadPassword::read_password() prompt issue...
	undef;
}

sub spacer { print "\n"; }

1;

__END__
