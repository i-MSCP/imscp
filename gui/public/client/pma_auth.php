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
 * The Original Code is i-MSCP - Multi Server Control Panel.
 *
 * The Initial Developer of the Original Code is i-MSCP Team.
 * Portions created by Initial Developer are Copyright (C) 2010-2011
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    iMSCP
 * @package		iMSCP_Core
 * @subpackage  Client
 * @copyright   2010-2011 by ispCP | http://i-mscp.net
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://i-mscp.net i-MSCP Home Site
 * @license     http://www.mozilla.org/MPL/ MPL 1.1
 */

/***********************************************************************************************************************
 * Script short description:
 *
 * This script allows PhpMyAdmin authentication from i-MSCP
 */

/*******************************************************************************
 * Script functions
 */

/**
 * Get database login credentials.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @access private
 * @param  int $dbUserId Database user unique identifier
 * @return array Array that contains login credentials or FALSE on failure
 */
function _getLoginCredentials($dbUserId)
{
	// @todo Should be optimized
	$query = "
		SELECT
			`sqlu_name`, `sqlu_pass`
		FROM
			`sql_user`, `sql_database`, `domain`
		WHERE
			`sql_user`.`sqld_id` = `sql_database`.`sqld_id`
		AND
			`sql_user`.`sqlu_id` = ?
		AND
			`sql_database`.`domain_id` = `domain`.`domain_id`
		AND
			`domain`.`domain_admin_id` = ?
	";
	$stmt = exec_query($query, array($dbUserId, $_SESSION['user_id']));

	if($stmt->rowCount() == 1) {
		return array(
			$stmt->fields['sqlu_name'],
			$stmt->fields['sqlu_pass']);
	} else {
		return false;
	}
}

/**
 * Creates all cookies for PhpMyAdmin.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @access private
 * @param  array $cookies Array that contains cookies definitions for PhpMyadmin
 * @return void
 */
function _pmaCreateCookies($cookies)
{
	foreach($cookies as $cookie) {
		header("Set-Cookie: $cookie", false);
	}
}

/**
 * Set PhpMyAdmin language according language set in panel.
 *
 * Note: If panel language doesn't match any language available for PMA, language
 * is set to English (en).
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @access private
 * @param string $location PMA URI location
 * @return string PMA URI location
 */
function _pmaSetLanguage($location)
{
	$uriComponents = parse_url($location);
	parse_str($uriComponents['query'], $queryParts);
	$queryParts['lang'] = substr($_SESSION['user_def_lang'], 0, 2);
	$uriComponents['query'] = http_build_query($queryParts);

	return http_build_url($location, $uriComponents);
}

/**
 * PhpMyAdmin authentication.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @param  int $dbUserId Database user unique identifier
 * @return bool FALSE on faillure
 */
function pmaAuth($dbUserId)
{
	$credentials = _getLoginCredentials($dbUserId);

	if($credentials) {
		$data = http_build_query(
			array(
				'pma_username' => $credentials[0],
				'pma_password' => stripcslashes($credentials[1])));
	} else {
		set_page_message(tr('Unknown SQL user id.'), 'error');
		return false;
	}

	// Prepares PhpMyadmin absolute Uri to use
	if(isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS'])) {
		$port = ($_SERVER['SERVER_PORT'] != '443') ? ':' . $_SERVER['SERVER_PORT'] : '';
		$pmaUri = "https://{$_SERVER['SERVER_NAME']}$port/pma/";
	} else {
		$port = ($_SERVER['SERVER_PORT'] != '80') ? ':' . $_SERVER['SERVER_PORT'] : '';
		$pmaUri = "http://{$_SERVER['SERVER_NAME']}$port/pma/";
	}

	// Set stream context (http) options
	stream_context_get_default(
		array(
			'http' => array(
				'method' => 'POST',
				'header' => "Host: {$_SERVER['SERVER_NAME']}$port\r\n" .
					"Content-Type: application/x-www-form-urlencoded\r\n" .
					'Content-Length: ' . strlen($data) . "\r\n" .
					"Connection: close\r\n\r\n",
				'content' => $data,
				'user_agent' => 'Mozilla/5.0',
				'max_redirects' => 1)));

	// Gets the headers from PhpMyAdmin
	$headers = get_headers($pmaUri, true);

	if($headers && isset($headers['Location'])) {
		_pmaCreateCookies($headers['Set-Cookie']);
        redirectTo(_pmaSetLanguage($headers['Location']));
	}

	set_page_message(tr('An error occurred while the authentication attempt.'), 'error');
	return false;
}

/***********************************************************************************************************************
 * Main program
 */

// Include all needed libraries
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

// Check for login
check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('sql')) {
    redirectTo('index.php');
}

/**
 *  Dispatches the request
 */
if(isset($_GET['id'])) {
	if(!pmaAuth((int) $_GET['id'])) {
		redirectTo('sql_manage.php');
	}
} else {
	redirectTo('/index.php');
}
