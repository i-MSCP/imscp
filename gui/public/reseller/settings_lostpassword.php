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

check_login('reseller');
EventAggregator::getInstance()->dispatch(Events::onResellerScriptStart);

$tpl = new TemplateEngine();
$tpl->define_dynamic([
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
EventAggregator::getInstance()->dispatch(
    Events::onResellerScriptEnd, ['templateEngine' => $tpl]
);
$tpl->prnt();

unsetMessages();
