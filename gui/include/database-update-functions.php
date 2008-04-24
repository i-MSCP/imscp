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
 * Select the current revision from the database and return it
 */
function getCurrentRevision() {
	global $sql;

	$query = "SELECT * FROM `config` WHERE `name` = 'DATABASE_REVISION'";
 	$rs = execute_query($sql, $query);
	$current_revision = (int)$rs->fields['value'];

	return $current_revision;
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
 * Check for existenz of a available update
 */
function checkNewRevisionExists() {
	$functionName = returnFunctionName(getNextRevision());

	if(function_exists($functionName))
		return true;
	else
		return false;
}

/*
 * Change the database revision to $newRevision
 */
function setDatabaseRevision($newRevision) {
	global $sql;

	$query = "UPDATE config SET value = ? WHERE name = ?";
	$rs = exec_query($sql, $query, array($newRevision, "DATABASE_REVISION"));
}

/*
 * Combine the needed function name, and return it
 */
function returnFunctionName($revision) {
	return $functionName = "_databaseUpdate_" . $revision;
}

/*
 * Execute all available update functions
 */
function executeDatabaseUpdates() {
	while(checkNewRevisionExists()) {
		$newRevision = getNextRevision();
		$functionName = returnFunctionName($newRevision, true);

		if(function_exists($functionName)) {
			$rs = $functionName();
			setDatabaseRevision($newRevision);
		}
	}
}

/*
 * Insert the update functions below this entry please. The revision should be ascending.
 * Don't insert a update twice!
 */

/*
 * Initital Update. Insert the Revision.
 */
function _databaseUpdate_1() {
	global $sql;

	$query = "INSERT INTO config (name, value) VALUES (? , ?)";
	exec_query($sql, $query, array('DATABASE_REVISION', '1'));
}


/*
 * Updates the database fields ispcp.mail_users.mail_addr to the right mail address
 * written by Christian Hernmarck, Feb 2008
 * Since it does not delete or add any field, it may be run several times...
 */
function _databaseUpdate_2() {
	global $sql; // we need the gloabl database connection

	$sqlUpd = array(); // we need several SQL Statements...

	// domain mail + forward
	$sqlUpd[] = "UPDATE `mail_users`, `domain`
		SET `mail_addr` = CONCAT(`mail_acc`,'@',`domain_name`)
		WHERE `mail_users`.`domain_id` = `domain`.`domain_id`
			AND (`mail_type` = 'normal_mail' OR `mail_type` = 'normal_forward')";

	// domain-alias mail + forward
	$sqlUpd[] = "UPDATE `mail_users`, `domain_aliasses`
		SET `mail_addr` = CONCAT(`mail_acc`,'@',`alias_name`)
		WHERE `mail_users`.`domain_id` = `domain_aliasses`.`domain_id` AND `mail_users`.`sub_id` = `domain_aliasses`.`alias_id`
			AND (`mail_type` = 'alias_mail' OR `mail_type` = 'alias_forward')";

	// subdomain mail + forward
	$sqlUpd[] = "UPDATE `mail_users`, `subdomain`, `domain`
		SET `mail_addr` = CONCAT(`mail_acc`,'@',`subdomain_name`,'.',`domain_name`)
		WHERE `mail_users`.`domain_id` = `subdomain`.`domain_id` AND `mail_users`.`sub_id` = `subdomain`.`subdomain_id`
			AND `mail_users`.`domain_id` = `domain`.`domain_id`
			AND (`mail_type` = 'subdom_mail' OR `mail_type` = 'subdom_forward')";

	// domain catchall
	$sqlUpd[] = "UPDATE `mail_users`, `domain`
		SET `mail_addr` = CONCAT('@',`domain_name`)
		WHERE `mail_users`.`domain_id` = `domain`.`domain_id`
			AND `mail_type` = 'normal_catchall'";

	// domain-alias catchall
	$sqlUpd[] = "UPDATE `mail_users`, `domain_aliasses`
		SET `mail_addr` = CONCAT('@',`alias_name`)
		WHERE `mail_users`.`domain_id` = `domain_aliasses`.`domain_id` AND `mail_users`.`sub_id` = `domain_aliasses`.`alias_id`
			AND `mail_type` = 'alias_catchall'";

	// subdomain catchall
	$sqlUpd[] = "UPDATE `mail_users`, `subdomain`, `domain`
		SET `mail_addr` = CONCAT('@',`subdomain_name`,'.',`domain_name`)
		WHERE `mail_users`.`domain_id` = `subdomain`.`domain_id` AND `mail_users`.`sub_id` = `subdomain`.`subdomain_id`
			AND `mail_users`.`domain_id` = `domain`.`domain_id`
			AND `mail_type` = 'subdom_catchall'";

	// go for it: run them all
	foreach($sqlUpd as $s) {
		$sql->Execute($s);
	}

} // end of _databaseUpdate_2

/*
 * Fix for ticket #1139 http://www.isp-control.net/ispcp/ticket/1139 (Benedikt Heintel, 2008-03-27)
 * Fix for ticket #1196 http://www.isp-control.net/ispcp/ticket/1196 (Benedikt Heintel, 2008-04-23)
 */
function _databaseUpdate_3() {
	global $sql; // we need the gloabl database connection

	// Ticket #1139
	$sqlUpd[] = "ALTER IGNORE TABLE `orders_settings` CHANGE `id` `id` int(10) unsigned NOT NULL auto_increment;";

	// Ticket #1196
	$sqlUpd[] = "ALTER IGNORE TABLE `mail_users` CHANGE `mail_auto_respond` `mail_auto_respond_text` text collate utf8_unicode_ci;";
	$sqlUpd[] = "ALTER IGNORE TABLE `mail_users` ADD `mail_auto_respond` BOOL NOT NULL default '0' AFTER `status`;";
	$sqlUpd[] = "ALTER IGNORE TABLE `mail_users` CHANGE `mail_type` `mail_type` varchar(30);";

	// go for it: run them all
	foreach($sqlUpd as $s) {
		$sql->Execute($s);
	}

} // end of _databaseUpdate_3
?>