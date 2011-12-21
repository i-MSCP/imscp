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
 * @author      William Lightning <kassah@gmail.com>
 * @link        http://i-mscp.net i-MSCP Home Site
 * @license     http://www.mozilla.org/MPL/ MPL 1.1
 */

/************************************************************************************
 * Script short description:
 *
 * This script allows net2ftp authentication from i-MSCP
 *
 * Borrowed heavily from client/pma_auth.php by Laurent Declercq <l.declercq@nuxwin.com>
 */

/************************************************************************************
 *  Script functions
 */

/**
 * Get ftp login credentials.
 *
 * @author William Lightning <kassah@gmail.com>
 * @access private
 * @param  int $userId FTP User
 * @return array Array that contains login credentials or FALSE on failure
 */
function _getLoginCredentials($userId)
{
	// @todo Should be optimized
	$query = "
		SELECT
			`userid`, `rawpasswd`
		FROM
			`ftp_users`, `domain`
		WHERE
			`ftp_users`.`uid` = `domain`.`domain_uid`
		AND
			`ftp_users`.`userid` = ?
		AND
			`domain`.`domain_admin_id` = ?
	";
	$stmt = exec_query($query, array($userId, $_SESSION['user_id']));

	if($stmt->rowCount() == 1) {
		return array(
			$stmt->fields['userid'],
			$stmt->fields['rawpasswd']
		);
	} else {
		return false;
	}
}

/**
 * Creates all cookies for net2ftp.
 *
 * @author William Lightning <kassah@gmail.com>
 * @access private
 * @param  array $cookies Array that contains cookies definitions for net2ftp
 * @return void
 */
function _net2ftpCreateCookies($cookies)
{
	foreach($cookies as $cookie) {
		header("Set-Cookie: $cookie", false);
	}
}

/**
 * net2ftp authentication.
 *
 * @author William Lightning <kassah@gmail.com>
 * @param  int $userId ftp username
 * @return bool TRUE on success, FALSE otherwise
 */
function net2ftpAuth($userId)
{
	$credentials = _getLoginCredentials($userId);

	if($credentials) {
		$data = http_build_query(
			array(
				'username' => $credentials[0],
				'password' => stripcslashes($credentials[1]),
				'ftpserver' => 'localhost',
				'ftpserverport' => '21',
				'directory' => '',
				'language' => 'en',
				'ftpmode' => 'automatic',
				'state' => 'browse',
				'state2' => 'main'));
	} else {
		set_page_message(tr('Unknown FTP user id.'), 'error');
		return false;
	}

	// Prepares Net2FTP absolute Uri to use
	if(isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS'])) {
		$port = ($_SERVER['SERVER_PORT'] != '443') ? ':' . $_SERVER['SERVER_PORT'] : '';
		$net2ftpUri = "https://{$_SERVER['SERVER_NAME']}$port/ftp/";
	} else {
		$port = ($_SERVER['SERVER_PORT'] != '80') ? ':' . $_SERVER['SERVER_PORT'] : '';
		$net2ftpUri = "http://{$_SERVER['SERVER_NAME']}$port/ftp/";
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
				'user_agent' => $_SERVER["HTTP_USER_AGENT"],
				'max_redirects' => 1)));

	// Gets the headers from Net2FTP
	$headers = get_headers($net2ftpUri, true);

	// Absolute minimum I could get a listing with.
	$url = $net2ftpUri.'?ftpserver=localhost&username='.urlencode($userId).'&state=browse&state2=main';

	_net2ftpCreateCookies($headers['Set-Cookie']);
	header("Location: {$url}");

	return true;
}

/************************************************************************************
 * Main script
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

// Check login
check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('ftp')) {
    redirectTo('index.php');
}

/**
 *  Dispatches the request
 */
if(isset($_GET['id'])) {
	if(!net2ftpAuth($_GET['id'])) {
		redirectTo('ftp_accounts.php');
	}
} else {
	redirectTo('/index.php');
}
