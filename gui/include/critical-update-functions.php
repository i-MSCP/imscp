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

/*
 * Get the current revision from the database and return it
 */
function getCurrentCriticalRevision() {
	$sql = Database::getInstance();

	$query	= "SELECT * FROM `config` WHERE `name` = 'CRITICAL_UPDATE_REVISION'";
 	$rs		= $sql->Execute($query);

	return (int)$rs->fields['value'];
}

/*
 * Return the current revision + 1
 */
function getNextCriticalRevision() {
	return getCurrentCriticalRevision() + 1;
}

/*
 * Simple check for a new update
 */
function checkCriticalUpdateExists() {
	if(checkNewCriticalRevisionExists())
		return true;
	else
		return false;
}

/*
 * Check for existence of an available update
 */
function checkNewCriticalRevisionExists() {
	$functionName = returnCriticalFunctionName(getNextCriticalRevision());

	if(function_exists($functionName))
		return true;
	else
		return false;
}

/*
 * Combine the needed function name, and return it
 */
function returnCriticalFunctionName($revision) {
	$functionName = "_criticalUpdate_" . $revision;

	return $functionName;
}

/*
 * Execute all available update functions.
 */
function executeCriticalUpdates() {
	$sql = Database::getInstance();
	$failedUpdate = false;

	while(checkNewCriticalRevisionExists()) {
		$engine_run_request=false;
		
		// Get the next database update revision
		$newRevision 	= getNextCriticalRevision();

		// Get the needed function name
		$functionName 	= returnCriticalFunctionName($newRevision);

		// Pull the query from the update function using a variable function
		$queryArray 	= $functionName($engine_run_request);

		// Add the query, to set the new Database Revision, to our queryArray
		$queryArray[]	= "UPDATE `config` SET `value` = '$newRevision' WHERE `name` = 'CRITICAL_UPDATE_REVISION'";

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

		if($engine_run_request){
			check_for_lock_file();
			send_request();
		}

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

function _criticalUpdate_1(&$engine_run_request) {
	$sql = Database::getInstance();
	$status=Config::get('ITEM_CHANGE_STATUS');
	
	setConfig_Value('CRITICAL_UPDATE_REVISION', 1);

	$sqlUpd = array();

	$query ="SELECT `mail_id`, `mail_pass` FROM `mail_users` WHERE `mail_type` RLIKE '^normal_mail' OR `mail_type` RLIKE '^alias_mail' OR `mail_type` RLIKE '^subdom_mail'";
	$rs = exec_query($sql, $query);
	
	if ($rs->RecordCount() != 0) {
		while (!$rs->EOF) {
			$sqlUpd[] = "UPDATE `mail_users` SET `mail_pass`='". encrypt_db_password($rs->fields['mail_pass']). "', `status`='$status' WHERE `mail_id`='". $rs->fields['mail_id'] ."'";
			$rs->MoveNext();
		}
	}
	
	$query ="SELECT `sqlu_id`, `sqlu_pass` FROM `sql_user`";
	$rs = exec_query($sql, $query);
	
	if ($rs->RecordCount() != 0) {
		while (!$rs->EOF) {
			$sqlUpd[] = "UPDATE `sql_user` SET `sqlu_pass` = '". encrypt_db_password($rs->fields['sqlu_pass']). "', `status`='$status' WHERE `sqlu_id`='". $rs->fields['sqlu_id'] ."'";
			$rs->MoveNext();
		}
	}
	
	$engine_run_request=true;
	
	return $sqlUpd;
}

?>