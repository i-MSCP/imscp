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
 * Generate reseller table
 * @param  iMSCP_pTemplate $tpl
 */
function gen_reseller_table($tpl)
{
    $stmt = execute_query(
        "
            SELECT t1.admin_id, t1.admin_name, t2.admin_name AS created_by
            FROM admin AS t1
            JOIN admin AS t2 ON(t1.created_by = t2.admin_id)
            WHERE t1.admin_type = 'reseller'
            ORDER BY created_by, admin_id
        "
    );

    $i = 0;

    if (!$stmt->rowCount()) {
        $tpl->assign([
            'MESSAGE'       => tr('Reseller list is empty.'),
            'RESELLER_LIST' => '',
        ]);
        $tpl->parse('PAGE_MESSAGE', 'page_message');
    } else {
        while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
            $adminId = $row['admin_id'];
            $adminVarname = "admin_id_" . $adminId;
            $tpl->assign([
                'NUMBER'        => $i + 1,
                'RESELLER_NAME' => tohtml($row['admin_name']),
                'OWNER'         => tohtml($row['created_by']),
                'CKB_NAME'      => $adminVarname,
            ]);
            $tpl->parse('RESELLER_ITEM', '.reseller_item');
            $i++;
        }

        $tpl->parse('RESELLER_LIST', 'reseller_list');
        $tpl->assign('PAGE_MESSAGE', '');
    }

    $stmt = execute_query("SELECT admin_id, admin_name FROM admin WHERE admin_type = 'admin' ORDER BY admin_name");

    while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
        if ((isset($_POST['uaction']) && $_POST['uaction'] == 'reseller_owner')
            && (isset($_POST['dest_admin']) && intval($_POST['dest_admin']) == $row['admin_id'])
        ) {
            $selected = ' selected';
        } else {
            $selected = '';
        }

        $tpl->assign([
            'OPTION'   => tohtml($row['admin_name']),
            'VALUE'    => $row['admin_id'],
            'SELECTED' => $selected
        ]);
        $tpl->parse('SELECT_ADMIN_OPTION', '.select_admin_option');
        $i++;
    }

    $tpl->parse('SELECT_ADMIN', 'select_admin');
    $tpl->assign('PAGE_MESSAGE', '');
}

/**
 * Update reseller owner
 *
 * @return void
 */
function update_reseller_owner()
{
    if (!isset($_POST['uaction']) || $_POST['uaction'] == 'reseller_owner') {
        return;
    }

    $stmt = execute_query("SELECT admin_id FROM admin WHERE admin_type = 'reseller' ORDER BY admin_name");

    while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
        $adminId = $row['admin_id'];
        $adminVarname = "admin_id_$adminId";

        if (isset($_POST[$adminVarname]) && $_POST[$adminVarname] == 'on') {
            $destAdmin = intval($_POST['dest_admin']);
            exec_query("UPDATE admin SET created_by = ? WHERE admin_id = ?", [$destAdmin, $adminId]);
        }
    }
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);
check_login('admin');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic([
    'layout'              => 'shared/layouts/ui.tpl',
    'page'                => 'admin/manage_reseller_owners.tpl',
    'page_message'        => 'layout',
    'hosting_plans'       => 'page',
    'reseller_list'       => 'page',
    'reseller_item'       => 'reseller_list',
    'select_admin'        => 'page',
    'select_admin_option' => 'select_admin'
]);
$tpl->assign([
    'TR_PAGE_TITLE'          => tr('Admin / Users / Resellers Assignment'),
    'TR_RESELLER_ASSIGNMENT' => tr('Reseller assignment'),
    'TR_RESELLER_USERS'      => tr('Reseller users'),
    'TR_NUMBER'              => tr('No.'),
    'TR_MARK'                => tr('Mark'),
    'TR_RESELLER_NAME'       => tr('Reseller name'),
    'TR_OWNER'               => tr('Owner'),
    'TR_TO_ADMIN'            => tr('To Admin'),
    'TR_MOVE'                => tr('Move')
]);

generateNavigation($tpl);
update_reseller_owner();
gen_reseller_table($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
