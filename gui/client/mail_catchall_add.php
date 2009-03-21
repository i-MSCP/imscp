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
$tpl->define_dynamic('page', Config::get('CLIENT_TEMPLATE_PATH') . '/mail_catchall_add.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('mail_list', 'page');

if (isset($_GET['id'])) {
	$item_id = $_GET['id'];
} else if (isset($_POST['id'])) {
	$item_id = $_POST['id'];
} else {
	user_goto('mail_catchall.php');
}

// page functions.

function gen_dynamic_page_data(&$tpl, &$sql, $id) {
	global $domain_id;

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
		$dmn_cgi) = get_domain_default_props($sql, $_SESSION['user_id']);

	$domain_id = $dmn_id;

	list($mail_acc_cnt,
		$dmn_mail_acc_cnt,
		$sub_mail_acc_cnt,
		$als_mail_acc_cnt,
		$alssub_mail_acc_cnt) = get_domain_running_mail_acc_cnt($sql, $dmn_id);

	if ($dmn_mailacc_limit != 0 && $mail_acc_cnt >= $dmn_mailacc_limit) {
		set_page_message(tr('Mail accounts limit reached!'));
		header("Location: mail_catchall.php");
		die();
	}

	$ok_status = Config::get('ITEM_OK_STATUS');
	$match = array();
	if (preg_match("/(\d+);(normal|alias|subdom|alssub)/", $id, $match) == 1) {
		$item_id = $match[1];
		$item_type = $match[2];

		if ($item_type === 'normal') {
			$query = "
				SELECT
					t1.mail_id, t1.mail_type, t2.domain_name, t1.mail_acc
				FROM
					mail_users AS t1,
					domain AS t2
				WHERE
					t1.domain_id = ?
				AND
					t2.domain_id = ?
				AND
					t1.sub_id = '0'
				AND
					t1.status = ?
				ORDER BY
					t1.mail_type DESC, t1.mail_acc
			";

			$rs = exec_query($sql, $query, array($item_id, $item_id, $ok_status));
			if ($rs->RecordCount() == 0) {
				$tpl->assign(array('FORWARD_MAIL' => 'checked="checked"', 'MAIL_LIST' => '', 'DEFAULT' => 'forward'));
			} else {
				$tpl->assign(array('NORMAL_MAIL' => 'checked="checked"', 'NORMAL_MAIL_CHECK' => 'checked', 'FORWARD_MAIL' => '', 'DEFAULT' => 'normal'));

				while (!$rs->EOF) {
					$show_mail_acc = decode_idna($rs->fields['mail_acc']);
					$show_domain_name = decode_idna($rs->fields['domain_name']);
					$mail_acc = $rs->fields['mail_acc'];
					$domain_name = $rs->fields['domain_name'];
					$tpl->assign(
						array(
							'MAIL_ID'				=> $rs->fields['mail_id'],
							'MAIL_ACCOUNT'			=> $show_mail_acc . "@" . $show_domain_name, // this will be shown in the templates
							'MAIL_ACCOUNT_PUNNY'	=> $mail_acc . "@" . $domain_name // this will be updated if we create catch all
						)
					);

					$tpl->parse('MAIL_LIST', '.mail_list');
					$rs->MoveNext();
				}
			}
		} else if ($item_type === 'alias') {
			$query = "
				SELECT
					t1.mail_id, t1.mail_type, t2.alias_name, t1.mail_acc
				FROM
					mail_users AS t1,
					domain_aliasses AS t2
				WHERE
					t1.sub_id = t2.alias_id
				AND
					t1.status = ?
				AND
					t1.mail_type LIKE 'alias_%'
				AND
					t2.alias_id = ?
				ORDER BY
					t1.mail_type DESC, t1.mail_acc
			";

			$rs = exec_query($sql, $query, array($ok_status, $item_id));

			if ($rs->RecordCount() == 0) {
				$tpl->assign(array('FORWARD_MAIL' => 'checked="checked"', 'MAIL_LIST' => '', 'DEFAULT' => 'forward'));
			} else {
				$tpl->assign(array('NORMAL_MAIL' => 'checked="checked"', 'NORMAL_MAIL_CHECK' => 'checked', 'FORWARD_MAIL' => '', 'DEFAULT' => 'normal'));

				while (!$rs->EOF) {
					$show_mail_acc = decode_idna($rs->fields['mail_acc']);
					$show_alias_name = decode_idna($rs->fields['alias_name']);
					$mail_acc = $rs->fields['mail_acc'];
					$alias_name = $rs->fields['alias_name'];
					$tpl->assign(
						array(
							'MAIL_ID'				=> $rs->fields['mail_id'],
							'MAIL_ACCOUNT'			=> $show_mail_acc . "@" . $show_alias_name, // this will be shown in the templates
							'MAIL_ACCOUNT_PUNNY'	=> $mail_acc . "@" . $alias_name // this will be updated if we create catch all
						)
					);

					$tpl->parse('MAIL_LIST', '.mail_list');
					$rs->MoveNext();
				}
			}
		} else if ($item_type === 'subdom') {
			$query = "
				SELECT
					t1.mail_id, t1.mail_type, CONCAT( t2.subdomain_name, '.', t3.domain_name ) AS subdomain_name, t1.mail_acc
				FROM
					mail_users AS t1,
					subdomain AS t2,
					domain AS t3
				WHERE
					t1.sub_id = t2.subdomain_id
				AND
					t2.domain_id = t3.domain_id
				AND
					t1.status = ?
				AND
					t1.mail_type LIKE 'subdom_%'
				AND
					t2.subdomain_id = ?
				ORDER BY
					t1.mail_type DESC, t1.mail_acc
			";

			$rs = exec_query($sql, $query, array($ok_status, $item_id));

			if ($rs->RecordCount() == 0) {
				$tpl->assign(array('FORWARD_MAIL' => 'checked="checked"', 'MAIL_LIST' => '', 'DEFAULT' => 'forward'));
			} else {
				$tpl->assign(array('NORMAL_MAIL' => 'checked="checked"', 'NORMAL_MAIL_CHECK' => 'checked', 'FORWARD_MAIL' => '', 'DEFAULT' => 'normal'));

				while (!$rs->EOF) {
					$show_mail_acc = decode_idna($rs->fields['mail_acc']);
					$show_alias_name = decode_idna($rs->fields['subdomain_name']);
					$mail_acc = $rs->fields['mail_acc'];
					$alias_name = $rs->fields['subdomain_name'];
					$tpl->assign(
						array(
							'MAIL_ID'				=> $rs->fields['mail_id'],
							'MAIL_ACCOUNT'			=> $show_mail_acc . "@" . $show_alias_name, // this will be shown in the templates
							'MAIL_ACCOUNT_PUNNY'	=> $mail_acc . "@" . $alias_name // this will be updated if we create catch all
						)
					);

					$tpl->parse('MAIL_LIST', '.mail_list');
					$rs->MoveNext();
				}
			}
		} else if ($item_type === 'alssub') {
			$query = "
				SELECT
					t1.mail_id, t1.mail_type, CONCAT( t2.subdomain_alias_name, '.', t3.alias_name ) AS subdomain_name, t1.mail_acc
				FROM
					mail_users AS t1,
					subdomain_alias AS t2,
					domain_aliasses AS t3
				WHERE
					t1.sub_id = t2.subdomain_alias_id
				AND
					t2.alias_id = t3.alias_id
				AND
					t1.status = ?
				AND
					t1.mail_type LIKE 'alssub_%'
				AND
					t2.subdomain_alias_id = ?
				ORDER BY
					t1.mail_type DESC, t1.mail_acc
			";

			$rs = exec_query($sql, $query, array($ok_status, $item_id));

			if ($rs->RecordCount() == 0) {
				$tpl->assign(array('FORWARD_MAIL' => 'checked="checked"', 'MAIL_LIST' => '', 'DEFAULT' => 'forward'));
			} else {
				$tpl->assign(array('NORMAL_MAIL' => 'checked="checked"', 'NORMAL_MAIL_CHECK' => 'checked', 'FORWARD_MAIL' => '', 'DEFAULT' => 'normal'));

				while (!$rs->EOF) {
					$show_mail_acc = decode_idna($rs->fields['mail_acc']);
					$show_alias_name = decode_idna($rs->fields['subdomain_name']);
					$mail_acc = $rs->fields['mail_acc'];
					$alias_name = $rs->fields['subdomain_name'];
					$tpl->assign(
						array(
							'MAIL_ID'				=> $rs->fields['mail_id'],
							'MAIL_ACCOUNT'			=> $show_mail_acc . "@" . $show_alias_name, // this will be shown in the templates
							'MAIL_ACCOUNT_PUNNY'	=> $mail_acc . "@" . $alias_name // this will be updated if we create catch all
						)
					);

					$tpl->parse('MAIL_LIST', '.mail_list');
					$rs->MoveNext();
				}
			}
		}
	} else {
		user_goto('mail_catchall.php');
	}
}

function create_catchall_mail_account(&$sql, $id) {
	list($realId, $type) = explode(';', $id);
	// Check if user is owner of the domain
	if (!preg_match('(normal|alias|subdom|alssub)', $type) || who_owns_this($realId, $type) != $_SESSION['user_id']) {
		set_page_message(tr('User does not exist or you do not have permission to access this interface!'));
		user_goto('mail_catchall.php');
	}

	$match = array();
	if (isset($_POST['uaction']) && $_POST['uaction'] === 'create_catchall' && $_POST['mail_type'] === 'normal') {
		if (preg_match("/(\d+);(normal|alias|subdom|alssub)/", $id, $match) == 1) {
			$item_id = $match[1];
			$item_type = $match[2];
			$post_mail_id = $_POST['mail_id'];

			if (preg_match("/(\d+);([^;]+);/", $post_mail_id, $match) == 1) {
				$mail_id = $match[1];
				$mail_acc = $match[2];

				if ($item_type === 'normal') {
					$mail_type = 'normal_catchall';
				} elseif ($item_type === 'alias') {
					$mail_type = 'alias_catchall';
				} elseif ($item_type === 'subdom') {
					$mail_type = 'subdom_catchall';
				} elseif ($item_type === 'alssub') {
					$mail_type = 'alssub_catchall';
				}

				$query = "
					SELECT
						domain_id, sub_id
					FROM
						mail_users
					WHERE
						mail_id = ?
				";

				$rs = exec_query($sql, $query, array($mail_id));
				$domain_id = $rs->fields['domain_id'];
				$sub_id = $rs->fields['sub_id'];
				$status = Config::get('ITEM_ADD_STATUS');

				// find the mail_addr (catchall -> "@(sub/alias)domain.tld", should be domain part of mail_acc
				$match = explode('@', $mail_acc);
				$mail_addr = '@' . $match[1];

				check_for_lock_file();

				$query = "
					INSERT INTO `mail_users`
						(mail_acc,
						mail_pass,
						mail_forward,
						domain_id,
						mail_type,
						sub_id,
						status,
						mail_auto_respond,
						quota,
						mail_addr)
					VALUES
						(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
				";

				$rs = exec_query($sql, $query, array($mail_acc, '_no_', '_no_', $domain_id, $mail_type, $sub_id, $status, '_no_', NULL, $mail_addr));

				send_request();
				write_log($_SESSION['user_logged'] . ": adds new email catch all");
				set_page_message(tr('Catch all account scheduled for creation!'));
				user_goto('mail_catchall.php');
			} else {
				user_goto('mail_catchall.php');
			}
		}
	} else if (isset($_POST['uaction']) && $_POST['uaction'] === 'create_catchall' && $_POST['mail_type'] === 'forward' && isset($_POST['forward_list'])) {
		if (preg_match("/(\d+);(normal|alias|subdom|alssub)/", $id, $match) == 1) {
			$item_id = $match[1];
			$item_type = $match[2];

			if ($item_type === 'normal') {
				$mail_type = 'normal_catchall';
				$sub_id = '0';
				$domain_id = $item_id;
				$query = "SELECT `domain_name` FROM `domain` WHERE `domain_id` = ?";
				$rs = exec_query($sql, $query, $domain_id);
				$mail_addr = '@' . $rs->fields['domain_name'];
			} elseif ($item_type === 'alias') {
				$mail_type = 'alias_catchall';
				$sub_id = $item_id;
				$query = "SELECT `domain_aliasses`.`domain_id`, `alias_name` FROM `domain_aliasses` WHERE `alias_id` = ?";
				$rs = exec_query($sql, $query, $item_id);
				$domain_id = $rs->fields['domain_id'];
				$mail_addr = '@' . $rs->fields['alias_name'];
			} elseif ($item_type === 'subdom') {
				$mail_type = 'subdom_catchall';
				$sub_id = $item_id;
				$query = "SELECT `subdomain`.`domain_id`, `subdomain_name`, `domain_name` FROM `subdomain`, `domain`
					WHERE `subdomain_id` = ? AND `domain`.`domain_id` = `subdomain`.`domain_id`";
				$rs = exec_query($sql, $query, $item_id);
				$domain_id = $rs->fields['domain_id'];
				$mail_addr = '@' . $rs->fields['subdomain_name'] . '.' . $rs->fields['domain_name'];
			} elseif ($item_type === 'alssub') {
				$mail_type = 'alssub_catchall';
				$sub_id = $item_id;
				$query = "
					SELECT
						t1.`subdomain_alias_name`,
						t2.`alias_name`,
						t2.`domain_id`
					FROM
						`subdomain_alias` as t1,
						`domain_aliasses` as t2
					WHERE
						t1.`subdomain_alias_id` = ?
					AND
						t1.`alias_id` = t2.`alias_id`
					";
				$rs = exec_query($sql, $query, $item_id);
				$domain_id = $rs->fields['domain_id'];
				$mail_addr = '@' . $rs->fields['subdomain_alias_name'] . '.' . $rs->fields['alias_name'];
			}
			$mail_forward = clean_input($_POST['forward_list']);
			$mail_acc = array();
			$faray = preg_split ("/[\n,]+/", $mail_forward);

			foreach ($faray as $value) {
				$value = trim($value);
				if (!chk_email($value) && $value !== '') {
					/* ERR .. strange :) not email in this line - warning */
					set_page_message(tr("Mail forward list error!"));
					return;
				} else if ($value === '') {
					set_page_message(tr("Mail forward list error!"));
					return;
				}
				$mail_acc[] = $value;
			}

			$status = Config::get('ITEM_ADD_STATUS');
			check_for_lock_file();

			$query = "
				INSERT INTO `mail_users`
					(mail_acc,
					mail_pass,
					mail_forward,
					domain_id,
					mail_type,
					sub_id,
					status,
					mail_auto_respond,
					quota,
					mail_addr)
				VALUES
					(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
			";

			$rs = exec_query($sql, $query, array(implode(',', $mail_acc), '_no_', '_no_', $domain_id, $mail_type, $sub_id, $status, '_no_', NULL, $mail_addr));

			send_request();
			write_log($_SESSION['user_logged'] . ": adds new email catch all ");
			set_page_message(tr('Catch all account scheduled for creation!'));
			user_goto('mail_catchall.php');
		} else {
			user_goto('mail_catchall.php');
		}
	}
}

// common page data.

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_CLIENT_CREATE_CATCHALL_PAGE_TITLE'	=> tr('ispCP - Client/Create CatchAll Mail Account'),
		'THEME_COLOR_PATH'						=> "../themes/$theme_color",
		'THEME_CHARSET'							=> tr('encoding'),
		'ISP_LOGO'								=> get_logo($_SESSION['user_id'])
	)
);

// dynamic page data.

gen_dynamic_page_data($tpl, $sql, $item_id);
create_catchall_mail_account($sql, $item_id);
$tpl->assign('ID', $item_id);

// static page messages.

gen_client_mainmenu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/main_menu_email_accounts.tpl');
gen_client_menu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/menu_email_accounts.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$tpl->assign(
	array(
		'TR_CREATE_CATCHALL_MAIL_ACCOUNT'	=> tr('Create catch all mail account'),
		'TR_MAIL_LIST'						=> tr('Mail accounts list'),
		'TR_CREATE_CATCHALL'				=> tr('Create catch all'),
		'TR_FORWARD_MAIL'					=> tr('Forward mail'),
		'TR_FORWARD_TO'						=> tr('Forward to')
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG'))
	dump_gui_debug();

unset_messages();

?>