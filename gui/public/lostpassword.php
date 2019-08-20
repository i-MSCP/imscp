<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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
 * @noinspection
 * PhpDocMissingThrowsInspection
 * PhpUnhandledExceptionInspection
 * PhpIncludeInspection
 */

use iMSCP\Event\EventAggregator;
use iMSCP\Event\Events;
use iMSCP\Exception\Exception;
use iMSCP\Plugin\BruteForce;
use iMSCP\Registry;
use iMSCP\TemplateEngine;

require_once 'imscp-lib.php';
require_once LIBRARY_PATH . '/Functions/LostPassword.php';

EventAggregator::getInstance()->dispatch(Events::onLostPasswordScriptStart);
do_session_timeout();

$cfg = Registry::get('config');
if (!$cfg['LOSTPASSWORD']) {
    showNotFoundErrorPage();
}

if (!function_exists('imagecreatetruecolor')) {
    throw new Exception(tr('PHP GD extension not loaded.'));
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
$tpl->define_dynamic([
    'layout'       => 'shared/layouts/simple.tpl',
    'page'         => 'lostpassword.tpl',
    'page_message' => 'layout'
]);
$tpl->assign([
    'TR_PAGE_TITLE'    => tr('i-MSCP - Multi Server Control Panel / Lost Password'),
    'CONTEXT_CLASS'    => '',
    'productLongName'  => tr('internet Multi Server Control Panel'),
    'productLink'      => 'https://www.i-mscp.net',
    'productCopyright' => tr('© 2010-2017 i-MSCP Team<br>All Rights Reserved'),
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
        $bruteForce = new BruteForce(Registry::get('pluginManager'), 'captcha');
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
EventAggregator::getInstance()->dispatch(Events::onLostPasswordScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();
