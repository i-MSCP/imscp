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

ALTER TABLE `domain_traffic` ADD `correction` TINYINT( 1 ) NOT NULL DEFAULT '0';
ALTER TABLE `domain_traffic` ADD INDEX `i_correction` ( `correction` ) ;

ALTER TABLE `htaccess_groups` ADD `status` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ;

ALTER TABLE `htaccess_users` ADD `status` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci iNULL ;

DROP TABLE IF EXISTS `lang_English`;
DROP TABLE IF EXISTS `lang_Chinese`;
DROP TABLE IF EXISTS `lang_Deutsch`;
DROP TABLE IF EXISTS `lang_Dutch`;
DROP TABLE IF EXISTS `lang_Finnish`;
DROP TABLE IF EXISTS `lang_Italian`;
DROP TABLE IF EXISTS `lang_magyar`;
DROP TABLE IF EXISTS `lang_Portugues`;
DROP TABLE IF EXISTS `lang_Portugues_Brasil`;
DROP TABLE IF EXISTS `lang_Spanish`;
DROP TABLE IF EXISTS `lang_French`;

ALTER TABLE `login` ADD `ipaddr` VARCHAR( 15 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ;
ALTER TABLE `login` ADD `user_name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ;
ALTER TABLE `login` ADD `login_count` TINYINT( 1 ) NULL;

-- Add Primary and possibly an index to login table!

ALTER TABLE `mail_users` ADD `quota` INT( 10 ) NULL DEFAULT '10485760';
ALTER TABLE `mail_users` ADD `mail_addr` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL ;

ALTER TABLE `server_traffic` ADD `correction` TINYINT( 1 ) NOT NULL DEFAULT '0';

-- END: Upgrade database structure:

-- BEGIN: Regenerate config files:
UPDATE `domain` SET `domain_status` = 'change' WHERE `domain_status` = 'ok';
UPDATE `subdomain` SET `subdomain_status` = 'change' WHERE `subdomain_status` = 'ok';
UPDATE `domain_aliasses` SET `alias_status` = 'change' WHERE `alias_status` = 'ok';
-- END: Regenerate config files:

COMMIT;
