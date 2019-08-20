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

/**
 * Generate page
 *
 * @param iMSCP_pTemplate $tpl
 * @return void
 */
function generatePage($tpl)
{
    $stmt = exec_query('SELECT userid, status FROM ftp_users WHERE admin_id = ?', $_SESSION['user_id']);

    if (!$stmt->rowCount()) {
        set_page_message(tr('You do not have FTP accounts.'), 'static_info');
        $tpl->assign('FTP_ACCOUNTS', '');
        return;
    }

    while ($row = $stmt->fetchRow()) {
        $tpl->assign([
            'FTP_ACCOUNT'        => tohtml($row['userid']),
            'UID'                => tohtml($row['userid'], 'htmlAttr'),
            'FTP_ACCOUNT_STATUS' => translate_dmn_status($row['status'])
        ]);

        if ($row['status'] != 'ok') {
            $tpl->assign('FTP_ACTIONS', '');
        } else {
            $tpl->parse('FTP_ACTIONS', 'ftp_actions');
        }

        $tpl->parse('FTP_ITEM', '.ftp_item');
    }
}

require_once 'imscp-lib.php';

check_login('user');
EventAggregator::getInstance()->dispatch(Events::onClientScriptStart);
customerHasFeature('ftp') or showBadRequestErrorPage();

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic([
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'client/ftp_accounts.tpl',
    'page_message' => 'layout',
    'ftp_message'  => 'page',
    'ftp_accounts' => 'page',
    'ftp_item'     => 'ftp_accounts',
    'ftp_actions'  => 'ftp_item'
]);
$tpl->assign([
    'TR_PAGE_TITLE'         => tr('Client / FTP / Overview'),
    'TR_FTP_ACCOUNT'        => tr('FTP account'),
    'TR_FTP_ACTIONS'        => tr('Actions'),
    'TR_FTP_ACCOUNT_STATUS' => tr('Status'),
    'TR_EDIT'               => tr('Edit'),
    'TR_DELETE'             => tr('Delete'),
    'TR_MESSAGE_DELETE'     => tr('Are you sure you want to delete the %s FTP account?', '%s'),
]);

EventAggregator::getInstance()->registerListener(
    Events::onGetJsTranslations,
    function ($e) {
        $tr = $e->getParam('translations');
        $tr['core']['dataTable'] = getDataTablesPluginTranslations();
        $tr['core']['deletion_confirm_msg'] = tr('Are you sure you want to delete the `%%s` FTP user?');
    }
);

generateNavigation($tpl);
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventAggregator::getInstance()->dispatch(Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
