#!/usr/bin/perl

# i-MSCP a internet Multi Server Control Panel
#
# Copyright (C) 2006-2010 by isp Control Panel - http://ispcp.net
# Copyright (C) 2010-2012 by internet Multi Server Control Panel - http://i-mscp.net
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
# Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
# internet Multi Server Control Panel. All Rights Reserved.
#
# The i-MSCP Home Page is:
#
#    http://i-mscp.net
#

# iMSCP specific:
#
# If you do not want this file to be regenerated from scratch during i-MSCP
# update process, change the 'AMAVIS_REGENERATE' parameter value to 'no' in the
# imscp.conf file.

use strict;

# COMMONLY ADJUSTED SETTINGS:

@bypass_virus_checks_maps = (1); # controls running of anti-virus code
# @bypass_spam_checks_maps  = (1); # controls running of anti-spam code
# $bypass_decode_parts = 1; # controls running of decoders&dearchivers

$max_servers = 2; # num of pre-forked children (2..30 is common), -m
$daemon_user  = 'vscan'; # (no default; customary: vscan or amavis), -u
$daemon_group = 'vscan'; # (no default; customary: vscan or amavis), -g

$mydomain = "{AMAVIS_DOMAIN}"; # a convenient default for other settings
$myhostname = '{AMAVIS_HOSTNAME}';
$MYHOME = '/var/spool/amavis'; # a convenient default for other settings, -H
$TEMPBASE = "$MYHOME/tmp"; # working directory, needs to exist, -T
$ENV{TMPDIR} = $TEMPBASE; # environment variable TMPDIR, used by SA, etc.
$QUARANTINEDIR = '/var/spool/amavis/virusmails'; # -Q
$X_HEADER_TAG  = 'X-Virus-Scanned'; # after-default
$X_HEADER_LINE = "i-MSCP MailStorm at $myhostname";  # after-default
$allowed_added_header_fields{lc('X-Spam-Checker-Version')} = 1;
# $quarantine_subdir_levels = 1; # add level of subdirs to disperse quarantine
# $release_format = 'resend'; # 'attach', 'plain', 'resend'
# $report_format  = 'arf'; # 'attach', 'plain', 'resend', 'arf'

# $daemon_chroot_dir = $MYHOME; # chroot directory or undef, -R

$db_home   = "$MYHOME/db"; # dir for bdb nanny/cache/snmp databases, -D
$helpers_home = "$MYHOME/var"; # working directory for SpamAssassin, -S
$lock_file = "$MYHOME/var/amavisd.lock"; # -L
$pid_file  = "$MYHOME/var/amavisd.pid"; # -P
#NOTE: create directories $MYHOME/tmp, $MYHOME/var, $MYHOME/db manually

$log_level = {AMAVIS_LOG_LEVEL}; # verbosity 0..5, -d
$log_recip_templ = undef; # disable by-recipient level-0 log entries
$DO_SYSLOG = 1; # log via syslogd (preferred)
$syslog_facility = 'mail';
	# Syslog facility as a string e.g.: mail, daemon, user, local0, ... local7
$syslog_priority = 'notice'; # Syslog base (minimal) priority as a string,
	# choose from: emerg, alert, crit, err, warning, notice, info, debug

$enable_db = 1; # enable use of BerkeleyDB/libdb (SNMP and nanny)
$enable_global_cache = 1; # enable use of libdb-based cache if $enable_db=1
$nanny_details_level = 2; # nanny verbosity: 1: traditional, 2: detailed
$enable_dkim_verification = 1; # enable DKIM signatures verification
$enable_dkim_signing = 1; # load DKIM signing code, keys defined by dkim_key

@local_domains_maps = (read_hash("/etc/imscp/amavisd/working/amavisd.domains"));

@mynetworks = qw(127.0.0.0/8 [::1] [FE80::]/10 [FEC0::]/10 10.0.0.0/24);

$unix_socketname = "$MYHOME/amavisd.sock"; # amavisd-release or amavis-milter
	# option(s) -p overrides $inet_socket_port and $unix_socketname

#$inet_socket_port = [10024, 10026, 9998];	 # listen on this local TCP port(s)
$inet_socket_port = [10024, 10026];	 # listen on this local TCP port(s)

# Mailzu
#$interface_policy{'9998'} = 'MAILZU';
#$policy_bank{'MAILZU'} = {
#   protocol => 'AM.PDP',
#	inet_acl => [qw( 127.0.0.1 [::1] {BASE_SERVER_IP} )],
#};

$policy_bank{'MYNETS'} = { # mail originating from @mynetworks
	originating => 1, # is true in MYNETS by default, but let's make it explicit
	os_fingerprint_method => undef,  # don't query p0f for internal clients
};

# it is up to MTA to re-route mail from authenticated roaming users or
# from internal hosts to a dedicated TCP port (such as 10026) for filtering
$interface_policy{'10026'} = 'ORIGINATING';

$policy_bank{'ORIGINATING'} = { # mail supposedly originating from our users
	originating => 1, # declare that mail was submitted by our smtp client
	allow_disclaimers => 1, # enables disclaimer insertion if available
	# notify administrator of locally originating malware
	virus_admin_maps => ['{DEFAULT_ADMIN_ADDRESS}'],
	spam_admin_maps => ['{DEFAULT_ADMIN_ADDRESS}'],
	warnbadhsender => 1,
	# forward to a smtpd service providing DKIM signing service
	#forward_method => 'smtp:[127.0.0.1]:10027',
	# force MTA conversion to 7-bit (e.g. before DKIM signing)
	smtpd_discard_ehlo_keywords => ['8BITMIME'],
	bypass_banned_checks_maps => [1], # allow sending any file names and types
	terminate_dsn_on_notify_success => 0, # don't remove NOTIFY=SUCCESS option
};

$interface_policy{'SOCK'} = 'AM.PDP-SOCK'; # only applies with $unix_socketname

# Use with amavis-release over a socket or with Petr Rehor's amavis-milter.c
# (with amavis-milter.c from this package or old amavis.c client use 'AM.CL'):
$policy_bank{'AM.PDP-SOCK'} = {
	protocol => 'AM.PDP',
	auth_required_release => 0, # do not require secret_id for amavisd-release
};

$policy_bank{'MILD_WHITELIST'} = {
	score_sender_maps => [ { '.' => [-1.8] } ],
};

$policy_bank{'WHITELIST'} = {
	bypass_spam_checks_maps => [1],
	spam_lovers_maps => [1],
};

$policy_bank{'NOVIRUSCHECK'} = {
	bypass_decode_parts => 1,
	bypass_virus_checks_maps => [1],
	virus_lovers_maps => [1],
};

$policy_bank{'NOBANNEDCHECK'} = {
	bypass_banned_checks_maps => [1],
	banned_files_lovers_maps  => [1],
};

$sa_tag_level_deflt = -9999.9; # add spam info headers if at, or above that level
$sa_tag2_level_deflt = 4.3; # add 'spam detected' headers at that level
$sa_kill_level_deflt = 10; # triggers spam evasive actions (e.g. blocks mail)
$sa_dsn_cutoff_level = 10; # spam level beyond which a DSN is not sent
$sa_crediblefrom_dsn_cutoff_level = 18; # likewise, but for a likely valid From
# $sa_quarantine_cutoff_level = 25; # spam level beyond which quarantine is off
$penpals_bonus_score = 8; # (no effect without a @storage_sql_dsn database)
$penpals_threshold_high = $sa_kill_level_deflt; # don't waste time on hi spam
$bounce_killer_score = 100; # spam score points to add for joe-jobbed bounces

$sa_mail_body_size_limit = 800*1024; # don't waste time on SA if mail is larger
$sa_local_tests_only = 0; # only tests which do not require internet access?

# Amavis database
#@storage_sql_dsn = (
#	[
#		'DBI:mysql:database={AMAVIS_DATABASE};host={DATABASE_HOST};port=3306',
#		'{AMAVIS_SQL_USER}', '{AMAVIS_SQL_PASSWORD}'
#	]
#);
#@lookup_sql_dsn = @storage_sql_dsn;

$timestamp_fmt_mysql = 1; # if using MySQL *and* msgs.time_iso is TIMESTAMP;
	# defaults to 0, which is good for non-MySQL or if msgs.time_iso is CHAR(16)

$sql_allow_8bit_address = 1; # maddr.email: VARCHAR (0), VARBINARY/BYTEA (1)
$sql_partition_tag = sub { my($msginfo)=@_; iso8601_week($msginfo->rx_time) };

$virus_admin = '{DEFAULT_ADMIN_ADDRESS}'; # notifications recip.
$banned_admin = '{DEFAULT_ADMIN_ADDRESS}';
$spam_admin = '{DEFAULT_ADMIN_ADDRESS}';

$mailfrom_notify_admin = "virusalert\@$mydomain"; # notifications sender
$mailfrom_notify_recip = "virusalert\@$mydomain"; # notifications sender
$mailfrom_notify_spamadmin = "spam.police\@$mydomain"; # notifications sender
$mailfrom_to_quarantine = ''; # null return path; uses original sender if undef

@addr_extension_virus_maps = ('virus');
@addr_extension_banned_maps = ('banned');
@addr_extension_spam_maps = ('spam');
@addr_extension_bad_header_maps = ('badh');
$recipient_delimiter = '+'; # undef disables address extensions altogether
	# when enabling addr extensions do also Postfix/main.cf: recipient_delimiter=+

$path = '/usr/local/sbin:/usr/local/bin:/usr/sbin:/sbin:/usr/bin:/bin';
# $dspam = 'dspam';

# For MAILZU
$banned_files_quarantine_method = 'sql:';
$spam_quarantine_method = 'sql:';
#$virus_quarantine_method = 'sql:';

# Note: It's just a sample, change it as you want...
@author_to_policy_bank_maps = ( {
  # 'friends.example.net' => 'WHITELIST,NOBANNEDCHECK',
  # 'user1@cust.example.net' => 'WHITELIST,NOBANNEDCHECK',
    '.ebay.com' => 'WHITELIST',
    '.ebay.co.uk' => 'WHITELIST',
    'ebay.at' => 'WHITELIST',
    'ebay.ca' => 'WHITELIST',
    'ebay.de' => 'WHITELIST',
    'ebay.fr' => 'WHITELIST',
    '.paypal.co.uk' => 'WHITELIST',
    '.paypal.com' => 'WHITELIST', # author signatures
    '.paypal.fr' => 'WHITELIST',
    './@paypal.com' => 'WHITELIST', # 3rd-party sign. by paypal.com
    './@paypal.fr' => 'WHITELIST',  # 3rd-party sign. by paypal.com
    'alert.bankofamerica.com' => 'WHITELIST',
    'amazon.com' => 'WHITELIST',
    'cisco.com' => 'WHITELIST',
    '.cnn.com' => 'WHITELIST',
    'skype.net' => 'WHITELIST',
    'welcome.skype.com' => 'WHITELIST',
    'cc.yahoo-inc.com' => 'WHITELIST',
    'cc.yahoo-inc.com/@yahoo-inc.com' => 'WHITELIST',
    'google.com' => 'MILD_WHITELIST',
    'googlemail.com' => 'MILD_WHITELIST',
    './@googlegroups.com' => 'MILD_WHITELIST',
    './@yahoogroups.com' => 'MILD_WHITELIST',
    './@yahoogroups.co.uk' => 'MILD_WHITELIST',
    './@yahoogroupes.fr' => 'MILD_WHITELIST',
    'yousendit.com' => 'MILD_WHITELIST',
    'meetup.com' => 'MILD_WHITELIST',
  } );

@additional_perl_modules = qw(
	Mail::SpamAssassin::BayesStore::SQL
	Mail::SpamAssassin::BayesStore::MySQL
	unicore/lib/gc_sc/Alnum.pl
	unicore/lib/gc_sc/Alpha.pl
	auto/POSIX/SigAction/new.al
	unicore/lib/gc_sc/Digit.pl
	unicore/lib/gc_sc/SpacePer.pl
	Mail/SpamAssassin/Plugin/FreeMail.pm
	Mail/SpamAssassin/SQLBasedAddrList.pm
);

$MAXLEVELS = 14;
$MAXFILES = 1500;
$MIN_EXPANSION_QUOTA = 100*1024; # bytes (default undef, not enforced)
$MAX_EXPANSION_QUOTA = 300*1024*1024; # bytes (default undef, not enforced)

$sa_spam_subject_tag = '***SPAM(_SCORE_)*** ';
$sa_spam_report_header = 1;

$defang_virus  = 1; # MIME-wrap passed infected mail
$defang_banned = 1; # MIME-wrap passed mail containing banned name
$defang_undecipherable = 1;
# for defanging bad headers only turn on certain minor contents categories:
$defang_by_ccat{+CC_BADH.",3"} = 1; # NUL or CR character in header
$defang_by_ccat{+CC_BADH.",5"} = 1; # header line longer than 998 characters
$defang_by_ccat{+CC_BADH.",6"} = 1; # header field syntax error

# OTHER MORE COMMON SETTINGS (defaults may suffice):

# $myhostname = 'host.example.com'; # must be a fully-qualified domain name!

$notify_method = 'smtp:[127.0.0.1]:10029';
$forward_method = 'smtp:[127.0.0.1]:10025';  # set to undef with milter!

$final_virus_destiny = D_DISCARD;
# $final_banned_destiny = D_BOUNCE;
# $final_spam_destiny = D_BOUNCE;
# $final_bad_header_destiny = D_PASS;
# $bad_header_quarantine_method = undef;

$final_spam_destiny = D_DISCARD;
$warnvirusrecip = 1;
$warnbannedrecip = 1;

# $os_fingerprint_method = 'p0f:*:2345';  # to query p0f-analyzer.pl

## hierarchy by which a final setting is chosen:
##  policy bank (based on port or IP address) -> *_by_ccat
##  *_by_ccat (based on mail contents) -> *_maps
##  *_maps (based on recipient address) -> final configuration value

# SOME OTHER VARIABLES WORTH CONSIDERING (see amavisd.conf-default for all)

# $warnbadhsender,
# $warnvirusrecip, $warnbannedrecip, $warnbadhrecip, (or @warn*recip_maps)
#
# @bypass_virus_checks_maps, @bypass_spam_checks_maps,
# @bypass_banned_checks_maps, @bypass_header_checks_maps,
#
# @virus_lovers_maps, @spam_lovers_maps,
# @banned_files_lovers_maps, @bad_header_lovers_maps,
#
# @blacklist_sender_maps, @score_sender_maps,
#
# $clean_quarantine_method, $virus_quarantine_to, $banned_quarantine_to,
# $bad_header_quarantine_to, $spam_quarantine_to,
#
# $defang_bad_header, $defang_undecipherable, $defang_spam

# REMAINING IMPORTANT VARIABLES ARE LISTED HERE BECAUSE OF LONGER ASSIGNMENTS

@keep_decoded_original_maps = (new_RE(
 qr'^MAIL$', # retain full original message for virus checking (can be slow)
  qr'^MAIL-UNDECIPHERABLE$', # recheck full mail if it contains undecipherables
  qr'^(ASCII(?! cpio)|text|uuencoded|xxencoded|binhex)'i,
# qr'^Zip archive data', # don't trust Archive::Zip
));

# for $banned_namepath_re (a new-style of banned table) see amavisd.conf-sample

$banned_filename_re = new_RE(
	### BLOCKED ANYWHERE
	# qr'^UNDECIPHERABLE$', # is or contains any undecipherable components
    qr'^\.(exe-ms|dll)$', # banned file(1) types, rudimentary
    qr'^\.(exe|lha|cab|dll)$', # banned file(1) types

	### BLOCK THE FOLLOWING, EXCEPT WITHIN UNIX ARCHIVES:
	# [ qr'^\.(gz|bz2)$' => 0 ], # allow any in gzip or bzip2
    [ qr'^\.(rpm|cpio|tar)$' => 0 ], # allow any in Unix-type archives

	qr'.\.(pif|scr)$'i, # banned extensions - rudimentary
	# qr'^\.zip$', # block zip type

	### BLOCK THE FOLLOWING, EXCEPT WITHIN ARCHIVES:
	# [ qr'^\.(zip|rar|arc|arj|zoo)$'=> 0 ], # allow any within these archives

	qr'^application/x-msdownload$'i, # block these MIME types
	qr'^application/x-msdos-program$'i,
	qr'^application/hta$'i,
	qr'^video/mpeg$'i,
	qr'^video/avi$'i,
	qr'^video/quicktime$'i,
	qr'^video/vnd.vivo$'i,
	qr'^video/x-msvideo$'i,
	qr'^video/msvideo$'i,
	qr'^video/x-ms-asf$'i,
	qr'^video/x-sgi-movie$'i,
	qr'^video/x-tango$'i,
	qr'^video/x-vif$'i,
	qr'^video/x-mpeg$'i,
	qr'^video/x-mpeq2a$'i,
	qr'^video/x-dv$'i,
	qr'^video/x-motion-jpeg$'i,
	qr'^audio/x-wav$'i,
	qr'^audio/wav$'i,
	qr'^audio/mpeg$'i,
	qr'^audio/x-mpeg$'i,
	qr'^audio/vnd.rn-realaudio$'i,
	qr'^audio/x-pn-realaudio-plugin$'i,
	qr'^audio/x-realaudio$'i,
	qr'^audio/x-pn-realaudio$'i,
	qr'^audio/mpeg3$'i,
	qr'^audio/x-mpeg-3$'i,
	qr'^audio/x-aiff$'i,
	qr'^audio/x-au$'i,
	qr'^audio/midi$'i,
	qr'^audio/mid$'i,
	qr'^audio/x-mid$'i,
	qr'^audio/x-midi$'i,
	qr'^music/crescendo$'i,
	qr'^application/ringing-tones$'i,
	qr'^application/vnd.nokia.ringing-tone$'i,
	qr'^application/smil$'i,
	qr'^x-music/x-midi$'i,
	#qr'^application/octet-stream$'i,
	qr'^application/x-javascript$'i,
	qr'^application/x-shockwave-flash$'i,
	qr'^text/javascript$'i,
	qr'^application/x-vbs$'i,
	qr'^text/vbs$'i,
	qr'^text/vbscript$'i,

	# qr'^message/partial$'i, # rfc2046 MIME type
	# qr'^message/external-body$'i, # rfc2046 MIME type

	# qr'^(application/x-msmetafile|image/x-wmf)$'i, # Windows Metafile MIME type
	# qr'^\.wmf$', # Windows Metafile file(1) type

	# block certain double extensions in filenames
	qr'\.[^./]*[A-Za-z][^./]*\.\s*(exe|vbs|pif|scr|bat|cmd|com|cpl|dll)[.\s]*$'i,

	# qr'\{[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12}\}?'i, # Class ID CLSID, strict
	# qr'\{[0-9a-z]{4,}(-[0-9a-z]{4,}){0,7}\}?'i, # Class ID extension CLSID, loose

	#  qr'.\.(exe|vbs|pif|scr|cpl)$'i, # banned extension - basic
	qr'.\.(exe|vbs|pif|scr|cpl|bat|cmd|com)$'i, # banned extension - basic+cmd
	qr'.\.(ade|adp|app|bas|bat|chm|cmd|com|cpl|emf|exe|fxp|grp|hlp|hta|
        inf|ins|isp|js|jse|lnk|mda|mdb|mde|mdw|mdt|mdz|msc|msi|msp|mst|
        ops|pcd|pif|prg|reg|scr|sct|shb|shs|vb|vbe|vbs|
        wmf|wsc|wsf|wsh)$'ix, # banned ext - long
	qr'.\.(ani|cur|ico)$'i, # banned cursors and icons filename
	qr'^\.ani$', # banned animated cursor file(1) type
	qr'.\.(asf|asx|mpg|mpe|mpeg|avi|mp3|wav|wma|wmf|wmv|mov|vob)$'i, # Banned Videos

	qr'.\.(mim|b64|bhx|hqx|xxe|uu|uue)$'i, # banned extension - WinZip vulnerab.
);
# See http://support.microsoft.com/default.aspx?scid=kb;EN-US;q262631
# and http://www.cknow.com/vtutor/vtextensions.htm

%banned_rules = (
  'NO-MS-EXEC'=> new_RE( qr'^\.(exe-ms)$' ),
  'PASSALL'   => new_RE( [qr'^' => 0] ),
  'ALLOW_EXE' =>  # pass executables except if name ends in .vbs .pif .scr .bat
    new_RE( qr'.\.(vbs|pif|scr|bat)$'i, [qr'^\.exe$' => 0] ),
  'ALLOW_VBS' =>  # allow names ending in .vbs
    new_RE( [qr'.\.vbs$' => 0] ),
  'NO-VIDEO' => new_RE( qr'^\.movie$',
    qr'.\.(asf|asx|mpg|mpe|mpeg|avi|mp3|wav|wma|wmf|wmv|mov|vob)$'i, ),
  'NO-MOVIES' => new_RE( qr'^\.movie$', qr'.\.(mpg|avi|mov)$'i, ),
  'MYNETS-DEFAULT' => new_RE(
    [ qr'^\.(rpm|cpio|tar)$' => 0 ], # allow any in Unix-type archives
    qr'.\.(vbs|pif|scr)$'i, # banned extension - rudimentary
    qr'.\.(exe|vbs|pif|scr|bat|cmd|com|cpl)$'i, # banned extension - basic
    qr'^\.(exe-ms)$', # banned file(1) types
  ),
  'DEFAULT' => $banned_filename_re,
);

# ENVELOPE SENDER SOFT-WHITELISTING / SOFT-BLACKLISTING



@score_sender_maps = (
	{   # a by-recipient hash lookup table,
		# results from all matching recipient tables are summed

		# ## per-recipient personal tables  (NOTE: positive: black, negative: white)
		# 'user1@example.com' => [{'bla-mobile.press@example.com' => 10.0}],
		# 'user3@example.com' => [{'.ebay.com' => -3.0}],
		# 'user4@example.com' => [{'cleargreen@cleargreen.com' => -7.0,
		#                          '.cleargreen.com' => -5.0}],

		## site-wide opinions about senders (the '.' matches any recipient)
		'.' => [  # the _first_ matching sender determines the score boost

            new_RE(  # regexp-type lookup table, just happens to be all soft-blacklist
                [qr'^(bulkmail|offers|cheapbenefits|earnmoney|foryou)@'i => 5.0],
                [qr'^(greatcasino|investments|lose_weight_today|market\.alert)@'i => 5.0],
                [qr'^(money2you|MyGreenCard|new\.tld\.registry|opt-out|opt-in)@'i => 5.0],
                [qr'^(optin|saveonlsmoking2002k|specialoffer|specialoffers)@'i => 5.0],
                [qr'^(stockalert|stopsnoring|wantsome|workathome|yesitsfree)@'i => 5.0],
                [qr'^(your_friend|greatoffers)@'i => 5.0],
                [qr'^(inkjetplanet|marketopt|MakeMoney)\d*@'i => 5.0],
            ),

			read_hash("/etc/imscp/amavisd/working/sender_scores_sitewide"),

			{ # a hash-type lookup table (associative array)
				'nobody@cert.org' => -3.0,
			},
		], # end of site-wide tables
});

@decoders = (
	['mail', \&do_mime_decode],
	['asc', \&do_ascii],
	['uue', \&do_ascii],
	['hqx', \&do_ascii],
	['ync', \&do_ascii],
	#['F', \&do_uncompress, ['unfreeze','freeze -d','melt','fcat'] ],
	['Z', \&do_uncompress, ['uncompress','gzip -d','zcat'] ],
	['gz', \&do_uncompress, 'gzip -d'],
	['gz', \&do_gunzip],
	['bz2', \&do_uncompress, 'bzip2 -d'],
	#['lzo', \&do_uncompress, 'lzop -d'],
	['rpm', \&do_uncompress, ['rpm2cpio.pl','rpm2cpio'] ],
	['cpio', \&do_pax_cpio, ['pax','gcpio','cpio'] ],
	['tar', \&do_pax_cpio, ['pax','gcpio','cpio'] ],
	['deb', \&do_ar, 'ar'],
	# ['a', \&do_ar, 'ar'],  # unpacking .a seems an overkill
	['zip', \&do_unzip],
	['7z', \&do_7zip, ['7zr','7za','7z'] ],
	['rar', \&do_unrar, ['rar','unrar'] ],
	['arj', \&do_unarj, ['arj','unarj'] ],
	#['arc', \&do_arc, ['nomarch','arc'] ],
	['zoo', \&do_zoo, ['zoo','unzoo'] ],
	['lha', \&do_lha, 'lha'],
	# ['doc', \&do_ole, 'ripole'],
	['cab', \&do_cabextract, 'cabextract'],
	['tnef', \&do_tnef_ext, 'tnef'],
	['tnef', \&do_tnef],
	# ['sit', \&do_unstuff, 'unstuff'], # broken/unsafe decoder
	['exe', \&do_executable, ['rar','unrar'], 'lha', ['arj','unarj'] ],
);

@virus_name_to_spam_score_maps = (
	new_RE(  # the order matters!
		[qr'^Phishing\.' => 0.1 ],
		[qr'^Structured\.(SSN|CreditCardNumber)\b' => 0.1 ],
		[qr'^(Email|HTML)\.Phishing\.(?!.*Sanesecurity)' => 0.1 ],
		[qr'^Sanesecurity\.(Malware|Rogue|Trojan)\.' => undef ], # keep as infected
		[qr'^Sanesecurity\.' => 0.1 ],
		[qr'^Sanesecurity_PhishBar_' => 0],
		[qr'^Sanesecurity.TestSig_' => 0],
		[qr'^Email\.Spam\.Bounce(\.[^., ]*)*\.Sanesecurity\.' => 0],
		[qr'^Email\.Spammail\b' => 0.1],
		[qr'^MSRBL-(Images|SPAM)\b' => 0.1],
		[qr'^VX\.Honeypot-SecuriteInfo\.com\.Joke' => 0.1],
		[qr'^VX\.not-virus_(Hoax|Joke)\..*-SecuriteInfo\.com(\.|\z)' => 0.1],
		[qr'^Email\.Spam.*-SecuriteInfo\.com(\.|\z)'=> 0.1],
		[qr'^Safebrowsing\.' => 0.1],
		[qr'-SecuriteInfo\.com(\.|\z)' => undef], # keep as infected
		[qr'^MBL_NA\.UNOFFICIAL' => 0.1], # false positives
		[qr'^MBL_' => undef],  # keep as infected
		[qr'^winnow\.(phish|spam)\.' => 0.1],
		[qr'^winnow\.malware\.' => undef],  # keep as infected
		[qr'^INetMsg\.SpamDomain-2m\.' => 0.1],
		[qr'^INetMsg\.SpamDomain-2w\.' => 0.1],
    )
);

@av_scanners = (
# ### http://www.clamav.net/
 ['ClamAV-clamd',
   \&ask_daemon, ["CONTSCAN {}\n", "/var/lib/clamav/clamd-socket"],
   qr/\bOK$/, qr/\bFOUND$/,
   qr/^.*?: (?!Infected Archive)(.*) FOUND$/m ],
);

@av_scanners_backup = (
  ### http://www.clamav.net/   - backs up clamd or Mail::ClamAV
  ['ClamAV-clamscan', 'clamscan',
    "--stdout --no-summary -r --tempdir=$TEMPBASE {}",
    [0], qr/:.*\sFOUND$/, qr/^.*?: (?!Infected Archive)(.*) FOUND$/m ],
);

1;  # Ensure a defined return
