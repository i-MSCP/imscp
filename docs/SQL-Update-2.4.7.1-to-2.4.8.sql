CREATE TABLE IF NOT EXISTS `ispcp`.`config` (`name` varchar(255) NOT NULL default '',`value` varchar(255) NOT NULL default '',PRIMARY KEY  (`name`));

ALTER TABLE `ispcp`.`admin` ADD `uniqkey_time` TIMESTAMP NULL AFTER `uniqkey`;
ALTER TABLE `ispcp`.`admin` ADD UNIQUE ( `admin_name` );
ALTER TABLE `ispcp`.`domain` CHANGE `domain_traffic_limit` `domain_traffic_limit` BIGINT NULL DEFAULT NULL;
ALTER TABLE `ispcp`.`domain` CHANGE `domain_disk_limit` `domain_disk_limit` BIGINT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `ispcp`.`domain` CHANGE `domain_disk_usage` `domain_disk_usage` BIGINT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `ispcp`.`domain` ADD INDEX i_domain_admin_id ( `domain_admin_id` );
ALTER TABLE `ispcp`.`domain` ADD UNIQUE ( `domain_name` );
ALTER TABLE `ispcp`.`domain_traffic` ADD `correction` TINYINT( 1 ) NOT NULL DEFAULT '0';
ALTER TABLE `ispcp`.`domain_traffic` ADD INDEX `i_correction` ( `correction` ) ;
ALTER TABLE `ispcp`.`domain_traffic` ADD INDEX `i_domain_id` ( `domain_id` );
ALTER TABLE `ispcp`.`domain_traffic` ADD INDEX `i_dtraff_time` ( `dtraff_time` );
ALTER TABLE `ispcp`.`htaccess` CHANGE `user_id` `user_id` TINYINT( 4 ) NULL DEFAULT NULL , CHANGE `group_id` `group_id` TINYINT( 4 ) , CHANGE `path` `path` VARCHAR( 255 );
ALTER TABLE `ispcp`.`htaccess` CHANGE `status` `status` VARCHAR( 255 ) default NULL;
ALTER TABLE `ispcp`.`htaccess_groups` ADD `status` varchar(255) default NULL;
ALTER TABLE `ispcp`.`htaccess_users` ADD `status` varchar(255) default NULL;
ALTER TABLE `ispcp`.`login` ADD `ipaddr` varchar(15) NULL AFTER `session_id`;
ALTER TABLE `ispcp`.`login` ADD `login_count` tinyint(1) NULL AFTER `lastaccess`;
ALTER TABLE `ispcp`.`login` ADD `user_name` varchar(255) NULL AFTER `ipaddr`;
ALTER TABLE `ispcp`.`server_traffic` ADD `correction` TINYINT( 1 ) NOT NULL DEFAULT '0';
ALTER TABLE `ispcp`.`server_traffic` ADD INDEX `i_correction` ( `correction` );
ALTER TABLE `ispcp`.`server_traffic` ADD INDEX `i_traff_time` ( `traff_time` );
ALTER TABLE `ispcp`.`suexec_props` DROP INDEX `id`;

UPDATE `ispcp`.`domain` SET `domain_status` = 'change' WHERE `domain_status` = 'ok';
UPDATE `ispcp`.`domain_aliasses` SET `alias_status` = 'change' WHERE `alias_status` = 'ok';
UPDATE `ispcp`.`domain_traffic` SET `dtraff_time` = `dtraff_time` - (`dtraff_time` % 1800);
UPDATE `ispcp`.`domain_traffic` SET `correction` = 0;
UPDATE `ispcp`.`htaccess` SET `status` = 'change';
UPDATE `ispcp`.`htaccess_groups` SET `status` = 'toadd';
UPDATE `ispcp`.`htaccess_users` SET `status` = 'toadd';
UPDATE `ispcp`.`server_traffic` SET `traff_time` = `traff_time` - (`traff_time` % 1800);
UPDATE `ispcp`.`server_traffic` SET `correction` = 0;
UPDATE `ispcp`.`subdomain` SET `subdomain_status` = 'change' WHERE `subdomain_status` = 'ok';
UPDATE `ispcp`.`user_gui_props` SET `lang` = 'lang_German' WHERE `lang` = 'lang_Deutsch';
UPDATE `ispcp`.`user_gui_props` SET `lang` = 'lang_PortuguesBrazil' WHERE `lang` = 'lang_Portugues_Brasil';

DROP TABLE IF EXISTS `ispcp`.`lang_Deutsch`;
DROP TABLE IF EXISTS `ispcp`.`lang_Portugues_Brasil`;

DELETE FROM `ispcp`.`login`;

INSERT INTO `ispcp`.`config` ( `name`, `value` ) VALUES ('PORT_FTP', '21;tcp;FTP;1;0'), ('PORT_SSH', '22;tcp;SSH;1;0'),('PORT_TELNET', '23;tcp;TELNET;1;0'),('PORT_SMTP', '25;tcp;SMPT;1;0'),('PORT_DNS', '53;tcp;DNS;1;0'),('PORT_HTTP', '80;tcp;HTTP;1;0'),('PORT_HTTPS', '443;tcp;HTTPS;1;0'),('PORT_POP3', '110;tcp;POP3;1;0'),('PORT_POP3-SSL', '995;tcp;POP3-SSL;1;0'),('PORT_IMAP', '143;tcp;IMAP;1;0'),('PORT_IMAP-SSL', '993;tcp;IMAP-SSL;1;0');