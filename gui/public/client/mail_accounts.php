<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2014 by i-MSCP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @category    iMSCP
 * @package     Client_Mail
 * @copyright   2010-2014 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

// TODO (nuxwin): DataTables server-side processing

/***********************************************************************************************************************
 * Functions
 */

/**
 * Count the number of email addresses created by default
 *
 * @param int $mainDmnId Main domain id
 * @return int Number of default mails adresses
 */
function _client_countDefaultMails($mainDmnId)
{
	$stmt = exec_query(
		'
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
		',
		array($mainDmnId, 'ok', 'toadd', 'abuse', 'postmaster', 'webmaster')
	);

	return $stmt->fields['cnt'];
}

/**
 * Generate user mail action links
 *
 * @param int $mailId Mail account unique identifier
 * @param string $mailStatus Mail account status
 * @return array
 */
function _client_generateUserMailAction($mailId, $mailStatus)
{
	if ($mailStatus == 'ok') {
		return array(
			tr('Delete'), "mail_delete.php?id=$mailId",
			tr('Edit'), "mail_edit.php?id=$mailId"
		);
	} else {
		return array(tr('N/A'), '#', tr('N/A'), '#');
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
	if ($mailStatus == 'ok') {
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
 * Generate Mail accounts list
 *
 * @param iMSCP_pTemplate $tpl reference to the template object
 * @param int $mainDmnId Customer main domain unique identifier
 * @return int number of subdomain mails addresses
 */
function _client_generateMailAccountsList($tpl, $mainDmnId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$stmt = exec_query(
		"
			SELECT
				`mail_id`, `mail_pass`,
			 	CONCAT(LEFT(`mail_forward`, 30), IF(LENGTH(`mail_forward`) > 30, '...', '')) AS `mail_forward`,
			 	`mail_type`, `status`, `mail_auto_respond`, `quota`, `mail_addr`
			FROM
				`mail_users`
			WHERE
				`domain_id` = ?
			AND
				`mail_type` NOT LIKE '%catchall%'
			ORDER BY
				`mail_addr` ASC, `mail_type` DESC
		",
		$mainDmnId
	);

	$rowCount = $stmt->rowCount();

	if (!$rowCount) {
		return 0;
	} else {
		$mainDmnProps = get_domain_default_props($_SESSION['user_id']);
		$mailQuotaLimit  = ($mainDmnProps['mail_quota']) ? bytesHuman($mainDmnProps['mail_quota']) : 0;

		$imapAvailable = function_exists('imap_open');

		if($imapAvailable) {
			imap_timeout(IMAP_OPENTIMEOUT, 1);
			imap_timeout(IMAP_READTIMEOUT, 2);
			imap_timeout(IMAP_CLOSETIMEOUT, 4);
		}

		$imapTimeoutReached = false;

		while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			list(
				$mailDelete, $mailDeleteScript, $mailEdit, $mailEditScript
			) = _client_generateUserMailAction($row['mail_id'], $row['status']);

			$mailAddr = $row['mail_addr'];
			$mailTypes = explode(',', $row['mail_type']);
			$mailType = '';
			$isMailbox = 0;

			foreach ($mailTypes as $type) {
				$mailType .= user_trans_mail_type($type);

				if (strpos($type, '_forward') !== false) {
					$mailType .= ': ' . str_replace(',', ', ', $row['mail_forward']);
				} else {
					$isMailbox = 1;
				}

				$mailType .= '<br />';
			}

			if ($isMailbox && $row['status'] == 'ok') {
				if ($imapAvailable) {
					$quotaMax = $row['quota'];

					if ($quotaMax) {
						if(
							!$imapTimeoutReached &&
							$imapStream = @imap_open("{localhost/notls}", $mailAddr, $row['mail_pass'], OP_HALFOPEN)
						) {
							$quotaUsage = imap_get_quotaroot($imapStream, 'INBOX');
							imap_close($imapStream);

							if (!empty($quotaUsage)) {
								$quotaUsage = $quotaUsage['usage'] * 1024;
							} else {
								$quotaUsage = 0;
							}

							$quotaMax = bytesHuman($quotaMax);

							$txtQuota = ($mailQuotaLimit) ?
								tr('%s / %s of %s', bytesHuman($quotaUsage), $quotaMax, $mailQuotaLimit)
								: sprintf('%s / %s', bytesHuman($quotaUsage), $quotaMax);
						} else {
							$imapTimeoutReached = true;
							$txtQuota = tr('Info Unavailable');
						}
					} else {
						$txtQuota = tr('unlimited');
					}
				} else {
					$txtQuota = tr('Info Unavailable');
				}
			} else {
				$txtQuota = '---';
			}

			$tpl->assign(
				array(
					'MAIL_ADDR' => tohtml(decode_idna($mailAddr)),
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
 * @return void
 */
function client_generatePage($tpl)
{
	if (customerHasFeature('mail')) {
		/** @var $cfg iMSCP_Config_Handler_File */
		$cfg = iMSCP_Registry::get('config');

		$dmnProps = get_domain_default_props($_SESSION['user_id']);
		$mainDmnId = $dmnProps['domain_id'];
		$dmnMailAccLimit = $dmnProps['domain_mailacc_limit'];

		$countedMails = _client_generateMailAccountsList($tpl, $mainDmnId);;
		$defaultMails = _client_countDefaultMails($mainDmnId);

		if (!$cfg->COUNT_DEFAULT_EMAIL_ADDRESSES) {
			$countedMails -= $defaultMails;
		}

		$totalMails = tr(
			'Total mails: %s / %s %s',
			$countedMails,
			translate_limit_value($dmnMailAccLimit),
			($defaultMails)
				? ($cfg->COUNT_DEFAULT_EMAIL_ADDRESSES)
					? '(' . tr('Incl. default mails') . ')'
					: '(' .  tr('Excl. default mails') . ')'
				: ''
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

/***********************************************************************************************************************
 * Main
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

if (customerHasMailOrExtMailFeatures()) {
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
			'auto_respond_edit_link' => 'auto_respond_item'
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

	client_generatePage($tpl);
	generateNavigation($tpl);
	generatePageMessage($tpl);

	$tpl->parse('LAYOUT_CONTENT', 'page');
	iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));
	$tpl->prnt();

	unsetMessages();
} else {
	showBadRequestErrorPage();
}
