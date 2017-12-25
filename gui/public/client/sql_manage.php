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

use iMSCP_Events as Events;
use iMSCP\TemplateEngine;
use iMSCP_Registry as Registry;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Can add SQL user for the given SQL database?
 *
 * @param int $sqldId SQL database unique identifier
 * @return bool
 */
function canAddSQLUserForDatabase($sqldId)
{
    $domainProps = get_domain_default_props($_SESSION['user_id']);

    if ($domainProps['domain_sqlu_limit'] == 0) {
        return true;
    }

    if (get_customer_sql_users_count($domainProps['domain_id']) >= $domainProps['domain_sqlu_limit']) {
        // Count all SQL users that are owned by the customer, excluding those
        // that are already assigned to $sqldId
        return exec_query(
                '
                SELECT COUNT(sqlu_id)
                FROM sql_user AS t1
                JOIN sql_database as t2 USING(sqld_id)
                WHERE t2.sqld_id <> ?
                AND t2.domain_id = ?
                AND CONCAT(t1.sqlu_name, t1.sqlu_host) NOT IN(
                SELECT CONCAT(sqlu_name, sqlu_host) FROM sql_user WHERE sqld_id = ?
            )
            ',
                [$sqldId, get_user_domain_id($_SESSION['user_id']), $sqldId]
            )->fetchColumn() > 0;
    }

    return true;
}

/**
 * Generates database sql users list
 *
 * @param TemplateEngine $tpl Template engine
 * @param int $sqldId Database unique identifier
 * @return void
 */
function generateDatabaseSqlUserList(TemplateEngine $tpl, $sqldId)
{
    $stmt = exec_query('SELECT sqlu_id, sqlu_name, sqlu_host FROM sql_user WHERE sqld_id = ? ORDER BY sqlu_name', [
        $sqldId
    ]);

    if (!$stmt->rowCount()) {
        $tpl->assign('SQL_USERS_LIST', '');
        return;
    }

    $tpl->assign([
        'SQL_USERS_LIST'          => '',
        'TR_DB_USER'              => tohtml(tr('User')),
        'TR_DB_USER_HOST'         => tohtml(tr('Host')),
        'TR_DB_USER_HOST_TOOLTIP' => tohtml(
            tr('Host from which SQL user is allowed to connect to SQL server'), 'htmlAttr'
        )
    ]);

    while ($row = $stmt->fetch()) {
        $tpl->assign([
            'DB_USER'      => tohtml($row['sqlu_name']),
            'DB_USER_HOST' => tohtml(decode_idna($row['sqlu_host'])),
            'DB_USER_JS'   => tojs($row['sqlu_name']),
            'SQLU_ID'      => tohtml($row['sqlu_id'], 'htmlAttr')
        ]);

        $tpl->parse('SQL_USERS_LIST', '.sql_users_list');
    }
}

/**
 * Generates databases list
 *
 * @param TemplateEngine $tpl Template engine
 * @return void
 */
function generatePage(TemplateEngine $tpl)
{
    $stmt = exec_query('SELECT sqld_id, sqld_name FROM sql_database WHERE domain_id = ? ORDER BY sqld_name ', [
        get_user_domain_id($_SESSION['user_id'])
    ]);

    if (!$stmt->rowCount()) {
        set_page_message(tr('You do not have databases.'), 'static_info');
        $tpl->assign('SQL_DATABASES_USERS_LIST', '');
        return;
    }

    while ($row = $stmt->fetch()) {
        $tpl->assign([
            'SQLD_ID'    => $row['sqld_id'],
            'DB_NAME'    => tohtml($row['sqld_name']),
            'DB_NAME_JS' => tojs($row['sqld_name'])
        ]);

        if (!canAddSQLUserForDatabase($row['sqld_id'])) {
            $tpl->assign('SQL_USER_ADD_LINK', '');
        } else {
            $tpl->parse('SQL_USER_ADD_LINK', 'sql_user_add_link');
        }

        generateDatabaseSqlUserList($tpl, $row['sqld_id']);
        $tpl->parse('SQL_DATABASES_LIST', '.sql_databases_list');
    }
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

check_login('user');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onClientScriptStart);
customerHasFeature('sql') or showBadRequestErrorPage();

$tpl = new TemplateEngine();
$tpl->define([
    'layout'                   => 'shared/layouts/ui.tpl',
    'page'                     => 'client/sql_manage.tpl',
    'page_message'             => 'layout',
    'sql_databases_users_list' => 'page',
    'sql_databases_list'       => 'sql_databases_users_list',
    'sql_users_list'           => 'sql_databases_list',
    'sql_user_add_link'        => 'sql_databases_list'
]);
$tpl->assign([
    'TR_PAGE_TITLE'              => tohtml(tr('Client / Databases / Overview')),
    'TR_MANAGE_SQL'              => tohtml(tr('Manage SQL')),
    'TR_DELETE'                  => tohtml(tr('Delete')),
    'TR_DATABASE'                => tohtml(tr('Database Name and Users')),
    'TR_CHANGE_PASSWORD'         => tohtml(tr('Update password')),
    'TR_ACTIONS'                 => tohtml(tr('Actions')),
    'TR_DATABASE_USERS'          => tohtml(tr('Database users')),
    'TR_ADD_USER'                => tohtml(tr('Add SQL user')),
    'TR_DATABASE_MESSAGE_DELETE' => tojs(tr("This database will be permanently deleted. This process cannot be recovered. All users linked to this database will also be deleted if not linked to another database. Are you sure you want to delete the '%s' database?", '%s')),
    'TR_USER_MESSAGE_DELETE'     => tojs(tr('Are you sure you want delete the %s SQL user?', '%s'))
]);

generateNavigation($tpl);
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
