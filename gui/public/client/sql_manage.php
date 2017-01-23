<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by i-MSCP Team
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
 * Generates database sql users list
 *
 * @access private
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $dbId Database unique identifier
 * @return void
 */
function _generateDatabaseSqlUserList($tpl, $dbId)
{
    $stmt = exec_query('SELECT sqlu_id, sqlu_name, sqlu_host FROM sql_user WHERE sqld_id = ? ORDER BY sqlu_name', $dbId);

    if (!$stmt->rowCount()) {
        $tpl->assign('SQL_USERS_LIST', '');
        return;
    }

    $tpl->assign(array(
        'SQL_USERS_LIST'          => '',
        'TR_DB_USER'              => tr('User'),
        'TR_DB_USER_HOST'         => tr('Host'),
        'TR_DB_USER_HOST_TOOLTIP' => tr('Host from which SQL user is allowed to connect to SQL server')
    ));

    while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
        $sqlUserName = $row['sqlu_name'];
        $tpl->assign(array(
            'DB_USER'      => tohtml($sqlUserName),
            'DB_USER_HOST' => tohtml(decode_idna($row['sqlu_host'])),
            'DB_USER_JS'   => tojs($sqlUserName),
            'USER_ID'      => $row['sqlu_id']
        ));
        $tpl->parse('SQL_USERS_LIST', '.sql_users_list');
    }
}

/**
 * Generates databases list
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function generatePage($tpl)
{
    $stmt = exec_query(
        'SELECT sqld_id, sqld_name FROM sql_database WHERE domain_id = ? ORDER BY sqld_name ',
        get_user_domain_id($_SESSION['user_id'])
    );

    if (!$stmt->rowCount()) {
        set_page_message(tr('You do not have databases.'), 'static_info');
        $tpl->assign('SQL_DATABASES_USERS_LIST', '');
        return;
    }

    while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
        $tpl->assign(array(
            'DB_ID'      => $row['sqld_id'],
            'DB_NAME'    => tohtml($row['sqld_name']),
            'DB_NAME_JS' => tojs($row['sqld_name'])
        ));
        _generateDatabaseSqlUserList($tpl, $row['sqld_id']);
        $tpl->parse('SQL_DATABASES_LIST', '.sql_databases_list');
    }
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);
check_login('user');
customerHasFeature('sql') or showBadRequestErrorPage();

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
    'layout'                   => 'shared/layouts/ui.tpl',
    'page'                     => 'client/sql_manage.tpl',
    'page_message'             => 'layout',
    'sql_databases_users_list' => 'page',
    'sql_databases_list'       => 'sql_databases_users_list',
    'sql_users_list'           => 'sql_databases_list'
));
$tpl->assign(array(
    'TR_PAGE_TITLE'              => tr('Client / Databases / Overview'),
    'TR_MANAGE_SQL'              => tr('Manage SQL'),
    'TR_DELETE'                  => tr('Delete'),
    'TR_DATABASE'                => tr('Database Name and Users'),
    'TR_CHANGE_PASSWORD'         => tr('Update password'),
    'TR_ACTIONS'                 => tr('Actions'),
    'TR_DATABASE_USERS'          => tr('Database users'),
    'TR_ADD_USER'                => tr('Add SQL user'),
    'TR_DATABASE_MESSAGE_DELETE' => tr("This database will be permanently deleted. This process cannot be recovered. All users linked to this database will also be deleted if not linked to another database. Are you sure you want to delete the '%s' database?", '%s'),
    'TR_USER_MESSAGE_DELETE'     => tr('Are you sure you want delete the %s SQL user?', '%s')
));

generateNavigation($tpl);
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();
