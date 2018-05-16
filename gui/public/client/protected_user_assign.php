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
 * Return htaccess username
 *
 * @param int $htuserId Htaccess user unique identifier
 * @param int $domainId Domain unique identifier
 * @return string
 * @throws Zend_Exception
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function client_getHtaccessUsername($htuserId, $domainId)
{
    $stmt = exec_query('SELECT uname, status FROM htaccess_users WHERE id = ? AND dmn_id = ?', [
        $htuserId, $domainId
    ]);

    if (!$stmt->rowCount()) {
        showBadRequestErrorPage();
    }

    $row = $stmt->fetchRow();

    if ($row['status'] != 'ok') {
        set_page_message(tr('A task is in progress for this htuser.'));
        redirectTo('protected_user_manage.php');
    }

    return $row['uname'];
}

/**
 * Generates page
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function client_generatePage($tpl)
{
    $domainId = get_user_domain_id($_SESSION['user_id']);

    if (isset($_GET['uname']) && is_number($_GET['uname'])) {
        $htuserId = intval($_GET['uname']);
        $tpl->assign('UNAME', tohtml(client_getHtaccessUsername($htuserId, $domainId)));
        $tpl->assign('UID', $htuserId);
    } elseif (isset($_POST['nadmin_name']) && is_number($_POST['nadmin_name'])) {
        $htuserId = intval($_POST['nadmin_name']);
        $tpl->assign('UNAME', tohtml(client_getHtaccessUsername($htuserId, $domainId)));
        $tpl->assign('UID', $htuserId);
    } else {
        redirectTo('protected_user_manage.php');
        return; // Useless but avoid stupid IDE warning about possible undefined variable
    }

    // Get groups
    $stmt = exec_query('SELECT * FROM htaccess_groups WHERE dmn_id = ?', $domainId);

    if (!$stmt->rowCount()) {
        set_page_message(tr('You have no groups.'), 'error');
        redirectTo('protected_user_manage.php');
    }

    $addedIn = 0;
    $notAddedIn = 0;

    while ($row = $stmt->fetchRow()) {
        $groupId = $row['id'];
        $groupName = $row['ugroup'];
        $members = $row['members'];

        $members = explode(',', $members);
        $grp_in = 0;
        // let's generate all groups where the user is assigned
        for ($i = 0, $cnt_members = count($members); $i < $cnt_members; $i++) {
            if ($htuserId == $members[$i]) {
                $tpl->assign([
                    'GRP_IN'    => tohtml($groupName),
                    'GRP_IN_ID' => $groupId,
                ]);

                $tpl->parse('ALREADY_IN', '.already_in');
                $grp_in = $groupId;
                $addedIn++;
            }
        }

        if ($grp_in !== $groupId) {
            $tpl->assign([
                'GRP_NAME' => tohtml($groupName),
                'GRP_ID'   => $groupId
            ]);
            $tpl->parse('GRP_AVLB', '.grp_avlb');
            $notAddedIn++;
        }
    }

    // generate add/remove buttons
    if ($addedIn < 1) {
        $tpl->assign('IN_GROUP', '');
    }

    if ($notAddedIn < 1) {
        $tpl->assign('NOT_IN_GROUP', '');
    }
}

/**
 * Assign a specific htaccess user to a specific htaccess group
 *
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function client_addHtaccessUserToHtaccessGroup()
{
    if (empty($_POST))
        return;

    if (!isset($_POST['uaction'])) {
        showBadRequestErrorPage();
    }

    if ($_POST['uaction'] != 'add') {
        return;
    }

    if (!isset($_GET['uname'])
        || !isset($_POST['groups'])
        || empty($_POST['groups'])
        || !isset($_POST['nadmin_name'])
        || !is_number($_POST['groups'])
        || !is_number($_POST['nadmin_name'])
    ) {
        showBadRequestErrorPage();
    }

    $domainId = get_user_domain_id($_SESSION['user_id']);
    $htuserId = clean_input($_POST['nadmin_name']);
    $htgroupId = $_POST['groups'];
    $stmt = exec_query('SELECT id, ugroup, members FROM htaccess_groups WHERE dmn_id = ? AND id = ?', [
        $domainId, $htgroupId
    ]);

    if (!$stmt->rowCount()) {
        showBadRequestErrorPage();
    }

    $row = $stmt->fetchRow();
    $members = $row['members'];
    if ($members == '') {
        $members = $htuserId;
    } else {
        $members = $members . ',' . $htuserId;
    }

    exec_query('UPDATE htaccess_groups SET members = ?, status = ? WHERE id = ? AND dmn_id = ?', [
        $members, 'tochange', $htgroupId, $domainId
    ]);

    send_request();
    set_page_message(tr('Htaccess user successfully assigned to the %s htaccess group', $row['ugroup']), 'success');
    redirectTo('protected_user_manage.php');
}

/**
 * Remove user from a specific group
 *
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function client_removeHtaccessUserFromHtaccessGroup()
{
    if (empty($_POST))
        return;

    if (!isset($_POST['uaction'])) {
        showBadRequestErrorPage();
    }

    if ($_POST['uaction'] != 'remove') {
        return;
    }

    if (!isset($_POST['groups_in'])
        || empty($_POST['groups_in'])
        || !isset($_POST['nadmin_name'])
        || !is_number($_POST['groups_in'])
        || !is_number($_POST['nadmin_name'])
    ) {
        showBadRequestErrorPage();
    }

    $domainId = get_user_domain_id($_SESSION['user_id']);
    $htgroupId = intval($_POST['groups_in']);
    $htuserId = clean_input($_POST['nadmin_name']);

    $stmt = exec_query('SELECT ugroup, members FROM htaccess_groups WHERE id = ? AND dmn_id = ?', [
        $htgroupId, $domainId
    ]);

    if (!$stmt->rowCount()) {
        showBadRequestErrorPage();
    }

    $row = $stmt->fetchRow();

    $members = explode(',', $row['members']);
    $key = array_search($htuserId, $members);

    if ($key === false) {
        return;
    }

    unset($members[$key]);
    $members = implode(',', $members);
    exec_query('UPDATE htaccess_groups SET members = ?, status = ? WHERE id = ? AND dmn_id = ?', [
        $members, 'tochange', $htgroupId, $domainId
    ]);

    send_request();
    set_page_message(tr('Htaccess user successfully deleted from the %s htaccess group ', $row['ugroup']), 'success');
    redirectTo('protected_user_manage.php');
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

check_login('user');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);
customerHasFeature('protected_areas') or showBadRequestErrorPage();

client_addHtaccessUserToHtaccessGroup();
client_removeHtaccessUserFromHtaccessGroup();

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic([
    'layout'        => 'shared/layouts/ui.tpl',
    'page'          => 'client/puser_assign.tpl',
    'page_message'  => 'layout',
    'in_group'      => 'page',
    'already_in'    => 'in_group',
    'remove_button' => 'in_group',
    'not_in_group'  => 'page',
    'grp_avlb'      => 'not_in_group',
    'add_button'    => 'not_in_group'
]);
$tpl->assign([
    'TR_PAGE_TITLE'      => 'Client / Webtools / Protected Areas / Manage Users and Groups / Assign Group',
    'TR_SELECT_GROUP'    => tr('Select group'),
    'TR_MEMBER_OF_GROUP' => tr('Member of group'),
    'TR_ADD'             => tr('Add'),
    'TR_REMOVE'          => tr('Remove'),
    'TR_CANCEL'          => tr('Cancel')
]);

generateNavigation($tpl);
client_generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
