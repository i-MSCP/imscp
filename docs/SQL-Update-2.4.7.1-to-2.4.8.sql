CREATE TABLE IF NOT EXISTS `vhcs2`.`config` (`name` varchar(255) NOT NULL default '',`value` varchar(255) NOT NULL default '',PRIMARY KEY  (`name`));

ALTER TABLE `vhcs2`.`admin` ADD `uniqkey_time` TIMESTAMP NULL AFTER `uniqkey`;
ALTER TABLE `vhcs2`.`admin` ADD UNIQUE ( `admin_name` );
ALTER TABLE `vhcs2`.`domain` CHANGE `domain_traffic_limit` `domain_traffic_limit` BIGINT NULL DEFAULT NULL;
ALTER TABLE `vhcs2`.`domain` CHANGE `domain_disk_limit` `domain_disk_limit` BIGINT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `vhcs2`.`domain` CHANGE `domain_disk_usage` `domain_disk_usage` BIGINT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `vhcs2`.`domain` ADD INDEX i_domain_admin_id ( `domain_admin_id` );
ALTER TABLE `vhcs2`.`domain` ADD UNIQUE ( `domain_name` );
ALTER TABLE `vhcs2`.`domain_traffic` ADD `correction` TINYINT( 1 ) NOT NULL DEFAULT '0';
ALTER TABLE `vhcs2`.`domain_traffic` ADD INDEX `i_correction` ( `correction` ) ;
ALTER TABLE `vhcs2`.`domain_traffic` ADD INDEX `i_domain_id` ( `domain_id` );
ALTER TABLE `vhcs2`.`domain_traffic` ADD INDEX `i_dtraff_time` ( `dtraff_time` );
ALTER TABLE `vhcs2`.`htaccess` CHANGE `user_id` `user_id` TINYINT( 4 ) NULL DEFAULT NULL , CHANGE `group_id` `group_id` TINYINT( 4 ) , CHANGE `path` `path` VARCHAR( 255 );
ALTER TABLE `vhcs2`.`htaccess` CHANGE `status` `status` VARCHAR( 255 ) default NULL;
ALTER TABLE `vhcs2`.`htaccess_groups` ADD `status` varchar(255) default NULL;
ALTER TABLE `vhcs2`.`htaccess_users` ADD `status` varchar(255) default NULL;
ALTER TABLE `vhcs2`.`login` ADD `ipaddr` varchar(15) NULL AFTER `session_id`;
ALTER TABLE `vhcs2`.`login` ADD `login_count` tinyint(1) NULL AFTER `lastaccess`;
ALTER TABLE `vhcs2`.`login` ADD `user_name` varchar(255) NULL AFTER `ipaddr`;
ALTER TABLE `vhcs2`.`server_traffic` ADD `correction` TINYINT( 1 ) NOT NULL DEFAULT '0';
ALTER TABLE `vhcs2`.`server_traffic` ADD INDEX `i_correction` ( `correction` );
ALTER TABLE `vhcs2`.`server_traffic` ADD INDEX `i_traff_time` ( `traff_time` );
ALTER TABLE `vhcs2`.`suexec_props` DROP INDEX `id`;

UPDATE `vhcs2`.`domain` SET `domain_status` = 'change' WHERE `domain_status` = 'ok';
UPDATE `vhcs2`.`domain_aliasses` SET `alias_status` = 'change' WHERE `alias_status` = 'ok';
UPDATE `vhcs2`.`domain_traffic` SET `dtraff_time` = `dtraff_time` - (`dtraff_time` % 1800);
UPDATE `vhcs2`.`domain_traffic` SET `correction` = 0;
UPDATE `vhcs2`.`htaccess` SET `status` = 'change';
UPDATE `vhcs2`.`htaccess_groups` SET `status` = 'toadd';
UPDATE `vhcs2`.`htaccess_users` SET `status` = 'toadd';
UPDATE `vhcs2`.`server_traffic` SET `traff_time` = `traff_time` - (`traff_time` % 1800);
UPDATE `vhcs2`.`server_traffic` SET `correction` = 0;
UPDATE `vhcs2`.`subdomain` SET `subdomain_status` = 'change' WHERE `subdomain_status` = 'ok';
UPDATE `vhcs2`.`user_gui_props` SET `lang` = 'lang_German' WHERE `lang` = 'lang_Deutsch';
UPDATE `vhcs2`.`user_gui_props` SET `lang` = 'lang_PortuguesBrazil' WHERE `lang` = 'lang_Portugues_Brasil';

DROP TABLE IF EXISTS `vhcs2`.`lang_Deutsch`;
DROP TABLE IF EXISTS `vhcs2`.`lang_Portugues_Brasil`;

DELETE FROM `vhcs2`.`login`;

INSERT INTO `vhcs2`.`config` ( `name`, `value` ) VALUES ('PORT_FTP', '21;tcp;FTP;1;0'), ('PORT_SSH', '22;tcp;SSH;1;0'),('PORT_TELNET', '23;tcp;TELNET;1;0'),('PORT_SMTP', '25;tcp;SMPT;1;0'),('PORT_DNS', '53;tcp;DNS;1;0'),('PORT_HTTP', '80;tcp;HTTP;1;0'),('PORT_HTTPS', '443;tcp;HTTPS;1;0'),('PORT_POP3', '110;tcp;POP3;1;0'),('PORT_POP3-SSL', '995;tcp;POP3-SSL;1;0'),('PORT_IMAP', '143;tcp;IMAP;1;0'),('PORT_IMAP-SSL', '993;tcp;IMAP-SSL;1;0');