<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "ispCP - ISP Control Panel".
 *
 * The Initial Developer of the Original Code is ispCP Team.
 * Portions created by Initial Developer are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2011 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package	 iMSCP_Update
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2006-2010 by ispCP | http://i-mscp.net
 * @author	  ispCP Team
 * @author	  i-MSCP Team
 * @version	 SVN: $Id$
 * @link		http://i-mscp.net i-MSCP Home Site
 * @license	 http://www.mozilla.org/MPL/ MPL 1.1
 */

/**
 * Class for database updates
 *
 * @category	i-MSCP
 * @package	 iMSCP_Update
 * @author	  Daniel Andreca <sci2tech@gmail.com>
 * @since	   r1355
 */
class iMSCP_Update_Database extends iMSCP_Update
{
	/**
	 * iMSCP_Update_Database instance.
	 *
	 * @var iMSCP_Update_Database
	 */
	protected static $_instance = null;

	/**
	 * The database variable name for the update version.
	 *
	 * @var string
	 */
	protected $_databaseVariableName = 'DATABASE_REVISION';

	/**
	 * The update functions prefix.
	 *
	 * @var string
	 */
	protected $_functionName = '_databaseUpdate_';

	/**
	 * Default error message for updates that have failed.
	 *
	 * @var string
	 */
	protected $_errorMessage = 'Database update %s failed';

	/**
	 * Get an iMSCP_Update_Database instance.
	 *
	 * @return iMSCP_Update_Database An iMSCP_Update_Database instance
	 */
	public static function getInstance()
	{
		if (is_null(self::$_instance)) {
			self::$_instance = new self;
		}

		return self::$_instance;
	}

	/*
	  * Insert the update functions below this entry. The revision has to be
	  * ascending and unique. Each databaseUpdate function has to return a array,
	  * even if the array contains only one entry.
	  */

	/**
	 * Initial Update. Insert the first Revision.
	 *
	 * @author Jochen Manz <zothos@zothos.net>
	 * @since r1355
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_1()
	{
		$sqlUpd = array();

		$sqlUpd[] = "
			INSERT INTO
				`config` (name, value)
			VALUES
				('DATABASE_REVISION', '1')
			;
		";

		return $sqlUpd;
	}

	/**
	 * Legacy code. Not used
	 *
	 * @return empty array. No SQL statements will be performed
	 */
	protected function _databaseUpdate_2() { return array(); }
	protected function _databaseUpdate_3() { return array(); }
	protected function _databaseUpdate_4() { return array(); }
	protected function _databaseUpdate_5() { return array(); }
	protected function _databaseUpdate_6() { return array(); }
	protected function _databaseUpdate_7() { return array(); }
	protected function _databaseUpdate_8() { return array(); }
	protected function _databaseUpdate_9() { return array(); }
	protected function _databaseUpdate_10() { return array(); }
	protected function _databaseUpdate_11() { return array(); }
	protected function _databaseUpdate_12() { return array(); }
	protected function _databaseUpdate_13() { return array(); }
	protected function _databaseUpdate_14() { return array(); }
	protected function _databaseUpdate_15() { return array(); }
	protected function _databaseUpdate_16() { return array(); }
	protected function _databaseUpdate_17() { return array(); }
	protected function _databaseUpdate_18() { return array(); }
	protected function _databaseUpdate_19() { return array(); }
	protected function _databaseUpdate_20() { return array(); }
	protected function _databaseUpdate_21() { return array(); }
	protected function _databaseUpdate_22() { return array(); }
	protected function _databaseUpdate_23() { return array(); }
	protected function _databaseUpdate_24() { return array(); }
	protected function _databaseUpdate_25() { return array(); }
	protected function _databaseUpdate_26() { return array(); }
	protected function _databaseUpdate_27() { return array(); }
	protected function _databaseUpdate_28() { return array(); }
	protected function _databaseUpdate_29() { return array(); }
	protected function _databaseUpdate_30() { return array(); }
	protected function _databaseUpdate_31() { return array(); }
	protected function _databaseUpdate_32() { return array(); }
	protected function _databaseUpdate_33() { return array(); }
	protected function _databaseUpdate_34() { return array(); }
	protected function _databaseUpdate_35() { return array(); }
	protected function _databaseUpdate_36() { return array(); }
	protected function _databaseUpdate_37() { return array(); }
	protected function _databaseUpdate_38() { return array(); }
	protected function _databaseUpdate_39() { return array(); }
	protected function _databaseUpdate_40() { return array(); }
	protected function _databaseUpdate_41() { return array(); }
	protected function _databaseUpdate_42() { return array(); }
	protected function _databaseUpdate_43() { return array(); }
	protected function _databaseUpdate_44() { return array(); }
	protected function _databaseUpdate_45() { return array(); }

	/*
	 * End of legacy code
	 */

	/**
	 * Fixed some CSRF issues in admin log.
	 *
	 * @author Thomas Wacker <thomas.wacker@ispcp.net>
	 * @since r3695
	 * @return array
	 */
	protected function _databaseUpdate_46()
	{
		$sqlUpd = array();

		$sqlUpd[] = "TRUNCATE TABLE `log`;";

		return $sqlUpd;
	}

	/**
	 * iMSCP start here. Any usage require copyright
	 */

	/**
	 * Removed unused 'suexec_props' table.
	 *
	 * @author Laurent Declercq <ldeclercq@nuxwin.com>
	 * @since r3709
	 * @return array
	 */
	protected function _databaseUpdate_47()
	{
		return array("DROP TABLE IF EXISTS `suexec_props`;");
	}

	/**
	 * Adding apps-installer ticket #14.
	 *
	 * @author  Sascha Bay (TheCry) <worst.case@gmx.de>
	 * @since   r3695
	 * @return  array
	 */
	protected function _databaseUpdate_48()
	{
		$sqlUpd = array();
		$sqlUpd[] = "
	 		CREATE TABLE IF NOT EXISTS
	 			`web_software` (
					`software_id` int(10) unsigned NOT NULL auto_increment,
					`software_master_id` int(10) unsigned NOT NULL default '0',
					`reseller_id` int(10) unsigned NOT NULL default '0',
					`software_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_version` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_language` varchar(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_type` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_db` tinyint(1) NOT NULL,
					`software_archive` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_installfile` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_prefix` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_link` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_desc` mediumtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_active` int(1) NOT NULL,
					`software_status` varchar(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`rights_add_by` int(10) unsigned NOT NULL default '0',
					`software_depot` varchar(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL NOT NULL DEFAULT 'no',
	  				PRIMARY KEY  (`software_id`)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
			;
		";

		$sqlUpd[] = "
			CREATE TABLE IF NOT EXISTS
				`web_software_inst` (
					`domain_id` int(10) unsigned NOT NULL,
					`alias_id` int(10) unsigned NOT NULL default '0',
					`subdomain_id` int(10) unsigned NOT NULL default '0',
					`subdomain_alias_id` int(10) unsigned NOT NULL default '0',
					`software_id` int(10) NOT NULL,
					`software_master_id` int(10) unsigned NOT NULL default '0',
					`software_res_del` int(1) NOT NULL default '0',
					`software_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_version` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_language` varchar(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`path` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL default '0',
					`software_prefix` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL default '0',
					`db` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL default '0',
					`database_user` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL default '0',
					`database_tmp_pwd` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL default '0',
					`install_username` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL default '0',
					`install_password` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL default '0',
					`install_email` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL default '0',
					`software_status` varchar(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_depot` varchar(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL NOT NULL DEFAULT 'no',
  					KEY `software_id` (`software_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
			;
		";

		$sqlUpd[] = self::secureAddColumnTable('domain', 'domain_software_allowed', "ALTER TABLE `domain` ADD `domain_software_allowed` VARCHAR( 15 ) COLLATE utf8_unicode_ci NOT NULL default 'no'");
		$sqlUpd[] = self::secureAddColumnTable('reseller_props', 'software_allowed', "ALTER TABLE `reseller_props` ADD `software_allowed` VARCHAR( 15 ) COLLATE utf8_unicode_ci NOT NULL default 'no'");
		$sqlUpd[] = self::secureAddColumnTable('reseller_props', 'softwaredepot_allowed', "ALTER TABLE `reseller_props` ADD `softwaredepot_allowed` VARCHAR( 15 ) COLLATE utf8_unicode_ci NOT NULL default 'yes'");
		$sqlUpd[] = "UPDATE `hosting_plans` SET `props` = CONCAT(`props`,';_no_');";

		return $sqlUpd;
	}

	/**
	 * Add i-MSCP daemon service properties (moved to 50).
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @since r3985
	 * @return array
	 */
	protected function _databaseUpdate_49()
	{
		return array();
	}

	/**
	 * Add i-MSCP daemon service properties
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @since r4004
	 * @return array
	 */
	protected function _databaseUpdate_50()
	{
		/** @var $dbConfig iMSCP_Config_Handler_Db */
		$dbConfig = iMSCP_Registry::get('dbConfig');
		$dbConfig->PORT_IMSCP_DAEMON = "9876;tcp;i-MSCP-Daemon;1;0;127.0.0.1";

		return array();
	}

	/**
	 * Added field for on-click-logon from the ftp-user site(such as PMA).
	 *
	 * @author William Lightning <kassah@gmail.com>
	 * @return array
	 */
	protected function _databaseUpdate_51()
	{
		$sqlUpd = array();

		$query = "
			ALTER IGNORE TABLE
				`ftp_users`
			ADD
				`rawpasswd` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
			AFTER
				`passwd`
			;
		";

		$sqlUpd[] = self::secureAddColumnTable('ftp_users', 'rawpasswd', $query);

		return $sqlUpd;
	}

	/**
	 * Adding apps-installer new options.
	 *
	 * @author  Sascha Bay (TheCry) <worst.case@gmx.de>
	 * @since   r4036
	 * @return  array
	 */
	protected function _databaseUpdate_52()
	{
		$sqlUpd = array();
		$sqlUpd[] = "
			CREATE TABLE IF NOT EXISTS
				`web_software_depot` (
					`package_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`package_install_type` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
					`package_title` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
					`package_version` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
					`package_language` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
					`package_type` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
					`package_description` mediumtext character set utf8 collate utf8_unicode_ci NOT NULL,
					`package_vendor_hp` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
					`package_download_link` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
					`package_signature_link` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
					PRIMARY KEY (`package_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1
			;
		";

		$sqlUpd[] = "
			CREATE TABLE IF NOT EXISTS
				`web_software_options` (
					`use_webdepot` tinyint(1) unsigned NOT NULL DEFAULT '1',
					`webdepot_xml_url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
					`webdepot_last_update` datetime NOT NULL
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
			;
		";

		$sqlUpd[] = "
			REPLACE INTO
				`web_software_options` (`use_webdepot`, `webdepot_xml_url`, `webdepot_last_update`)
			VALUES
				('1', 'http://app-pkg.i-mscp.net/imscp_webdepot_list.xml', '0000-00-00 00:00:00')
			;
		";

		$sqlUpd[] = self::secureAddColumnTable(
			'web_software',
			'software_installtype',
			"
				ALTER IGNORE TABLE
					`web_software`
				ADD
					`software_installtype` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL
				AFTER
					`reseller_id`
			"
		);

		$sqlUpd[] = " UPDATE `web_software` SET `software_installtype` = 'install'";

		$sqlUpd[] = self::secureAddColumnTable(
			'reseller_props',
			'websoftwaredepot_allowed',
			"ALTER IGNORE TABLE `reseller_props` ADD `websoftwaredepot_allowed` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL DEFAULT 'yes'"
		);

		return $sqlUpd;
	}

	/**
	 * Decrypt email, ftp and sql users password in database
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @version 1.0.0
	 * @since r4509
	 *
	 * @return array
	 */
	protected function _databaseUpdate_53()
	{
		$sqlUpd = array();

		$status = iMSCP_Registry::get('config')->ITEM_CHANGE_STATUS;

		$query = "
			SELECT
				`mail_id`,
				`mail_pass`
			FROM
				`mail_users`
			WHERE
				`mail_type` RLIKE '^normal_mail'
			OR
				`mail_type` RLIKE '^alias_mail'
			OR
				`mail_type` RLIKE '^subdom_mail'
			;
		";

		$rs = exec_query($query);

		if ($rs->recordCount() != 0) {
			while (!$rs->EOF) {
				$sqlUpd[] = "
					UPDATE
						`mail_users`
					SET
						`mail_pass`= '" . decrypt_db_password($rs->fields['mail_pass']) . "',
						`status` = '$status' WHERE `mail_id` = '" . $rs->fields['mail_id'] . "'
					;
				";

				$rs->moveNext();
			}
		}

		$query = "
			SELECT
				`sqlu_id`,
				`sqlu_pass`
			FROM
				`sql_user`
			;
		";

		$rs = exec_query($query);

		if ($rs->recordCount() != 0) {
			while (!$rs->EOF) {
				$sqlUpd[] = "
					UPDATE
						`sql_user`
					SET
						`sqlu_pass` = '" . decrypt_db_password($rs->fields['sqlu_pass']) . "'
					WHERE `sqlu_id` = '" . $rs->fields['sqlu_id'] . "'
					;
				";

				$rs->moveNext();
			}
		}

		$query = "
			SELECT
				`userid`,
				`rawpasswd`
			FROM
				`ftp_users`
			;
		";

		$rs = exec_query($query);

		if ($rs->recordCount() != 0) {
			while (!$rs->EOF) {
				$sqlUpd[] = "
					UPDATE
						`ftp_users`
					SET
						`rawpasswd` = '" . decrypt_db_password($rs->fields['rawpasswd']) . "'
					WHERE `userid` = '" . $rs->fields['userid'] . "'
					;
				";

				$rs->moveNext();
			}
		}

		return $sqlUpd;
	}

	/**
	 * Convert tables to InnoDB
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @version 1.0.0
	 * @since r4509
	 *
	 * @return		array
	 */
	protected function _databaseUpdate_54()
	{

		$sqlUpd = array();

		/** @var $db iMSCP_Database */
		$db = iMSCP_Registry::get('db');
		$tables = $db->metaTables();

		foreach ($tables as $table) {
			$sqlUpd[] = "ALTER TABLE $table ENGINE=InnoDB;";
		}

		return $sqlUpd;
	}

	/*
	 * DO NOT CHANGE ANYTHING BELOW THIS LINE!
	 */

	/**
	 *
	 * Check if a column exists in a database table and if not execute query to add that column
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @version 1.0.0
	 * @since r4509
	 *
	 * @param	string $table	Table name
	 * @param	string $column	Columnt to be added to table
	 * @param	string $query	Query to create column
	 * @return	string 			query to be performed
	 */
	protected function secureAddColumnTable($table, $column, $query)
	{
		$dbName = iMSCP_Registry::get('config')->DATABASE_NAME;

		return "
			DROP PROCEDURE IF EXISTS test;
			CREATE PROCEDURE test()
			BEGIN
				if not exists(
					SELECT * FROM information_schema.COLUMNS WHERE column_name='$column' and table_name='$table' and table_schema='$dbName'
				) THEN
					$query;
				END IF;
			END;
			CALL test();
			DROP PROCEDURE IF EXISTS test;
		";
	}
}
