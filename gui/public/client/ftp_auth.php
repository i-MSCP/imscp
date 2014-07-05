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
 * @subpackage  Client_Ftp
 * @copyright   2010-2014 by i-MSCP team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
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
function _getLoginCredentials($userId)
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
 * Creates all cookies for Pydio (AjaXplorer)
 *
 * @access private
 * @param  array|string $cookies Array or string which contains cookies definitions for ajaxplorer
 * @return void
 */
function _ajaxplorerCreateCookies($cookies)
{
	foreach ((array)$cookies as $cookie) {
		header("Set-Cookie: $cookie", false);
	}
}

/**
 * Pydio (AjaXplorer) authentication
 *
 * @param  int $userId ftp username
 * @return bool FALSE on failure
 */
function _ajaxplorerAuth($userId)
{
	if (file_exists(GUI_ROOT_DIR . '/data/tmp/failedAJXP.log')) {
		@unlink(GUI_ROOT_DIR . '/data/tmp/failedAJXP.log');
	}

	$credentials = _getLoginCredentials($userId);

	if (!$credentials) {
		set_page_message(tr('Unknown FTP user.'), 'error');
		return false;
	}

	$contextOptions = array();

	// Prepares Pydio (AjaXplorer) absolute Uri to use
	if (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS'])) {
		$port = ($_SERVER['SERVER_PORT'] != '443') ? ':' . $_SERVER['SERVER_PORT'] : '';
		$ajaxplorerUri = "https://{$_SERVER['SERVER_NAME']}$port/ftp/";

		$contextOptions = array(
			'ssl' => array(
				'verify_peer' => false,
				'allow_self_signed' => true
			)
		);
	} else {
		$port = ($_SERVER['SERVER_PORT'] != '80') ? ':' . $_SERVER['SERVER_PORT'] : '';
		$ajaxplorerUri = "http://{$_SERVER['SERVER_NAME']}$port/ftp/";
	}

	// Pydio (AjaXplorer) authentication

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
	$secureToken = file_get_contents("$ajaxplorerUri/index.php?action=get_secure_token", false, $context);

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
	$headers = get_headers("{$ajaxplorerUri}?secure_token={$secureToken}", true);

	_ajaxplorerCreateCookies($headers['Set-Cookie']);

	redirectTo($ajaxplorerUri);

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

if (!customerHasFeature('ftp') || !(isset($cfg['FILEMANAGER_ADDON']) && $cfg['FILEMANAGER_ADDON'] == 'AjaXplorer')) {
	showBadRequestErrorPage();
}

if (isset($_GET['id'])) {
	if (!_ajaxplorerAuth(clean_input($_GET['id']))) {
		redirectTo('ftp_accounts.php');
	}
} else {
	redirectTo('/index.php');
}
