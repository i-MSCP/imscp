<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2016 by i-MSCP team
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
 * Generate domain type list
 *
 * @throws iMSCP_Exception
 * @param int $mainDmnId Customer main domain id
 * @param iMSCP_pTemplate $tpl
 * @return void
 */
function generateDomainTypeList($mainDmnId, $tpl)
{
    $stmt = exec_query(
        '
            SELECT
                count(`t2`.`subdomain_id`) AS `sub_count`,
                count(`t3`.`alias_id`) AS `als_count`,
                count(`t4`.`subdomain_alias_id`) AS `alssub_count`
            FROM `domain` AS `t1`
            LEFT JOIN `subdomain` AS `t2` ON(`t2`.`domain_id` = `t1`.`domain_id`)
            LEFT JOIN `domain_aliasses` AS `t3` ON(`t3`.`domain_id` = `t1`.`domain_id`)
            LEFT JOIN `subdomain_alias` AS `t4` ON(`t4`.`alias_id` = `t3`.`alias_id`)
            WHERE `t1`.`domain_id` = ?
        ',
        $mainDmnId
    );
    $row = $stmt->fetchRow();

    $dmns = array(
        array('count' => '1', 'type' => 'dmn', 'tr' => tr('Domain')),
        array('count' => $row['sub_count'], 'type' => 'sub', 'tr' => tr('Subdomain')),
        array('count' => $row['als_count'], 'type' => 'als', 'tr' => tr('Domain alias')),
        array('count' => $row['alssub_count'], 'type' => 'alssub', 'tr' => tr('Subdomain alias'))
    );

    foreach ($dmns as $dmn) {
        if ($dmn['count']) {
            $tpl->assign(array(
                'DOMAIN_TYPE' => tohtml($dmn['type']),
                'DOMAIN_TYPE_SELECTED' => isset($_POST['domain_type']) && $_POST['domain_type'] == $dmn['type']
                    ? ' selected' : ($dmn['type'] == 'dmn' ? ' selected' : ''),
                'TR_DOMAIN_TYPE' => $dmn['tr']
            ));
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
        return array(array(
            'domain_name_val' => $domainName,
            'domain_name' => $domainName
        ));
    }

    switch ($dmnType) {
        case 'sub':
            $query = "
                SELECT CONCAT(`subdomain_name`, '.', '$mainDmnName') AS `name` FROM `subdomain`
                WHERE `domain_id` = ? AND `subdomain_status` = ?
            ";
            break;
        case 'als':
            $query = 'SELECT `alias_name` AS `name` FROM `domain_aliasses` WHERE `domain_id` = ? AND `alias_status` = ?';
            break;
        case 'alssub':
            $query = "
                SELECT CONCAT(`t2`.`subdomain_alias_name`, '.', `t1`.`alias_name`) AS `name`
                FROM `domain_aliasses` AS `t1`
                INNER JOIN `subdomain_alias` AS `t2` ON(`t2`.`alias_id` = `t1`.`alias_id`)
                WHERE `t1`.domain_id = ? AND `t2`.`subdomain_alias_status` = ?
            ";
            break;
        default:
            showBadRequestErrorPage();
            exit;
    }


    $stmt = exec_query($query, array($mainDmnId, 'ok'));
    if (!$stmt->rowCount()) {
        showBadRequestErrorPage();
    }

    $dmnList = array();
    while ($row = $stmt->fetchRow()) {
        $domainName = decode_idna($row['name']);
        $dmnList[] = array(
            'domain_name_val' => $domainName,
            'domain_name' => $domainName
        );
    }

    return $dmnList;
}

/**
 * Add Ftp account
 *
 * @throws iMSCP_Exception_Database
 * @param string $mainDmnName Customer main domain
 * @return bool TRUE on success, FALSE otherwise
 */
function addAccount($mainDmnName)
{
    $ret = true;

    if (!isset($_POST['domain_type'])
        || !isset($_POST['username'])
        || !isset($_POST['domain_name'])
        || !isset($_POST['password'])
        || !isset($_POST['password_repeat'])
        || !isset($_POST['home_dir'])
    ) {
        showBadRequestErrorPage();
    }

    $username = clean_input($_POST['username']);
    $dmnName = clean_input($_POST['domain_name']);
    $passwd = clean_input($_POST['password']);
    $passwdRepeat = clean_input($_POST['password_repeat']);
    $homeDir = clean_input($_POST['home_dir']);

    if (!validates_username($username)) {
        set_page_message(tr('Incorrect username length or syntax.'), 'error');
        $ret = false;
    }

    if ($passwd !== $passwdRepeat) {
        set_page_message(tr('Passwords do not match'), 'error');
        $ret = false;
    } elseif (!checkPasswordSyntax($passwd)) {
        $ret = false;
    }

    // Check for home directory existence
    if ($homeDir != '/' && $homeDir != '') {
        // Strip possible double-slashes
        $homeDir = str_replace('//', '/', $homeDir);

        // Check for updirs '..'
        if (strpos($homeDir, '..') !== false) {
            set_page_message(tr('Invalid home directory.'), 'error');
            $ret = false;
        }

        if ($ret) {
            $vfs = new iMSCP_VirtualFileSystem($mainDmnName);
            if (!$vfs->exists($homeDir)) {
                set_page_message(tr("Home directory '%s' doesn't exist", $homeDir), 'error');
                $ret = false;
            }
        }
    }

    if (!$ret) {
        return $ret;
    }

    // Check that the customer is the owner of the domain for which the ftp Account is added
    if (!customerHasDomain($dmnName, $_SESSION['user_id'])) {
        showBadRequestErrorPage();
    }

    $cfg = iMSCP_Registry::get('config');
    $userid = $username . '@' . decode_idna($dmnName);
    $encryptedPassword = cryptPasswordWithSalt($passwd);
    $shell = '/bin/sh';
    $homeDir = rtrim(str_replace('//', '/', $cfg['USER_WEB_DIR'] . '/' . $mainDmnName . '/' . $homeDir), '/');

    // Retrieve customer uid/gid
    $stmt = exec_query(
        '
            SELECT `t1`.`admin_name`, `t1`.`admin_sys_uid`, `t1`.`admin_sys_gid`, `t2`.`domain_disk_limit`,
                count(`t3`.`name`) AS `quota_entry`
            FROM `admin` AS `t1`
            LEFT JOIN `domain` AS `t2` ON (`t2`.`domain_admin_id` = `t1`.`admin_id` )
            LEFT JOIN `quotalimits` AS `t3` ON (`t3`.`name` = `t1`.`admin_name` )
            WHERE `t1`.`admin_id` = ?
        ',
        $_SESSION['user_id']
    );
    $row = $stmt->fetchRow();

    $groupName = $row['admin_name'];
    $uid = $row['admin_sys_uid'];
    $gid = $row['admin_sys_gid'];
    $diskspaceLimit = $row['domain_disk_limit'];
    $quotaEntriesExist = $row['quota_entry'] ? true : false;

    iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeAddFtp, array(
        'ftpUserId' => $userid,
        'ftpPassword' => $encryptedPassword,
        'ftpRawPassword' => $passwd,
        'ftpUserUid' => $uid,
        'ftpUserGid' => $gid,
        'ftpUserShell' => $shell,
        'ftpUserHome' => $homeDir
    ));

    $db = iMSCP_Database::getInstance();

    try {
        $db->beginTransaction();

        exec_query(
            '
                INSERT INTO `ftp_users` (
                    `userid`, `admin_id`, `passwd`, `rawpasswd`, `uid`, `gid`, `shell`, `homedir`, `status`
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
            ',
            array($userid, $_SESSION['user_id'], $encryptedPassword, $passwd, $uid, $gid, $shell, $homeDir, 'toadd')
        );

        $stmt = exec_query('SELECT `members` FROM `ftp_group` WHERE `groupname` = ? LIMIT 1', $groupName);
        $row = $stmt->fetchRow();

        // Ftp group
        if (!$stmt->rowCount()) {
            exec_query('INSERT INTO `ftp_group` (`groupname`, `gid`, `members`) VALUES (?, ?, ?)', array(
                $groupName, $gid, $userid
            ));
        } else {
            exec_query('UPDATE `ftp_group` SET `members` = ? WHERE `groupname` = ?', array(
                $row['members'] . ",$userid", $groupName
            ));
        }

        // Quota limit
        if (!$quotaEntriesExist) {
            exec_query(
                '
                    INSERT INTO `quotalimits` (
                        `name`, `quota_type`, `per_session`, `limit_type`, `bytes_in_avail`, `bytes_out_avail`,
                        `bytes_xfer_avail`, `files_in_avail`, `files_out_avail`, `files_xfer_avail`
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                     )
                ',
                array($groupName, 'group', 'false', 'hard', $diskspaceLimit * 1024 * 1024, 0, 0, 0, 0, 0)
            );
        }

        $db->commit();
    } catch (iMSCP_Exception_Database $e) {
        $db->rollBack();
        if (!($e->getCode() == 23000)) {
            throw $e;

        }

        set_page_message(tr('Ftp account with same username already exists.'), 'error');
        $ret = false;
    }

    if ($ret) {
        iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterAddFtp, array(
            'ftpUserId' => $userid,
            'ftpPassword' => $encryptedPassword,
            'ftpRawPassword' => $passwd,
            'ftpUserUid' => $uid,
            'ftpUserGid' => $gid,
            'ftpUserShell' => $shell,
            'ftpUserHome' => $homeDir
        ));
        send_request();
        write_log(sprintf('%s added Ftp account: %s', $_SESSION['user_logged'], $userid), E_USER_NOTICE);
        set_page_message(tr('FTP account successfully added.'), 'success');
    }

    return $ret;
}

/**
 * Generate page data
 *
 * @param string $mainDmn Customer main domain name
 * @param string $mainDmnId Customer main domain id
 * @param iMSCP_pTemplate $tpl
 * @return void
 */
function generatePage($mainDmn, $mainDmnId, $tpl)
{
    $tpl->assign(array(
        'USERNAME' => isset($_POST['username']) ? tohtml(clean_input($_POST['username'])) : '',
        'PASSWORD' => isset($_POST['password']) ? tohtml(clean_input($_POST['password'])) : '',
        'PASSWORD_REPEAT' => isset($_POST['password_repeat']) ? tohtml(clean_input($_POST['password_repeat'])) : '',
        'HOME_DIR' => isset($_POST['home_dir']) ? tohtml(clean_input($_POST['home_dir'])) : '/'
    ));

    generateDomainTypeList($mainDmnId, $tpl);

    $dmnList = getDomainList(
        $mainDmn, $mainDmnId, isset($_POST['domain_type']) ? clean_input($_POST['domain_type']) : 'dmn'
    );

    foreach ($dmnList as $dmn) {
        $tpl->assign(array(
            'DOMAIN_NAME_VAL' => tohtml($dmn['domain_name_val'], 'htmlAttr'),
            'DOMAIN_NAME' => tohtml($dmn['domain_name']),
            'DOMAIN_NAME_SELECTED' => isset($_POST['domain_name']) && $_POST['domain_name'] === $dmn['domain_name']
                ? ' selected' : ''
        ));
        $tpl->parse('DOMAIN_LIST', '.domain_list');
    }
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);
check_login('user');
customerHasFeature('ftp') or showBadRequestErrorPage();

$mainDmnProps = get_domain_default_props($_SESSION['user_id']);
$mainDmnId = $mainDmnProps['domain_id'];
$mainDmnName = $mainDmnProps['domain_name'];
$ftpAccountLimit = $mainDmnProps['domain_ftpacc_limit'];

if (is_xhr() && isset($_POST['domain_type'])) {
    echo json_encode(getDomainList($mainDmnName, $mainDmnId, clean_input($_POST['domain_type'])));
    return;
}

if (!empty($_POST)) {
    // Check for ftp account limit (only on new account submission to avoid too many query each time the page
    // is displayed
    $nbFtpAccounts = get_customer_running_ftp_acc_cnt($_SESSION['user_id']);
    if ($ftpAccountLimit && $nbFtpAccounts >= $ftpAccountLimit) {
        set_page_message(tr('FTP account limit reached.'), 'error');
        redirectTo('ftp_accounts.php');
    }

    if (addAccount($mainDmnName)) {
        redirectTo('ftp_accounts.php');
    }
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'client/ftp_add.tpl',
    'page_message' => 'layout',
    'domain_list' => 'page',
    'domain_types' => 'page'
));
$tpl->assign(array(
    'TR_PAGE_TITLE' => tr('Client / FTP / Add FTP Account'),
    'TR_FTP_ACCOUNT_DATA' => tr('Ftp account data'),
    'TR_DOMAIN_TYPE_LABEL' => tr('Domain type'),
    'TR_USERNAME' => tr('Username'),
    'TR_PASSWORD' => tr('Password'),
    'TR_PASSWORD_REPEAT' => tr('Repeat password'),
    'TR_HOME_DIR' => tr('Home directory'),
    'TR_CHOOSE_DIR' => tr('Choose dir'),
    'TR_ADD' => tr('Add'),
    'TR_CANCEL' => tr('Cancel')
));

iMSCP_Events_Aggregator::getInstance()->registerListener('onGetJsTranslations', function ($e) {
    /** @var $e iMSCP_Events_Event */
    $translations = $e->getParam('translations');
    $translations['core']['close'] = tr('Close');
    $translations['core']['ftp_directories'] = tr('Ftp directories');
});

generateNavigation($tpl);
generatePage($mainDmnName, $mainDmnId, $tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();

unsetMessages();
