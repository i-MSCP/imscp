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

use iMSCP\Crypt as Crypt;
use iMSCP\VirtualFileSystem as VirtualFileSystem;
use iMSCP_Events as Events;
use iMSCP_Exception as iMSCPException;
use iMSCP_Registry as Registry;
use iMSCP\TemplateEngine;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generate domain type list
 *
 * @param int $mainDmnId Customer main domain id
 * @param TemplateEngine $tpl
 * @return void
 */
function generateDomainTypeList($mainDmnId, $tpl)
{
    $stmt = exec_query(
        '
            SELECT count(t2.subdomain_id) AS sub_count, count(t3.alias_id) AS als_count,
                count(t4.subdomain_alias_id) AS alssub_count
            FROM domain AS t1
            LEFT JOIN subdomain AS t2 ON(t2.domain_id = t1.domain_id)
            LEFT JOIN domain_aliasses AS t3 ON(t3.domain_id = t1.domain_id)
            LEFT JOIN subdomain_alias AS t4 ON(t4.alias_id = t3.alias_id)
            WHERE t1.domain_id = ?
        ',
        [$mainDmnId]
    );
    $row = $stmt->fetch();

    $domains = [
        ['count' => '1', 'type' => 'dmn', 'tr' => tr('Domain')],
        ['count' => $row['sub_count'], 'type' => 'sub', 'tr' => tr('Subdomain')],
        ['count' => $row['als_count'], 'type' => 'als', 'tr' => tr('Domain alias')],
        ['count' => $row['alssub_count'], 'type' => 'alssub', 'tr' => tr('Subdomain alias')]
    ];

    foreach ($domains as $domain) {
        if ($domain['count']) {
            $tpl->assign([
                'DOMAIN_TYPE'          => tohtml($domain['type']),
                'DOMAIN_TYPE_SELECTED' => (isset($_POST['domain_type']) && $_POST['domain_type'] == $domain['type'])
                    ? ' selected' : ($domain['type'] == 'dmn' ? ' selected' : ''),
                'TR_DOMAIN_TYPE'       => $domain['tr']
            ]);
            $tpl->parse('DOMAIN_TYPES', '.domain_types');
        }
    }
}

/**
 * Get domain list
 *
 * @param string $mainDmnName Customer main domain name
 * @param string $mainDmnId Customer main domain id
 * @param string $dmnType Domain type (dmn|sub|als|alssub) for which list must be generated
 * @return array Domain list
 */
function getDomainList($mainDmnName, $mainDmnId, $dmnType = 'dmn')
{
    if ($dmnType == 'dmn') {
        $domainName = decode_idna($mainDmnName);
        return [[
            'domain_name_val' => $domainName,
            'domain_name'     => $domainName
        ]];
    }

    switch ($dmnType) {
        case 'sub':
            $query = "
                SELECT CONCAT(subdomain_name, '.', '$mainDmnName') AS name FROM subdomain
                WHERE domain_id = ? AND subdomain_status = ?
            ";
            break;
        case 'als':
            $query = 'SELECT alias_name AS name FROM domain_aliasses WHERE domain_id = ? AND alias_status = ?';
            break;
        case 'alssub':
            $query = "
                SELECT CONCAT(t2.subdomain_alias_name, '.', t1.alias_name) AS name
                FROM domain_aliasses AS t1
                JOIN subdomain_alias AS t2 ON(t2.alias_id = t1.alias_id)
                WHERE t1.domain_id = ?
                AND t2.subdomain_alias_status = ?
            ";
            break;
        default:
            showBadRequestErrorPage();
            exit;
    }


    $stmt = exec_query($query, [$mainDmnId, 'ok']);
    if (!$stmt->rowCount()) {
        showBadRequestErrorPage();
    }

    $dmnList = [];
    while ($row = $stmt->fetch()) {
        $domainName = decode_idna($row['name']);
        $dmnList[] = [
            'domain_name_val' => $domainName,
            'domain_name'     => $domainName
        ];
    }

    return $dmnList;
}

/**
 * Add Ftp account
 *
 * @throws iMSCPException
 * @return bool TRUE on success, FALSE on failure
 */
function addAccount()
{
    if (!isset($_POST['domain_type'])
        || !isset($_POST['username'])
        || !isset($_POST['domain_name'])
        || !isset($_POST['password'])
        || !isset($_POST['password_repeat'])
        || !isset($_POST['home_dir'])
    ) {
        showBadRequestErrorPage();
    }

    $error = false;
    $username = clean_input($_POST['username']);
    $dmnName = mb_strtolower(clean_input($_POST['domain_name']));
    $passwd = clean_input($_POST['password']);
    $passwdRepeat = clean_input($_POST['password_repeat']);
    $homeDir = utils_normalizePath('/' . clean_input($_POST['home_dir']));

    if (!customerHasDomain($dmnName, $_SESSION['user_id'])) {
        showBadRequestErrorPage();
    }

    if (!validates_username($username)) {
        set_page_message(tr('Invalid FTP username.'), 'error');
        $error = true;
    }

    if ($passwd !== $passwdRepeat) {
        set_page_message(tr('Passwords do not match.'), 'error');
        $error = true;
    } elseif (!checkPasswordSyntax($passwd)) {
        $error = true;
    }

    if ($homeDir === '') {
        set_page_message(tr('FTP home directory cannot be empty.'), 'error');
        $error = true;
    }

    if ($error) {
        return false;
    }

    $mainDmnProps = get_domain_default_props($_SESSION['user_id']);

    $vfs = new VirtualFileSystem($_SESSION['user_logged']);
    if ($homeDir !== '/'
        && !$vfs->exists($homeDir, VirtualFileSystem::VFS_TYPE_DIR)
    ) {
        set_page_message(tr("Directory '%s' doesn't exist.", $homeDir), 'error');
        return false;
    }

    $username .= '@' . encode_idna($dmnName);
    $homeDir = utils_normalizePath(
        '/' . Registry::get('config')['USER_WEB_DIR'] . '/' . $mainDmnProps['domain_name'] . '/' . $homeDir
    );
    $stmt = exec_query(
        '
            SELECT t1.admin_name, t1.admin_sys_uid, t1.admin_sys_gid, t2.domain_disk_limit, t3.name AS quota_entry
            FROM admin AS t1
            JOIN domain AS t2 ON (t2.domain_admin_id = t1.admin_id)
            LEFT JOIN quotalimits AS t3 ON (t3.name = t1.admin_name)
            WHERE t1.admin_id = ?
        ',
        [$_SESSION['user_id']]
    );
    $row1 = $stmt->fetch();

    /** @var iMSCP_Database $db */
    $db = Registry::get('iMSCP_Application')->getDatabase();

    try {
        $db->beginTransaction();

        Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onBeforeAddFtp, [
            'ftpUserId'    => $username,
            'ftpPassword'  => $passwd,
            'ftpUserUid'   => $row1['admin_sys_uid'],
            'ftpUserGid'   => $row1['admin_sys_gid'],
            'ftpUserShell' => '/bin/sh',
            'ftpUserHome'  => $homeDir
        ]);

        exec_query(
            "
                INSERT INTO ftp_users (
                    userid, admin_id, passwd, uid, gid, shell, homedir, status
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, 'toadd'
                )
            ",
            [
                $username, $_SESSION['user_id'], Crypt::sha512($passwd), $row1['admin_sys_uid'], $row1['admin_sys_gid'],
                '/bin/sh', $homeDir
            ]
        );

        exec_query(
            "
                INSERT INTO ftp_group (groupname, gid, members) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE members = CONCAT(members, ',', ?)
            ",
            [$row1['admin_name'], $row1['admin_sys_gid'], $username, $username]
        );

        if (!$row1['quota_entry']) {
            exec_query(
                "
                    INSERT INTO quotalimits (
                        name, quota_type, per_session, limit_type, bytes_in_avail, bytes_out_avail, bytes_xfer_avail,
                        files_in_avail, files_out_avail, files_xfer_avail
                    ) VALUES (
                        ?, 'group', 'false', 'hard', ?, 0, 0, 0, 0, 0
                     )
                ",
                [$row1['admin_name'], ($row1['domain_disk_limit']) ? $row1['domain_disk_limit'] * 1024 * 1024 : 0]
            );
        }

        Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onAfterAddFtp, [
            'ftpUserId'    => $username,
            'ftpPassword'  => $passwd,
            'ftpUserUid'   => $row1['admin_sys_uid'],
            'ftpUserGid'   => $row1['admin_sys_gid'],
            'ftpUserShell' => '/bin/sh',
            'ftpUserHome'  => $homeDir
        ]);

        $db->commit();
        send_request();
        write_log(
            sprintf('A new FTP account (%s) has been created by %s', $username, $_SESSION['user_logged']), E_USER_NOTICE
        );
        set_page_message(tr('FTP account successfully added.'), 'success');
    } catch (iMSCPException $e) {
        $db->rollBack();
        if ($e->getCode() == 23000) {
            set_page_message(tr('FTP account already exists.'), 'error');
            return false;
        }

        throw $e;
    }

    return true;
}

/**
 * Generate page
 *
 * @param TemplateEngine $tpl
 * @return void
 */
function generatePage($tpl)
{
    $mainDmnProps = get_domain_default_props($_SESSION['user_id']);

    # Set parameters for the FTP chooser
    $_SESSION['ftp_chooser_domain_id'] = $mainDmnProps['domain_id'];
    $_SESSION['ftp_chooser_user'] = $_SESSION['user_logged'];
    $_SESSION['ftp_chooser_root_dir'] = '/';
    $_SESSION['ftp_chooser_hidden_dirs'] = [];
    $_SESSION['ftp_chooser_unselectable_dirs'] = [];

    $tpl->assign([
        'USERNAME' => isset($_POST['username']) ? tohtml($_POST['username'], 'htmlAttr') : '',
        'HOME_DIR' => isset($_POST['home_dir']) ? tohtml($_POST['home_dir'], 'htmlAttr') : '/'
    ]);

    generateDomainTypeList($mainDmnProps['domain_id'], $tpl);
    $dmnList = getDomainList(
        $mainDmnProps['domain_name'],
        $mainDmnProps['domain_id'],
        (isset($_POST['domain_type'])) ? clean_input($_POST['domain_type']) : 'dmn'
    );

    foreach ($dmnList as $dmn) {
        $tpl->assign([
            'DOMAIN_NAME_VAL'      => tohtml($dmn['domain_name_val'], 'htmlAttr'),
            'DOMAIN_NAME'          => tohtml($dmn['domain_name']),
            'DOMAIN_NAME_SELECTED' => (isset($_POST['domain_name']) && $_POST['domain_name'] == $dmn['domain_name'])
                ? ' selected' : ''
        ]);
        $tpl->parse('DOMAIN_LIST', '.domain_list');
    }
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

check_login('user');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onClientScriptStart);
customerHasFeature('ftp') or showBadRequestErrorPage();

$mainDmnProps = get_domain_default_props($_SESSION['user_id']);

if (is_xhr()
    && isset($_POST['domain_type'])
) {
    echo json_encode(
        getDomainList($mainDmnProps['domain_name'], $mainDmnProps['domain_id'], clean_input($_POST['domain_type']))
    );
    return;
}

if (!empty($_POST)) {
    $nbFtpAccounts = get_customer_ftp_users_count($_SESSION['user_id']);

    if ($mainDmnProps['domain_ftpacc_limit'] && $nbFtpAccounts >= $mainDmnProps['domain_ftpacc_limit']) {
        set_page_message(tr('FTP account limit reached.'), 'error');
        redirectTo('ftp_accounts.php');
    }

    if (addAccount()) {
        redirectTo('ftp_accounts.php');
    }
}

$tpl = new TemplateEngine();
$tpl->define([
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'client/ftp_add.tpl',
    'page_message' => 'layout',
    'domain_list'  => 'page',
    'domain_types' => 'page'
]);
$tpl->assign([
    'TR_PAGE_TITLE'        => tr('Client / FTP / Add FTP Account'),
    'TR_FTP_ACCOUNT_DATA'  => tr('Ftp account data'),
    'TR_DOMAIN_TYPE_LABEL' => tr('Domain type'),
    'TR_USERNAME'          => tr('Username'),
    'TR_PASSWORD'          => tr('Password'),
    'TR_PASSWORD_REPEAT'   => tr('Repeat password'),
    'TR_HOME_DIR'          => tr('Home directory'),
    'TR_CHOOSE_DIR'        => tr('Choose dir'),
    'TR_ADD'               => tr('Add'),
    'TR_CANCEL'            => tr('Cancel')
]);

Registry::get('iMSCP_Application')->getEventsManager()->registerListener(Events::onGetJsTranslations, function ($e) {
    /** @var $e iMSCP_Events_Event */
    $translations = $e->getParam('translations');
    $translations['core']['close'] = tr('Close');
    $translations['core']['ftp_directories'] = tr('FTP home directory');
});

generateNavigation($tpl);
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
