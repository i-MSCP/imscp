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

check_login('reseller');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onResellerScriptStart);

$tpl = new TemplateEngine();
$tpl->define([
    'layout'         => 'shared/layouts/ui.tpl',
    'page'           => 'reseller/settings_lostpassword.tpl',
    'page_message'   => 'layout',
    'custom_buttons' => 'page'
]);

$selected_on = '';
$selected_off = '';
$data_1 = get_lostpassword_activation_email($_SESSION['user_id']);
$data_2 = get_lostpassword_password_email($_SESSION['user_id']);

if (isset($_POST['uaction']) && $_POST['uaction'] == 'apply') {
    $error = false;
    $data_1['subject'] = clean_input($_POST['subject1']);
    $data_1['message'] = clean_input($_POST['message1']);
    $data_2['subject'] = clean_input($_POST['subject2']);
    $data_2['message'] = clean_input($_POST['message2']);

    if (empty($data_1['subject']) || empty($data_2['subject'])) {
        set_page_message(tr('You must specify a subject.'), 'error');
        $error = true;
    }

    if (empty($data_1['message']) || empty($data_2['message'])) {
        set_page_message(tr('You must specify a message.'), 'error');
        $error = true;
    }

    if ($error) {
        return false;
    }

    set_lostpassword_activation_email($_SESSION['user_id'], $data_1);
    set_lostpassword_password_email($_SESSION['user_id'], $data_2);
    set_page_message(tr('Lost password email templates were updated.'), 'success');

}

generateNavigation($tpl);

$tpl->assign([
    'TR_PAGE_TITLE'               => tr('Reseller / Customers / Lost Password Email'),
    'TR_MESSAGE_TEMPLATE_INFO'    => tr('Message template info'),
    'TR_MESSAGE_TEMPLATE'         => tr('Message template'),
    'SUBJECT_VALUE1'              => tohtml($data_1['subject']),
    'MESSAGE_VALUE1'              => tohtml($data_1['message']),
    'SUBJECT_VALUE2'              => tohtml($data_2['subject']),
    'MESSAGE_VALUE2'              => tohtml($data_2['message']),
    'SENDER_EMAIL_VALUE'          => tohtml($data_1['sender_email']),
    'SENDER_NAME_VALUE'           => tohtml($data_1['sender_name']),
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
    'TR_UPDATE'                   => tr('Update'),
    'TR_BASE_SERVER_VHOST_PREFIX' => tr('URL protocol'),
    'TR_BASE_SERVER_VHOST'        => tr('URL to this admin panel'),
    'TR_BASE_SERVER_VHOST_PORT'   => tr('URL port'),
    'TR_CANCEL'                   => tr('Cancel')
]);

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onResellerScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
