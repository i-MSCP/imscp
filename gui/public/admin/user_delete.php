<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010 by i-MSCP | http://i-mscp.net
 * @version 	SVN: $Id$
 * @link 		http://i-mscp.net
 * @author 		ispCP Team
 * @author 		i-MSCP Team
 *
 * @license
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
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 * Portions created by the i-MSCP Team are Copyright (C) 2010 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

/************************************************************************************
 * Script functions
 */

/**
 * Deletes an user (admin or reseller).
 *
 * @param int $userId User unique identifier
 */
function admin_deleteUser($userId)
{
    $userId = (int) $userId;

    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    /** @var $db iMSCP_Database */
    $db = iMSCP_Registry::get('db');

    $query = "
		SELECT
			a.`admin_type`, b.`logo`
		FROM
		    `admin` a
		LEFT JOIN
			`user_gui_props` b ON (b.`user_id` = a.`admin_id`)
		WHERE
			`admin_id` = ?
	";

    $stmt = exec_query($query, $userId);
    $data = $stmt->fetchRow();
    $userType = $data['admin_type'];

    if (empty($userType) || $userType == 'user') {
        set_page_message(tr('Invalid user Id.'), 'error');
        redirectTo('manage_users.php');
    }

    // Users (admins/resellers) common items to delete
    $itemsToDelete = array(
        'admin' => 'admin_id = ?',
        'email_tpls' => 'owner_id = ?',
        'tickets' => 'ticket_from = ? OR ticket_to = ?',
        'user_gui_props' => 'user_id = ?');

    // Note: Admin can also have they own hosting_plans bug must not be considerated
    // as common item since first admin must be never removed
    if ($userType == 'reseller') {
        // Getting reseller's software package to remove inf any
        $query = '
		    SELECT
			    `software_id`, `software_archive`
		    FROM
			    `web_software`
		    WHERE
			    `reseller_id` = ?
	    ';
        $stmt = exec_query($query, $userId);
        $swPackages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Getting custom relesse isp logo if set
        $resellerLogo = $data['logo'];

        // Add specific reseller items to remove
        $itemsToDelete = array_merge(array(
                                          'hosting_plans' => 'reseller_id = ?',
                                          'reseller_props' => 'reseller_id = ?',
                                          'web_software' => 'reseller_id = ?',
                                          'orders' => 'user_id = ?',
                                          'orders_settings' => 'user_id = ?'
                                     ),
                                     $itemsToDelete);
    }

    // We are using transaction to ensure data consistency and prevent any garbage in
    // the database. If one query fail, the whole process is reverted (annulé).

    try {
        // Cleanup database
        $db->beginTransaction();

        foreach ($itemsToDelete as $table => $where) {
            // Build the DELETE statement
            $query = "DELETE FROM "
                     . quoteIdentifier($table)
                     . (($where) ? " WHERE $where" : '');

            exec_query($query, array_fill(0, substr_count($where, '?'), $userId));
        }

        $db->commit();

        // Cleanup file system

        // We are safe here. We don't stop the process same if files cannot be
        // removed. That can result in garbages but the sysadmin can easily delete
        // them through ssh.

        // Deleting reseller software instaler local repository
        if(isset($swPackages) && !empty($swPackages)) {
            _admin_deleteResellerSwPackages($userId, $swPackages);
        }

        // Deleting user logo
        if (isset($resellerLogo) && !empty($resellerLogo)) {
            $logoPath = $cfg->GUI_ROOT_DIR . '/data/ispLogos/' . $resellerLogo;

            if (file_exists($logoPath) && !@unlink($logoPath)) {
                write_log('Unable to remove user logo ' . $logoPath, E_USER_ERROR);
            }
        }

        $userTr = ($userType == 'reseller') ? tr('Reseller') : tr('Admin');
        set_page_message(tr('%s account successfully deleted.', $userTr), 'success');
        write_log($_SESSION['user_logged'] . ": deletes user " . $userId, E_USER_NOTICE);
    } catch (PDOException $e) {
        $db->rollBack();
        set_page_message(tr('Unable to delete user with Id: %d', $userId), 'error');
        write_log('Unable to delete user Id ' . $userId, E_USER_ERROR);

    }

    redirectTo('manage_users.php');
}

/**
 * Delete reseller softwares.
 *
 * @param int $userId Reseller unique identifier
 * @param array $swPackages Array that contains software package to remove
 * @return void
 */
function _admin_deleteResellerSwPackages($userId, array $swPackages)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    // Remove all reseller's software packages if any
    foreach($swPackages as $package) {
        $packagePath = $cfg->GUI_SOFTWARE_DIR . '/' . $userId . '/' .
                   $package['software_archive'] . '-' .
                   $package['software_id'] . '.tar.gz';


        if (file_exists($packagePath) && !@unlink($packagePath)) {
            write_log('Unable to remove reseller package ' . $packagePath, E_USER_ERROR);
        }
    }

    // Remove reseller software installer local repository directory
    $resellerSwDirectory = $cfg->GUI_SOFTWARE_DIR . '/' . $userId . '/';

    if (is_dir($resellerSwDirectory) && @rmdir($resellerSwDirectory)) {
        write_log('Unable to remove reseller softwares directory ' .
                  $resellerSwDirectory, E_USER_ERROR);
    }
}

/**
 * Validates user deletion.
 *
 * @param int $userId User unique identifier
 * @return bool TRUE if deletion can be done, FALSE otherwise
 */
function admin_validateUserDeletion($userId)
{
    $userId = (int) $userId;
    $retVal = false;

    // Checking for domains created by user
    $query = "
	    SELECT
	        COUNT(`domain_id`) `num_domains`
	    FROM
	        `domain`
	    WHERE
	        `domain_created_id` = ?
	";
    $stmt = exec_query($query, $userId);

    if ($stmt->fields['num_domains'] == 0) {
        $query = "SELECT `admin_type` FROM `admin` WHERE `admin_id` = ?";
        $stmt = exec_query($query, $userId);

        $type = $stmt->fields['admin_type'];

        if ($type == 'admin' || $type == 'reseller') {
            $retVal = true;
        } else {
            set_page_message(tr('Invalid user Id.'), 'error');
        }
    } else {
        set_page_message(tr("You can't delete a reseller that has domain accounts. Please, remove them before."), 'error');
    }

    return $retVal;
}

/**
 * Validates domain account deletion.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $domainId Domain account unique identifier
 */
function admin_validateDomainAccountDeletion($tpl, $domainId)
{

    $domainId = (int) $domainId;

    // Checking for domain account owner
    $query = "
	    SELECT
	        `domain_id`, `domain_name`, `domain_created_id`
	    FROM
	        `domain`
	    WHERE
	        `domain_id` = ?
	";
    $stmt = exec_query($query, $domainId);

    if ($stmt->fields['domain_id'] == 0) {
        set_page_message(tr('Wrong domain Id.'), 'warning');
        redirectTo('manage_users.php');
    }

    $tpl->assign(array(
                      'TR_DELETE_DOMAIN' => tr('Delete domain'),
                      'TR_DOMAIN_SUMMARY' => tr('Domain account summary'),
                      'TR_DOMAIN_EMAILS' => tr('Domain e-mails'),
                      'TR_DOMAIN_FTPS' => tr('Domain FTP accounts'),
                      'TR_DOMAIN_ALIASES' => tr('Domain aliases'),
                      'TR_DOMAIN_SUBS' => tr('Domain subdomains'),
                      'TR_DOMAIN_DBS' => tr('Domain databases'),
                      'TR_REALLY_WANT_TO_DELETE_DOMAIN' => tr('Do you really want to delete the entire domain? This operation cannot be undone.'),
                      'TR_BUTTON_DELETE' => tr('Delete domain'),
                      'TR_YES_DELETE_DOMAIN' => tr('Yes, delete the domain.'),
                      'DOMAIN_NAME' => tohtml($stmt->fields['domain_name']),
                      'DOMAIN_ID' => $stmt->fields['domain_id']));

    // Checking for domain's mail accounts
    $query = "SELECT * FROM `mail_users` WHERE `domain_id` = ?";
    $stmt = exec_query($query, $domainId);

    if (!$stmt->EOF) {
        while (!$stmt->EOF) {
            // Create mail type's text
            $mail_types = explode(',', $stmt->fields['mail_type']);
            $mdisplay_a = array();

            foreach ($mail_types as $mtype) {
                $mdisplay_a[] = user_trans_mail_type($mtype);
            }

            $mdisplay_txt = implode(', ', $mdisplay_a);

            $tpl->assign(array(
                              'MAIL_ADDR' => tohtml($stmt->fields['mail_addr']),
                              'MAIL_TYPE' => $mdisplay_txt));

            $tpl->parse('MAIL_ITEM', '.mail_item');
            $stmt->moveNext();
        }
    } else {
        $tpl->assign('MAIL_LIST', '');
    }

    // Check for FTP account in domain
    $query = "
	    SELECT
	        `ftp_users`.*
	    FROM
	        `ftp_users`, `domain`
	    WHERE
	        `domain`.`domain_id` = ?
	    AND
	        `ftp_users`.`uid` = `domain`.`domain_uid`
	";
    $stmt = exec_query($query, $domainId);

    if (!$stmt->EOF) {
        while (!$stmt->EOF) {

            $tpl->assign(array(
                              'FTP_USER' => tohtml($stmt->fields['userid']),
                              'FTP_HOME' => tohtml($stmt->fields['homedir'])));

            $tpl->parse('FTP_ITEM', '.ftp_item');
            $stmt->moveNext();
        }
    } else {
        $tpl->assign('FTP_LIST', '');
    }

    // Check for domain's aliases
    $alias_a = array();
    $query = "SELECT * FROM `domain_aliasses` WHERE `domain_id` = ?";
    $stmt = exec_query($query, $domainId);

    if (!$stmt->EOF) {
        while (!$stmt->EOF) {
            $alias_a[] = $stmt->fields['alias_id'];

            $tpl->assign(array(
                              'ALS_NAME' => tohtml($stmt->fields['alias_name']),
                              'ALS_MNT' => tohtml($stmt->fields['alias_mount'])));

            $tpl->parse('ALS_ITEM', '.als_item');
            $stmt->moveNext();
        }
    } else {
        $tpl->assign('ALS_LIST', '');
    }

    // check for subdomains
    $any_sub_found = false;
    $query = "SELECT * FROM `subdomain` WHERE `domain_id` = ?";
    $stmt = exec_query($query, $domainId);

    while (!$stmt->EOF) {
        $any_sub_found = true;
        $tpl->assign(array(
                          'SUB_NAME' => tohtml($stmt->fields['subdomain_name']),
                          'SUB_MNT' => tohtml($stmt->fields['subdomain_mount'])));

        $tpl->parse('SUB_ITEM', '.sub_item');
        $stmt->moveNext();
    }

    if (!$any_sub_found) {
        $tpl->assign('SUB_LIST', '');
    }

    // Check subdomain_alias
    if (count($alias_a) > 0) {
        $aliasIds = implode(',', $alias_a);

        $query = "SELECT * FROM `subdomain_alias` WHERE `alias_id` IN ($aliasIds)";
        $stmt = execute_query($query);

        while (!$stmt->EOF) {
            $any_sub_found = true;
            $tpl->assign(array(
                              'SUB_NAME' => tohtml($stmt->fields['subdomain_alias_name']),
                              'SUB_MNT' => tohtml($stmt->fields['subdomain_alias_mount'])));

            $tpl->parse('SUB_ITEM', '.sub_item');
            $stmt->moveNext();
        }
    }

    // Check for databases and -users
    $query = "SELECT * FROM `sql_database` WHERE `domain_id` = ?";
    $stmt = exec_query($query, $domainId);

    if (!$stmt->EOF) {
        while (!$stmt->EOF) {
            $query = "SELECT * FROM `sql_user` WHERE `sqld_id` = ?";
            $ures = exec_query($query, $stmt->fields['sqld_id']);

            $users_a = array();

            while (!$ures->EOF) {
                $users_a[] = $ures->fields['sqlu_name'];
                $ures->moveNext();
            }

            $users_txt = implode(', ', $users_a);

            $tpl->assign(array(
                              'DB_NAME' => tohtml($stmt->fields['sqld_name']),
                              'DB_USERS' => tohtml($users_txt)));

            $tpl->parse('DB_ITEM', '.db_item');
            $stmt->moveNext();
        }
    } else {
        $tpl->assign('DB_LIST', '');
    }
}

/************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page', $cfg->ADMIN_TEMPLATE_PATH . '/user_delete.tpl');

$tpl->define_dynamic(array('mail_list' => 'page',
                          'ftp_list' => 'page',
                          'als_list' => 'page',
                          'sub_list' => 'page',
                          'db_list' => 'page',
                          'mail_item' => 'mail_list',
                          'sub_item' => 'sub_list',
                          'als_item' => 'als_list',
                          'ftp_item' => 'ftp_list',
                          'db_item' => 'db_list',
                          'page_message' => 'page',
                          'logged_from' => 'page'));

$tpl->assign(array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Delete Domain'),
		'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()));

if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
	if (admin_validateUserDeletion($_GET['delete_id'])) {
		admin_deleteUser($_GET['delete_id']);
	} else {
		redirectTo('manage_users.php');
	}
} elseif (isset($_GET['domain_id'])) {
	admin_validateDomainAccountDeletion($tpl, $_GET['domain_id']);
} elseif (isset($_POST['domain_id']) &&
          isset($_POST['delete']) && $_POST['delete'] == 1) {
	delete_domain((int)$_POST['domain_id'], 'manage_users.php');
} else {
    if(isset($_GET['delete'])) {
        set_page_message(tr('Wrong domain ID.'), 'error');
    } else {
        set_page_message(tr('You must confirm domain deletion.'), 'error');
        redirectTo('user_delete.php?domain_id=' . intval($_POST['domain_id']));
    }

	redirectTo('manage_users.php');
}

gen_admin_mainmenu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/main_menu_users_manage.tpl');
gen_admin_menu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/menu_users_manage.tpl');
generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(
    iMSCP_Events::onAdminScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();
