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
 * @subpackage	Client
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2012 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('mail')) {
    redirectTo('index.php');
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if (isset($_GET['id']) && $_GET['id'] !== '') {
	global $delete_id;
	$delete_id = $_GET['id'];
} else {
	redirectTo('mail_accounts.php');
}

// Test if we have a proper delete_id.
if (!isset($delete_id)) {
	redirectTo('mail_accounts.php');
}

if (!is_numeric($delete_id)) {
	redirectTo('mail_accounts.php');
}

$dmn_name = $_SESSION['user_logged'];

$query = "
	SELECT
		t1.`mail_id`, t2.`domain_id`, t2.`domain_name`
	FROM
		`mail_users` AS t1,
		`domain` AS t2
	WHERE
		t1.`mail_id` = ?
	AND
		t1.`domain_id` = t2.`domain_id`
	AND
		t2.`domain_name` = ?
";

$rs = exec_query($query, array($delete_id, $dmn_name));

if ($rs->recordCount() == 0) {
	redirectTo('mail_accounts.php');
}

// check for catchall assigment !!
$query = "SELECT `mail_acc`, `domain_id`, `sub_id`, `mail_type` FROM `mail_users` WHERE `mail_id` = ?";
$res = exec_query($query, $delete_id);
$data = $res->fetchRow();

if (preg_match("/".MT_NORMAL_MAIL."/", $data['mail_type']) || preg_match("/".MT_NORMAL_FORWARD."/", $data['mail_type'])) {
	// mail to normal domain
	// global $domain_name;
	$mail_name = $data['mail_acc'] . '@' . $_SESSION['user_logged']; //$domain_name;
} else if (preg_match("/".MT_ALIAS_MAIL."/", $data['mail_type']) || preg_match("/".MT_ALIAS_FORWARD."/", $data['mail_type'])) {
	// mail to domain alias
	$res_tmp = exec_query("SELECT `alias_name` FROM `domain_aliasses` WHERE `alias_id` = ?", $data['sub_id']);
	$dat_tmp = $res_tmp->fetchRow();
	$mail_name = $data['mail_acc'] . '@' . $dat_tmp['alias_name'];
} else if (preg_match("/".MT_SUBDOM_MAIL."/", $data['mail_type']) || preg_match("/".MT_SUBDOM_FORWARD."/", $data['mail_type'])) {
	// mail to subdomain
	$res_tmp = exec_query("SELECT `subdomain_name` FROM `subdomain` WHERE `subdomain_id` = ?", $data['sub_id']);
	$dat_tmp = $res_tmp->fetchRow();
	$mail_name = $data['mail_acc'] . '@' . $dat_tmp['subdomain_name'].'.'.$dmn_name;
} else if (preg_match("/".MT_ALSSUB_MAIL."/", $data['mail_type']) || preg_match("/".MT_ALSSUB_FORWARD."/", $data['mail_type'])) {
	// mail to subdomain
	$res_tmp = exec_query("SELECT `subdomain_alias_name`, `alias_name` FROM `subdomain_alias` AS t1, `domain_aliasses` AS t2 WHERE t1.`alias_id` = t2.`alias_id` AND `subdomain_alias_id` = ?", $data['sub_id']);
	$dat_tmp = $res_tmp->fetchRow();
	$mail_name = $data['mail_acc'] . '@' . $dat_tmp['subdomain_alias_name'].'.'.$dat_tmp['alias_name'];
}

$query = "SELECT `mail_id` FROM `mail_users` WHERE `mail_acc` = ? OR `mail_acc` LIKE ? OR `mail_acc` LIKE ? OR `mail_acc` LIKE ?";
$res_tmp = exec_query($query, array($mail_name, "$mail_name,%", "%,$mail_name,%", "%,$mail_name"));
$num = $res_tmp->rowCount();
if ($num > 0) {
	set_page_message(tr('Please delete first CatchAll account for this email.'), 'error');
	$_SESSION['catchall_assigned'] = 1;
	redirectTo('mail_accounts.php');
}

/**
 * @todo useDB prepared statements
 */
iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onBeforeDeleteMail, array('mailId' => $delete_id));

$query = "UPDATE `mail_users` SET `status` = ? WHERE `mail_id` = ?";
exec_query($query, array($cfg->ITEM_DELETE_STATUS, $delete_id));

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAfterDeleteMail, array('mailId' => $delete_id));

update_reseller_c_props(get_reseller_id($data['domain_id']));

send_request();
$admin_login = decode_idna($_SESSION['user_logged']);
write_log("$admin_login: deletes mail account: " . $mail_name, E_USER_NOTICE);
$_SESSION['maildel'] = 1;

redirectTo('mail_accounts.php');
