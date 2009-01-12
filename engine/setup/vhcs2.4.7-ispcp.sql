--
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT=0;

START TRANSACTION;
USE {DATABASE};

-- BEGIN: Upgrade database structure:
ALTER IGNORE TABLE `admin` CHANGE `customer_id` `customer_id` varchar(200) NULL DEFAULT '0';
ALTER IGNORE TABLE `admin` CHANGE `created_by` `created_by` INT(10) UNSIGNED NULL DEFAULT '0';
ALTER IGNORE TABLE `admin` ADD `gender` varchar(1) DEFAULT NULL;
ALTER IGNORE TABLE `admin` ADD `uniqkey_time` TIMESTAMP NULL DEFAULT NULL;
ALTER IGNORE TABLE `admin` ADD UNIQUE KEY `admin_name` (`admin_name`);

CREATE TABLE `config` (
  `name` varchar(255) collate utf8_unicode_ci NOT NULL default '',
  `value` varchar(255) collate utf8_unicode_ci NOT NULL default '',
  PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


INSERT INTO `config` ( `name` , `value` )
VALUES (
'PORT_FTP', '21;tcp;FTP;1;0'
), (
'PORT_SSH', '22;tcp;SSH;1;0'
),(
'PORT_TELNET', '23;tcp;TELNET;1;0'
),(
'PORT_SMTP', '25;tcp;SMTP;1;0'
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
),(
'PORT_POSTGREY', '60000;tcp;POSTGREY;1;1'
),(
'PORT_AMAVIS', '10024;tcp;AMaVis;1;1'
),(
'PORT_SPAMASSASSIN', '783;tcp;SPAMASSASSIN;1;1'
),(
'PORT_POLICYD-WEIGHT', '12525;tcp;POLICYD-WEIGHT;1;1'
),(
'DATABASE_REVISION', '1'
);

ALTER IGNORE TABLE `domain` CHANGE `domain_traffic_limit` `domain_traffic_limit` bigint(20) default NULL;
ALTER IGNORE TABLE `domain` CHANGE `domain_disk_limit` `domain_disk_limit` bigint(20) unsigned default NULL;
ALTER IGNORE TABLE `domain` CHANGE `domain_disk_usage` `domain_disk_usage` bigint(20) unsigned default NULL;
ALTER IGNORE TABLE `domain` ADD UNIQUE KEY `domain_name` (`domain_name`);
ALTER IGNORE TABLE `domain` ADD INDEX `i_domain_admin_id` (`domain_admin_id`);

-- Drop useless table
DROP TABLE IF EXISTS `domain_props`;

ALTER IGNORE TABLE `domain_traffic` ADD `correction` TINYINT( 1 ) NOT NULL DEFAULT '0';
ALTER IGNORE TABLE `domain_traffic` ADD INDEX `i_correction` ( `correction` );
ALTER IGNORE TABLE `domain_traffic` ADD INDEX `i_domain_id` (`domain_id`);
ALTER IGNORE TABLE `domain_traffic` ADD INDEX `i_dtraff_time` (`dtraff_time`);
ALTER IGNORE TABLE `domain_traffic` CHANGE `dtraff_time` `dtraff_time` BIGINT UNSIGNED NULL DEFAULT NULL ,
CHANGE `dtraff_web` `dtraff_web` BIGINT UNSIGNED NULL DEFAULT NULL ,
CHANGE `dtraff_ftp` `dtraff_ftp` BIGINT UNSIGNED NULL DEFAULT NULL ,
CHANGE `dtraff_mail` `dtraff_mail` BIGINT UNSIGNED NULL DEFAULT NULL ,
CHANGE `dtraff_pop` `dtraff_pop` BIGINT UNSIGNED NULL DEFAULT NULL;

ALTER IGNORE TABLE `sql_user` CHANGE `sqlu_name` `sqlu_name` varchar(64) binary DEFAULT 'n/a';
ALTER IGNORE TABLE `sql_user` CHANGE `sqlu_pass` `sqlu_pass` varchar(64) binary DEFAULT 'n/a';

ALTER IGNORE TABLE `ftp_group` ADD UNIQUE KEY `groupname` (`groupname`);

ALTER IGNORE TABLE `htaccess_groups` ADD `status` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ;

ALTER IGNORE TABLE `htaccess_users` ADD `status` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ;

-- Drop existing languages (they are outdated anyways)
DROP TABLE IF EXISTS `lang_Chinese`;
DROP TABLE IF EXISTS `lang_Danish`;
DROP TABLE IF EXISTS `lang_Deutsch`;
DROP TABLE IF EXISTS `lang_Dutch`;
DROP TABLE IF EXISTS `lang_English`;
DROP TABLE IF EXISTS `lang_Finnish`;
DROP TABLE IF EXISTS `lang_French`;
DROP TABLE IF EXISTS `lang_German`;
DROP TABLE IF EXISTS `lang_Italian`;
DROP TABLE IF EXISTS `lang_magyar`;
DROP TABLE IF EXISTS `lang_Portugues`;
DROP TABLE IF EXISTS `lang_Portugues_Brasil`;
DROP TABLE IF EXISTS `lang_Russian`;
DROP TABLE IF EXISTS `lang_Spanish`;

ALTER IGNORE TABLE `log` CHANGE `log_time` `log_time` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP;

-- Add Primary key and possibly an index to login table!
TRUNCATE TABLE `login`;
ALTER IGNORE TABLE `login` ADD `ipaddr` VARCHAR( 15 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ;
ALTER IGNORE TABLE `login` ADD `login_count` TINYINT( 1 ) default '0';
ALTER IGNORE TABLE `login` ADD `captcha_count` TINYINT( 1 ) default '0';
ALTER IGNORE TABLE `login` ADD `user_name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ;
ALTER IGNORE TABLE `login` ADD PRIMARY KEY ( `session_id` );

ALTER IGNORE TABLE `mail_users` ADD `quota` INT( 10 ) NULL DEFAULT '10485760';
ALTER IGNORE TABLE `mail_users` ADD `mail_addr` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ;

ALTER IGNORE TABLE `orders` ADD `gender` varchar(1) default NULL;

ALTER IGNORE TABLE `quotalimits` CHANGE `name` `name` varchar(30) default NULL,
CHANGE `quota_type` `quota_type` ENUM('user','group','class','all') NOT NULL default 'user',
CHANGE `per_session` `per_session` enum('false','true') NOT NULL default 'false',
CHANGE `limit_type` `limit_type` enum('soft','hard') NOT NULL default 'soft',
CHANGE `bytes_in_avail` `bytes_in_avail` float NOT NULL default '0',
CHANGE `bytes_out_avail` `bytes_out_avail` float NOT NULL default '0',
CHANGE `bytes_xfer_avail` `bytes_xfer_avail` float NOT NULL default '0',
CHANGE `files_in_avail` `files_in_avail` int(10) unsigned NOT NULL default '0',
CHANGE `files_out_avail` `files_out_avail` int(10) unsigned NOT NULL default '0',
CHANGE `files_xfer_avail` `files_xfer_avail` int(10) unsigned NOT NULL default '0';
ALTER IGNORE TABLE `quotalimits` ADD PRIMARY KEY ( `name` );

ALTER IGNORE TABLE `quotatallies` CHANGE `name` `name` varchar(30) NOT NULL default '',
CHANGE `quota_type` `quota_type` enum('user','group','class','all') NOT NULL default 'user',
CHANGE `bytes_in_used` `bytes_in_used` float NOT NULL default '0',
CHANGE `bytes_out_used` `bytes_out_used` float NOT NULL default '0',
CHANGE `bytes_xfer_used` `bytes_xfer_used` float NOT NULL default '0',
CHANGE `files_in_used` `files_in_used` int(10) unsigned NOT NULL default '0',
CHANGE `files_out_used` `files_out_used` int(10) unsigned NOT NULL default '0',
CHANGE `files_xfer_used` `files_xfer_used` int(10) unsigned NOT NULL default '0';
ALTER IGNORE TABLE `quotatallies` ADD PRIMARY KEY ( `name` );

ALTER IGNORE TABLE `server_traffic` CHANGE `traff_time` `traff_time` BIGINT(20) UNSIGNED NULL DEFAULT NULL ,
CHANGE `bytes_in` `bytes_in` BIGINT(20) UNSIGNED NULL DEFAULT NULL ,
CHANGE `bytes_out` `bytes_out` BIGINT(20) UNSIGNED NULL DEFAULT NULL ,
CHANGE `bytes_mail_in` `bytes_mail_in` BIGINT(20) UNSIGNED NULL DEFAULT NULL ,
CHANGE `bytes_mail_out` `bytes_mail_out` BIGINT(20) UNSIGNED NULL DEFAULT NULL ,
CHANGE `bytes_pop_in` `bytes_pop_in` BIGINT(20) UNSIGNED NULL DEFAULT NULL ,
CHANGE `bytes_pop_out` `bytes_pop_out` BIGINT(20) UNSIGNED NULL DEFAULT NULL ,
CHANGE `bytes_web_in` `bytes_web_in` BIGINT(20) UNSIGNED NULL DEFAULT NULL ,
CHANGE `bytes_web_out` `bytes_web_out` BIGINT(20) UNSIGNED NULL DEFAULT NULL;
ALTER IGNORE TABLE `server_traffic` ADD `correction` TINYINT( 1 ) NOT NULL DEFAULT '0';
ALTER IGNORE TABLE `server_traffic` ADD INDEX `i_correction` (`correction`);
ALTER IGNORE TABLE `server_traffic` ADD INDEX `i_traff_time` (`traff_time`);

CREATE TABLE IF NOT EXISTS `subdomain_alias` (
					`subdomain_alias_id` int(10) unsigned NOT NULL auto_increment,
					`alias_id` int(10) unsigned default NULL,
					`subdomain_alias_name` varchar(200) collate utf8_unicode_ci default NULL,
					`subdomain_alias_mount` varchar(200) collate utf8_unicode_ci default NULL,
					`subdomain_alias_status` varchar(255) collate utf8_unicode_ci default NULL,
					PRIMARY KEY  (`subdomain_alias_id`)
					) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Drop useless table
DROP TABLE IF EXISTS `syslog`;

ALTER IGNORE TABLE `user_gui_props` CHANGE `user_id` `user_id` int(10) unsigned NOT NULL default 0,
CHANGE `lang` `lang` varchar(255) default '',
CHANGE `layout` `layout` varchar(255) default '';

-- END: Upgrade database structure

-- BEGIN: Regenerate config files:
UPDATE `domain` SET `domain_status` = 'change' WHERE `domain_status` = 'ok';
UPDATE `subdomain` SET `subdomain_status` = 'change' WHERE `subdomain_status` = 'ok';
UPDATE `domain_aliasses` SET `alias_status` = 'change' WHERE `alias_status` = 'ok';
UPDATE `mail_users` SET `status` = 'change' WHERE `status` = 'ok';
-- END: Regenerate config files

-- BEGIN: Change to default ispCP Theme :
UPDATE `user_gui_props` SET `layout` = 'omega_original';
-- END: Change to default ispCP Theme :

-- Change charset:

ALTER DATABASE `ispcp` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

COMMIT;
