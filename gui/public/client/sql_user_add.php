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
 * Check SQL permissions
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $databaseId Database unique identifier
 */
function client_checkSqlUserPermissions($tpl, $databaseId)
{
    $domainProps = get_domain_default_props($_SESSION['user_id']);

    if ($domainProps['domain_sqlu_limit'] != 0
        && get_customer_sql_databases_count($domainProps['domain_id']) >= $domainProps['domain_sqlu_limit']
    ) {
        $tpl->assign('CREATE_SQLUSER', '');
    }

    $stmt = exec_query(
        'SELECT COUNT(sqld_id) FROM sql_database JOIN domain USING(domain_id) WHERE sqld_id = ? AND domain_id = ?',
        [$databaseId, $domainProps['domain_id']]
    );

    if (!$stmt->fetchRow(PDO::FETCH_COLUMN)) {
        showBadRequestErrorPage();
    }
}

/**
 * Get SQL user list
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $customerId Customer id
 * @param int $databaseId Database id
 * @return void
 */
function client_generateSqlUserList($tpl, $customerId, $databaseId)
{
    // Select all SQL users that are owned by $customerId except those that are
    // already assigned to $databaseId
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
        [$databaseId, get_user_domain_id($customerId), $databaseId]
    );

    if ($stmt->rowCount()) {
        while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
            $tpl->assign([
                'SQLUSER_ID'  => $row['sqlu_id'],
                'SQLUSER_IDN' => tohtml($row['sqlu_name'] . '@' . decode_idna($row['sqlu_host'])),
            ]);
            $tpl->parse('SQLUSER_LIST', '.sqluser_list');
        }
    } else {
        $tpl->assign('SHOW_SQLUSER_LIST', '');
    }
}

/**
 * Does the given SQL user already exists?
 *
 * @param string $sqlUser SQL user name
 * @param string $sqlUserHost SQL user host
 * @return bool TRUE if the given sql user already exists, FALSE otherwise
 */
function client_isSqlUser($sqlUser, $sqlUserHost)
{
    return (bool)exec_query(
        'SELECT COUNT(User) FROM mysql.user WHERE User = ? AND Host = ?', [$sqlUser, $sqlUserHost]
    )->fetchRow(PDO::FETCH_COLUMN);
}

/**
 * Add SQL user for the given database
 *
 * @throws Exception
 * @throws iMSCP_Exception_Database
 * @param int $customerId Customer unique identifier
 * @param int $databaseId Database unique identifier
 * @return void
 */
function client_addSqlUser($customerId, $databaseId)
{
    if (empty($_POST)) {
        return;
    }

    if (!isset($_POST['uaction'])) {
        showBadRequestErrorPage();
    }

    $dmnId = get_user_domain_id($customerId);

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
            && !iMSCP_Validate::getInstance()->hostname($host, ['allow' => Zend_Validate_Hostname::ALLOW_DNS | Zend_Validate_Hostname::ALLOW_IP])
        ) {
            set_page_message(tr('Invalid SQL user host: %s', iMSCP_Validate::getInstance()->getLastValidationMessages()), 'error');
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

        if (client_isSqlUser($user, $host) || in_array($user, ['debian-sys-maint', 'mysql.user', 'root'])) {
            set_page_message(tr("The `%s' SQL user is not available or not permitted.", $user . '@' . decode_idna($host)), 'error');
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
            [intval($_POST['sqluser_id']), $databaseId, $dmnId]
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
    $stmt = exec_query('SELECT sqld_name FROM sql_database WHERE sqld_id = ? AND domain_id = ?', [$databaseId, $dmnId]);

    if (!$stmt->rowCount()) {
        showBadRequestErrorPage();
    }

    $row = $stmt->fetchRow(PDO::FETCH_ASSOC);
    $dbName = $row['sqld_name'];
    $config = iMSCP_Registry::get('config');
    $mysqlConfig = new iMSCP_Config_Handler_File($config['CONF_DIR'] . '/mysql/mysql.data');

    iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeAddSqlUser);

    // Here we cannot use transaction due to statements that cause an implicit commit. Thus we execute
    // those statements first to let the i-MSCP database in clean state if one of them fails.
    // See https://dev.mysql.com/doc/refman/5.7/en/implicit-commit.html for more details

    if ($needUserCreate && isset($password)) {
        if ($mysqlConfig['SQLD_TYPE'] == 'mariadb' || version_compare($mysqlConfig['SQLD_VERSION'], '5.7.6', '<')) {
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
    $dbName = preg_replace('/([%_])/', '\\\\$1', $dbName);

    exec_query(sprintf('GRANT ALL PRIVILEGES ON %s.* to ?@?', quoteIdentifier($dbName)), [$user, $host]);
    exec_query('INSERT INTO sql_user (sqld_id, sqlu_name, sqlu_host) VALUES (?, ?, ?)', [
        $databaseId, $user, $host
    ]);

    iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterAddSqlUser);

    write_log(sprintf('A SQL user has been added by %s', $_SESSION['user_logged']), E_USER_NOTICE);
    set_page_message(tr('SQL user successfully added.'), 'success');
    redirectTo('sql_manage.php');
}

/**
 * Generate page
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $databaseId
 * @return void
 */
function client_generatePage($tpl, $databaseId)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

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

    if (isset($_POST['uaction']) && $_POST['uaction'] == 'add_user') {
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
                $cfg['DATABASE_USER_HOST'] == '127.0.0.1'
                    ? 'localhost' : decode_idna($cfg['DATABASE_USER_HOST'])
            ),
            'USE_DMN_ID'            => '',
            'START_ID_POS_SELECTED' => ' selected',
            'END_ID_POS_SELECTED'   => ''
        ]);
    }

    $tpl->assign('ID', $databaseId);
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);
check_login('user');

if (!customerHasFeature('sql') || !isset($_REQUEST['id'])) {
    showBadRequestErrorPage();
}

$databaseId = intval($_REQUEST['id']);

client_addSqlUser($_SESSION['user_id'], $databaseId);

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic([
    'layout'               => 'shared/layouts/ui.tpl',
    'page'                 => 'client/sql_user_add.tpl',
    'page_message'         => 'layout',
    'mysql_prefix_no'      => 'page',
    'mysql_prefix_yes'     => 'page',
    'mysql_prefix_infront' => 'page',
    'mysql_prefix_behind'  => 'page',
    'mysql_prefix_all'     => 'page',
    'sqluser_list'         => 'page',
    'show_sqluser_list'    => 'page',
    'create_sqluser'       => 'page'
]);

$tpl->assign([
    'TR_PAGE_TITLE'               => tr('Client / Databases / Overview / Add SQL User'),
    'TR_ADD_SQL_USER'             => tr('Add SQL user'),
    'TR_USER_NAME'                => tr('SQL user name'),
    'TR_USER_HOST'                => tr('SQL user host'),
    'TR_USER_HOST_TIP'            => tr("This is the host from which this SQL user must be allowed to connect to the SQL server. Enter the %s wildcard character to allow this SQL user to connect from any host.", '%'),
    'TR_USE_DMN_ID'               => tr('SQL user prefix/suffix'),
    'TR_START_ID_POS'             => tr('In front'),
    'TR_END_ID_POS'               => tr('Behind'),
    'TR_ADD'                      => tr('Add'),
    'TR_CANCEL'                   => tr('Cancel'),
    'TR_ADD_EXIST'                => tr('Assign'),
    'TR_PASS'                     => tr('Password'),
    'TR_PASS_REP'                 => tr('Repeat password'),
    'TR_SQL_USER_NAME'            => tr('SQL users'),
    'TR_ASSIGN_EXISTING_SQL_USER' => tr('Assign existing SQL user'),
    'TR_NEW_SQL_USER_DATA'        => tr('New SQL user data')
]);

client_checkSqlUserPermissions($tpl, $databaseId);
client_generateSqlUserList($tpl, $_SESSION['user_id'], $databaseId);
client_generatePage($tpl, $databaseId);
generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
