<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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

use iMSCP_Database as Database;
use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventsManager;
use iMSCP_pTemplate as TemplateEngine;

/**
 * Move the given reseller from the given administrator to the given administrator
 *
 * @throws Exception
 * @param int $resellerId Reseller unique identifier
 * @param int $fromAdministratorId Administrator unique identifier
 * @param int $toAdministratorId Administrator unique identifier
 * @return void
 */
function moveReseller($resellerId, $fromAdministratorId, $toAdministratorId)
{
    $db = Database::getInstance();

    try {
        $db->beginTransaction();

        // Move reseller to (TO) administrator
        exec_query('UPDATE admin SET created_by = ? WHERE admin_id = ?', [$toAdministratorId, $resellerId]);

        EventsManager::getInstance()->dispatch(Events::onMoveReseller, [
            'resellerId'          => $resellerId,
            'fromAdministratorId' => $fromAdministratorId,
            'toAdministratorId'   => $toAdministratorId
        ]);

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        write_log(sprintf("Couldn't move reseller with ID %d: %s", $resellerId, $e->getMessage()));
        throw new Exception(tr("Couldn't move reseller with ID %d: %s", $resellerId, $e->getMessage()), $e->getCode(), $e);
    }
}

/**
 * Move selected resellers
 *
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 * @return void
 */
function moveResellers()
{
    if (!isset($_POST['from_administrator'])
        || !isset($_POST['to_administrator'])
        || !isset($_POST['administrator_resellers'])
        || !is_array($_POST['administrator_resellers'])
    ) {
        showBadRequestErrorPage();
    }

    set_time_limit(0);
    ignore_user_abort(true);

    try {
        $fromAdministratorId = intval($_POST['from_administrator']);
        $toAdministratorId = intval($_POST['to_administrator']);

        if ($fromAdministratorId == $toAdministratorId) {
            showBadRequestErrorPage();
        }

        foreach ($_POST['administrator_resellers'] as $resellerId) {
            moveReseller(intval($resellerId), $fromAdministratorId, $toAdministratorId);
        }
        set_page_message(tr('Reseller(s) successfully moved.'), 'success');
        redirectTo('users.php');
    } catch (Exception $e) {
        set_page_message(tohtml($e->getMessage()), 'error');
        redirectTo('manage_reseller_owners.php');
    }
}

/**
 * Generate page
 *
 * @param TemplateEngine $tpl
 * @return void
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function generatePage(TemplateEngine $tpl)
{
    $administrators = $stmt = execute_query("SELECT admin_id, admin_name FROM admin WHERE admin_type = 'admin'")->fetchAll();
    $fromAdministratorId = isset($_POST['from_administrator']) ? intval($_POST['from_administrator']) : $administrators[0]['admin_id'];
    $toAdministratorId = isset($_POST['to_administrator']) ? intval($_POST['to_administrator']) : $administrators[1]['admin_id'];

    // Generate From/To reseller lists
    foreach ($administrators as $administrator) {
        $tpl->assign([
            'FROM_ADMINISTRATOR_ID'       => tohtml($administrator['admin_id'], 'htmlAttr'),
            'FROM_ADMINISTRATOR_NAME'     => tohtml($administrator['admin_name']),
            'FROM_ADMINISTRATOR_SELECTED' => $fromAdministratorId == $administrator['admin_id'] ? ' selected' : ''
        ]);
        $tpl->parse('FROM_ADMINISTRATOR_ITEM', '.from_administrator_item');
        $tpl->assign([
            'TO_ADMINISTRATOR_ID'       => tohtml($administrator['admin_id'], 'htmlAttr'),
            'TO_ADMINISTRATOR_NAME'     => tohtml($administrator['admin_name']),
            'TO_ADMINISTRATOR_SELECTED' => $toAdministratorId == $administrator['admin_id'] ? ' selected' : ''
        ]);
        $tpl->parse('TO_ADMINISTRATOR_ITEM', '.to_administrator_item');
    }

    // Generate resellers list for the selected (FROM) administrator
    $resellers = exec_query("SELECT admin_id, admin_name FROM admin WHERE created_by = ? AND admin_type = 'reseller'", [$fromAdministratorId]);

    if (!$resellers->rowCount()) {
        $tpl->assign('FROM_ADMINISTRATOR_RESELLERS_LIST', '');
        return;
    }

    $selectedResellers = isset($_POST['administrator_resellers']) ? $_POST['administrator_resellers'] : [];
    while ($reseller = $resellers->fetchRow()) {
        $tpl->assign([
            'RESELLER_ID'                    => tohtml($reseller['admin_id'], 'htmlAttr'),
            'RESELLER_NAME'                  => tohtml(decode_idna($reseller['admin_name'])),
            'ADMINISTRATOR_RESELLER_CHECKED' => in_array($reseller['admin_id'], $selectedResellers) ? ' checked' : ''
        ]);
        $tpl->parse('FROM_ADMINISTRATOR_RESELLER_ITEM', '.from_administrator_reseller_item');
    }
}

require 'imscp-lib.php';

check_login('admin');
EventsManager::getInstance()->dispatch(Events::onAdminScriptStart);
systemHasManyAdmins() or showBadRequestErrorPage();

if (isset($_POST['uaction']) && $_POST['uaction'] == 'move_resellers') {
    moveResellers();
}

$tpl = new TemplateEngine();
$tpl->define_dynamic([
    'layout'                            => 'shared/layouts/ui.tpl',
    'page'                              => 'admin/manage_reseller_owners.phtml',
    'page_message'                      => 'layout',
    'from_administrator_resellers_list' => 'page',
    'from_administrator_reseller_item'  => 'from_administrator_resellers_list',
    'from_administrator_item'           => 'page',
    'to_administrator_item'             => 'page'
]);
$tpl->assign('TR_PAGE_TITLE', tohtml(tr('Admin / Users / Reseller Assignments')));

generateNavigation($tpl);
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventsManager::getInstance()->dispatch(Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
