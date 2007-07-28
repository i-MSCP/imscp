--
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT=0;

START TRANSACTION;
USE ispcp;

-- BEGIN: Upgrade database structure:
ALTER TABLE `admin` ADD `uniqkey_time` TIMESTAMP NULL ;

CREATE TABLE `config` (
  `name` varchar(255) collate utf8_unicode_ci NOT NULL default '',
  `value` varchar(255) collate utf8_unicode_ci NOT NULL default '',
  PRIMARY KEY  (`name`)
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
);

ALTER TABLE `domain_traffic` ADD `correction` TINYINT( 1 ) NOT NULL DEFAULT '0';
ALTER TABLE `domain_traffic` ADD INDEX `i_correction` ( `correction` ) ;
ALTER TABLE `domain_traffic` CHANGE `dtraff_time` `dtraff_time` BIGINT UNSIGNED NULL DEFAULT NULL ,
CHANGE `dtraff_web` `dtraff_web` BIGINT UNSIGNED NULL DEFAULT NULL ,
CHANGE `dtraff_ftp` `dtraff_ftp` BIGINT UNSIGNED NULL DEFAULT NULL ,
CHANGE `dtraff_mail` `dtraff_mail` BIGINT UNSIGNED NULL DEFAULT NULL ,
CHANGE `dtraff_pop` `dtraff_pop` BIGINT UNSIGNED NULL DEFAULT NULL;

ALTER TABLE `server_traffic` CHANGE `traff_time` `traff_time` BIGINT UNSIGNED NULL DEFAULT NULL ,
CHANGE `bytes_in` `bytes_in` BIGINT UNSIGNED NULL DEFAULT NULL ,
CHANGE `bytes_out` `bytes_out` BIGINT UNSIGNED NULL DEFAULT NULL ,
CHANGE `bytes_mail_in` `bytes_mail_in` BIGINT UNSIGNED NULL DEFAULT NULL ,
CHANGE `bytes_mail_out` `bytes_mail_out` BIGINT UNSIGNED NULL DEFAULT NULL ,
CHANGE `bytes_pop_in` `bytes_pop_in` BIGINT UNSIGNED NULL DEFAULT NULL ,
CHANGE `bytes_pop_out` `bytes_pop_out` BIGINT UNSIGNED NULL DEFAULT NULL ,
CHANGE `bytes_web_in` `bytes_web_in` BIGINT UNSIGNED NULL DEFAULT NULL ,
CHANGE `bytes_web_out` `bytes_web_out` BIGINT UNSIGNED NULL DEFAULT NULL;

ALTER TABLE `htaccess_groups` ADD `status` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ;

ALTER TABLE `htaccess_users` ADD `status` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ;

ALTER TABLE `login` ADD `ipaddr` VARCHAR( 15 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ;
ALTER TABLE `login` ADD `user_name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ;
ALTER TABLE `login` ADD `captcha_count` TINYINT( 1 ) default '0';
ALTER TABLE `login` ADD `login_count` TINYINT( 1 ) default '0';
ALTER TABLE `login` ADD PRIMARY KEY ( `session_id` )

ALTER TABLE `quotalimits` ADD PRIMARY KEY ( `name` )
ALTER TABLE `quotatallies` ADD PRIMARY KEY ( `name` )

-- Drop existing languages (they are outdated anyways)

DROP TABLE IF EXISTS `lang_English`;
DROP TABLE IF EXISTS `lang_Chinese`;
DROP TABLE IF EXISTS `lang_Deutsch`;
DROP TABLE IF EXISTS `lang_German`;
DROP TABLE IF EXISTS `lang_Dutch`;
DROP TABLE IF EXISTS `lang_Finnish`;
DROP TABLE IF EXISTS `lang_Italian`;
DROP TABLE IF EXISTS `lang_magyar`;
DROP TABLE IF EXISTS `lang_Portugues`;
DROP TABLE IF EXISTS `lang_Portugues_Brasil`;
DROP TABLE IF EXISTS `lang_Spanish`;
DROP TABLE IF EXISTS `lang_French`;
DROP TABLE IF EXISTS `lang_Russian`;

-- Drop useless tables

DROP TABLE IF EXISTS `domain_props`;

-- Add Primary and possibly an index to login table!

ALTER TABLE `mail_users` ADD `quota` INT( 10 ) NULL DEFAULT '10485760';
ALTER TABLE `mail_users` ADD `mail_addr` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ;

ALTER TABLE `server_traffic` ADD `correction` TINYINT( 1 ) NOT NULL DEFAULT '0';

-- END: Upgrade database structure

-- BEGIN: Regenerate config files:
UPDATE `domain` SET `domain_status` = 'change' WHERE `domain_status` = 'ok';
UPDATE `subdomain` SET `subdomain_status` = 'change' WHERE `subdomain_status` = 'ok';
UPDATE `domain_aliasses` SET `alias_status` = 'change' WHERE `alias_status` = 'ok';
-- END: Regenerate config files

-- Change charset:

ALTER DATABASE `ispcp` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci

COMMIT;
