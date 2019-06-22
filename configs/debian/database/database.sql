--
-- Table structure for table `admin`
--

create table if not exists `admin` (
  `admin_id` int(10) unsigned not null auto_increment,
  `admin_name` varchar(255) not null,
  `admin_pass` varchar(200) not null,
  `admin_type` varchar(10) not null,
  `admin_sys_name` varchar(16) default null,
  `admin_sys_uid` int(10) unsigned not null default '0',
  `admin_sys_gname` varchar(32) default null,
  `admin_sys_gid`int(10) unsigned not null default '0',
  `domain_created` int(10) unsigned not null default '0',
  `customer_id` varchar(200) default '0',
  `created_by` int(10) unsigned default '0',
  `fname` varchar(200) default null,
  `lname` varchar(200) default null,
  `gender` varchar(1) default null,
  `firm` varchar(200) default null,
  `zip` varchar(10) default null,
  `city` varchar(200) default null,
  `state` varchar(200) default null,
  `country` varchar(200) default null,
  `email` varchar(200) default null,
  `phone` varchar(200) default null,
  `fax` varchar(200) default null,
  `street1` varchar(200) default null,
  `street2` varchar(200) default null,
  `uniqkey` varchar(255) default null,
  `uniqkey_time` timestamp null default null,
  `admin_status` varchar(255) not null default 'ok',
  unique key `admin_id` (`admin_id`),
  unique key `admin_name` (`admin_name`(191)),
  index `created_by` (`created_by`),
  index `admin_status` (`admin_status`(15))
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `autoreplies_log`
--

create table if not exists `autoreplies_log` (
  `time` datetime not null comment 'Date and time of the sent autoreply',
  `from` varchar(255) not null comment 'autoreply message sender',
  `to` varchar(255) not null comment 'autoreply message recipient',
  index ( `time` ),
  index `from` (`from`(191)),
  index `to` (`to`(191))
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci comment = 'Sent autoreplies log table';

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

create table if not exists `config` (
  `name` varchar(191) not null,
  `value` longtext not null,
  primary key (`name`)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

--
-- Dumping data for table `config`
--

insert ignore into `config` (`name`, `value`) values
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
  ('DATABASE_REVISION', '278');

-- --------------------------------------------------------

--
-- Table structure for table `custom_menus`
--

create table if not exists `custom_menus` (
  `menu_id` int(10) unsigned not null auto_increment,
  `menu_level` varchar(10) not null,
  `menu_order` int(10) unsigned not null default '0',
  `menu_name` varchar(255) not null,
  `menu_link` varchar(200) not null,
  `menu_target` varchar(200) not null default '',
  primary key (`menu_id`),
  index `menu_level` (`menu_level`)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `domain`
--

create table if not exists `domain` (
  `domain_id` int(10) unsigned not null auto_increment,
  `domain_name` varchar(255) not null,
  `domain_admin_id` int(10) unsigned not null,
  `domain_created` int(10) unsigned not null,
  `domain_expires` int(10) unsigned not null default '0',
  `domain_last_modified` int(10) unsigned not null default '0',
  `domain_mailacc_limit` int(11) not null default '0',
  `domain_ftpacc_limit` int(11) not null default '0',
  `domain_traffic_limit` bigint(20) not null default '0',
  `domain_sqld_limit` int(11) not null default '0',
  `domain_sqlu_limit` int(11) not null default '0',
  `domain_status` varchar(255) not null,
  `domain_alias_limit` int(11) not null default '0',
  `domain_subd_limit` int(11) not null default '0',
  `domain_ip_id` int(10) unsigned not null,
  `domain_disk_limit` bigint(20) unsigned not null default '0',
  `domain_disk_usage` bigint(20) unsigned not null default '0',
  `domain_disk_file` bigint(20) unsigned not null default '0',
  `domain_disk_mail` bigint(20) unsigned not null default '0',
  `domain_disk_sql` bigint(20) unsigned not null default '0',
  `domain_php` varchar(15) not null,
  `domain_cgi` varchar(15) not null,
  `allowbackup` varchar(12) not null default 'dmn|sql|mail',
  `domain_dns` varchar(15) not null default 'no',
  `domain_software_allowed` varchar(15) not null default 'no',
  `phpini_perm_system` varchar(20) not null default 'no',
  `phpini_perm_allow_url_fopen` varchar(20) not null default 'no',
  `phpini_perm_display_errors` varchar(20) not null default 'no',
  `phpini_perm_disable_functions` varchar(20) not null default 'no',
  `phpini_perm_mail_function` varchar(20) not null default 'yes',
  `domain_external_mail` varchar(15) not null default 'no',
  `external_mail` varchar(15) not null default 'off',
  `web_folder_protection` varchar(5) not null default 'yes',
  `mail_quota` bigint(20) unsigned not null default 0,
  `document_root` varchar(255) not null default '/htdocs',
  `url_forward` varchar(255) not null default 'no',
  `type_forward` varchar(5) default null,
  `host_forward` varchar(3) not null default 'Off',
  `wildcard_alias` enum('yes', 'no') not null default 'no',
  primary key (`domain_id`),
  unique key `domain_name` (`domain_name`(191)),
  index `i_domain_admin_id` (`domain_admin_id`)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `domain_aliasses`
--

create table if not exists `domain_aliasses` (
  `alias_id` int(10) unsigned not null auto_increment,
  `domain_id` int(10) unsigned not null,
  `alias_name` varchar(255) not null,
  `alias_status` varchar(255) not null,
  `alias_mount` varchar(200) not null,
  `alias_document_root` varchar(255) not null default '/htdocs',
  `alias_ip_id` int(10) unsigned not null,
  `url_forward` varchar(255) not null default 'no',
  `type_forward` varchar(5) default null,
  `host_forward` varchar(3) not null default 'Off',
  `wildcard_alias` enum('yes', 'no') not null default 'no',
  `external_mail` varchar(15) not null default 'off',
  primary key (`alias_id`),
  index `domain_id` (`domain_id`),
  index `alias_name` (`alias_name`(191)),
  index `alias_status` (`alias_status`(15))
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `domain_dns`
--

create table if not exists `domain_dns` (
  `domain_dns_id` int(11) not null auto_increment,
  `domain_id` int(11) not null,
  `alias_id` int(11) not null,
  `domain_dns` text not null,
  `domain_class` enum('IN','CH','HS') not null default 'IN',
  `domain_type` enum(
    'A','AAAA','CERT','CNAME','DNAME','GPOS','KEY','KX','MX','NAPTR','NSAP',
    'NS','NXT','PTR','PX','SIG','SRV','TXT','SPF'
  ) not null default 'A',
  `domain_text` text not null,
  `owned_by` varchar(255) not null default 'custom_dns_feature',
  `domain_dns_status` text not null,
  primary key (`domain_dns_id`),
  unique key `domain_id` (
    `domain_id`,`alias_id`,`domain_dns`(191),`domain_class`,`domain_type`,
    `domain_text`(191)
  ),
  index `domain_dns_status` (`domain_dns_status`(15))
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `domain_traffic`
--

create table if not exists `domain_traffic` (
  `domain_id` int(10) unsigned not null,
  `dtraff_time` bigint(20) unsigned not null,
  `dtraff_web` bigint(20) unsigned default '0',
  `dtraff_ftp` bigint(20) unsigned default '0',
  `dtraff_mail` bigint(20) unsigned default '0',
  `dtraff_pop` bigint(20) unsigned default '0',
  primary key (`domain_id`, `dtraff_time`)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_tpls`
--

create table if not exists `email_tpls` (
  `id` int(10) unsigned not null auto_increment,
  `owner_id` int(10) unsigned not null default '0',
  `name` varchar(200) default null,
  `subject` varchar(200) default null,
  `message` text,
  primary key (`id`),
  index `owner_id` (`owner_id`)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `error_pages`
--

create table if not exists `error_pages` (
  `ep_id` int(10) unsigned not null auto_increment,
  `user_id` int(10) unsigned not null default '0',
  `error_401` text not null,
  `error_403` text not null,
  `error_404` text not null,
  `error_500` text not null,
  primary key (`ep_id`),
  index `user_id` (`user_id`)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ftp_group`
--

create table if not exists `ftp_group` (
  `groupname` varchar(255) not null,
  `gid` int(10) unsigned not null default '0',
  `members` text ,
  unique key `groupname` (`groupname`(191))
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ftp_user`
--

create table if not exists `ftp_users` (
  `userid` varchar(255) not null,
  `admin_id` int(10) unsigned not null default '0',
  `passwd` varchar(255) not null,
  `uid` int(10) unsigned not null default '0',
  `gid` int(10) unsigned not null default '0',
  `shell` varchar(255) not null,
  `homedir` varchar(255) not null,
  `status` varchar(255) not null default 'ok',
  unique key (`userid`(191)),
  index `admin_id` (`admin_id`),
  index `status` (`status`(15))
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hosting_plans`
--

create table if not exists `hosting_plans` (
  `id` int(10) unsigned not null auto_increment,
  `reseller_id` int(10) unsigned not null default '0',
  `name` varchar(255) not null,
  `props` text ,
  `description` text ,
  `status` int(10) unsigned not null default '0',
  primary key (`id`),
  index `reseller_id` (`reseller_id`),
  index `status` (`status`)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `htaccess`
--

create table if not exists `htaccess` (
  `id` int(10) unsigned not null auto_increment,
  `dmn_id` int(10) unsigned not null,
  `user_id` varchar(255) not null,
  `group_id` varchar(255) default null,
  `auth_type` varchar(255) not null,
  `auth_name` varchar(255) not null,
  `path` varchar(255) not null,
  `status` varchar(255) not null,
  primary key (`id`),
  index `dmn_id` (`dmn_id`),
  index `status` (`status`(15))
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `htaccess_groups`
--

create table if not exists `htaccess_groups` (
  `id` int(10) unsigned not null auto_increment,
  `dmn_id` int(10) unsigned not null,
  `ugroup` varchar(255) default null,
  `members` text ,
  `status` varchar(255) not null,
  primary key (`id`),
  index `dmn_id` (`dmn_id`),
  index `status` (`status`(15))
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `htaccess_users`
--

create table if not exists `htaccess_users` (
  `id` int(10) unsigned not null auto_increment,
  `dmn_id` int(10) unsigned not null,
  `uname` varchar(255) not null,
  `upass` varchar(255) not null,
  `status` varchar(255) not null,
  primary key (`id`),
  index `dmn_id` (`dmn_id`),
  index `status` (`status`(15))
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log`
--

create table if not exists `log` (
  `log_id` int(10) unsigned not null auto_increment,
  `log_time` timestamp not null default current_timestamp   
    on update current_timestamp,
  `log_message` text ,
  primary key (`log_id`),
  index `log_time` (`log_time`)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

create table if not exists `login` (
  `session_id` varchar(191) not null,
  `ipaddr` varchar(40) not null,
  `lastaccess` int(10) unsigned not null,
  `login_count` tinyint(1) default '0',
  `captcha_count` tinyint(1) default '0',
  `user_name` varchar(255) not null,
  primary key (`session_id`),
  index `lastaccess` (`lastaccess`),
  index `user_name` (`user_name`(191))
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mail_users`
--

create table if not exists `mail_users` (
  `mail_id` int(10) unsigned not null auto_increment,
  `mail_acc` text not null,
  `mail_pass` varchar(255) not null default '_no_',
  `mail_forward` text ,
  `domain_id` int(10) unsigned not null,
  `mail_type` varchar(30) not null,
  `sub_id` int(10) unsigned default null,
  `status` varchar(255) default null,
  `po_active` varchar(3) not null default 'yes',
  `mail_auto_respond` tinyint(1) not null default '0',
  `mail_auto_respond_text` text ,
  `quota` bigint(20) unsigned not null default '0',
  `mail_addr` varchar(255) not null,
  primary key (`mail_id`),
  index `domain_id` (`domain_id`),
  index `sub_id` (`sub_id`),
  unique key `mail_addr` (`mail_addr`(191)),
  index `status` (`status`(15)),
  index `po_active` (`po_active`)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `php_ini`
--

create table if not exists `php_ini` (
  `id` int(11) not null auto_increment,
  `admin_id` int(10) not null,
  `domain_id` int(10) not null,
  `domain_type` varchar(15) not null default 'dmn',
  `disable_functions` varchar(255) not null default
    'show_source, system, shell_exec, passthru, exec, phpinfo, shell, symlink, popen, proc_open',
  `allow_url_fopen` varchar(10) not null default 'off',
  `display_errors` varchar(10) not null default 'off',
  `error_reporting` varchar(255) not null default 'E_ALL & ~E_DEPRECATED & ~E_STRICT',
  `post_max_size` int(11) not null default '10',
  `upload_max_filesize` int(11) not null default '10',
  `max_execution_time` int(11) not null default '30',
  `max_input_time` int(11) not null default '60',
  `memory_limit` int(11) not null default '128',
  primary key (`id`),
  UNIQUE `unique_php_ini` (`admin_id`,`domain_id`,`domain_type`)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plugin`
--

create table if not exists `plugin` (
  `plugin_id` int(11) UNSIGNED not null auto_increment,
  `plugin_name` varchar(50) not null,
  `plugin_type` varchar(20) not null,
  `plugin_info` text not null,
  `plugin_config` text default null,
  `plugin_config_prev` text default null,
  `plugin_priority` int(11) UNSIGNED not null default '0',
  `plugin_status` varchar(255) not null,
  `plugin_error` text null default null,
  `plugin_backend` varchar(3) not null default 'no',
  `plugin_lockers` text default null,
  primary key (`plugin_id`),
  unique key `name` (`plugin_name`),
  index `plugin_priority` (`plugin_priority`),
  index `plugin_status` (`plugin_status`(15)),
  index `plugin_error` (`plugin_error`(15))
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quotalimits`
--

create table if not exists `quotalimits` (
  `name` varchar(191) not null,
  `quota_type` enum('user','group','class','all') not null default 'user',
  `per_session` enum('false','true') not null default 'false',
  `limit_type` enum('soft','hard') not null default 'soft',
  `bytes_in_avail` float not null default '0',
  `bytes_out_avail` float not null default '0',
  `bytes_xfer_avail` float not null default '0',
  `files_in_avail` int(10) unsigned not null default '0',
  `files_out_avail` int(10) unsigned not null default '0',
  `files_xfer_avail` int(10) unsigned not null default '0',
  primary key (`name`)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quotatallies`
--

create table if not exists `quotatallies` (
  `name` varchar(191) not null,
  `quota_type` enum('user','group','class','all') not null default 'user',
  `bytes_in_used` float not null default '0',
  `bytes_out_used` float not null default '0',
  `bytes_xfer_used` float not null default '0',
  `files_in_used` int(10) unsigned not null default '0',
  `files_out_used` int(10) unsigned not null default '0',
  `files_xfer_used` int(10) unsigned not null default '0',
  primary key (`name`)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reseller_props`
--

create table if not exists `reseller_props` (
  `id` int(10) unsigned not null auto_increment,
  `reseller_id` int(10) unsigned not null,
  `current_dmn_cnt` int(11) not null default '0',
  `max_dmn_cnt` int(11) not null default '0',
  `current_sub_cnt` int(11) not null default '0',
  `max_sub_cnt` int(11) not null default '0',
  `current_als_cnt` int(11) not null default '0',
  `max_als_cnt` int(11) not null default '0',
  `current_mail_cnt` int(11) not null default '0',
  `max_mail_cnt` int(11) not null default '0',
  `current_ftp_cnt` int(11) not null default '0',
  `max_ftp_cnt` int(11) not null default '0',
  `current_sql_db_cnt` int(11) not null default '0',
  `max_sql_db_cnt` int(11) not null default '0',
  `current_sql_user_cnt` int(11) not null default '0',
  `max_sql_user_cnt` int(11) not null default '0',
  `current_disk_amnt` int(11) not null default '0',
  `max_disk_amnt` int(11) not null default '0',
  `current_traff_amnt` int(11) not null default '0',
  `max_traff_amnt` int(11) not null default '0',
  `support_system` ENUM( 'yes', 'no' ) not null default 'yes',
  `reseller_ips` text ,
  `software_allowed` varchar(15) not null default 'no',
  `softwaredepot_allowed` varchar(15) not null default 'yes',
  `websoftwaredepot_allowed` varchar(15) not null default 'yes',
  `php_ini_system` varchar(15) not null default 'no',
  `php_ini_al_disable_functions` varchar(15) not null default 'no',
  `php_ini_al_mail_function` varchar(15) not null default 'yes',
  `php_ini_al_allow_url_fopen` varchar(15) not null default 'no',
  `php_ini_al_display_errors` varchar(15) not null default 'no',
  `php_ini_max_post_max_size` int(11) not null default '0',
  `php_ini_max_upload_max_filesize` int(11) not null default '0',
  `php_ini_max_max_execution_time` int(11) not null default '0',
  `php_ini_max_max_input_time` int(11) not null default '0',
  `php_ini_max_memory_limit` int(11) not null default '0',
  primary key (`id`),
  index `reseller_id` (`reseller_id`)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `server_ips`
--

create table if not exists `server_ips` (
  `ip_id` int(10) unsigned not null auto_increment,
  `ip_number` varchar(45) not null,
  `ip_netmask` tinyint(1) unsigned not null,
  `ip_card` varchar(255) not null,
  `ip_config_mode` varchar(15) default 'auto',
  `ip_status` varchar(255) not null,
  primary key (`ip_id`),
  unique key `ip_number` (`ip_number`),
  index `ip_status` (`ip_status`(15))
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `server_traffic`
--

create table if not exists `server_traffic` (
  `traff_time` int(10) unsigned not null,
  `bytes_in` bigint(20) unsigned not null default '0',
  `bytes_out` bigint(20) unsigned not null default '0',
  `bytes_mail_in` bigint(20) unsigned not null default '0',
  `bytes_mail_out` bigint(20) unsigned not null default '0',
  `bytes_pop_in` bigint(20) unsigned not null default '0',
  `bytes_pop_out` bigint(20) unsigned not null default '0',
  `bytes_web_in` bigint(20) unsigned not null default '0',
  `bytes_web_out` bigint(20) unsigned not null default '0',
  primary key (`traff_time`)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sql_database`
--

create table if not exists `sql_database` (
  `sqld_id` int(10) unsigned not null auto_increment,
  `domain_id` int(10) unsigned not null,
  `sqld_name` varchar(64) not null,
  primary key (`sqld_id`),
  index `domain_id` (`domain_id`),
  unique key `sqld_name` (`sqld_name`)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sql_user`
--

create table if not exists `sql_user` (
  `sqlu_id` int(10) unsigned not null auto_increment,
  `sqld_id` int(10) unsigned not null,
  `sqlu_name` varchar(16) not null,
  `sqlu_host` varchar(255) not null,
  primary key (`sqlu_id`),
  index `sqld_id` (`sqld_id`),
  index `sqlu_name` (`sqlu_name`),
  index `sqlu_host` (`sqlu_host`(191))
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ssl_certs`
--

create table if not exists `ssl_certs` (
  `cert_id` int(10) unsigned not null auto_increment,
  `domain_id` int(10) not null,
  `domain_type` enum('dmn','als','sub','alssub') not null default 'dmn',
  `private_key` text not null,
  `certificate` text not null,
  `ca_bundle` text ,
  `allow_hsts` varchar(10) not null default 'off',
  `hsts_max_age` int(11) not null default '31536000',
  `hsts_include_subdomains` varchar(10) not null default 'off',
  `status` varchar(255) not null,
  primary key (`cert_id`),
  unique key `domain_id_domain_type` (`domain_id`, `domain_type`),
  index `status` (`status`(15))
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subdomain`
--

create table if not exists `subdomain` (
  `subdomain_id` int(10) unsigned not null auto_increment,
  `domain_id` int(10) unsigned not null,
  `subdomain_name` varchar(200) not null,
  `subdomain_mount` varchar(200) not null,
  `subdomain_document_root` varchar(255) not null default '/htdocs',
  `subdomain_url_forward` varchar(255) not null default 'no',
  `subdomain_type_forward` varchar(5) default null,
  `subdomain_host_forward` varchar(3) not null default 'Off',
  `subdomain_wildcard_alias` enum('yes', 'no') not null default 'no',
  `subdomain_status` varchar(255) not null,
  primary key (`subdomain_id`),
  index `domain_id` (`domain_id`),
  index `subdomain_status` (`subdomain_status`(15))
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subdomain_alias`
--

create table if not exists `subdomain_alias` (
  `subdomain_alias_id` int(10) unsigned not null auto_increment,
  `alias_id` int(10) unsigned not null,
  `subdomain_alias_name` varchar(200) not null,
  `subdomain_alias_mount` varchar(200) not null,
  `subdomain_alias_document_root` varchar(255) not null default '/htdocs',
  `subdomain_alias_url_forward` varchar(255) not null default 'no',
  `subdomain_alias_type_forward` varchar(5) default null,
  `subdomain_alias_host_forward` varchar(3) not null default 'Off',
  `subdomain_alias_wildcard_alias` enum('yes', 'no') not null default 'no',
  `subdomain_alias_status` varchar(255) not null,
  primary key (`subdomain_alias_id`),
  index `alias_id` (`alias_id`),
  index `subdomain_alias_status` (`subdomain_alias_status`(15))
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

create table if not exists `tickets` (
  `ticket_id` int(10) unsigned not null auto_increment,
  `ticket_level` int(10) not null,
  `ticket_from` int(10) unsigned not null,
  `ticket_to` int(10) unsigned not null,
  `ticket_status` int(10) unsigned not null,
  `ticket_reply` int(10) unsigned not null,
  `ticket_urgency` int(10) unsigned not null,
  `ticket_date` int(10) unsigned not null,
  `ticket_subject` varchar(255) not null,
  `ticket_message` text ,
  primary key (`ticket_id`),
  index `ticket_from` (`ticket_from`),
  index `ticket_to` (`ticket_to`),
  index `ticket_status` (`ticket_status`)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_gui_props`
--

create table if not exists `user_gui_props` (
  `user_id` int(10) unsigned not null,
  `lang` varchar(15) default 'browser',
  `layout` varchar(100) not null default 'default',
  `layout_color` varchar(15) not null default 'black',
  `logo` varchar(255) not null default '',
  `show_main_menu_labels` tinyint(1) not null default '0',
  unique key `user_id` (`user_id`)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `web_software`
--

create table if not exists `web_software` (
  `software_id` int(10) unsigned not null auto_increment,
  `software_master_id` int(10) unsigned not null default '0',
  `reseller_id` int(10) unsigned not null default '0',
  `software_installtype` varchar(15) not null,
  `software_name` varchar(100) not null,
  `software_version` varchar(20) not null,
  `software_language` varchar(15) not null,
  `software_type` varchar(20) not null,
  `software_db` tinyint(1) not null,
  `software_archive` varchar(100) not null,
  `software_installfile` varchar(100) not null,
  `software_prefix` varchar(50) not null,
  `software_link` varchar(100) not null,
  `software_desc` mediumtext not null,
  `software_active` int(1) not null,
  `software_status` varchar(15) not null,
  `rights_add_by` int(10) unsigned not null default '0',
  `software_depot` varchar(15) not null not null default 'no',
  primary key (`software_id`),
  index `software_master_id` (`software_master_id`),
  index `reseller_id` (`reseller_id`)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `web_software_inst`
--

create table if not exists `web_software_inst` (
  `domain_id` int(10) unsigned not null,
  `alias_id` int(10) unsigned not null default '0',
  `subdomain_id` int(10) unsigned not null default '0',
  `subdomain_alias_id` int(10) unsigned not null default '0',
  `software_id` int(10) not null,
  `software_master_id` int(10) unsigned not null default '0',
  `software_res_del` int(1) not null default '0',
  `software_name` varchar(100) not null,
  `software_version` varchar(20) not null,
  `software_language` varchar(15) not null,
  `path` varchar(255) not null default '0',
  `software_prefix` varchar(50) not null default '0',
  `db` varchar(100) not null default '0',
  `database_user` varchar(100) not null default '0',
  `database_tmp_pwd` varchar(100) not null default '0',
  `install_username` varchar(100) not null default '0',
  `install_password` varchar(100) not null default '0',
  `install_email` varchar(100) not null default '0',
  `software_status` varchar(15) not null,
  `software_depot` varchar(15) not null not null default 'no',
  index `domain_id` (`domain_id`),
  index `alias_id` (`alias_id`),
  index `subdomain_id` (`subdomain_id`),
  index `subdomain_alias_id` (`subdomain_alias_id`),
  index `software_id` (`software_id`),
  index `software_master_id` (`software_master_id`)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `web_software_depot`
--

create table if not exists `web_software_depot` (
  `package_id` int(10) unsigned not null auto_increment,
  `package_install_type` varchar(15) not null,
  `package_title` varchar(100) not null,
  `package_version` varchar(20) not null,
  `package_language` varchar(15) not null,
  `package_type` varchar(20) not null,
  `package_description` mediumtext not null,
  `package_vendor_hp` varchar(100) not null,
  `package_download_link` varchar(100) not null,
  `package_signature_link` varchar(100) not null,
  primary key (`package_id`)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci auto_increment=1;

-- --------------------------------------------------------

--
-- Table structure for table `web_software_options`
--

create table if not exists `web_software_options` (
  `use_webdepot` tinyint(1) unsigned not null default '1',
  `webdepot_xml_url` varchar(255) not null,
  `webdepot_last_update` datetime not null,
  unique key `use_webdepot` (`use_webdepot`)
) engine=InnoDB default charset=utf8mb4 collate=utf8mb4_unicode_ci;

--
-- Dumping data for table `web_software_options`
--

insert ignore into `web_software_options` (
    `use_webdepot`, `webdepot_xml_url`, `webdepot_last_update`
) values (
    1, 'http://app-pkg.i-mscp.net/imscp_webdepot_list.xml', '0000-00-00 00:00:00'
);
