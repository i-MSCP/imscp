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

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('layout', 'shared/layouts/ui.tpl');
$tpl->define_dynamic('page', 'client/mail_accounts.tpl');
$tpl->define_dynamic('page_message', 'layout');
$tpl->define_dynamic('mail_message', 'page');
$tpl->define_dynamic('mail_item', 'page');
$tpl->define_dynamic('mail_auto_respond', 'mail_item');
$tpl->define_dynamic('default_mails_form', 'page');
$tpl->define_dynamic('mails_total', 'page');
$tpl->define_dynamic('no_mails', 'page');
$tpl->define_dynamic('table_list', 'page');

$tpl->assign(
	array(
		'TR_PAGE_TITLE'	=> tr('i-MSCP - Client / Manage mail'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()));

/**
 *
 * @param int $mail_id mail id
 * @param string $mail_status mail status
 * @return array
 */
function gen_user_mail_action($mail_id, $mail_status) {

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if ($mail_status === $cfg->ITEM_OK_STATUS) {
		return array(
			tr('Delete'),
			"mail_delete.php?id=$mail_id",
			tr('Edit'),
			"mail_edit.php?id=$mail_id");
	} else {
		return array(tr('N/A'), '#', tr('N/A'), '#');
	}
}

/**
 *
 * @param iMSCP_pTemplate $tpl pTemplate instance
 * @param int $mail_id
 * @param string $mail_type
 * @param string $mail_status
 * @param int $mail_auto_respond
 * @return void
 */
function gen_user_mail_auto_respond(
	$tpl, $mail_id, $mail_type, $mail_status, $mail_auto_respond) {

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if ($mail_status === $cfg->ITEM_OK_STATUS) {
		if ($mail_auto_respond == false) {
			$tpl->assign(
				array(
					'AUTO_RESPOND_DISABLE' => tr('Enable'),
					'AUTO_RESPOND_DISABLE_SCRIPT' =>
						"mail_autoresponder_enable.php?id=$mail_id",
					'AUTO_RESPOND_EDIT' => tr('N/A'),
					'AUTO_RESPOND_EDIT_SCRIPT' => '#',
					'AUTO_RESPOND_VIS' => 'inline'));
		} else {
			$tpl->assign(
				array(
					'AUTO_RESPOND_DISABLE' => tr('Disable'),
					'AUTO_RESPOND_DISABLE_SCRIPT' =>
						"mail_autoresponder_disable.php?id=$mail_id",
					'AUTO_RESPOND_EDIT' => tr('Edit'),
					'AUTO_RESPOND_EDIT_SCRIPT' =>
						"mail_autoresponder_edit.php?id=$mail_id",
					'AUTO_RESPOND_VIS' => 'inline'));
		}
	} else {
		$tpl->assign(
			array(
				'AUTO_RESPOND_DISABLE' => tr('Please wait for update'),
				'AUTO_RESPOND_DISABLE_SCRIPT' => '#',
				'AUTO_RESPOND_EDIT' => tr('N/A'),
				'AUTO_RESPOND_EDIT_SCRIPT' => '#',
				'AUTO_RESPOND_VIS' => 'inline'));
	}
}

/**
 *
 * @param iMSCP_pTemplate $tpl reference to pTemplate object
 * @param int $dmn_id domain name id
 * @param string $dmn_name domain name
 * @return int number of domain mails adresses
 */
function gen_page_dmn_mail_list($tpl, $dmn_id, $dmn_name) {

	$dmn_query = "
		SELECT
			`mail_id`, `mail_acc`, `mail_type`, `status`, `mail_auto_respond`,
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
				`mail_type` LIKE '%" . MT_NORMAL_MAIL . "%'
			OR
				`mail_type` LIKE '%" . MT_NORMAL_FORWARD . "%'
			)
	";

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

	$dmn_query .= "ORDER BY `mail_acc` ASC, `mail_type` DESC";

	$rs = exec_query($dmn_query, $dmn_id);

	if ($rs->recordCount() == 0) {
		return 0;
	} else {
		while (!$rs->EOF) {

			list(
				$mail_delete,
				$mail_delete_script,
				$mail_edit,
				$mail_edit_script
			) = gen_user_mail_action(
				$rs->fields['mail_id'], $rs->fields['status']
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
							array("\r\n", "\n", "\r"), ", ",
							$rs->fields['mail_forward']
						);
				}

				$mail_type .= '<br />';
			}

			$tpl->assign(
				array(
					'MAIL_ACC' => tohtml($mail_acc . '@' . $show_dmn_name),
					'MAIL_TYPE' => $mail_type,
					'MAIL_STATUS' => translate_dmn_status($rs->fields['status']),
					'MAIL_DELETE' => $mail_delete,
					'MAIL_DELETE_SCRIPT' => $mail_delete_script,
					'MAIL_EDIT' => $mail_edit,
					'MAIL_EDIT_SCRIPT' => $mail_edit_script
				)
			);

			gen_user_mail_auto_respond(
				$tpl, $rs->fields['mail_id'], $rs->fields['mail_type'],
				$rs->fields['status'], $rs->fields['mail_auto_respond']
			);

			$tpl->parse('MAIL_ITEM', '.mail_item');

			$rs->moveNext();
		}

		return $rs->recordCount();
	}
} // end gen_page_dmn_mail_list()

/**
 * Must be documented
 *
 * @param iMSCP_pTemplate $tpl reference to the template object
 * @param int $dmn_id domain name id
 * @param strinc $dmn_name domain name
 * @return int number of subdomain mails addresses
 */
function gen_page_sub_mail_list($tpl, $dmn_id, $dmn_name) {

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

	$sub_query .= "ORDER BY t2.`mail_acc` ASC, t2.`mail_type` DESC";
	$rs = exec_query($sub_query, array($dmn_id, $dmn_id));

	if ($rs->recordCount() == 0) {
		return 0;
	} else {
		while (!$rs->EOF) {
			list(
				$mail_delete, $mail_delete_script,
				$mail_edit, $mail_edit_script
			) = gen_user_mail_action(
				$rs->fields['mail_id'], $rs->fields['status']
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
						tohtml($mail_acc.'@'.$show_sub_name.'.'.$show_dmn_name),
					'MAIL_TYPE' => $mail_type,
					'MAIL_STATUS' => translate_dmn_status($rs->fields['status']),
					'MAIL_DELETE' => $mail_delete,
					'MAIL_DELETE_SCRIPT' => $mail_delete_script,
					'MAIL_EDIT' => $mail_edit,
					'MAIL_EDIT_SCRIPT' => $mail_edit_script));

			gen_user_mail_auto_respond(
				$tpl, $rs->fields['mail_id'], $rs->fields['mail_type'],
				$rs->fields['status'], $rs->fields['mail_auto_respond']);

			$tpl->parse('MAIL_ITEM', '.mail_item');

			$rs->moveNext();
		}

		return $rs->recordCount();
	}
} // end gen_page_sub_mail_list()

/**
 * Must be documented
 *
 * @param iMSCP_pTemplate $tpl reference to the pTemplate object
 * @param int $dmn_id domain name id
 * @param string $dmn_name domain name
 * @return int number of subdomain alias mails addresses
 */
function gen_page_als_sub_mail_list($tpl, $dmn_id, $dmn_name) {

	$sub_query = "
		SELECT
			t1.`mail_id`, t1.`mail_acc`, t1.`mail_type`, t1.`status`,
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
				t1.`mail_type` LIKE '%" . MT_ALSSUB_MAIL . "%'
			OR
				t1.`mail_type` LIKE '%" . MT_ALSSUB_FORWARD . "%'
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

	$sub_query .= "ORDER BY t1.`mail_acc` ASC, t1.`mail_type` DESC";
	$rs = exec_query($sub_query, $dmn_id);

	if ($rs->recordCount() == 0) {
		return 0;
	} else {
		while (!$rs->EOF) {
			list(
				$mail_delete, $mail_delete_script, $mail_edit, $mail_edit_script
			) = gen_user_mail_action(
				$rs->fields['mail_id'], $rs->fields['status']
			);

			$mail_acc = decode_idna($rs->fields['mail_acc']);
			$show_alssub_name = decode_idna($rs->fields['alssub_name']);
			$mail_types = explode(',', $rs->fields['mail_type']);
			$mail_type = '';

			foreach ($mail_types as $type) {
				$mail_type .= user_trans_mail_type($type);

				if (strpos($type, '_forward') !== false) {
					$mail_type .= ': ' . str_replace(
						array("\r\n", "\n", "\r"), ", ",
						$rs->fields['mail_forward']
					);
				}

				$mail_type .= '<br />';
			}

			$tpl->assign(
				array(
					'MAIL_ACC' => tohtml($mail_acc . '@' . $show_alssub_name),
					'MAIL_TYPE' => $mail_type,
					'MAIL_STATUS' => translate_dmn_status($rs->fields['status']),
					'MAIL_DELETE' => $mail_delete,
					'MAIL_DELETE_SCRIPT' => $mail_delete_script,
					'MAIL_EDIT' => $mail_edit,
					'MAIL_EDIT_SCRIPT' => $mail_edit_script
				)
			);

			gen_user_mail_auto_respond(
				$tpl, $rs->fields['mail_id'], $rs->fields['mail_type'],
				$rs->fields['status'], $rs->fields['mail_auto_respond']
			);

			$tpl->parse('MAIL_ITEM', '.mail_item');
			$rs->moveNext();
		}

		return $rs->recordCount();
	}
} // end gen_page_als_sub_mail_list()

/**
 * Must be documented
 *
 * @param pTtempalte $tpl reference to pTemplate object
 * @param int $dmn_id domain name id;
 * @param string $dmn_name domain name
 * @return int number of domain alias mails addresses
 */
function gen_page_als_mail_list($tpl, $dmn_id, $dmn_name) {

	$als_query = "
		SELECT
			t1.`alias_id` AS als_id, t1.`alias_name` AS als_name, t2.`mail_id`,
			t2.`mail_acc`, t2.`mail_type`, t2.`status`, t2.`mail_auto_respond`,
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
				t2.`mail_type` LIKE '%" . MT_ALIAS_MAIL . "%'
			OR
				t2.`mail_type` LIKE '%" . MT_ALIAS_FORWARD . "%'
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

	$als_query .= "ORDER BY t2.`mail_acc` ASC, t2.`mail_type` DESC";
	$rs = exec_query($als_query, array($dmn_id, $dmn_id));

	if ($rs->recordCount() == 0) {
		return 0;
	} else {
		while (!$rs->EOF) {

			list(
				$mail_delete, $mail_delete_script, $mail_edit, $mail_edit_script
			) = gen_user_mail_action(
				$rs->fields['mail_id'], $rs->fields['status']
			);

			$mail_acc = decode_idna($rs->fields['mail_acc']);
			// Unused variable
			// $show_dmn_name = decode_idna($dmn_name);
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
					'MAIL_ACC' => tohtml($mail_acc . '@' . $show_als_name),
					'MAIL_TYPE' => $mail_type,
					'MAIL_STATUS' => translate_dmn_status($rs->fields['status']),
					'MAIL_DELETE' => $mail_delete,
					'MAIL_DELETE_SCRIPT' => $mail_delete_script,
					'MAIL_EDIT' => $mail_edit,
					'MAIL_EDIT_SCRIPT' => $mail_edit_script));

			gen_user_mail_auto_respond(
				$tpl, $rs->fields['mail_id'], $rs->fields['mail_type'],
				$rs->fields['status'], $rs->fields['mail_auto_respond']);

			$tpl->parse('MAIL_ITEM', '.mail_item');
			$rs->moveNext();
		}

		return $rs->recordCount();
	}
} // end gen_page_als_mail_list()

/**
 * Must be documented
 *
 * @param iMSCP_pTemplate $tpl Reference to the pTemplate object
 * @param int $user_id Customer id
 * @return void
 */
function gen_page_lists($tpl, $user_id) {

	global $domainId;

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	list($domainId,$dmn_name,,,,,,,$dmn_mailacc_limit
	) = get_domain_default_props($user_id);

	$dmn_mails = gen_page_dmn_mail_list($tpl, $domainId, $dmn_name);
	$sub_mails = gen_page_sub_mail_list($tpl, $domainId, $dmn_name);
	$alssub_mails = gen_page_als_sub_mail_list($tpl, $domainId, $dmn_name);
	$als_mails = gen_page_als_mail_list($tpl, $domainId, $dmn_name);

	// If 'uaction' is set and own value is != 'hide', the total includes
	// the number of email by default
	$counted_mails = $total_mails =
		$dmn_mails + $sub_mails + $als_mails + $alssub_mails;

	$default_mails = count_default_mails($domainId);

	if ($cfg->COUNT_DEFAULT_EMAIL_ADDRESSES == 0) {
		if (isset($_POST['uaction']) && $_POST['uaction'] == 'show') {
			$counted_mails -= $default_mails;
		}
	} else {
		if (!isset($_POST['uaction']) || $_POST['uaction'] == 'hide') {
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
				'ALLOWED_MAIL_ACCOUNTS' => ($dmn_mailacc_limit != 0)
					? $dmn_mailacc_limit : tr('unlimited')
			)
		);
	} else {
		if (!isset($_POST['uaction']) || $_POST['uaction'] == 'hide') {
			$tpl->assign(array('TABLE_LIST' => ''));
		}

		$tpl->assign(
			array(
				'MAIL_MSG' => tr('Mail accounts list is empty!'),
				'MAIL_ITEM' => '', 'MAILS_TOTAL' => ''
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
 * @author Laurent declercq <l.declercq@nuxwin.com>
 * @since r2513
 * @param int $dmn_id Domain name id
 * @return int Number of default mails adresses
 */
function count_default_mails($dmn_id) {

	static $count_default_mails;

	if (!is_int($count_default_mails)) {

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

		$rs = exec_query($query, $dmn_id);
		$count_default_mails = (int) $rs->fields['cnt'];
	}

	return $count_default_mails;
}

if (isset($_SESSION['email_support']) && $_SESSION['email_support'] == 'no') {
	$tpl->assign('NO_MAILS', '');
}

gen_page_lists($tpl, $_SESSION['user_id']);
generateNavigation($tpl);

$tpl->assign(
	array(
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
		'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete %s?', true, '%s')));

// Displays the "show/hide" button for default emails
// only if default mail address exists
if (count_default_mails($domainId) > 0) {

	$tpl->assign(
		array(
			'TR_DEFAULT_EMAILS_BUTTON' =>
			(!isset($_POST['uaction']) || $_POST['uaction'] != 'show') ? tr('Show default E-Mail addresses') : tr('Hide default E-Mail Addresses'),
			'VL_DEFAULT_EMAILS_BUTTON' => (isset($_POST['uaction']) && $_POST['uaction'] == 'show') ? 'hide' :'show'));

} else {
	$tpl->assign(array('DEFAULT_MAILS_FORM' => ''));
}

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();


unsetMessages();
