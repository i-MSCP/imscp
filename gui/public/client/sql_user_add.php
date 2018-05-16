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
use iMSCP_Validate as Validator;
use Zend_Validate_Hostname as ValidateHostname;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Check SQL permissions
 *
 * @param TemplateEngine $tpl
 * @param int $sqldId Database unique identifier
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function checkSqlUserPermissions(TemplateEngine $tpl, $sqldId)
{
    global $canAddNewSQLUser;

    $canAddNewSQLUser = true;
    $domainProps = get_domain_default_props($_SESSION['user_id']);

    if ($domainProps['domain_sqlu_limit'] != 0
        && get_customer_sql_users_count($domainProps['domain_id']) >= $domainProps['domain_sqlu_limit']
    ) {
        set_page_message(tr("SQL users limit is reached. You cannot add new SQL users."), 'static_info');
        $canAddNewSQLUser = false;
        $tpl->assign('CREATE_SQLUSER', '');
    }

    $stmt = exec_query(
        'SELECT COUNT(sqld_id) FROM sql_database JOIN domain USING(domain_id) WHERE sqld_id = ? AND domain_id = ?',
        [$sqldId, $domainProps['domain_id']]
    );

    if ($stmt->fetchRow(PDO::FETCH_COLUMN) < 1) {
        showBadRequestErrorPage();
    }
}

/**
 * Get SQL user list
 *
 * @param TemplateEngine $tpl
 * @param int $sqldId Database unique identifier
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function generateSqlUserList(TemplateEngine $tpl, $sqldId)
{
    global $canAddNewSQLUser;

    // Select all SQL users that are owned by the customer except those that are
    // already assigned to $sqldId
    $stmt = exec_query(
        "
            SELECT MAX(t1.sqlu_id) AS sqlu_id, t1.sqlu_name, t1.sqlu_host
            FROM sql_user AS t1
            JOIN sql_database AS t2 USING(sqld_id)
            WHERE t2.sqld_id <> ?
            AND t2.domain_id = ?
            AND CONCAT(t1.sqlu_name, t1.sqlu_host) NOT IN(
                SELECT CONCAT(sqlu_name, sqlu_host) FROM sql_user WHERE sqld_id = ?
            )
            GROUP BY t1.sqlu_name, t1.sqlu_host
        ",
        [$sqldId, get_user_domain_id($_SESSION['user_id']), $sqldId]
    );

    if ($stmt->rowCount()) {
        while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
            $tpl->assign([
                'SQLUSER_ID'  => $row['sqlu_id'],
                'SQLUSER_IDN' => tohtml($row['sqlu_name'] . '@' . decode_idna($row['sqlu_host'])),
            ]);
            $tpl->parse('SQLUSER_LIST', '.sqluser_list');
        }

        return;
    }

    if (!$canAddNewSQLUser) {
        showBadRequestErrorPage();
    }

    $tpl->assign('SHOW_SQLUSER_LIST', '');
}

/**
 * Does the given SQL user already exists?
 *
 * @param string $sqlUser SQL user name
 * @param string $sqlUserHost SQL user host
 * @return bool TRUE if the given sql user already exists, FALSE otherwise
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function isSqlUser($sqlUser, $sqlUserHost)
{
    return (bool)exec_query(
        'SELECT COUNT(User) FROM mysql.user WHERE User = ? AND Host = ?', [$sqlUser, $sqlUserHost]
    )->fetchRow(PDO::FETCH_COLUMN);
}

/**
 * Add SQL user for the given database
 *
 * @throws Exception
 * @param int $sqldId Database unique identifier
 * @return void
 */
function addSqlUser($sqldId)
{
    if (!isset($_POST['uaction'])) {
        showBadRequestErrorPage();
    }

    $dmnId = get_user_domain_id($_SESSION['user_id']);

    if (!isset($_POST['reuse_sqluser'])) {
        $needUserCreate = true;

        if (!isset($_POST['user_name'])
            || !isset($_POST['user_host'])
            || !isset($_POST['pass'])
            || !isset($_POST['pass_rep'])
        ) {
            showBadRequestErrorPage();
        }

        $user = clean_input($_POST['user_name']);
        $host = clean_input($_POST['user_host']);
        $password = clean_input($_POST['pass']);
        $passwordConf = clean_input($_POST['pass_rep']);

        if ($user === '') {
            set_page_message(tr('Please enter an username.'), 'error');
            return;
        }

        if ($host === '') {
            set_page_message(tr('Please enter an SQL user host.'), 'error');
            return;
        }

        $host = encode_idna(clean_input($_POST['user_host']));

        if ($host !== '%'
            && $host !== 'localhost'
            && !Validator::getInstance()->hostname(
                $host, ['allow' => ValidateHostname::ALLOW_DNS | ValidateHostname::ALLOW_IP]
            )
        ) {
            set_page_message(
                tr('Invalid SQL user host: %s', Validator::getInstance()->getLastValidationMessages()), 'error'
            );
            return;
        }

        if ($password === '') {
            set_page_message(tr('Please enter a password.'), 'error');
            return;
        }

        if ($password !== $passwordConf) {
            set_page_message(tr('Passwords do not match.'), 'error');
            return;
        }

        if (!checkPasswordSyntax($password)) {
            return;
        }

        if (isset($_POST['use_dmn_id'])
            && $_POST['use_dmn_id'] == 'on'
            && isset($_POST['id_pos'])
            && $_POST['id_pos'] == 'start'
        ) {
            $user = $dmnId . '_' . clean_input($_POST['user_name']);
        } elseif (isset($_POST['use_dmn_id'])
            && $_POST['use_dmn_id'] == 'on'
            && isset($_POST['id_pos'])
            && $_POST['id_pos'] == 'end'
        ) {
            $user = clean_input($_POST['user_name']) . '_' . $dmnId;
        } else {
            $user = clean_input($_POST['user_name']);
        }

        if (strlen($user) > 16) {
            set_page_message(tr('SQL username is too long.'), 'error');
            return;
        }

        if (isSqlUser($user, $host)
            || in_array($user, ['debian-sys-maint', 'mysql.user', 'root'])
        ) {
            set_page_message(
                tr("The `%s' SQL user is not available or not permitted.", $user . '@' . decode_idna($host)), 'error'
            );
            return;
        }
    } elseif (isset($_POST['sqluser_id'])) { // Using existing SQL user as specified in input data
        $needUserCreate = false;
        $stmt = exec_query(
            '
                SELECT t1.sqlu_name, t1.sqlu_host
                FROM sql_user AS t1
                JOIN sql_database as t2 USING(sqld_id)
                WHERE t1.sqlu_id = ?
                AND t1.sqld_id <> ?
                AND t2.domain_id = ?
            ',
            [intval($_POST['sqluser_id']), $sqldId, $dmnId]
        );

        if (!$stmt->rowCount()) {
            showBadRequestErrorPage();
        }

        $row = $stmt->fetchRow(PDO::FETCH_ASSOC);
        $user = $row['sqlu_name'];
        $host = $row['sqlu_host'];
    } else {
        showBadRequestErrorPage();
        return;
    }

    # Retrieve database to which SQL user should be assigned
    $stmt = exec_query('SELECT sqld_name FROM sql_database WHERE sqld_id = ? AND domain_id = ?', [$sqldId, $dmnId]);

    if (!$stmt->rowCount()) {
        showBadRequestErrorPage();
    }

    $row = $stmt->fetchRow(PDO::FETCH_ASSOC);
    $mysqlConfig = new ConfigFile(Registry::get('config')['CONF_DIR'] . '/mysql/mysql.data');

    EventsManager::getInstance()->dispatch(Events::onBeforeAddSqlUser, [
        'SqlUsername'     => $user,
        'SqlUserHost'     => $host,
        'SqlUserPassword' => isset($password) ? $password : ''
    ]);

    // Here we cannot use transaction due to statements that cause an implicit commit. Thus we execute
    // those statements first to let the i-MSCP database in clean state if one of them fails.
    // See https://dev.mysql.com/doc/refman/5.7/en/implicit-commit.html for more details

    if ($needUserCreate && isset($password)) {
        if ($mysqlConfig['SQLD_TYPE'] == 'mariadb'
            || version_compare($mysqlConfig['SQLD_VERSION'], '5.7.6', '<')
        ) {
            exec_query('CREATE USER ?@? IDENTIFIED BY ?', [$user, $host, $password]);
        } else {
            exec_query('CREATE USER ?@? IDENTIFIED BY ? PASSWORD EXPIRE NEVER', [$user, $host, $password]);
        }
    }

    // According MySQL documentation (http://dev.mysql.com/doc/refman/5.5/en/grant.html#grant-accounts-passwords)
    // The “_” and “%” wildcards are permitted when specifying database names in GRANT statements that grant privileges
    // at the global or database levels. This means, for example, that if you want to use a “_” character as part of a
    // database name, you should specify it as “\_” in the GRANT statement, to prevent the user from being able to
    // access additional databases matching the wildcard pattern; for example, GRANT ... ON `foo\_bar`.* TO ....
    //
    // In practice, without escaping, an user added for db `a_c` would also have access to a db `abc`.
    $row['sqld_name'] = preg_replace('/([%_])/', '\\\\$1', $row['sqld_name']);

    exec_query(sprintf('GRANT ALL PRIVILEGES ON %s.* to ?@?', quoteIdentifier($row['sqld_name'])), [$user, $host]);
    exec_query('INSERT INTO sql_user (sqld_id, sqlu_name, sqlu_host) VALUES (?, ?, ?)', [
        $sqldId, $user, $host
    ]);

    EventsManager::getInstance()->dispatch(Events::onAfterAddSqlUser, [
        'SqlUserId'       => iMSCP_Database::getInstance()->insertId(),
        'SqlUsername'     => $user,
        'SqlUserHost'     => $host,
        'SqlUserPassword' => isset($password) ? $password : '',
        'SqlDatabaseId'   => $sqldId
    ]);
    write_log(sprintf('A SQL user has been added by %s', $_SESSION['user_logged']), E_USER_NOTICE);
    set_page_message(tr('SQL user successfully added.'), 'success');
    redirectTo('sql_manage.php');
}

/**
 * Generate page
 *
 * @param TemplateEngine $tpl
 * @param int $sqldId
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 */
function generatePage(TemplateEngine $tpl, $sqldId)
{
    checkSqlUserPermissions($tpl, $sqldId);
    generateSqlUserList($tpl, $sqldId);

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

    if (isset($_POST['uaction'])
        && $_POST['uaction'] == 'add_user'
    ) {
        $tpl->assign([
            'USER_NAME'             => isset($_POST['user_name']) ? tohtml($_POST['user_name'], true) : '',
            'USER_HOST'             => isset($_POST['user_host']) ? tohtml($_POST['user_host'], true) : '',
            'USE_DMN_ID'            => isset($_POST['use_dmn_id']) && $_POST['use_dmn_id'] === 'on' ? ' checked' : '',
            'START_ID_POS_SELECTED' => isset($_POST['id_pos']) && $_POST['id_pos'] !== 'end' ? ' selected' : '',
            'END_ID_POS_SELECTED'   => isset($_POST['id_pos']) && $_POST['id_pos'] === 'end' ? ' selected' : ''
        ]);
    } else {
        $tpl->assign([
            'USER_NAME'             => '',
            'USER_HOST'             => tohtml(
                $cfg['DATABASE_USER_HOST'] == '127.0.0.1' ? 'localhost' : decode_idna($cfg['DATABASE_USER_HOST'])
            ),
            'USE_DMN_ID'            => '',
            'START_ID_POS_SELECTED' => ' selected',
            'END_ID_POS_SELECTED'   => ''
        ]);
    }

    $tpl->assign('SQLD_ID', $sqldId);
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

check_login('user');
EventsManager::getInstance()->dispatch(Events::onClientScriptStart);
customerHasFeature('sql') && isset($_REQUEST['sqld_id']) or showBadRequestErrorPage();

$sqldId = intval($_REQUEST['sqld_id']);

if (!empty($_POST)) {
    addSqlUser($sqldId);
}

$tpl = new TemplateEngine();
$tpl->define_dynamic([
    'layout'               => 'shared/layouts/ui.tpl',
    'page'                 => 'client/sql_user_add.tpl',
    'page_message'         => 'layout',
    'show_sqluser_list'    => 'page',
    'sqluser_list'         => 'show_sqluser_list',
    'create_sqluser'       => 'page',
    'mysql_prefix_yes'     => 'create_sqluser',
    'mysql_prefix_no'      => 'create_sqluser',
    'mysql_prefix_all'     => 'create_sqluser',
    'mysql_prefix_infront' => 'create_sqluser',
    'mysql_prefix_behind'  => 'create_sqluser'
]);
$tpl->assign([
    'TR_PAGE_TITLE'               => tohtml(tr('Client / Databases / Overview / Add SQL User')),
    'TR_USER_NAME'                => tohtml(tr('SQL user name')),
    'TR_USER_HOST'                => tohtml(tr('SQL user host')),
    'TR_USER_HOST_TIP'            => tohtml(tr("This is the host from which this SQL user must be allowed to connect to the SQL server. Enter the %s wildcard character to allow this SQL user to connect from any host.", '%'), 'htmlAttr'),
    'TR_USE_DMN_ID'               => tohtml(tr('SQL user prefix/suffix')),
    'TR_START_ID_POS'             => tohtml(tr('In front')),
    'TR_END_ID_POS'               => tohtml(tr('Behind')),
    'TR_ADD'                      => tohtml(tr('Add'), 'htmlAttr'),
    'TR_CANCEL'                   => tohtml(tr('Cancel')),
    'TR_ADD_EXIST'                => tohtml(tr('Assign'), 'htmlAttr'),
    'TR_PASS'                     => tohtml(tr('Password')),
    'TR_PASS_REP'                 => tohtml(tr('Repeat password')),
    'TR_SQL_USER_NAME'            => tohtml(tr('SQL users')),
    'TR_ASSIGN_EXISTING_SQL_USER' => tohtml(tr('Assign existing SQL user')),
    'TR_NEW_SQL_USER_DATA'        => tohtml(tr('New SQL user data'))
]);

generateNavigation($tpl);
generatePage($tpl, $sqldId);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventsManager::getInstance()->dispatch(Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
