--
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT=0;

START TRANSACTION;
USE {DATABASE};

-- BEGIN: Upgrade database structure:
CREATE TABLE IF NOT EXISTS `subdomain_alias` (
					`subdomain_alias_id` int(10) unsigned NOT NULL auto_increment,
					`alias_id` int(10) unsigned default NULL,
					`subdomain_alias_name` varchar(200) collate utf8_unicode_ci default NULL,
					`subdomain_alias_mount` varchar(200) collate utf8_unicode_ci default NULL,
					`subdomain_alias_status` varchar(255) collate utf8_unicode_ci default NULL,
					PRIMARY KEY  (`subdomain_alias_id`)
					) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
-- END: Upgrade database structure

-- BEGIN: Regenerate config files:
UPDATE `domain` SET `domain_status` = 'change' WHERE `domain_status` = 'ok';
UPDATE `domain_aliasses` SET `alias_status` = 'change' WHERE `alias_status` = 'ok';
UPDATE `subdomain` SET `subdomain_status` = 'change' WHERE `subdomain_status` = 'ok';
UPDATE `subdomain_alias` SET `subdomain_alias_status` = 'change' WHERE `subdomain_alias_status` = 'ok';
UPDATE `mail_users` SET `status` = 'change' WHERE `status` = 'ok';
-- END: Regenerate config files

-- Change charset:
ALTER DATABASE `{DATABASE}` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

COMMIT;
