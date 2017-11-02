--
-- Table structure for table `admin`
--

CREATE TABLE IF NOT EXISTS `admin` (
  `admin_id` int(10) unsigned NOT NULL auto_increment,
  `admin_name` varchar(200) collate utf8_unicode_ci DEFAULT NULL,
  `admin_pass` varchar(200) collate utf8_unicode_ci DEFAULT NULL,
  `admin_type` varchar(10) collate utf8_unicode_ci DEFAULT NULL,
  `admin_sys_name` varchar(16) collate utf8_unicode_ci DEFAULT NULL,
  `admin_sys_uid` int(10) unsigned NOT NULL DEFAULT '0',
  `admin_sys_gname` varchar(32) collate utf8_unicode_ci DEFAULT NULL,
  `admin_sys_gid`int(10) unsigned NOT NULL DEFAULT '0',
  `domain_created` int(10) unsigned NOT NULL DEFAULT '0',
  `customer_id` varchar(200) collate utf8_unicode_ci DEFAULT '0',
  `created_by` int(10) unsigned DEFAULT '0',
  `fname` varchar(200) collate utf8_unicode_ci DEFAULT NULL,
  `lname` varchar(200) collate utf8_unicode_ci DEFAULT NULL,
  `gender` varchar(1) collate utf8_unicode_ci DEFAULT NULL,
  `firm` varchar(200) collate utf8_unicode_ci DEFAULT NULL,
  `zip` varchar(10) collate utf8_unicode_ci DEFAULT NULL,
  `city` varchar(200) collate utf8_unicode_ci DEFAULT NULL,
  `state` varchar(200) collate utf8_unicode_ci DEFAULT NULL,
  `country` varchar(200) collate utf8_unicode_ci DEFAULT NULL,
  `email` varchar(200) collate utf8_unicode_ci DEFAULT NULL,
  `phone` varchar(200) collate utf8_unicode_ci DEFAULT NULL,
  `fax` varchar(200) collate utf8_unicode_ci DEFAULT NULL,
  `street1` varchar(200) collate utf8_unicode_ci DEFAULT NULL,
  `street2` varchar(200) collate utf8_unicode_ci DEFAULT NULL,
  `uniqkey` varchar(255) collate utf8_unicode_ci DEFAULT NULL,
  `uniqkey_time` timestamp NULL DEFAULT NULL,
  `admin_status` varchar(255) collate utf8_unicode_ci NOT NULL DEFAULT 'ok',
  UNIQUE KEY `admin_id` (`admin_id`),
  UNIQUE KEY `admin_name` (`admin_name`),
  INDEX `created_by` (`created_by`)
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
  `name` varchar(255) collate utf8_unicode_ci NOT NULL DEFAULT '',
  `value` longtext collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `config`
--

INSERT IGNORE INTO `config` (`name`, `value`) VALUES
  ('PORT_IMSCP_DAEMON', '9876;tcp;i-MSCP-Daemon;1;127.0.0.1'),
  ('PORT_FTP', '21;tcp;FTP;1;0.0.0.0'),
  ('PORT_SSH', '22;tcp;SSH;1;0.0.0.0'),
  ('PORT_TELNET', '23;tcp;TELNET;1;0.0.0.0'),
  ('PORT_SMTP', '25;tcp;SMTP;1;0.0.0.0'),
  ('PORT_SMTP-SSL', '465;tcp;SMTP-SSL;0;0.0.0.0'),
  ('PORT_DNS', '53;tcp;DNS;1;0.0.0.0'),
  ('PORT_HTTP', '80;tcp;HTTP;1;0.0.0.0'),
  ('PORT_HTTPS', '443;tcp;HTTPS;0;0.0.0.0'),
  ('PORT_POP3', '110;tcp;POP3;1;0.0.0.0'),
  ('PORT_POP3-SSL', '995;tcp;POP3-SSL;0;0.0.0.0'),
  ('PORT_IMAP', '143;tcp;IMAP;1;0.0.0.0'),
  ('PORT_IMAP-SSL', '993;tcp;IMAP-SSL;0;0.0.0.0'),
  ('SHOW_COMPRESSION_SIZE', '1'),
  ('PREVENT_EXTERNAL_LOGIN_ADMIN', '1'),
  ('PREVENT_EXTERNAL_LOGIN_RESELLER', '1'),
  ('PREVENT_EXTERNAL_LOGIN_CLIENT', '1'),
  ('DATABASE_REVISION', '273');

-- --------------------------------------------------------

--
-- Table structure for table `custom_menus`
--

CREATE TABLE IF NOT EXISTS `custom_menus` (
  `menu_id` int(10) unsigned NOT NULL auto_increment,
  `menu_level` varchar(10) collate utf8_unicode_ci DEFAULT NULL,
  `menu_order` int(10) unsigned DEFAULT NULL,
  `menu_name` varchar(255) collate utf8_unicode_ci DEFAULT NULL,
  `menu_link` varchar(200) collate utf8_unicode_ci DEFAULT NULL,
  `menu_target` varchar(200) collate utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY  (`menu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `domain`
--

CREATE TABLE IF NOT EXISTS `domain` (
  `domain_id` int(10) unsigned NOT NULL auto_increment,
  `domain_name` varchar(200) collate utf8_unicode_ci DEFAULT NULL,
  `domain_admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `domain_created` int(10) unsigned NOT NULL DEFAULT '0',
  `domain_expires` int(10) unsigned NOT NULL DEFAULT '0',
  `domain_last_modified` int(10) unsigned NOT NULL DEFAULT '0',
  `domain_mailacc_limit` int(11) DEFAULT NULL,
  `domain_ftpacc_limit` int(11) DEFAULT NULL,
  `domain_traffic_limit` bigint(20) DEFAULT NULL,
  `domain_sqld_limit` int(11) DEFAULT NULL,
  `domain_sqlu_limit` int(11) DEFAULT NULL,
  `domain_status` varchar(255) collate utf8_unicode_ci DEFAULT NULL,
  `domain_alias_limit` int(11) DEFAULT NULL,
  `domain_subd_limit` int(11) DEFAULT NULL,
  `domain_ip_id` int(10) unsigned DEFAULT NULL,
  `domain_disk_limit` bigint(20) unsigned DEFAULT NULL,
  `domain_disk_usage` bigint(20) unsigned DEFAULT NULL,
  `domain_disk_file` bigint(20) unsigned DEFAULT NULL,
  `domain_disk_mail` bigint(20) unsigned DEFAULT NULL,
  `domain_disk_sql` bigint(20) unsigned DEFAULT NULL,
  `domain_php` varchar(15) collate utf8_unicode_ci DEFAULT NULL,
  `domain_cgi` varchar(15) collate utf8_unicode_ci DEFAULT NULL,
  `allowbackup` varchar(12) collate utf8_unicode_ci NOT NULL DEFAULT 'dmn|sql|mail',
  `domain_dns` varchar(15) collate utf8_unicode_ci NOT NULL DEFAULT 'no',
  `domain_software_allowed` varchar(15) collate utf8_unicode_ci NOT NULL DEFAULT 'no',
  `phpini_perm_system` VARCHAR(20) NOT NULL DEFAULT 'no',
  `phpini_perm_allow_url_fopen` VARCHAR(20) NOT NULL DEFAULT 'no',
  `phpini_perm_display_errors` VARCHAR(20) NOT NULL DEFAULT 'no',
  `phpini_perm_disable_functions` VARCHAR(20) NOT NULL DEFAULT 'no',
  `phpini_perm_mail_function` VARCHAR(20) NOT NULL DEFAULT 'yes',
  `domain_external_mail` varchar(15) collate utf8_unicode_ci NOT NULL DEFAULT 'no',
  `external_mail` varchar(15) collate utf8_unicode_ci NOT NULL DEFAULT 'off',
  `web_folder_protection` varchar(5) collate utf8_unicode_ci NOT NULL DEFAULT 'yes',
  `mail_quota` bigint(20) unsigned NOT NULL,
  `document_root` varchar(255) collate utf8_unicode_ci NOT NULL DEFAULT '/htdocs',
  `url_forward` varchar(255) collate utf8_unicode_ci NOT NULL DEFAULT 'no',
  `type_forward` varchar(5) collate utf8_unicode_ci DEFAULT NULL,
  `host_forward` varchar(3) collate utf8_unicode_ci NOT NULL DEFAULT 'Off',
  PRIMARY KEY (`domain_id`),
  UNIQUE KEY `domain_name` (`domain_name`),
  INDEX `i_domain_admin_id` (`domain_admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `domain_aliasses`
--

CREATE TABLE IF NOT EXISTS `domain_aliasses` (
  `alias_id` int(10) unsigned NOT NULL auto_increment,
  `domain_id` int(10) unsigned DEFAULT NULL,
  `alias_name` varchar(200) collate utf8_unicode_ci DEFAULT NULL,
  `alias_status` varchar(255) collate utf8_unicode_ci DEFAULT NULL,
  `alias_mount` varchar(200) collate utf8_unicode_ci DEFAULT NULL,
  `alias_document_root` varchar(255) collate utf8_unicode_ci NOT NULL DEFAULT '/htdocs',
  `alias_ip_id` int(10) unsigned DEFAULT NULL,
  `url_forward` varchar(255) collate utf8_unicode_ci NOT NULL DEFAULT 'no',
  `type_forward` varchar(5) collate utf8_unicode_ci DEFAULT NULL,
  `host_forward` varchar(3) collate utf8_unicode_ci NOT NULL DEFAULT 'Off',
  `external_mail` varchar(15) collate utf8_unicode_ci NOT NULL DEFAULT 'off',
  PRIMARY KEY (`alias_id`),
  INDEX `domain_id` (`domain_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `domain_dns`
--

CREATE TABLE IF NOT EXISTS `domain_dns` (
  `domain_dns_id` int(11) NOT NULL auto_increment,
  `domain_id` int(11) NOT NULL,
  `alias_id` int(11) NOT NULL,
  `domain_dns` text collate utf8_unicode_ci NOT NULL,
  `domain_class` enum('IN','CH','HS') collate utf8_unicode_ci NOT NULL DEFAULT 'IN',
  `domain_type` enum('A','AAAA','CERT','CNAME','DNAME','GPOS','KEY','KX','MX','NAPTR','NSAP','NS','NXT','PTR','PX','SIG','SRV','TXT','SPF') collate utf8_unicode_ci NOT NULL DEFAULT 'A',
  `domain_text` text collate utf8_unicode_ci NOT NULL,
  `owned_by` varchar(255) collate utf8_unicode_ci NOT NULL DEFAULT 'custom_dns_feature',
  `domain_dns_status` text collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`domain_dns_id`),
  UNIQUE KEY `domain_id` (`domain_id`,`alias_id`,`domain_dns`(255),`domain_class`,`domain_type`,`domain_text`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `domain_traffic`
--

CREATE TABLE IF NOT EXISTS `domain_traffic` (
  `domain_id` int(10) unsigned NOT NULL,
  `dtraff_time` bigint(20) unsigned NOT NULL,
  `dtraff_web` bigint(20) unsigned DEFAULT '0',
  `dtraff_ftp` bigint(20) unsigned DEFAULT '0',
  `dtraff_mail` bigint(20) unsigned DEFAULT '0',
  `dtraff_pop` bigint(20) unsigned DEFAULT '0',
  PRIMARY KEY (`domain_id`, `dtraff_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_tpls`
--

CREATE TABLE IF NOT EXISTS `email_tpls` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `owner_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(200) collate utf8_unicode_ci DEFAULT NULL,
  `subject` varchar(200) collate utf8_unicode_ci DEFAULT NULL,
  `message` text collate utf8_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `error_pages`
--

CREATE TABLE IF NOT EXISTS `error_pages` (
  `ep_id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `error_401` text collate utf8_unicode_ci NOT NULL,
  `error_403` text collate utf8_unicode_ci NOT NULL,
  `error_404` text collate utf8_unicode_ci NOT NULL,
  `error_500` text collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`ep_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ftp_group`
--

CREATE TABLE IF NOT EXISTS `ftp_group` (
  `groupname` varchar(255) collate utf8_unicode_ci DEFAULT NULL,
  `gid` int(10) unsigned NOT NULL DEFAULT '0',
  `members` text collate utf8_unicode_ci,
  UNIQUE KEY `groupname` (`groupname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ftp_user`
--

CREATE TABLE IF NOT EXISTS `ftp_users` (
  `userid` varchar(255) collate utf8_unicode_ci DEFAULT NULL,
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `passwd` varchar(255) collate utf8_unicode_ci DEFAULT NULL,
  `uid` int(10) unsigned NOT NULL DEFAULT '0',
  `gid` int(10) unsigned NOT NULL DEFAULT '0',
  `shell` varchar(255) collate utf8_unicode_ci DEFAULT NULL,
  `homedir` varchar(255) collate utf8_unicode_ci DEFAULT NULL,
  `status` varchar(255) collate utf8_unicode_ci NOT NULL DEFAULT 'ok',
  UNIQUE KEY (`userid`),
  INDEX `admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hosting_plans`
--

CREATE TABLE IF NOT EXISTS `hosting_plans` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `reseller_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) collate utf8_unicode_ci DEFAULT NULL,
  `props` text collate utf8_unicode_ci,
  `description` text collate utf8_unicode_ci,
  `status` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `htaccess`
--

CREATE TABLE IF NOT EXISTS `htaccess` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `dmn_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` varchar(255) collate utf8_unicode_ci DEFAULT NULL,
  `group_id` varchar(255) collate utf8_unicode_ci DEFAULT NULL,
  `auth_type` varchar(255) collate utf8_unicode_ci DEFAULT NULL,
  `auth_name` varchar(255) collate utf8_unicode_ci DEFAULT NULL,
  `path` varchar(255) collate utf8_unicode_ci DEFAULT NULL,
  `status` varchar(255) collate utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `htaccess_groups`
--

CREATE TABLE IF NOT EXISTS `htaccess_groups` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `dmn_id` int(10) unsigned NOT NULL DEFAULT '0',
  `ugroup` varchar(255) collate utf8_unicode_ci DEFAULT NULL,
  `members` text collate utf8_unicode_ci,
  `status` varchar(255) collate utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `htaccess_users`
--

CREATE TABLE IF NOT EXISTS `htaccess_users` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `dmn_id` int(10) unsigned NOT NULL DEFAULT '0',
  `uname` varchar(255) collate utf8_unicode_ci DEFAULT NULL,
  `upass` varchar(255) collate utf8_unicode_ci DEFAULT NULL,
  `status` varchar(255) collate utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log`
--

CREATE TABLE IF NOT EXISTS `log` (
  `log_id` int(10) unsigned NOT NULL auto_increment,
  `log_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `log_message` text collate utf8_unicode_ci,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

CREATE TABLE IF NOT EXISTS `login` (
  `session_id` varchar(200) collate utf8_unicode_ci NOT NULL DEFAULT '',
  `ipaddr` varchar(40) collate utf8_unicode_ci DEFAULT NULL,
  `lastaccess` int(10) unsigned DEFAULT NULL,
  `login_count` tinyint(1) DEFAULT '0',
  `captcha_count` tinyint(1) DEFAULT '0',
  `user_name` varchar(255) collate utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mail_users`
--

CREATE TABLE IF NOT EXISTS `mail_users` (
  `mail_id` int(10) unsigned NOT NULL auto_increment,
  `mail_acc` text collate utf8_unicode_ci DEFAULT NULL,
  `mail_pass` varchar(255) collate utf8_unicode_ci NOT NULL DEFAULT '_no_',
  `mail_forward` text collate utf8_unicode_ci,
  `domain_id` int(10) unsigned DEFAULT NULL,
  `mail_type` varchar(30) collate utf8_unicode_ci DEFAULT NULL,
  `sub_id` int(10) unsigned DEFAULT NULL,
  `status` varchar(255) collate utf8_unicode_ci DEFAULT NULL,
  `po_active` varchar(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'yes',
  `mail_auto_respond` tinyint(1) NOT NULL DEFAULT '0',
  `mail_auto_respond_text` text collate utf8_unicode_ci,
  `quota` bigint(20) unsigned DEFAULT NULL,
  `mail_addr` varchar(254) collate utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`mail_id`),
  INDEX `domain_id` (`domain_id`),
  INDEX `sub_id` (`sub_id`),
  UNIQUE KEY `mail_addr` (`mail_addr`),
  INDEX `status` (`status`),
  INDEX `po_active` (`po_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `php_ini`
--

CREATE TABLE IF NOT EXISTS `php_ini` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(10) NOT NULL,
  `domain_id` int(10) NOT NULL,
  `domain_type` varchar(15) NOT NULL DEFAULT 'dmn',
  `disable_functions` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'show_source, system, shell_exec, passthru, exec, phpinfo, shell, symlink, popen, proc_open',
  `allow_url_fopen` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'off',
  `display_errors` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'off',
  `error_reporting` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'E_ALL & ~E_DEPRECATED & ~E_STRICT',
  `post_max_size` int(11) NOT NULL DEFAULT '10',
  `upload_max_filesize` int(11) NOT NULL DEFAULT '10',
  `max_execution_time` int(11) NOT NULL DEFAULT '30',
  `max_input_time` int(11) NOT NULL DEFAULT '60',
  `memory_limit` int(11) NOT NULL DEFAULT '128',
  PRIMARY KEY (`id`),
  UNIQUE `unique_php_ini` (`admin_id`,`domain_id`,`domain_type`)
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
  `plugin_config_prev` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `plugin_priority` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `plugin_status` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `plugin_error` text COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `plugin_backend` varchar(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  `plugin_lockers` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`plugin_id`),
  UNIQUE KEY `name` (`plugin_name`),
  INDEX `plugin_priority` (`plugin_priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quotalimits`
--

CREATE TABLE IF NOT EXISTS `quotalimits` (
  `name` varchar(255) collate utf8_unicode_ci NOT NULL DEFAULT '',
  `quota_type` enum('user','group','class','all') collate utf8_unicode_ci NOT NULL DEFAULT 'user',
  `per_session` enum('false','true') collate utf8_unicode_ci NOT NULL DEFAULT 'false',
  `limit_type` enum('soft','hard') collate utf8_unicode_ci NOT NULL DEFAULT 'soft',
  `bytes_in_avail` float NOT NULL DEFAULT '0',
  `bytes_out_avail` float NOT NULL DEFAULT '0',
  `bytes_xfer_avail` float NOT NULL DEFAULT '0',
  `files_in_avail` int(10) unsigned NOT NULL DEFAULT '0',
  `files_out_avail` int(10) unsigned NOT NULL DEFAULT '0',
  `files_xfer_avail` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quotatallies`
--

CREATE TABLE IF NOT EXISTS `quotatallies` (
  `name` varchar(255) collate utf8_unicode_ci NOT NULL DEFAULT '',
  `quota_type` enum('user','group','class','all') collate utf8_unicode_ci NOT NULL DEFAULT 'user',
  `bytes_in_used` float NOT NULL DEFAULT '0',
  `bytes_out_used` float NOT NULL DEFAULT '0',
  `bytes_xfer_used` float NOT NULL DEFAULT '0',
  `files_in_used` int(10) unsigned NOT NULL DEFAULT '0',
  `files_out_used` int(10) unsigned NOT NULL DEFAULT '0',
  `files_xfer_used` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reseller_props`
--

CREATE TABLE IF NOT EXISTS `reseller_props` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `reseller_id` int(10) unsigned NOT NULL DEFAULT '0',
  `current_dmn_cnt` int(11) DEFAULT NULL,
  `max_dmn_cnt` int(11) DEFAULT NULL,
  `current_sub_cnt` int(11) DEFAULT NULL,
  `max_sub_cnt` int(11) DEFAULT NULL,
  `current_als_cnt` int(11) DEFAULT NULL,
  `max_als_cnt` int(11) DEFAULT NULL,
  `current_mail_cnt` int(11) DEFAULT NULL,
  `max_mail_cnt` int(11) DEFAULT NULL,
  `current_ftp_cnt` int(11) DEFAULT NULL,
  `max_ftp_cnt` int(11) DEFAULT NULL,
  `current_sql_db_cnt` int(11) DEFAULT NULL,
  `max_sql_db_cnt` int(11) DEFAULT NULL,
  `current_sql_user_cnt` int(11) DEFAULT NULL,
  `max_sql_user_cnt` int(11) DEFAULT NULL,
  `current_disk_amnt` int(11) DEFAULT NULL,
  `max_disk_amnt` int(11) DEFAULT NULL,
  `current_traff_amnt` int(11) DEFAULT NULL,
  `max_traff_amnt` int(11) DEFAULT NULL,
  `support_system` ENUM( 'yes', 'no' ) NOT NULL DEFAULT 'yes',
  `reseller_ips` text collate utf8_unicode_ci,
  `software_allowed` varchar(15) collate utf8_general_ci NOT NULL DEFAULT 'no',
  `softwaredepot_allowed` varchar(15) collate utf8_general_ci NOT NULL DEFAULT 'yes',
  `websoftwaredepot_allowed` varchar(15) collate utf8_general_ci NOT NULL DEFAULT 'yes',
  `php_ini_system` VARCHAR(15) NOT NULL DEFAULT 'no',
  `php_ini_al_disable_functions` VARCHAR(15) NOT NULL DEFAULT 'no',
  `php_ini_al_mail_function` VARCHAR(15) NOT NULL DEFAULT 'yes',
  `php_ini_al_allow_url_fopen` VARCHAR(15) NOT NULL DEFAULT 'no',
  `php_ini_al_display_errors` VARCHAR(15) NOT NULL DEFAULT 'no',
  `php_ini_max_post_max_size` int(11) NOT NULL DEFAULT '0',
  `php_ini_max_upload_max_filesize` int(11) NOT NULL DEFAULT '0',
  `php_ini_max_max_execution_time` int(11) NOT NULL DEFAULT '0',
  `php_ini_max_max_input_time` int(11) NOT NULL DEFAULT '0',
  `php_ini_max_memory_limit` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  INDEX `reseller_id` (`reseller_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `server_ips`
--

CREATE TABLE IF NOT EXISTS `server_ips` (
  `ip_id` int(10) unsigned NOT NULL auto_increment,
  `ip_number` varchar(45) collate utf8_unicode_ci DEFAULT NULL,
  `ip_netmask` tinyint(1) unsigned DEFAULT NULL,
  `ip_card` varchar(255) collate utf8_unicode_ci DEFAULT NULL,
  `ip_config_mode` varchar(15) collate utf8_unicode_ci DEFAULT 'auto',
  `ip_status` varchar(255) collate utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`ip_id`),
  UNIQUE KEY `ip_number` (`ip_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `server_traffic`
--

CREATE TABLE IF NOT EXISTS `server_traffic` (
  `traff_time` int(10) unsigned DEFAULT NULL,
  `bytes_in` bigint(20) unsigned DEFAULT NULL,
  `bytes_out` bigint(20) unsigned DEFAULT NULL,
  `bytes_mail_in` bigint(20) unsigned DEFAULT NULL,
  `bytes_mail_out` bigint(20) unsigned DEFAULT NULL,
  `bytes_pop_in` bigint(20) unsigned DEFAULT NULL,
  `bytes_pop_out` bigint(20) unsigned DEFAULT NULL,
  `bytes_web_in` bigint(20) unsigned DEFAULT NULL,
  `bytes_web_out` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`traff_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sql_database`
--

CREATE TABLE IF NOT EXISTS `sql_database` (
  `sqld_id` int(10) unsigned NOT NULL auto_increment,
  `domain_id` int(10) unsigned NOT NULL,
  `sqld_name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`sqld_id`),
  INDEX `domain_id` (`domain_id`),
  UNIQUE KEY `sqld_name` (`sqld_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sql_user`
--

CREATE TABLE IF NOT EXISTS `sql_user` (
  `sqlu_id` int(10) unsigned NOT NULL auto_increment,
  `sqld_id` int(10) unsigned NOT NULL,
  `sqlu_name` varchar(16) collate utf8_unicode_ci NOT NULL,
  `sqlu_host` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`sqlu_id`),
  INDEX `sqld_id` (`sqld_id`),
  INDEX `sqlu_name` (`sqlu_name`),
  INDEX `sqlu_host` (`sqlu_host`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ssl_certs`
--

CREATE TABLE IF NOT EXISTS `ssl_certs` (
  `cert_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `domain_id` int(10) NOT NULL,
  `domain_type` enum('dmn','als','sub','alssub') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'dmn',
  `private_key` text COLLATE utf8_unicode_ci NOT NULL,
  `certificate` text COLLATE utf8_unicode_ci NOT NULL,
  `ca_bundle` text COLLATE utf8_unicode_ci,
  `allow_hsts` VARCHAR(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'off',
  `hsts_max_age` int(11) NOT NULL DEFAULT '31536000',
  `hsts_include_subdomains` VARCHAR(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'off',
  `status` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`cert_id`),
  UNIQUE KEY `domain_id_domain_type` (`domain_id`, `domain_type`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subdomain`
--

CREATE TABLE IF NOT EXISTS `subdomain` (
  `subdomain_id` int(10) unsigned NOT NULL auto_increment,
  `domain_id` int(10) unsigned DEFAULT NULL,
  `subdomain_name` varchar(200) collate utf8_unicode_ci DEFAULT NULL,
  `subdomain_mount` varchar(200) collate utf8_unicode_ci DEFAULT NULL,
  `subdomain_document_root` varchar(255) collate utf8_unicode_ci NOT NULL DEFAULT '/htdocs',
  `subdomain_url_forward` varchar(255) collate utf8_unicode_ci NOT NULL DEFAULT 'no',
  `subdomain_type_forward` varchar(5) collate utf8_unicode_ci DEFAULT NULL,
  `subdomain_host_forward` varchar(3) collate utf8_unicode_ci NOT NULL DEFAULT 'Off',
  `subdomain_status` varchar(255) collate utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`subdomain_id`),
  INDEX `domain_id` (`domain_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subdomain_alias`
--

CREATE TABLE IF NOT EXISTS `subdomain_alias` (
  `subdomain_alias_id` int(10) unsigned NOT NULL auto_increment,
  `alias_id` int(10) unsigned DEFAULT NULL,
  `subdomain_alias_name` varchar(200) collate utf8_unicode_ci DEFAULT NULL,
  `subdomain_alias_mount` varchar(200) collate utf8_unicode_ci DEFAULT NULL,
  `subdomain_alias_document_root` varchar(255) collate utf8_unicode_ci NOT NULL DEFAULT '/htdocs',
  `subdomain_alias_url_forward` varchar(255) collate utf8_unicode_ci NOT NULL DEFAULT 'no',
  `subdomain_alias_type_forward` varchar(5) collate utf8_unicode_ci DEFAULT NULL,
  `subdomain_alias_host_forward` varchar(3) collate utf8_unicode_ci NOT NULL DEFAULT 'Off',
  `subdomain_alias_status` varchar(255) collate utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`subdomain_alias_id`),
  INDEX `alias_id` (`alias_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE IF NOT EXISTS `tickets` (
  `ticket_id` int(10) unsigned NOT NULL auto_increment,
  `ticket_level` int(10) DEFAULT NULL,
  `ticket_from` int(10) unsigned DEFAULT NULL,
  `ticket_to` int(10) unsigned DEFAULT NULL,
  `ticket_status` int(10) unsigned DEFAULT NULL,
  `ticket_reply` int(10) unsigned DEFAULT NULL,
  `ticket_urgency` int(10) unsigned DEFAULT NULL,
  `ticket_date` int(10) unsigned DEFAULT NULL,
  `ticket_subject` varchar(255) collate utf8_unicode_ci DEFAULT NULL,
  `ticket_message` text collate utf8_unicode_ci,
  PRIMARY KEY  (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_gui_props`
--

CREATE TABLE IF NOT EXISTS `user_gui_props` (
  `user_id` int(10) unsigned NOT NULL,
  `lang` varchar(15) collate utf8_unicode_ci DEFAULT 'browser',
  `layout` varchar(100) collate utf8_unicode_ci NOT NULL DEFAULT 'default',
  `layout_color` varchar(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'black',
  `logo` varchar(255) collate utf8_unicode_ci NOT NULL DEFAULT '',
  `show_main_menu_labels` tinyint(1) NOT NULL DEFAULT '0',
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `web_software`
--

CREATE TABLE IF NOT EXISTS `web_software` (
  `software_id` int(10) unsigned NOT NULL auto_increment,
  `software_master_id` int(10) unsigned NOT NULL DEFAULT '0',
  `reseller_id` int(10) unsigned NOT NULL DEFAULT '0',
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
  `rights_add_by` int(10) unsigned NOT NULL DEFAULT '0',
  `software_depot` varchar(15) character set utf8 collate utf8_unicode_ci NOT NULL NOT NULL DEFAULT 'no',
  PRIMARY KEY (`software_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `web_software_inst`
--

CREATE TABLE IF NOT EXISTS `web_software_inst` (
  `domain_id` int(10) unsigned NOT NULL,
  `alias_id` int(10) unsigned NOT NULL DEFAULT '0',
  `subdomain_id` int(10) unsigned NOT NULL DEFAULT '0',
  `subdomain_alias_id` int(10) unsigned NOT NULL DEFAULT '0',
  `software_id` int(10) NOT NULL,
  `software_master_id` int(10) unsigned NOT NULL DEFAULT '0',
  `software_res_del` int(1) NOT NULL DEFAULT '0',
  `software_name` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL,
  `software_version` varchar(20) character set utf8 collate utf8_unicode_ci NOT NULL,
  `software_language` varchar(15) character set utf8 collate utf8_unicode_ci NOT NULL,
  `path` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL DEFAULT '0',
  `software_prefix` varchar(50) character set utf8 collate utf8_unicode_ci NOT NULL DEFAULT '0',
  `db` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL DEFAULT '0',
  `database_user` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL DEFAULT '0',
  `database_tmp_pwd` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL DEFAULT '0',
  `install_username` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL DEFAULT '0',
  `install_password` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL DEFAULT '0',
  `install_email` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL DEFAULT '0',
  `software_status` varchar(15) character set utf8 collate utf8_unicode_ci NOT NULL,
  `software_depot` varchar(15) character set utf8 collate utf8_unicode_ci NOT NULL NOT NULL DEFAULT 'no',
  INDEX `software_id` (`software_id`)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

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
