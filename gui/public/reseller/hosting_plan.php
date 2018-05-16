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

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generate page
 *
 * @param iMSCP_pTemplate $tpl
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function generatePage($tpl)
{
    $stmt = exec_query(
        'SELECT id, name, status FROM hosting_plans WHERE reseller_id = ? ORDER BY id', $_SESSION['user_id']
    );

    if (!$stmt->rowCount()) {
        $tpl->assign('HOSTING_PLANS', '');
        set_page_message(tr('No hosting plan available.'), 'static_info');
        return;
    }

    $tpl->assign([
        'TR_ID'     => tr('Id'),
        'TR_NAME'   => tr('Name'),
        'TR_STATUS' => tr('Status'),
        'TR_EDIT'   => tr('Edit'),
        'TR_ACTION' => tr('Actions'),
        'TR_DELETE' => tr('Delete')
    ]);

    iMSCP_Events_Aggregator::getInstance()->registerListener('onGetJsTranslations', function ($e) {
        /** @var iMSCP_Events_Event $e */
        $translations = $e->getParam('translations');
        $translations['core']['hp_delete_confirmation'] = tr('Are you sure you want to delete this hosting plan?');
    });

    while ($row = $stmt->fetchRow()) {
        $tpl->assign([
            'ID'     => $row['id'],
            'NAME'   => tohtml($row['name']),
            'STATUS' => $row['status'] ? tr('Available') : tr('Unavailable'),
        ]);
        $tpl->parse('HOSTING_PLAN', '.hosting_plan');
    }
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

check_login('reseller');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic([
    'layout'        => 'shared/layouts/ui.tpl',
    'page'          => 'reseller/hosting_plan.tpl',
    'page_message'  => 'layout',
    'hosting_plans' => 'page',
    'hosting_plan'  => 'hosting_plans'
]);

$tpl->assign('TR_PAGE_TITLE', tr('Reseller / Hosting Plans / Overview'));

generateNavigation($tpl);
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
