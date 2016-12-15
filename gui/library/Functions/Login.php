<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2016 by i-MSCP Team <team@i-mscp.net>
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

/**
 * Initialize login
 *
 * @param iMSCP_Events_Manager_Interface $eventManager Events Manager
 * @return void
 */
function init_login($eventManager)
{
    // Purge expired sessions
    do_session_timeout();

    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    if ($cfg['BRUTEFORCE']) {
        $bruteforce = new iMSCP_Plugin_Bruteforce(iMSCP_Registry::get('pluginManager'));
        $bruteforce->register($eventManager);
    }

    // Register default authentication handler with high-priority
    $eventManager->registerListener(iMSCP_Events::onAuthentication, 'login_credentials', 99);
    // Register listener that is responsible to check domain status and expire date
    $eventManager->registerListener(iMSCP_Events::onBeforeSetIdentity, 'login_checkDomainAccount');
}

/**
 * Credentials authentication handler
 *
 * @throws iMSCP_Exception_Database
 * @param iMSCP_Authentication_AuthEvent
 * @return void
 */
function login_credentials(iMSCP_Authentication_AuthEvent $authEvent)
{
    $username = (!empty($_POST['uname'])) ? encode_idna(clean_input($_POST['uname'])) : '';
    $password = (!empty($_POST['upass'])) ? clean_input($_POST['upass']) : '';

    if ($username === '' || $password === '') {
        $message = array();

        if (empty($username)) {
            $message[] = tr('The username field is empty.');
        }

        if (empty($password)) {
            $message[] = tr('The password field is empty.');
        }

        $authEvent->setAuthenticationResult(new iMSCP_Authentication_Result(
            (count($message) == 2)
                ? iMSCP_Authentication_Result::FAILURE_CREDENTIAL_EMPTY
                : iMSCP_Authentication_Result::FAILURE_CREDENTIAL_INVALID
            ,
            NULL,
            $message
        ));
        return;
    }

    $stmt = exec_query(
        'SELECT admin_id, admin_name, admin_pass, admin_type, email, created_by FROM admin WHERE admin_name = ?',
        $username
    );

    if (!$stmt->rowCount()) {
        $authEvent->setAuthenticationResult(new iMSCP_Authentication_Result(
            iMSCP_Authentication_Result::FAILURE_IDENTITY_NOT_FOUND, NULL, tr('Unknown username.')
        ));
        return;
    }

    $identity = $stmt->fetchRow(PDO::FETCH_OBJ);

    if (!\iMSCP\Crypt::hashEqual(md5($password), $identity->admin_pass) && !\iMSCP\Crypt::verify($password, $identity->admin_pass)) {
        $authEvent->setAuthenticationResult(new iMSCP_Authentication_Result(
            iMSCP_Authentication_Result::FAILURE_CREDENTIAL_INVALID, NULL, tr('Bad password.')
        ));
        return;
    }

    if (strpos($identity->admin_pass, '$apr1$') !== 0) { # Not an APR-1 hashed password, we recreate the hash
        // We must postpone update until the onAfterAuthentication event to handle cases where the authentication process
        // fail later on (case of a multi-factor authentication process)
        iMSCP_Events_Aggregator::getInstance()->registerListener(
            iMSCP_Events::onAfterAuthentication,
            function (iMSCP_Events_Event $event) use ($password) {
                /** @var iMSCP_Authentication_Result $authResult */
                $authResult = $event->getParam('authResult');

                if (!$authResult->isValid()) {
                    return;
                }

                $identity = $authResult->getIdentity();

                exec_query('UPDATE admin SET admin_pass = ?, admin_status = ? WHERE admin_id = ?', array(
                    \iMSCP\Crypt::apr1MD5($password), ($identity->admin_type) == 'user' ? 'tochangepwd' : 'ok',
                    $identity->admin_id
                ));

                write_log(sprintf('Password for user %s has been re-encrypted using APR-1 algorithm', $identity->admin_name), E_USER_NOTICE);

                if ($identity->admin_type == 'user') {
                    send_request();
                }
            },
            array('password' => $password, 'identity' => $identity)
        );
    }

    $authEvent->setAuthenticationResult(new iMSCP_Authentication_Result(iMSCP_Authentication_Result::SUCCESS, $identity));
}

/**
 * Check domain account state (status and expires date)
 *
 * Note: Listen to the onBeforeSetIdentity event triggered in the iMSCP_Authentication component.
 *
 * @param iMSCP_Events_Event $event An iMSCP_Events_Events object representing an onBeforeSetIdentity event.
 * @return void
 */
function login_checkDomainAccount($event)
{
    /** @var $identity stdClass */
    $identity = $event->getParam('identity');

    if ($identity->admin_type == 'user') {
        $stmt = exec_query(
            '
              SELECT domain_expires, domain_status, admin_status
              FROM domain
              INNER JOIN admin ON(domain_admin_id = admin_id)
              WHERE domain_admin_id = ?
            ',
            $identity->admin_id
        );

        $isAccountStateOk = true;

        if (($stmt->fields['admin_status'] != 'ok') || ($stmt->fields['domain_status'] != 'ok')) {
            $isAccountStateOk = false;
            set_page_message(tr('Your account is currently under maintenance or disabled. Please, contact your reseller.'), 'error');
        } else {
            $domainExpireDate = $stmt->fields['domain_expires'];
            if ($domainExpireDate && $domainExpireDate < time()) {
                $isAccountStateOk = false;
                set_page_message(tr('Your account has expired.'), 'error');
            }
        }

        if (!$isAccountStateOk) {
            redirectTo('index.php');
        }
    }
}

/**
 * Session garbage collector
 *
 * @return void
 */
function do_session_timeout()
{
    $cfg = iMSCP_Registry::get('config');
    // We must not remove bruteforce plugin data (AND `user_name` IS NOT NULL)
    exec_query('DELETE FROM login WHERE lastaccess < ? AND user_name IS NOT NULL', time() - $cfg['SESSION_TIMEOUT'] * 60);
}

/**
 * Check login
 *
 * @param string $userLevel User level (admin|reseller|user)
 * @param bool $preventExternalLogin If TRUE, external login is disallowed
 */
function check_login($userLevel, $preventExternalLogin = true)
{
    do_session_timeout();
    $auth = iMSCP_Authentication::getInstance();

    if (!$auth->hasIdentity()) {
        $auth->unsetIdentity(); // Ensure deletion of all entity data

        if (is_xhr()) {
            showForbiddenErrorPage();
        }

        redirectTo('/index.php');
    }

    $cfg = iMSCP_Registry::get('config');
    $identity = $auth->getIdentity();

    // When the panel is in maintenance mode, only administrators can access the interface
    if ($cfg['MAINTENANCEMODE'] && $identity->admin_type != 'admin'
        && (!isset($_SESSION['logged_from_type']) || $_SESSION['logged_from_type'] != 'admin')
    ) {
        $auth->unsetIdentity();
        redirectTo('/index.php');
    }

    // Check user level
    if (empty($userLevel) || ($userLevel !== 'all' && $identity->admin_type != $userLevel)) {
        $auth->unsetIdentity();
        redirectTo('/index.php');
    }

    // prevent external login / check for referer
    if ($preventExternalLogin
        && !empty($_SERVER['HTTP_REFERER'])
        && ($fromHost = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST))
        && $fromHost !== getRequestHost()
    ) {
        $auth->unsetIdentity();
        showForbiddenErrorPage();
    }

    // If all goes fine update session and lastaccess
    $_SESSION['user_login_time'] = time();
    exec_query('UPDATE login SET lastaccess = ? WHERE session_id = ?', array($_SESSION['user_login_time'], session_id()));
}

/**
 * Switch between user's interfaces
 *
 * @param int $fromId User ID to switch from
 * @param int $toId User ID to switch on
 * @return void
 */
function change_user_interface($fromId, $toId)
{
    $toActionScript = false;

    while (1) { // We loop over nothing here, it's just a way to avoid code repetition
        $stmt = exec_query(
            '
              SELECT admin_id, admin_name, admin_type, email, created_by
              FROM admin
              WHERE admin_id IN(?, ?)
              ORDER BY FIELD(admin_id, ?, ?)
              LIMIT 2
            ',
            array($fromId, $toId, $fromId, $toId)
        );

        if ($stmt->rowCount() < 2) {
            set_page_message(tr('Wrong request.'), 'error');
        }

        list($from, $to) = $stmt->fetchAll(PDO::FETCH_OBJ);

        $fromToMap = array();
        $fromToMap['admin']['BACK'] = 'manage_users.php';
        $fromToMap['admin']['reseller'] = 'index.php';
        $fromToMap['admin']['user'] = 'index.php';
        $fromToMap['reseller']['user'] = 'index.php';
        $fromToMap['reseller']['BACK'] = 'users.php';

        if (!isset($fromToMap[$from->admin_type][$to->admin_type]) || ($from->admin_type == $to->admin_type)) {
            if (isset($_SESSION['logged_from_id']) && $_SESSION['logged_from_id'] == $to->admin_id) {
                $toActionScript = $fromToMap[$to->admin_type]['BACK'];
            } else {
                set_page_message(tr('Wrong request.'), 'error');
                write_log(
                    sprintf("%s tried to switch onto %s's interface", $from->admin_name, decode_idna($to->admin_name)),
                    E_USER_WARNING
                );
                break;
            }
        }

        $toActionScript = ($toActionScript) ? $toActionScript : $fromToMap[$from->admin_type][$to->admin_type];

        // Set new identity
        $auth = iMSCP_Authentication::getInstance();
        $auth->unsetIdentity();
        $auth->setIdentity($to);

        if ($from->admin_type != 'user' && $to->admin_type != 'admin') {
            // Set additional data about user from wich we are logged from
            $_SESSION['logged_from_type'] = $from->admin_type;
            $_SESSION['logged_from'] = $from->admin_name;
            $_SESSION['logged_from_id'] = $from->admin_id;
            write_log(sprintf("%s switched onto %s's interface", $from->admin_name, decode_idna($to->admin_name)), E_USER_NOTICE);
        } else {
            write_log(sprintf("%s switched back from %s's interface", $to->admin_name, decode_idna($from->admin_name)), E_USER_NOTICE);
        }

        break;
    }

    redirectToUiLevel($toActionScript);
}

/**
 * Redirects to user ui level
 *
 * @throws iMSCP_Exception in case ui level is unknow
 * @param string $actionScript Action script on which user should be redirected
 * @return void
 */
function redirectToUiLevel($actionScript = 'index.php')
{
    $auth = iMSCP_Authentication::getInstance();

    if (!$auth->hasIdentity()) {
        return;
    }

    switch ($auth->getIdentity()->admin_type) {
        case 'user':
            $userType = 'client';
            break;
        case 'admin':
            $userType = 'admin';
            break;
        case 'reseller':
            $userType = 'reseller';
            break;
        default:
            throw new iMSCP_Exception('Unknown UI level');
    }

    // Prevents display of any old message when switching to another user level
    Zend_Session::namespaceUnset('pageMessages');
    redirectTo('/' . $userType . '/' . $actionScript);
}
