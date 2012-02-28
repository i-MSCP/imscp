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
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('mail')) {
    redirectTo('index.php');
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('layout', 'shared/layouts/ui.tpl');
$tpl->define_dynamic('page', 'client/mail_add.tpl');
$tpl->define_dynamic('page_message', 'layout');
$tpl->define_dynamic('als_list', 'page');
$tpl->define_dynamic('sub_list', 'page');
$tpl->define_dynamic('als_sub_list', 'page');
$tpl->define_dynamic('to_alias_domain', 'page');
$tpl->define_dynamic('to_subdomain', 'page');
$tpl->define_dynamic('to_alias_subdomain', 'page');

/**
 * @param $tpl
 * @param $dmn_name
 * @param $post_check
 * @return void
 */
function gen_page_form_data($tpl, $dmn_name, $post_check) {

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$dmn_name = decode_idna($dmn_name);

	if ($post_check === 'no') {

		$tpl->assign(
			array(
				'USERNAME' => '',
				'DOMAIN_NAME' => tohtml($dmn_name),
				'MAIL_DMN_CHECKED' => $cfg->HTML_CHECKED,
				'MAIL_ALS_CHECKED' => '',
				'MAIL_SUB_CHECKED' => '',
				'MAIL_ALS_SUB_CHECKED' => '',
				'NORMAL_MAIL_CHECKED' => $cfg->HTML_CHECKED,
				'FORWARD_MAIL_CHECKED' => '',
				'FORWARD_LIST' => ''));

	} else {
		if (!isset($_POST['forward_list'])) {
			$f_list = '';
		} else {
			$f_list = $_POST['forward_list'];
		}

		$tpl->assign(
			array(
				'USERNAME' => clean_input($_POST['username'], true),
				'DOMAIN_NAME' => tohtml($dmn_name),
				'MAIL_DMN_CHECKED' => ($_POST['dmn_type'] === 'dmn') ? $cfg->HTML_CHECKED : '',
				'MAIL_ALS_CHECKED' => ($_POST['dmn_type'] === 'als') ? $cfg->HTML_CHECKED : '',
				'MAIL_SUB_CHECKED' => ($_POST['dmn_type'] === 'sub') ? $cfg->HTML_CHECKED : '',
				'MAIL_ALS_SUB_CHECKED' => ($_POST['dmn_type'] === 'als_sub') ? $cfg->HTML_CHECKED : '',
				'NORMAL_MAIL_CHECKED' => (isset($_POST['mail_type_normal'])) ? $cfg->HTML_CHECKED : '',
				'FORWARD_MAIL_CHECKED' => (isset($_POST['mail_type_forward'])) ? $cfg->HTML_CHECKED : '',
				'FORWARD_LIST' => $f_list));
	}
}

/**
 * @param $tpl
 * @param $dmn_id
 * @param $post_check
 * @return void
 */
function gen_dmn_als_list($tpl, $dmn_id, $post_check) {

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$ok_status = $cfg->ITEM_OK_STATUS;

	$query = '
		SELECT
			`alias_id`, `alias_name`
		FROM
			`domain_aliasses`
		WHERE
			`domain_id` = ?
		AND
			`alias_status` = ?
		ORDER BY
			`alias_name`
	';

	$rs = exec_query($query, array($dmn_id, $ok_status));
	if ($rs->recordCount() == 0) {
		$tpl->assign(
			array(
				'ALS_ID' => '0',
				'ALS_SELECTED' => $cfg->HTML_SELECTED,
				'ALS_NAME' => tr('Empty list')));

		$tpl->parse('ALS_LIST', 'als_list');
		$tpl->assign('TO_ALIAS_DOMAIN', '');
	} else {
		$first_passed = false;
		while (!$rs->EOF) {
			if ($post_check === 'yes') {
				if (!isset($_POST['als_id'])) {
					$als_id = '';
				} else {
					$als_id = $_POST['als_id'];
				}

				if ($als_id == $rs->fields['alias_id']) {
					$als_selected = $cfg->HTML_SELECTED;
				} else {
					$als_selected = '';
				}
			} else {
				if (!$first_passed) {
					$als_selected = $cfg->HTML_SELECTED;
				} else {
					$als_selected = '';
				}
			}

			$alias_name = decode_idna($rs->fields['alias_name']);
			$tpl->assign(
				array(
					'ALS_ID' => $rs->fields['alias_id'],
					'ALS_SELECTED' => $als_selected,
					'ALS_NAME' => tohtml($alias_name)));

			$tpl->parse('ALS_LIST', '.als_list');
			$rs->moveNext();

			if (!$first_passed)
				$first_passed = true;
		}
	}
}

/**
 * @param $tpl
 * @param $dmn_id
 * @param $dmn_name
 * @param $post_check
 * @return void
 */
function gen_dmn_sub_list($tpl, $dmn_id, $dmn_name, $post_check) {

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$ok_status = $cfg->ITEM_OK_STATUS;

	$query = '
		SELECT
			`subdomain_id` AS sub_id, `subdomain_name` AS sub_name
		FROM
			`subdomain`
		WHERE
			`domain_id` = ?
		AND
			`subdomain_status` = ?
		ORDER BY
			`subdomain_name`
    ';

	$rs = exec_query($query, array($dmn_id, $ok_status));

	if ($rs->recordCount() == 0) {
		$tpl->assign(
			array(
				'SUB_ID' => '0',
				'SUB_SELECTED' => $cfg->HTML_SELECTED,
				'SUB_NAME' => tr('Empty list')));

		$tpl->parse('SUB_LIST', 'sub_list');
		$tpl->assign('TO_SUBDOMAIN', '');
	} else {
		$first_passed = false;

		while (!$rs->EOF) {
			if ($post_check === 'yes') {
				if (!isset($_POST['sub_id'])) {
					$sub_id = '';
				} else {
					$sub_id = $_POST['sub_id'];
				}

				if ($sub_id == $rs->fields['sub_id']) {
					$sub_selected = $cfg->HTML_SELECTED;
				} else {
					$sub_selected = '';
				}
			} else {
				if (!$first_passed) {
					$sub_selected = $cfg->HTML_SELECTED;
				} else {
					$sub_selected = '';
				}
			}

			$sub_name = decode_idna($rs->fields['sub_name']);
			$dmn_name = decode_idna($dmn_name);
			$tpl->assign(
				array(
					'SUB_ID' => $rs->fields['sub_id'],
					'SUB_SELECTED' => $sub_selected,
					'SUB_NAME' => tohtml($sub_name . '.' . $dmn_name)));

			$tpl->parse('SUB_LIST', '.sub_list');
			$rs->moveNext();

			if (!$first_passed) {
				$first_passed = true;
			}
		}
	}
}

/**
 * @param $tpl
 * @param $dmn_id
 * @param $post_check
 * @return void
 */
function gen_dmn_als_sub_list($tpl, $dmn_id, $post_check) {

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$ok_status = $cfg->ITEM_OK_STATUS;

	$query = '
		SELECT
			t1.`subdomain_alias_id` AS als_sub_id,
			t1.`subdomain_alias_name` AS als_sub_name, t2.`alias_name` AS als_name
		FROM
			`subdomain_alias` AS t1
		LEFT JOIN (`domain_aliasses` AS t2) ON (t1.`alias_id` = t2.`alias_id`)
		WHERE
			t1.`alias_id` IN (SELECT `alias_id` FROM `domain_aliasses` WHERE `domain_id` = ?)
		AND
			t1.`subdomain_alias_status` = ?
		ORDER BY
			t1.`subdomain_alias_name`
	';

	$rs = exec_query($query, array($dmn_id, $ok_status));

	if ($rs->recordCount() == 0) {
		$tpl->assign(
			array(
				'ALS_SUB_ID' => '0',
				'ALS_SUB_SELECTED' => $cfg->HTML_SELECTED,
				'ALS_SUB_NAME' => tr('Empty list')));

		$tpl->parse('ALS_SUB_LIST', 'sub_list');
		$tpl->assign('TO_ALIAS_SUBDOMAIN', '');
	} else {
		$first_passed = false;

		while (!$rs->EOF) {
			if ($post_check === 'yes') {
				if (!isset($_POST['als_sub_id'])) {
					$als_sub_id = '';
				} else {
					$als_sub_id = $_POST['als_sub_id'];
				}

				if ($als_sub_id == $rs->fields['als_sub_id']) {
					$als_sub_selected = $cfg->HTML_SELECTED;
				} else {
					$als_sub_selected = '';
				}
			} else {
				if (!$first_passed) {
					$als_sub_selected = $cfg->HTML_SELECTED;
				} else {
					$als_sub_selected = '';
				}
			}

			$als_sub_name = decode_idna($rs->fields['als_sub_name']);
			$als_name = decode_idna($rs->fields['als_name']);
			$tpl->assign(
				array(
					'ALS_SUB_ID' => $rs->fields['als_sub_id'],
					'ALS_SUB_SELECTED' => $als_sub_selected,
					'ALS_SUB_NAME' => tohtml($als_sub_name . '.' . $als_name)));

			$tpl->parse('ALS_SUB_LIST', '.als_sub_list');
			$rs->moveNext();

			if (!$first_passed)
				$first_passed = true;
		}
	}
}

/**
 * @param $domain_id
 * @param $dmn_name
 * @param $mail_acc
 * @return bool
 */
function schedule_mail_account($domain_id, $dmn_name, $mail_acc) {

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	/** @var $db iMSCP_Database */
	$db = iMSCP_Registry::get('db');

	$mail_auto_respond = false;
	$mail_auto_respond_text = '';
	$mail_addr = $mail_acc.'@'.decode_idna($dmn_name);

	if (array_key_exists('mail_type_normal',$_POST)) {
		$mail_pass = $_POST['pass'];
		$mail_forward = '_no_';
		if ($_POST['dmn_type'] === 'dmn') {
			$mail_type[] = MT_NORMAL_MAIL;
			$sub_id = '0';
		} else if ($_POST['dmn_type'] === 'sub') {
			$mail_type[] = MT_SUBDOM_MAIL;
			$sub_id = $_POST['sub_id'];
		} else if ($_POST['dmn_type'] === 'als_sub') {
			$mail_type[] = MT_ALSSUB_MAIL;
			$sub_id = $_POST['als_sub_id'];
		} else if ($_POST['dmn_type'] === 'als') {
			$mail_type[] = MT_ALIAS_MAIL;
			$sub_id = $_POST['als_id'];
		} else {
			set_page_message(tr('Unknown domain type.'), 'error');
			return false;
		}
	}

	if (array_key_exists('mail_type_forward',$_POST)) {
		if ($_POST['dmn_type'] === 'dmn') {
			$mail_type[] = MT_NORMAL_FORWARD;
			$sub_id = '0';
		} else if ($_POST['dmn_type'] === 'sub') {
			$mail_type[] = MT_SUBDOM_FORWARD;
			$sub_id = $_POST['sub_id'];
		} else if ($_POST['dmn_type'] === 'als_sub') {
			$mail_type[] = MT_ALSSUB_FORWARD;
			$sub_id = $_POST['als_sub_id'];
		} else if ($_POST['dmn_type'] === 'als') {
			$mail_type[] = MT_ALIAS_FORWARD;
			$sub_id = $_POST['als_id'];
		} else {
			set_page_message(tr('Unknown domain type.'), 'error');
			return false;
		}

		if (!isset($_POST['mail_type_normal'])) {
			$mail_pass = '_no_';
		}

		$mail_forward = $_POST['forward_list'];
		$farray = preg_split("/[\n,]+/", $mail_forward);
		$mail_accs = array();

		foreach ($farray as $value) {
			$value = trim($value);
			if (!chk_email($value) && $value !== '') {
				// @todo ERROR .. strange :) not email in this line - warning
				set_page_message(tr('Mailformat of an address in your forward list is incorrect.'), 'error');
				return false;
			} else if ($value === '') {
				set_page_message(tr('Mail forward list empty.'), 'info');
				return false;
			} else if ($mail_acc.'@'.decode_idna($dmn_name) == $value){
				set_page_message(tr('Forward to same address is not allowed.'), 'error');
				return false;
			}
			$mail_accs[] = $value;
		}
		$mail_forward = implode(',', $mail_accs);
	}

	$mail_type = implode(',', $mail_type);
	list($dmn_type, $type) = explode('_', $mail_type, 2);

	$check_acc_query = "
		SELECT
			COUNT(`mail_id`) AS cnt
		FROM
			`mail_users`
		WHERE
			`mail_acc` = ?
		AND
			`domain_id` = ?
		AND
			`sub_id` = ?
		AND
			LEFT (`mail_type`, LOCATE('_', `mail_type`)-1) = ?
	";

	$rs = exec_query($check_acc_query, array($mail_acc, $domain_id, $sub_id, $dmn_type));

	if ($rs->fields['cnt'] > 0) {
		set_page_message(tr('Mail account already exists.'), 'error');
		return false;
	}

	iMSCP_Events_Manager::getInstance()->dispatch(
		iMSCP_Events::onBeforeAddMail, array('mailUsername' => $mail_acc, 'MailAddress' => $mail_addr)
	);

	$query = '
		INSERT INTO `mail_users` (
			`mail_acc`, `mail_pass`, `mail_forward`, `domain_id`, `mail_type`,
			`sub_id`, `status`, `mail_auto_respond`, `mail_auto_respond_text`,
			`mail_addr`
		) VALUES
			(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
	';

	exec_query($query, array($mail_acc,
			$mail_pass,
			$mail_forward,
			$domain_id,
			$mail_type,
			$sub_id,
			$cfg->ITEM_ADD_STATUS,
			$mail_auto_respond,
			$mail_auto_respond_text,
			$mail_addr));

	$mail_id = $db->insertId();

	iMSCP_Events_Manager::getInstance()->dispatch(
		iMSCP_Events::onAfterAddMail,
		array('mailUsername' => $mail_acc, 'mailAddress' => $mail_addr, 'mailId' => $mail_id
		)
	);

	update_reseller_c_props(get_reseller_id($domain_id));

	write_log($_SESSION['user_logged'] . ': adds new mail account: ' . (!empty($mail_addr) ? $mail_addr : $mail_acc), E_USER_NOTICE);
	set_page_message(tr('Mail account scheduled for addition.'), 'success');
	send_request();
	redirectTo('mail_accounts.php');
}

/**
 * @param $dmn_id
 * @param $dmn_name
 * @return bool
 */
function check_mail_acc_data($dmn_id, $dmn_name) {

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$mail_type_normal = isset($_POST['mail_type_normal']) ? $_POST['mail_type_normal'] : false;
	$mail_type_forward = isset($_POST['mail_type_forward']) ? $_POST['mail_type_forward'] : false;

	if (($mail_type_normal == false) && ($mail_type_forward == false)) {
		set_page_message(tr('Please select at least one mail type.'), 'error');
		return false;
	}

	if ($mail_type_normal) {
		$pass = clean_input($_POST['pass']);
		$pass_rep = clean_input($_POST['pass_rep']);
	}

	if (!isset($_POST['username']) || $_POST['username'] == '') {
		set_page_message(tr('Please enter mail account username.'), 'error');
		return false;
	}

	$mail_acc = strtolower(clean_input($_POST['username']));
	if (imscp_check_local_part($mail_acc) == '0') {
		set_page_message(tr('Invalid mail local part.'), 'error');
		return false;
	}

	if ($mail_type_normal) {
		if (trim($pass) === '' || trim($pass_rep) === '') {
			set_page_message(tr('Password data is missing.'), 'error');
			return false;
		} else if ($pass !== $pass_rep) {
			set_page_message(tr("Passwords doesn't matches"), 'error');
			return false;
		} else if (!chk_password($pass, 50, "/[`\xb4'\"\\\\\x01-\x1f\015\012|<>^$]/i")) {
			// Not permitted chars
			if ($cfg->PASSWD_STRONG) {
				set_page_message(sprintf(tr('The password must be at least %s long and contain letters and numbers to be valid.'), $cfg->PASSWD_CHARS), 'error');
			} else {
				set_page_message(sprintf(tr('Password data is shorter than %s signs or includes not permitted signs!'), $cfg->PASSWD_CHARS), 'error');
			}
			return false;
		}
	}

	if ($_POST['dmn_type'] === 'sub') {
		$id = 'sub_id';
		$query = '
			SELECT
				CONCAT(t1.`subdomain_name`,\'.\',t2.`domain_name`) AS name
			FROM
				`subdomain` AS t1,`domain` AS t2
			WHERE
				t1.`domain_id` = t2.`domain_id`
			AND
				t1.`subdomain_id` = ?
			AND
				t1.`domain_id` = ?
		';
		$type = tr('Subdomain');
	}

	if ($_POST['dmn_type'] === 'als_sub') {
		$id = 'als_sub_id';
		$query = '
			SELECT
				CONCAT(t1.`subdomain_alias_name`,\'.\',t2.`alias_name`) AS name
			FROM
				`subdomain_alias` AS t1
			LEFT JOIN (`domain_aliasses` AS t2) ON (t1.`alias_id` = t2.`alias_id`)
			LEFT JOIN (`domain` AS t3) ON (t2.`domain_id` = t3.`domain_id`)
			WHERE
				t1.`subdomain_alias_id` = ?
			AND
				t3.`domain_id` = ?
		';
		$type = tr('Subdomain alias');
	}

	if ($_POST['dmn_type'] === 'als') {
		$id = 'als_id';
		$query = 'SELECT `alias_name` AS name FROM `domain_aliasses` WHERE `alias_id` = ? AND `domain_id` = ?';
		$type = tr('Alias');
	}

	if (in_array($_POST['dmn_type'], array('sub', 'als_sub', 'als'))) {
		if (!isset($_POST[$id])) {
			set_page_message(sprintf(tr('%s list is empty! You cannot add mail accounts.'), $type), 'error');
			return false;
		}
		if (!is_numeric($_POST[$id])) {
			set_page_message(sprintf(tr('%s id is invalid! You cannot add mail accounts.'), $type), 'error');
			return false;
		}
		$rs = exec_query($query, array($_POST[$id], $dmn_id));
		if ($rs->fields['name'] == '') {
			set_page_message(sprintf(tr('%s id is invalid! You cannot add mail accounts.'), $type), 'error');
			return false;
		}
		$dmn_name=$rs->fields['name'];
	}

	if ($mail_type_forward && empty($_POST['forward_list'])) {
		set_page_message(tr('Forward list is empty.'), 'info');
		return false;
	}

	schedule_mail_account($dmn_id, $dmn_name, $mail_acc);
}

/**
 * @param $tpl
 * @param $user_id
 * @return void
 */
function gen_page_mail_acc_props($tpl, $user_id) {
	list($dmn_id,
		$dmn_name,
		$dmn_gid,
		$dmn_uid,
		$dmn_created_id,
		$dmn_created,
		$dmn_expires,
		$dmn_last_modified,
		$dmn_mailacc_limit,
		$dmn_ftpacc_limit,
		$dmn_traff_limit,
		$dmn_sqld_limit,
		$dmn_sqlu_limit,
		$dmn_status,
		$dmn_als_limit,
		$dmn_subd_limit,
		$dmn_ip_id,
		$dmn_disk_limit,
		$dmn_disk_usage,
		$dmn_php,
		$dmn_cgi,
		$allowbackup,
		$dmn_dns
	) = get_domain_default_props($user_id);

	list($mail_acc_cnt,
		$dmn_mail_acc_cnt,
		$sub_mail_acc_cnt,
		$als_mail_acc_cnt,
		$alssub_mail_acc_cnt) = get_domain_running_mail_acc_cnt($dmn_id);

	if ($dmn_mailacc_limit != 0 && $mail_acc_cnt >= $dmn_mailacc_limit) {
		set_page_message(tr('Mail accounts limit reached.'), 'error');
		redirectTo('mail_accounts.php');
	} else {
		$post_check = isset($_POST['uaction']) ? 'yes' : 'no';
		gen_page_form_data($tpl, $dmn_name, $post_check);
		gen_dmn_als_list($tpl, $dmn_id, $post_check);
		gen_dmn_sub_list($tpl, $dmn_id, $dmn_name, $post_check);
		gen_dmn_als_sub_list($tpl, $dmn_id, $post_check);

		if (isset($_POST['uaction']) && $_POST['uaction'] === 'add_user') {
			check_mail_acc_data($dmn_id, $dmn_name);
		}
	}
}

if (isset($_SESSION['email_support']) && $_SESSION['email_support'] == "no") {
	redirectTo('index.php');
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Client / Manage mail / Add account'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()));

gen_page_mail_acc_props($tpl, $_SESSION['user_id']);
generateNavigation($tpl);

$tpl->assign(
	array(
		 'TR_ADD_MAIL_USER' => tr('Add mail users'),
		 'TR_USERNAME' => tr('Username'),
		 'TR_TO_MAIN_DOMAIN' => tr('To main domain'),
		 'TR_TO_DMN_ALIAS' => tr('To domain alias'),
		 'TR_TO_SUBDOMAIN' => tr('To subdomain'),
		 'TR_TO_ALS_SUBDOMAIN' => tr('To alias subdomain'),
		 'TR_NORMAL_MAIL' => tr('Normal mail'),
		 'TR_PASSWORD' => tr('Password'),
		 'TR_PASSWORD_REPEAT' => tr('Repeat password'),
		 'TR_FORWARD_MAIL' => tr('Forward mail'),
		 'TR_FORWARD_TO' => tr('Forward to'),
		 'TR_FWD_HELP' => tr('Separate multiple email addresses with a line-break.'),
		 'TR_ADD' => tr('Add'),
		 'TR_EMPTY_DATA' => tr('You did not fill all required fields'),
		 'TR_MAIl_ACCOUNT_DATA' => tr('Mail account data')));

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
