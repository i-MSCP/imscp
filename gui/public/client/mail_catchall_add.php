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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2014 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    iMSCP
 * @package     Client_Mail
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
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
 * @param int $id
 * @return void
 */
function client_generatePage($tpl, $id)
{
	$cfg = iMSCP_Registry::get('config');

	$domainProps = get_domain_default_props($_SESSION['user_id']);
	$dmnId = $domainProps['domain_id'];
	$dmnMailAccountLimit = $domainProps['domain_mailacc_limit'];

	list($nbMailAccounts) = get_domain_running_mail_acc_cnt($dmnId);

	if ($dmnMailAccountLimit != 0 && $nbMailAccounts >= $dmnMailAccountLimit) {
		set_page_message(tr('Email account limit is reached.'), 'error');
		redirectTo('mail_catchall.php');
	}

	$okStatus = 'ok';
	$match = array();

	if (preg_match("/^(\d+);(normal|alias|subdom|alssub)$/", $id, $match)) {
		$itemId = $match[1];
		$itemType = $match[2];

		if ($itemType == 'normal') {
			$query = '
				SELECT
					`t1`.`mail_id`, `t1`.`mail_type`, `t2`.`domain_name`, `t1`.`mail_acc`
				FROM
					`mail_users` AS `t1`, `domain` AS `t2`
				WHERE
					`t1`.`domain_id` = ?
				AND
					`t2`.`domain_id` = ?
				AND
					`t1`.`sub_id` = ?
				AND
					`t1`.`status` = ?
				ORDER BY
					`t1`.`mail_type` DESC, `t1`.`mail_acc`
			';
			$stmt = exec_query($query, array($itemId, $itemId, 0, $okStatus));

			if (!$stmt->rowCount()) {
				$tpl->assign(
					array(
						'FORWARD_MAIL_CHECKED' => $cfg->HTML_CHECKED,
						'FORWARD_LIST_VAL' => isset($_POST['forward_list']) ? tohtml($_POST['forward_list']) : '',
						'MAIL_LIST' => ''
					)
				);
			} else {
				$tpl->assign(
					array(
						'NORMAL_MAIL_CHECKED' => (isset($_POST['mail_type']) && $_POST['mail_type'] == 'forward')
							? '' : $cfg->HTML_CHECKED,
						'FORWARD_MAIL_CHECKED' => (isset($_POST['mail_type']) && $_POST['mail_type'] == 'forward')
							? $cfg->HTML_CHECKED : '',
						'FORWARD_LIST_VAL' => isset($_POST['forward_list']) ? tohtml($_POST['forward_list']) : ''
					)
				);

				while ($data = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
					$showMailAccount = decode_idna($data['mail_acc']);
					$showDomainName = decode_idna($data['domain_name']);
					$mailAccount = $data['mail_acc'];
					$domainName = $data['domain_name'];
					$tpl->assign(
						array(
							'MAIL_ID' => $data['mail_id'],
							'MAIL_ACCOUNT' => tohtml($showMailAccount . '@' . $showDomainName),
							'MAIL_ACCOUNT_PUNNY' => tohtml($mailAccount . '@' . $domainName)
						)
					);

					$tpl->parse('MAIL_ITEM', '.mail_item');
				}
			}
		} elseif ($itemType == 'alias') {
			$query = "
				SELECT
					`t1`.`mail_id`, `t1`.`mail_type`, `t2`.`alias_name`, `t1`.`mail_acc`
				FROM
					`mail_users` AS `t1`, `domain_aliasses` AS `t2`
				WHERE
					`t1`.`sub_id` = `t2`.`alias_id`
				AND
					`t1`.`status` = ?
				AND
					`t1`.`mail_type` LIKE 'alias_%'
				AND
					`t2`.`alias_id` = ?
				ORDER BY
					`t1`.`mail_type` DESC, `t1`.`mail_acc`
			";

			$stmt = exec_query($query, array($okStatus, $itemId));

			if (!$stmt->rowCount()) {
				$tpl->assign(
					array(
						'FORWARD_MAIL_CHECKED' => $cfg->HTML_CHECKED,
						'FORWARD_LIST_VAL' => isset($_POST['forward_list']) ? tohtml($_POST['forward_list']) : '',
						'MAIL_LIST' => ''
					)
				);
			} else {
				$tpl->assign(
					array(
						'NORMAL_MAIL_CHECKED' => (isset($_POST['mail_type']) && $_POST['mail_type'] == 'forward')
							? '' : $cfg->HTML_CHECKED,
						'FORWARD_MAIL_CHECKED' => (isset($_POST['mail_type']) && $_POST['mail_type'] == 'forward')
							? $cfg->HTML_CHECKED : '',
						'FORWARD_LIST_VAL' => isset($_POST['forward_list']) ? tohtml($_POST['forward_list']) : ''
					)
				);

				while ($data = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
					$showMailAccount = decode_idna($data['mail_acc']);
					$show_alias_name = decode_idna($data['alias_name']);
					$mailAccount = $data['mail_acc'];
					$alsName = $data['alias_name'];

					$tpl->assign(
						array(
							'MAIL_ID' => $data['mail_id'],
							'MAIL_ACCOUNT' => tohtml($showMailAccount . '@' . $show_alias_name),
							'MAIL_ACCOUNT_PUNNY' => tohtml($mailAccount . '@' . $alsName)
						)
					);

					$tpl->parse('MAIL_ITEM', '.mail_item');
				}
			}
		} elseif ($itemType == 'subdom') {
			$query = "
				SELECT
					`t1`.`mail_id`, `t1`.`mail_type`,
					CONCAT(`t2`.`subdomain_name`, '.', `t3`.`domain_name`) AS `subdomain_name`,
					`t1`.`mail_acc`
				FROM
					`mail_users` AS `t1`, `subdomain` AS `t2`, `domain` AS `t3`
				WHERE
					`t1`.`sub_id` = `t2`.`subdomain_id`
				AND
					`t2`.`domain_id` = `t3`.`domain_id`
				AND
					`t1`.`status` = ?
				AND
					`t1`.`mail_type` LIKE 'subdom_%'
				AND
					`t2`.`subdomain_id` = ?
				ORDER BY
					`t1`.`mail_type` DESC, `t1`.`mail_acc`
			";
			$stmt = exec_query($query, array($okStatus, $itemId));

			if (!$stmt->rowCount()) {
				$tpl->assign(
					array(
						'FORWARD_MAIL_CHECKED' => $cfg->HTML_CHECKED,
						'FORWARD_LIST_VAL' => isset($_POST['forward_list']) ? tohtml($_POST['forward_list']) : '',
						'MAIL_LIST' => ''
					)
				);
			} else {
				$tpl->assign(
					array(
						'NORMAL_MAIL_CHECKED' => (isset($_POST['mail_type']) && $_POST['mail_type'] == 'forward')
							? '' : $cfg->HTML_CHECKED,
						'FORWARD_MAIL_CHECKED' => (isset($_POST['mail_type']) && $_POST['mail_type'] == 'forward')
							? $cfg->HTML_CHECKED : '',
						'FORWARD_LIST_VAL' => isset($_POST['forward_list']) ? tohtml($_POST['forward_list']) : ''
					)
				);

				while ($data = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
					$showMailAccount = decode_idna($data['mail_acc']);
					$show_alias_name = decode_idna($data['subdomain_name']);
					$mailAccount = $data['mail_acc'];
					$alsName = $data['subdomain_name'];

					$tpl->assign(
						array(
							'MAIL_ID' => $data['mail_id'],
							'MAIL_ACCOUNT' => tohtml($showMailAccount . '@' . $show_alias_name),
							'MAIL_ACCOUNT_PUNNY' => tohtml($mailAccount . '@' . $alsName)
						)
					);

					$tpl->parse('MAIL_ITEM', '.mail_item');
				}
			}
		} elseif ($itemType == 'alssub') {
			$query = "
				SELECT
					`t1`.`mail_id`, `t1`.`mail_type`,
					CONCAT(`t2`.`subdomain_alias_name`, '.', `t3`.`alias_name`) AS `subdomain_name`,
					`t1`.`mail_acc`
				FROM
					`mail_users` AS `t1`, `subdomain_alias` AS `t2`, `domain_aliasses` AS `t3`
				WHERE
					`t1`.`sub_id` = `t2`.`subdomain_alias_id`
				AND
					`t2`.`alias_id` = `t3`.`alias_id`
				AND
					`t1`.`status` = ?
				AND
					`t1`.`mail_type` LIKE 'alssub_%'
				AND
					`t2`.`subdomain_alias_id` = ?
				ORDER BY
					`t1`.`mail_type` DESC, `t1`.`mail_acc`
			";
			$stmt = exec_query($query, array($okStatus, $itemId));

			if (!$stmt->rowCount()) {
				$tpl->assign(
					array(
						'FORWARD_MAIL_CHECKED' => $cfg->HTML_CHECKED,
						'FORWARD_LIST_VAL' => isset($_POST['forward_list']) ? tohtml($_POST['forward_list']) : '',
						'MAIL_LIST' => ''
					)
				);
			} else {
				$tpl->assign(
					array(
						'NORMAL_MAIL_CHECKED' => (isset($_POST['mail_type']) && $_POST['mail_type'] == 'forward')
							? '' : $cfg->HTML_CHECKED,
						'FORWARD_MAIL_CHECKED' => (isset($_POST['mail_type']) && $_POST['mail_type'] == 'forward')
							? $cfg->HTML_CHECKED : '',
						'FORWARD_LIST_VAL' => isset($_POST['forward_list']) ? tohtml($_POST['forward_list']) : ''
					)
				);

				while ($data = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
					$showMailAccount = decode_idna($data['mail_acc']);
					$show_alias_name = decode_idna($data['subdomain_name']);
					$mailAccount = $data['mail_acc'];
					$alsName = $data['subdomain_name'];

					$tpl->assign(
						array(
							'MAIL_ID' => $data['mail_id'],
							'MAIL_ACCOUNT' => tohtml($showMailAccount . '@' . $show_alias_name),
							'MAIL_ACCOUNT_PUNNY' => tohtml($mailAccount . '@' . $alsName)
						)
					);

					$tpl->parse('MAIL_ITEM', '.mail_item');
				}
			}
		}
	} else {
		showBadRequestErrorPage();
	}
}

/**
 * Add catchall
 *
 * @param string $id
 * @return void
 */
function client_addCatchall($id)
{
	list($realId, $type) = explode(';', $id);

	// Check if user is owner of the domain
	if (!preg_match('(normal|alias|subdom|alssub)', $type) || who_owns_this($realId, $type) != $_SESSION['user_id']) {
		set_page_message(tr('User do not exist or you do not have permission to access this interface'), 'error');
		redirectTo('mail_catchall.php');
	}

	$match = array();
	$mailType = $dmnId = $subId = $mailAddr = '';

	if (isset($_POST['uaction']) && $_POST['uaction'] == 'create_catchall' && $_POST['mail_type'] == 'normal') {
		if (preg_match("/^(\d+);(normal|alias|subdom|alssub)$/", $id, $match)) {
			$itemId = $match[1];
			$itemType = $match[2];
			$postMailId = $_POST['mail_id'];

			if (preg_match("/(\d+);([^;]+);/", $postMailId, $match)) {
				$mailId = $match[1];
				$mailAccount = $match[2];

				if ($itemType == 'normal') {
					$mailType = MT_NORMAL_CATCHALL;
				} elseif ($itemType == 'alias') {
					$mailType = MT_ALIAS_CATCHALL;
				} elseif ($itemType == 'subdom') {
					$mailType = MT_SUBDOM_CATCHALL;
				} elseif ($itemType == 'alssub') {
					$mailType = MT_ALSSUB_CATCHALL;
				}

				$query = "SELECT `domain_id`, `sub_id` FROM `mail_users` WHERE `mail_id` = ?";

				$stmt = exec_query($query, $mailId);
				$dmnId = $stmt->fields['domain_id'];
				$subId = $stmt->fields['sub_id'];

				// Find the mail_addr (catchall -> "@(sub/alias)domain.tld", should be domain part of mail_acc
				$match = explode('@', $mailAccount);
				$mailAddr = '@' . $match[1];

				$query = "
					INSERT INTO `mail_users` (
						`mail_acc`, `mail_pass`, `mail_forward`, `domain_id`, `mail_type`, `sub_id`, `status`,
						`mail_auto_respond`, `quota`, `mail_addr`
					) VALUES (
						?, ?, ?, ?, ?, ?, ?, ?, ?, ?
					)
				";
				exec_query(
					$query,
					array(
						$mailAccount, '_no_', '_no_', $dmnId, $mailType, $subId, 'toadd', '_no_', NULL,
						$mailAddr
					)
				);

				send_request();

				write_log("{$_SESSION['user_logged']} added new catch all", E_USER_NOTICE);
				set_page_message(tr('Catch all successfully scheduled for addition.'), 'success');
				redirectTo('mail_catchall.php');
			} else {
				redirectTo('mail_catchall.php');
			}
		}
	} else if (
		isset($_POST['uaction']) && $_POST['uaction'] == 'create_catchall' && $_POST['mail_type'] == 'forward'
		&& isset($_POST['forward_list'])
	) {
		if (preg_match("/^(\d+);(normal|alias|subdom|alssub)$/", $id, $match) == 1) {
			$itemId = $match[1];
			$itemType = $match[2];

			if ($itemType == 'normal') {
				$mailType = MT_NORMAL_CATCHALL;
				$subId = '0';
				$dmnId = $itemId;
				$query = "SELECT `domain_name` FROM `domain` WHERE `domain_id` = ?";
				$stmt = exec_query($query, $dmnId);
				$mailAddr = '@' . $stmt->fields['domain_name'];
			} elseif ($itemType == 'alias') {
				$mailType = MT_ALIAS_CATCHALL;
				$subId = $itemId;
				$query = "SELECT `domain_aliasses`.`domain_id`, `alias_name` FROM `domain_aliasses` WHERE `alias_id` = ?";
				$stmt = exec_query($query, $itemId);
				$dmnId = $stmt->fields['domain_id'];
				$mailAddr = '@' . $stmt->fields['alias_name'];
			} elseif ($itemType == 'subdom') {
				$mailType = MT_SUBDOM_CATCHALL;
				$subId = $itemId;
				$query = "SELECT `subdomain`.`domain_id`, `subdomain_name`, `domain_name` FROM `subdomain`, `domain`
					WHERE `subdomain_id` = ? AND `domain`.`domain_id` = `subdomain`.`domain_id`";
				$stmt = exec_query($query, $itemId);
				$dmnId = $stmt->fields['domain_id'];
				$mailAddr = '@' . $stmt->fields['subdomain_name'] . '.' . $stmt->fields['domain_name'];
			} elseif ($itemType == 'alssub') {
				$mailType = MT_ALSSUB_CATCHALL;
				$subId = $itemId;
				$query = "
					SELECT
						`t1`.`subdomain_alias_name`, `t2`.`alias_name`, `t2`.`domain_id`
					FROM
						`subdomain_alias` AS `t1`, `domain_aliasses` AS `t2`
					WHERE
						`t1`.`subdomain_alias_id` = ?
					AND
						`t1`.`alias_id` = `t2`.`alias_id`
				";
				$stmt = exec_query($query, $itemId);

				$dmnId = $stmt->fields['domain_id'];
				$mailAddr = '@' . $stmt->fields['subdomain_alias_name'] . '.' . $stmt->fields['alias_name'];
			}

			$mailForward = clean_input($_POST['forward_list']);
			$mailAccount = array();
			$faray = preg_split("/[\n,]+/", $mailForward);

			foreach ($faray as $value) {
				$value = trim($value);

				if (!chk_email($value) && $value != '') {
					set_page_message(tr('An error has been found in mail forward list.'), 'error');
					return;
				} else if ($value == '') {
					set_page_message(tr('An error has been found in mail forward list.'), 'error');
					return;
				}

				$mailAccount[] = $value;
			}

			$query = "
				INSERT INTO `mail_users` (
					`mail_acc`, `mail_pass`, `mail_forward`, `domain_id`, `mail_type`, `sub_id`, `status`,
					`mail_auto_respond`, `quota`, `mail_addr`
				) VALUES (
					?, ?, ?, ?, ?, ?, ?, ?, ?, ?
				)
			";
			exec_query(
				$query,
				array(
					implode(',', $mailAccount), '_no_', '_no_', $dmnId, $mailType, $subId, 'toadd',
					'_no_', NULL, $mailAddr
				)
			);

			send_request();

			write_log("{$_SESSION['user_logged']} added new catch all", E_USER_NOTICE);
			set_page_message(tr('Catch all successfully scheduled for addition.'), 'success');
			redirectTo('mail_catchall.php');
		} else {
			redirectTo('mail_catchall.php');
		}
	}
}

/***********************************************************************************************************************
 * Main
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

customerHasFeature('mail') or showBadRequestErrorPage();

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'client/mail_catchall_add.tpl',
		'page_message' => 'layout',
		'mail_list' => 'page',
		'mail_item' => 'mail_list'
	)
);

if (isset($_GET['id'])) {
	$itemId = $_GET['id'];
} else if (isset($_POST['id'])) {
	$itemId = $_POST['id'];
} else {
	redirectTo('mail_catchall.php');
	exit;
}

$tpl->assign(
	array(
		'TR_CLIENT_CREATE_CATCHALL_PAGE_TITLE' => tr('i-MSCP - Client/Create CatchAll Mail Account'),
		'ISP_LOGO' => layout_getUserLogo()
	)
);

client_generatePage($tpl, $itemId);
client_addCatchall($itemId);
$tpl->assign('ID', $itemId);

generateNavigation($tpl);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Client / Email / Catchall / Add Catchall'),
		'TR_MAIL_LIST' => tr('Email account list'),
		'TR_CATCHALL' => tr('Catchall'),
		'TR_ADD' => tr('Add'),
		'TR_CANCEL' => tr('Cancel'),
		'TR_FORWARD_MAIL' => tr('Forward mail'),
		'TR_FORWARD_TO' => tr('Forward to'),
		'TR_FWD_HELP' => tr('Separate multiple email addresses with a line-break.')
	)
);

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
