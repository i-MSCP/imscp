<?php
/**
 * i-MSCP - internet Multi Server Control Panel
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2013 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @package     iMSCP_Core
 * @subpackage  Client
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2013 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generate page data
 *
 * @param iMSCP_pTemplate $tpl
 * @param string $dmnName Domain name
 * @param string $postCheck POST check
 * @return void
 */
function client_generatePageData($tpl, $dmnName, $postCheck)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$dmnName = decode_idna($dmnName);

	if ($postCheck == 'no') {
		$tpl->assign(
			array(
				'USERNAME' => '',
				'DOMAIN_NAME' => tohtml($dmnName),
				'MAIL_DMN_CHECKED' => $cfg->HTML_CHECKED,
				'MAIL_ALS_CHECKED' => '',
				'MAIL_SUB_CHECKED' => '',
				'MAIL_ALS_SUB_CHECKED' => '',
				'NORMAL_MAIL_CHECKED' => $cfg->HTML_CHECKED,
				'FORWARD_MAIL_CHECKED' => '',
				'FORWARD_LIST' => ''
			)
		);
	} else {
		if (!isset($_POST['forward_list'])) {
			$forwardList = '';
		} else {
			$forwardList = $_POST['forward_list'];
		}

		$tpl->assign(
			array(
				'USERNAME' => clean_input($_POST['username'], true),
				'DOMAIN_NAME' => tohtml($dmnName),
				'MAIL_DMN_CHECKED' => ($_POST['dmn_type'] === 'dmn') ? $cfg->HTML_CHECKED : '',
				'MAIL_ALS_CHECKED' => ($_POST['dmn_type'] === 'als') ? $cfg->HTML_CHECKED : '',
				'MAIL_SUB_CHECKED' => ($_POST['dmn_type'] === 'sub') ? $cfg->HTML_CHECKED : '',
				'MAIL_ALS_SUB_CHECKED' => ($_POST['dmn_type'] === 'als_sub') ? $cfg->HTML_CHECKED : '',
				'NORMAL_MAIL_CHECKED' => (isset($_POST['mail_type_normal'])) ? $cfg->HTML_CHECKED : '',
				'FORWARD_MAIL_CHECKED' => (isset($_POST['mail_type_forward'])) ? $cfg->HTML_CHECKED : '',
				'FORWARD_LIST' => $forwardList
			)
		);
	}
}

/**
 * Generate domain alias list
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $dmnId Domain unique identifier
 * @param string $postCheck POST check
 * @return void
 */
function client_generateAlsList($tpl, $dmnId, $postCheck)
{

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
	$stmt = exec_query($query, array($dmnId, $ok_status));

	if (!$stmt->rowCount()) {
		$tpl->assign(
			array(
				'ALS_ID' => '0',
				'ALS_SELECTED' => $cfg->HTML_SELECTED,
				'ALS_NAME' => tr('Empty list')
			)
		);

		$tpl->parse('ALS_LIST', 'als_list');
		$tpl->assign('TO_ALIAS_DOMAIN', '');
	} else {
		$firstPassed = false;

		while ($data = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			if ($postCheck === 'yes') {
				if (!isset($_POST['als_id'])) {
					$alsId = '';
				} else {
					$alsId = $_POST['als_id'];
				}

				if ($alsId == $data['alias_id']) {
					$alsSelected = $cfg->HTML_SELECTED;
				} else {
					$alsSelected = '';
				}
			} else {
				if (!$firstPassed) {
					$alsSelected = $cfg->HTML_SELECTED;
				} else {
					$alsSelected = '';
				}
			}

			$alsName = decode_idna($data['alias_name']);
			$tpl->assign(
				array(
					'ALS_ID' => $data['alias_id'],
					'ALS_SELECTED' => $alsSelected,
					'ALS_NAME' => tohtml($alsName)
				)
			);

			$tpl->parse('ALS_LIST', '.als_list');

			if (!$firstPassed) {
				$firstPassed = true;
			}
		}
	}
}

/**
 * Generate dmn subdomain list
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $dmnId Domain unique identifier
 * @param string $dmnName Domain name
 * @param string $postCheck POST check
 * @return void
 */
function client_generateDmnSubList($tpl, $dmnId, $dmnName, $postCheck)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$okStatus = $cfg->ITEM_OK_STATUS;

	$query = '
		SELECT
			`subdomain_id` AS `sub_id`, `subdomain_name` AS `sub_name`
		FROM
			`subdomain`
		WHERE
			`domain_id` = ?
		AND
			`subdomain_status` = ?
		ORDER BY
			`subdomain_name`
	';

	$stmt = exec_query($query, array($dmnId, $okStatus));

	if (!$stmt->rowCount()) {
		$tpl->assign(
			array(
				'SUB_ID' => '0',
				'SUB_SELECTED' => $cfg->HTML_SELECTED,
				'SUB_NAME' => tr('Empty list')
			)
		);

		$tpl->parse('SUB_LIST', 'sub_list');
		$tpl->assign('TO_SUBDOMAIN', '');
	} else {
		$firstPassed = false;

		while ($data = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			if ($postCheck === 'yes') {
				if (!isset($_POST['sub_id'])) {
					$subId = '';
				} else {
					$subId = $_POST['sub_id'];
				}

				if ($subId == $data['sub_id']) {
					$subSelected = $cfg->HTML_SELECTED;
				} else {
					$subSelected = '';
				}
			} else {
				if (!$firstPassed) {
					$subSelected = $cfg->HTML_SELECTED;
				} else {
					$subSelected = '';
				}
			}

			$subName = decode_idna($data['sub_name']);
			$dmnName = decode_idna($dmnName);
			$tpl->assign(
				array(
					'SUB_ID' => $data['sub_id'],
					'SUB_SELECTED' => $subSelected,
					'SUB_NAME' => tohtml($subName . '.' . $dmnName
					)
				)
			);

			$tpl->parse('SUB_LIST', '.sub_list');

			if (!$firstPassed) {
				$firstPassed = true;
			}
		}
	}
}

/**
 * Generate als subdomain list
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $dmnId Domain unique identifier
 * @param string $postCheck POST check
 * @return void
 */
function client_generateAlsSubList($tpl, $dmnId, $postCheck)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$okStatus = $cfg->ITEM_OK_STATUS;

	$query = '
		SELECT
			`t1`.`subdomain_alias_id` AS `als_sub_id`,
			`t1`.`subdomain_alias_name` AS `als_sub_name`, `t2`.`alias_name` AS `als_name`
		FROM
			`subdomain_alias` AS `t1`
		LEFT JOIN
			`domain_aliasses` AS `t2` ON (t1.`alias_id` = `t2`.`alias_id`)
		WHERE
			`t1`.`alias_id` IN (SELECT `alias_id` FROM `domain_aliasses` WHERE `domain_id` = ?)
		AND
			`t1`.`subdomain_alias_status` = ?
		ORDER BY
			`t1`.`subdomain_alias_name`
	';
	$stmt = exec_query($query, array($dmnId, $okStatus));

	if (!$stmt->rowCount()) {
		$tpl->assign(
			array(
				'ALS_SUB_ID' => '0',
				'ALS_SUB_SELECTED' => $cfg->HTML_SELECTED,
				'ALS_SUB_NAME' => tr('Empty list')
			)
		);

		$tpl->parse('ALS_SUB_LIST', 'sub_list');
		$tpl->assign('TO_ALIAS_SUBDOMAIN', '');
	} else {
		$firstPassed = false;

		while ($data = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			if ($postCheck === 'yes') {
				if (!isset($_POST['als_sub_id'])) {
					$alsSubId = '';
				} else {
					$alsSubId = $_POST['als_sub_id'];
				}

				if ($alsSubId == $data['als_sub_id']) {
					$alsSubSelected = $cfg->HTML_SELECTED;
				} else {
					$alsSubSelected = '';
				}
			} else {
				if (!$firstPassed) {
					$alsSubSelected = $cfg->HTML_SELECTED;
				} else {
					$alsSubSelected = '';
				}
			}

			$alsSubName = decode_idna($data['als_sub_name']);
			$alsName = decode_idna($data['als_name']);
			$tpl->assign(
				array(
					'ALS_SUB_ID' => $data['als_sub_id'],
					'ALS_SUB_SELECTED' => $alsSubSelected,
					'ALS_SUB_NAME' => tohtml($alsSubName . '.' . $alsName)
				)
			);

			$tpl->parse('ALS_SUB_LIST', '.als_sub_list');

			if (!$firstPassed) {
				$firstPassed = true;
			}
		}
	}
}

/**
 * Schedule addition of mail account
 *
 * @param int $dmnId Domain unique identifier
 * @param string $dmnName Domain name
 * @param string $mailAccount Mail account to add
 * @return bool
 */
function schedule_mail_account($dmnId, $dmnName, $mailAccount)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	/** @var $db iMSCP_Database */
	$db = iMSCP_Registry::get('db');

	$dmnProps = get_domain_default_props($_SESSION['user_id']);

	$mailAutoRespond = false;
	$mailAutoRespondText = '';
	$mailAddr = $mailAccount . '@' . decode_idna($dmnName);

	// Init variables
	$mailType = array();
	$subId = $mailPassword = $mailForward = '';

	if (array_key_exists('mail_type_normal', $_POST)) {
		$mailPassword = $_POST['pass'];
		$mailForward = '_no_';

		if ($_POST['dmn_type'] == 'dmn') {
			$mailType[] = MT_NORMAL_MAIL;
			$subId = '0';
		} else if ($_POST['dmn_type'] == 'sub') {
			$mailType[] = MT_SUBDOM_MAIL;
			$subId = $_POST['sub_id'];
		} else if ($_POST['dmn_type'] == 'als_sub') {
			$mailType[] = MT_ALSSUB_MAIL;
			$subId = $_POST['als_sub_id'];
		} else if ($_POST['dmn_type'] == 'als') {
			$mailType[] = MT_ALIAS_MAIL;
			$subId = $_POST['als_id'];
		} else {
			set_page_message(tr('Unknown domain type.'), 'error');
			return false;
		}
	}

	if (array_key_exists('mail_type_forward', $_POST)) {
		if ($_POST['dmn_type'] == 'dmn') {
			$mailType[] = MT_NORMAL_FORWARD;
			$subId = '0';
		} else if ($_POST['dmn_type'] == 'sub') {
			$mailType[] = MT_SUBDOM_FORWARD;
			$subId = $_POST['sub_id'];
		} else if ($_POST['dmn_type'] == 'als_sub') {
			$mailType[] = MT_ALSSUB_FORWARD;
			$subId = $_POST['als_sub_id'];
		} else if ($_POST['dmn_type'] == 'als') {
			$mailType[] = MT_ALIAS_FORWARD;
			$subId = $_POST['als_id'];
		} else {
			set_page_message(tr('Unknown domain type.'), 'error');
			return false;
		}

		if (!isset($_POST['mail_type_normal'])) {
			$mailPassword = '_no_';
		}

		$mailForward = $_POST['forward_list'];
		$farray = preg_split("/[\n,]+/", $mailForward);
		$mailAccounts = array();

		foreach ($farray as $value) {
			$value = trim($value);

			if (!chk_email($value) && $value != '') {
				// @todo ERROR .. strange :) not email in this line - warning
				set_page_message(tr('Mailformat of an address in your forward list is incorrect.'), 'error');
				return false;
			} else if ($value == '') {
				set_page_message(tr('Mail forward list is empty.'), 'info');
				return false;
			} else if ($mailAccount . '@' . decode_idna($dmnName) == $value) {
				set_page_message(tr('Forward to same address is not allowed.'), 'error');
				return false;
			}

			$mailAccounts[] = $value;
		}

		$mailForward = implode(',', $mailAccounts);
	}

	$mailType = implode(',', $mailType);
	list($dmnType) = explode('_', $mailType, 2);

	$checkAccountQuery = "
		SELECT
			COUNT(`mail_id`) AS `cnt`
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
	$stmt = exec_query($checkAccountQuery, array($mailAccount, $dmnId, $subId, $dmnType));

	if ($stmt->fields['cnt'] > 0) {
		set_page_message(tr('Email account already exists.'), 'error');
		return false;
	}

	iMSCP_Events_Manager::getInstance()->dispatch(
		iMSCP_Events::onBeforeAddMail, array('mailUsername' => $mailAccount, 'MailAddress' => $mailAddr)
	);

	$query = '
		INSERT INTO `mail_users` (
			`mail_acc`, `mail_pass`, `mail_forward`, `domain_id`, `mail_type`, `sub_id`, `status`, `mail_auto_respond`,
			`mail_auto_respond_text`, `quota`, `mail_addr`
		) VALUES
			(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
	';

	exec_query(
		$query,
		array(
			$mailAccount, $mailPassword, $mailForward, $dmnId, $mailType, $subId, $cfg->ITEM_TOADD_STATUS,
			$mailAutoRespond, $mailAutoRespondText, $dmnProps['mail_quota'], $mailAccount . '@' . $dmnName
		)
	);

	iMSCP_Events_Manager::getInstance()->dispatch(
		iMSCP_Events::onAfterAddMail,
		array('mailUsername' => $mailAccount, 'mailAddress' => $mailAddr, 'mailId' => $db->insertId())
	);

	send_request();

	write_log(
		"{$_SESSION['user_logged']}: added new email account: " . (!empty($mailAddr) ? $mailAddr : $mailAccount),
		E_USER_NOTICE
	);
	set_page_message(tr('Email account successfully scheduled for addition.'), 'success');

	redirectTo('mail_accounts.php');
	exit;
}

/**
 * Check mail account
 *
 * @param int $dmnId Domain unique identifier
 * @param string $dmnName Domain name
 * @return bool
 */
function client_checkMailAccount($dmnId, $dmnName)
{
	$mailTypeNormal = isset($_POST['mail_type_normal']) ? $_POST['mail_type_normal'] : false;
	$mailTypeForward = isset($_POST['mail_type_forward']) ? $_POST['mail_type_forward'] : false;

	if (($mailTypeNormal == false) && ($mailTypeForward == false)) {
		set_page_message(tr('Please select at least one mail type.'), 'error');
		return false;
	}

	$mailPassword = $mailPasswordConfirmation = '';

	if ($mailTypeNormal) {
		$mailPassword = clean_input($_POST['pass']);
		$mailPasswordConfirmation = clean_input($_POST['pass_rep']);
	}

	if (!isset($_POST['username']) || $_POST['username'] == '') {
		set_page_message(tr('Please enter email account username.'), 'error');
		return false;
	}

	$mailAccount = strtolower(clean_input($_POST['username']));

	if (!imscp_check_local_part($mailAccount)) {
		set_page_message(tr('Invalid email local part.'), 'error');
		return false;
	}

	if ($mailTypeNormal) {
		if (trim($mailPassword) == '' || trim($mailPasswordConfirmation) == '') {
			set_page_message(tr('Password data is missing.'), 'error');
			return false;
		} else if ($mailPassword !== $mailPasswordConfirmation) {
			set_page_message(tr("Passwords do not match."), 'error');
			return false;
		} else if (!checkPasswordSyntax($mailPassword, "/[`\xb4'\"\\\\\x01-\x1f\015\012|<>^$]/i")) {
			return false;
		}
	}

	if ($_POST['dmn_type'] == 'sub') {
		$id = 'sub_id';
		$query = "
			SELECT
				CONCAT(`t1`.`subdomain_name`, '.', `t2`.`domain_name`) AS `name`
			FROM
				`subdomain` AS `t1`,`domain` AS `t2`
			WHERE
				`t1`.`domain_id` = `t2`.`domain_id`
			AND
				`t1`.`subdomain_id` = ?
			AND
				`t1`.`domain_id` = ?
		";
		$type = tr('Subdomain');
	} elseif ($_POST['dmn_type'] == 'als_sub') {
		$id = 'als_sub_id';
		$query = "
			SELECT
				CONCAT(`t1`.`subdomain_alias_name`, '.', `t2`.`alias_name`) AS `name`
			FROM
				`subdomain_alias` AS `t1`
			LEFT JOIN
				`domain_aliasses` AS `t2` ON (`t1`.`alias_id` = `t2`.`alias_id`)
			LEFT JOIN
				`domain` AS `t3` ON (`t2`.`domain_id` = `t3`.`domain_id`)
			WHERE
				`t1`.`subdomain_alias_id` = ?
			AND
				`t3`.`domain_id` = ?
		";
		$type = tr('Subdomain alias');
	} elseif ($_POST['dmn_type'] == 'als') {
		$id = 'als_id';
		$query = 'SELECT `alias_name` AS `name` FROM `domain_aliasses` WHERE `alias_id` = ? AND `domain_id` = ?';
		$type = tr('Alias');
	}

	if (isset($id) && isset($type) && isset($query)) {
		if (!isset($_POST[$id])) {
			set_page_message(sprintf(tr('%s list is empty. You cannot add email accounts.'), $type), 'error');
			return false;
		}

		if (!is_numeric($_POST[$id])) {
			set_page_message(sprintf(tr('%s id is invalid. You cannot add email accounts.'), $type), 'error');
			return false;
		}

		$stmt = exec_query($query, array($_POST[$id], $dmnId));

		if ($stmt->fields['name'] == '') {
			set_page_message(sprintf(tr('%s id is invalid! You cannot add email accounts.'), $type), 'error');
			return false;
		}

		$dmnName = $stmt->fields['name'];
	}

	if ($mailTypeForward && empty($_POST['forward_list'])) {
		set_page_message(tr('Forward list is empty.'), 'info');
		return false;
	}

	schedule_mail_account($dmnId, $dmnName, $mailAccount);
	return true;
}

/**
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $customerId Customer unique identifier
 * @return void
 */
function client_generatePage($tpl, $customerId)
{
	$dmnProps = get_domain_default_props($customerId);
	$dmnId = $dmnProps['domain_id'];
	$dmnName = $dmnProps['domain_name'];
	$dmnMailAccountLimit = $dmnProps['domain_mailacc_limit'];

	list($nbMailAccounts) = get_domain_running_mail_acc_cnt($dmnId);

	if ($dmnMailAccountLimit != 0 && $nbMailAccounts >= $dmnMailAccountLimit) {
		set_page_message(tr('Email account limit is reached.'), 'error');
		redirectTo('mail_accounts.php');
	} else {
		$postCheck = isset($_POST['uaction']) ? 'yes' : 'no';
		client_generatePageData($tpl, $dmnName, $postCheck);
		client_generateAlsList($tpl, $dmnId, $postCheck);
		client_generateDmnSubList($tpl, $dmnId, $dmnName, $postCheck);
		client_generateAlsSubList($tpl, $dmnId, $postCheck);

		if (isset($_POST['uaction']) && $_POST['uaction'] == 'add_user') {
			client_checkMailAccount($dmnId, $dmnName);
		}
	}
}

/***********************************************************************************************************************
 * Main
 */
// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

customerHasFeature('mail') or showBadRequestErrorPage();

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'client/mail_add.tpl',
		'page_message' => 'layout',
		'als_list' => 'page',
		'sub_list' => 'page',
		'als_sub_list' => 'page',
		'to_alias_domain' => 'page',
		'to_subdomain' => 'page',
		'to_alias_subdomain' => 'page'
	)
);


if (isset($_SESSION['email_support']) && $_SESSION['email_support'] == "no") {
	redirectTo('index.php');
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Client / Email / Add Email Account'),
		'ISP_LOGO' => layout_getUserLogo()
	)
);

client_generatePage($tpl, $_SESSION['user_id']);
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
		'TR_MAIl_ACCOUNT_DATA' => tr('Email account data')
	)
);

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
