<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
 * @version 	SVN: $ID$
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

/*
 * Get the current revision from the database and return it
 */
function getCurrentRevision() {
	global $sql;

	$query	= "SELECT * FROM `config` WHERE `name` = 'DATABASE_REVISION'";
 	$rs		= $sql->Execute($query);

	return (int)$rs->fields['value'];
}

/*
 * Return the current revision + 1
 */
function getNextRevision() {
	return getCurrentRevision() + 1;
}

/*
 * Simple check for a new update
 */
function checkDatabaseUpdateExists() {
	if(checkNewRevisionExists())
		return true;
	else
		return false;
}

/*
 * Check for existence of an available update
 */
function checkNewRevisionExists() {
	$functionName = returnFunctionName(getNextRevision());

	if(function_exists($functionName))
		return true;
	else
		return false;
}

/*
 * Combine the needed function name, and return it
 */
function returnFunctionName($revision) {
	$functionName = "_databaseUpdate_" . $revision;

	return $functionName;
}

/*
 * Execute all available update functions.
 */
function executeDatabaseUpdates() {
	global $sql;
	$failedUpdate = false;

	while(checkNewRevisionExists()) {
		// Get the next database update revision
		$newRevision 	= getNextRevision();

		// Get the needed function name
		$functionName 	= returnFunctionName($newRevision);

		// Pull the query from the update function using a variable function
		$queryArray 	= $functionName();

		// Add the query, to set the new Database Revision, to our queryArray
		$queryArray[]	= "UPDATE `config` SET `value` = '$newRevision' WHERE `name` = 'DATABASE_REVISION'";

		// Start the Transaction
		$sql->StartTrans();

		// Execute every query in our queryArray
		foreach($queryArray as $query) {
			$sql->Execute($query);
		}

		// Set failedUpdate to true if an databaseUpdate failed
 		if ($sql->HasFailedTrans())
			$failedUpdate = true;

		// Complete the Transactin and rollback if nessessary
		$sql->CompleteTrans();

		// Display an error if nessessary
		if($failedUpdate)
			system_message(tr("Database update %s failed", $newRevision));
	}
}

/*
 * Insert the update functions below this entry. The revision has to be ascending and unique.
 * Each databaseUpdate function has to return a array. Even if the array contains only one entry.
 */

/*
 * Initital Update. Insert the first Revision.
 */
function _databaseUpdate_1() {
	$sqlUpd = array();

	$sqlUpd[] = "INSERT INTO `config` (name, value) VALUES ('DATABASE_REVISION' , '1')";

	return $sqlUpd;
}


/*
 * Updates the database fields ispcp.mail_users.mail_addr to the right mail address
 * written by Christian Hernmarck, Feb 2008
 * Since it does not delete or add any field, it may be run several times...
 */
function _databaseUpdate_2() {
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

/*
 * Fix for ticket #1139 http://www.isp-control.net/ispcp/ticket/1139 (Benedikt Heintel, 2008-03-27)
 */
function _databaseUpdate_3() {
	$sqlUpd = array();

	$sqlUpd[] = "ALTER IGNORE TABLE `orders_settings` CHANGE `id` `id` int(10) unsigned NOT NULL auto_increment;";

	return $sqlUpd;
}

/*
 * Fix for ticket #1196 http://www.isp-control.net/ispcp/ticket/1196 (Benedikt Heintel, 2008-04-23)
 */
function _databaseUpdate_4() {
	$sqlUpd = array();

	$sqlUpd[] = "ALTER IGNORE TABLE `mail_users` CHANGE `mail_auto_respond` `mail_auto_respond_text` text collate utf8_unicode_ci;";
	$sqlUpd[] = "ALTER IGNORE TABLE `mail_users` ADD `mail_auto_respond` BOOL NOT NULL default '0' AFTER `status`;";
	$sqlUpd[] = "ALTER IGNORE TABLE `mail_users` CHANGE `mail_type` `mail_type` varchar(30);";

	return $sqlUpd;
}

/*
 * Fix for ticket #1346 http://www.isp-control.net/ispcp/ticket/1346 (Benedikt Heintel, 2008-06-13)
 */
function _databaseUpdate_5() {
	$sqlUpd = array();

	$sqlUpd[] = "ALTER IGNORE TABLE `sql_user` CHANGE `sqlu_name` `sqlu_name` varchar(64) binary DEFAULT 'n/a';";
	$sqlUpd[] = "ALTER IGNORE TABLE `sql_user` CHANGE `sqlu_pass` `sqlu_pass` varchar(64) binary DEFAULT 'n/a';";

	return $sqlUpd;
}
?>