--
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT=0;

START TRANSACTION;
USE {DATABASE};

-- BEGIN: Regenerate config files:
UPDATE `domain` SET `domain_status` = 'change' WHERE `domain_status` = 'ok';
UPDATE `domain_aliasses` SET `alias_status` = 'change' WHERE `alias_status` = 'ok';
UPDATE `subdomain` SET `subdomain_status` = 'change' WHERE `subdomain_status` = 'ok';
UPDATE `subdomain_alias` SET `subdomain_alias_status` = 'change' WHERE `subdomain_alias_status` = 'ok';
UPDATE `mail_users` SET `status` = 'change' WHERE `status` = 'ok';
UPDATE `htaccess` SET `status` = 'change' WHERE `status` = 'ok';
UPDATE `htaccess_groups` SET `status` = 'change' WHERE `status` = 'ok';
UPDATE `htaccess_users` SET `status` = 'change' WHERE `status` = 'ok';
-- END: Regenerate config files

COMMIT;
