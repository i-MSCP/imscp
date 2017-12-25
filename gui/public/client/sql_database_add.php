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
use iMSCP_Exception as iMSCPException;
use iMSCP\TemplateEngine;
use iMSCP_Registry as Registry;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Add SQL database
 *
 * @return void
 */
function addSqlDb()
{
    if (!isset($_POST['db_name'])) {
        showBadRequestErrorPage();
    }

    $dbName = clean_input($_POST['db_name']);

    if ($_POST['db_name'] === '') {
        set_page_message(tr('Please type database name.'), 'error');
        return;
    }

    $mainDmnId = get_user_domain_id($_SESSION['user_id']);

    if (isset($_POST['use_dmn_id'])
        && $_POST['use_dmn_id'] == 'on'
    ) {
        if (isset($_POST['id_pos'])
            && $_POST['id_pos'] == 'start'
        ) {
            $dbName = $mainDmnId . '_' . $dbName;
        } elseif (isset($_POST['id_pos']) && $_POST['id_pos'] === 'end') {
            $dbName = $dbName . '_' . $mainDmnId;
        }
    }

    if (strlen($dbName) > 64) {
        set_page_message(tr('Database name is too long.'), 'error');
        return;
    }

    if (in_array($dbName, ['information_schema', 'mysql', 'performance_schema', 'sys', 'test'])
        || exec_query('SHOW DATABASES LIKE ?', $dbName)->rowCount() > 0
    ) {
        set_page_message(tr('Database name is unavailable or unallowed.'), 'error');
        return;
    }

    try {
        Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onBeforeAddSqlDb, ['dbName' => $dbName]);
        execute_query(sprintf('CREATE DATABASE IF NOT EXISTS %s', quoteIdentifier($dbName)));
        exec_query('INSERT INTO sql_database (domain_id, sqld_name) VALUES (?, ?)', [$mainDmnId, $dbName]);
        Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onAfterAddSqlDb, [
            'dbId'   => Registry::get('iMSCP_Application')->getDatabase()->lastInsertId(),
            'dbName' => $dbName
        ]);
        set_page_message(tr('SQL database successfully created.'), 'success');
        write_log(
            sprintf('A new database (%s) has been created by %s', $dbName, $_SESSION['user_logged']), E_USER_NOTICE
        );
    } catch (iMSCPException $e) {
        write_log(sprintf("Couldn't create the %s database: %s", $dbName, $e->getMessage()));
        set_page_message(tr("Couldn't create the %s database.", $dbName), 'error');
    }

    redirectTo('sql_manage.php');
}

/**
 * Generate page
 *
 * @param TemplateEngine $tpl
 * @return void
 */
function generatePage(TemplateEngine $tpl)
{
    $cfg = Registry::get('config');

    if ($cfg['MYSQL_PREFIX'] != 'none') {
        $tpl->assign('MYSQL_PREFIX_YES', '');

        if ($cfg['MYSQL_PREFIX'] == 'behind') {
            $tpl->assign('MYSQL_PREFIX_INFRONT', '');
            $tpl->parse('MYSQL_PREFIX_BEHIND', 'mysql_prefix_behind');
            $tpl->assign('MYSQL_PREFIX_ALL', '');
        } else {
            $tpl->parse('MYSQL_PREFIX_INFRONT', 'mysql_prefix_infront');
            $tpl->assign([
                'MYSQL_PREFIX_BEHIND' => '',
                'MYSQL_PREFIX_ALL'    => ''
            ]);
        }
    } else {
        $tpl->assign([
            'MYSQL_PREFIX_NO'      => '',
            'MYSQL_PREFIX_INFRONT' => '',
            'MYSQL_PREFIX_BEHIND'  => ''
        ]);
        $tpl->parse('MYSQL_PREFIX_ALL', 'mysql_prefix_all');
    }

    $tpl->assign([
        'DB_NAME'               => isset($_POST['db_name']) ? tohtml($_POST['db_name'], 'htmlAttr') : '',
        'USE_DMN_ID'            => isset($_POST['use_dmn_id']) && $_POST['use_dmn_id'] === 'on' ? ' checked' : '',
        'START_ID_POS_SELECTED' => isset($_POST['id_pos']) && $_POST['id_pos'] !== 'end' ? ' checked' : '',
        'END_ID_POS_SELECTED'   => isset($_POST['id_pos']) && $_POST['id_pos'] === 'end' ? ' checked' : ''
    ]);
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

check_login('user');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onClientScriptStart);
customerHasFeature('sql') && !customerSqlDbLimitIsReached() or showBadRequestErrorPage();

if (!empty($_POST)) {
    addSqlDb();
}

$tpl = new TemplateEngine();
$tpl->define([
    'layout'               => 'shared/layouts/ui.tpl',
    'page'                 => 'client/sql_database_add.tpl',
    'page_message'         => 'layout',
    'mysql_prefix_yes'     => 'page',
    'mysql_prefix_no'      => 'page',
    'mysql_prefix_all'     => 'page',
    'mysql_prefix_infront' => 'page',
    'mysql_prefix_behind'  => 'page'
]);
$tpl->assign([
    'TR_PAGE_TITLE'   => tohtml(tr('Client / Databases / Add SQL Database')),
    'TR_DATABASE'     => tohtml(tr('Database')),
    'TR_DB_NAME'      => tohtml(tr('Database name')),
    'TR_USE_DMN_ID'   => tohtml(tr('Database prefix/suffix')),
    'TR_START_ID_POS' => tohtml(tr('In front')),
    'TR_END_ID_POS'   => tohtml(tr('Behind')),
    'TR_ADD'          => tohtml(tr('Add'), 'htmlAttr'),
    'TR_CANCEL'       => tohtml(tr('Cancel'))
]);

generatePage($tpl);
generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
