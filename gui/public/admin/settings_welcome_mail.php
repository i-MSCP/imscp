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

$tpl = new TemplateEngine();
$tpl->define_dynamic([
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'admin/settings_welcome_mail.tpl',
    'page_message' => 'layout'
]);

if (isset($_POST['uaction']) && $_POST['uaction'] == 'email_setup') {
    $data['subject'] = isset($_POST['auto_subject']) ? clean_input($_POST['auto_subject']) : '';
    $data['message'] = isset($_POST['auto_message']) ? clean_input($_POST['auto_message']) : '';
    $error = false;

    if ($data['subject'] == '') {
        set_page_message(tr('Please specify a message subject.'), 'error');
        $error = true;
    }

    if ($data['message'] == '') {
        set_page_message(tr('Please specify a message content.'), 'error');
        $error = true;
    }

    if (!$error) {
        set_welcome_email(0, $data);
        set_page_message(tr('Welcome email template has been updated.'), 'success');
        redirectTo('settings_welcome_mail.php');
    }
}

$data = get_welcome_email($_SESSION['user_id']);

$tpl->assign([
    'TR_PAGE_TITLE'               => tr('Admin / Settings / Welcome Email'),
    'TR_EMAIL_SETUP'              => tr('Email setup'),
    'TR_MESSAGE_TEMPLATE_INFO'    => tr('Message template info'),
    'TR_USER_LOGIN_NAME'          => tr('User login (system) name'),
    'TR_USER_PASSWORD'            => tr('User password'),
    'TR_USER_REAL_NAME'           => tr('User real (first and last) name'),
    'TR_MESSAGE_TEMPLATE'         => tr('Message template'),
    'TR_SUBJECT'                  => tr('Subject'),
    'TR_MESSAGE'                  => tr('Message'),
    'TR_SENDER_EMAIL'             => tr('Reply-To email'),
    'TR_SENDER_NAME'              => tr('Reply-To name'),
    'TR_UPDATE'                   => tr('Update'),
    'TR_USERTYPE'                 => tr('User type (admin, reseller, user)'),
    'TR_BASE_SERVER_VHOST_PREFIX' => tr('URL protocol'),
    'TR_BASE_SERVER_VHOST'        => tr('URL to this admin panel'),
    'TR_BASE_SERVER_VHOST_PORT'   => tr('URL port'),
    'SUBJECT_VALUE'               => tohtml($data['subject']),
    'MESSAGE_VALUE'               => tohtml($data['message']),
    'SENDER_EMAIL_VALUE'          => tohtml($data['sender_email']),
    'SENDER_NAME_VALUE'           => tohtml($data['sender_name'])
]);

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventAggregator::getInstance()->dispatch(Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
