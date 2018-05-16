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

use iMSCP_Config_Handler_File as ConfigFile;
use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventsManager;
use iMSCP_pTemplate as TemplateEngine;
use iMSCP_Registry as Registry;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Update SQL user password
 *
 * @param int $sqluId SQL user unique identifier
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 * @çeturn void
 */
function updateSqlUserPassword($sqluId)
{
    $stmt = exec_query('SELECT sqlu_name, sqlu_host FROM sql_user WHERE sqlu_id = ?', $sqluId);

    if (!$stmt->rowCount()) {
        showBadRequestErrorPage();
    }

    $row = $stmt->fetchRow(PDO::FETCH_ASSOC);

    if (!isset($_POST['password'])
        || !isset($_POST['password_confirmation'])
    ) {
        showBadRequestErrorPage();
    }

    $password = clean_input($_POST['password']);
    $passwordConf = clean_input($_POST['password_confirmation']);

    if ($password === '') {
        set_page_message(tr('The password cannot be empty.'), 'error');
        return;
    }

    if ($passwordConf === '') {
        set_page_message(tr('Please confirm the password.'), 'error');
        return;
    }

    if ($password !== $passwordConf) {
        set_page_message(tr('Passwords do not match.'), 'error');
        return;
    }

    if (!checkPasswordSyntax($password)) {
        return;
    }

    $config = Registry::get('config');
    $mysqlConfig = new ConfigFile($config['CONF_DIR'] . '/mysql/mysql.data');

    EventsManager::getInstance()->dispatch(Events::onBeforeEditSqlUser, [
        'sqlUserId'       => $sqluId,
        'sqlUserPassword' => $password
    ]);

    // Here we cannot use transaction due to statements that cause an implicit commit. Thus we execute
    // those statements first to let the i-MSCP database in clean state if one of them fails.
    // See https://dev.mysql.com/doc/refman/5.7/en/implicit-commit.html for more details

    // Update SQL user password in the mysql system tables;
    if ($mysqlConfig['SQLD_TYPE'] == 'mariadb' || version_compare($mysqlConfig['SQLD_VERSION'], '5.7.6', '<')) {
        exec_query('SET PASSWORD FOR ?@? = PASSWORD(?)', [$row['sqlu_name'], $row['sqlu_host'], $password]);
    } else {
        exec_query('ALTER USER ?@? IDENTIFIED BY ? PASSWORD EXPIRE NEVER', [
            $row['sqlu_name'], $row['sqlu_host'], $password
        ]);
    }

    set_page_message(tr('SQL user password successfully updated.'), 'success');
    write_log(
        sprintf('%s updated %s@%s SQL user password.', $_SESSION['user_logged'], $row['sqlu_name'], $row['sqlu_host']),
        E_USER_NOTICE
    );
    EventsManager::getInstance()->dispatch(Events::onAfterEditSqlUser, [
        'sqlUserId'       => $sqluId,
        'sqlUserPassword' => $password
    ]);
    redirectTo('sql_manage.php');
}

/**
 * Generate page
 *
 * @param TemplateEngine $tpl
 * @param int $sqluId SQL user unique identifier
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function generatePage(TemplateEngine $tpl, $sqluId)
{
    $stmt = exec_query('SELECT sqlu_name, sqlu_host FROM sql_user WHERE sqlu_id = ?', $sqluId);

    if (!$stmt->rowCount()) {
        showBadRequestErrorPage();
    }

    $row = $stmt->fetchRow(PDO::FETCH_ASSOC);
    $tpl->assign([
        'USER_NAME' => tohtml($row['sqlu_name']),
        'SQLU_ID'   => tohtml($sqluId, 'htmlAttr')
    ]);
}

/**
 * Checks if SQL user permissions
 *
 * @param  int $sqlUserId SQL user unique identifier
 * @return bool TRUE if the logged-in user has permission on SQL user, FALSE otherwise
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function checkSqlUserPerms($sqlUserId)
{
    return (bool)exec_query(
        '
            SELECT COUNT(t1.sqlu_id)
            FROM sql_user AS t1
            JOIN sql_database AS t2 USING(sqld_id)
            JOIN domain AS t3 USING(domain_id)
            WHERE t1.sqlu_id = ?
            AND t3.domain_admin_id = ?
        ',
        [$sqlUserId, $_SESSION['user_id']]
    )->fetchRow(PDO::FETCH_COLUMN);
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

check_login('user');
EventsManager::getInstance()->dispatch(Events::onClientScriptStart);
customerHasFeature('sql') && isset($_REQUEST['sqlu_id']) or showBadRequestErrorPage();

$sqluId = intval($_REQUEST['sqlu_id']);

if (!checkSqlUserPerms($sqluId)) {
    showBadRequestErrorPage();
}

if (!empty($_POST)) {
    updateSqlUserPassword($sqluId);
}

$tpl = new TemplateEngine();
$tpl->define_dynamic([
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'client/sql_change_password.tpl',
    'page_message' => 'layout'
]);
$tpl->assign([
    'TR_PAGE_TITLE'            => tohtml(tr('Client / Databases / Overview / Update SQL User Password')),
    'TR_SQL_USER_PASSWORD'     => tohtml(tr('SQL user password')),
    'TR_DB_USER'               => tohtml(tr('User')),
    'TR_PASSWORD'              => tohtml(tr('Password')),
    'TR_PASSWORD_CONFIRMATION' => tohtml(tr('Password confirmation')),
    'TR_UPDATE'                => tohtml(tr('Update'), 'htmlAttr'),
    'TR_CANCEL'                => tohtml(tr('Cancel'))
]);

generateNavigation($tpl);
generatePage($tpl, $sqluId);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventsManager::getInstance()->dispatch(Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
