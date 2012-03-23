--
-- i-MSCP - internet Multi Server Control Panel
--
-- Copyright (C) 2001-2006 by moleSoftware GmbH - http://www.molesoftware.com
-- Copyright (C) 2006-2010 by isp Control Panel - http://ispcp.net
-- Copyright (C) 2010-2012 by internet Multi Server Control Panel - http://i-mscp.net
--
-- Version: $Id$
--
-- The contents of this file are subject to the Mozilla Public License
-- Version 1.1 (the "License"); you may not use this file except in
-- compliance with the License. You may obtain a copy of the License at
-- http://www.mozilla.org/MPL/
--
-- Software distributed under the License is distributed on an "AS IS"
-- basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
-- License for the specific language governing rights and limitations
-- under the License.
--
-- The Original Code is "VHCS - Virtual Hosting Control System".
--
-- The Initial Developer of the Original Code is moleSoftware GmbH.
-- Portions created by Initial Developer are Copyright (C) 2001-2006
-- by moleSoftware GmbH. All Rights Reserved.
--
-- Portions created by the ispCP Team are Copyright (C) 2006-2010 by
-- isp Control Panel. All Rights Reserved.
--
-- Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
-- internet Multi Server Control Panel. All Rights Reserved.
--
-- The i-MSCP Home Page is:
--
--    http://i-mscp.net
--
-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE IF NOT EXISTS `admin` (
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
  UNIQUE KEY `admin_name` (`admin_name`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `autoreplies_log`
--

CREATE TABLE IF NOT EXISTS `autoreplies_log` (
  `time` DATETIME NOT NULL COMMENT 'Date and time of the sent autoreply',
  `from` VARCHAR( 255 ) NOT NULL COMMENT 'autoreply message sender',
  `to` VARCHAR( 255 ) NOT NULL COMMENT 'autoreply message recipient',
  INDEX ( `time` )
) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci COMMENT = 'Sent autoreplies log table';

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

CREATE TABLE IF NOT EXISTS `config` (
  `name` varchar(255) collate utf8_unicode_ci NOT NULL default '',
  `value` longtext collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `config`
--

INSERT IGNORE INTO `config` (`name`, `value`) VALUES
('PORT_IMSCP_DAEMON', '9876;tcp;i-MSCP-Daemon;1;0;127.0.0.1'),
('PORT_FTP', '21;tcp;FTP;1;0;'),
('PORT_SSH', '22;tcp;SSH;1;1;'),
('PORT_TELNET', '23;tcp;TELNET;1;0;'),
('PORT_SMTP', '25;tcp;SMTP;1;0;'),
('PORT_SMTP-SSL', '465;tcp;SMTP-SSL;0;0;'),
('PORT_DNS', '53;tcp;DNS;1;0;'),
('PORT_HTTP', '80;tcp;HTTP;1;0;'),
('PORT_HTTPS', '443;tcp;HTTPS;0;0;'),
('PORT_POP3', '110;tcp;POP3;1;0;'),
('PORT_POP3-SSL', '995;tcp;POP3-SSL;0;0;'),
('PORT_IMAP', '143;tcp;IMAP;1;0;'),
('PORT_IMAP-SSL', '993;tcp;IMAP-SSL;0;0;'),
('PORT_POSTGREY', '10023;tcp;POSTGREY;1;1;localhost'),
('PORT_AMAVIS', '10024;tcp;AMaVis;0;1;localhost'),
('PORT_SPAMASSASSIN', '783;tcp;SPAMASSASSIN;0;1;localhost'),
('PORT_POLICYD-WEIGHT', '12525;tcp;POLICYD-WEIGHT;1;1;localhost'),
('SHOW_COMPRESSION_SIZE', '1'),
('PREVENT_EXTERNAL_LOGIN_ADMIN', '1'),
('PREVENT_EXTERNAL_LOGIN_RESELLER', '1'),
('PREVENT_EXTERNAL_LOGIN_CLIENT', '1'),
('DATABASE_REVISION', '108'),
('PHPINI_ALLOW_URL_FOPEN', 'Off'),
('PHPINI_DISPLAY_ERRORS', 'Off'),
('PHPINI_REGISTER_GLOBALS', 'Off'),
('PHPINI_UPLOAD_MAX_FILESIZE', '10'),
('PHPINI_POST_MAX_SIZE', '10'),
('PHPINI_MEMORY_LIMIT', '128'),
('PHPINI_OPEN_BASEDIR', ''),
('PHPINI_MAX_INPUT_TIME', '60'),
('PHPINI_MAX_EXECUTION_TIME', '30'),
('PHPINI_ERROR_REPORTING', 'E_ALL & ~E_NOTICE & ~E_WARNING'),
('PHPINI_DISABLE_FUNCTIONS', 'show_source,system,shell_exec,passthru,exec,phpinfo,shell,symlink');

-- --------------------------------------------------------

--
-- Table structure for table `custom_menus`
--

CREATE TABLE IF NOT EXISTS `custom_menus` (
  `menu_id` int(10) unsigned NOT NULL auto_increment,
  `menu_level` varchar(10) collate utf8_unicode_ci default NULL,
  `menu_order` int(10) unsigned DEFAULT NULL,
  `menu_name` varchar(255) collate utf8_unicode_ci default NULL,
  `menu_link` varchar(200) collate utf8_unicode_ci default NULL,
  `menu_target` varchar(200) collate utf8_unicode_ci NOT NULL default '',
  PRIMARY KEY  (`menu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `domain`
--

CREATE TABLE IF NOT EXISTS `domain` (
  `domain_id` int(10) unsigned NOT NULL auto_increment,
  `domain_name` varchar(200) collate utf8_unicode_ci default NULL,
  `domain_gid` int(10) unsigned NOT NULL default '0',
  `domain_uid` int(10) unsigned NOT NULL default '0',
  `domain_admin_id` int(10) unsigned NOT NULL default '0',
  `domain_created_id` int(10) unsigned NOT NULL default '0',
  `domain_created` int(10) unsigned NOT NULL default '0',
  `domain_expires` int(10) unsigned NOT NULL default '0',
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
  `domain_software_allowed` varchar(15) collate utf8_unicode_ci NOT NULL default 'no',
  `phpini_perm_system` VARCHAR( 20 ) NOT NULL DEFAULT 'no',
  `phpini_perm_register_globals` VARCHAR( 20 ) NOT NULL DEFAULT 'no',
  `phpini_perm_allow_url_fopen` VARCHAR( 20 ) NOT NULL DEFAULT 'no',
  `phpini_perm_display_errors` VARCHAR( 20 ) NOT NULL DEFAULT 'no',
  `phpini_perm_disable_functions` VARCHAR( 20 ) NOT NULL DEFAULT 'no',
  PRIMARY KEY `domain_id` (`domain_id`),
  UNIQUE KEY `domain_name` (`domain_name`),
  KEY `i_domain_admin_id` (`domain_admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `domain_aliasses`
--

CREATE TABLE IF NOT EXISTS `domain_aliasses` (
  `alias_id` int(10) unsigned NOT NULL auto_increment,
  `domain_id` int(10) unsigned default NULL,
  `alias_name` varchar(200) collate utf8_unicode_ci default NULL,
  `alias_status` varchar(255) collate utf8_unicode_ci default NULL,
  `alias_mount` varchar(200) collate utf8_unicode_ci default NULL,
  `alias_ip_id` int(10) unsigned default NULL,
  `url_forward` varchar(200) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`alias_id`),
  KEY `domain_id` (`domain_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `domain_dns`
--

CREATE TABLE IF NOT EXISTS `domain_dns` (
  `domain_dns_id` int(11) NOT NULL auto_increment,
  `domain_id` int(11) NOT NULL,
  `alias_id` int(11) NOT NULL,
  `domain_dns` varchar(50) collate utf8_unicode_ci NOT NULL,
  `domain_class` enum('IN','CH','HS') collate utf8_unicode_ci NOT NULL default 'IN',
  `domain_type` enum('A','AAAA','CERT','CNAME','DNAME','GPOS','KEY','KX','MX','NAPTR','NSAP','NS','NXT','PTR','PX','SIG','SRV','TXT') collate utf8_unicode_ci NOT NULL default 'A',
  `domain_text` varchar(128) collate utf8_unicode_ci NOT NULL,
  `protected` varchar(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  PRIMARY KEY  (`domain_dns_id`),
  UNIQUE KEY `domain_id` (`domain_id`,`alias_id`,`domain_dns`,`domain_class`,`domain_type`,`domain_text`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `domain_traffic`
--

CREATE TABLE IF NOT EXISTS `domain_traffic` (
  `dtraff_id` int(10) unsigned NOT NULL auto_increment,
  `domain_id` int(10) unsigned default NULL,
  `dtraff_time` bigint(20) unsigned default NULL,
  `dtraff_web` bigint(20) unsigned default NULL,
  `dtraff_ftp` bigint(20) unsigned default NULL,
  `dtraff_mail` bigint(20) unsigned default NULL,
  `dtraff_pop` bigint(20) unsigned default NULL,
  PRIMARY KEY  (`dtraff_id`),
  KEY `i_domain_id` (`domain_id`),
  KEY `i_dtraff_time` (`dtraff_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_tpls`
--

CREATE TABLE IF NOT EXISTS `email_tpls` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `owner_id` int(10) unsigned NOT NULL default '0',
  `name` varchar(200) collate utf8_unicode_ci default NULL,
  `subject` varchar(200) collate utf8_unicode_ci default NULL,
  `message` text collate utf8_unicode_ci,
  PRIMARY KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `error_pages`
--

CREATE TABLE IF NOT EXISTS `error_pages` (
  `ep_id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL default '0',
  `error_401` text collate utf8_unicode_ci NOT NULL,
  `error_403` text collate utf8_unicode_ci NOT NULL,
  `error_404` text collate utf8_unicode_ci NOT NULL,
  `error_500` text collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`ep_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ftp_group`
--

CREATE TABLE IF NOT EXISTS `ftp_group` (
  `groupname` varchar(255) collate utf8_unicode_ci default NULL,
  `gid` int(10) unsigned NOT NULL default '0',
  `members` text collate utf8_unicode_ci,
  UNIQUE KEY `groupname` (`groupname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ftp_users`
--

CREATE TABLE IF NOT EXISTS `ftp_users` (
  `userid` varchar(255) collate utf8_unicode_ci default NULL,
  `passwd` varchar(255) collate utf8_unicode_ci default NULL,
  `rawpasswd` varchar(255) collate utf8_unicode_ci default NULL,
  `uid` int(10) unsigned NOT NULL default '0',
  `gid` int(10) unsigned NOT NULL default '0',
  `shell` varchar(255) collate utf8_unicode_ci default NULL,
  `homedir` varchar(255) collate utf8_unicode_ci default NULL,
  UNIQUE KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hosting_plans`
--

CREATE TABLE IF NOT EXISTS `hosting_plans` (
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
  `tos`	BLOB NOT NULL,
  PRIMARY KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `htaccess`
--

CREATE TABLE IF NOT EXISTS `htaccess` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `dmn_id` int(10) unsigned NOT NULL default '0',
  `user_id` varchar(255) collate utf8_unicode_ci default NULL,
  `group_id` varchar(255) collate utf8_unicode_ci default NULL,
  `auth_type` varchar(255) collate utf8_unicode_ci default NULL,
  `auth_name` varchar(255) collate utf8_unicode_ci default NULL,
  `path` varchar(255) collate utf8_unicode_ci default NULL,
  `status` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `htaccess_groups`
--

CREATE TABLE IF NOT EXISTS `htaccess_groups` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `dmn_id` int(10) unsigned NOT NULL default '0',
  `ugroup` varchar(255) collate utf8_unicode_ci default NULL,
  `members` text collate utf8_unicode_ci,
  `status` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `htaccess_users`
--

CREATE TABLE IF NOT EXISTS `htaccess_users` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `dmn_id` int(10) unsigned NOT NULL default '0',
  `uname` varchar(255) collate utf8_unicode_ci default NULL,
  `upass` varchar(255) collate utf8_unicode_ci default NULL,
  `status` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log`
--

CREATE TABLE IF NOT EXISTS `log` (
  `log_id` int(10) unsigned NOT NULL auto_increment,
  `log_time` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `log_message` text collate utf8_unicode_ci,
  PRIMARY KEY  (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

CREATE TABLE IF NOT EXISTS `login` (
  `session_id` varchar(200) collate utf8_unicode_ci NOT NULL default '',
  `ipaddr` varchar(40) collate utf8_unicode_ci default NULL,
  `lastaccess` int(10) unsigned default NULL,
  `login_count` tinyint(1) default '0',
  `captcha_count` tinyint(1) default '0',
  `user_name` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mail_users`
--

CREATE TABLE IF NOT EXISTS `mail_users` (
  `mail_id` int(10) unsigned NOT NULL auto_increment,
  `mail_acc` text collate utf8_unicode_ci default NULL,
  `mail_pass` varchar(150) collate utf8_unicode_ci default NULL,
  `mail_forward` text collate utf8_unicode_ci,
  `domain_id` int(10) unsigned default NULL,
  `mail_type` varchar(30) collate utf8_unicode_ci default NULL,
  `sub_id` int(10) unsigned default NULL,
  `status` varchar(255) collate utf8_unicode_ci default NULL,
  `mail_auto_respond` tinyint(1) NOT NULL default '0',
  `mail_auto_respond_text` text collate utf8_unicode_ci,
  `quota` int(10) default '104857600',
  `mail_addr` varchar(254) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`mail_id`),
  KEY `domain_id` (`domain_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE IF NOT EXISTS `orders` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders_settings`
--

CREATE TABLE IF NOT EXISTS `orders_settings` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL default '0',
  `header` text collate utf8_unicode_ci,
  `footer` text collate utf8_unicode_ci,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `php_ini`
--

CREATE TABLE IF NOT EXISTS `php_ini` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain_id` int(10) NOT NULL,
  `status` varchar(55) COLLATE utf8_unicode_ci NOT NULL,
  `disable_functions` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'show_source, system, shell_exec, passthru, exec, phpinfo, shell, symlink, popen, proc_open',
  `allow_url_fopen` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Off',
  `register_globals` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Off',
  `display_errors` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Off',
  `error_reporting` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'E_ALL & ~E_DEPRECATED',
  `post_max_size` int(11) NOT NULL DEFAULT '10',
  `upload_max_filesize` int(11) NOT NULL DEFAULT '10',
  `max_execution_time` int(11) NOT NULL DEFAULT '30',
  `max_input_time` int(11) NOT NULL DEFAULT '60',
  `memory_limit` int(11) NOT NULL DEFAULT '128',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plugin`
--

CREATE TABLE IF NOT EXISTS `plugin` (
  `plugin_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `plugin_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `plugin_type` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `plugin_info` text COLLATE utf8_unicode_ci NOT NULL,
  `plugin_config` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `plugin_status` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'disabled',
  PRIMARY KEY (`plugin_id`),
  UNIQUE KEY `name` (`plugin_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quotalimits`
--

CREATE TABLE IF NOT EXISTS `quotalimits` (
  `name` varchar(30) collate utf8_unicode_ci NOT NULL default '',
  `quota_type` enum('user','group','class','all') collate utf8_unicode_ci NOT NULL default 'user',
  `per_session` enum('false','true') collate utf8_unicode_ci NOT NULL default 'false',
  `limit_type` enum('soft','hard') collate utf8_unicode_ci NOT NULL default 'soft',
  `bytes_in_avail` float NOT NULL default '0',
  `bytes_out_avail` float NOT NULL default '0',
  `bytes_xfer_avail` float NOT NULL default '0',
  `files_in_avail` int(10) unsigned NOT NULL default '0',
  `files_out_avail` int(10) unsigned NOT NULL default '0',
  `files_xfer_avail` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quotatallies`
--

CREATE TABLE IF NOT EXISTS `quotatallies` (
  `name` varchar(30) collate utf8_unicode_ci NOT NULL default '',
  `quota_type` enum('user','group','class','all') collate utf8_unicode_ci NOT NULL default 'user',
  `bytes_in_used` float NOT NULL default '0',
  `bytes_out_used` float NOT NULL default '0',
  `bytes_xfer_used` float NOT NULL default '0',
  `files_in_used` int(10) unsigned NOT NULL default '0',
  `files_out_used` int(10) unsigned NOT NULL default '0',
  `files_xfer_used` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quota_dovecot`
--

CREATE TABLE IF NOT EXISTS `quota_dovecot` (
  `username` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `bytes` bigint(20) NOT NULL DEFAULT '0',
  `messages` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reseller_props`
--

CREATE TABLE IF NOT EXISTS `reseller_props` (
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
  `support_system` ENUM( 'yes', 'no' ) NOT NULL DEFAULT 'yes',
  `customer_id` varchar(200) collate utf8_unicode_ci default NULL,
  `reseller_ips` text collate utf8_unicode_ci,
  `software_allowed` varchar(15) collate utf8_general_ci NOT NULL default 'no',
  `softwaredepot_allowed` varchar(15) collate utf8_general_ci NOT NULL default 'yes',
  `websoftwaredepot_allowed` varchar(15) collate utf8_general_ci NOT NULL default 'yes',
  `php_ini_system` VARCHAR(15) NOT NULL DEFAULT 'no',
  `php_ini_al_disable_functions` VARCHAR(15) NOT NULL DEFAULT 'no',
  `php_ini_al_allow_url_fopen` VARCHAR(15) NOT NULL DEFAULT 'no',
  `php_ini_al_register_globals` VARCHAR(15) NOT NULL DEFAULT 'no',
  `php_ini_al_display_errors` VARCHAR(15) NOT NULL DEFAULT 'no',
  `php_ini_max_post_max_size` int(11) NOT NULL DEFAULT '0',
  `php_ini_max_upload_max_filesize` int(11) NOT NULL DEFAULT '0',
  `php_ini_max_max_execution_time` int(11) NOT NULL DEFAULT '0',
  `php_ini_max_max_input_time` int(11) NOT NULL DEFAULT '0',
  `php_ini_max_memory_limit` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY `id` (`id`),
  KEY `reseller_id` (`reseller_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `server_ips`
--

CREATE TABLE IF NOT EXISTS `server_ips` (
  `ip_id` int(10) unsigned NOT NULL auto_increment,
  `ip_number` varchar(40) collate utf8_unicode_ci default NULL,
  `ip_domain` varchar(200) collate utf8_unicode_ci default NULL,
  `ip_alias` varchar(200) collate utf8_unicode_ci default NULL,
  `ip_card` varchar(255) collate utf8_unicode_ci default NULL,
  `ip_ssl_domain_id` int(10) default NULL,
  `ip_status` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY `ip_id` (`ip_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `server_traffic`
--

CREATE TABLE IF NOT EXISTS `server_traffic` (
  `straff_id` int(10) unsigned NOT NULL auto_increment,
  `traff_time` int(10) unsigned default NULL,
  `bytes_in` bigint(20) unsigned default NULL,
  `bytes_out` bigint(20) unsigned default NULL,
  `bytes_mail_in` bigint(20) unsigned default NULL,
  `bytes_mail_out` bigint(20) unsigned default NULL,
  `bytes_pop_in` bigint(20) unsigned default NULL,
  `bytes_pop_out` bigint(20) unsigned default NULL,
  `bytes_web_in` bigint(20) unsigned default NULL,
  `bytes_web_out` bigint(20) unsigned default NULL,
  PRIMARY KEY  (`straff_id`),
  KEY `traff_time` (`traff_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sql_database`
--

CREATE TABLE IF NOT EXISTS `sql_database` (
  `sqld_id` int(10) unsigned NOT NULL auto_increment,
  `domain_id` int(10) unsigned default '0',
  `sqld_name` varchar(64) character set utf8 collate utf8_bin default 'n/a',
  PRIMARY KEY `sqld_id` (`sqld_id`),
  KEY `domain_id` (`domain_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sql_user`
--

CREATE TABLE IF NOT EXISTS `sql_user` (
  `sqlu_id` int(10) unsigned NOT NULL auto_increment,
  `sqld_id` int(10) unsigned default '0',
  `sqlu_name` varchar(64) collate utf8_unicode_ci default 'n/a',
  `sqlu_pass` varchar(64) collate utf8_unicode_ci default 'n/a',
  PRIMARY KEY `sqlu_id` (`sqlu_id`),
  KEY `sqld_id` (`sqld_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ssl_certs`
--

CREATE TABLE IF NOT EXISTS `ssl_certs` (
  `cert_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id` int(10) NOT NULL,
  `type` enum('dmn','als','sub','alssub') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'dmn',
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `key` text COLLATE utf8_unicode_ci NOT NULL,
  `cert` text COLLATE utf8_unicode_ci NOT NULL,
  `ca_cert` text COLLATE utf8_unicode_ci,
  `status` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`cert_id`),
  KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `straff_settings`
--

CREATE TABLE IF NOT EXISTS `straff_settings` (
  `straff_max` int(10) unsigned default NULL,
  `straff_warn` int(10) unsigned default NULL,
  `straff_email` int(10) unsigned default NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `straff_settings`
--

INSERT IGNORE INTO `straff_settings` (`straff_max`, `straff_warn`, `straff_email`) VALUES (0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `subdomain`
--

CREATE TABLE IF NOT EXISTS `subdomain` (
  `subdomain_id` int(10) unsigned NOT NULL auto_increment,
  `domain_id` int(10) unsigned default NULL,
  `subdomain_name` varchar(200) collate utf8_unicode_ci default NULL,
  `subdomain_mount` varchar(200) collate utf8_unicode_ci default NULL,
  `subdomain_url_forward` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `subdomain_status` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`subdomain_id`),
  KEY `domain_id` (`domain_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subdomain_alias`
--

CREATE TABLE IF NOT EXISTS `subdomain_alias` (
  `subdomain_alias_id` int(10) unsigned NOT NULL auto_increment,
  `alias_id` int(10) unsigned default NULL,
  `subdomain_alias_name` varchar(200) collate utf8_unicode_ci default NULL,
  `subdomain_alias_mount` varchar(200) collate utf8_unicode_ci default NULL,
  `subdomain_alias_url_forward` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `subdomain_alias_status` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`subdomain_alias_id`),
  KEY `alias_id` (`alias_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE IF NOT EXISTS `tickets` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_gui_props`
--

CREATE TABLE IF NOT EXISTS `user_gui_props` (
  `user_id` int(10) unsigned NOT NULL,
  `lang` varchar(5) collate utf8_unicode_ci default '',
  `layout` varchar(100) collate utf8_unicode_ci default NULL,
  `layout_color` varchar(15) COLLATE utf8_unicode_ci default NULL,
  `logo` varchar(255) collate utf8_unicode_ci NOT NULL default '',
  `show_main_menu_labels` tinyint(1) NOT NULL DEFAULT '1',
  UNIQUE `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `web_software`
--

CREATE TABLE IF NOT EXISTS `web_software` (
  `software_id` int(10) unsigned NOT NULL auto_increment,
  `software_master_id` int(10) unsigned NOT NULL default '0',
  `reseller_id` int(10) unsigned NOT NULL default '0',
  `software_installtype` varchar(15) character set utf8 collate utf8_unicode_ci NOT NULL,
  `software_name` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL,
  `software_version` varchar(20) character set utf8 collate utf8_unicode_ci NOT NULL,
  `software_language` varchar(15) character set utf8 collate utf8_unicode_ci NOT NULL,
  `software_type` varchar(20) character set utf8 collate utf8_unicode_ci NOT NULL,
  `software_db` tinyint(1) NOT NULL,
  `software_archive` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL,
  `software_installfile` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL,
  `software_prefix` varchar(50) character set utf8 collate utf8_unicode_ci NOT NULL,
  `software_link` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL,
  `software_desc` mediumtext character set utf8 collate utf8_unicode_ci NOT NULL,
  `software_active` int(1) NOT NULL,
  `software_status` varchar(15) character set utf8 collate utf8_unicode_ci NOT NULL,
  `rights_add_by` int(10) unsigned NOT NULL default '0',
  `software_depot` varchar(15) character set utf8 collate utf8_unicode_ci NOT NULL NOT NULL DEFAULT 'no',
  PRIMARY KEY  (`software_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `web_software_inst`
--

CREATE TABLE IF NOT EXISTS `web_software_inst` (
  `domain_id` int(10) unsigned NOT NULL,
  `alias_id` int(10) unsigned NOT NULL default '0',
  `subdomain_id` int(10) unsigned NOT NULL default '0',
  `subdomain_alias_id` int(10) unsigned NOT NULL default '0',
  `software_id` int(10) NOT NULL,
  `software_master_id` int(10) unsigned NOT NULL default '0',
  `software_res_del` int(1) NOT NULL default '0',
  `software_name` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL,
  `software_version` varchar(20) character set utf8 collate utf8_unicode_ci NOT NULL,
  `software_language` varchar(15) character set utf8 collate utf8_unicode_ci NOT NULL,
  `path` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL default '0',
  `software_prefix` varchar(50) character set utf8 collate utf8_unicode_ci NOT NULL default '0',
  `db` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL default '0',
  `database_user` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL default '0',
  `database_tmp_pwd` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL default '0',
  `install_username` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL default '0',
  `install_password` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL default '0',
  `install_email` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL default '0',
  `software_status` varchar(15) character set utf8 collate utf8_unicode_ci NOT NULL,
  `software_depot` varchar(15) character set utf8 collate utf8_unicode_ci NOT NULL NOT NULL DEFAULT 'no',
  KEY `software_id` (`software_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `web_software_depot`
--

CREATE TABLE IF NOT EXISTS `web_software_depot` (
  `package_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `package_install_type` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
  `package_title` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `package_version` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `package_language` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
  `package_type` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `package_description` mediumtext character set utf8 collate utf8_unicode_ci NOT NULL,
  `package_vendor_hp` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `package_download_link` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `package_signature_link` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`package_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `web_software_options`
--

CREATE TABLE IF NOT EXISTS `web_software_options` (
  `use_webdepot` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `webdepot_xml_url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `webdepot_last_update` datetime NOT NULL,
  UNIQUE KEY `use_webdepot` (`use_webdepot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `web_software_options`
--

INSERT IGNORE INTO `web_software_options` (`use_webdepot`, `webdepot_xml_url`, `webdepot_last_update`) VALUES (1, 'http://app-pkg.i-mscp.net/imscp_webdepot_list.xml', '0000-00-00 00:00:00');

CREATE TABLE IF NOT EXISTS `roundcube_users` (
	`user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`username` varchar(128) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
	`mail_host` varchar(128) NOT NULL,
	`alias` varchar(128) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
	`created` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
	`last_login` datetime DEFAULT NULL,
	`language` varchar(5) DEFAULT NULL,
	`preferences` text,
	PRIMARY KEY (`user_id`),
	UNIQUE KEY `username` (`username`,`mail_host`),
	KEY `alias_index` (`alias`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `roundcube_cache` (
	`cache_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`cache_key` varchar(128) CHARACTER SET ascii NOT NULL,
	`created` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
	`data` longtext NOT NULL,
	`user_id` int(10) unsigned NOT NULL,
	PRIMARY KEY (`cache_id`),
	KEY `created_index` (`created`),
	KEY `user_cache_index` (`user_id`,`cache_key`),
	CONSTRAINT `user_id_fk_cache` FOREIGN KEY (`user_id`) REFERENCES `roundcube_users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `roundcube_cache_index` (
	`user_id` int(10) unsigned NOT NULL,
	`mailbox` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
	`changed` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
	`valid` tinyint(1) NOT NULL DEFAULT '0',
	`data` longtext NOT NULL,
	PRIMARY KEY (`user_id`,`mailbox`),
	KEY `changed_index` (`changed`),
	CONSTRAINT `user_id_fk_cache_index` FOREIGN KEY (`user_id`) REFERENCES `roundcube_users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `roundcube_cache_messages` (
	`user_id` int(10) unsigned NOT NULL,
	`mailbox` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
	`uid` int(11) unsigned NOT NULL DEFAULT '0',
	`changed` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
	`data` longtext NOT NULL,
	`flags` int(11) NOT NULL DEFAULT '0',
	PRIMARY KEY (`user_id`,`mailbox`,`uid`),
	KEY `changed_index` (`changed`),
	CONSTRAINT `user_id_fk_cache_messages` FOREIGN KEY (`user_id`) REFERENCES `roundcube_users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `roundcube_cache_thread` (
	`user_id` int(10) unsigned NOT NULL,
	`mailbox` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
	`changed` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
	`data` longtext NOT NULL,
	PRIMARY KEY (`user_id`,`mailbox`),
	KEY `changed_index` (`changed`),
	CONSTRAINT `user_id_fk_cache_thread` FOREIGN KEY (`user_id`) REFERENCES `roundcube_users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `roundcube_contactgroups` (
	`contactgroup_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`user_id` int(10) unsigned NOT NULL,
	`changed` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
	`del` tinyint(1) NOT NULL DEFAULT '0',
	`name` varchar(128) NOT NULL DEFAULT '',
	PRIMARY KEY (`contactgroup_id`),
	KEY `contactgroups_user_index` (`user_id`,`del`),
	CONSTRAINT `user_id_fk_contactgroups` FOREIGN KEY (`user_id`) REFERENCES `roundcube_users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `roundcube_contacts` (
	`contact_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`changed` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
	`del` tinyint(1) NOT NULL DEFAULT '0',
	`name` varchar(128) NOT NULL DEFAULT '',
	`email` text NOT NULL,
	`firstname` varchar(128) NOT NULL DEFAULT '',
	`surname` varchar(128) NOT NULL DEFAULT '',
	`vcard` longtext,
	`words` text,
	`user_id` int(10) unsigned NOT NULL,
	PRIMARY KEY (`contact_id`),
	KEY `user_contacts_index` (`user_id`,`del`),
	CONSTRAINT `user_id_fk_contacts` FOREIGN KEY (`user_id`) REFERENCES `roundcube_users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `roundcube_contactgroupmembers` (
	`contactgroup_id` int(10) unsigned NOT NULL,
	`contact_id` int(10) unsigned NOT NULL,
	`created` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
	PRIMARY KEY (`contactgroup_id`,`contact_id`),
	KEY `contactgroupmembers_contact_index` (`contact_id`),
	CONSTRAINT `contactgroup_id_fk_contactgroups` FOREIGN KEY (`contactgroup_id`) REFERENCES `roundcube_contactgroups` (`contactgroup_id`) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT `contact_id_fk_contacts` FOREIGN KEY (`contact_id`) REFERENCES `roundcube_contacts` (`contact_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `roundcube_dictionary` (
	`user_id` int(10) unsigned DEFAULT NULL,
	`language` varchar(5) NOT NULL,
	`data` longtext NOT NULL,
	UNIQUE KEY `uniqueness` (`user_id`,`language`),
	CONSTRAINT `user_id_fk_dictionary` FOREIGN KEY (`user_id`) REFERENCES `roundcube_users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `roundcube_identities` (
	`identity_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`user_id` int(10) unsigned NOT NULL,
	`changed` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
	`del` tinyint(1) NOT NULL DEFAULT '0',
	`standard` tinyint(1) NOT NULL DEFAULT '0',
	`name` varchar(128) NOT NULL,
	`organization` varchar(128) NOT NULL DEFAULT '',
	`email` varchar(128) NOT NULL,
	`reply-to` varchar(128) NOT NULL DEFAULT '',
	`bcc` varchar(128) NOT NULL DEFAULT '',
	`signature` text,
	`html_signature` tinyint(1) NOT NULL DEFAULT '0',
	PRIMARY KEY (`identity_id`),
	KEY `user_identities_index` (`user_id`,`del`),
	CONSTRAINT `user_id_fk_identities` FOREIGN KEY (`user_id`) REFERENCES `roundcube_users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `roundcube_searches` (
	`search_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`user_id` int(10) unsigned NOT NULL,
	`type` int(3) NOT NULL DEFAULT '0',
	`name` varchar(128) NOT NULL,
	`data` text,
	PRIMARY KEY (`search_id`),
	UNIQUE KEY `uniqueness` (`user_id`,`type`,`name`),
	CONSTRAINT `user_id_fk_searches` FOREIGN KEY (`user_id`) REFERENCES `roundcube_users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `roundcube_session` (
	`sess_id` varchar(128) NOT NULL,
	`created` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
	`changed` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
	`ip` varchar(40) NOT NULL,
	`vars` mediumtext NOT NULL,
	PRIMARY KEY (`sess_id`),
	KEY `changed_index` (`changed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
