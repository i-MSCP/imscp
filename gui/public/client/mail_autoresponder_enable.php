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

use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventsManager;
use iMSCP_pTemplate as TemplateEngine;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Checks the given mail account
 *
 * - Mail account must exists
 * - Mail account must be owned by customer
 * - Mail account must be of type normal, forward or normal & forward
 * - Mail account must must be in consistent state
 * - Mail account autoresponder must not be active
 *
 * @param int $mailAccountId Mail account unique identifier
 * @return string|false string if all conditions are meet, FALSE otherwise
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function checkMailAccount($mailAccountId)
{
    return exec_query(
        "
            SELECT IFNULL(mail_auto_respond_text, '') FROM mail_users AS t1
            JOIN domain AS t2 USING(domain_id)
            WHERE t1.mail_id = ? AND t2.domain_admin_id = ? AND t1.mail_type NOT RLIKE ? AND t1.status = 'ok'
            AND t1.mail_auto_respond = 0
        ",
        [
            $mailAccountId,
            $_SESSION['user_id'],
            MT_NORMAL_CATCHALL . '|' . MT_SUBDOM_CATCHALL . '|' . MT_ALIAS_CATCHALL . '|' . MT_ALSSUB_CATCHALL
        ]
    )->fetchRow(PDO::FETCH_COLUMN);
}

/**
 * Activate autoresponder of the given mail account with the given autoreponder message
 *
 * @param int $mailAccountId Mail account id
 * @param string $autoresponderMessage Auto-responder message
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function activateAutoresponder($mailAccountId, $autoresponderMessage)
{
    if ($autoresponderMessage === '') {
        set_page_message(tr('Autoresponder message cannot be empty.'), 'error');
        redirectTo("mail_autoresponder_enable.php?mail_account_id=$mailAccountId");
    }

    exec_query(
        "
            UPDATE mail_users
            SET status = 'tochange', mail_auto_respond = 1, mail_auto_respond_text = ?
            WHERE mail_id = ?
        ",
        [$autoresponderMessage, $mailAccountId]
    );
    send_request();
    write_log(sprintf('A mail autoresponder has been activated by %s', $_SESSION['user_logged']), E_USER_NOTICE);
    set_page_message(tr('Autoresponder has been activated.'), 'success');
}

/**
 * Generate page
 *
 * @param TemplateEngine $tpl Template engine instance
 * @param int $mailAccountId Mail account id
 * @return void
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function generatePage($tpl, $mailAccountId)
{
    $stmt = exec_query('SELECT mail_auto_respond_text FROM mail_users WHERE mail_id = ?', $mailAccountId);
    $row = $stmt->fetchRow();
    $tpl->assign('AUTORESPONDER_MESSAGE', tohtml($row['mail_auto_respond_text']));
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

check_login('user');
EventsManager::getInstance()->dispatch(Events::onClientScriptStart);

if (!customerHasFeature('mail')
    || !isset($_REQUEST['id'])
) {
    showBadRequestErrorPage();
}

$mailAccountId = intval($_REQUEST['id']);

if (($autoresponderMsg = checkMailAccount($mailAccountId)) === FALSE) {
    showBadRequestErrorPage();
}

if ($autoresponderMsg !== '') {
    activateAutoresponder($mailAccountId, $autoresponderMsg);
    redirectTo('mail_accounts.php');
}

if (!isset($_POST['id'])) {
    $tpl = new TemplateEngine();
    $tpl->define_dynamic([
        'layout'       => 'shared/layouts/ui.tpl',
        'page'         => 'client/mail_autoresponder.tpl',
        'page_message' => 'layout'
    ]);
    $tpl->assign([
        'TR_PAGE_TITLE'            => tohtml(tr('Client / Mail / Overview / Activate Autoresponder')),
        'TR_AUTORESPONDER_MESSAGE' => tohtml(tr('Please enter your autoresponder message below')),
        'TR_ACTION'                => tohtml(tr('Activate')),
        'TR_CANCEL'                => tohtml(tr('Cancel')),
        'MAIL_ACCOUNT_ID'          => tohtml($mailAccountId)
    ]);

    generateNavigation($tpl);
    generatePage($tpl, $mailAccountId);
    generatePageMessage($tpl);

    $tpl->parse('LAYOUT_CONTENT', 'page');
    EventsManager::getInstance()->dispatch(Events::onClientScriptEnd, ['templateEngine' => $tpl]);
    $tpl->prnt();

    unsetMessages();
} elseif (isset($_POST['autoresponder_message'])) {
    activateAutoresponder($mailAccountId, clean_input($_POST['autoresponder_message']));
    redirectTo('mail_accounts.php');
} else {
    showBadRequestErrorPage();
}
