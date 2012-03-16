<?php
/**
 * i-MSCP - internet Multi Server Control Panel
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
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Client
 * @copyright	2010-2012 by i-MSCP team
 * @author		iMSCP Team
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license		http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/************************************************************************************
 * Script short description:
 *
 * This script allows AjaxPlorer authentication from i-MSCP (onClick login)
 *
 */

/************************************************************************************
 *  Script functions
 */

/**
 * Get ftp login credentials.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @access private
 * @param  int $userId FTP User
 * @return array Array that contains login credentials or FALSE on failure
 */
function _getLoginCredentials($userId)
{
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

	if ($stmt->rowCount()) {
		return array(
			$stmt->fields['userid'],
			$stmt->fields['rawpasswd']
		);
	} else {
		return false;
	}
}

/**
 * Creates all cookies for AjaxPlorer.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @access private
 * @param  array $cookies Array that contains cookies definitions for ajaxplorer
 * @return void
 */
function _ajaxplorerCreateCookies($cookies)
{
	foreach ($cookies as $cookie) {
		header("Set-Cookie: $cookie", false);
	}
}

/**
 * AjaxPlorer authentication.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @param  int $userId ftp username
 * @return bool TRUE on success, FALSE otherwise
 */
function _ajaxplorerAuth($userId)
{
	$credentials = _getLoginCredentials($userId);

	if ($credentials) {
		$data = http_build_query(
			array(
				'userid' => $credentials[0],
				'password' => stripcslashes($credentials[1]),
				'get_action' => 'login',
				'login_seed' => '-1',
				'_method' => 'put',
				"remember_me" => ''
			)
		);
	} else {
		set_page_message(tr('Unknown FTP user id.'), 'error');
		return false;
	}

	// Prepares AjaxPlorer absolute Uri to use
	if (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS'])) {
		$port = ($_SERVER['SERVER_PORT'] != '443') ? ':' . $_SERVER['SERVER_PORT'] : '';
		$ajaxplorerUri = "https://{$_SERVER['SERVER_NAME']}$port/ftp/";
	} else {
		$port = ($_SERVER['SERVER_PORT'] != '80') ? ':' . $_SERVER['SERVER_PORT'] : '';
		$ajaxplorerUri = "http://{$_SERVER['SERVER_NAME']}$port/ftp/";
	}

	// AjaxPlorer session initialization

	stream_context_get_default(
		array(
			'http' => array(
				'method' => 'HEAD',
				'header' => "Host: {$_SERVER['SERVER_NAME']}\r\n" .
					"Connection: close\r\n\r\n",
				'user_agent' => $_SERVER["HTTP_USER_AGENT"],
			)
		)
	);

	$headers = get_headers($ajaxplorerUri, true);

	// AjaxPlorer secure token

	stream_context_get_default(
		array(
			'http' => array(
				'method' => 'GET',
				'header' => "Host: {$_SERVER['SERVER_NAME']}\r\n" .
					"Connection: close\r\n" .
					"Cookie: {$headers['Set-Cookie']}\r\n\r\n",
				'user_agent' => $_SERVER["HTTP_USER_AGENT"]
			)
		)
	);

	$secureToken = file_get_contents("{$ajaxplorerUri}/?action=get_secure_token");

	// AjaxPlorer authentication

	stream_context_get_default(
		array(
			'http' => array(
				'method' => 'POST',
				'header' => "Host: {$_SERVER['SERVER_NAME']}\r\n" .
					"Connection: close\r\n" .
					"Content-Type: application/x-www-form-urlencoded\r\n" .
					"X-Requested-With: XMLHttpRequest\r\n" .
					'Content-Length: ' . strlen($data) . "\r\n" .
					"Cookie: {$headers['Set-Cookie']}\r\n\r\n",
				'content' => $data,
				'user_agent' => $_SERVER["HTTP_USER_AGENT"],
			)
		)
	);

	$headers = get_headers("{$ajaxplorerUri}?secure_token={$secureToken}", true);

	_ajaxplorerCreateCookies($headers['Set-Cookie']);
	header("Location: {$ajaxplorerUri}");

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
if (isset($_GET['id'])) {
	if (!_ajaxplorerAuth($_GET['id'])) {
		redirectTo('ftp_accounts.php');
	}
} else {
	redirectTo('/index.php');
}
