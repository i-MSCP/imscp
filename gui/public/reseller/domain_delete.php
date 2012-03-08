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
 * @subpackage	Reseller
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
 * Generates domain account deletion validation page.
 *
 * @param int $domainId Domain account unique identifier
 * @return iMSCP_pTemplate
 */
function reseller_generateDomainAcountDeletionValidationPage($domainId)
{
    $domainId = (int)$domainId;

    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    // check for domain owner
    $query = "
        SELECT
            `domain_name`
        FROM
            `domain`
        WHERE
            `domain_id` = ?
        AND
            `domain_created_id` = ?
    ";
    $stmt = exec_query($query, array($domainId, $_SESSION['user_id']));

    if (!$stmt->rowCount()) {
        set_page_message(tr('Wrong request'), 'error');
        redirectTo('users.php');
    }

    $domainName = tohtml(decode_idna($stmt->fields['domain_name']));

    $tpl = new iMSCP_pTemplate();
	$tpl->define_dynamic(
		array(
			'layout' => 'shared/layouts/ui.tpl',
			'page' => 'reseller/domain_delete.tpl',
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
			'TR_PAGE_TITLE' => tr('i-MSCP - Reseller / Domain Account Deletion Validation'),
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

    if ($stmt->rowCount()) {
        while (!$stmt->EOF) {
            $mailTypes = explode(',', $stmt->fields['mail_type']);
            $mailTypesdisplayArray = array();

            foreach ($mailTypes as $mtype) {
                $mailTypesdisplayArray[] = user_trans_mail_type($mtype);
            }

            $mailTypesdisplayTxt = implode(', ', $mailTypesdisplayArray);
            $addr = explode('@', $stmt->fields['mail_addr']);

			$tpl->assign(
				array(
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

    if ($stmt->rowCount()) {
        while (!$stmt->EOF) {
			$username = explode('@', $stmt->fields['userid']);
			$tpl->assign(
				array(
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

    if ($stmt->rowCount()) {
        while (!$stmt->EOF) {
            $aliasIds[] = $stmt->fields['alias_id'];
			$tpl->assign(
				array(
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

    if ($stmt->rowCount()) {
        while (!$stmt->EOF) {
			$tpl->assign(
				array(
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

        if ($stmt->rowCount()) {
            while (!$stmt->EOF) {
				$tpl->assign(
					array(
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

    if ($stmt->rowCount()) {
        while (!$stmt->EOF) {
            $query = "SELECT `sqlu_name` FROM `sql_user` WHERE `sqld_id` = ?";
            $stmt2 = exec_query($query, $stmt->fields['sqld_id']);

            $sqlUsersList = array();

            if ($stmt2->rowCount()) {
                while (!$stmt2->EOF) {
                    $sqlUsersList[] = $stmt2->fields['sqlu_name'];
                    $stmt2->moveNext();
                }
            }

			$tpl->assign(
				array(
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

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login(__FILE__);

if (isset($_GET['domain_id']) && !empty($_GET['domain_id'])) {
    $tpl = reseller_generateDomainAcountDeletionValidationPage($_GET['domain_id']);
} elseif (isset($_POST['domain_id']) && !empty($_POST['domain_id']) && isset($_POST['delete']) && $_POST['delete'] == 1) {

	$domainId = intval($_POST['domain_id']);

	try {
    	if(!delete_domain($domainId, true)) {
			throw new iMSCP_Exception('Domain account not found.');
		}

		set_page_message(tr('Domain account successfully scheduled for deletion.'), 'success');
		write_log(sprintf('%s deleted the domain account with ID %d', $_SESSION['user_logged'], $domainId), E_USER_NOTICE);
	} catch(iMSCP_Exception $e) {
		set_page_message(tr('Unable to delete the domain account. A message has been sent to the administrator.'), 'error');
		write_log(sprintf("System was unable to delete domain account with ID %s. Message was: %s", $domainId, $e->getMessage()), E_USER_ERROR);
	}

	redirectTo('users.php');
	exit; // Avoid IDE warning
} else {
    if (isset($_GET['delete'])) {
        set_page_message(tr('Wrong domain Id.'), 'error');
    } else {
        set_page_message(tr('You must confirm domain deletion.'), 'error');
        redirectTo('domain_delete.php?domain_id=' . intval($_POST['domain_id']));
		exit;
    }

    redirectTo('users.php');
	exit;
}

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd,  array('templateEngine' => $tpl));

$tpl->prnt();
unsetMessages();
