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
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2016 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 */

// Include core library
require_once 'imscp-lib.php';
require_once LIBRARY_PATH . '/Functions/LostPassword.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onLostPasswordScriptStart);
do_session_timeout();

$cfg = iMSCP_Registry::get('config');
if (!$cfg['LOSTPASSWORD']) {
    showNotFoundErrorPage();
}

if (!function_exists('imagecreatetruecolor')) {
    throw new iMSCP_Exception(tr("PHP GD extension not loaded."));
}

removeOldKeys($cfg['LOSTPASSWORD_TIMEOUT']);

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
    'layout' => 'shared/layouts/simple.tpl',
    'page' => 'lostpassword.tpl',
    'page_message' => 'layout'
));
$tpl->assign(array(
    'TR_PAGE_TITLE' => tr('i-MSCP - Multi Server Control Panel / Lost Password'),
    'CONTEXT_CLASS' => '',
    'productLongName' => tr('internet Multi Server Control Panel'),
    'productLink' => 'http://www.i-mscp.net',
    'productCopyright' => tr('© 2010-2016 i-MSCP Team<br>All Rights Reserved'),
    'TR_CAPCODE' => tr('Security code'),
    'GET_NEW_IMAGE' => tr('Get a new security code'),
    'CAPTCHA_WIDTH' => $cfg['LOSTPASSWORD_CAPTCHA_WIDTH'],
    'CAPTCHA_HEIGHT' => $cfg['LOSTPASSWORD_CAPTCHA_HEIGHT'],
    'TR_USERNAME' => tr('Username'),
    'TR_SEND' => tr('Send'),
    'TR_CANCEL' => tr('Cancel')
));

if (isset($_GET['key'])) { // Password request validation
    $key = clean_input($_GET['key']);
    if (sendPassword($key)) {
        set_page_message(tr('Your password has been successfully renewed. Check your emails.'), 'success');
    }

    redirectTo('lostpassword.php');
} elseif (!empty($_POST)) { // Request for new password
    if ($cfg['BRUTEFORCE']) {
        $bruteForce = new iMSCP_Plugin_Bruteforce(iMSCP_Registry::get('pluginManager'), 'captcha');
        if ($bruteForce->isWaiting() || $bruteForce->isBlocked()) {
            set_page_message($bruteForce->getLastMessage(), 'error');
            redirectTo('lostpassword.php');
        }

        $bruteForce->recordAttempt();
    }

    if (isset($_POST['uname']) && isset($_POST['capcode']) && isset($_SESSION['image'])) {
        $uname = clean_input($_POST['uname']);
        $capcode = clean_input($_POST['capcode']);

        if ($_SESSION['image'] !== $capcode) {
            set_page_message(tr('Wrong security code'), 'error');
        } else if (sendPasswordRequestValidation($uname)) {
            set_page_message(tr('Your request for password renewal has been registered. You will receive an email with instructions to complete the process.'), 'success');
            redirectTo('index.php');
        }
    } else {
        set_page_message(tr('All fields are required.'), 'error');
    }

    redirectTo('lostpassword.php');
}

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onLostPasswordScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();
