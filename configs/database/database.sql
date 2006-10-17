create database {DATABASE_NAME};

use {DATABASE_NAME};

# VHCS(tm) - Virtual Hosting Control System
# (c) 2001-2006 moleSoftware
# http://vhcs.net | http://www.molesoftware.com
# All rights reserved
#
# Datenbank: `vhcs_pro_dev`
# --------------------------------------------------------
#
# Tabellenstruktur für Tabelle `admin`
#

CREATE TABLE `admin` (
  `admin_id` int(10) unsigned NOT NULL auto_increment,
  `admin_name` varchar(200) default NULL,
  `admin_pass` varchar(200) default NULL,
  `admin_type` varchar(10) default NULL,
  `domain_created` int(10) unsigned NOT NULL default '0',
  `customer_id` varchar(200) default NULL,
  `created_by` int(10) unsigned default NULL,
  `fname` varchar(200) default NULL,
  `lname` varchar(200) default NULL,
  `firm` varchar(200) default NULL,
  `zip` varchar(10) default NULL,
  `city` varchar(200) default NULL,
  `country` varchar(200) default NULL,
  `email` varchar(200) default NULL,
  `phone` varchar(200) default NULL,
  `fax` varchar(200) default NULL,
  `street1` varchar(200) default NULL,
  `street2` varchar(200) default NULL,
  `uniqkey` varchar(255) default NULL,
  `uniqkey_time` TIMESTAMP NULL,
  UNIQUE KEY `admin_id` (`admin_id`),
  UNIQUE KEY `admin_name` (`admin_name`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

#
# Daten für Tabelle `admin`
#


# --------------------------------------------------------

CREATE TABLE `orders` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL default '0',
  `plan_id` int(10) unsigned NOT NULL default '0',
  `date` int(10) unsigned NOT NULL default '0',
  `domain_name` varchar(200) default NULL,
  `customer_id` varchar(200) default NULL,
  `fname` varchar(200) default NULL,
  `lname` varchar(200) default NULL,
  `firm` varchar(200) default NULL,
  `zip` varchar(10) default NULL,
  `city` varchar(200) default NULL,
  `country` varchar(200) default NULL,
  `email` varchar(200) default NULL,
  `phone` varchar(200) default NULL,
  `fax` varchar(200) default NULL,
  `street1` varchar(200) default NULL,
  `street2` varchar(200) default NULL,
  `status` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

#
# Daten für Tabelle `orders`
#


# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `orders_settings`
#

CREATE TABLE `orders_settings` (
  `id` int(10) unsigned NOT NULL default '0',
  `user_id` int(10) unsigned NOT NULL default '0',
  `header` text,
  `footer` text,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

#
# Daten für Tabelle `orders_settings`
#


# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `custom_menus`
#

CREATE TABLE `custom_menus` (
  `menu_id` int(10) unsigned NOT NULL auto_increment,
  `menu_level` varchar(10) default NULL,
  `menu_name` varchar(255) default NULL,
  `menu_link` varchar(200) default NULL,
  `menu_target` varchar(200) NOT NULL default '',
  PRIMARY KEY  (`menu_id`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

#
# Daten für Tabelle `custom_menus`
#


# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `domain`
#

CREATE TABLE `domain` (
  `domain_id` int(10) unsigned NOT NULL auto_increment,
  `domain_name` varchar(200) default NULL,
  `domain_gid` int(10) unsigned NOT NULL default '0',
  `domain_uid` int(10) unsigned NOT NULL default '0',
  `domain_admin_id` int(10) unsigned NOT NULL default '0',
  `domain_created_id` int(10) unsigned NOT NULL default '0',
  `domain_created` int(10) unsigned NOT NULL default '0',
  `domain_last_modified` int(10) unsigned NOT NULL default '0',
  `domain_mailacc_limit` int(11) default NULL,
  `domain_ftpacc_limit` int(11) default NULL,
  `domain_traffic_limit` int(11) default NULL,
  `domain_sqld_limit` int(11) default NULL,
  `domain_sqlu_limit` int(11) default NULL,
  `domain_status` varchar(255) default NULL,
  `domain_alias_limit` int(11) default NULL,
  `domain_subd_limit` int(11) default NULL,
  `domain_ip_id` int(10) unsigned default NULL,
  `domain_disk_limit` int(30) unsigned default NULL,
  `domain_disk_usage` int(30) unsigned default NULL,
  `domain_php` varchar(15) default NULL,
  `domain_cgi` varchar(15) default NULL,
  UNIQUE KEY `domain_id` (`domain_id`),
  UNIQUE KEY `domain_name` (`domain_name`),
  KEY `i_domain_domain_admin_id` (`domain_admin_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

#
# Daten für Tabelle `domain`
#


# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `domain_aliasses`
#

CREATE TABLE `domain_aliasses` (
  `alias_id` int(10) unsigned NOT NULL auto_increment,
  `domain_id` int(10) unsigned default NULL,
  `alias_name` varchar(200) default NULL,
  `alias_status` varchar(255) default NULL,
  `alias_mount` varchar(200) default NULL,
  `alias_ip_id` int(10) unsigned default NULL,
  `url_forward` varchar(200) default NULL,
  PRIMARY KEY  (`alias_id`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

#
# Daten für Tabelle `domain_aliasses`
#


# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `domain_props`
#

CREATE TABLE `domain_props` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `dmn_id` int(10) unsigned default NULL,
  `dmn_type` varchar(255) default NULL,
  `suexec_support` varchar(255) default NULL,
  `suexec_id` int(10) unsigned default NULL,
  `ssl_support` varchar(255) default NULL,
  `ssl_id` int(10) unsigned default NULL,
  `pri_master_dns` varchar(255) default NULL,
  `pri_master_dns_ip` varchar(255) default NULL,
  `sec_master_dns` varchar(255) default NULL,
  `sec_master_dns_ip` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `id` (`id`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

#
# Daten für Tabelle `domain_props`
#


# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `domain_traffic`
#

CREATE TABLE `domain_traffic` (
  `dtraff_id` int(10) unsigned NOT NULL auto_increment,
  `domain_id` int(10) unsigned default NULL,
  `dtraff_time` int(10) unsigned default NULL,
  `dtraff_web` int(10) unsigned default NULL,
  `dtraff_ftp` int(10) unsigned default NULL,
  `dtraff_mail` int(10) unsigned default NULL,
  `dtraff_pop` int(10) unsigned default NULL,
  PRIMARY KEY  (`dtraff_id`),
  KEY `i_domain_traffic_domain_id` (`domain_id`),
  KEY `dtraff_time` (`dtraff_time`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

#
# Daten für Tabelle `domain_traffic`
#


# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `email_tpls`
#

CREATE TABLE `email_tpls` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `owner_id` int(10) unsigned NOT NULL default '0',
  `name` varchar(200) default NULL,
  `subject` varchar(200) default NULL,
  `message` text,
  UNIQUE KEY `id` (`id`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

#
# Daten für Tabelle `email_tpls`
#


# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `error_pages`
#

CREATE TABLE `error_pages` (
  `ep_id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL default '0',
  `error_401` text NOT NULL,
  `error_403` text NOT NULL,
  `error_404` text NOT NULL,
  `error_500` text NOT NULL,
  PRIMARY KEY  (`ep_id`)
) TYPE=MyISAM AUTO_INCREMENT=3 ;

#
# Daten für Tabelle `error_pages`
#


# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `ftp_group`
#

CREATE TABLE `ftp_group` (
  `groupname` varchar(255) default NULL,
  `gid` int(10) unsigned NOT NULL default '0',
  `members` text
) TYPE=MyISAM;

#
# Daten für Tabelle `ftp_group`
#


# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `hosting_plans`
#

CREATE TABLE `hosting_plans` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `reseller_id` int(10) unsigned NOT NULL default '0',
  `name` varchar(255) default NULL,
  `description` varchar(255) default NULL,
  `props` text,
  `price` decimal(10,2) NOT NULL default '0.00',
  `setup_fee` decimal(10,2) NOT NULL default '0.00',
  `value` varchar(255) default NULL,
  `payment` varchar(255) default NULL,
  `status` int(10) unsigned NOT NULL default '0',
  UNIQUE KEY `id` (`id`)
) TYPE=MyISAM;

#
# Daten für Tabelle `hosting_plans`
#


# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `log`
#

CREATE TABLE `log` (
  `log_id` int(10) unsigned NOT NULL auto_increment,
  `log_time` timestamp(14) NOT NULL,
  `log_message` varchar(250) default NULL,
  PRIMARY KEY  (`log_id`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

#
# Daten für Tabelle `log`
#


# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `login`
#

CREATE TABLE `login` (
  `session_id` varchar(200) default NULL,
  `ipaddr` varchar(15) default NULL,
  `lastaccess` int(10) unsigned default NULL,
  `user_name` varchar(255) default NULL,
  `login_count` tinyint(1) default NULL
) TYPE=MyISAM;

#
# Daten für Tabelle `login`
#


# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `mail_users`
#

CREATE TABLE `mail_users` (
  `mail_id` int(10) unsigned NOT NULL auto_increment,
  `mail_acc` varchar(200) default NULL,
  `mail_pass` varchar(150) default NULL,
  `mail_forward` text,
  `domain_id` int(10) unsigned default NULL,
  `mail_type` varchar(20) default NULL,
  `sub_id` int(10) unsigned default NULL,
  `status` varchar(255) default NULL,
  `mail_auto_respond` text,
  PRIMARY KEY  (`mail_id`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

#
# Daten für Tabelle `mail_users`
#


# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `reseller_props`
#

CREATE TABLE `reseller_props` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `reseller_id` int(10) unsigned NOT NULL default '0',
  `current_dmn_cnt` int(11) default NULL,
  `max_dmn_cnt` int(11) default NULL,
  `current_sub_cnt` int(11) default NULL,
  `max_sub_cnt` int(11) default NULL,
  `current_als_cnt` int(11) default NULL,
  `max_als_cnt` int(11) default NULL,
  `current_mail_cnt` int(11) default NULL,
  `max_mail_cnt` int(11) default NULL,
  `current_ftp_cnt` int(11) default NULL,
  `max_ftp_cnt` int(11) default NULL,
  `current_sql_db_cnt` int(11) default NULL,
  `max_sql_db_cnt` int(11) default NULL,
  `current_sql_user_cnt` int(11) default NULL,
  `max_sql_user_cnt` int(11) default NULL,
  `current_disk_amnt` int(11) default NULL,
  `max_disk_amnt` int(11) default NULL,
  `current_traff_amnt` int(11) default NULL,
  `max_traff_amnt` int(11) default NULL,
  `customer_id` varchar(200) default NULL,
  `reseller_ips` text,
  UNIQUE KEY `id` (`id`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

#
# Daten für Tabelle `reseller_props`
#


# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `server_ips`
#

CREATE TABLE `server_ips` (
  `ip_id` int(10) unsigned NOT NULL auto_increment,
  `ip_number` varchar(15) default NULL,
  `ip_domain` varchar(200) default NULL,
  `ip_alias` varchar(200) default NULL,
  UNIQUE KEY `ip_id` (`ip_id`)
) TYPE=MyISAM AUTO_INCREMENT=8 ;

#
# Daten für Tabelle `server_ips`
#

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `server_traffic`
#

CREATE TABLE `server_traffic` (
  `straff_id` int(10) unsigned NOT NULL auto_increment,
  `traff_time` int(10) unsigned default NULL,
  `bytes_in` int(10) unsigned default NULL,
  `bytes_out` int(10) unsigned default NULL,
  `bytes_mail_in` int(10) unsigned default NULL,
  `bytes_mail_out` int(10) unsigned default NULL,
  `bytes_pop_in` int(10) unsigned default NULL,
  `bytes_pop_out` int(10) unsigned default NULL,
  `bytes_web_in` int(10) unsigned default NULL,
  `bytes_web_out` int(10) unsigned default NULL,
  PRIMARY KEY  (`straff_id`),
  KEY `traff_time` (`traff_time`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

#
# Daten für Tabelle `server_traffic`
#


# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `sql_database`
#

CREATE TABLE `sql_database` (
  `sqld_id` int(10) unsigned NOT NULL auto_increment,
  `domain_id` int(10) unsigned default '0',
  `sqld_name` varchar(64) binary default 'n/a',
  UNIQUE KEY `sqld_id` (`sqld_id`)
) TYPE=MyISAM AUTO_INCREMENT=3 ;

#
# Daten für Tabelle `sql_database`
#


# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `sql_user`
#

CREATE TABLE `sql_user` (
  `sqlu_id` int(10) unsigned NOT NULL auto_increment,
  `sqld_id` int(10) unsigned default '0',
  `sqlu_name` varchar(16) binary default 'n/a',
  `sqlu_pass` varchar(16) binary default 'n/a',
  UNIQUE KEY `sqlu_id` (`sqlu_id`)
) TYPE=MyISAM AUTO_INCREMENT=2 ;

#
# Daten für Tabelle `sql_user`
#


# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `straff_settings`
#

CREATE TABLE `straff_settings` (
  `straff_max` int(10) unsigned default NULL,
  `straff_warn` int(10) unsigned default NULL,
  `straff_email` int(10) unsigned default NULL
) TYPE=MyISAM;

#
# Daten für Tabelle `straff_settings`
#

INSERT INTO `straff_settings` VALUES (0, 0, 0);

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `subdomain`
#

CREATE TABLE `subdomain` (
  `subdomain_id` int(10) unsigned NOT NULL auto_increment,
  `domain_id` int(10) unsigned default NULL,
  `subdomain_name` varchar(200) default NULL,
  `subdomain_mount` varchar(200) default NULL,
  `subdomain_status` varchar(255) default NULL,
  PRIMARY KEY  (`subdomain_id`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

#
# Daten für Tabelle `subdomain`
#


# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `suexec_props`
#

CREATE TABLE `suexec_props` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `uid` int(10) unsigned default NULL,
  `gid` int(10) unsigned default NULL,
  `usr` varchar(255) default NULL,
  `grp` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `id` (`id`)
) TYPE=MyISAM AUTO_INCREMENT=2 ;

#
# Daten für Tabelle `suexec_props`
#

#INSERT INTO `suexec_props` VALUES (1, 3001, 3001, 'vhcs0001', 'vhcs0001');

# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `syslog`
#

CREATE TABLE `syslog` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `date` int(10) unsigned NOT NULL default '0',
  `user_id` int(10) unsigned NOT NULL default '0',
  `user_name` varchar(255) default NULL,
  `action` varchar(255) default NULL,
  `comment` text,
  UNIQUE KEY `id` (`id`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

#
# Daten für Tabelle `syslog`
#


# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `tickets`
#

CREATE TABLE `tickets` (
  `ticket_id` int(10) unsigned NOT NULL auto_increment,
  `ticket_level` int(10) default NULL,
  `ticket_from` int(10) unsigned default NULL,
  `ticket_to` int(10) unsigned default NULL,
  `ticket_status` int(10) unsigned default NULL,
  `ticket_reply` int(10) unsigned default NULL,
  `ticket_urgency` int(10) unsigned default NULL,
  `ticket_date` int(10) unsigned default NULL,
  `ticket_subject` varchar(255) default NULL,
  `ticket_message` text,
  PRIMARY KEY  (`ticket_id`)
) TYPE=MyISAM AUTO_INCREMENT=2 ;

#
# Daten für Tabelle `tickets`
#


# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `user_gui_props`
#

CREATE TABLE `user_gui_props` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL default '0',
  `lang` varchar(255) default NULL,
  `layout` varchar(255) default NULL,
  `logo` varchar(255) NOT NULL default '0',
  UNIQUE KEY `id` (`id`)
) TYPE=MyISAM AUTO_INCREMENT=66 ;

#
# Daten für Tabelle `user_gui_props`
#

#INSERT INTO `user_gui_props` VALUES (3, 3, 'lang_Deutsch', 'yellow', 'admin.jpg');


# --------------------------------------------------------

#
# Tabellenstruktur Tabelle `ftp_users`
#

CREATE TABLE `ftp_users` (
  `userid` varchar(255) default NULL,
  `passwd` varchar(255) default NULL,
  `uid` int(10) unsigned NOT NULL default '0',
  `gid` int(10) unsigned NOT NULL default '0',
  `shell` varchar(255) default NULL,
  `homedir` varchar(255) default NULL,
  UNIQUE KEY `userid` (`userid`)
) TYPE=MyISAM;

#
# Daten für Tabelle `ftp_users`
#


# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `quotalimits`
#

CREATE TABLE `quotalimits` (
  `name` VARCHAR(30),
  `quota_type` ENUM("user", "group", "class", "all") NOT NULL,
  `per_session` ENUM("false", "true") NOT NULL,
  `limit_type` ENUM("soft", "hard") NOT NULL,
  `bytes_in_avail` FLOAT NOT NULL,
  `bytes_out_avail` FLOAT NOT NULL,
  `bytes_xfer_avail` FLOAT NOT NULL,
  `files_in_avail` INT UNSIGNED NOT NULL,
  `files_out_avail` INT UNSIGNED NOT NULL,
  `files_xfer_avail` INT UNSIGNED NOT NULL
);

#
# Daten für Tabelle `quotalimits`
#


# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `quotatallies`
#

CREATE TABLE `quotatallies` (
  `name` VARCHAR(30) NOT NULL,
  `quota_type` ENUM("user", "group", "class", "all") NOT NULL,
  `bytes_in_used` FLOAT NOT NULL,
  `bytes_out_used` FLOAT NOT NULL,
  `bytes_xfer_used` FLOAT NOT NULL,
  `files_in_used` INT UNSIGNED NOT NULL,
  `files_out_used` INT UNSIGNED NOT NULL,
  `files_xfer_used` INT UNSIGNED NOT NULL
);

#
# Daten für Tabelle `quotatallies`
#


# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `auto_num`
#

CREATE TABLE `auto_num` (
  `id` INT unsigned NOT NULL AUTO_INCREMENT,
  `msg` VARCHAR(255),
  UNIQUE KEY `id` (`id`)
);

#
# Daten für Tabelle `auto_num`
#


# --------------------------------------------------------

#
# Tabellenstruktur für Tabelle `config`
#

CREATE TABLE `config` (
  `name` varchar(255) NOT NULL default '',
  `value` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`name`)
) TYPE=MyISAM;

#
# Daten für Tabelle `auto_num`
#

INSERT INTO `config` ( `name` , `value` )
VALUES (
'PORT_FTP', '21;tcp;FTP;1;0'
), (
'PORT_SSH', '22;tcp;SSH;1;0'
),(
'PORT_TELNET', '23;tcp;TELNET;1;0'
),(
'PORT_SMTP', '25;tcp;SMPT;1;0'
),(
'PORT_DNS', '53;tcp;DNS;1;0'
),(
'PORT_HTTP', '80;tcp;HTTP;1;0'
),(
'PORT_HTTPS', '443;tcp;HTTPS;1;0'
),(
'PORT_POP3', '110;tcp;POP3;1;0'
),(
'PORT_POP3-SSL', '995;tcp;POP3-SSL;1;0'
),(
'PORT_IMAP', '143;tcp;IMAP;1;0'
),(
'PORT_IMAP-SSL', '993;tcp;IMAP-SSL;1;0'
);

# --------------------------------------------------------

