<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
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
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();
$tpl->define_dynamic(
	'page',
	Config::get('CLIENT_TEMPLATE_PATH') . '/mail_accounts.tpl'
);
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('mail_message', 'page');
$tpl->define_dynamic('mail_item', 'page');
$tpl->define_dynamic('mail_auto_respond', 'mail_item');
$tpl->define_dynamic('default_mails_form', 'page');
$tpl->define_dynamic('mails_total', 'page');
$tpl->define_dynamic('no_mails', 'page');

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_CLIENT_MANAGE_USERS_PAGE_TITLE'	=> tr('ispCP - Client/Manage Users'),
		'THEME_COLOR_PATH' => "../themes/$theme_color",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id'])
	)
);

// page functions.

/**
 * Must be documented
 *
 * @param int $mail_id mail id
 * @param string $mail_status mail status
 * @return array 
 */
function gen_user_mail_action($mail_id, $mail_status) {

	if ($mail_status === Config::get('ITEM_OK_STATUS')) {
		return array(
			tr('Delete'),
			"mail_delete.php?id=$mail_id",
			tr('Edit'),
			"mail_edit.php?id=$mail_id"
		);
	} else {
		return array(tr('N/A'), '#', tr('N/A'), '#');
	}
}

/**
 * Must be documented
 *
 * @param object $tpl
 * @param int $mail_id
 * @param string $mail_type
 * @param string $mail_status
 * @param int $mail_auto_respond
 * @return void
 */
function gen_user_mail_auto_respond(
	&$tpl, $mail_id, $mail_type, $mail_status, $mail_auto_respond
) {

	if ($mail_status === Config::get('ITEM_OK_STATUS')) {
		if ($mail_auto_respond == false) {
			$tpl->assign(
				array(
					'AUTO_RESPOND_DISABLE' => tr('Enable'),

					'AUTO_RESPOND_DISABLE_SCRIPT' =>
						"mail_autoresponder_enable.php?id=$mail_id",

					'AUTO_RESPOND_EDIT' => '',
					'AUTO_RESPOND_EDIT_SCRIPT' => '',
					'AUTO_RESPOND_VIS' => 'inline'
				)
			);
		} else {
			$tpl->assign(
				array(
					'AUTO_RESPOND_DISABLE' => tr('Disable'),

					'AUTO_RESPOND_DISABLE_SCRIPT' =>
						"mail_autoresponder_disable.php?id=$mail_id",

					'AUTO_RESPOND_EDIT' => tr('Edit'),

					'AUTO_RESPOND_EDIT_SCRIPT' =>
						"mail_autoresponder_edit.php?id=$mail_id",

					'AUTO_RESPOND_VIS' => 'inline'
				)
			);
		}
	} else {
		$tpl->assign(
			array(
				'AUTO_RESPOND_DISABLE' => tr('Please wait for update'),
				'AUTO_RESPOND_DISABLE_SCRIPT' => '',
				'AUTO_RESPOND_EDIT' => '',
				'AUTO_RESPOND_EDIT_SCRIPT' => '',
				'AUTO_RESPOND_VIS' => 'inline'
			)
		);
	}
}

/**
 * Must be documented
 *
 * @param object $tpl reference to template instance
 * @param object $sql reference to database instance
 * @param int $dmn_id domain name id
 * @param string $dmn_name domain name
 * @return int number of domain mails adresses
 */
function gen_page_dmn_mail_list(&$tpl, &$sql, $dmn_id, $dmn_name) {

	$dmn_query = "
		SELECT
			`mail_id`,
			`mail_acc`,
			`mail_type`,
			`status`,
			`mail_auto_respond`,
		CONCAT(
			LEFT(`mail_forward`, 	20),
			IF( LENGTH(`mail_forward`) > 20, '...', '')
		) AS 'mail_forward'
		FROM
			`mail_users`
		WHERE
			`domain_id` = ?
		AND
			`sub_id` = 0
		AND
			(
				`mail_type` LIKE '%".MT_NORMAL_MAIL."%'
			OR
				`mail_type` LIKE '%".MT_NORMAL_FORWARD."%'
			) ";

	if (!isset($_POST['uaction']) || $_POST['uaction'] == 'hide') {
		$dmn_query .= "
			AND
				`mail_acc` != 'abuse'
			AND
				`mail_acc` != 'postmaster'
			AND
				`mail_acc` != 'webmaster'
		";
	}

	$dmn_query .= "
		ORDER BY
			`mail_acc` ASC,
			`mail_type` DESC
	";

	$rs = exec_query($sql, $dmn_query, array($dmn_id));

	if ($rs->RecordCount() == 0) {
		return 0;
	} else {
		global $counter;

		while (!$rs->EOF) {

			$tpl->assign(
				'ITEM_CLASS',
				($counter % 2 == 0) ? 'content' : 'content2'
			);

			list(
				$mail_delete,
				$mail_delete_script,
				$mail_edit,
				$mail_edit_script
			) = gen_user_mail_action(
				$rs->fields['mail_id'],
				$rs->fields['status']
			);

			$mail_acc = decode_idna($rs->fields['mail_acc']);
			$show_dmn_name = decode_idna($dmn_name);

			$mail_types = explode(',', $rs->fields['mail_type']);
			$mail_type = '';

			foreach ($mail_types as $type) {
				$mail_type .= user_trans_mail_type($type);

				if (strpos($type, '_forward') !== false) {
					$mail_type .= ': ' . 
						str_replace(
							array("\r\n", "\n", "\r"),
							", ",
							$rs->fields['mail_forward']
						);
				}

				$mail_type .= '<br />';
			}

			$tpl->assign(
				array(
					'MAIL_ACC' => $mail_acc . '@' . $show_dmn_name,
					'MAIL_TYPE' => $mail_type,
					'MAIL_STATUS' => translate_dmn_status($rs->fields['status']),
					'MAIL_DELETE' => $mail_delete,
					'MAIL_DELETE_SCRIPT' => $mail_delete_script,
					'MAIL_EDIT' => $mail_edit,
					'MAIL_EDIT_SCRIPT' => $mail_edit_script
				)
			);

			gen_user_mail_auto_respond(
				$tpl,
				$rs->fields['mail_id'],
				$rs->fields['mail_type'],
				$rs->fields['status'],
				$rs->fields['mail_auto_respond']
			);

			$tpl->parse('MAIL_ITEM', '.mail_item');

			$rs->MoveNext();
			$counter++;
		}

		return $rs->RecordCount();
	}
} // end gen_page_dmn_mail_list()

/**
 * Must be documented
 *
 * @param object &$tpl reference to the template instance 
 * @param object &$sql reference to the database instance
 * @param int $dmn_id domain name id
 * @param strinc $dmn_name domain name
 * @return int number of subdomain mails addresses
 */
function gen_page_sub_mail_list(&$tpl, &$sql, $dmn_id, $dmn_name) {
	
	$sub_query = "
		SELECT
			t1.`subdomain_id` AS sub_id,
			t1.`subdomain_name` AS sub_name,
			t2.`mail_id`,
			t2.`mail_acc`,
			t2.`mail_type`,
			t2.`status`,
			t2.`mail_auto_respond`,
		CONCAT(
			LEFT(t2.`mail_forward`, 20),
			IF(LENGTH(t2.`mail_forward`) > 20, '...', '')
		) AS 'mail_forward'
		FROM
			`subdomain` AS t1,
			`mail_users` AS t2
		WHERE
			t1.`domain_id` = ?
		AND
			t2.`domain_id` = ?
		AND
			(
				t2.`mail_type` LIKE '%".MT_SUBDOM_MAIL."%'
			OR
				t2.`mail_type` LIKE '%".MT_SUBDOM_FORWARD."%'
			)
		AND
			t1.`subdomain_id` = t2.`sub_id`
	";

	if (!isset($_POST['uaction']) || $_POST['uaction'] == 'hide') {
		$sub_query .= "
			AND
				`mail_acc` != 'abuse'
			AND
				`mail_acc` != 'postmaster'
			AND
				`mail_acc` != 'webmaster'
		";
	}

	$sub_query .= "
		ORDER BY
			t2.`mail_acc` ASC,
			t2.`mail_type` DESC
	";

	$rs = exec_query($sql, $sub_query, array($dmn_id, $dmn_id));

	if ($rs->RecordCount() == 0) {
		return 0;
	} else {
		global $counter;

		while (!$rs->EOF) {
			$tpl->assign(
				'ITEM_CLASS',
				($counter % 2 == 0) ? 'content' : 'content2'
			);

			list(
				$mail_delete,
				$mail_delete_script,
				$mail_edit,
				$mail_edit_script
			) = gen_user_mail_action(
				$rs->fields['mail_id'],
				$rs->fields['status']
			);

			$mail_acc = decode_idna($rs->fields['mail_acc']);

			$show_sub_name = decode_idna($rs->fields['sub_name']);
			$show_dmn_name = decode_idna($dmn_name);

			$mail_types = explode(',', $rs->fields['mail_type']);
			$mail_type = '';

			foreach ($mail_types as $type) {
				$mail_type .= user_trans_mail_type($type);

				if (strpos($type, '_forward') !== false) {
						$mail_type .= ': ' . str_replace(
							array("\r\n", "\n", "\r"),
							", ",
							$rs->fields['mail_forward']
						);
				}

				$mail_type .= '<br />';
			}

			$tpl->assign(
				array(
					'MAIL_ACC' =>
						$mail_acc.'@'.$show_sub_name.'.'.$show_dmn_name,

					'MAIL_TYPE' => $mail_type,
					'MAIL_STATUS' => translate_dmn_status($rs->fields['status']),
					'MAIL_DELETE' => $mail_delete,
					'MAIL_DELETE_SCRIPT' => $mail_delete_script,
					'MAIL_EDIT' => $mail_edit,
					'MAIL_EDIT_SCRIPT' => $mail_edit_script
				)
			);

			gen_user_mail_auto_respond(
				$tpl,
				$rs->fields['mail_id'],
				$rs->fields['mail_type'],
				$rs->fields['status'],
				$rs->fields['mail_auto_respond']
			);

			$tpl->parse('MAIL_ITEM', '.mail_item');

			$rs->MoveNext();
			$counter++;
		}

		return $rs->RecordCount();
	}
} // end gen_page_sub_mail_list()

/**
 * Must be documented
 *
 * @param object &$tpl reference to the template instance
 * @param object &$sql reference to the database instance
 * @param int $dmn_id domain name id
 * @param string $dmn_name domain name
 * @return int number of subdomain alias mails addresses
 */
function gen_page_als_sub_mail_list(&$tpl, &$sql, $dmn_id, $dmn_name) {

	$sub_query = "
		SELECT
			t1.`mail_id`,
			t1.`mail_acc`,
			t1.`mail_type`,
			t1.`status`,
			t1.`mail_auto_respond`,
		CONCAT(
			LEFT(t1.`mail_forward`, 20),
			IF(LENGTH(t1.`mail_forward`) > 20, '...', '')
		) AS 'mail_forward',
		CONCAT(
			t2.`subdomain_alias_name`, '.', t3.`alias_name`
		) AS 'alssub_name'
		FROM
			`mail_users` AS t1
		LEFT JOIN (
			`subdomain_alias` AS t2
		) ON (
			t1.`sub_id` = t2.`subdomain_alias_id`
		)
		LEFT JOIN (
			`domain_aliasses` AS t3
		) ON (
			t2.`alias_id` = t3.`alias_id`
		)
		WHERE
			t1.`domain_id` = ?
		AND
			(
				t1.`mail_type` LIKE '%".MT_ALSSUB_MAIL."%'
			OR
				t1.`mail_type` LIKE '%".MT_ALSSUB_FORWARD."%'
			)
	";

	if (!isset($_POST['uaction']) || $_POST['uaction'] == 'hide') {
		$sub_query .= "
			AND
				`mail_acc` != 'abuse'
			AND
				`mail_acc` != 'postmaster'
			AND
				`mail_acc` != 'webmaster'
		";
	}

	$sub_query .= "
		ORDER BY
			t1.`mail_acc` ASC,
			t1.`mail_type` DESC
	";

	$rs = exec_query($sql, $sub_query, array($dmn_id));

	if ($rs->RecordCount() == 0) {
		return 0;
	} else {
		global $counter;

		while (!$rs->EOF) {
			$tpl->assign(
				'ITEM_CLASS',
				($counter % 2 == 0) ? 'content' : 'content2'
			);

			list(
				$mail_delete,
				$mail_delete_script,
				$mail_edit, $mail_edit_script
			) = gen_user_mail_action(
				$rs->fields['mail_id'],
				$rs->fields['status']
			);

			$mail_acc = decode_idna($rs->fields['mail_acc']);

			$show_alssub_name = decode_idna($rs->fields['alssub_name']);

			$mail_types = explode(',', $rs->fields['mail_type']);
			$mail_type = '';

			foreach ($mail_types as $type) {
				$mail_type .= user_trans_mail_type($type);

				if (strpos($type, '_forward') !== false) {
					$mail_type .= ': ' . str_replace(
						array("\r\n", "\n", "\r"),
						", ",
						$rs->fields['mail_forward']
					);
				}

				$mail_type .= '<br />';
			}

			$tpl->assign(
				array(
					'MAIL_ACC' => $mail_acc . '@' . $show_alssub_name,
					'MAIL_TYPE' => $mail_type,
					'MAIL_STATUS' => translate_dmn_status($rs->fields['status']),
					'MAIL_DELETE' => $mail_delete,
					'MAIL_DELETE_SCRIPT' => $mail_delete_script,
					'MAIL_EDIT' => $mail_edit,
					'MAIL_EDIT_SCRIPT' => $mail_edit_script
				)
			);

			gen_user_mail_auto_respond(
				$tpl,
				$rs->fields['mail_id'],
				$rs->fields['mail_type'],
				$rs->fields['status'],
				$rs->fields['mail_auto_respond']
			);

			$tpl->parse('MAIL_ITEM', '.mail_item');

			$rs->MoveNext();
			$counter++;
		}

		return $rs->RecordCount();
	}
} // end gen_page_als_sub_mail_list()

/**
 * Must be documented
 *
 * @param object &$tpl reference to the template instance
 * @param unknown_type &$sql reference to the database instance
 * @param unknown_type $dmn_id domain name id;
 * @param unknown_type $dmn_name domain name
 * @return int number of domain alias mails addresses
 */
function gen_page_als_mail_list(&$tpl, &$sql, $dmn_id, $dmn_name) {

	$als_query = "
		SELECT
			t1.`alias_id` AS als_id,
			t1.`alias_name` AS als_name,
			t2.`mail_id`,
			t2.`mail_acc`,
			t2.`mail_type`,
			t2.`status`,
			t2.`mail_auto_respond`,
		CONCAT(
			LEFT(t2.`mail_forward`, 20),
			IF( LENGTH(t2.`mail_forward`) > 20, '...', '')
		) AS 'mail_forward'
		FROM
			`domain_aliasses` AS t1,
			`mail_users` AS t2
		WHERE
			t1.`domain_id` = ?
		AND
			t2.`domain_id` = ?
		AND
			t1.`alias_id` = t2.`sub_id`
		AND
			(
				t2.`mail_type` LIKE '%".MT_ALIAS_MAIL."%'
			OR
				t2.`mail_type` LIKE '%".MT_ALIAS_FORWARD."%'
			)
	";

	if (!isset($_POST['uaction']) || $_POST['uaction'] == 'hide') {
		$als_query .= "
			AND
				`mail_acc` != 'abuse'
			AND
				`mail_acc` != 'postmaster'
			AND
				`mail_acc` != 'webmaster'
		";
	}

	$als_query .= "
		ORDER BY
			t2.`mail_acc` ASC,
			t2.`mail_type` DESC
	";

	$rs = exec_query($sql, $als_query, array($dmn_id, $dmn_id));

	if ($rs->RecordCount() == 0) {
		return 0;
	} else {
		global $counter;

		while (!$rs->EOF) {
			$tpl->assign(
				'ITEM_CLASS',
				($counter % 2 == 0) ? 'content' : 'content2'
			);

			list(
				$mail_delete,
				$mail_delete_script,
				$mail_edit,
				$mail_edit_script
			) = gen_user_mail_action(
				$rs->fields['mail_id'],
				$rs->fields['status']
			);

			$mail_acc = decode_idna($rs->fields['mail_acc']);
			$show_dmn_name = decode_idna($dmn_name);

			$show_als_name = decode_idna($rs->fields['als_name']);

			$mail_types = explode(',', $rs->fields['mail_type']);
			$mail_type = '';

			foreach ($mail_types as $type) {
				$mail_type .= user_trans_mail_type($type);

				if (strpos($type, '_forward') !== false) {
					 $mail_type .= ': ' . str_replace(
					 	array("\r\n", "\n", "\r"),
						", ",
						$rs->fields['mail_forward']
					 );
				}

				$mail_type .= '<br />';
			}

			$tpl->assign(
				array(
					'MAIL_ACC' => $mail_acc . '@' . $show_als_name,
					'MAIL_TYPE' => $mail_type,
					'MAIL_STATUS' => translate_dmn_status($rs->fields['status']),
					'MAIL_DELETE' => $mail_delete,
					'MAIL_DELETE_SCRIPT'=> $mail_delete_script,
					'MAIL_EDIT' => $mail_edit,
					'MAIL_EDIT_SCRIPT' => $mail_edit_script
				)
			);

			gen_user_mail_auto_respond(
				$tpl,
				$rs->fields['mail_id'],
				$rs->fields['mail_type'],
				$rs->fields['status'],
				$rs->fields['mail_auto_respond']
			);

			$tpl->parse('MAIL_ITEM', '.mail_item');

			$rs->MoveNext();
			$counter++;
		}

		return $rs->RecordCount();
	}
} // end gen_page_als_mail_list()

/**
 * Must be documented
 *
 * @param object &$tpl reference to the template instance
 * @param object &$sql reference to the database instance
 * @param int $user_id customer id
 * @return void
 */
function gen_page_lists(&$tpl, &$sql, $user_id) {

	global $dmn_id;

	list(
		$dmn_id,
		$dmn_name,
		$dmn_gid,
		$dmn_uid,
		$dmn_created_id,
		$dmn_created,
		$domain_expires,
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
		$dmn_cgi
	) = get_domain_default_props($sql, $user_id);

	$dmn_mails = gen_page_dmn_mail_list($tpl, $sql, $dmn_id, $dmn_name);
	$sub_mails = gen_page_sub_mail_list($tpl, $sql, $dmn_id, $dmn_name);
	$alssub_mails = gen_page_als_sub_mail_list($tpl, $sql, $dmn_id, $dmn_name);
	$als_mails = gen_page_als_mail_list($tpl, $sql, $dmn_id, $dmn_name);
	
	// If 'uaction' is set and own value is != 'hide', the total includes
	// the number of email by default
	$counted_mails = $total_mails =
		$dmn_mails +
		$sub_mails +
		$als_mails +
		$alssub_mails;

	$default_mails = count_default_mails($sql, $dmn_id);

	if (Config::get('COUNT_DEFAULT_EMAIL_ADDRESSES') == 0) {
		if(isset($_POST['uaction']) && $_POST['uaction'] == 'show') {
			$counted_mails -= $default_mails;
		}
	} else {
		if(!isset($_POST['uaction']) || $_POST['uaction'] == 'hide') {
			$counted_mails += $default_mails;
		}
	}

	if ($total_mails > 0) {
		$tpl->assign(
			array(
				'MAIL_MESSAGE' => '',
				'DMN_TOTAL' => $dmn_mails,
				'SUB_TOTAL' => $sub_mails,
				'ALSSUB_TOTAL' => $sub_mails,
				'ALS_TOTAL' => $als_mails,
				'TOTAL_MAIL_ACCOUNTS' => $counted_mails,
				'ALLOWED_MAIL_ACCOUNTS' => ($dmn_mailacc_limit != 0) ?
					$dmn_mailacc_limit : tr('unlimited')
			)
		);
	} else {
		$tpl->assign(
			array(
				'MAIL_MSG' => tr('Mail accounts list is empty!'),
				'MAIL_ITEM' => '',
				'MAILS_TOTAL' => ''
			)
		);

		$tpl->parse('MAIL_MESSAGE', 'mail_message');
	}

} // end gen_page_lists()

/**
 * Count the number of email addresses created by default
 *
 * Return the number of default mail adresses according
 * the state of 'uaction''. If no 'uaction' is set or if the
 * 'uaction' is set to 'hide', 0 will be returned.
 * 
 * Note: 'uaction' = user action -> ($_POST['uaction'])
 *
 * For performances reasons, the query is performed only once
 * and the result is cached.
 * 
 * @author Laurent declercq <laurent.declercq@ispcp.net>
 * @since r2513
 * @param object &$sql reference to the Database instance
 * @param int domain name id
 * @return int number of default mails adresses
 */
function count_default_mails(&$sql, $dmn_id) {

	static $count_default_mails;

	if(!is_int($count_default_mails)) {

		$query = "
			SELECT COUNT(`mail_id`) AS cnt
			FROM
				`mail_users`
			WHERE
				`domain_id` = ?
			AND
				(
				 	`mail_acc` = 'abuse'
				OR
					`mail_acc` = 'postmaster'
				OR
					`mail_acc` = 'webmaster'
				)
		";

			$rs = exec_query($sql, $query, array($dmn_id));
			$count_default_mails = (int) $rs->fields['cnt'];
	}

	return $count_default_mails;
}

// dynamic page data.

if (isset($_SESSION['email_support']) && $_SESSION['email_support'] == 'no') {
	$tpl->assign('NO_MAILS', '');
}

gen_page_lists($tpl, $sql, $_SESSION['user_id']);

// static page messages.

gen_client_mainmenu(
	$tpl,
	Config::get('CLIENT_TEMPLATE_PATH') . '/main_menu_email_accounts.tpl'
);

gen_client_menu(
	$tpl,
	Config::get('CLIENT_TEMPLATE_PATH') . '/menu_email_accounts.tpl'
);

gen_logged_from($tpl);

check_permissions($tpl);

$tpl->assign(
	array(
		'TR_MANAGE_USERS' => tr('Manage users'),
		'TR_MAIL_USERS' => tr('Mail users'),
		'TR_MAIL' => tr('Mail'),
		'TR_TYPE' => tr('Type'),
		'TR_STATUS' => tr('Status'),
		'TR_ACTION' => tr('Action'),
		'TR_AUTORESPOND' => tr('Auto respond'),
		'TR_DMN_MAILS' => tr('Domain mails'),
		'TR_SUB_MAILS' => tr('Subdomain mails'),
		'TR_ALS_MAILS' => tr('Alias mails'),
		'TR_TOTAL_MAIL_ACCOUNTS' => tr('Mails total'),
		'TR_DELETE' => tr('Delete'),
		'TR_MESSAGE_DELETE' =>
			tr('Are you sure you want to delete %s?', true, '%s'),
	)
);

// Displays the "show/hide" button for default emails
// only if default mail address exists
if(count_default_mails($sql, $dmn_id) > 0) {

	$tpl->assign(
		array(
			'TR_DEFAULT_EMAILS_BUTTON' =>
			(!isset($_POST['uaction']) || $_POST['uaction'] != 'show') ?
				tr('Show default E-Mail addresses') :
				tr('Hide default E-Mail Addresses'),

			'VL_DEFAULT_EMAILS_BUTTON' =>
			(isset($_POST['uaction']) && $_POST['uaction'] == 'show') ?
				'hide' :'show'
		)
	);

} else {
	$tpl->assign(
		array('DEFAULT_MAILS_FORM' => '')
	);
}

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
unset_messages();
