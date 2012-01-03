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
 * @category    iMSCP
 * @package     iMSCP_Core
 * @subpackage  Client
 * @copyright   2010-2012 by i-MSCP team
 * @author      iMSCP Team
 * @author      William Lightning <kassah@gmail.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
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
