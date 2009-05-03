create database {DATABASE_NAME} CHARACTER SET utf8 COLLATE utf8_unicode_ci;

use {DATABASE_NAME};

--
-- ISPCP ω (OMEGA) a Virtual Hosting Control Panel
-- Copyright (c) 2001-2006 by moleSoftware GmbH
-- Copyright (c) 2006-2009 by ispCP | http://isp-control.net
--
-- --------------------------------------------------------

--
-- Table structure for table `admin`
--


CREATE TABLE `admin` (
  `admin_id` int(10) unsigned NOT NULL auto_increment,
  `admin_name` varchar(200) collate utf8_unicode_ci default NULL,
  `admin_pass` varchar(200) collate utf8_unicode_ci default NULL,
  `admin_type` varchar(10) collate utf8_unicode_ci default NULL,
  `domain_created` int(10) unsigned NOT NULL default '0',
  `customer_id` varchar(200) collate utf8_unicode_ci default '0',
  `created_by` int(10) unsigned default '0',
  `fname` varchar(200) collate utf8_unicode_ci default NULL,
  `lname` varchar(200) collate utf8_unicode_ci default NULL,
  `gender` varchar(1) collate utf8_unicode_ci default NULL,
  `firm` varchar(200) collate utf8_unicode_ci default NULL,
  `zip` varchar(10) collate utf8_unicode_ci default NULL,
  `city` varchar(200) collate utf8_unicode_ci default NULL,
  `state` varchar(200) collate utf8_unicode_ci default NULL,
  `country` varchar(200) collate utf8_unicode_ci default NULL,
  `email` varchar(200) collate utf8_unicode_ci default NULL,
  `phone` varchar(200) collate utf8_unicode_ci default NULL,
  `fax` varchar(200) collate utf8_unicode_ci default NULL,
  `street1` varchar(200) collate utf8_unicode_ci default NULL,
  `street2` varchar(200) collate utf8_unicode_ci default NULL,
  `uniqkey` varchar(255) collate utf8_unicode_ci default NULL,
  `uniqkey_time` timestamp NULL default NULL,
  UNIQUE KEY `admin_id` (`admin_id`),
  UNIQUE KEY `admin_name` (`admin_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auto_num`
--

CREATE TABLE `auto_num` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `msg` varchar(255) collate utf8_unicode_ci default NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

CREATE TABLE `config` (
  `name` varchar(255) collate utf8_unicode_ci NOT NULL default '',
  `value` varchar(255) collate utf8_unicode_ci NOT NULL default '',
  PRIMARY KEY  (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `config`
--

INSERT INTO `config` (`name`, `value`) VALUES
('PORT_FTP', '21;tcp;FTP;1;0;'),
('PORT_SSH', '22;tcp;SSH;1;0;'),
('PORT_TELNET', '23;tcp;TELNET;1;0;'),
('PORT_SMTP', '25;tcp;SMTP;1;0;'),
('PORT_SMTP-SSL', '465;tcp;SMTP-SSL;1;0;'),
('PORT_DNS', '53;tcp;DNS;1;0;'),
('PORT_HTTP', '80;tcp;HTTP;1;0;'),
('PORT_HTTPS', '443;tcp;HTTPS;0;0;'),
('PORT_POP3', '110;tcp;POP3;1;0;'),
('PORT_POP3-SSL', '995;tcp;POP3-SSL;1;0;'),
('PORT_IMAP', '143;tcp;IMAP;1;0;'),
('PORT_IMAP-SSL', '993;tcp;IMAP-SSL;0;0;'),
('PORT_POSTGREY', '60000;tcp;POSTGREY;1;1;localhost'),
('PORT_AMAVIS', '10024;tcp;AMaVis;1;1;localhost'),
('PORT_SPAMASSASSIN', '783;tcp;SPAMASSASSIN;1;1;localhost'),
('PORT_POLICYD-WEIGHT', '12525;tcp;POLICYD-WEIGHT;1;1;localhost'),
('SHOW_SERVERLOAD' , '1'),
('PREVENT_EXTERNAL_LOGIN_ADMIN', '1'),
('PREVENT_EXTERNAL_LOGIN_RESELLER', '1'),
('PREVENT_EXTERNAL_LOGIN_CLIENT', '1'),
('DATABASE_REVISION', '19'),
('CRITICAL_UPDATE_REVISION', 3);

-- --------------------------------------------------------

--
-- Table structure for table `custom_menus`
--

CREATE TABLE `custom_menus` (
  `menu_id` int(10) unsigned NOT NULL auto_increment,
  `menu_level` varchar(10) collate utf8_unicode_ci default NULL,
  `menu_name` varchar(255) collate utf8_unicode_ci default NULL,
  `menu_link` varchar(200) collate utf8_unicode_ci default NULL,
  `menu_target` varchar(200) collate utf8_unicode_ci NOT NULL default '',
  PRIMARY KEY  (`menu_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `domain`
--

CREATE TABLE `domain` (
  `domain_id` int(10) unsigned NOT NULL auto_increment,
  `domain_name` varchar(200) collate utf8_unicode_ci default NULL,
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
  `domain_status` varchar(255) collate utf8_unicode_ci default NULL,
  `domain_alias_limit` int(11) default NULL,
  `domain_subd_limit` int(11) default NULL,
  `domain_ip_id` int(10) unsigned default NULL,
  `domain_disk_limit` bigint(20) unsigned default NULL,
  `domain_disk_usage` bigint(20) unsigned default NULL,
  `domain_php` varchar(15) collate utf8_unicode_ci default NULL,
  `domain_cgi` varchar(15) collate utf8_unicode_ci default NULL,
  `allowbackup` varchar(8) collate utf8_unicode_ci NOT NULL default 'full',
  `domain_dns` varchar(15) collate utf8_unicode_ci NOT NULL default 'no',
  UNIQUE KEY `domain_id` (`domain_id`),
  UNIQUE KEY `domain_name` (`domain_name`),
  KEY `i_domain_admin_id` (`domain_admin_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `domain_aliasses`
--

CREATE TABLE `domain_aliasses` (
  `alias_id` int(10) unsigned NOT NULL auto_increment,
  `domain_id` int(10) unsigned default NULL,
  `alias_name` varchar(200) collate utf8_unicode_ci default NULL,
  `alias_status` varchar(255) collate utf8_unicode_ci default NULL,
  `alias_mount` varchar(200) collate utf8_unicode_ci default NULL,
  `alias_ip_id` int(10) unsigned default NULL,
  `url_forward` varchar(200) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`alias_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `domain_dns`
--

CREATE TABLE `domain_dns` (
  `domain_dns_id` int(11) NOT NULL auto_increment,
  `domain_id` int(11) NOT NULL,
  `alias_id` int(11) default NULL,
  `domain_dns` varchar(50) collate utf8_unicode_ci NOT NULL,
  `domain_class` enum('IN','CH','HS') collate utf8_unicode_ci NOT NULL default 'IN',
  `domain_type` enum('A','AAAA','CERT','CNAME','DNAME','GPOS','KEY','KX','MX','NAPTR','NSAP','NS ​','NXT','PTR','PX','SIG','SRV','TXT') collate utf8_unicode_ci NOT NULL default 'A',
  `domain_text` varchar(128) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`domain_dns_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `domain_traffic`
--

CREATE TABLE `domain_traffic` (
  `dtraff_id` int(10) unsigned NOT NULL auto_increment,
  `domain_id` int(10) unsigned DEFAULT NULL,
  `dtraff_time` bigint(20) unsigned DEFAULT NULL,
  `dtraff_web` bigint(20) unsigned DEFAULT NULL,
  `dtraff_ftp` bigint(20) unsigned DEFAULT NULL,
  `dtraff_mail` bigint(20) unsigned DEFAULT NULL,
  `dtraff_pop` bigint(20) unsigned DEFAULT NULL,
  `correction` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`dtraff_id`),
  KEY `i_correction` (`correction`),
  KEY `i_domain_id` (`domain_id`),
  KEY `i_dtraff_time` (`dtraff_time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_tpls`
--

CREATE TABLE `email_tpls` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `owner_id` int(10) unsigned NOT NULL default '0',
  `name` varchar(200) collate utf8_unicode_ci default NULL,
  `subject` varchar(200) collate utf8_unicode_ci default NULL,
  `message` text collate utf8_unicode_ci,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `error_pages`
--

CREATE TABLE `error_pages` (
  `ep_id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL default '0',
  `error_401` text collate utf8_unicode_ci NOT NULL,
  `error_403` text collate utf8_unicode_ci NOT NULL,
  `error_404` text collate utf8_unicode_ci NOT NULL,
  `error_500` text collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`ep_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ftp_group`
--

CREATE TABLE `ftp_group` (
  `groupname` varchar(255) collate utf8_unicode_ci default NULL,
  `gid` int(10) unsigned NOT NULL default '0',
  `members` text collate utf8_unicode_ci,
  UNIQUE KEY `groupname` (`groupname`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ftp_users`
--

CREATE TABLE `ftp_users` (
  `userid` varchar(255) collate utf8_unicode_ci default NULL,
  `passwd` varchar(255) collate utf8_unicode_ci default NULL,
  `uid` int(10) unsigned NOT NULL default '0',
  `gid` int(10) unsigned NOT NULL default '0',
  `shell` varchar(255) collate utf8_unicode_ci default NULL,
  `homedir` varchar(255) collate utf8_unicode_ci default NULL,
  UNIQUE KEY `userid` (`userid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hosting_plans`
--

CREATE TABLE `hosting_plans` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `reseller_id` int(10) unsigned NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `props` text collate utf8_unicode_ci,
  `description` text collate utf8_unicode_ci,
  `price` decimal(10,2) NOT NULL default '0.00',
  `setup_fee` decimal(10,2) NOT NULL default '0.00',
  `value` varchar(255) collate utf8_unicode_ci default NULL,
  `payment` varchar(255) collate utf8_unicode_ci default NULL,
  `status` int(10) unsigned NOT NULL default '0',
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `htaccess`
--

CREATE TABLE `htaccess` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `dmn_id` int(10) unsigned NOT NULL default '0',
  `user_id` varchar(255) collate utf8_unicode_ci default NULL,
  `group_id` varchar(255) collate utf8_unicode_ci default NULL,
  `auth_type` varchar(255) collate utf8_unicode_ci default NULL,
  `auth_name` varchar(255) collate utf8_unicode_ci default NULL,
  `path` varchar(255) collate utf8_unicode_ci default NULL,
  `status` varchar(255) collate utf8_unicode_ci default NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `htaccess_groups`
--

CREATE TABLE `htaccess_groups` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `dmn_id` int(10) unsigned NOT NULL default '0',
  `ugroup` varchar(255) collate utf8_unicode_ci default NULL,
  `members` text collate utf8_unicode_ci,
  `status` varchar(255) collate utf8_unicode_ci default NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `htaccess_users`
--

CREATE TABLE `htaccess_users` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `dmn_id` int(10) unsigned NOT NULL default '0',
  `uname` varchar(255) collate utf8_unicode_ci default NULL,
  `upass` varchar(255) collate utf8_unicode_ci default NULL,
  `status` varchar(255) collate utf8_unicode_ci default NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log`
--

CREATE TABLE `log` (
  `log_id` int(10) unsigned NOT NULL auto_increment,
  `log_time` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `log_message` varchar(250) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`log_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

CREATE TABLE `login` (
  `session_id` varchar(200) collate utf8_unicode_ci NOT NULL default '',
  `ipaddr` varchar(15) collate utf8_unicode_ci default NULL,
  `lastaccess` int(10) unsigned default NULL,
  `login_count` tinyint(1) default '0',
  `captcha_count` tinyint(1) default '0',
  `user_name` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`session_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mail_users`
--

CREATE TABLE `mail_users` (
  `mail_id` int(10) unsigned NOT NULL auto_increment,
  `mail_acc` varchar(200) collate utf8_unicode_ci default NULL,
  `mail_pass` varchar(150) collate utf8_unicode_ci default NULL,
  `mail_forward` text collate utf8_unicode_ci,
  `domain_id` int(10) unsigned default NULL,
  `mail_type` varchar(30) collate utf8_unicode_ci default NULL,
  `sub_id` int(10) unsigned default NULL,
  `status` varchar(255) collate utf8_unicode_ci default NULL,
  `mail_auto_respond` tinyint(1) NOT NULL default '0',
  `mail_auto_respond_text` text collate utf8_unicode_ci,
  `quota` int(10) default '10485760',
  `mail_addr` varchar(200) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`mail_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL default '0',
  `plan_id` int(10) unsigned NOT NULL default '0',
  `date` int(10) unsigned NOT NULL default '0',
  `domain_name` varchar(200) collate utf8_unicode_ci default NULL,
  `customer_id` varchar(200) collate utf8_unicode_ci default NULL,
  `fname` varchar(200) collate utf8_unicode_ci default NULL,
  `lname` varchar(200) collate utf8_unicode_ci default NULL,
  `gender` varchar(1) collate utf8_unicode_ci default NULL,
  `firm` varchar(200) collate utf8_unicode_ci default NULL,
  `zip` varchar(10) collate utf8_unicode_ci default NULL,
  `city` varchar(200) collate utf8_unicode_ci default NULL,
  `state` varchar(200) collate utf8_unicode_ci default NULL,
  `country` varchar(200) collate utf8_unicode_ci default NULL,
  `email` varchar(200) collate utf8_unicode_ci default NULL,
  `phone` varchar(200) collate utf8_unicode_ci default NULL,
  `fax` varchar(200) collate utf8_unicode_ci default NULL,
  `street1` varchar(200) collate utf8_unicode_ci default NULL,
  `street2` varchar(200) collate utf8_unicode_ci default NULL,
  `status` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders_settings`
--

CREATE TABLE `orders_settings` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL default '0',
  `header` text collate utf8_unicode_ci,
  `footer` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
-- --------------------------------------------------------

--
-- Table structure for table `quotalimits`
--

CREATE TABLE `quotalimits` (
  `name` varchar(30) DEFAULT NULL,
  `quota_type` enum('user','group','class','all') NOT NULL DEFAULT 'user',
  `per_session` enum('false','true') NOT NULL DEFAULT 'false',
  `limit_type` enum('soft','hard') NOT NULL DEFAULT 'soft',
  `bytes_in_avail` float NOT NULL DEFAULT '0',
  `bytes_out_avail` float NOT NULL DEFAULT '0',
  `bytes_xfer_avail` float NOT NULL DEFAULT '0',
  `files_in_avail` int(10) unsigned NOT NULL DEFAULT '0',
  `files_out_avail` int(10) unsigned NOT NULL DEFAULT '0',
  `files_xfer_avail` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quotatallies`
--

CREATE TABLE `quotatallies` (
  `name` varchar(30) NOT NULL DEFAULT '',
  `quota_type` enum('user','group','class','all') NOT NULL DEFAULT 'user',
  `bytes_in_used` float NOT NULL DEFAULT '0',
  `bytes_out_used` float NOT NULL DEFAULT '0',
  `bytes_xfer_used` float NOT NULL DEFAULT '0',
  `files_in_used` int(10) unsigned NOT NULL DEFAULT '0',
  `files_out_used` int(10) unsigned NOT NULL DEFAULT '0',
  `files_xfer_used` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reseller_props`
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
  `customer_id` varchar(200) collate utf8_unicode_ci default NULL,
  `reseller_ips` text collate utf8_unicode_ci,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `server_ips`
--

CREATE TABLE `server_ips` (
  `ip_id` int(10) unsigned NOT NULL auto_increment,
  `ip_number` varchar(15) collate utf8_unicode_ci default NULL,
  `ip_domain` varchar(200) collate utf8_unicode_ci default NULL,
  `ip_alias` varchar(200) collate utf8_unicode_ci default NULL,
  `ip_card` varchar(255) collate utf8_unicode_ci default NULL,
  `ip_ssl_domain_id` int(10) default NULL,
  `ip_status` varchar(255) collate utf8_unicode_ci default NULL,
  UNIQUE KEY `ip_id` (`ip_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `server_traffic`
--

CREATE TABLE `server_traffic` (
  `straff_id` int(10) unsigned NOT NULL auto_increment,
  `traff_time` int(10) unsigned DEFAULT NULL,
  `bytes_in` bigint(20) unsigned DEFAULT NULL,
  `bytes_out` bigint(20) unsigned DEFAULT NULL,
  `bytes_mail_in` bigint(20) unsigned DEFAULT NULL,
  `bytes_mail_out` bigint(20) unsigned DEFAULT NULL,
  `bytes_pop_in` bigint(20) unsigned DEFAULT NULL,
  `bytes_pop_out` bigint(20) unsigned DEFAULT NULL,
  `bytes_web_in` bigint(20) unsigned DEFAULT NULL,
  `bytes_web_out` bigint(20) unsigned DEFAULT NULL,
  `correction` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`straff_id`),
  KEY (`correction`),
  KEY (`traff_time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sql_database`
--

CREATE TABLE `sql_database` (
  `sqld_id` int(10) unsigned NOT NULL auto_increment,
  `domain_id` int(10) unsigned default '0',
  `sqld_name` varchar(64) character set utf8 collate utf8_bin default 'n/a',
  UNIQUE KEY `sqld_id` (`sqld_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sql_user`
--

CREATE TABLE `sql_user` (
  `sqlu_id` int(10) unsigned NOT NULL auto_increment,
  `sqld_id` int(10) unsigned default '0',
  `sqlu_name` varchar(64) collate utf8_unicode_ci default 'n/a',
  `sqlu_pass` varchar(64) collate utf8_unicode_ci default 'n/a',
  UNIQUE KEY `sqlu_id` (`sqlu_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `straff_settings`
--

CREATE TABLE `straff_settings` (
  `straff_max` int(10) unsigned DEFAULT NULL,
  `straff_warn` int(10) unsigned DEFAULT NULL,
  `straff_email` int(10) unsigned DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `straff_settings`
--

INSERT INTO `straff_settings` (`straff_max`, `straff_warn`, `straff_email`) VALUES (0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `subdomain`
--

CREATE TABLE `subdomain` (
  `subdomain_id` int(10) unsigned NOT NULL auto_increment,
  `domain_id` int(10) unsigned default NULL,
  `subdomain_name` varchar(200) collate utf8_unicode_ci default NULL,
  `subdomain_mount` varchar(200) collate utf8_unicode_ci default NULL,
  `subdomain_status` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`subdomain_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subdomain_alias`
--

CREATE TABLE `subdomain_alias` (
  `subdomain_alias_id` int(10) unsigned NOT NULL auto_increment,
  `alias_id` int(10) unsigned default NULL,
  `subdomain_alias_name` varchar(200) collate utf8_unicode_ci default NULL,
  `subdomain_alias_mount` varchar(200) collate utf8_unicode_ci default NULL,
  `subdomain_alias_status` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`subdomain_alias_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suexec_props`
--

CREATE TABLE `suexec_props` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `uid` int(10) unsigned default NULL,
  `gid` int(10) unsigned default NULL,
  `usr` varchar(255) collate utf8_unicode_ci default NULL,
  `grp` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
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
  `ticket_subject` varchar(255) collate utf8_unicode_ci default NULL,
  `ticket_message` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ticket_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_gui_props`
--

CREATE TABLE `user_gui_props` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL default '0',
  `lang` varchar(255) collate utf8_unicode_ci default '',
  `layout` varchar(255) collate utf8_unicode_ci default '',
  `logo` varchar(255) collate utf8_unicode_ci NOT NULL default '0',
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
