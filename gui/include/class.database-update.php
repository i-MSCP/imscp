<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
 *
 *  @license
 *   This program is free software; you can redistribute it and/or
 *   modify it under the terms of the GPL General Public License
 *   as published by the Free Software Foundation; either version 2.0
 *   of the License, or (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GPL General Public License for more details.
 *
 *   You may have received a copy of the GPL General Public License
 *   along with this program.
 *
 *   An on-line copy of the GPL General Public License can be found
 *   http://www.fsf.org/licensing/licenses/gpl.txt
 **/


/**
 * Implementing abstract class ispcpUpdate for database update functions
 *
 * @author	Daniel Andreca <sci2tech@gmail.com>
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
 * @version	1.0
 * @since	r1355
 */
class databaseUpdate extends ispcpUpdate{
	protected $databaseVariableName="DATABASE_REVISION";
	protected $functionName="_databaseUpdate_";
	protected $errorMessage="Database update %s failed";

	public static function getInstance(){
		static $instance=null;
		if($instance===null)$instance= new self();
		return $instance;
	}

	/*
	* Insert the update functions below this entry. The revision has to be ascending and unique.
	* Each databaseUpdate function has to return a array. Even if the array contains only one entry.
	*/

	/**
	 * Initital Update. Insert the first Revision.
	 *
	 * @author		Jochen Manz <zothos@zothos.net>
	 * @copyright	2006-2008 by ispCP | http://isp-control.net
	 * @version		1.0
	 * @since		r1355
	 *
	 * @access		protected
	 * @return		sql statements to be performed
	 */	
	protected function _databaseUpdate_1() {
		$sqlUpd = array();

		$sqlUpd[] = "INSERT INTO `config` (name, value) VALUES ('DATABASE_REVISION' , '1')";

		return $sqlUpd;
	}
	
	/**
	 * Updates the database fields ispcp.mail_users.mail_addr to the right mail address.
	 *
	 * @author		Christian Hernmarck
	 * @copyright	2006-2008 by ispCP | http://isp-control.net
	 * @version		1.0
	 * @since		r1355
	 *
	 * @access		protected
	* @return		sql statements to be performed
	 */
	protected function _databaseUpdate_2() {
		$sqlUpd = array(); // we need several SQL Statements...
	
		// domain mail + forward
		$sqlUpd[] 	= "UPDATE `mail_users`, `domain`"
					. "SET `mail_addr` = CONCAT(`mail_acc`,'@',`domain_name`)"
					. "WHERE `mail_users`.`domain_id` = `domain`.`domain_id`"
					. "AND (`mail_type` = 'normal_mail' OR `mail_type` = 'normal_forward');";

		// domain-alias mail + forward
		$sqlUpd[] 	= "UPDATE `mail_users`, `domain_aliasses`"
					. "SET `mail_addr` = CONCAT(`mail_acc`,'@',`alias_name`)"
					. "WHERE `mail_users`.`domain_id` = `domain_aliasses`.`domain_id` AND `mail_users`.`sub_id` = `domain_aliasses`.`alias_id`"
					. "AND (`mail_type` = 'alias_mail' OR `mail_type` = 'alias_forward');";

		// subdomain mail + forward
		$sqlUpd[] 	= "UPDATE `mail_users`, `subdomain`, `domain`"
					. "SET `mail_addr` = CONCAT(`mail_acc`,'@',`subdomain_name`,'.',`domain_name`)"
					. "WHERE `mail_users`.`domain_id` = `subdomain`.`domain_id` AND `mail_users`.`sub_id` = `subdomain`.`subdomain_id`"
					. "AND `mail_users`.`domain_id` = `domain`.`domain_id`"
					. "AND (`mail_type` = 'subdom_mail' OR `mail_type` = 'subdom_forward');";

		// domain catchall
		$sqlUpd[] 	= "UPDATE `mail_users`, `domain`"
					. "SET `mail_addr` = CONCAT('@',`domain_name`)"
					. "WHERE `mail_users`.`domain_id` = `domain`.`domain_id`"
					. "AND `mail_type` = 'normal_catchall';";

		// domain-alias catchall
		$sqlUpd[] 	= "UPDATE `mail_users`, `domain_aliasses`"
					. "SET `mail_addr` = CONCAT('@',`alias_name`)"
					. "WHERE `mail_users`.`domain_id` = `domain_aliasses`.`domain_id` AND `mail_users`.`sub_id` = `domain_aliasses`.`alias_id`"
					. "AND `mail_type` = 'alias_catchall';";

		// subdomain catchall
		$sqlUpd[] 	= "UPDATE `mail_users`, `subdomain`, `domain`"
					. "SET `mail_addr` = CONCAT('@',`subdomain_name`,'.',`domain_name`)"
					. "WHERE `mail_users`.`domain_id` = `subdomain`.`domain_id` AND `mail_users`.`sub_id` = `subdomain`.`subdomain_id`"
					. "AND `mail_users`.`domain_id` = `domain`.`domain_id`"
					. "AND `mail_type` = 'subdom_catchall';";
	
		return $sqlUpd;
	}

	/**
	 * Fix for ticket #1139 http://www.isp-control.net/ispcp/ticket/1139.
	 *
	 * @author		Benedikt Heintel
	 * @copyright	2006-2008 by ispCP | http://isp-control.net
	 * @version		1.0
	 * @since		r1355
	 *
	 * @access		protected
	 * @return		sql statements to be performed
	 */
	protected function _databaseUpdate_3() {
		$sqlUpd = array();

		$sqlUpd[] = "ALTER IGNORE TABLE `orders_settings` CHANGE `id` `id` int(10) unsigned NOT NULL auto_increment;";

		return $sqlUpd;
	}

	/**
	 * Fix for ticket #1196 http://www.isp-control.net/ispcp/ticket/1196.
	 *
	 * @author		Benedikt Heintel
	 * @copyright	2006-2008 by ispCP | http://isp-control.net
	 * @version		1.0
	 * @since		r1355
	 *
	 * @access		protected
	 * @return		sql statements to be performed
	 */
	protected function _databaseUpdate_4() {
		$sqlUpd = array();

		$sqlUpd[] = "ALTER IGNORE TABLE `mail_users` CHANGE `mail_auto_respond` `mail_auto_respond_text` text collate utf8_unicode_ci;";
		$sqlUpd[] = "ALTER IGNORE TABLE `mail_users` ADD `mail_auto_respond` BOOL NOT NULL default '0' AFTER `status`;";
		$sqlUpd[] = "ALTER IGNORE TABLE `mail_users` CHANGE `mail_type` `mail_type` varchar(30);";

		return $sqlUpd;
	}

	/**
	 * Fix for ticket #1346 http://www.isp-control.net/ispcp/ticket/1346.
	 *
	 * @author		Benedikt Heintel
	 * @copyright	2006-2008 by ispCP | http://isp-control.net
	 * @version		1.0
	 * @since		r1355
	 *
	 * @access		protected
	 * @return		sql statements to be performed
	 */
	protected function _databaseUpdate_5() {
		$sqlUpd = array();

		$sqlUpd[] = "ALTER IGNORE TABLE `sql_user` CHANGE `sqlu_name` `sqlu_name` varchar(64) binary DEFAULT 'n/a';";
		$sqlUpd[] = "ALTER IGNORE TABLE `sql_user` CHANGE `sqlu_pass` `sqlu_pass` varchar(64) binary DEFAULT 'n/a';";

		return $sqlUpd;
	}

	/**
	 * Fix for ticket #755 http://www.isp-control.net/ispcp/ticket/755.
	 *
	 * @author		Markus Milkereit
	 * @copyright	2006-2008 by ispCP | http://isp-control.net
	 * @version		1.0
	 * @since		r1355
	 *
	 * @access		protected
	 * @return		sql statements to be performed
	 */
	protected function _databaseUpdate_6() {
		$sqlUpd = array();

		$sqlUpd[] = "ALTER IGNORE TABLE `htaccess`
					CHANGE `user_id` `user_id` VARCHAR(255) NULL DEFAULT NULL,
					CHANGE `group_id` `group_id` VARCHAR(255) NULL DEFAULT NULL";

		return $sqlUpd;
	}

	/*
	* DO NOT CHANGE ANYTHING BELOW THIS LINE
	*/
}
?>