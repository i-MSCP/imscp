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

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if(!resellerHasFeature('domain_aliases')) {
	return 'index.php';
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if (isset($_GET['action']) && $_GET['action'] === "delete") {

	if (isset($_GET['del_id']) && !empty($_GET['del_id'])) {
		$del_id = $_GET['del_id'];
	} else {
		$_SESSION['orderaldel'] = '_no_';
		redirectTo('alias.php');
	}

	$query = "DELETE FROM `domain_aliasses` WHERE `alias_id` = ?";
	$rs = exec_query($query, $del_id);

	// delete "ordered"/pending email accounts
	$domain_id = who_owns_this($del_id, 'als_id', true);
	$query = "DELETE FROM `mail_users` WHERE `sub_id` = ? AND `domain_id` = ? AND `status` = ? AND `mail_type` LIKE 'alias%'";
	$rs = exec_query($query, array($del_id, $domain_id, $cfg->ITEM_ORDERED_STATUS));

	redirectTo('alias.php');

} else if (isset($_GET['action']) && $_GET['action'] === "activate") {

	if (isset($_GET['act_id']) && !empty($_GET['act_id']))
		$act_id = $_GET['act_id'];
	else {
		$_SESSION['orderalact'] = '_no_';
		redirectTo('alias.php');
	}
	$query = "SELECT `alias_name` FROM `domain_aliasses` WHERE `alias_id` = ?";
	$rs = exec_query($query, $act_id);
	if ($rs->recordCount() == 0) {
		redirectTo('alias.php');
	}
	$alias_name = $rs->fields['alias_name'];

	$query = "UPDATE `domain_aliasses` SET `alias_status` = 'toadd' WHERE `alias_id` = ?";
	$rs = exec_query($query, $act_id);

	$domain_id = who_owns_this($act_id, 'als_id', true);
	$query = 'SELECT `email` FROM `admin`, `domain` WHERE `admin`.`admin_id` = `domain`.`domain_admin_id` AND `domain`.`domain_id` = ?';
	$rs = exec_query($query, $domain_id);
	if ($rs->recordCount() == 0) {
		redirectTo('alias.php');
	}
	$user_email = $rs->fields['email'];
	// Create the 3 default addresses if wanted
	if ($cfg->CREATE_DEFAULT_EMAIL_ADDRESSES) client_mail_add_default_accounts($domain_id, $user_email, $alias_name, 'alias', $act_id);

	// enable "ordered"/pending email accounts
	// ??? are there pending mail_addresses ???, joximu
	$query = "UPDATE `mail_users` SET `status` = ? WHERE `sub_id` = ? AND `domain_id` = ? AND `status` = ? AND `mail_type` LIKE 'alias%'";
	$rs = exec_query($query, array($cfg->ITEM_ADD_STATUS, $act_id, $domain_id, $cfg->ITEM_ORDERED_STATUS));

	send_request();

	$admin_login = $_SESSION['user_logged'];

	write_log("$admin_login: domain alias activated: $alias_name.", E_USER_NOTICE);

	set_page_message(tr('Alias scheduled for activation.'), 'success');

	$_SESSION['orderalact'] = '_yes_';
	redirectTo('alias.php');

} else {
	redirectTo('alias.php');
}
