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

use iMSCP_Registry as Registry;
use iMSCP\TemplateEngine;

require 'imscp-lib.php';

check_login('admin');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onAdminScriptStart);

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
$tpl->define([
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
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
