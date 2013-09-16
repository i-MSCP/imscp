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
			tr('Delete'), "mail_delete.php?id=$mailId",
			tr('Edit'), "mail_edit.php?id=$mailId",
			tr('Quota'), "mail_quota.php?id=$mailId");
	} else {
		return array(tr('N/A'), '#', tr('N/A'), '#', tr('N/A'), '#');
	}
}

/**
 * Generate auto-resonder action links
 *
 * @param iMSCP_pTemplate $tpl pTemplate instance
 * @param int $mailId Mail uique identifier
 * @param string $mailStatus Mail status
 * @param bool $mailAutoRespond
 * @return void
 */
function _client_generateUserMailAutoRespond($tpl, $mailId, $mailStatus, $mailAutoRespond)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if ($mailStatus == $cfg->ITEM_OK_STATUS) {
		if (!$mailAutoRespond) {
			$tpl->assign(
				array(
					'AUTO_RESPOND' => tr('Enable'),
					'AUTO_RESPOND_SCRIPT' =>
					"mail_autoresponder_enable.php?mail_account_id=$mailId",
					'AUTO_RESPOND_EDIT_LINK' => ''
				)
			);
		} else {
			$tpl->assign(
				array(
					'AUTO_RESPOND' => tr('Disable'),
					'AUTO_RESPOND_SCRIPT' =>
					"mail_autoresponder_disable.php?mail_account_id=$mailId",
					'AUTO_RESPOND_EDIT' => tr('Edit'),
					'AUTO_RESPOND_EDIT_SCRIPT' =>
					"mail_autoresponder_edit.php?mail_account_id=$mailId",
				)
			);

			$tpl->parse('AUTO_RESPOND_EDIT_LINK', 'auto_respond_edit_link');
		}
		$tpl->parse('AUTO_RESPOND_ITEM', 'auto_respond_item');
	} else {
		$tpl->assign('AUTO_RESPOND_ITEM', '');
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

	$query = "
		SELECT
			`mail_id`, `mail_acc`, `mail_type`,
			`status`, `mail_auto_respond`,
			CONCAT(LEFT(`mail_forward`, 30), IF( LENGTH(`mail_forward`) > 30, '...', '')) AS `mail_forward`
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
		ORDER BY
			`mail_acc` ASC, `mail_type` DESC
	";
	$stmt = exec_query($query, $dmnId);

	$rowCount = $stmt->rowCount();

	if (!$rowCount) {
		return 0;
	} else {
		while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			list(
				$mailDelete, $mailDeleteScript, $mailEdit, $mailEditScript, $mailQuota, $mailQuotaScript
				) = _client_generateUserMailAction($row['mail_id'], $row['status']);

			$mailAcc = decode_idna($row['mail_acc']);
			$showDmnName = decode_idna($dmnName);
			$mailTypes = explode(',', $row['mail_type']);
			$mailType = '';
			$isMailbox = 0;

			foreach ($mailTypes as $type) {
				$mailType .= user_trans_mail_type($type);

				if (strpos($type, '_forward') !== false) {
					$mailType .= ': ' . str_replace(array("\r\n", "\n", "\r"), ', ', $row['mail_forward']);
				} else {
					$isMailbox = 1;
				}

				$mailType .= '<br />';
			}

			$textQuota = '---';
			$localeinfo = localeconv();

			if ($isMailbox) {
				$complete_email = $mailAcc . '@' . $showDmnName;

				$quota_query = '
					SELECT
						`bytes`, `quota`
				 	FROM
						`mail_users`
					LEFT JOIN
						`quota_dovecot` ON (`mail_users`.`mail_addr` = `quota_dovecot`.`username`)
					WHERE
						`mail_addr` = ?
				';
				$stmtQuota = exec_query($quota_query, array($complete_email));

				$userQuotaMax = $stmtQuota->fields['quota'];
				$userQuota = $stmtQuota->fields['bytes'];

				if (is_null($userQuota) || ($userQuota < 0)) {
					$userQuota = 0;
				}

				if ($userQuotaMax == 0) {
					$userQuotaMax = tr('unlimited');
					$userQuotaPercent = '0' . $localeinfo['decimal_point'] . '00';
				} else {
					$userQuotaPercent = number_format(
						(($userQuota / $userQuotaMax) * 100), 2, $localeinfo['decimal_point'], ''
					);

					$userQuotaMax = bytesHuman($userQuotaMax, null, 0);
				}

				$userQuota = bytesHuman($userQuota, null, 0);
				$textQuota = $userQuota . ' / ' . $userQuotaMax . ' (' . $userQuotaPercent . '%)';

				$tpl->assign(
					array(
						'MAIL_QUOTA' => $mailQuota,
						'MAIL_QUOTA_SCRIPT' => $mailQuotaScript,
					)
				);

				$tpl->parse('QUOTA_LINK', 'quota_link');
			} else {
				$tpl->assign('QUOTA_LINK', '');
			}

			$tpl->assign(
				array(
					'MAIL_ACC' => tohtml($mailAcc . '@' . $showDmnName),
					'MAIL_TYPE' => $mailType,
					'MAIL_STATUS' => translate_dmn_status($row['status']),
					'MAIL_DELETE' => $mailDelete,
					'MAIL_DELETE_SCRIPT' => $mailDeleteScript,
					'MAIL_EDIT' => $mailEdit,
					'MAIL_EDIT_SCRIPT' => $mailEditScript,
					'MAIL_QUOTA_VALUE' => $textQuota,
					'DEL_ITEM' => $row['mail_id'],
					'DISABLED_DEL_ITEM' => ($row['status'] != 'ok') ? $cfg->HTML_DISABLED : ''
				)
			);

			_client_generateUserMailAutoRespond($tpl, $row['mail_id'], $row['status'], $row['mail_auto_respond']);

			$tpl->parse('MAIL_ITEM', '.mail_item');
		}

		return $rowCount;
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

	$query = "
		SELECT
			`t1`.`subdomain_id` AS `sub_id`,
			`t1`.`subdomain_name` AS `sub_name`,
			`t2`.`mail_id`, `t2`.`mail_acc`,
			`t2`.`mail_type`, `t2`.`status`,
			`t2`.`mail_auto_respond`,
			CONCAT( LEFT(`t2`.`mail_forward`, 30), IF(LENGTH(`t2`.`mail_forward`) > 30, '...', '')) AS `mail_forward`
		FROM
			`subdomain` AS `t1`, `mail_users` AS `t2`
		WHERE
			`t1`.`domain_id` = :dmnId
		AND
			`t2`.`domain_id` = :dmnId
		AND
			(
				`t2`.`mail_type` LIKE '%" . MT_SUBDOM_MAIL . "%'
			OR
				`t2`.`mail_type` LIKE '%" . MT_SUBDOM_FORWARD . "%'
			)
		AND
			`t1`.`subdomain_id` = `t2`.`sub_id`
		ORDER BY
			`t2`.`mail_acc` ASC, t2.`mail_type` DESC
	";
	$stmt = exec_query($query, array('dmnId' => $dmnId));

	$rowCount = $stmt->rowCount();

	if (!$rowCount) {
		return 0;
	} else {
		while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			list(
				$mailDelete, $mailDeleteScript, $mailEdit, $mailEditScript, $mailQuota, $mailQuotaScript
			) = _client_generateUserMailAction($row['mail_id'], $row['status']);

			$mailAcc = decode_idna($row['mail_acc']);
			$showSubName = decode_idna($row['sub_name']);
			$showDmnName = decode_idna($dmnName);
			$mailTypes = explode(',', $row['mail_type']);
			$mailType = '';

			$isMailbox = 0;

			foreach ($mailTypes as $type) {
				$mailType .= user_trans_mail_type($type);

				if (strpos($type, '_forward') !== false) {
					$mailType .= ': ' . str_replace(array("\r\n", "\n", "\r"), ', ', $row['mail_forward']);
				} else {
					$isMailbox = 1;
				}

				$mailType .= '<br />';
			}

			$txtQuota = '---';
			$localeInfo = localeconv();

			if ($isMailbox) {
				$completeEmail = $mailAcc . '@' . $showSubName . '.' . $showDmnName;

				$quotaQquery = '
					SELECT
						`bytes`, `quota`
				 	FROM
						`mail_users`
					LEFT JOIN
						`quota_dovecot` ON (`mail_users`.`mail_addr` = `quota_dovecot`.`username`)
					WHERE
						`mail_addr` = ?
				';
				$stmtQuota = exec_query($quotaQquery, array($completeEmail));

				$userQuotaMax = $stmtQuota->fields['quota'];
				$userQuota = $stmtQuota->fields['bytes'];

				if (is_null($userQuota) || ($userQuota < 0)) {
					$userQuota = 0;
				}

				if ($userQuotaMax == 0) {
					$userQuotaMax = tr('unlimited');
					$userQuotaPercent = '0' . $localeInfo['decimal_point'] . '00';
				} else {
					$userQuotaPercent = number_format(
						(($userQuota / $userQuotaMax) * 100), 2, $localeInfo['decimal_point'], ''
					);
					$userQuotaMax = bytesHuman($userQuotaMax, null, 0);
				}

				$userQuota = bytesHuman($userQuota, null, 0);
				$txtQuota = $userQuota . ' / ' . $userQuotaMax . ' (' . $userQuotaPercent . '%)';

				$tpl->assign(
					array(
						'MAIL_QUOTA' => $mailQuota,
						'MAIL_QUOTA_SCRIPT' => $mailQuotaScript,
					)
				);

				$tpl->parse('QUOTA_LINK', 'quota_link');
			} else {
				$tpl->assign('QUOTA_LINK', '');
			}

			$tpl->assign(
				array(
					'MAIL_ACC' =>
					tohtml($mailAcc . '@' . $showSubName . '.' . $showDmnName),
					'MAIL_TYPE' => $mailType,
					'MAIL_STATUS' => translate_dmn_status($row['status']),
					'MAIL_DELETE' => $mailDelete,
					'MAIL_DELETE_SCRIPT' => $mailDeleteScript,
					'MAIL_EDIT' => $mailEdit,
					'MAIL_EDIT_SCRIPT' => $mailEditScript,
					'MAIL_QUOTA_VALUE' => $txtQuota,
					'DEL_ITEM' => $row['mail_id'],
					'DISABLED_DEL_ITEM' => ($row['status'] != 'ok') ? $cfg->HTML_DISABLED : ''
				)
			);

			_client_generateUserMailAutoRespond($tpl, $row['mail_id'], $row['status'], $row['mail_auto_respond']);

			$tpl->parse('MAIL_ITEM', '.mail_item');
		}

		return $rowCount;
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

	$query = "
		SELECT
			`t1`.`mail_id`,
			`t1`.`mail_acc`,
			`t1`.`mail_type`,
			`t1`.`status`,
			`t1`.`mail_auto_respond`,
			CONCAT(LEFT(`t1`.`mail_forward`, 30), IF(LENGTH(`t1`.`mail_forward`) > 30, '...', '') ) AS `mail_forward`,
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
		ORDER BY
			`t1`.`mail_acc` ASC, `t1`.`mail_type` DESC
	";
	$stmt = exec_query($query, $dmnId);

	$rowCount = $stmt->rowCount();

	if (!$rowCount) {
		return 0;
	} else {
		while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			list(
				$mailDelete, $mailDeleteScript, $mailEdit, $mailEditScript, $mailQuota, $mailQuotaScript
				) = _client_generateUserMailAction($row['mail_id'], $row['status']);

			$mailAcc = decode_idna($row['mail_acc']);
			$showAlssubName = decode_idna($row['alssub_name']);
			$mailTypes = explode(',', $row['mail_type']);
			$mailType = '';
			$isMailbox = 0;

			foreach ($mailTypes as $type) {
				$mailType .= user_trans_mail_type($type);

				if (strpos($type, '_forward') !== false) {
					$mailType .= ': ' . str_replace(array("\r\n", "\n", "\r"), ', ', $row['mail_forward']);
				} else {
					$isMailbox = 1;
				}

				$mailType .= '<br />';
			}

			$txtQuota = "---";
			$localeInfo = localeconv();

			if ($isMailbox) {
				$completeMail = $mailAcc . '@' . $showAlssubName;

				$quotaQuery = '
					SELECT
						`bytes`, `quota`
				 	FROM
						`mail_users`
					LEFT JOIN
						`quota_dovecot` ON (`mail_users`.`mail_addr` = `quota_dovecot`.`username`)
					WHERE
						`mail_addr` = ?
				';
				$stmtQuota = exec_query($quotaQuery, array($completeMail));

				$userQuotaMax = $stmtQuota->fields['quota'];
				$userQuota = $stmtQuota->fields['bytes'];

				if (is_null($userQuota) || ($userQuota < 0)) {
					$userQuota = 0;
				}

				if ($userQuotaMax == 0) {
					$userQuotaMax = tr('unlimited');
					$userQuotaPercent = '0' . $localeInfo['decimal_point'] . '00';
				} else {
					$userQuotaPercent = number_format(
						(($userQuota / $userQuotaMax) * 100), 2, $localeInfo['decimal_point'], ''
					);
					$userQuotaMax = bytesHuman($userQuotaMax, null, 0);
				}

				$userQuota = bytesHuman($userQuota, null, 0);
				$txtQuota = $userQuota . ' / ' . $userQuotaMax . ' (' . $userQuotaPercent . '%)';

				$tpl->assign(
					array(
						'MAIL_QUOTA' => $mailQuota,
						'MAIL_QUOTA_SCRIPT' => $mailQuotaScript,
					)
				);

				$tpl->parse('QUOTA_LINK', 'quota_link');
			} else {
				$tpl->assign('QUOTA_LINK', '');
			}

			$tpl->assign(
				array(
					'MAIL_ACC' => tohtml($mailAcc . '@' . $showAlssubName),
					'MAIL_TYPE' => $mailType,
					'MAIL_STATUS' => translate_dmn_status($row['status']),
					'MAIL_DELETE' => $mailDelete,
					'MAIL_DELETE_SCRIPT' => $mailDeleteScript,
					'MAIL_EDIT' => $mailEdit,
					'MAIL_EDIT_SCRIPT' => $mailEditScript,
					'MAIL_QUOTA_VALUE' => $txtQuota,
					'DEL_ITEM' => $row['mail_id'],
					'DISABLED_DEL_ITEM' => ($row['status'] != 'ok') ? $cfg->HTML_DISABLED : ''
				)
			);

			_client_generateUserMailAutoRespond($tpl, $row['mail_id'], $row['status'], $row['mail_auto_respond']);

			$tpl->parse('MAIL_ITEM', '.mail_item');
		}

		return $rowCount;
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

	$query = "
		SELECT
			`t1`.`alias_id` AS `als_id`,
			`t1`.`alias_name` AS `als_name`,
			`t2`.`mail_id`, `t2`.`mail_acc`,
			`t2`.`mail_type`, `t2`.`status`,
			`t2`.`mail_auto_respond`,
			CONCAT(LEFT(t2.`mail_forward`, 30), IF( LENGTH(t2.`mail_forward`) > 30, '...', '')) AS `mail_forward`
		FROM
			`domain_aliasses` AS `t1`, `mail_users` AS `t2`
		WHERE
			`t1`.`domain_id` = :dmnId
		AND
			`t2`.`domain_id` = :dmnId
		AND
			`t1`.`alias_id` = t2.`sub_id`
		AND
			(
				`t2`.`mail_type` LIKE '%" . MT_ALIAS_MAIL . "%'
			OR
				`t2`.`mail_type` LIKE '%" . MT_ALIAS_FORWARD . "%'
			)
		ORDER BY
			t2.`mail_acc` ASC, t2.`mail_type` DESC
	";
	$stmt = exec_query($query, array('dmnId' => $dmnId));

	$rowCount = $stmt->rowCount();

	if (!$rowCount) {
		return 0;
	} else {
		while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			list(
				$mailDelete, $mailDeleteScript, $mailEdit, $mailEditScript, $mailQuota, $mailQuotaScript
				) = _client_generateUserMailAction($row['mail_id'], $row['status']);

			$mailAcc = decode_idna($row['mail_acc']);
			$showAlsName = decode_idna($row['als_name']);
			$mailTypes = explode(',', $row['mail_type']);
			$mailType = '';
			$isMailbox = 0;

			foreach ($mailTypes as $type) {
				$mailType .= user_trans_mail_type($type);

				if (strpos($type, '_forward') !== false) {
					$mailType .= ': ' . str_replace(array("\r\n", "\n", "\r"), ', ', $row['mail_forward']);
				} else {
					$isMailbox = 1;
				}

				$mailType .= '<br />';
			}

			$txtQuota = '---';
			$localeInfo = localeconv();

			if ($isMailbox) {
				$completeMail = $mailAcc . '@' . $showAlsName;

				$quotaQuery = '
					SELECT
						`bytes`, `quota`
				 	FROM
						`mail_users`
					LEFT JOIN
						`quota_dovecot` ON (`mail_users`.`mail_addr` = `quota_dovecot`.`username`)
					WHERE
						`mail_addr` = ?
				';
				$stmtQuota = exec_query($quotaQuery, array($completeMail));

				$userQuotaMax = $stmtQuota->fields['quota'];
				$userQuota = $stmtQuota->fields['bytes'];

				if (is_null($userQuota) || ($userQuota < 0)) {
					$userQuota = 0;
				}

				if ($userQuotaMax == 0) {
					$userQuotaMax = tr('unlimited');
					$userquotapercent = '0' . $localeInfo['decimal_point'] . '00';
				} else {
					$userquotapercent = number_format(
						(($userQuota / $userQuotaMax) * 100), 2, $localeInfo['decimal_point'], ''
					);
					$userQuotaMax = bytesHuman($userQuotaMax, null, 0);
				}

				$userQuota = bytesHuman($userQuota, null, 0);
				$txtQuota = $userQuota . ' / ' . $userQuotaMax . ' (' . $userquotapercent . '%)';

				$tpl->assign(
					array(
						'MAIL_QUOTA' => $mailQuota,
						'MAIL_QUOTA_SCRIPT' => $mailQuotaScript,
					)
				);

				$tpl->parse('QUOTA_LINK', 'quota_link');
			} else {
				$tpl->assign('QUOTA_LINK', '');
			}

			$tpl->assign(
				array(
					'MAIL_ACC' => tohtml($mailAcc . '@' . $showAlsName),
					'MAIL_TYPE' => $mailType,
					'MAIL_STATUS' => translate_dmn_status($row['status']),
					'MAIL_DELETE' => $mailDelete,
					'MAIL_DELETE_SCRIPT' => $mailDeleteScript,
					'MAIL_EDIT' => $mailEdit,
					'MAIL_EDIT_SCRIPT' => $mailEditScript,
					'MAIL_QUOTA_VALUE' => $txtQuota,
					'DEL_ITEM' => $row['mail_id'],
					'DISABLED_DEL_ITEM' => ($row['status'] != 'ok') ? $cfg->HTML_DISABLED : ''
				)
			);

			_client_generateUserMailAutoRespond($tpl, $row['mail_id'], $row['status'], $row['mail_auto_respond']);

			$tpl->parse('MAIL_ITEM', '.mail_item');
		}

		return $rowCount;
	}
}

/**
 * Generate page
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

		$dmnProps = get_domain_default_props($userId);
		$dmnId = $dmnProps['domain_id'];
		$dmnName = $dmnProps['domain_name'];
		$dmnMailAccLimit = $dmnProps['domain_mailacc_limit'];

		$dmnMails = _client_generateDmnMailList($tpl, $dmnId, $dmnName);
		$subMails = _client_generateSubMailList($tpl, $dmnId, $dmnName);
		$alssubMails = _client_generateAlssubMailList($tpl, $dmnId);
		$alsMails = _client_generateAlsMailList($tpl, $dmnId);

		$countedMails = $dmnMails + $subMails + $alsMails + $alssubMails;
		$defaultMails = _client_countDefaultMails($dmnId);

		if (!$cfg->COUNT_DEFAULT_EMAIL_ADDRESSES) {
			$countedMails -= $defaultMails;
		}

		$totalMails = tr(
			'Total mails: %s of %s (%s)',
			$countedMails,
			translate_limit_value($dmnMailAccLimit),
			($cfg->COUNT_DEFAULT_EMAIL_ADDRESSES) ? tr('Incl. default mails') : tr('Excl. default mails')
		);

		if ($countedMails || $defaultMails) {
			$tpl->assign('TOTAL_MAIL_ACCOUNTS', $totalMails);
		} else {
			$tpl->assign('MAIL_ITEMS', '');
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
 * @param int $mainDmnId Main domain id
 * @return int Number of default mails adresses
 */
function _client_countDefaultMails($mainDmnId)
{
	/** @var iMSCP_Config_Handler_File $cfg */
	$cfg = iMSCP_Registry::get('config');

	$query = "
		SELECT
			COUNT(`mail_id`) AS `cnt`
		FROM
			`mail_users`
		WHERE
			`domain_id` = ?
		AND
			(`status` = ? OR `status` = ?)
		AND
			(`mail_acc` = ? OR `mail_acc` = ? OR `mail_acc` = ?)
	";
	$stmt = exec_query(
		$query, array($mainDmnId, $cfg->ITEM_OK_STATUS, $cfg->ITEM_TOADD_STATUS, 'abuse', 'postmaster', 'webmaster')
	);

	return $stmt->fields['cnt'];
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
			'mail_items' => 'mail_feature',
			'mail_item' => 'mail_items',
			'auto_respond_item' => 'mail_item',
			'auto_respond_edit_link' =>  'auto_respond_item',
			'quota_link' => 'mail_item'
		)
	);

	$tpl->assign(
		array(
			'TR_PAGE_TITLE' => tr('Client / Email / Overview'),
			'ISP_LOGO' => layout_getUserLogo(),
			'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations(),
			'TR_MAIL' => tr('Mail'),
			'TR_TYPE' => tr('Type'),
			'TR_STATUS' => tr('Status'),
			'TR_QUOTA' => tr('Quota'),
			'TR_ACTIONS' => tr('Actions'),
			'TR_AUTORESPOND' => tr('Auto responder'),
			'TR_DELETE' => tr('Delete'),
			'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete %s?', true, '%s'),
			'TR_MESSAGE_DELETE_SELECTED_ITEMS' => tr('Are you sure you want to delete all selected items?', true),
			'TR_DELETE_SELECTED_ITEMS' => tr('Delete selected items'),
			'TR_MESSAGE_DELETE_SELECTED_ITEMS_ERR' => tr('You must select a least one item to delete')
		)
	);

	global $userId;
	$userId = $_SESSION['user_id'];

	client_generatePage($tpl, $userId);
	generateNavigation($tpl);
	generatePageMessage($tpl);

	$tpl->parse('LAYOUT_CONTENT', 'page');
	iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));
	$tpl->prnt();

	unsetMessages();
} else {
	showBadRequestErrorPage();
}
