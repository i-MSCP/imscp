<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @version		SVN: $Id$
 * @link		http://isp-control.net
 * @author		ispCP Team
 *
 * @license
 *   This program is free software; you can redistribute it and/or modify it under
 *   the terms of the MPL General Public License as published by the Free Software
 *   Foundation; either version 1.1 of the License, or (at your option) any later
 *   version.
 *   You should have received a copy of the MPL Mozilla Public License along with
 *   this program; if not, write to the Open Source Initiative (OSI)
 *   http://opensource.org | osi@opensource.org
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();

$tpl->define_dynamic('page', Config::get('CLIENT_TEMPLATE_PATH') . '/mail_add.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('als_list', 'page');
$tpl->define_dynamic('sub_list', 'page');
$tpl->define_dynamic('als_sub_list', 'page');
$tpl->define_dynamic('to_alias_domain', 'page');
$tpl->define_dynamic('to_subdomain', 'page');
$tpl->define_dynamic('to_alias_subdomain', 'page');

// page functions.

function gen_page_form_data(&$tpl, $dmn_name, $post_check) {
	$dmn_name = decode_idna($dmn_name);

	if ($post_check === 'no') {

		$tpl->assign(
			array(
				'USERNAME'				=> "",
				'DOMAIN_NAME'			=> $dmn_name,
				'MAIL_DMN_CHECKED'		=> 'checked="checked"',
				'MAIL_ALS_CHECKED'		=> "",
				'MAIL_SUB_CHECKED'		=> "",
				'MAIL_ALS_SUB_CHECKED'	=> "",
				'NORMAL_MAIL_CHECKED'	=> 'checked="checked"',
				'FORWARD_MAIL_CHECKED'	=> "",
				'FORWARD_LIST'			=> ""
			)
		);

	} else {
		if (!isset($_POST['forward_list'])) {
			$f_list = '';
		} else {
			$f_list = $_POST['forward_list'];
		}

		$tpl->assign(
			array(
				'USERNAME'				=> clean_input($_POST['username'], true),
				'DOMAIN_NAME'			=> $dmn_name,
				'MAIL_DMN_CHECKED'		=> ($_POST['dmn_type'] === 'dmn') ? 'checked="checked"' : "",
				'MAIL_ALS_CHECKED'		=> ($_POST['dmn_type'] === 'als') ? 'checked="checked"' : "",
				'MAIL_SUB_CHECKED'		=> ($_POST['dmn_type'] === 'sub') ? 'checked="checked"' : "",
				'MAIL_ALS_SUB_CHECKED'	=> ($_POST['dmn_type'] === 'als_sub') ? 'checked="checked"' : "",
				'NORMAL_MAIL_CHECKED'	=> (isset($_POST['mail_type_normal'])) ? 'checked="checked"' : "",
				'FORWARD_MAIL_CHECKED'	=> (isset($_POST['mail_type_forward'])) ? 'checked="checked"' : "",
				'FORWARD_LIST'			=> $f_list
			)
		);
	}
}

function gen_dmn_als_list(&$tpl, &$sql, $dmn_id, $post_check) {
	$ok_status = Config::get('ITEM_OK_STATUS');

	$query = "
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
	";

	$rs = exec_query($sql, $query, array($dmn_id, $ok_status));
	if ($rs->RecordCount() == 0) {
		$tpl->assign(
			array(
				'ALS_ID'		=> '0',
				'ALS_SELECTED'	=> 'selected="selected"',
				'ALS_NAME'		=> tr('Empty list')
			)
		);
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
					$als_selected = 'selected="selected"';
				} else {
					$als_selected = '';
				}
			} else {
				if (!$first_passed) {
					$als_selected = 'selected="selected"';
				} else {
					$als_selected = '';
				}
			}

			$alias_name = decode_idna($rs->fields['alias_name']);
			$tpl->assign(
				array(
					'ALS_ID'		=> $rs->fields['alias_id'],
					'ALS_SELECTED'	=> $als_selected,
					'ALS_NAME'		=> $alias_name
				)
			);
			$tpl->parse('ALS_LIST', '.als_list');
			$rs->MoveNext();

			if (!$first_passed)
				$first_passed = true;
		}
	}
}

function gen_dmn_sub_list(&$tpl, &$sql, $dmn_id, $dmn_name, $post_check) {
	$ok_status = Config::get('ITEM_OK_STATUS');

	$query = "
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
";

	$rs = exec_query($sql, $query, array($dmn_id, $ok_status));

	if ($rs->RecordCount() == 0) {
		$tpl->assign(
			array(
				'SUB_ID'		=> '0',
				'SUB_SELECTED'	=> 'selected="selected"',
				'SUB_NAME'		=> tr('Empty list')
			)
		);
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
					$sub_selected = 'selected="selected"';
				} else {
					$sub_selected = '';
				}
			} else {
				if (!$first_passed) {
					$sub_selected = 'selected="selected"';
				} else {
					$sub_selected = '';
				}
			}

			$sub_name = decode_idna($rs->fields['sub_name']);
			$dmn_name = decode_idna($dmn_name);
			$tpl->assign(
				array(
					'SUB_ID'		=> $rs->fields['sub_id'],
					'SUB_SELECTED'	=> $sub_selected,
					'SUB_NAME'		=> $sub_name . '.' . $dmn_name
				)
			);
			$tpl->parse('SUB_LIST', '.sub_list');
			$rs->MoveNext();

			if (!$first_passed)
				$first_passed = true;
		}
	}
}

function gen_dmn_als_sub_list(&$tpl, &$sql, $dmn_id, $post_check) {
	$ok_status = Config::get('ITEM_OK_STATUS');

	$query = "
		SELECT
			t1.`subdomain_alias_id` AS als_sub_id,
			t1.`subdomain_alias_name` AS als_sub_name,
			t2.`alias_name` AS als_name
		FROM
			`subdomain_alias` AS t1
		LEFT JOIN (`domain_aliasses` AS t2) ON (t1.`alias_id` = t2.`alias_id`)
		WHERE
			t1.`alias_id` IN (SELECT `alias_id` FROM `domain_aliasses` WHERE `domain_id` = ?)
		AND
			t1.`subdomain_alias_status` = ?
		ORDER BY
			t1.`subdomain_alias_name`
	";

	$rs = exec_query($sql, $query, array($dmn_id, $ok_status));

	if ($rs->RecordCount() == 0) {
		$tpl->assign(
			array(
				'ALS_SUB_ID'		=> '0',
				'ALS_SUB_SELECTED'	=> 'selected="selected"',
				'ALS_SUB_NAME'		=> tr('Empty list')
			)
		);
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
					$als_sub_selected = 'selected="selected"';
				} else {
					$als_sub_selected = '';
				}
			} else {
				if (!$first_passed) {
					$als_sub_selected = 'selected="selected"';
				} else {
					$als_sub_selected = '';
				}
			}

			$als_sub_name = decode_idna($rs->fields['als_sub_name']);
			$als_name = decode_idna($rs->fields['als_name']);
			$tpl->assign(
				array(
					'ALS_SUB_ID'		=> $rs->fields['als_sub_id'],
					'ALS_SUB_SELECTED'	=> $als_sub_selected,
					'ALS_SUB_NAME'		=> $als_sub_name . '.' . $als_name
				)
			);
			$tpl->parse('ALS_SUB_LIST', '.als_sub_list');
			$rs->MoveNext();

			if (!$first_passed)
				$first_passed = true;
		}
	}
}

function schedule_mail_account(&$sql, $domain_id, $dmn_name, $mail_acc) {

	$mail_auto_respond = false;
	$mail_auto_respond_text = '';
	$mail_addr = '';

	if (array_key_exists('mail_type_normal',$_POST)) {
		$mail_pass = $_POST['pass'];
		$mail_forward = '_no_';
		if ($_POST['dmn_type'] === 'dmn') {
			$mail_type[] = MT_NORMAL_MAIL;
			$sub_id = '0';
			$mail_addr = $mail_acc.'@'.$dmn_name; // the complete address
		} else if ($_POST['dmn_type'] === 'sub') {
			$mail_type[] = MT_SUBDOM_MAIL;
			$sub_id = $_POST['sub_id'];
			$mail_addr = $mail_acc.'@'.decode_idna($dmn_name); // the complete address
		} else if ($_POST['dmn_type'] === 'als_sub') {
			$mail_type[] = MT_ALSSUB_MAIL;
			$sub_id = $_POST['als_sub_id'];
			$mail_addr = $mail_acc.'@'.decode_idna($dmn_name); // the complete address
		} else if ($_POST['dmn_type'] === 'als') {
			$mail_type[] = MT_ALIAS_MAIL;
			$sub_id = $_POST['als_id'];
			$mail_addr = $mail_acc.'@'.decode_idna($dmn_name); // the complete address
		} else {
			set_page_message(tr('Unknown domain type'));
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
			set_page_message(tr('Unknown domain type'));
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
				/* ERROR .. strange :) not email in this line - warning */
				set_page_message(tr("Mailformat of an address in your forward list is incorrect!"));
				return false;
			} else if ($value === '') {
				set_page_message(tr("Mail forward list empty!"));
				return false;
			} else if ($mail_acc.'@'.decode_idna($dmn_name) == $value){
				set_page_message(tr("Forward to same address is not allowed!"));
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

	$rs = exec_query($sql, $check_acc_query, array($mail_acc, $domain_id, $sub_id, $dmn_type));

	if ($rs->fields['cnt'] > 0) {
		set_page_message(tr('Mail account already exists!'));
		return false;
	}

	if (preg_match("/^normal_mail/", $mail_type)
		|| preg_match("/^alias_mail/", $mail_type)
		|| preg_match("/^subdom_mail/", $mail_type)
		|| preg_match("/^alssub_mail/", $mail_type)) {
		$mail_pass=encrypt_db_password($mail_pass);
	}

	$query = "
		INSERT INTO `mail_users` (
			`mail_acc`,
			`mail_pass`,
			`mail_forward`,
			`domain_id`,
			`mail_type`,
			`sub_id`,
			`status`,
			`mail_auto_respond`,
			`mail_auto_respond_text`,
			`mail_addr`
		) VALUES
			(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
	";

	$rs = exec_query($sql, $query, array($mail_acc,
			$mail_pass,
			$mail_forward,
			$domain_id,
			$mail_type,
			$sub_id,
			Config::get('ITEM_ADD_STATUS'),
			$mail_auto_respond,
			$mail_auto_respond_text,
			$mail_addr));

	update_reseller_c_props(get_reseller_id($domain_id));

	write_log($_SESSION['user_logged'] . ": adds new mail account: " . (isset($mail_addr) ? $mail_addr : $mail_acc));
	set_page_message(tr('Mail account scheduled for addition!'));
	send_request();
	user_goto('mail_accounts.php');
}

function check_mail_acc_data(&$sql, $dmn_id, $dmn_name) {

	$mail_type_normal = isset($_POST['mail_type_normal']) ? $_POST['mail_type_normal'] : false;
	$mail_type_forward = isset($_POST['mail_type_forward']) ? $_POST['mail_type_forward'] : false;

	if (($mail_type_normal == false) && ($mail_type_forward == false)) {
		set_page_message(tr('Please select at least one mail type!'));
		return false;
	}

	if ($mail_type_normal) {
		$pass = clean_input($_POST['pass']);
		$pass_rep = clean_input($_POST['pass_rep']);
	}

	if (!isset($_POST['username']) || $_POST['username'] === '') {
		set_page_message(tr('Please enter mail account username!'));
		return false;
	}

	$mail_acc = strtolower(clean_input($_POST['username']));
	if (ispcp_check_local_part($mail_acc) == "0") {
		set_page_message(tr("Invalid Mail Localpart Format used!"));
		return false;
	}

	if ($mail_type_normal) {
		if (trim($pass) === '' || trim($pass_rep) === '') {
			set_page_message(tr('Password data is missing!'));
			return false;
		} else if ($pass !== $pass_rep) {
			set_page_message(tr('Entered passwords differ!'));
			return false;
		} else if (!chk_password($pass, 50, "/[`\xb4'\"\\\\\x01-\x1f\015\012|<>^$]/i")) {
			// Not permitted chars
			if (Config::get('PASSWD_STRONG')) {
				set_page_message(sprintf(tr('The password must be at least %s long and contain letters and numbers to be valid.'), Config::get('PASSWD_CHARS')));
			} else {
				set_page_message(sprintf(tr('Password data is shorter than %s signs or includes not permitted signs!'), Config::get('PASSWD_CHARS')));
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
			set_page_message(sprintf(tr('%s list is empty! You cannot add mail accounts!'),$type));
			return false;
		}
		if (!is_numeric($_POST[$id])) {
			set_page_message(sprintf(tr('%s id is invalid! You cannot add mail accounts!'),$type));
			return false;
		}
		$rs = exec_query($sql, $query, array($_POST[$id], $dmn_id));
		if ($rs->fields['name'] == '') {
			set_page_message(sprintf(tr('%s id is invalid! You cannot add mail accounts!'),$type));
			return false;
		}
		$dmn_name=$rs->fields['name'];
	}

	if ($mail_type_forward && empty($_POST['forward_list'])) {
		set_page_message(tr('Forward list is empty!'));
		return false;
	}

	schedule_mail_account($sql, $dmn_id, $dmn_name, $mail_acc);
}

function gen_page_mail_acc_props(&$tpl, &$sql, $user_id) {
	list($dmn_id,
		$dmn_name,
		$dmn_gid,
		$dmn_uid,
		$dmn_created_id,
		$dmn_created,
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
		$dmn_cgi) = get_domain_default_props($sql, $user_id);

	list($mail_acc_cnt,
		$dmn_mail_acc_cnt,
		$sub_mail_acc_cnt,
		$als_mail_acc_cnt,
		$alssub_mail_acc_cnt) = get_domain_running_mail_acc_cnt($sql, $dmn_id);

	if ($dmn_mailacc_limit != 0 && $mail_acc_cnt >= $dmn_mailacc_limit) {
		set_page_message(tr('Mail accounts limit reached!'));
		user_goto('mail_accounts.php');
	} else {
		$post_check = isset($_POST['uaction']) ? 'yes' : 'no';
		gen_page_form_data($tpl, $dmn_name, $post_check);
		gen_dmn_als_list($tpl, $sql, $dmn_id, $post_check);
		gen_dmn_sub_list($tpl, $sql, $dmn_id, $dmn_name, $post_check);
		gen_dmn_als_sub_list($tpl, $sql, $dmn_id, $post_check);
		if (isset($_POST['uaction']) && $_POST['uaction'] === 'add_user') {
			check_mail_acc_data($sql, $dmn_id, $dmn_name);
		}
	}
}

// common page data.

if (isset($_SESSION['email_support']) && $_SESSION['email_support'] == "no") {
	header("Location: index.php");
}

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_CLIENT_ADD_MAIL_ACC_PAGE_TITLE'	=> tr('ispCP - Client/Add Mail User'),
		'THEME_COLOR_PATH'					=> "../themes/$theme_color",
		'THEME_CHARSET'						=> tr('encoding'),
		'ISP_LOGO'							=> get_logo($_SESSION['user_id'])
	)
);

// dynamic page data.

gen_page_mail_acc_props($tpl, $sql, $_SESSION['user_id']);

// static page messages.

gen_client_mainmenu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/main_menu_email_accounts.tpl');
gen_client_menu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/menu_email_accounts.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$tpl->assign(
	array(
		'TR_ADD_MAIL_USER'		=> tr('Add mail users'),
		'TR_USERNAME'			=> tr('Username'),
		'TR_TO_MAIN_DOMAIN'		=> tr('To main domain'),
		'TR_TO_DMN_ALIAS'		=> tr('To domain alias'),
		'TR_TO_SUBDOMAIN'		=> tr('To subdomain'),
		'TR_TO_ALS_SUBDOMAIN'	=> tr('To alias subdomain'),
		'TR_NORMAL_MAIL'		=> tr('Normal mail'),
		'TR_PASSWORD'			=> tr('Password'),
		'TR_PASSWORD_REPEAT'	=> tr('Repeat password'),
		'TR_FORWARD_MAIL'		=> tr('Forward mail'),
		'TR_FORWARD_TO'			=> tr('Forward to'),
		'TR_FWD_HELP'			=> tr("Separate multiple email addresses with a line-break."),
		'TR_ADD'				=> tr('Add'),
		'TR_EMPTY_DATA'			=> tr('You did not fill all required fields')
	)
);

gen_page_message($tpl);
$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
