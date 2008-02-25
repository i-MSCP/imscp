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
	$rs = exec_query($sql, $query, array('DATABASE_REVISION', '1'));
}
?>
