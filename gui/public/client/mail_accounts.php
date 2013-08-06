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
 * Format bytes in human form
 *
 * @param int $bytes
 * @param int $precision
 * @return string
 */
function formatBytes($bytes, $precision = 0)
{
	$units = array('B', 'KB', 'MB', 'GB', 'TB');

	$bytes = max($bytes, 0);
	$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
	$pow = min($pow, count($units) - 1);

	// Uncomment one of the following alternatives
	$bytes /= pow(1024, $pow);

	return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Generate user mail action
 *
 * @param int $mailId mail id
 * @param string $mailStatus mail status
 * @return array
 */
function _client_generateUserMailAction($mailId, $mailStatus)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if ($mailStatus == $cfg->ITEM_OK_STATUS) {
		return array(
			tr('Delete'),
			"mail_delete.php?id=$mailId",
			tr('Edit'),
			"mail_edit.php?id=$mailId",
			tr('Quota'),
			"mail_quota.php?id=$mailId");
	} else {
		return array(tr('N/A'), '#', tr('N/A'), '#', tr('N/A'), '#');
	}
}

/**
 * Generate auto-resonder action links
 *
 * @param iMSCP_pTemplate $tpl pTemplate instance
 * @param int $mailId
 * @param string $mailStatus
 * @param int $mailAutoRespond
 * @return void
 */
function _client_generateUserMailAutoRespond($tpl, $mailId, $mailStatus, $mailAutoRespond)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if ($mailStatus == $cfg->ITEM_OK_STATUS) {
		if ($mailAutoRespond == false) {
			$tpl->assign(
				array(
					'AUTO_RESPOND_DISABLE' => tr('Enable'),
					'AUTO_RESPOND_DISABLE_SCRIPT' =>
					"mail_autoresponder_enable.php?mail_account_id=$mailId",
					'AUTO_RESPOND_EDIT' => tr('N/A'),
					'AUTO_RESPOND_EDIT_SCRIPT' => '#',
					'AUTO_RESPOND_VIS' => 'inline'
				)
			);
		} else {
			$tpl->assign(
				array(
					'AUTO_RESPOND_DISABLE' => tr('Disable'),
					'AUTO_RESPOND_DISABLE_SCRIPT' =>
					"mail_autoresponder_disable.php?mail_account_id=$mailId",
					'AUTO_RESPOND_EDIT' => tr('Edit'),
					'AUTO_RESPOND_EDIT_SCRIPT' =>
					"mail_autoresponder_edit.php?mail_account_id=$mailId",
					'AUTO_RESPOND_VIS' => 'inline'
				)
			);
		}
	} else {
		$tpl->assign(
			array(
				'AUTO_RESPOND_DISABLE' => tr('Please wait for update'),
				'AUTO_RESPOND_DISABLE_SCRIPT' => '#',
				'AUTO_RESPOND_EDIT' => tr('N/A'),
				'AUTO_RESPOND_EDIT_SCRIPT' => '#',
				'AUTO_RESPOND_VIS' => 'inline'
			)
		);
	}
}

/**
 * Generate domain mail list
 *
 * @param iMSCP_pTemplate $tpl reference to pTemplate object
 * @param int $dmnId domain name id
 * @param string $dmnName domain name
 * @return int number of domain mails adresses
 */
function _client_generateDmnMailList($tpl, $dmnId, $dmnName)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$dmnQuery = "
		SELECT
			`mail_id`, `mail_acc`, `mail_type`, `status`, `mail_auto_respond`,
			CONCAT(LEFT(`mail_forward`, 20), IF( LENGTH(`mail_forward`) > 20, '...', '')) AS `mail_forward`
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
		$dmnQuery .= "
			AND
				`mail_acc` != 'abuse'
			AND
				`mail_acc` != 'postmaster'
			AND
				`mail_acc` != 'webmaster'
		";
	}

	$dmnQuery .= "ORDER BY `mail_acc` ASC, `mail_type` DESC";

	$stmt = exec_query($dmnQuery, $dmnId);

	if (!$stmt->rowCount()) {
		return 0;
	} else {
		while (!$stmt->EOF) {
			list(
				$mailDelete, $mailDeleteScript, $mailEdit, $mailEditScript, $mailQuota, $mailQuotaScript
				) = _client_generateUserMailAction(
				$stmt->fields['mail_id'], $stmt->fields['status']
			);

			$mailAcc = decode_idna($stmt->fields['mail_acc']);
			$showDmnName = decode_idna($dmnName);
			$mailTypes = explode(',', $stmt->fields['mail_type']);
			$mailType = '';
			$isMailbox = 0;

			foreach ($mailTypes as $type) {
				$mailType .= user_trans_mail_type($type);

				if (strpos($type, '_forward') !== false) {
					$mailType .= ': ' . str_replace(array("\r\n", "\n", "\r"), ", ", $stmt->fields['mail_forward']);
				} else {
					$isMailbox = 1;
				}

				$mailType .= '<br />';
			}

			$textQuota = "---";
			$localeinfo = localeconv();

			if ($isMailbox) {
				$complete_email = $mailAcc . '@' . $showDmnName;

				$quota_query = "
					SELECT
						`bytes`, `quota`
				 	FROM
						`mail_users`
					LEFT JOIN
						`quota_dovecot`
					ON
						`mail_users`.`mail_addr` = `quota_dovecot`.`username`
					WHERE
						`mail_addr` = ?
				";

				$stmtQuota = exec_query($quota_query, array($complete_email));
				$userQuotaMax = $stmtQuota->fields['quota'];
				$userQuota = $stmtQuota->fields['bytes'];

				if (is_null($userQuota) || ($userQuota < 0)) {
					$userQuota = 0;
				}

				if ($userQuotaMax == 0) {
					$userQuotaMax = tr('unlimited');
					$userQuotaPercent = "0" . $localeinfo['decimal_point'] . "000";
				} else {
					$userQuotaPercent = number_format(
						(($userQuota / $userQuotaMax) * 100), 3, $localeinfo['decimal_point'], ''
					);
					$userQuotaMax = formatBytes($userQuotaMax);
				}

				$userQuota = formatBytes($userQuota);
				$textQuota = $userQuota . " / " . $userQuotaMax . "<br/>" . $userQuotaPercent . " %";
			}

			$tpl->assign(
				array(
					'MAIL_ACC' => tohtml($mailAcc . '@' . $showDmnName),
					'MAIL_TYPE' => $mailType,
					'MAIL_STATUS' => translate_dmn_status($stmt->fields['status']),
					'MAIL_DELETE' => $mailDelete,
					'MAIL_DELETE_SCRIPT' => $mailDeleteScript,
					'MAIL_EDIT' => $mailEdit,
					'MAIL_EDIT_SCRIPT' => $mailEditScript,
					'MAIL_QUOTA' => $mailQuota,
					'MAIL_QUOTA_SCRIPT' => $mailQuotaScript,
					'MAIL_QUOTA_VALUE' => $textQuota,
					'DEL_ITEM' => $stmt->fields['mail_id'],
					'DISABLED_DEL_ITEM' => ($stmt->fields['status'] != 'ok') ? $cfg->HTML_DISABLED : ''
				)
			);

			_client_generateUserMailAutoRespond(
				$tpl, $stmt->fields['mail_id'], $stmt->fields['status'], $stmt->fields['mail_auto_respond']
			);

			$tpl->parse('MAIL_ITEM', '.mail_item');
			$stmt->moveNext();
		}

		return $stmt->rowCount();
	}
}

/**
 * Generate subdomain mail list
 *
 * @param iMSCP_pTemplate $tpl reference to the template object
 * @param int $dmnId domain name id
 * @param string $dmnName domain name
 * @return int number of subdomain mails addresses
 */
function _client_generateSubMailList($tpl, $dmnId, $dmnName)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$subQuery = "
		SELECT
			`t1`.`subdomain_id` AS `sub_id`, `t1`.`subdomain_name` AS `sub_name`, `t2`.`mail_id`, `t2`.`mail_acc`,
			`t2`.`mail_type`, `t2`.`status`, t2.`mail_auto_respond`,
			CONCAT( LEFT(`t2`.`mail_forward`, 20), IF(LENGTH(`t2`.`mail_forward`) > 20, '...', '')) AS `mail_forward`
		FROM
			`subdomain` AS `t1`, `mail_users` AS `t2`
		WHERE
			`t1`.`domain_id` = ?
		AND
			`t2`.`domain_id` = ?
		AND
			(
				`t2`.`mail_type` LIKE '%" . MT_SUBDOM_MAIL . "%'
			OR
				`t2`.`mail_type` LIKE '%" . MT_SUBDOM_FORWARD . "%'
			)
		AND
			`t1`.`subdomain_id` = `t2`.`sub_id`
	";

	if (!isset($_POST['uaction']) || $_POST['uaction'] == 'hide') {
		$subQuery .= "
			AND
				`mail_acc` != 'abuse'
			AND
				`mail_acc` != 'postmaster'
			AND
				`mail_acc` != 'webmaster'
		";
	}

	$subQuery .= "ORDER BY t2.`mail_acc` ASC, t2.`mail_type` DESC";
	$stmt = exec_query($subQuery, array($dmnId, $dmnId));

	if (!$stmt->rowCount()) {
		return 0;
	} else {
		while (!$stmt->EOF) {
			list(
				$mailDelete, $mailDeleteScript, $mailEdit, $mailEditScript, $mailQuota, $mailQuotaScript
				) = _client_generateUserMailAction(
				$stmt->fields['mail_id'], $stmt->fields['status']
			);

			$mailAcc = decode_idna($stmt->fields['mail_acc']);
			$showSubName = decode_idna($stmt->fields['sub_name']);
			$showDmnName = decode_idna($dmnName);
			$mailTypes = explode(',', $stmt->fields['mail_type']);
			$mailType = '';

			$isMailbox = 0;

			foreach ($mailTypes as $type) {
				$mailType .= user_trans_mail_type($type);

				if (strpos($type, '_forward') !== false) {
					$mailType .= ': ' . str_replace(array("\r\n", "\n", "\r"), ", ", $stmt->fields['mail_forward']);
				} else {
					$isMailbox = 1;
				}

				$mailType .= '<br />';
			}

			$txtQuota = "---";
			$localeInfo = localeconv();

			if ($isMailbox) {
				$completeEmail = $mailAcc . '@' . $showSubName . '.' . $showDmnName;

				$quota_query = "
					SELECT
						`bytes`, `quota`
				 	FROM
						`mail_users`
					LEFT JOIN
						`quota_dovecot`
					ON
						`mail_users`.`mail_addr` = `quota_dovecot`.`username`
					WHERE
						`mail_addr` = ?
				";

				$stmtQuota = exec_query($quota_query, array($completeEmail));
				$userQuotaMax = $stmtQuota->fields['quota'];
				$userQuota = $stmtQuota->fields['bytes'];

				if (is_null($userQuota) || ($userQuota < 0)) {
					$userQuota = 0;
				}

				if ($userQuotaMax == 0) {
					$userQuotaMax = tr('unlimited');
					$userQuotaPercent = "0" . $localeInfo['decimal_point'] . "000";
				} else {
					$userQuotaPercent = number_format(
						(($userQuota / $userQuotaMax) * 100), 3, $localeInfo['decimal_point'], ''
					);
					$userQuotaMax = formatBytes($userQuotaMax);
				}

				$userQuota = formatBytes($userQuota);
				$txtQuota = $userQuota . " / " . $userQuotaMax . "<br/>" . $userQuotaPercent . " %";
			}

			$tpl->assign(
				array(
					'MAIL_ACC' =>
					tohtml($mailAcc . '@' . $showSubName . '.' . $showDmnName),
					'MAIL_TYPE' => $mailType,
					'MAIL_STATUS' => translate_dmn_status($stmt->fields['status']),
					'MAIL_DELETE' => $mailDelete,
					'MAIL_DELETE_SCRIPT' => $mailDeleteScript,
					'MAIL_EDIT' => $mailEdit,
					'MAIL_EDIT_SCRIPT' => $mailEditScript,
					'MAIL_QUOTA' => $mailQuota,
					'MAIL_QUOTA_SCRIPT' => $mailQuotaScript,
					'MAIL_QUOTA_VALUE' => $txtQuota,
					'DEL_ITEM' => $stmt->fields['mail_id'],
					'DISABLED_DEL_ITEM' => ($stmt->fields['status'] != 'ok') ? $cfg->HTML_DISABLED : ''
				)
			);

			_client_generateUserMailAutoRespond(
				$tpl, $stmt->fields['mail_id'], $stmt->fields['status'], $stmt->fields['mail_auto_respond']
			);

			$tpl->parse('MAIL_ITEM', '.mail_item');
			$stmt->moveNext();
		}

		return $stmt->rowCount();
	}
}

/**
 * Generate subdomain alias mail list
 *
 * @param iMSCP_pTemplate $tpl reference to the pTemplate object
 * @param int $dmnId domain name id
 * @return int number of subdomain alias mails addresses
 */
function _client_generateAlssubMailList($tpl, $dmnId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$subQuery = "
		SELECT
			`t1`.`mail_id`, `t1`.`mail_acc`, `t1`.`mail_type`, `t1`.`status`, `t1`.`mail_auto_respond`,
			CONCAT(LEFT(`t1`.`mail_forward`, 20), IF(LENGTH(`t1`.`mail_forward`) > 20, '...', '') ) AS `mail_forward`,
			CONCAT(`t2`.`subdomain_alias_name`, '.', `t3`.`alias_name`) AS `alssub_name`
		FROM
			`mail_users` AS `t1`
		LEFT JOIN
			`subdomain_alias` AS `t2` ON (`t1`.`sub_id` = `t2`.`subdomain_alias_id`)
		LEFT JOIN
			`domain_aliasses` AS `t3` ON (`t2`.`alias_id` = `t3`.`alias_id`)
		WHERE
			`t1`.`domain_id` = ?
		AND
			(
				`t1`.`mail_type` LIKE '%" . MT_ALSSUB_MAIL . "%'
			OR
				`t1`.`mail_type` LIKE '%" . MT_ALSSUB_FORWARD . "%'
			)
	";

	if (!isset($_POST['uaction']) || $_POST['uaction'] == 'hide') {
		$subQuery .= "
			AND
				`mail_acc` != 'abuse'
			AND
				`mail_acc` != 'postmaster'
			AND
				`mail_acc` != 'webmaster'
		";
	}

	$subQuery .= "ORDER BY `t1`.`mail_acc` ASC, `t1`.`mail_type` DESC";
	$stmt = exec_query($subQuery, $dmnId);

	if (!$stmt->rowCount()) {
		return 0;
	} else {
		while (!$stmt->EOF) {
			list(
				$mailDelete, $mailDeleteScript, $mailEdit, $mailEditScript, $mailQuota, $mailQuotaScript
				) = _client_generateUserMailAction(
				$stmt->fields['mail_id'], $stmt->fields['status']
			);

			$mailAcc = decode_idna($stmt->fields['mail_acc']);
			$showAlssubName = decode_idna($stmt->fields['alssub_name']);
			$mailTypes = explode(',', $stmt->fields['mail_type']);
			$mailType = '';
			$isMailbox = 0;

			foreach ($mailTypes as $type) {
				$mailType .= user_trans_mail_type($type);

				if (strpos($type, '_forward') !== false) {
					$mailType .= ': ' . str_replace(array("\r\n", "\n", "\r"), ", ", $stmt->fields['mail_forward']);
				} else {
					$isMailbox = 1;
				}

				$mailType .= '<br />';
			}

			$txtQuota = "---";
			$localeInfo = localeconv();

			if ($isMailbox) {
				$completeMail = $mailAcc . '@' . $showAlssubName;

				$quotaQuery = "
					SELECT
						`bytes`, `quota`
				 	FROM
						`mail_users`
					LEFT JOIN
						`quota_dovecot`
					ON
						`mail_users`.`mail_addr` = `quota_dovecot`.`username`
					WHERE
						`mail_addr` = ?
				";

				$stmtQuota = exec_query($quotaQuery, array($completeMail));
				$userQuotaMax = $stmtQuota->fields['quota'];
				$userQuota = $stmtQuota->fields['bytes'];

				if (is_null($userQuota) || ($userQuota < 0)) {
					$userQuota = 0;
				}

				if ($userQuotaMax == 0) {
					$userQuotaMax = tr('unlimited');
					$userQuotaPercent = "0" . $localeInfo['decimal_point'] . "000";
				} else {
					$userQuotaPercent = number_format(
						(($userQuota / $userQuotaMax) * 100), 3, $localeInfo['decimal_point'], ''
					);
					$userQuotaMax = formatBytes($userQuotaMax);
				}

				$userQuota = formatBytes($userQuota);
				$txtQuota = $userQuota . " / " . $userQuotaMax . "<br/>" . $userQuotaPercent . " %";
			}

			$tpl->assign(
				array(
					'MAIL_ACC' => tohtml($mailAcc . '@' . $showAlssubName),
					'MAIL_TYPE' => $mailType,
					'MAIL_STATUS' => translate_dmn_status($stmt->fields['status']),
					'MAIL_DELETE' => $mailDelete,
					'MAIL_DELETE_SCRIPT' => $mailDeleteScript,
					'MAIL_EDIT' => $mailEdit,
					'MAIL_EDIT_SCRIPT' => $mailEditScript,
					'MAIL_QUOTA' => $mailQuota,
					'MAIL_QUOTA_SCRIPT' => $mailQuotaScript,
					'MAIL_QUOTA_VALUE' => $txtQuota,
					'DEL_ITEM' => $stmt->fields['mail_id'],
					'DISABLED_DEL_ITEM' => ($stmt->fields['status'] != 'ok') ? $cfg->HTML_DISABLED : ''
				)
			);

			_client_generateUserMailAutoRespond(
				$tpl, $stmt->fields['mail_id'], $stmt->fields['status'], $stmt->fields['mail_auto_respond']
			);

			$tpl->parse('MAIL_ITEM', '.mail_item');
			$stmt->moveNext();
		}

		return $stmt->rowCount();
	}
}

/**
 * Generate domain aliases mail list
 *
 * @param iMSCP_pTemplate $tpl reference to pTemplate object
 * @param int $dmnId domain name id;
 * @return int number of domain alias mails addresses
 */
function _client_generateAlsMailList($tpl, $dmnId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$alsQuery = "
		SELECT
			`t1`.`alias_id` AS `als_id`, `t1`.`alias_name` AS `als_name`, `t2`.`mail_id`, `t2`.`mail_acc`,
			`t2`.`mail_type`, `t2`.`status`, `t2`.`mail_auto_respond`,
			CONCAT(LEFT(t2.`mail_forward`, 20), IF( LENGTH(t2.`mail_forward`) > 20, '...', '')) AS `mail_forward`
		FROM
			`domain_aliasses` AS `t1`, `mail_users` AS `t2`
		WHERE
			`t1`.`domain_id` = ?
		AND
			`t2`.`domain_id` = ?
		AND
			`t1`.`alias_id` = t2.`sub_id`
		AND
			(
				`t2`.`mail_type` LIKE '%" . MT_ALIAS_MAIL . "%'
			OR
				`t2`.`mail_type` LIKE '%" . MT_ALIAS_FORWARD . "%'
			)
	";

	if (!isset($_POST['uaction']) || $_POST['uaction'] == 'hide') {
		$alsQuery .= "
			AND
				`mail_acc` != 'abuse'
			AND
				`mail_acc` != 'postmaster'
			AND
				`mail_acc` != 'webmaster'
		";
	}

	$alsQuery .= "ORDER BY t2.`mail_acc` ASC, t2.`mail_type` DESC";
	$stmt = exec_query($alsQuery, array($dmnId, $dmnId));

	if (!$stmt->rowCount()) {
		return 0;
	} else {
		while (!$stmt->EOF) {
			list(
				$mailDelete, $mailDeleteScript, $mailEdit, $mailEditScript, $mailQuota, $mailQuotaScript
				) = _client_generateUserMailAction(
				$stmt->fields['mail_id'], $stmt->fields['status']
			);

			$mailAcc = decode_idna($stmt->fields['mail_acc']);
			$showAlsName = decode_idna($stmt->fields['als_name']);
			$mailTypes = explode(',', $stmt->fields['mail_type']);
			$mailType = '';
			$isMailbox = 0;

			foreach ($mailTypes as $type) {
				$mailType .= user_trans_mail_type($type);

				if (strpos($type, '_forward') !== false) {
					$mailType .= ': ' . str_replace(array("\r\n", "\n", "\r"), ", ", $stmt->fields['mail_forward']);
				} else {
					$isMailbox = 1;
				}

				$mailType .= '<br />';
			}

			$txtQuota = "---";
			$localeInfo = localeconv();

			if ($isMailbox) {
				$completeMail = $mailAcc . '@' . $showAlsName;
				$quotaQuery = "
					SELECT
						`bytes`, `quota`
				 	FROM
						`mail_users`
					LEFT JOIN
						`quota_dovecot`
					ON
						`mail_users`.`mail_addr` = `quota_dovecot`.`username`
					WHERE
						`mail_addr` = ?";

				$stmtQuota = exec_query($quotaQuery, array($completeMail));
				$userQuotaMax = $stmtQuota->fields['quota'];
				$userQuota = $stmtQuota->fields['bytes'];

				if (is_null($userQuota) || ($userQuota < 0)) {
					$userQuota = 0;
				}

				if ($userQuotaMax == 0) {
					$userQuotaMax = tr('unlimited');
					$userquotapercent = "0" . $localeInfo['decimal_point'] . "000";
				} else {
					$userquotapercent = number_format((($userQuota / $userQuotaMax) * 100), 3, $localeInfo['decimal_point'], '');
					$userQuotaMax = formatBytes($userQuotaMax);
				}

				$userQuota = formatBytes($userQuota);
				$txtQuota = $userQuota . " / " . $userQuotaMax . "<br/>" . $userquotapercent . " %";
			}

			$tpl->assign(
				array(
					'MAIL_ACC' => tohtml($mailAcc . '@' . $showAlsName),
					'MAIL_TYPE' => $mailType,
					'MAIL_STATUS' => translate_dmn_status($stmt->fields['status']),
					'MAIL_DELETE' => $mailDelete,
					'MAIL_DELETE_SCRIPT' => $mailDeleteScript,
					'MAIL_EDIT' => $mailEdit,
					'MAIL_EDIT_SCRIPT' => $mailEditScript,
					'MAIL_QUOTA' => $mailQuota,
					'MAIL_QUOTA_SCRIPT' => $mailQuotaScript,
					'MAIL_QUOTA_VALUE' => $txtQuota,
					'DEL_ITEM' => $stmt->fields['mail_id'],
					'DISABLED_DEL_ITEM' => ($stmt->fields['status'] != 'ok') ? $cfg->HTML_DISABLED : ''
				)
			);

			_client_generateUserMailAutoRespond(
				$tpl, $stmt->fields['mail_id'], $stmt->fields['status'], $stmt->fields['mail_auto_respond']
			);

			$tpl->parse('MAIL_ITEM', '.mail_item');
			$stmt->moveNext();
		}

		return $stmt->rowCount();
	}
}

/**
 * Must be documented
 *
 * @param iMSCP_pTemplate $tpl Reference to the pTemplate object
 * @param int $userId Customer id
 * @return void
 */
function client_generatePage($tpl, $userId)
{
	if (customerHasFeature('mail')) {
		/** @var $cfg iMSCP_Config_Handler_File */
		$cfg = iMSCP_Registry::get('config');

		$domainProps = get_domain_default_props($userId);
		$dmnId = $domainProps['domain_id'];
		$dmnName = $domainProps['domain_name'];
		$dmnMailAccLimit = $domainProps['domain_mailacc_limit'];

		$dmnMails = _client_generateDmnMailList($tpl, $dmnId, $dmnName);
		$subMails = _client_generateSubMailList($tpl, $dmnId, $dmnName);
		$alssubMails = _client_generateAlssubMailList($tpl, $dmnId);
		$alsMails = _client_generateAlsMailList($tpl, $dmnId);

		// If 'uaction' is set and own value is != 'hide', the total includes
		// the number of email by default
		$countedMails = $totalMails = $dmnMails + $subMails + $alsMails + $alssubMails;
		$defaultMails = _client_countDefaultMails($dmnId);

		if ($cfg->COUNT_DEFAULT_EMAIL_ADDRESSES == 0) {
			if (isset($_POST['uaction']) && $_POST['uaction'] == 'show') {
				$countedMails -= $defaultMails;
			}
		} elseif (!isset($_POST['uaction']) || $_POST['uaction'] == 'hide') {
			$countedMails += $defaultMails;
		}

		if ($totalMails > 0) {
			$tpl->assign(
				array(
					'DMN_TOTAL' => $dmnMails,
					'SUB_TOTAL' => $subMails,
					'ALSSUB_TOTAL' => $subMails,
					'ALS_TOTAL' => $alsMails,
					'TOTAL_MAIL_ACCOUNTS' => $countedMails,
					'ALLOWED_MAIL_ACCOUNTS' => ($dmnMailAccLimit != 0) ? $dmnMailAccLimit : tr('unlimited'),
					'TR_DELETE_MARKED_MAILS' => tr('Delete marked mails')
				)
			);

			$tpl->parse('MARK_ALL_MAILS_TO_DELETE_JQUERY', 'mark_all_mails_to_delete_jquery');
			$tpl->parse('MARK_ALL_MAILS_TO_DELETE', 'mark_all_mails_to_delete');
			$tpl->parse('DELETE_MARKED_MAILS_FORM_HEAD', 'delete_marked_mails_form_head');
			$tpl->parse('DELETE_MARKED_MAILS_FORM_BOTTOM', 'delete_marked_mails_form_bottom');
		} else {
			if (!isset($_POST['uaction']) || $_POST['uaction'] == 'hide') {
				$tpl->assign('TABLE_LIST', '');
			}

			$tpl->assign(
				array(
					'MAIL_ITEM' => '', 'MAILS_TOTAL' => '',
					'DEL_ITEM' => '',
					'MARK_ALL_MAILS_TO_DELETE' => '',
					'TR_DELETE_MARKED_MAILS' => '',
					'DELETE_MARKED_MAILS_FORM_HEAD' => '', 'DELETE_MARKED_MAILS_FORM_BOTTOM' => '',
					'MARK_ALL_MAILS_TO_DELETE_JQUERY' => ''
				)
			);

			set_page_message(tr('Mail accounts list is empty.'), 'info');
		}
	} else {
		$tpl->assign('MAIL_FEATURE', '');
		set_page_message(tr('Mail feature is disabled.'), 'info');
	}
}

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
 * @param int $dmnId Domain name id
 * @return int Number of default mails adresses
 */
function _client_countDefaultMails($dmnId)
{
	static $countDefaultMails = null;

	if (null === $countDefaultMails) {
		$query = "
			SELECT
				COUNT(`mail_id`) AS `cnt`
			FROM
				`mail_users`
			WHERE
				`domain_id` = ?
			AND
				(`status` = 'ok' OR `status` = 'toadd')
			AND
				(`mail_acc` = 'abuse' OR `mail_acc` = 'postmaster' OR `mail_acc` = 'webmaster')
		";

		$stmt = exec_query($query, $dmnId);
		$countDefaultMails = $stmt->fields['cnt'];
	}

	return $countDefaultMails;
}

/***********************************************************************************************************************
 * Main
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

if (customerHasMailOrExtMailFeatures()) {

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$tpl = new iMSCP_pTemplate();
	$tpl->define_dynamic(
		array(
			'layout' => 'shared/layouts/ui.tpl',
			'page' => 'client/mail_accounts.tpl',
			'page_message' => 'layout',
			'mail_feature' => 'page',
			'mark_all_mails_to_delete_jquery' => 'mail_feature',
			'delete_marked_mails_form_head' => 'mail_feature',
			'mark_all_mails_to_delete' => 'mail_feature',
			'mail_item' => 'mail_feature',
			'auto_respond' => 'mail_item',
			'mails_total' => 'mail_feature',
			'delete_marked_mails_form_bottom' => 'mail_feature',
			'default_mails_form' => 'mail_feature'
		)
	);

	$tpl->assign(
		array(
			'TR_PAGE_TITLE' => tr('Client / Email / Overview'),
			'THEME_CHARSET' => tr('encoding'),
			'ISP_LOGO' => layout_getUserLogo(),
			'TR_MAIL' => tr('Mail'),
			'TR_DEL_ITEM' => tr('Mark all'),
			'TR_TYPE' => tr('Type'),
			'TR_STATUS' => tr('Status'),
			'TR_QUOTA' => tr('Quota'),
			'TR_ACTIONS' => tr('Actions'),
			'TR_AUTORESPOND' => tr('Auto respond'),
			'TR_TOTAL_MAIL_ACCOUNTS' => tr('Mails total'),
			'TR_DELETE' => tr('Delete'),
			'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete %s?', true, '%s'),
			'TR_MESSAGE_DELETE_MARKED' => tr('Are you sure you want to delete all marked emails?', true),
		)
	);

	global $userId;
	$userId = $_SESSION['user_id'];

	client_generatePage($tpl, $userId);
	generateNavigation($tpl);

	if (customerHasFeature('mail')) {
		// Displays the "show/hide" button for default emails only if default mail address exists
		if (_client_countDefaultMails($userId) > 0) {
			$tpl->assign(
				array(
					'TR_DEFAULT_EMAILS_BUTTON' => (!isset($_POST['uaction']) || $_POST['uaction'] != 'show')
						? tr('Show default E-Mail addresses') : tr('Hide default E-Mail Addresses'),
					'VL_DEFAULT_EMAILS_BUTTON' => (isset($_POST['uaction']) && $_POST['uaction'] == 'show') ? 'hide' : 'show'
				)
			);
		} else {
			$tpl->assign(array('DEFAULT_MAILS_FORM' => ''));
		}
	}

	generatePageMessage($tpl);

	$tpl->parse('LAYOUT_CONTENT', 'page');

	iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

	$tpl->prnt();

	unsetMessages();
} else {
	showBadRequestErrorPage();
}
