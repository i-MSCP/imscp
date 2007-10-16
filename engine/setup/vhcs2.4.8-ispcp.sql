--
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT=0;

START TRANSACTION;
USE {DATABASE};

-- BEGIN: Upgrade database structure:
ALTER TABLE `admin` CHANGE `customer_id` `customer_id` varchar(200) NULL DEFAULT '0';
ALTER TABLE `admin` CHANGE `created_by` `created_by` INT(10) UNSIGNED NULL DEFAULT '0';
ALTER TABLE `admin` ADD `gender` varchar(1) DEFAULT NULL;
ALTER TABLE `admin` CHANGE `uniqkey_time` `uniqkey_time` TIMESTAMP NULL DEFAULT NULL;

INSERT INTO `config` ( `name` , `value` )
VALUES (
'PORT_POSTGREY', '60000;tcp;POSTGREY;1;1'
),(
'PORT_AMAVIS', '10024;tcp;AMaVis;1;1'
),(
'PORT_SPAMASSASSIN', '783;tcp;SPAMASSASSIN;1;1'
);

ALTER TABLE `domain` CHANGE `domain_traffic_limit` `domain_traffic_limit` bigint(20) default NULL;
ALTER TABLE `domain` CHANGE `domain_disk_limit` `domain_disk_limit` bigint(20) unsigned default NULL;
ALTER TABLE `domain` CHANGE `domain_disk_usage` `domain_disk_usage` bigint(20) unsigned default NULL;
ALTER TABLE `domain` DROP INDEX `i_domain_domain_admin_id`;
ALTER TABLE `domain` ADD INDEX `i_domain_admin_id` (`domain_admin_id`);

-- Drop useless table
DROP TABLE IF EXISTS `domain_props`;

ALTER TABLE `domain_traffic` ADD `correction` TINYINT( 1 ) NOT NULL DEFAULT '0';
ALTER TABLE `domain_traffic` ADD INDEX `i_correction` ( `correction` );
ALTER TABLE `domain_traffic` ADD INDEX `i_domain_id` (`domain_id`);
ALTER TABLE `domain_traffic` ADD INDEX `i_dtraff_time` (`dtraff_time`);
ALTER TABLE `domain_traffic` DROP INDEX `i_domain_traffic_domain_id`;
ALTER TABLE `domain_traffic` DROP INDEX `dtraff_time`;
ALTER TABLE `domain_traffic` CHANGE `dtraff_time` `dtraff_time` BIGINT UNSIGNED NULL DEFAULT NULL ,
CHANGE `dtraff_web` `dtraff_web` BIGINT UNSIGNED NULL DEFAULT NULL ,
CHANGE `dtraff_ftp` `dtraff_ftp` BIGINT UNSIGNED NULL DEFAULT NULL ,
CHANGE `dtraff_mail` `dtraff_mail` BIGINT UNSIGNED NULL DEFAULT NULL ,
CHANGE `dtraff_pop` `dtraff_pop` BIGINT UNSIGNED NULL DEFAULT NULL;

ALTER TABLE `ftp_group` ADD UNIQUE KEY `groupname` (`groupname`);

-- Drop existing languages (they are outdated anyways)
DROP TABLE IF EXISTS `lang_Chinese`;
DROP TABLE IF EXISTS `lang_Danish`;
DROP TABLE IF EXISTS `lang_Deutsch`;
DROP TABLE IF EXISTS `lang_Dutch`;
-- English Translation is up to date after install
-- DROP TABLE IF EXISTS `lang_English`;
DROP TABLE IF EXISTS `lang_Finnish`;
DROP TABLE IF EXISTS `lang_French`;
DROP TABLE IF EXISTS `lang_German`;
DROP TABLE IF EXISTS `lang_Italian`;
DROP TABLE IF EXISTS `lang_magyar`;
DROP TABLE IF EXISTS `lang_Portugues`;
DROP TABLE IF EXISTS `lang_Portugues_Brasil`;
DROP TABLE IF EXISTS `lang_Russian`;
DROP TABLE IF EXISTS `lang_Spanish`;

ALTER TABLE `log` CHANGE `log_time` `log_time` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP;

-- Add Primary key and possibly an index to login table!
ALTER TABLE `login` ADD `captcha_count` TINYINT( 1 ) default '0';
ALTER TABLE `login` ADD PRIMARY KEY ( `session_id` );

ALTER TABLE `mail_users` ADD `quota` INT( 10 ) NULL DEFAULT '10485760';
ALTER TABLE `mail_users` ADD `mail_addr` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ;

ALTER TABLE `orders` ADD `gender` varchar(1) default NULL;

ALTER TABLE `quotalimits` CHANGE `name` `name` varchar(30) default NULL,
CHANGE `quota_type` `quota_type` ENUM('user','group','class','all') NOT NULL default 'user',
CHANGE `per_session` `per_session` enum('false','true') NOT NULL default 'false',
CHANGE `limit_type` `limit_type` enum('soft','hard') NOT NULL default 'soft',
CHANGE `bytes_in_avail` `bytes_in_avail` float NOT NULL default '0',
CHANGE `bytes_out_avail` `bytes_out_avail` float NOT NULL default '0',
CHANGE `bytes_xfer_avail` `bytes_xfer_avail` float NOT NULL default '0',
CHANGE `files_in_avail` `files_in_avail` int(10) unsigned NOT NULL default '0',
CHANGE `files_out_avail` `files_out_avail` int(10) unsigned NOT NULL default '0',
CHANGE `files_xfer_avail` `files_xfer_avail` int(10) unsigned NOT NULL default '0';
ALTER TABLE `quotalimits` ADD PRIMARY KEY ( `name` );

ALTER TABLE `quotatallies` CHANGE `name` `name` varchar(30) NOT NULL default '',
CHANGE `quota_type` `quota_type` enum('user','group','class','all') NOT NULL default 'user',
CHANGE `bytes_in_used` `bytes_in_used` float NOT NULL default '0',
CHANGE `bytes_out_used` `bytes_out_used` float NOT NULL default '0',
CHANGE `bytes_xfer_used` `bytes_xfer_used` float NOT NULL default '0',
CHANGE `files_in_used` `files_in_used` int(10) unsigned NOT NULL default '0',
CHANGE `files_out_used` `files_out_used` int(10) unsigned NOT NULL default '0',
CHANGE `files_xfer_used` `files_xfer_used` int(10) unsigned NOT NULL default '0';
ALTER TABLE `quotatallies` ADD PRIMARY KEY ( `name` );

ALTER TABLE `server_traffic` CHANGE `traff_time` `traff_time` BIGINT(20) UNSIGNED NULL DEFAULT NULL ,
CHANGE `bytes_in` `bytes_in` BIGINT(20) UNSIGNED NULL DEFAULT NULL ,
CHANGE `bytes_out` `bytes_out` BIGINT(20) UNSIGNED NULL DEFAULT NULL ,
CHANGE `bytes_mail_in` `bytes_mail_in` BIGINT(20) UNSIGNED NULL DEFAULT NULL ,
CHANGE `bytes_mail_out` `bytes_mail_out` BIGINT(20) UNSIGNED NULL DEFAULT NULL ,
CHANGE `bytes_pop_in` `bytes_pop_in` BIGINT(20) UNSIGNED NULL DEFAULT NULL ,
CHANGE `bytes_pop_out` `bytes_pop_out` BIGINT(20) UNSIGNED NULL DEFAULT NULL ,
CHANGE `bytes_web_in` `bytes_web_in` BIGINT(20) UNSIGNED NULL DEFAULT NULL ,
CHANGE `bytes_web_out` `bytes_web_out` BIGINT(20) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `server_traffic` ADD `correction` TINYINT( 1 ) NOT NULL DEFAULT '0';
ALTER TABLE `server_traffic` DROP INDEX `traff_time`;
ALTER TABLE `server_traffic` ADD INDEX `i_correction` (`correction`);
ALTER TABLE `server_traffic` ADD INDEX `i_traff_time` (`traff_time`);

-- Drop useless table
DROP TABLE IF EXISTS `syslog`;

ALTER TABLE `user_gui_props` CHANGE `user_id` `user_id` int(10) unsigned NOT NULL default 0,
CHANGE `lang` `lang` varchar(255) default '',
CHANGE `layout` `layout` varchar(255) default '';

-- END: Upgrade database structure

-- BEGIN: Regenerate config files:
UPDATE `domain` SET `domain_status` = 'change' WHERE `domain_status` = 'ok';
UPDATE `subdomain` SET `subdomain_status` = 'change' WHERE `subdomain_status` = 'ok';
UPDATE `domain_aliasses` SET `alias_status` = 'change' WHERE `alias_status` = 'ok';
UPDATE `mail_users` SET `status` = 'change' WHERE `status` = 'ok';
-- END: Regenerate config files

-- Change charset:

ALTER DATABASE `ispcp` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

COMMIT;
