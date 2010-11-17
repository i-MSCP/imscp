<?php
/**
 * i-MSCP a internet Multi Server Control Panel
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		iMSCP_Update
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2006-2010 by ispCP | http://i-mscp.net
 * @author 		ispCP Team
 * @author 		i-MSCP Team
 * @version 	SVN: $Id$
 * @link		http://i-mscp.net i-MSCP Home Site
 * @license		http://www.mozilla.org/MPL/ MPL 1.1
 */

/**
 * Class for database updates
 *
 * @category	i-MSCP
 * @package		iMSCP_Update
 * @author		Daniel Andreca <sci2tech@gmail.com>
 * @version		1.0.1
 * @since		r1355
 */
class iMSCP_Update_Database extends iMSCP_Update {

	/**
	 * iMSCP_Update_Database instance
	 *
	 * @var iMSCP_Update_Database
	 */
	protected static $_instance = null;

	/**
	 * The database variable name for the update version
	 *
	 * @var string
	 */
	protected $_databaseVariableName = 'DATABASE_REVISION';

	/**
	 * The update functions prefix
	 *
	 * @var string
	 */
	protected $_functionName = '_databaseUpdate_';

	/**
	 * Default error message for updates that have failed
	 *
	 * @var string
	 */
	protected $_errorMessage = 'Database update %s failed';

	/**
	 * Get an iMSCP_Update_Database instance
	 *
	 * @return iMSCP_Update_Database An iMSCP_Update_Database instance
	 */
	public static function getInstance() {

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
	 * @version 1.0.0
	 * @since r1355
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_1() {

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
	 * Updates the database fields i-mscp.mail_users.mail_addr to the right mail
	 * address.
	 *
	 * @author Christian Hernmarck
	 * @version 1.0.0
	 * @since r1355
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_2() {

		$sqlUpd = array();

		// domain mail + forward
		$sqlUpd[] = "
			UPDATE
				`mail_users`,
				`domain`
			SET
				`mail_addr` = CONCAT(`mail_acc`,'@',`domain_name`)
			WHERE
				`mail_users`.`domain_id` = `domain`.`domain_id`
			AND
				(`mail_type` = 'normal_mail' OR `mail_type` = 'normal_forward')
			;
		";

		// domain-alias mail + forward
		$sqlUpd[] = "
			UPDATE
				`mail_users`,
				`domain_aliasses`
			SET
				`mail_addr` = CONCAT(`mail_acc`,'@',`alias_name`)
			WHERE
				`mail_users`.`domain_id` = `domain_aliasses`.`domain_id`
			AND
				`mail_users`.`sub_id` = `domain_aliasses`.`alias_id`
			AND
				(`mail_type` = 'alias_mail' OR `mail_type` = 'alias_forward')
			;
		";

		// subdomain mail + forward
		$sqlUpd[] = "
			UPDATE
				`mail_users`,
				`subdomain`,
				`domain`
			SET
				`mail_addr` = CONCAT(`mail_acc`,'@',`subdomain_name`,'.',`domain_name`)
			WHERE
				`mail_users`.`domain_id` = `subdomain`.`domain_id`
			AND
				`mail_users`.`sub_id` = `subdomain`.`subdomain_id`
			AND
				`mail_users`.`domain_id` = `domain`.`domain_id`
			AND
				(`mail_type` = 'subdom_mail' OR `mail_type` = 'subdom_forward')
			;
		";

		// domain catchall
		$sqlUpd[] = "
			UPDATE
				`mail_users`,
				`domain`
			SET
				`mail_addr` = CONCAT('@',`domain_name`)
			WHERE
				`mail_users`.`domain_id` = `domain`.`domain_id`
			AND
				`mail_type` = 'normal_catchall'
			;
		";

		// domain-alias catchall
		$sqlUpd[] = "
			UPDATE
				`mail_users`,
				`domain_aliasses`
			SET
				`mail_addr` = CONCAT('@',`alias_name`)
			WHERE
				`mail_users`.`domain_id` = `domain_aliasses`.`domain_id`
			AND
				`mail_users`.`sub_id` = `domain_aliasses`.`alias_id`
			AND
				`mail_type` = 'alias_catchall'
			;
		";

		// subdomain catchall
		$sqlUpd[] = "
			UPDATE
				`mail_users`,
				`subdomain`,
				`domain`
			SET
				`mail_addr` = CONCAT('@',`subdomain_name`,'.',`domain_name`)
			WHERE
				`mail_users`.`domain_id` = `subdomain`.`domain_id`
			AND
				`mail_users`.`sub_id` = `subdomain`.`subdomain_id`
			AND
				`mail_users`.`domain_id` = `domain`.`domain_id`
			AND
				`mail_type` = 'subdom_catchall'
			;
		";

		return $sqlUpd;
	}

	/**
	 * Fix for ticket #1139 http://www.isp-control.net/i-mscp/ticket/1139.
	 *
	 * @author Benedikt Heintel <benedikt.heintel@i-mscp.net>
	 * @version 1.0.0
	 * @since r1355
	 * @return Array SQL statements to be performed
	 */
	protected function _databaseUpdate_3() {

		$sqlUpd = array();

		$sqlUpd[] = "
			ALTER IGNORE TABLE
				`orders_settings`
			CHANGE
				`id` `id` int(10) unsigned NOT NULL auto_increment
			;
		";

		return $sqlUpd;
	}

	/**
	 * Fix for ticket #1196 http://www.isp-control.net/i-mscp/ticket/1196.
	 *
	 * @author Benedikt Heintel <benedikt.heintel@i-mscp.net>
	 * @version 1.0.0
	 * @since r1355
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_4() {

		$sqlUpd = array();

		$sqlUpd[] = "
			ALTER IGNORE TABLE
				`mail_users`
			CHANGE
				`mail_auto_respond` `mail_auto_respond_text` text collate utf8_unicode_ci
			;
		";

		$sqlUpd[] = "
			ALTER IGNORE TABLE
				`mail_users`
			ADD
				`mail_auto_respond` BOOL NOT NULL default '0' AFTER `status`
			;
		";

		$sqlUpd[] = "
			ALTER IGNORE TABLE
				`mail_users`
			CHANGE
				`mail_type` `mail_type` varchar(30)
			;
		";

		return $sqlUpd;
	}

	/**
	 * Fix for ticket #1346 http://www.isp-control.net/i-mscp/ticket/1346.
	 *
	 * @author Benedikt Heintel <benedikt.heintel@i-mscp.net>
	 * @version 1.0.0
	 * @since r1355
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_5() {

		$sqlUpd = array();

		$sqlUpd[] = "
			ALTER IGNORE TABLE
				`sql_user`
			CHANGE
				`sqlu_name` `sqlu_name` varchar(64) binary DEFAULT 'n/a'
			;
		";

		$sqlUpd[] = "
			ALTER IGNORE TABLE
				`sql_user`
			CHANGE
				`sqlu_pass` `sqlu_pass` varchar(64) binary DEFAULT 'n/a'
			;
		";

		return $sqlUpd;
	}

	/**
	 * Fix for ticket #755 http://www.isp-control.net/i-mscp/ticket/755.
	 *
	 * @author Markus Milkereit
	 * @version 1.0.0
	 * @since r1355
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_6() {

		$sqlUpd = array();

		$sqlUpd[] = "
			ALTER IGNORE TABLE
				`htaccess`
			CHANGE
				`user_id` `user_id` VARCHAR(255) NULL DEFAULT NULL,
			CHANGE
				`group_id` `group_id` VARCHAR(255) NULL DEFAULT NULL
			;
		";

		return $sqlUpd;
	}

	/**
	 * Fix for ticket #1509 http://www.isp-control.net/i-mscp/ticket/1509.
	 *
	 * @author Benedikt Heintel <benedikt.heintel@i-mscp.net>
	 * @version 1.0.0
	 * @since r1356
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_7() {

		$sqlUpd = array();

		$sqlUpd[] = "
			DROP TABLE IF EXISTS
				`subdomain_alias`
			;
		";

		$sqlUpd[] = "
			CREATE TABLE IF NOT EXISTS
				`subdomain_alias` (
					`subdomain_alias_id` int(10) unsigned NOT NULL auto_increment,
					`alias_id` int(10) unsigned default NULL,
					`subdomain_alias_name` varchar(200) collate utf8_unicode_ci default NULL,
					`subdomain_alias_mount` varchar(200) collate utf8_unicode_ci default NULL,
					`subdomain_alias_status` varchar(255) collate utf8_unicode_ci default NULL,
					PRIMARY KEY (`subdomain_alias_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
			;
		";

		return $sqlUpd;
	}

	/**
	 * Fix for ticket #1571 http://www.isp-control.net/i-mscp/ticket/1571.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @version 1.0.0
	 * @since r1417
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_8() {

		$sqlUpd = array();

		// moved to critical because we need to run engine request
		return $sqlUpd;
	}

	/**
	 * Fix for ticket #1610 http://www.isp-control.net/i-mscp/ticket/1610.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @version 1.0.0
	 * @since r1462
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_9() {

		$sqlUpd = array();

		$sqlUpd[] = "
			ALTER IGNORE TABLE
				`mail_users`
			CHANGE
				`mail_acc` `mail_acc` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
			CHANGE
				`mail_pass` `mail_pass` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
			CHANGE
				`mail_forward` `mail_forward` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
			CHANGE
				`mail_type` `mail_type` VARCHAR(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
			CHANGE
				`status` `status` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL
			;
		";

		return $sqlUpd;
	}

	/**
	 * Fix for ticket #1664 http://www.isp-control.net/i-mscp/ticket/1664.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @version 1.0.0
	 * @since r1508
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_10() {

		$sqlUpd = array();

		$sqlUpd[] = "
			UPDATE
				`config`
			SET
				`value` = CONCAT(`value`, ';')
			WHERE `name` LIKE \"PORT_%\"
			;
		";

		$sqlUpd[] = "
			UPDATE
				`config`
			SET
				`value` = CONCAT(`value`, 'localhost')
			WHERE `name` IN (\"PORT_POSTGREY\", \"PORT_AMAVIS\", \"PORT_SPAMASSASSIN\", \"PORT_POLICYD-WEIGHT\")
			;
		";

		return $sqlUpd;
	}

	/**
	 * Fix for ticket #1620 http://www.isp-control.net/i-mscp/ticket/1620.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @version 1.0.0
	 * @since r1550
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_11() {

		$sqlUpd = array();

		$sqlUpd[] = "
			ALTER IGNORE TABLE
				`admin`
			ADD
				`state` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL
			AFTER
				`city`
			;
		";

		$sqlUpd[] = "
			ALTER IGNORE TABLE
				`orders`
			ADD
				`state` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL
			AFTER
				`city`
			;
		";

		return $sqlUpd;
	}

	/**
	 * add variable SHOW_SERVERLOAD to config table
	 *
	 * @author Thomas Häber
	 * @version 1.0.1
	 * @since r1614
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_12() {

		$sqlUpd = array();

		$sqlUpd[] = "
			INSERT INTO
				`config` (name, value)
			VALUES
				('SHOW_SERVERLOAD', '1')
			;
		";

		return $sqlUpd;
	}

	/**
	 * add variables PREVENT_EXTERNAL_LOGIN for each user type to config table
	 *
	 * @author Thomas Häber
	 * @version 1.0.1
	 * @since r1659
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_13() {

		$sqlUpd = array();

		$sqlUpd[] = "
			INSERT INTO
				`config` (name, value)
			VALUES
				('PREVENT_EXTERNAL_LOGIN_ADMIN', '1')
			;
		";

		$sqlUpd[] = "
			INSERT INTO
				`config` (name, value)
			VALUES
				('PREVENT_EXTERNAL_LOGIN_RESELLER', '1')
			;
		";

		$sqlUpd[] = "
			INSERT INTO
				`config` (name, value)
			VALUES ('PREVENT_EXTERNAL_LOGIN_CLIENT','1')
			;
		";

		return $sqlUpd;
	}

	/**
	 * Fixed #1761: Hosting plan description (to short field description in SQL
	 * table hosting_plan)
	 *
	 * @author Thomas Häber
	 * @version 1.0.1
	 * @since r1663
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_14() {

		$sqlUpd = array();

		$sqlUpd[] = "
			ALTER IGNORE TABLE
				`hosting_plans`
			CHANGE
				`description` `description` TEXT
			;
		";

		return $sqlUpd;
	}

	/**
	 * missing db updates for per-domain backup
	 *
	 * @author Jochen Manz
	 * @version 1.0.1
	 * @since r1663
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_15() {

		$sqlUpd = array();

		$sqlUpd[] = "
			ALTER IGNORE TABLE
				`domain`
			ADD
				`allowbackup` VARCHAR(8) NOT NULL DEFAULT 'full'
			;
		";

		return $sqlUpd;
	}

	/**
	 * update SMTP-SSL to the original Port list, see ticket #1806
	 * http://www.isp-control.net/i-mscp/ticket/1806.
	 *
	 * @author Christian Hernmarck
	 * @version 1.0.1
	 * @since r1714 (ca)
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_16() {

		$sqlUpd = array();

		$sqlUpd[] = "
			INSERT IGNORE INTO
				`config` (`name`, `value`)
			VALUES
				('PORT_SMTP-SSL', '465;tcp;SMTP-SSL;1;0;')
			;
		";

		return $sqlUpd;
	}

	/**
	 * Clean ticket database: Remove html entities from subjects and messages
	 * Related to ticket #1721 http://www.isp-control.net/i-mscp/ticket/1721.
	 *
	 * @author Thomas Wacker
	 * @version 1.0.1
	 * @since r1718
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_17() {

		$sqlUpd = array();

		$sql = iMSCP_Registry::get('Db');

		$query	= "
			SELECT
				`ticket_id`,
				`ticket_subject`,
				`ticket_message`
			FROM
				`tickets`
			ORDER BY
				`ticket_id`
			;
		";

		$rs = exec_query($sql, $query);

		if ($rs->recordCount() != 0) {
			while (!$rs->EOF) {
				$subject = html_entity_decode(
					$rs->fields['ticket_subject'], ENT_QUOTES, 'UTF-8'
				);

				$message = html_entity_decode(
					$rs->fields['ticket_message'], ENT_QUOTES, 'UTF-8'
				);

				if ($subject != $rs->fields['ticket_subject'] ||
					$message != $rs->fields['ticket_message']) {

					$sqlUpd[] = "
						UPDATE
							`tickets`
						SET
							`ticket_subject` = '" . addslashes($subject) . "',
							`ticket_message` = '" . addslashes($message) . "'
						WHERE
							`ticket_id` = '" . addslashes($rs->fields['ticket_id']) . "'
						;
					";
				}

				$rs->moveNext();
			}
		}

		return $sqlUpd;
	}

	/**
	 * Fix for ticket #1810 http://www.isp-control.net/i-mscp/ticket/1810.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @version 1.0.1
	 * @since r1726
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_18() {

		$sqlUpd = array();

		// Moved to 19
		return $sqlUpd;
	}

	/**
	 * Add suport for DNS management.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @version 1.0.1
	 * @since r1727
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_19() {

		$sqlUpd = array();

		$sqlUpd[] = "
			CREATE TABLE IF NOT EXISTS
				`domain_dns` (
					`domain_dns_id` int(11) NOT NULL auto_increment,
					`domain_id` int(11) NOT NULL,
					`alias_id` int(11) default NULL,
					`domain_dns` varchar(50) collate utf8_unicode_ci NOT NULL,
					`domain_class` enum('IN','CH','HS') collate utf8_unicode_ci NOT NULL default 'IN',
					`domain_type` enum('A','AAAA','CERT','CNAME','DNAME','GPOS','KEY','KX','MX','NAPTR','NSAP','NS​','NXT','PTR','PX','SIG','SRV','TXT') collate utf8_unicode_ci NOT NULL default 'A',
					`domain_text` varchar(128) collate utf8_unicode_ci NOT NULL,
					PRIMARY KEY  (`domain_dns_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
			;
		";

		$sqlUpd[] = "
			ALTER IGNORE TABLE
				`domain`
			ADD
				`domain_dns` VARCHAR(15) NOT NULL DEFAULT 'no'
			;
		";

		$sqlUpd[] = "
			UPDATE
				`hosting_plans`
			SET
				`props` = CONCAT(`props`,'_no_;')
			;
		";

		$sqlUpd[] = "
			UPDATE
				`config`
			SET
				`value` = '465;tcp;SMTP-SSL;1;0;' WHERE `name` = 'PORT_SMTP-SSL'
			;
		";

		return $sqlUpd;
	}

	/**
	 * Correct some reseller properties
	 *
	 * @author Thomas Wacker
	 * @version 1.0.1
	 * @since r1834
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_20() {

		$sqlUpd = array();

		$sql = iMSCP_Registry::get('Db');

		$query	= "
			SELECT
				`reseller_id`
			FROM
				`reseller_props`
			ORDER BY
				`reseller_id`
			;
		";

		$rs = exec_query($sql, $query);

		if ($rs->recordCount() != 0) {
			while (!$rs->EOF) {
				$props = recalc_reseller_c_props($rs->fields['reseller_id']);

				$sqlUpd[] = "
					UPDATE
						`reseller_props`
					SET
						`current_dmn_cnt` = '{$props[0]}',
						`current_sub_cnt` = '{$props[1]}',
						`current_als_cnt` = '{$props[2]}',
						`current_mail_cnt` = '{$props[3]}',
						`current_ftp_cnt` = '{$props[4]}',
						`current_sql_db_cnt` = '{$props[5]}',
						`current_sql_user_cnt` = '{$props[6]}'
					WHERE
						`reseller_id` = " . $rs->fields['reseller_id'] . "
					;
				";

				$rs->moveNext();
			}
		}

		return $sqlUpd;
	}

	/**
	 * Try to correct E-Mail-Template after-order-msg
	 *
	 * @author Thomas Wacker
	 * @version 1.0.1
	 * @since r1848
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_21() {

		$sqlUpd = array();

		$sql = iMSCP_Registry::get('Db');

		$add = "\n\nYou have to click the following link to continue the domain creation process.\n\n{ACTIVATE_LINK}\n";

		$query = "
			SELECT
				`id`, `message`
			FROM
				`email_tpls`
			WHERE
				`name` = ?
			;
		";

		$res = exec_query($sql, $query, 'after-order-msg');

		while ($data = $res->fetchRow()) {
			$msg = $data['message'];
			$n = strpos($msg, '{DOMAIN}');

			if ($n !== false) {
				$msg = substr($msg, 0, $n+8).$add.substr($msg, $n+8);

				$sqlUpd[] = "
					UPDATE
						`email_tpls`
					SET
						`message` = '" . addslashes($msg) . "'
					WHERE
						`id` = {$data['id']}
					;
				";
			}
		}

		return $sqlUpd;
	}

	/**
	 * Add domain expiration field (Thanks to alecksievici)
	 *
	 * @author Thomas Wacker
	 * @version 1.0.1
	 * @since r1849
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_22() {

		$sqlUpd = array();

		$sqlUpd[] = "
			ALTER IGNORE TABLE
				`domain`
			ADD
				`domain_expires` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0'
			AFTER
				`domain_created`
			;
		";

		return $sqlUpd;
	}

	/**
	 * Add domain expiration field
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @version 1.0.2
	 * @since r1955
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_23() {

		$sqlUpd = array();

		$sqlUpd[] = "
			ALTER IGNORE TABLE
				`domain_dns`
			CHANGE
				`domain_type` `domain_type` ENUM( 'A', 'AAAA', 'CERT', 'CNAME', 'DNAME', 'GPOS', 'KEY', 'KX', 'MX', 'NAPTR', 'NSAP', 'NS', 'NXT', 'PTR', 'PX', 'SIG', 'SRV', 'TXT' ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'A'
			;
		";

		return $sqlUpd;
	}

	/**
	 * Fixes for ticket #1985 http://www.isp-control.net/i-mscp/ticket/1985.
	 *
	 * This db update provides the following:
	 * Fixes for hosting plans properties:
	 *  - Possible missing of backup property
	 *  - Possible inversion between backup and dns properties
	 * Remove the last semicolon in all "hosting_plans.props"
	 * Fixes for "domain.allowbackup" and "domain.domain_dns" fieds
	 *  - Possible inversion between the values of "domain.allowbackup" and "domain.domain_dns
	 *  - Possible unstripped values
	 *  - Possible missing value in "domain.allowbackup"
	 *  - Change the naming convention for option 'domain' related to the backup feature
	 *
	 * @author Laurent Declercq <laurent.declercq@i-mscp.net>
	 * @version 1.0.2
	 * @since r1998
	 * @return array SQL statements to be performed
	 */
	 protected function _databaseUpdate_24() {

		$sql = iMSCP_Registry::get('Db');
		$sqlUpd = array();

		/**
		 * Fixes for hosting plans properties:
		 * - Possible missing of backup property
		 * - Possible inversion between backup and dns properties
		 * - Remove the last semicolon in all "hosting_plans.props"
		 */
		$query = "
			SELECT
				`id`,
				`props`
			FROM
				`hosting_plans`
			;
		";

		$rs = exec_query($sql, $query);

		if ($rs->recordCount() != 0)
		{
			while (!$rs->EOF)
			{
				list(
						$a, $b, $c,
						$d, $e, $f,
						$g, $h, $i,
						$j, $k, $l
					) = explode(';', $rs->fields['props']);

				// Possible missing of backup property
				if ($l == '') {

					$new_props = "$a;$b;$c;$d;$e;$f;$g;$h;$i;$j;_full_;$k";

				// Possible inversion between backup and dns properties
				} elseif ( ($l != '_no_') && ($l != '_yes_') ) {

					$new_props = "$a;$b;$c;$d;$e;$f;$g;$h;$i;$j;$l;$k";

				// Remove the last semicolon in all "hosting_plans.props"
				} else {

					$new_props = "$a;$b;$c;$d;$e;$f;$g;$h;$i;$j;$k;$l";
				}

				$sqlUpd[] = "
					UPDATE
						`hosting_plans`
					SET
						`props` = '$new_props'
					WHERE
						`id`= '{$rs->fields['id']}'
					;
				";

				$rs->moveNext();
			}
		}

		/**
		 * Fixes for "domain.allowbackup" and "domain.domain_dns" fieds
		 *  - Possible inversion between the values of "domain.allowbackup" and
		 *  "domain.domain_dns"
		 *  - Possible unstripped values
		 *  - Possible missing value in "domain.allowbackup"
		 *  - Change the naming convention for option 'domain' related to the
		 *  backup feature
		 */

		// Temporary table used by the following SQL statement
		$sqlUpd[] = "
			CREATE TEMPORARY TABLE IF NOT EXISTS
				`upd_imscp`
			AS SELECT
				`domain_id` AS `tdomain_id`,
				TRIM(BOTH '_' FROM `allowbackup`) AS `tdomain_dns`,
				`domain_dns` AS `tallowbackup`
			FROM
				`domain`
			WHERE
				`domain_dns` NOT REGEXP '^[(yes|no)]'
			;
		";

		// Possible inversion between the values of "domain.allowbackup" and
		// "domain.domain_dns
		$sqlUpd[] = "
			UPDATE
				`domain`,`upd_imscp`
			SET
				`allowbackup` = `tallowbackup`,
				`domain_dns` = `tdomain_dns`
			WHERE
				`domain_id` = `tdomain_id`
			;
		";

		// Possible missing value in "domain.allowbackup"
		$sqlUpd[] = "
			UPDATE
				`domain`
			SET
				`allowbackup` = 'full'
			WHERE
				`allowbackup` = ''
			;
		";

		// Change the naming convention for option 'domain' related to the
		// backup feature
		$sqlUpd[] = "
			UPDATE
				`domain`
			SET
				`allowbackup` = 'dmn'
			WHERE
				`allowbackup` = 'domain';
		";

		return $sqlUpd;
	 }

	/**
	 * Fixes for ticket #2000 http://www.isp-control.net/i-mscp/ticket/1985.
	 *
	 * @author Laurent Declercq <laurent.declercq@i-mscp.net>
	 * @version 1.0.2
	 * @since r2013
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_25() {

		$sqlUpd = array();

		$sqlUpd[] = "
			UPDATE
				`user_gui_props`
			SET
				`lang` = 'lang_EnglishBritain'
			WHERE
				`lang` = 'lang_English'
			;
		";

		return $sqlUpd;
	}

	/**
	 * Fixes for ticket #2047 http://www.isp-control.net/i-mscp/ticket/2047.
	 *
	 * @author Benedikt Heintel <benedikt.heintel@i-mscp.net>
	 * @version 1.0.2
	 * @since r2173
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_26() {

		$sqlUpd = array();

		// Change all NULL values to decimal 0
		$sqlUpd[] = "
			UPDATE
				`domain_dns`
			SET
				`domain_dns`.`alias_id` = '0'
			WHERE
				`domain_dns`.`alias_id`= NULL
			;
		";

		// Remove NULL value for alias_id
		$sqlUpd[] = "
			ALTER IGNORE TABLE
				`domain_dns`
			CHANGE
				`domain_dns`.`alias_id` `domain_dns`.`alias_id` INT(11) NOT NULL
			;
		";

		// Add Unique Key
		$sqlUpd[] = "
			ALTER IGNORE TABLE
				`domain_dns`
			ADD UNIQUE
				(`domain_id`,
				`alias_id`,
				`domain_dns`,
				`domain_class`,
				`domain_type`,
				`domain_text`)
			;
		";

		return $sqlUpd;
	}

	/**
	 * Adding Support System Control:
	 * Admin can Enable and Disable Reseller's support system from frontend,
	 * belongs to ticket #1121 @see http://isp-control.net/i-mscp/ticket/1121
	 *
	 * @author Sebastian Sellmeier
	 * @version 1.0.1
	 * @since r2500
	 * @return array Sql statements to be performed
	 */
	protected function _databaseUpdate_27() {
		$sqlUpd = array();

		$sqlUpd[] = "
			ALTER IGNORE TABLE
				`reseller_props`
			ADD
				`support_system` ENUM( 'yes', 'no' ) NOT NULL DEFAULT 'yes'
			AFTER
				`max_traff_amnt`
			;
		";

		return $sqlUpd;
	}

	/**
	 * Adding autoreply loop detection/prevention.
	 *
	 * @author Marc pujol
	 * @version 1.0.4
	 * @since r2592
	 * @return array Sql statements to be performed
	 */
	protected function _databaseUpdate_28() {
		$sqlUpd = array();

		// Dropping the table is safe enough because the worst thing that may happen is that we
		// autoreply twice the same sender if the update is re-applied. Not a big deal...
		$sqlUpd[] = "
			DROP TABLE IF EXISTS
				`autoreplies_log`
			;
		";

		$sqlUpd[] = "
			CREATE TABLE
				`autoreplies_log` (
					`time` DATETIME NOT NULL COMMENT 'Date and time of the sent autoreply',
					`from` VARCHAR( 255 ) NOT NULL COMMENT 'autoreply message sender',
					`to` VARCHAR( 255 ) NOT NULL COMMENT 'autoreply message recipient',
					INDEX ( `time` )
			) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci COMMENT = 'Sent autoreplies log table'
			;
		";

		return $sqlUpd;
	}

	/**
	 * Transitional issue (Fix database update conflict)
	 *
	 * @author Laurent Declercq <laurent.declercq@i-mscp.net>
	 * @since r2701
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_29() {

		// old SQL statements was moved to 31 to
		// resolve conflict created under 1.0.5
		return array();
	}

	/**
	 * Fix for ticket #2265 http://www.isp-control.net/i-mscp/ticket/2265
	 *
	 * This update adding slash as first char if doesn't exists and remove
	 * double and trailling slash in the relative paths of `.htaccess` files
	 * for convenience reasons in the i-mscp-htaccess-mngr engine script.
	 *
	 * @author Laurent Declercq <laurent.declercq@i-mscp.net>
	 * @since r2698
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_30() {

		$sqlUpd = array();
		$sql = iMSCP_Registry::get('Db');

		$query = "
			SELECT
				`id`,
				`path`
			FROM
				`htaccess`
			;
		";

		$rs = exec_query($sql, $query);

		if ($rs->recordCount() != 0) {
			while (!$rs->EOF) {
				$path = $rs->fields['path'];

				$clean_path = array();

				foreach (explode(DIRECTORY_SEPARATOR, $path) as $dir) {
					if ($dir != '') {
						$clean_path[] = $dir;
					}
				}

				$path = '/' . implode(DIRECTORY_SEPARATOR, $clean_path);

				$sqlUpd[] = "
					UPDATE
						`htaccess`
					SET
						`path` = '$path'
					WHERE
						`id` = '{$rs->fields['id']}'
					;
				";

				$rs->moveNext();
			}
		}

		return $sqlUpd;
	}

	/**
	 * Adding field for term of service
	 *
	 * @author Francesco Bux
	 * @version 1.0.5
	 * @since r2614
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_31() {

		$sqlUpd = array();
		$sql = iMSCP_Registry::get('Db');

		// Test added to prevent error if old version of
		// self::database_databaseUpdate_29() was already applyed
		$query = "
			SHOW COLUMNS FROM
				`hosting_plans`
			LIKE
				'tos'
			;
		";

		$rs = exec_query($sql, $query);

		// Create the new columns only if doesn't already exists
		if ($rs->recordCount() == 0) {
			$sqlUpd[] = "
				ALTER IGNORE TABLE
				    `hosting_plans`
				ADD
					`tos` BLOB NOT NULL
				;
			";
		}

		return $sqlUpd;
	}

	/**
	 * Fix for ticket #2195 http://www.isp-control.net/i-mscp/ticket/2195
	 *
	 * Remove all user gui properties that are orphan in the 'user_gui_props'
	 * database table.
	 *
	 * @author Laurent Declercq <laurent.declercq@i-mscp.net>
	 * @since r2712
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_32() {

		$sqlUpd = array();
		$sql = iMSCP_Registry::get('Db');

		$query = "
			SELECT
				`user_id`
			FROM
				`user_gui_props`
			WHERE
				(SELECT
					count(`admin_id`)
				FROM
					`admin`
				WHERE
					`admin_id` = `user_id`) = 0
			;
		";

		// Get PDO statement object
		$stmt = exec_query($sql, $query);

		// Get a list of ids where each id represent an
		// user  gui property that should be deleted

		if ($stmt->recordCount()) {
			$list_ids = array();

			while ($row = $stmt->fetchRow()) {
				$list_ids[] = $row['user_id'];
			}

			// Prepares the list of ids comma separated
			$list_ids = implode(',', $list_ids);

			// SQL statement to delete all the user properties that are orphan
			$sqlUpd[] = "
				DELETE FROM
					`user_gui_props`
				WHERE
					`user_id` IN ($list_ids)
				;
			";
		}

		return $sqlUpd;
	}

	/**
	 * Old "criticalUpdate" functions moved here due to removal of critical
	 * updates, they will be executed if not done so far, thereafter, the
	 * constant CRITICAL_UPDATE_REVISION will be removed
	 *
	 * @author Benedikt Heintel <benedikt.heintel@i-mscp.net>
	 * @since r2876
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_33() {

		$cfg = iMSCP_Registry::get('Config');
		$sql = iMSCP_Registry::get('Db');

		$sqlUpd = array();

        if (isset($cfg->CRITICAL_UPDATE_REVISION)) {
            $critical_update = $cfg->CRITICAL_UPDATE_REVISION;
        }

		if (!isset($critical_update) || $critical_update < 3) {
			/**
			 * Old Critical Update #1
			 *
			 * Encrypt email and sql users password in database
			 *
			 * @author Daniel Andreca <sci2tech@gmail.com>
			 * @version 1.0.0
			 * @since r1355
			 */
			if (!isset($critical_update)) {
				$status = $cfg->ITEM_CHANGE_STATUS;

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

				$rs = exec_query($sql, $query);

				if ($rs->recordCount() != 0) {
					while (!$rs->EOF) {
						$sqlUpd[] = "
							UPDATE
								`mail_users`
							SET
								`mail_pass`= '" .
								encrypt_db_password($rs->fields['mail_pass']) .
								"', `status` = '$status' WHERE `mail_id` = '" .
								$rs->fields['mail_id'] ."'
							;
						";

						$rs->moveNext();
					}
				}

				$query ="
					SELECT
						`sqlu_id`,
						`sqlu_pass`
					FROM
						`sql_user`
					;
				";

				$rs = exec_query($sql, $query);

				if ($rs->recordCount() != 0) {
					while (!$rs->EOF) {
						$sqlUpd[] = "
							UPDATE
								`sql_user`
							SET
								`sqlu_pass` = '" .
								encrypt_db_password($rs->fields['sqlu_pass']) .
								"' WHERE `sqlu_id` = '".
								$rs->fields['sqlu_id'] . "'
							;
						";

						$rs->moveNext();
					}
				}
			} // end Old Critical Update #1

			/**
			 * Old Critical Update #2
			 *
			 * Create default group for statistics
			 * Fix for ticket #1571 http://www.isp-control.net/i-mscp/ticket/1571
			 *
			 * @author Daniel Andreca <sci2tech@gmail.com>
			 * @version 1.0.0
			 * @since r1417
			 */
			if ($critical_update < 2) {

				$status = $cfg->ITEM_ADD_STATUS;
				$statsgroup = $cfg->AWSTATS_GROUP_AUTH;

				$query = "
					SELECT
						`domain_id`
					FROM
						`domain`
					WHERE
						`domain_id` NOT IN (
							SELECT
								`dmn_id`
							FROM
								`htaccess_groups`
							WHERE
								`ugroup` = '{$statsgroup}'
						)
					;
				";

				$rs = exec_query($sql, $query);

				if ($rs->recordCount() != 0) {
					while (!$rs->EOF) {
						$sqlUpd[] = "
							INSERT INTO
								htaccess_groups (`dmn_id`, `ugroup`,`status`)
							VALUES (
								'{$rs->fields['domain_id']}',
								'{$statsgroup}',
								'{$status}'
							)
							;
						";

						$rs->moveNext();
					}
				}
			}

			/**
			 * Old Critical Update #3
			 *
			 * Create default group for statistics
			 * Fix for ticket #1571 http://www.isp-control.net/i-mscp/ticket/1571.
			 *
			 * @author Daniel Andreca <sci2tech@gmail.com>
			 * @version 1.0.0
			 * @since r1725
			 */
			$interfaces = new iMSCP_NetworkCard();
			$card = $interfaces->ip2NetworkCard($cfg->BASE_SERVER_IP);

			$sqlUpd[] = "
				ALTER IGNORE TABLE
					`server_ips`
				ADD
					`ip_card` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL,
				ADD
					`ip_ssl_domain_id` INT( 10 ) NULL,
				ADD
					`ip_status` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL
				;
			";

			$sqlUpd[] = "
				UPDATE
					`server_ips`
				SET
					`ip_card` = '" . $card . "',
					`ip_status` = '" . $cfg->ITEM_CHANGE_STATUS . "'
				;
			";

			/**
			 * Old Critical Updates #4 and #5 moved to {@see _databaseUpdate_24}
			 */
		}

		if (isset($critical_update)) {
			$sqlUpd[] = "
				DELETE IGNORE FROM
					`imscp`.`config`
				WHERE
					`config`.`name` = 'CRITICAL_UPDATE_REVISION'
				;
			";
		}

		return $sqlUpd;
	}

	/**
	 * Fix for ticket #2345 http://www.isp-control.net/i-mscp/ticket/2345
	 *
	 * Deletes the 'Show Server Load' option and the related variable
	 *
	 * @author Benedikt Heintel <benedikt.heintel@i-mscp.net>
	 * @since r2876
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_34() {

		return array("
			DELETE IGNORE FROM
				`imscp`.`config`
			WHERE
				`config`.`name` = 'SHOW_SERVERLOAD'
			;
		");
	}

	/**
	 * Fix for ticket #2201 http://www.isp-control.net/i-mscp/ticket/2201
	 * Fix for Ticket #2452: Update from v1.0.6 will fail on
	 * Deletes the now useless column `correction` from table `domain_traffic`
	 *
	 * @author Benedikt Heintel <benedikt.heintel@i-mscp.net> (#2201)
	 * @author Daniel Andreca <scie2tech@gmail.com> (#2452)
	 * @since r2899
	 * @version 1.0.1
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_35() {

		$sqlUpd = array();
		$sql = iMSCP_Registry::get('Db');

		// For domain traffic
		$query = "
			SHOW COLUMNS FROM
				`imscp`.`domain_traffic`
			WHERE
				`Field` = 'correction'
			;
		";

		$rs = exec_query($sql, $query);

		// Drop the column only if it exists
		if ($rs->recordCount() != 0) {
			$sqlUpd[] = "
				ALTER IGNORE TABLE
					`imscp`.`domain_traffic`
				DROP
					`correction`
				;
			";
		}

		// For server traffic
		$query = "
			SHOW COLUMNS FROM
				`imscp`.`server_traffic`
			WHERE
				`Field` = 'correction'
			;
		";

		$rs = exec_query($sql, $query);

		// Drop the column only if it exists
		if ($rs->recordCount() != 0) {
			$sqlUpd[] = "
				ALTER IGNORE TABLE
					`imscp`.`server_traffic`
				DROP
					`correction`
				;
			";
		}

		return $sqlUpd;
	}

	/**
	 * Fix for ticket #2371 http://isp-control.net/i-mscp/ticket/2371
	 *
	 * i-MSCP GUI fails to login via IPv6
	 *
	 * @author Sascha Bay
	 * @since r2918
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_36() {
		$sqlUpd = array();

		$sqlUpd[] = "
			ALTER IGNORE TABLE
				`login`
			CHANGE
				`ipaddr` `ipaddr` VARCHAR(40) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL
			;
		";

		$sqlUpd[] = "
			ALTER IGNORE TABLE
				`server_ips`
			CHANGE
				`ip_number` `ip_number` VARCHAR(40) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL
			;
		";

		return $sqlUpd;
	}

	/**
	 * Fix for ticket #2319 http://isp-control.net/i-mscp/ticket/2319
	 *
	 * Old Database tables does not support UTF8
	 *
	 * @author Sascha Bay
	 * @since r2920
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_37() {

		$sqlUpd = array();

		$sqlUpd[] = "
			ALTER IGNORE TABLE
				`log`
			CHANGE
				`log_message` `log_message` VARCHAR(250) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL
			;
		";

		return $sqlUpd;
	}

	/**
	 * Changed the config.value column type to allow to store larges data
	 *
	 * Some data can be very larges like serialized data that represent a
	 * object, an array...
	 *
	 * @author Laurent Declercq <laurent.declercq@i-mscp.net>
	 * @since r2988
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_38() {

		return array("
			ALTER TABLE
				`config`
			CHANGE
				`value` `value` LONGTEXT
			CHARACTER SET
				utf8
			COLLATE utf8_unicode_ci
				NOT NULL
			;
		");
	}

	/**
	 * Fix illegal value _full_ for allow backup
	 *
	 * Adding a domain purchased via order panel will insert type of
	 * backup '_full_' which is not allowed
	 *
	 * @since r3263
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_39() {

		return array("
			UPDATE
				`domain`
			SET
				`allowbackup` = 'full'
			WHERE
				`allowbackup` = '_full_'
			;
		");
	}

	/**
	 * Added support for subdomain redirect
	 *
	 * @author Daniel Andreca (sci2tech) <sci2tech@gmail.com>
	 * @since r3392
	 * @return array SQL statements to be performed
	 */
	protected function _databaseUpdate_40() {
		$sqlUpd = array();

		$sqlUpd[] = "
			ALTER IGNORE TABLE
				`subdomain`
			ADD
				`subdomain_url_forward` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL
			AFTER
				`subdomain_mount`;
		";

		$sqlUpd[] = "
			ALTER IGNORE TABLE
				`subdomain_alias`
			ADD
				`subdomain_alias_url_forward` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL
			AFTER
				`subdomain_alias_mount`;
		";

		return $sqlUpd;
	}

	/**
	 * Fix for #2224 Postgrey - Port changed to 10023 for some distributions
	 *
	 * Note: Moved to 42 (previous preinst fix was wrong)
	 *
	 * @author Laurent Declercq <laurent.declercq@i-mscp.net>
	 * @since r3299
	 * @return array
	 */
	protected function _databaseUpdate_41() {
		return array();
	}

	/**
	 * Fix for #2224 Postgrey - Port changed to 10023 for some distributions
	 *
	 * Note: Moved to 43 (previous fix was wrong)
	 *
	 * @author Laurent Declercq <laurent.declercq@i-mscp.net>
	 * @since r3477
	 * @return array
	 */
	protected function _databaseUpdate_42() {
		return array();
	}

	/**
	 * Fix for #2489 Postgrey - Undefined offset in settings_ports.php
	 *
	 * @author Laurent Declercq <laurent.declercq@i-mscp.net>
	 * @since r3547
	 * @return array
	 */
	protected function _databaseUpdate_43() {

		$cfg = new iMSCP_Config_Handler_File();
		$DbConfig = iMSCP_Registry::get('Db_Config');
		$DbConfig->PORT_POSTGREY =
			"{$cfg->PORT_POSTGREY};tcp;POSTGREY;1;1;localhost";

		return array();
	}

	/**
	 * Moved to 45
	 */
	protected function _databaseUpdate_44() {

		$sqlUpd = array();


		return $sqlUpd;
	}

	/**
	 * Allows to protect custom DNS records against deletion
	 *
	 * @author Laurent Declercq <laurent.declercq@i-mscp.net>
	 * @since r3607
	 * @return array
	 */
	protected function _databaseUpdate_45() {

		$sqlUpd = array();

		$sqlUpd[] = "
			ALTER IGNORE TABLE
				`domain_dns`
			ADD
				`protected` VARCHAR( 3 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no'
			AFTER
				`domain_text`
			;
		";

		return $sqlUpd;
	}

	/**
	 * Fixed some CSRF issues in admin log
	 * 
	 *  @author Thomas Wacker <thomas.wacker@ispcp.net>
	 *  @since r3695
	 *  @return array
	 */
	protected function _databaseUpdate_46() {
		$sqlUpd = array();
		
		$sqlUpd[] = "TRUNCATE TABLE `log`;";
		
		return $sqlUpd;
	}

	/**
	 * Removed unused 'suexec_props' table
	 *
	 *  @author Laurent Declercq <laurent.declercq@ispcp.net>
	 *  @since r3709
	 *  @return array
	 */
	protected function _databaseUpdate_47() {
		$sqlUpd = array();

		$sqlUpd[] = "DROP TABLE IF EXISTS `suexec_props`;";

		return $sqlUpd;
	}
	
	/**
	 * Adding apps-installer ticket #14 https://sourceforge.net/apps/trac/i-mscp/ticket/14
	 *
	 * @author		Sascha Bay (TheCry) <worst.case@gmx.de>
	 * @since		r3695
	 *
	 * @access		protected
	 * @return		array
	 */
	 protected function _databaseUpdate_48() {
	 	$sqlUpd = array();
	 	$sqlUpd[]	= "CREATE TABLE IF NOT EXISTS `web_software` (
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
						) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
						
		$sqlUpd[]	= "CREATE TABLE IF NOT EXISTS `web_software_inst` (
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
						) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		
		$sqlUpd[]	= "ALTER TABLE `domain` ADD `domain_software_allowed` VARCHAR( 15 ) COLLATE utf8_unicode_ci NOT NULL default 'no';";
		
		$sqlUpd[]	= "ALTER TABLE `reseller_props` ADD `software_allowed` VARCHAR( 15 ) COLLATE utf8_unicode_ci NOT NULL default 'no';";
		$sqlUpd[]	= "ALTER TABLE `reseller_props` ADD `softwaredepot_allowed` VARCHAR( 15 ) COLLATE utf8_unicode_ci NOT NULL default 'yes';";
	 	
	 	$sqlUpd[]	= "UPDATE `hosting_plans` SET `props`=CONCAT(`props`,';_no_') ";
	 	
	 	return $sqlUpd;
		
	 }

	/*
	 * DO NOT CHANGE ANYTHING BELOW THIS LINE!
	 */
}
