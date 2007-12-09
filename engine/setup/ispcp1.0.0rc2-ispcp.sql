--
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT=0;

START TRANSACTION;
USE {DATABASE};

-- BEGIN: Upgrade database structure:
ALTER TABLE `admin` CHANGE `customer_id` `customer_id` varchar(200) NULL DEFAULT '0';
ALTER TABLE `admin` CHANGE `created_by` `created_by` INT(10) UNSIGNED NULL DEFAULT '0';
ALTER TABLE `admin` ADD `gender` varchar(1) DEFAULT NULL;

INSERT INTO `config` ( `name` , `value` )
VALUES (
'PORT_POSTGREY', '60000;tcp;POSTGREY;1;1'
),(
'PORT_AMAVIS', '10024;tcp;AMaVis;1;1'
),(
'PORT_SPAMASSASSIN', '783;tcp;SPAMASSASSIN;1;1'
),(
'PORT_POLICYD-WEIGHT', '12525;tcp;POLICYD-WEIGHT;1;1'
),(
'DATABASE_REVISION', '3'
);

-- Drop useless table
DROP TABLE IF EXISTS `domain_props`;

ALTER TABLE `domain_traffic` CHANGE `dtraff_time` `dtraff_time` BIGINT UNSIGNED NULL DEFAULT NULL ,
CHANGE `dtraff_web` `dtraff_web` BIGINT UNSIGNED NULL DEFAULT NULL ,
CHANGE `dtraff_ftp` `dtraff_ftp` BIGINT UNSIGNED NULL DEFAULT NULL ,
CHANGE `dtraff_mail` `dtraff_mail` BIGINT UNSIGNED NULL DEFAULT NULL ,
CHANGE `dtraff_pop` `dtraff_pop` BIGINT UNSIGNED NULL DEFAULT NULL;

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

-- Add Primary key and possibly an index to login table!
ALTER TABLE `login` ADD `captcha_count` TINYINT( 1 ) default '0';
ALTER TABLE `login` ADD PRIMARY KEY ( `session_id` );
ALTER TABLE `login` CHANGE `login_count` `login_count` tinyint(1) default '0';

ALTER TABLE `orders` ADD `gender` varchar(1) default NULL;

ALTER TABLE `quotalimits` ADD PRIMARY KEY ( `name` );

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
