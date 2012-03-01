<?php
/**
 * i-MSCP a internet Multi Server Control Panel
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Admin
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2012 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
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
	iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onBeforeDeleteUser, array('userId' => $userId));

    $userId = (int)$userId;

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
        // Getting reseller's software packages to remove if any
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
        $itemsToDelete = array_merge(
			array(
				'hosting_plans' => 'reseller_id = ?',
				'reseller_props' => 'reseller_id = ?',
				'web_software' => 'reseller_id = ?',
				'orders' => 'user_id = ?',
				'orders_settings' => 'user_id = ?'
			),
			$itemsToDelete);
    }

    // We are using transaction to ensure data consistency and prevent any garbage in
    // the database. If one query fail, the whole process is reverted.

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

        // Cleanup files system

        // We are safe here. We don't stop the process same if files cannot be
        // removed. That can result in garbages but the sysadmin can easily delete
        // them through ssh.

        // Deleting reseller software instaler local repository
        if (isset($swPackages) && !empty($swPackages)) {
            _admin_deleteResellerSwPackages($userId, $swPackages);
        } elseif($userType == 'reseller' &&
                 is_dir($cfg->GUI_SOFTWARE_DIR . '/' . $userId) &&
                 @rmdir($cfg->GUI_SOFTWARE_DIR . '/' . $userId) == false
        ) {
            write_log('Unable to remove reseller softwares directory: ' .
                  $cfg->GUI_SOFTWARE_DIR . '/' . $userId, E_USER_ERROR);
        }

        // Deleting user logo
        if (isset($resellerLogo) && !empty($resellerLogo)) {
            $logoPath = $cfg->GUI_ROOT_DIR . '/data/ispLogos/' . $resellerLogo;

            if (file_exists($logoPath) && @unlink($logoPath) == false) {
                write_log('Unable to remove user logo ' . $logoPath, E_USER_ERROR);
            }
        }

        $userTr = ($userType == 'reseller') ? tr('Reseller') : tr('Admin');
        set_page_message(tr('%s account successfully deleted.', $userTr), 'success');
        write_log($_SESSION['user_logged'] . ": deletes user " . $userId, E_USER_NOTICE);
    } catch (iMSCP_Exception_Database $e) {
        $db->rollBack();

        if (!$cfg->DEBUG) {
            set_page_message(tr('Unable to delete user with Id: %d', $userId), 'error');
            write_log(sprintf("Unable to delete user with Id '%s' for the following reason: %s",
                              $userId, $e->getMessage()), E_USER_ERROR);
        } else {
            throw new iMSCP_Exception_Database($e->getMessage());
        }
    }

	iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAfterDeleteUser, array('userId' => $userId));

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
    foreach ($swPackages as $package) {
        $packagePath = $cfg->GUI_SOFTWARE_DIR . '/' . $userId . '/' .
                       $package['software_archive'] . '-' .
                       $package['software_id'] . '.tar.gz';


        if (file_exists($packagePath) && !@unlink($packagePath)) {
            write_log('Unable to remove reseller package ' . $packagePath, E_USER_ERROR);
        }
    }

    // Remove reseller software installer local repository directory
    $resellerSwDirectory = $cfg->GUI_SOFTWARE_DIR . '/' . $userId;

    if (is_dir($resellerSwDirectory) && @rmdir($resellerSwDirectory) == false) {
        write_log('Unable to remove reseller softwares directory: ' .
                  $resellerSwDirectory, E_USER_ERROR);
    }
}

/**
 * Validates user (administrators, resellers) deletion.
 *
 * @param int $userId User unique identifier
 * @return bool TRUE if deletion can be done, FALSE otherwise
 */
function admin_validateUserDeletion($userId)
{
    $userId = (int)$userId;
    $retVal = true;

    // User is super admin
    if ($userId == 1) {
        set_page_message(tr('Invalid user Id.'), 'error');
        $retVal = false;
    } else {
        $query = "
            SELECT
                `a`.`admin_type`, `b`.`domain_id`
            FROM
                `admin` a
            LEFT JOIN
                `domain` `b` ON (`b`.`domain_created_id` = `a`.`admin_id`)
            WHERE
                `a`.`admin_id` = ?
            LIMIT 1
        ";
        $stmt = exec_query($query, $userId);

        // User was not found or user is a client
        if ($stmt->rowCount() == 0 || $stmt->fields['admin_type'] == 'user') {
            set_page_message(tr('Invalid user Id.'), 'error');
            $retVal = false;
            // user has domain accounts (reseller)
        } elseif ($stmt->fields['domain_id'] != null) {
            set_page_message(tr("You can't delete a reseller that has domain accounts. Please, remove them before."), 'error');
            $retVal = false;
        }
    }

    return $retVal;
}

/**
 * Generates domain account deletion validation page.
 *
 * @param int $domainId Domain account unique identifier
 * @return iMSCP_pTemplate
 */
function admin_generateDomainAcountDeletionValidationPage($domainId)
{
    $domainId = (int)$domainId;

    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    // Getting needed domain information
    $query = "SELECT `domain_name` FROM `domain` WHERE `domain_id` = ?";
    $stmt = exec_query($query, $domainId);

    if ($stmt->rowCount() == 0) {
        set_page_message(tr('Wrong domain Id.'), 'error');
        redirectTo('manage_users.php');
    }

    $domainName = tohtml(decode_idna($stmt->fields['domain_name']));

    $tpl = new iMSCP_pTemplate();
    $tpl->define_dynamic(
		array(
			'layout' => 'shared/layouts/ui.tpl',
			'page' => 'admin/user_delete.tpl',
			'page_message' => 'layout',
			'mail_list' => 'page',
			'mail_item' => 'mail_list',
			'ftp_list' => 'page',
			'ftp_item' => 'ftp_list',
			'als_list' => 'page',
			'als_item' => 'als_list',
			'sub_list' => 'page',
			'sub_item' => 'sub_list',
			'db_list' => 'page',
			'db_item' => 'db_list'));

    $tpl->assign(
		array(
			'TR_PAGE_TITLE' => tr('i-MSCP - Admin / Domain Account Deletion Validation'),
			'THEME_CHARSET' => tr('encoding'),
			'ISP_LOGO' => layout_getUserLogo(),
			'TR_DELETE_DOMAIN' => tr('Delete domain'),
			'TR_DOMAIN_SUMMARY' => tr('Domain account summary'),
			'TR_DOMAIN_EMAILS' => tr('Domain e-mails'),
			'TR_DOMAIN_FTPS' => tr('Domain FTP accounts'),
			'TR_DOMAIN_ALIASES' => tr('Domain aliases'),
			'TR_DOMAIN_SUBS' => tr('Domain subdomains'),
			'TR_DOMAIN_DBS' => tr('Domain databases'),
			'TR_REALLY_WANT_TO_DELETE_DOMAIN' => tr("Do you really want to delete the entire <strong>'%s'</strong> domain? This operation cannot be undone.", true, $domainName),
			'TR_BUTTON_DELETE' => tr('Delete domain'),
			'TR_YES_DELETE_DOMAIN' => tr('Yes, delete the domain.'),
			'DOMAIN_NAME' => $domainName,
			'DOMAIN_ID' => $domainId));

	generateNavigation($tpl);

    // Checks for domain's mail accounts

    $query = "SELECT `mail_type`, `mail_addr` FROM `mail_users` WHERE `domain_id` = ?";
    $stmt = exec_query($query, $domainId);

    if ($stmt->rowCount() != 0) {
        while (!$stmt->EOF) {
            $mailTypes = explode(',', $stmt->fields['mail_type']);
            $mailTypesdisplayArray = array();

            foreach ($mailTypes as $mtype) {
                $mailTypesdisplayArray[] = user_trans_mail_type($mtype);
            }

            $mailTypesdisplayTxt = implode(', ', $mailTypesdisplayArray);
            $addr = explode('@', $stmt->fields['mail_addr']);

            $tpl->assign(array(
                              'MAIL_ADDR' => tohtml($addr[0] . '@' . decode_idna($addr[1])),
                              'MAIL_TYPE' => $mailTypesdisplayTxt));

            $tpl->parse('MAIL_ITEM', '.mail_item');
            $stmt->moveNext();
        }
    } else {
        $tpl->assign('MAIL_LIST', '');
    }

    // Checks for FTP accounts in domain

    $query = "
	    SELECT
	        `ftp_users`.`userid`, `ftp_users`.`homedir`
	    FROM
	        `ftp_users`, `domain`
	    WHERE
	        `domain`.`domain_id` = ?
	    AND
	        `ftp_users`.`uid` = `domain`.`domain_uid`
	";
    $stmt = exec_query($query, $domainId);

    if ($stmt->rowCount() != 0) {
        while (!$stmt->EOF) {
            $username = explode('@', $stmt->fields['userid']);
            $tpl->assign(array(
                              'FTP_USER' => tohtml($username[0] . '@' . decode_idna($username[1])),
                              'FTP_HOME' => tohtml(substr($stmt->fields['homedir'], strlen($cfg->FTP_HOMEDIR)))));

            $tpl->parse('FTP_ITEM', '.ftp_item');
            $stmt->moveNext();
        }
    } else {
        $tpl->assign('FTP_LIST', '');
    }

    // Checks for domain's aliases

    $aliasIds = array();
    $query = "
        SELECT
            `alias_id`, `alias_name`, `alias_mount`
        FROM
            `domain_aliasses`
        WHERE
            `domain_id` = ?
    ";
    $stmt = exec_query($query, $domainId);

    if ($stmt->rowCount() != 0) {
        while (!$stmt->EOF) {
            $aliasIds[] = $stmt->fields['alias_id'];
            $tpl->assign(array(
                              'ALS_NAME' => tohtml(decode_idna($stmt->fields['alias_name'])),
                              'ALS_MNT' => tohtml($stmt->fields['alias_mount'])));

            $tpl->parse('ALS_ITEM', '.als_item');
            $stmt->moveNext();
        }
    } else {
        $tpl->assign('ALS_LIST', '');
    }

    // Checks for subdomains

    $query = "
        SELECT
            `subdomain_name`, `subdomain_mount`
        FROM
            `subdomain`
        WHERE
            `domain_id` = ?
    ";
    $stmt = exec_query($query, $domainId);

    if ($stmt->rowCount() != 0) {
        while (!$stmt->EOF) {
            $tpl->assign(array(
                              'SUB_NAME' => tohtml(decode_idna($stmt->fields['subdomain_name'])),
                              'SUB_MNT' => tohtml($stmt->fields['subdomain_mount'])));

            $tpl->parse('SUB_ITEM', '.sub_item');
            $stmt->moveNext();
        }
    } else {
        $tpl->assign('SUB_LIST', '');
    }

    // Checks subdomain_alias

    if (count($aliasIds) > 0) {
        $aliasIds = implode(',', $aliasIds);

        $query = "
            SELECT
                `subdomain_alias_name`, `subdomain_alias_mount`
            FROM
                `subdomain_alias`
            WHERE
                `alias_id` IN ($aliasIds)
        ";
        $stmt = execute_query($query);

        if ($stmt->rowCount() != 0) {
            while (!$stmt->EOF) {
                $tpl->assign(array(
                                  'SUB_NAME' => tohtml(decode_idna($stmt->fields['subdomain_alias_name'])),
                                  'SUB_MNT' => tohtml($stmt->fields['subdomain_alias_mount'])));

                $tpl->parse('SUB_ITEM', '.sub_item');
                $stmt->moveNext();
            }
        }
    }

    // Checks for databases and SQL users

    $query = "SELECT `sqld_id`, `sqld_name` FROM `sql_database` WHERE `domain_id` = ?";
    $stmt = exec_query($query, $domainId);

    if ($stmt->rowCount() != 0) {
        while (!$stmt->EOF) {
            $query = "SELECT `sqlu_name` FROM `sql_user` WHERE `sqld_id` = ?";
            $stmt2 = exec_query($query, $stmt->fields['sqld_id']);

            $sqlUsersList = array();

            if ($stmt2->rowCount() != 0) {
                while (!$stmt2->EOF) {
                    $sqlUsersList[] = $stmt2->fields['sqlu_name'];
                    $stmt2->moveNext();
                }
            }

            $tpl->assign(array(
                              'DB_NAME' => tohtml($stmt->fields['sqld_name']),
                              'DB_USERS' => tohtml(implode(', ', $sqlUsersList))));

            $tpl->parse('DB_ITEM', '.db_item');
            $stmt->moveNext();
        }
    } else {
        $tpl->assign('DB_LIST', '');
    }

    return $tpl;
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

if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    if (admin_validateUserDeletion($_GET['delete_id'])) {
        admin_deleteUser($_GET['delete_id']);
		exit; // Avoid IDE warning
    } else {
        redirectTo('manage_users.php');
		exit; // Avoid IDE warning
    }
} elseif (isset($_GET['domain_id'])) {
    $tpl = admin_generateDomainAcountDeletionValidationPage($_GET['domain_id']);
} elseif (isset($_POST['domain_id']) && isset($_POST['delete']) && $_POST['delete'] == 1) {
	$domainId = intval($_POST['domain_id']);

	try {
    	if(!delete_domain($domainId)) {
			throw new iMSCP_Exception('Domain account not found');
		}

		set_page_message(tr('Domain account successfully scheduled for deletion.'), 'success');
		write_log(sprintf('%s deleted the domain account with ID %d', $_SESSION['user_logged'], $domainId), E_USER_NOTICE);
	} catch(iMSCP_Exception $e) {
		if(($previous = $e->getPrevious()) && ($previous instanceof iMSCP_Exception_Database)) {
			/** @var $previous iMSCP_Exception_Database */
			$queryMessagePart = ' Query was: ' . $previous->getQuery();
		} else {
			$queryMessagePart = '';
		}

		set_page_message(tr('Unable to delete the domain. Please, consult admin logs or your mail for more information.'), 'error');
		write_log(sprintf("System was unable to delete domain account with ID %s. Message was: %s.", $domainId, $e->getMessage() . $queryMessagePart), E_USER_ERROR);
	}

    redirectTo('manage_users.php');
	exit; // Avoid IDE warning
} else {
    if (isset($_GET['delete'])) {
        set_page_message(tr('Wrong domain ID.'), 'error');
    } else {
        set_page_message(tr('You must confirm domain deletion.'), 'error');
        redirectTo('user_delete.php?domain_id=' . intval($_POST['domain_id']));
		exit; // Avoid IDE warning
    }

    redirectTo('manage_users.php');
	exit; // Avoid IDE warning
}

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
