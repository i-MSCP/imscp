<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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
 * Functions
 */

/**
 * Get PhpMyadmin login credentials
 *
 * @access private
 * @param  int $dbUserId Database user unique identifier
 * @return mixed Array that contains login credentials, FALSE otherwise
 */
function _client_pmaGetLoginCredentials($dbUserId)
{
    $stmt = exec_query(
        '
            SELECT sqlu_name AS pma_username, sqlu_pass AS pma_password FROM sql_user t1
            INNER JOIN domain t2 ON(t2.domain_admin_id = ?)
            INNER JOIN sql_database t3 ON(t3.domain_id = t2.domain_id)
            WHERE t1.sqld_id = t3.sqld_id
            AND t1.sqlu_id = ?
        ',
        array($_SESSION['user_id'], $dbUserId)
    );

    if($stmt->rowCount()) {
        return $stmt->fetchRow(PDO::FETCH_ASSOC);
    }
    
    return array();
}

/**
 * Creates all cookies for PhpMyAdmin
 *
 * @access private
 * @param  array|string $cookies Array or string which contains cookies definitions for PhpMyadmin
 * @return void
 */
function _client_pmaCreateCookies($cookies)
{
    foreach ((array)$cookies as $cookie) {
        header("Set-Cookie: $cookie", false);
    }
}

/**
 * Set PhpMyAdmin language according language set in panel
 *
 * Note: If panel language doesn't match any language available for PMA, language
 * is set to English (en).
 *
 * @access private
 * @param string $location PMA URI location
 * @return string PMA URI location
 */
function _client_pmaSetLanguage($location)
{
    /** @var Zend_Translate_Adapter $translator */
    $translator = iMSCP_Registry::get('translator')->getAdapter();
    $uriComponents = parse_url($location);
    parse_str($uriComponents['query'], $queryParts);
    $queryParts['lang'] = substr($translator->getLocale(), 0, 2);
    $uriComponents['query'] = http_build_query($queryParts);
    return http_build_url($location, $uriComponents);
}

/**
 * PhpMyAdmin authentication
 *
 * @param  int $dbUserId Database user unique identifier
 * @return bool FALSE on faillure
 */
function client_pmaAuth($dbUserId)
{
    $data = _client_pmaGetLoginCredentials($dbUserId);

    if (!$data) {
        set_page_message(tr('Unknown SQL user'), 'error');
        return false;
    }

    $data['server'] = 1;
    $data['token'] = '';
    $postData = http_build_query($data);
    $pmaBaseUrl = getRequestBaseUrl() . '/pma/';
    $contextOptions = array(
        'http' => array(
            'method' => 'POST',
            'protocol_version' => '1.1',
            'header' => array(
                'Host: ' . getHttpHost(),
                'Content-Type: application/x-www-form-urlencoded',
                'Content-Length: ' . strlen($postData),
                'User-Agent: i-MSCP',
                'Connection: close',
            ),
            'content' => $postData,
            'max_redirects' => 5
        )
    );

    if (isSecureRequest()) {
        $contextOptions['ssl'] = array(
            'verify_peer' => false,
            'allow_self_signed' => true
        );
    }

    stream_context_set_default($contextOptions);

    $headers = get_headers($pmaBaseUrl, true);
    if ($headers && isset($headers['Location'])) {
        _client_pmaCreateCookies($headers['Set-Cookie']);
        redirectTo(_client_pmaSetLanguage(str_replace('./index.php', '/pma/index.php', $headers['Location'])));
    }

    set_page_message(tr('An error occurred during authentication.'), 'error');
    return false;
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);
check_login('user');

if (!customerHasFeature('sql') || !isset($_GET['id'])) {
    showBadRequestErrorPage();
} elseif (!client_pmaAuth((int)$_GET['id'])) {
    redirectTo('sql_manage.php');
}
