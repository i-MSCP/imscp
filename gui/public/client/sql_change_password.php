<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2017 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Checks if an user has permissions on a specific SQL user
 *
 * @param  int $sqlUserId SQL user unique identifier
 * @return bool TRUE if the logged in user has permission on SQL user, FALSE otherwise
 */
function check_user_sql_perms($sqlUserId)
{
    return (bool)exec_query(
        '
            SELECT COUNT(t1.sqlu_id)
            FROM sql_user AS t1
            JOIN sql_database AS t2 USING(sqld_id)
            JOIN domain AS t3 USING(domain_id)
            WHERE t1.sqlu_id = ?
            AND t2.domain_admin_id = ?
        ',
        [$sqlUserId, $_SESSION['user_id']]
    )->fetchRow(PDO::FETCH_COLUMN);
}

/**
 * Generate page
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $id Sql user id
 * @return array
 */
function client_generatePage($tpl, $id)
{
    $stmt = exec_query('SELECT sqlu_name, sqlu_host FROM sql_user WHERE sqlu_id = ?', $id);

    if (!$stmt->rowCount()) {
        showBadRequestErrorPage();
    }

    $row = $stmt->fetchRow(PDO::FETCH_ASSOC);
    $tpl->assign([
        'USER_NAME' => tohtml($row['sqlu_name']),
        'ID'        => tohtml($id)
    ]);

    return [$row['sqlu_name'], $row['sqlu_host']];
}

/**
 * Update SQL user password
 *
 * @param int $id Sql user id
 * @param string $user Sql user name
 * @param string $host SQL user host
 * @çeturn void
 */
function client_updateSqlUserPassword($id, $user, $host)
{
    if (!isset($_POST['uaction'])) {
        return;
    }

    if (!isset($_POST['password']) || !isset($_POST['password_confirmation'])) {
        showBadRequestErrorPage();
    }

    $password = clean_input($_POST['password']);
    $passwordConf = clean_input($_POST['password_confirmation']);

    if ($password === '') {
        set_page_message(tr('Password cannot be empty.'), 'error');
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

    $config = iMSCP_Registry::get('config');
    $mysqlConfig = new iMSCP_Config_Handler_File($config['CONF_DIR'] . '/mysql/mysql.data');

    iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeEditSqlUser, ['sqlUserId' => $id]);

    // Here we cannot use transaction due to statements that cause an implicit commit. Thus we execute
    // those statements first to let the i-MSCP database in clean state if one of them fails.
    // See https://dev.mysql.com/doc/refman/5.7/en/implicit-commit.html for more details

    // Update SQL user password in the mysql system tables;
    if ($mysqlConfig['SQLD_TYPE'] == 'mariadb' || version_compare($mysqlConfig['SQLD_VERSION'], '5.7.6', '<')) {
        exec_query('SET PASSWORD FOR ?@? = PASSWORD(?)', [$user, $host, $password]);
    } else {
        exec_query('ALTER USER ?@? IDENTIFIED BY ? PASSWORD EXPIRE NEVER', [$user, $host, $password]);
    }

    set_page_message(tr('SQL user password successfully updated.'), 'success');
    write_log(sprintf('%s updated %s@%s SQL user password.', $_SESSION['user_logged'], $user, $host), E_USER_NOTICE);

    iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterEditSqlUser, ['sqlUserId' => $id]);
    redirectTo('sql_manage.php');
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);
check_login('user');
customerHasFeature('sql') or showBadRequestErrorPage();

if (!isset($_REQUEST['id'])) {
    showBadRequestErrorPage();
}

$id = intval($_REQUEST['id']);

if (!check_user_sql_perms($id)) {
    showBadRequestErrorPage();
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic([
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'client/sql_change_password.tpl',
    'page_message' => 'layout'
]);

$tpl->assign([
    'TR_PAGE_TITLE'            => tr('Client / Databases / Overview / Update SQL User Password'),
    'TR_DB_USER'               => tr('User'),
    'TR_PASSWORD'              => tr('Password'),
    'TR_PASSWORD_CONFIRMATION' => tr('Password confirmation'),
    'TR_CHANGE'                => tr('Update'),
    'TR_CANCEL'                => tr('Cancel')
]);

list($user, $host) = client_generatePage($tpl, $id);

client_updateSqlUserPassword($id, $user, $host);
generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
