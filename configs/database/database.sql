create database {DATABASE_NAME};

use {DATABASE_NAME};

--
-- VHCS ω (OMEGA) - Virtual Hosting Control System | Omega Version
-- Copyright (c) 2001-2006 by moleSoftware GmbH
-- Copyright (c) 2006-2007 by ispCP | http://isp-control.net
--
-- --------------------------------------------------------

-- Tabellenstruktur für Tabelle `admin`
--

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
  `uniqkey_time` timestamp NULL default NULL,
  UNIQUE KEY `admin_id` (`admin_id`),
  UNIQUE KEY `admin_name` (`admin_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `auto_num`
--

CREATE TABLE `auto_num` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `msg` varchar(255) default NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `config`
--

CREATE TABLE `config` (
  `name` varchar(255) NOT NULL default '',
  `value` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci;

--
-- Daten für Tabelle `auto_num`
--

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

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `custom_menus`
--
CREATE TABLE `custom_menus` (
  `menu_id` int(10) unsigned NOT NULL auto_increment,
  `menu_level` varchar(10) default NULL,
  `menu_name` varchar(255) default NULL,
  `menu_link` varchar(200) default NULL,
  `menu_target` varchar(200) NOT NULL default '',
  PRIMARY KEY  (`menu_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `domain`
--

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
  `domain_traffic_limit` bigint(20) default NULL,
  `domain_sqld_limit` int(11) default NULL,
  `domain_sqlu_limit` int(11) default NULL,
  `domain_status` varchar(255) default NULL,
  `domain_alias_limit` int(11) default NULL,
  `domain_subd_limit` int(11) default NULL,
  `domain_ip_id` int(10) unsigned default NULL,
  `domain_disk_limit` bigint(20) unsigned default NULL,
  `domain_disk_usage` bigint(20) unsigned default NULL,
  `domain_php` varchar(15) default NULL,
  `domain_cgi` varchar(15) default NULL,
  UNIQUE KEY `domain_id` (`domain_id`),
  UNIQUE KEY `domain_name` (`domain_name`),
  KEY `i_domain_admin_id` (`domain_admin_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `domain_aliasses`
--

CREATE TABLE `domain_aliasses` (
  `alias_id` int(10) unsigned NOT NULL auto_increment,
  `domain_id` int(10) unsigned default NULL,
  `alias_name` varchar(200) default NULL,
  `alias_status` varchar(255) default NULL,
  `alias_mount` varchar(200) default NULL,
  `alias_ip_id` int(10) unsigned default NULL,
  `url_forward` varchar(200) default NULL,
  PRIMARY KEY  (`alias_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `domain_props`
--

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
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `domain_traffic`
--

CREATE TABLE `domain_traffic` (
  `dtraff_id` int(10) unsigned NOT NULL auto_increment,
  `domain_id` int(10) unsigned default NULL,
  `dtraff_time` int(10) unsigned default NULL,
  `dtraff_web` int(10) unsigned default NULL,
  `dtraff_ftp` int(10) unsigned default NULL,
  `dtraff_mail` int(10) unsigned default NULL,
  `dtraff_pop` int(10) unsigned default NULL,
  `correction` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`dtraff_id`),
  KEY `i_correction` (`correction`),
  KEY `i_domain_id` (`domain_id`),
  KEY `i_dtraff_time` (`dtraff_time`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `email_tpls`
--

CREATE TABLE `email_tpls` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `owner_id` int(10) unsigned NOT NULL default '0',
  `name` varchar(200) default NULL,
  `subject` varchar(200) default NULL,
  `message` text,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `error_pages`
--

CREATE TABLE `error_pages` (
  `ep_id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL default '0',
  `error_401` text NOT NULL,
  `error_403` text NOT NULL,
  `error_404` text NOT NULL,
  `error_500` text NOT NULL,
  PRIMARY KEY  (`ep_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `ftp_group`
--

CREATE TABLE `ftp_group` (
  `groupname` varchar(255) default NULL,
  `gid` int(10) unsigned NOT NULL default '0',
  `members` text,
  UNIQUE KEY `groupname` (`groupname`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `ftp_users`
--

CREATE TABLE `ftp_users` (
  `userid` varchar(255) default NULL,
  `passwd` varchar(255) default NULL,
  `uid` int(10) unsigned NOT NULL default '0',
  `gid` int(10) unsigned NOT NULL default '0',
  `shell` varchar(255) default NULL,
  `homedir` varchar(255) default NULL,
  UNIQUE KEY `userid` (`userid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `hosting_plans`
--

CREATE TABLE `hosting_plans` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `reseller_id` int(10) unsigned NOT NULL default '0',
  `name` varchar(255) default NULL,
  `props` text,
  `description` varchar(255) default NULL,
  `price` decimal(4,2) NOT NULL default '0.00',
  `setup_fee` decimal(4,2) NOT NULL default '0.00',
  `value` varchar(255) default NULL,
  `payment` varchar(255) default NULL,
  `status` int(10) unsigned NOT NULL default '0',
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `htaccess`
--
-- Erzeugt am: 14. Juni 2006 um 23:42
-- Aktualisiert am: 09. Januar 2007 um 17:37
-- Letzter Check am: 09. Januar 2007 um 17:37
--

CREATE TABLE `htaccess` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `dmn_id` int(10) unsigned NOT NULL default '0',
  `user_id` tinyint(4) default NULL,
  `group_id` tinyint(4) default NULL,
  `auth_type` varchar(255) character set latin1 collate latin1_german1_ci default NULL,
  `auth_name` varchar(255) character set latin1 collate latin1_german1_ci default NULL,
  `path` varchar(255) character set latin1 collate latin1_german1_ci default NULL,
  `status` varchar(255) character set latin1 collate latin1_german1_ci default NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `htaccess_groups`
--
-- Erzeugt am: 05. März 2006 um 21:59
-- Aktualisiert am: 01. Oktober 2006 um 13:59
-- Letzter Check am: 15. September 2006 um 21:41
--

CREATE TABLE `htaccess_groups` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `dmn_id` int(10) unsigned NOT NULL default '0',
  `ugroup` varchar(255) default NULL,
  `members` text,
  `status` varchar(255) default NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `htaccess_users`
--
-- Erzeugt am: 05. März 2006 um 21:59
-- Aktualisiert am: 09. Januar 2007 um 17:37
-- Letzter Check am: 09. Januar 2007 um 17:37
--

CREATE TABLE `htaccess_users` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `dmn_id` int(10) unsigned NOT NULL default '0',
  `uname` varchar(255) default NULL,
  `upass` varchar(255) default NULL,
  `status` varchar(255) default NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------
-- Tabellenstruktur für Tabelle `log`
--

CREATE TABLE `log` (
  `log_id` int(10) unsigned NOT NULL auto_increment,
  `log_time` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `log_message` varchar(250) default NULL,
  PRIMARY KEY  (`log_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `login`
--

CREATE TABLE `login` (
  `session_id` varchar(200) default NULL,
  `ipaddr` varchar(15) default NULL,
  `lastaccess` int(10) unsigned default NULL,
  `login_count` tinyint(1) default NULL,
  `user_name` varchar(255) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mail_users`
--

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
  `quota` int(10) default '10485760',
  `mail_addr` varchar(200) default NULL,
  PRIMARY KEY  (`mail_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `orders`
--

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
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `orders_settings`
--

CREATE TABLE `orders_settings` (
  `id` int(10) unsigned NOT NULL default '0',
  `user_id` int(10) unsigned NOT NULL default '0',
  `header` text,
  `footer` text,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `quotalimits`
--

CREATE TABLE `quotalimits` (
  `name` varchar(30) default NULL,
  `quota_type` enum('user','group','class','all') NOT NULL default 'user',
  `per_session` enum('false','true') NOT NULL default 'false',
  `limit_type` enum('soft','hard') NOT NULL default 'soft',
  `bytes_in_avail` float NOT NULL default '0',
  `bytes_out_avail` float NOT NULL default '0',
  `bytes_xfer_avail` float NOT NULL default '0',
  `files_in_avail` int(10) unsigned NOT NULL default '0',
  `files_out_avail` int(10) unsigned NOT NULL default '0',
  `files_xfer_avail` int(10) unsigned NOT NULL default '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `quotatallies`
--

CREATE TABLE `quotatallies` (
  `name` varchar(30) NOT NULL default '',
  `quota_type` enum('user','group','class','all') NOT NULL default 'user',
  `bytes_in_used` float NOT NULL default '0',
  `bytes_out_used` float NOT NULL default '0',
  `bytes_xfer_used` float NOT NULL default '0',
  `files_in_used` int(10) unsigned NOT NULL default '0',
  `files_out_used` int(10) unsigned NOT NULL default '0',
  `files_xfer_used` int(10) unsigned NOT NULL default '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------


-- Tabellenstruktur für Tabelle `reseller_props`
--

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
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `server_ips`
--

CREATE TABLE `server_ips` (
  `ip_id` int(10) unsigned NOT NULL auto_increment,
  `ip_number` varchar(15) default NULL,
  `ip_domain` varchar(200) default NULL,
  `ip_alias` varchar(200) default NULL,
  UNIQUE KEY `ip_id` (`ip_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `server_traffic`
--

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
  `correction` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`straff_id`),
  KEY `i_correction` (`correction`),
  KEY `i_traff_time` (`traff_time`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `sql_database`
--

CREATE TABLE `sql_database` (
  `sqld_id` int(10) unsigned NOT NULL auto_increment,
  `domain_id` int(10) unsigned default '0',
  `sqld_name` varchar(64) binary default 'n/a',
  UNIQUE KEY `sqld_id` (`sqld_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `sql_user`
--

CREATE TABLE `sql_user` (
  `sqlu_id` int(10) unsigned NOT NULL auto_increment,
  `sqld_id` int(10) unsigned default '0',
  `sqlu_name` varchar(16) binary default 'n/a',
  `sqlu_pass` varchar(16) binary default 'n/a',
  UNIQUE KEY `sqlu_id` (`sqlu_id`)
) TYPE=MyISAM AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `straff_settings`
--

CREATE TABLE `straff_settings` (
  `straff_max` int(10) unsigned default NULL,
  `straff_warn` int(10) unsigned default NULL,
  `straff_email` int(10) unsigned default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Daten für Tabelle `straff_settings`
--

INSERT INTO `straff_settings` VALUES (0, 0, 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `subdomain`
--

CREATE TABLE `subdomain` (
  `subdomain_id` int(10) unsigned NOT NULL auto_increment,
  `domain_id` int(10) unsigned default NULL,
  `subdomain_name` varchar(200) default NULL,
  `subdomain_mount` varchar(200) default NULL,
  `subdomain_status` varchar(255) default NULL,
  `subdomain_phpv` varchar(15) default NULL,
  PRIMARY KEY  (`subdomain_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `suexec_props`
--

CREATE TABLE `suexec_props` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `uid` int(10) unsigned default NULL,
  `gid` int(10) unsigned default NULL,
  `usr` varchar(255) default NULL,
  `grp` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `syslog`
--

CREATE TABLE `syslog` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `date` int(10) unsigned NOT NULL default '0',
  `user_id` int(10) unsigned NOT NULL default '0',
  `user_name` varchar(255) default NULL,
  `action` varchar(255) default NULL,
  `comment` text,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tickets`
--

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
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user_gui_props`
--

CREATE TABLE `user_gui_props` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL default '0',
  `lang` varchar(255) default NULL,
  `layout` varchar(255) default NULL,
  `logo` varchar(255) NOT NULL default '0',
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
