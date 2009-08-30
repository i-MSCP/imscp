--
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT=0;

START TRANSACTION;
USE {DATABASE};

--- Database update revision 11
ALTER TABLE `admin` ADD `state` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `city`;
ALTER TABLE `orders` ADD `state` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `city`;

--- Database update revision 12
INSERT IGNORE INTO `config` (name, value) VALUES ('SHOW_SERVERLOAD' , '1');

--- Database update revision 13
INSERT IGNORE INTO `config` (name, value) VALUES ('PREVENT_EXTERNAL_LOGIN_ADMIN' , '1');
INSERT IGNORE INTO `config` (name, value) VALUES ('PREVENT_EXTERNAL_LOGIN_RESELLER' , '1');
INSERT IGNORE INTO `config` (name, value) VALUES ('PREVENT_EXTERNAL_LOGIN_CLIENT' , '1');

--- Database update revision 14
ALTER TABLE `hosting_plans` CHANGE `description` `description` TEXT;

--- Database update revision 15
ALTER TABLE `domain` ADD `allowbackup` VARCHAR( 8 ) NOT NULL DEFAULT 'full';

--- Database update revision 16
INSERT IGNORE INTO `config` (`name`, `value`) VALUES ('PORT_SMTP-SSL', '465;tcp;SMTP-SSL;1;0;');

--- Database update revision 17

--- Database update revision 18

--- Database update revision 19
CREATE TABLE IF NOT EXISTS `domain_dns` (
					`domain_dns_id` int(11) NOT NULL auto_increment,
					`domain_id` int(11) NOT NULL,
					`alias_id` int(11) default NULL,
					`domain_dns` varchar(50) collate utf8_unicode_ci NOT NULL,
					`domain_class` enum('IN','CH','HS') collate utf8_unicode_ci NOT NULL default 'IN',
					`domain_type` enum('A','AAAA','CERT','CNAME','DNAME','GPOS','KEY','KX','MX','NAPTR','NSAP','NS​','NXT','PTR','PX','SIG','SRV','TXT') collate utf8_unicode_ci NOT NULL default 'A',
					`domain_text` varchar(128) collate utf8_unicode_ci NOT NULL,
					PRIMARY KEY  (`domain_dns_id`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
ALTER IGNORE TABLE `domain` ADD `domain_dns` VARCHAR( 15 ) NOT NULL DEFAULT 'no';
UPDATE `hosting_plans` SET `props`=CONCAT(`props`,'_no_;') ;
UPDATE `config` SET `value` = '465;tcp;SMTP-SSL;1;0;' WHERE `name` = 'PORT_SMTP-SSL';

--- Database update revision 22
ALTER TABLE `domain` ADD `domain_expires` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `domain_created`;

--- Database update revision 23
ALTER TABLE `domain_dns` CHANGE `domain_type` `domain_type` ENUM( 'A', 'AAAA', 'CERT', 'CNAME', 'DNAME', 'GPOS', 'KEY', 'KX', 'MX', 'NAPTR', 'NSAP', 'NS​', 'NXT', 'PTR', 'PX', 'SIG', 'SRV', 'TXT' ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'A'

REPLACE INTO `config` (name, value) VALUES ('DATABASE_REVISION' , '23');

COMMIT;
