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
use iMSCP\TemplateEngine;

require 'imscp-lib.php';

check_login('admin');
EventAggregator::getInstance()->dispatch(Events::onAdminScriptStart);

if (isset($_POST['uaction']) && $_POST['uaction'] == 'apply') {
    $errorMessage = '';
    $activationEmailData['subject'] = isset($_POST['subject1']) ? clean_input($_POST['subject1']) : '';
    $activationEmailData['message'] = isset($_POST['subject1']) ? clean_input($_POST['message1']) : '';
    $passwordEmailData['subject'] = isset($_POST['subject1']) ? clean_input($_POST['subject2']) : '';
    $passwordEmailData['message'] = isset($_POST['subject1']) ? clean_input($_POST['message2']) : '';

    if ($activationEmailData['subject'] == '' || $passwordEmailData['subject'] == '') {
        $errorMessage = tr('Please specify a message subject.');
    }

    if ($activationEmailData['message'] == '' || $passwordEmailData['message'] == '') {
        $errorMessage = tr('Please specify a message content.');
    }

    if (!empty($errorMessage)) {
        set_page_message($errorMessage, 'error');
    } else {
        set_lostpassword_activation_email(0, $activationEmailData);
        set_lostpassword_password_email(0, $passwordEmailData);
        set_page_message(tr('Lost password email templates were updated.'), 'success');
        redirectTo('settings_lostpassword.php');
    }
} else {
    $activationEmailData = get_lostpassword_activation_email($_SESSION['user_id']);
    $passwordEmailData = get_lostpassword_password_email($_SESSION['user_id']);
}

$tpl = new TemplateEngine();
$tpl->define_dynamic([
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'admin/settings_lostpassword.tpl',
    'page_message' => 'layout'
]);
$tpl->assign([
    'TR_PAGE_TITLE'               => tr('Admin / Settings / Lost Password Email'),
    'TR_LOSTPW_EMAIL'             => tr('Lost password email'),
    'TR_MESSAGE_TEMPLATE_INFO'    => tr('Message template info'),
    'TR_MESSAGE_TEMPLATE'         => tr('Message template'),
    'SUBJECT_VALUE1'              => tohtml($activationEmailData['subject']),
    'MESSAGE_VALUE1'              => tohtml($activationEmailData['message']),
    'SUBJECT_VALUE2'              => tohtml($passwordEmailData['subject']),
    'MESSAGE_VALUE2'              => tohtml($passwordEmailData['message']),
    'SENDER_EMAIL_VALUE'          => tohtml($activationEmailData['sender_email']),
    'SENDER_NAME_VALUE'           => tohtml($activationEmailData['sender_name']),
    'TR_ACTIVATION_EMAIL'         => tr('Activation email'),
    'TR_PASSWORD_EMAIL'           => tr('Password email'),
    'TR_USER_LOGIN_NAME'          => tr('User login (system) name'),
    'TR_USER_PASSWORD'            => tr('User password'),
    'TR_USER_REAL_NAME'           => tr('User (first and last) name'),
    'TR_LOSTPW_LINK'              => tr('Lost password link'),
    'TR_SUBJECT'                  => tr('Subject'),
    'TR_MESSAGE'                  => tr('Message'),
    'TR_SENDER_EMAIL'             => tr('Reply-To email'),
    'TR_SENDER_NAME'              => tr('Reply-To name'),
    'TR_APPLY_CHANGES'            => tr('Apply changes'),
    'TR_BASE_SERVER_VHOST_PREFIX' => tr('URL protocol'),
    'TR_BASE_SERVER_VHOST'        => tr('URL to this admin panel'),
    'TR_BASE_SERVER_VHOST_PORT'   => tr('URL port')
]);

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventAggregator::getInstance()->dispatch(Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
