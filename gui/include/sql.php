<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
 *
 * @license
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
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 */

/**
 * Convenience method to execute a query
 *
 * <b>Note:</b> You may pass additional parameters. They will be treated as
 * though you called PDOStatement::setFetchMode() on the resultant statement
 * object that is wrapped by the ispCP_Database_ResultSet object.
 *
 * @see ispCP_Database::execute()
 * @throws ispCP_Exception
 * @param  ispCP_Database $db ispCP_Database instance
 * @param string $query Sql statement to be executed
 * @param array|int|string $parameters OPTIONAL parameters that represents
 * data to bind to the placeholders for prepared statement, or an integer
 * that represents the Fetch mode for Sql statement. The fetch mode must be
 * one of the PDO::FETCH_* constants.
 * @param int|string|object $parameters OPTIONAL parameter for SQL statement
 * only. Can be a colum number, an object, a class name (depending of the
 * Fetch mode used).
 * @param array $parameters OPTIONAL parameter for Sql statements only. Can
 * be an array that contains constructor arguments. (See PDO::FETCH_CLASS)
 * @return ispCP_Database_ResultSet Returns an ispCP_Database_ResultSet object
 **/
function execute_query($db, $query, $parameters = null) {

	if(!is_null($parameters)) {
		$parameters = func_get_args();
		array_shift($parameters);
		$stmt = call_user_func_array(array($db, 'execute'), $parameters);
	} else {
		$stmt = $db->execute($query);
	}

	if ($stmt == false)
		throw new ispCP_Exception_Database($db->getLastErrorMessage());

	return $stmt;
}

/**
 * Convenience method to prepare and execute a query
 *
 * Note: On failure, and if the $failDie parameter is set to TRUE, this function
 * sends a mail to the administrator with some relevant  information such as
 * the debug information if the {@link ispCP_Exception_Writer_Mail writer} is
 * active.
 *
 * @throws ispCP_Exception_Database
 * @param ispCP_Database $db ispCP_Database Instance
 * @param string $query Sql statement
 * @param string|int|array $bind Data to bind to the placeholders
 * @param boolean $failDie If TRUE, throws an ispCP_Exception_Database exception
 * on failure
 * @return ispCP_Database_ResultSet Return a DatabaseResult object that
 * represent a result set or FALSE on failure if $failDie is set to FALSE.
 */
function exec_query($db, $query, $bind = null, $failDie = true) {

	if(!($stmt = $db->prepare($query)) || !($stmt = $db->execute($stmt, $bind))) {
		if($failDie) {
			throw new ispCP_Exception_Database(
				$db->getLastErrorMessage() . " - Query: $query"
			);
		}
	}

	return $stmt;
}

/**
 * Function quoteIdentifier
 * @todo document this function
 */
function quoteIdentifier($identifier) {

	$db = ispCP_Registry::get('Db');

	$identifier = str_replace(
		$db->nameQuote, '\\' . $db->nameQuote, $identifier
	);

	return $db->nameQuote . $identifier . $db->nameQuote;
}
