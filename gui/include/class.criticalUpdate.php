<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @version		SVN: $Id$
 * @link		http://isp-control.net
 * @author		ispCP Team
 *
 * @license
 *   This program is free software; you can redistribute it and/or
 *   modify it under the terms of the GPL General Public License
 *   as published by the Free Software Foundation; either version 2.0
 *   of the License, or (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *   GPL General Public License for more details.
 *
 *   You may have received a copy of the GPL General Public License
 *   along with this program.
 *
 *   An on-line copy of the GPL General Public License can be found
 *   http://www.fsf.org/licensing/licenses/gpl.txt
 */

/**
 * Implementing abstract class ispcpUpdate for critical update functions
 *
 * @author		Daniel Andreca <sci2tech@gmail.com>
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @version		1.0
 * @since		r1355
 * @todo		use db prepared statements
 */
class criticalUpdate extends ispcpUpdate{

	/**
	 * The database variable name for the update version
	 * @var string 
	 */
	protected $databaseVariableName = "CRITICAL_UPDATE_REVISION";
	
	/**
	 * The update functions prefix
	 * @var string 
	 */
	protected $functionName = "_criticalUpdate_";
	
	/**
	 * Error message for updates that have failed 
	 * @var string 
	 */
	protected $errorMessage = "Critical update %s failed";

	/**
	 * Create and return a new criticalUpdate instance
	 *
	 * return object criticalUpdate instance
	 */
	public static function getInstance() {
	
		static $instance = null;
		if ($instance === null) $instance = new self();

		return $instance;
	}

	/*
	 * Insert the update functions below this entry. The revision has to be ascending and unique.
	 * Each criticalUpdate function has to return a array. Even if the array is empty.
	 */

	/**
	 * Encrypt email and sql users password in database
	 *
	 * @author		Daniel Andreca <sci2tech@gmail.com>
	 * @copyright	2006-2009 by ispCP | http://isp-control.net
	 * @version		1.0
	 * @since		r1355
	 *
	 * @access		protected
	 * @param		Type $engine_run_request Set to true if is needed to perform an engine request
	 * @return		Type $sqlUpd Sql statements to be performed
	 */
	protected function _criticalUpdate_1(&$engine_run_request) {

		$status = Config::get('ITEM_CHANGE_STATUS');
		$sql = Database::getInstance();
		setConfig_Value('CRITICAL_UPDATE_REVISION', 1);

		$sqlUpd = array();

		$query = "SELECT `mail_id`, `mail_pass` FROM `mail_users` WHERE `mail_type` RLIKE '^normal_mail' OR `mail_type` RLIKE '^alias_mail' OR `mail_type` RLIKE '^subdom_mail'";
		$rs = exec_query($sql, $query);

		if ($rs->RecordCount() != 0) {
			while (!$rs->EOF) {
				$sqlUpd[] = "UPDATE `mail_users` SET `mail_pass`= '". encrypt_db_password($rs->fields['mail_pass']). "', `status` = '$status' WHERE `mail_id` = '". $rs->fields['mail_id'] ."'";
				$rs->MoveNext();
			}
		}

		$query ="SELECT `sqlu_id`, `sqlu_pass` FROM `sql_user`";
		$rs = exec_query($sql, $query);

		if ($rs->RecordCount() != 0) {
			while (!$rs->EOF) {
				$sqlUpd[] = "UPDATE `sql_user` SET `sqlu_pass` = '". encrypt_db_password($rs->fields['sqlu_pass']). "' WHERE `sqlu_id` = '". $rs->fields['sqlu_id'] ."'";
				$rs->MoveNext();
			}
		}

		$engine_run_request = true;

		return $sqlUpd;
	}

	/**
	 * Create default group for statistics
	 * Fix for ticket #1571 http://www.isp-control.net/ispcp/ticket/1571.
	 *
	 * @author		Daniel Andreca <sci2tech@gmail.com>
	 * @copyright	2006-2009 by ispCP | http://isp-control.net
	 * @version		1.0
	 * @since		r1417
	 *
	 * @access		protected
	 * @param		Type $engine_run_request Set to true if is needed to perform an engine request
	 * @return		Type $sqlUpd Sql statements to be performed
	 */
	protected function _criticalUpdate_2(&$engine_run_request) {

		$sqlUpd = array();

		$status = Config::get('ITEM_ADD_STATUS');
		$statsgroup = Config::get('AWSTATS_GROUP_AUTH');
		$sql = Database::getInstance();

		$query = "SELECT `domain_id` FROM `domain` WHERE `domain_id` NOT IN (SELECT `dmn_id` FROM `htaccess_groups` WHERE `ugroup` = '{$statsgroup}')";
		$rs = exec_query($sql, $query);

		if ($rs->RecordCount() != 0) {
			while (!$rs->EOF) {
				$sqlUpd[] = "INSERT INTO htaccess_groups (`dmn_id`, `ugroup`,`status`) VALUES ('{$rs->fields['domain_id']}', '{$statsgroup}', '{$status}')";
				$rs->MoveNext();
			}
		}

		$engine_run_request = true;
		return $sqlUpd;
	}

	/**
	 * Create default group for statistics
	 * Fix for ticket #1571 http://www.isp-control.net/ispcp/ticket/1571.
	 *
	 * @author		Daniel Andreca <sci2tech@gmail.com>
	 * @copyright	2006-2009 by ispCP | http://isp-control.net
	 * @version		1.0
	 * @since		r1725
	 *
	 * @access		protected
	 * @param		Type	$engine_run_request	Set to true if is needed to perform an engine request
	 * @return		Type	$sqlUpd	Sql statements to be performed
	 */
	protected function _criticalUpdate_3(&$engine_run_request) {

		$sqlUpd = array();

		$sql = Database::getInstance();
		$interfaces=new networkCard();
		$card = $interfaces->ip2NetworkCard(Config::get('BASE_SERVER_IP'));

		$sqlUpd[] = "ALTER TABLE `server_ips`
					ADD `ip_card` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL,
					ADD `ip_ssl_domain_id` INT( 10 ) NULL,
					ADD `ip_status` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL";
		$sqlUpd[] = "UPDATE `server_ips` SET `ip_card` = '" . $card . "', `ip_status` = '" . Config::get('ITEM_CHANGE_STATUS') . "'";

		$engine_run_request = true;
		return $sqlUpd;
	}

	/**
	 * Change the naming convention for option 'domain' related to the backup feature
	 * Fix for ticket #1971 http://www.isp-control.net/ispcp/ticket/1971.
	 *
	 * @author		Laurent Declercq <l.declercq@nuxwin.com>
	 * @copyright	2006-2009 by ispCP | http://isp-control.net
	 * @version		1.1
	 * @since		r1986
	 *
	 * @access		protected
	 * @param		Type $engine_run_request Set to true if is needed to perform an engine request
	 * @return		Type $sqlUpd Sql statements to be performed
	 */
	protected function _criticalUpdate_4(&$engine_run_request)
	{
		// moved to databaseUpdate::_databaseUpdate_24 because the critical updates are performed first
		return array();
	}

	/**
	 * Possible missing of backup property
	 * Fix for ticket #1980 http://www.isp-control.net/ispcp/ticket/1980.
	 *
	 * @author		Laurent Declercq <l.declercq@nuxwin.com>
	 * @copyright	2006-2009 by ispCP | http://isp-control.net
	 * @version		1.2
	 * @since		r1986
	 *
	 * @access		protected
	 * @param		Type $engine_run_request Set to true if is needed to perform an engine request
	 * @return		Type $sqlUpd Sql statements to be performed
	 */
	protected function _criticalUpdate_5(&$engine_run_request)
	{
		// moved to databaseUpdate::_databaseUpdate_24 because the critical updates are performed first
		return array();
	}

	/*
	 * DO NOT CHANGE ANYTHING BELOW THIS LINE!
	 */
}
