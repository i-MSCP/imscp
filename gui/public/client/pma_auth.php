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
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @author      iMSCP Team
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
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
 * Get PhpMyadmin login credentials.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @access private
 * @param  int $dbUserId Database user unique identifier
 * @return mixed Array that contains login credentials, FALSE otherwise
 */
function _client_pmaGetLoginCredentials($dbUserId)
{
	$query = "
		SELECT
			`sqlu_name`, `sqlu_pass`
		FROM
			`sql_user` `t1`
		INNER JOIN
			`domain` `t2` ON(`t2`.`domain_admin_id` = ?)
		INNER JOIN
			`sql_database` `t3` ON(`t3`.`domain_id` = `t2`.`domain_id`)
		WHERE
			`t1`.`sqld_id` = `t3`.`sqld_id`
		AND
			`t1`.`sqlu_id` = ?
	";
	$stmt = exec_query($query, array((int)$_SESSION['user_id'], $dbUserId));

	return $stmt->fetchRow(PDO::FETCH_NUM);
}

/**
 * Creates all cookies for PhpMyAdmin.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @access private
 * @param  array $cookies Array that contains cookies definitions for PhpMyadmin
 * @return void
 */
function _client_pmaCreateCookies($cookies)
{
	foreach ($cookies as $cookie) {
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
function _client_pmaSetLanguage($location)
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
function client_pmaAuth($dbUserId)
{
	$credentials = _client_pmaGetLoginCredentials($dbUserId);

	if ($credentials) {
		$httpQuery = http_build_query(
			array(
				'pma_username' => $credentials[0],
				'pma_password' => stripcslashes($credentials[1])));
	} else {
		set_page_message(tr('Wrong SQL user identifier.'), 'error');
		return false;
	}

	// Prepares PhpMyadmin absolute Uri to use
	if (!empty($_SERVER['HTTPS'])) {
		$port = ($_SERVER['SERVER_PORT'] != '443') ? ':' . $_SERVER['SERVER_PORT'] : '';
		$pmaUri = "https://{$_SERVER['SERVER_NAME']}$port/pma/";
	} else {
		$port = ($_SERVER['SERVER_PORT'] != '80') ? ':' . $_SERVER['SERVER_PORT'] : '';
		$pmaUri = "http://{$_SERVER['SERVER_NAME']}$port/pma/";
	}

	// Set stream context (http) options
	stream_context_set_default(
		array(
			'http' => array(
				'method' => 'POST',
				'header' => "Host: {$_SERVER['SERVER_NAME']}$port\r\n" .
					"Content-Type: application/x-www-form-urlencoded\r\n" .
					'Content-Length: ' . strlen($httpQuery) . "\r\n" .
					"Connection: close\r\n\r\n",
				'content' => $httpQuery,
				'max_redirects' => 1)));

	// Gets the headers from PhpMyAdmin
	$headers = get_headers($pmaUri, true);

	if ($headers && isset($headers['Location'])) {
		_client_pmaCreateCookies($headers['Set-Cookie']);
		redirectTo(_client_pmaSetLanguage($headers['Location']));
	}

	set_page_message(tr('An error occurred while the authentication attempt.'), 'error');
	return false;
}

/***********************************************************************************************************************
 * Main program
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

// Check for login
check_login(__FILE__);

/**
 *  Dispatches the request
 */
if (!customerHasFeature('sql')) {
	redirectTo('index.php');
} elseif (!isset($_GET['id']) || !client_pmaAuth((int)$_GET['id'])) {
	redirectTo('sql_manage.php');
}
