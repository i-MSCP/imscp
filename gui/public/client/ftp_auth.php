<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2015 by Laurent Declercq <l.declercq@nuxwin.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/***********************************************************************************************************************
 *  Functions
 */

/**
 * Get ftp login credentials
 *
 * @access private
 * @param  int $userId FTP User
 * @return array Array that contains login credentials or FALSE on failure
 */
function _client_pydioGetLoginCredentials($userId)
{
	$query = "
		SELECT
			t1.userid, t1.rawpasswd
		FROM
			ftp_users AS t1
		INNER JOIN
			admin AS t2 ON(t2.admin_sys_uid = t1.uid AND t2.admin_sys_gid = t1.gid)
		WHERE
			t1.userid = ?
		AND
			t2.admin_id = ?
	";
	$stmt = exec_query($query, array($userId, $_SESSION['user_id']));

	return $stmt->fetchRow(PDO::FETCH_NUM);
}

/**
 * Creates all cookies for Pydio
 *
 * @access private
 * @param  array|string $cookies Array or string which contains cookies definitions for Pydio
 * @return void
 */
function _client_pydioCreateCookies($cookies)
{
	foreach ((array)$cookies as $cookie) {
		header("Set-Cookie: $cookie", false);
	}
}

/**
 * Pydio authentication
 *
 * @param  int $userId ftp username
 * @return bool FALSE on failure
 */
function client_pydioAuth($userId)
{
	if (file_exists(GUI_ROOT_DIR . '/data/tmp/failedAJXP.log')) {
		@unlink(GUI_ROOT_DIR . '/data/tmp/failedAJXP.log');
	}

	$credentials = _client_pydioGetLoginCredentials($userId);

	if (!$credentials) {
		set_page_message(tr('Unknown FTP user.'), 'error');
		return false;
	}

	$contextOptions = array();

	// Prepares Pydio absolute Uri to use
	if (isSecureRequest()) {
		$contextOptions = array(
			'ssl' => array(
				'verify_peer' => false,
				'allow_self_signed' => true
			)
		);
	}

	$pydioBaseUrl = getBaseUrl() . '/ftp/';
	$port = getUriPort();

	// Pydio authentication

	$context = stream_context_create(
		array_merge($contextOptions, array(
			'http' => array(
				'method' => 'GET',
				'protocol_version' => '1.1',
				'header' => array(
					'Host: ' . $_SERVER['SERVER_NAME'] . $port,
					'User-Agent: i-MSCP',
					'Connection: close'
				)
			)
		))
	);

	# Getting secure token
	$secureToken = file_get_contents("$pydioBaseUrl/index.php?action=get_secure_token", false, $context);

	$postData = http_build_query(
		array(
			'get_action' => 'login',
			'userid' => $credentials[0],
			'login_seed' => '-1',
			"remember_me" => 'false',
			'password' => stripcslashes($credentials[1]),
			'_method' => 'put'
		)
	);

	$contextOptions = array_merge($contextOptions, array(
		'http' => array(
			'method' => 'POST',
			'protocol_version' => '1.1',
			'header' => array(
				'Host: ' . $_SERVER['SERVER_NAME'] . $port,
				'Content-Type: application/x-www-form-urlencoded',
				'X-Requested-With: XMLHttpRequest',
				'Content-Length: ' . strlen($postData),
				'User-Agent: i-MSCP',
				'Connection: close'
			),
			'content' => $postData
		)
	));

	stream_context_set_default($contextOptions);

	# TODO Parse the full response and display error message on authentication failure
	$headers = get_headers("{$pydioBaseUrl}?secure_token={$secureToken}", true);

	_client_pydioCreateCookies($headers['Set-Cookie']);

	redirectTo($pydioBaseUrl);

	exit;
}

/***********************************************************************************************************************
 * Main
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

/** @var $cg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if (!customerHasFeature('ftp') || !(isset($cfg['FILEMANAGER_PACKAGE']) && $cfg['FILEMANAGER_PACKAGE'] == 'Pydio')) {
	showBadRequestErrorPage();
}

if (isset($_GET['id'])) {
	if (!client_pydioAuth(clean_input($_GET['id']))) {
		redirectTo('ftp_accounts.php');
	}
} else {
	redirectTo('/index.php');
}
