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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2018 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 */

use iMSCP_Events as Events;
use iMSCP_Exception as iMSCPException;
use iMSCP_Plugin_Bruteforce as BruteForcePlugin;
use iMSCP\TemplateEngine;
use iMSCP_Registry as Registry;

require_once 'imscp-lib.php';
require_once LIBRARY_PATH . '/Functions/LostPassword.php';

Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onLostPasswordScriptStart);
do_session_timeout();

$cfg = Registry::get('config');
if (!$cfg['LOSTPASSWORD']) {
    showNotFoundErrorPage();
}

if (!function_exists('imagecreatetruecolor')) {
    throw new iMSCPException(tr('PHP GD extension not loaded.'));
}

removeOldKeys($cfg['LOSTPASSWORD_TIMEOUT']);

if (isset($_GET['key'])) {
    $key = clean_input($_GET['key']);
    if (sendPassword($key)) {
        set_page_message(tr('Your password has been successfully scheduled for renewal. Check your mails.'), 'success');
    }

    redirectTo('index.php');
}

$tpl = new TemplateEngine();
$tpl->define([
    'layout'       => 'shared/layouts/simple.tpl',
    'page'         => 'lostpassword.tpl',
    'page_message' => 'layout'
]);
$tpl->assign([
    'TR_PAGE_TITLE'    => tr('i-MSCP - Multi Server Control Panel / Lost Password'),
    'CONTEXT_CLASS'    => '',
    'productLongName'  => tr('internet Multi Server Control Panel'),
    'productLink'      => 'https://www.i-mscp.net',
    'productCopyright' => tr('© 2010-2018 i-MSCP Team<br>All Rights Reserved'),
    'TR_CAPCODE'       => tr('Security code'),
    'GET_NEW_IMAGE'    => tr('Get a new security code'),
    'CAPTCHA_WIDTH'    => tohtml($cfg['LOSTPASSWORD_CAPTCHA_WIDTH'], 'htmlAttr'),
    'CAPTCHA_HEIGHT'   => tohtml($cfg['LOSTPASSWORD_CAPTCHA_HEIGHT'], 'htmlAttr'),
    'TR_USERNAME'      => tr('Username'),
    'TR_SEND'          => tr('Send'),
    'TR_CANCEL'        => tr('Cancel'),
    'UNAME'            => isset($_POST['uname']) ? $_POST['uname'] : ''
]);

if (!empty($_POST)) {
    if ($cfg['BRUTEFORCE']) {
        $bruteForce = new BruteForcePlugin(Registry::get('iMSCP_Application')->getPluginManager(), 'captcha');
        if ($bruteForce->isWaiting() || $bruteForce->isBlocked()) {
            set_page_message($bruteForce->getLastMessage(), 'error');
            redirectTo('index.php');
        }

        $bruteForce->logAttempt();
    }

    if (!isset($_POST['capcode']) || !isset($_POST['uname'])) {
        showBadRequestErrorPage();
    } elseif (!isset($_SESSION['capcode'])) {
        set_page_message(tr('Security code has expired'), 'error');
    } elseif ($_POST['capcode'] == '' || $_POST['uname'] == '') {
        set_page_message(tr('All fields are required.'), 'error');
    } else {
        $uname = clean_input($_POST['uname']);
        $capcode = clean_input($_POST['capcode']);

        if (strtolower($_SESSION['capcode']) !== strtolower($capcode)) {
            set_page_message(tr('Wrong security code'), 'error');
        } else if (sendPasswordRequestValidation($uname)) {
            set_page_message(tr('Your request for password renewal has been registered. You will receive a mail with instructions to complete the process.'), 'success');
            redirectTo('index.php');
        }
    }
}

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onLostPasswordScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();
