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

/***********************************************************************************************************************
 * Functions
 */

/**
 * Get domains list
 *
 * @return array Domains list
 */
function _client_getDomainsList()
{
	static $domainsList = null;

	if (null === $domainsList) {
		$mainDmnProps = get_domain_default_props($_SESSION['user_id']);

		$domainsList = array(
			array(
				'name' => $mainDmnProps['domain_name'],
				'id' => $mainDmnProps['domain_id'],
				'type' => 'dmn'
			)
		);

		$query = "
			SELECT
				CONCAT(`t1`.`subdomain_name`, '.', `t2`.`domain_name`) AS `name`, `t1`.`subdomain_id` AS `id`,
				'sub' AS `type`
			FROM
				`subdomain` AS `t1`
			INNER JOIN
				`domain` AS `t2` USING(`domain_id`)
			WHERE
				`t1`.`domain_id` = :domain_id
			AND
				`t1`.`subdomain_status` = :status_ok
			UNION
			SELECT
				`alias_name` AS `name`, `alias_id` AS `id`, 'als' AS `type`
			FROM
				`domain_aliasses`
			WHERE
				`domain_id` = :domain_id
			AND
				`alias_status` = :status_ok
			UNION
			SELECT
				CONCAT(`t1`.`subdomain_alias_name`, '.', `t2`.`alias_name`) AS `name`, `t1`.`subdomain_alias_id` AS `id`,
				'alssub' AS `type`
			FROM
				`subdomain_alias` AS `t1`
			INNER JOIN
				`domain_aliasses` AS `t2` USING(`alias_id`)
			WHERE
				`t2`.`domain_id` = :domain_id
			AND
				`subdomain_alias_status` = :status_ok
		";
		$stmt = exec_query(
			$query, array('domain_id' => $mainDmnProps['domain_id'], 'status_ok' => 'ok')
		);

		if ($stmt->rowCount()) {
			$domainsList = array_merge($domainsList, $stmt->fetchAll(PDO::FETCH_ASSOC));
			usort($domainsList, function ($a, $b) {
				return strnatcmp(decode_idna($a['name']), decode_idna($b['name']));
			});
		}
	}

	return $domainsList;
}

/**
 * Add mail account
 *
 * @return bool TRUE on success, FALSE otherwise
 */
function client_addMailAccount()
{
	if (
		isset($_POST['username']) && isset($_POST['domain_name']) && isset($_POST['password']) &&
		isset($_POST['password_rep']) && isset($_POST['quota']) && isset($_POST['forward_list'])
	) {
		$mainDmnProps = get_domain_default_props($_SESSION['user_id']);
		$password = $forwardList = '_no_';
		$mailType = $subId = '';
		$quota = null;

		$mailTypeNormal = (isset($_POST['account_type']) && in_array($_POST['account_type'], array('1', '3')));
		$mailTypeForward = (isset($_POST['account_type']) && in_array($_POST['account_type'], array('2', '3')));

		if (!$mailTypeNormal && !$mailTypeForward) {
			showBadRequestErrorPage();
		}

		// Check for username
		$username = strtolower(clean_input($_POST['username']));

		if ($_POST['username'] == '' || !imscp_check_local_part($username)) {
			set_page_message(tr('Invalid Email username.'), 'error');
			return false;
		}

		// Check for domain existence and owner
		$domainName = clean_input($_POST['domain_name']);
		$domainType = null;
		$domainId = null;

		foreach (_client_getDomainsList() as $domain) {
			if ($domain['name'] == $domainName) {
				$domainType = $domain['type'];
				$domainId = $domain['id'];
				$subId = ($domainType != 'dmn') ? $domainId : '0';
			}
		}

		if (null !== $domainType) {
			$mailAddr = $username . '@' . $domainName;

			if ($mailTypeNormal) {
				// Check for pasword
				$password = clean_input($_POST['password']);
				$password_rep = clean_input($_POST['password_rep']);

				if ($password == '') {
					set_page_message(tr('Password is missing.'), 'error');
					return false;
				} elseif ($password_rep == '') {
					set_page_message(tr('You must confirm your password.'), 'error');
					return false;
				} elseif ($password !== $password_rep) {
					set_page_message(tr("Passwords do not match."), 'error');
					return false;
				} elseif (!checkPasswordSyntax($password, "/[`\xb4'\"\\\\\x01-\x1f\015\012|<>^$]/i")) {
					return false;
				}

				// Check for quota
				$quota = clean_input($_POST['quota']);

				if (is_number($quota)) {
					$quota *= 1048576; // MiB to Bytes

					if ($mainDmnProps['mail_quota'] != '0') {
						if ($quota == '0') {
							set_page_message(tr('Incorrect Email quota.'), 'error');
							return false;
						}

						$stmt = exec_query(
							'SELECT SUM(`quota`) AS `quota` FROM `mail_users` WHERE `domain_id` = ? AND `quota` IS NOT NULL',
							$mainDmnProps['domain_id']
						);

						$quotaLimit = floor($mainDmnProps['mail_quota'] - ($stmt->fields['quota']));

						if ($quota > $quotaLimit) {
							set_page_message(
								tr('Email quota cannot be bigger than %s', bytesHuman($quotaLimit, 'MiB')), 'error'
							);
							return false;
						}
					}
				} else {
					set_page_message(tr('Email quota must be a number.'), 'error');
					return false;
				}

				switch ($domainType) {
					case 'dmn':
						$mailType = MT_NORMAL_MAIL;
						break;
					case 'sub':
						$mailType = MT_SUBDOM_MAIL;
						break;
					case 'als':
						$mailType = MT_ALIAS_MAIL;
						break;
					case 'alssub':
						$mailType = MT_ALSSUB_MAIL;
				}
			}

			if ($mailTypeForward) {
				// Check forward list
				$forwardList = clean_input($_POST['forward_list']);

				if ($forwardList == '') {
					set_page_message(tr('Forward list is empty.'), 'error');
					return false;
				}

				$forwardList = preg_split("/[\n,]+/", $forwardList);

				foreach ($forwardList as $key => &$forwardEmailAddr) {
					$forwardEmailAddr = encode_idna(trim($forwardEmailAddr));

					if ($forwardEmailAddr == '') {
						unset($forwardList[$key]);
					} elseif (!chk_email($forwardEmailAddr)) {
						set_page_message(tr('Wrong mail syntax in forward list.'), 'error');
						return false;
					} elseif ($forwardEmailAddr == $mailAddr) {
						set_page_message(tr('You cannot forward %s on itself.', $mailAddr), 'error');
						return false;
					}
				}

				$forwardList = implode(',', array_unique($forwardList));

				switch ($domainType) {
					case 'dmn':
						$mailType .= (($mailType != '') ? ',' : '') . MT_NORMAL_FORWARD;
						break;
					case 'sub':
						$mailType .= (($mailType != '') ? ',' : '') . MT_SUBDOM_FORWARD;
						break;
					case 'als':
						$mailType .= (($mailType != '') ? ',' : '') . MT_ALIAS_FORWARD;
						break;
					case 'alssub':
						$mailType .= (($mailType != '') ? ',' : '') . MT_ALSSUB_FORWARD;
				}
			}

			// Check for mail account existence
			$stmt = exec_query("SELECT `mail_id` FROM `mail_users` WHERE `mail_addr` = ?", $mailAddr);

			if ($stmt->rowCount()) {
				set_page_message(tr('Email account already exists.'), 'error');
				return false;
			}

			// Add mail account into database

			/** @var $db iMSCP_Database */
			$db = iMSCP_Registry::get('db');

			iMSCP_Events_Aggregator::getInstance()->dispatch(
				iMSCP_Events::onBeforeAddMail, array('mailUsername' => $username, 'MailAddress' => $mailAddr)
			);

			$query = '
				INSERT INTO `mail_users` (
					`mail_acc`, `mail_pass`, `mail_forward`, `domain_id`, `mail_type`, `sub_id`, `status`,
					`mail_auto_respond`, `mail_auto_respond_text`, `quota`, `mail_addr`
				) VALUES
					(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
			';
			exec_query(
				$query,
				array(
					$username, $password, $forwardList, $mainDmnProps['domain_id'], $mailType, $subId,
					'toadd', '0', NULL, $quota, $mailAddr
				)
			);

			iMSCP_Events_Aggregator::getInstance()->dispatch(
				iMSCP_Events::onAfterAddMail,
				array('mailUsername' => $username, 'mailAddress' => $mailAddr, 'mailId' => $db->insertId())
			);

			// Schedule mail account addition
			send_request();

			write_log("{$_SESSION['user_logged']}: added new Email account: $mailAddr", E_USER_NOTICE);
			set_page_message(tr('Email account successfully scheduled for addition.'), 'success');
		} else {
			showBadRequestErrorPage();
		}
	} else {
		showBadRequestErrorPage();
	}

	return true;
}

/**
 * Generate page
 *
 * @param iMSCP_pTemplate $tpl
 */
function client_generatePage($tpl)
{
	$mainDmnProps = get_domain_default_props($_SESSION['user_id']);

	$stmt = exec_query(
		'SELECT SUM(`quota`) AS `quota` FROM `mail_users` WHERE `domain_id` = ? AND `quota` IS NOT NULL',
		$mainDmnProps['domain_id']
	);

	$quota = $stmt->fields['quota'];

	if ($mainDmnProps['mail_quota'] != '0' && $quota >= $mainDmnProps['mail_quota']) {
		set_page_message(
			'You cannot add new Email account. You have already assigned all your Email quota to other mailboxes. Please first, review your quota assignments.',
			'warning'
		);
		$tpl->assign('MAIL_ACCOUNT', '');
	} else {
		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');

		$checked = $cfg->HTML_CHECKED;
		$selected = $cfg->HTML_SELECTED;

		$mailType = (isset($_POST['account_type']) && in_array($_POST['account_type'], array('1', '2', '3')))
			? $_POST['account_type'] : '1';

		$tpl->assign(
			array(
				'USERNAME' => isset($_POST['username']) ? tohtml($_POST['username']) : '',
				'NORMAL_CHECKED' => ($mailType == '1') ? $checked : '',
				'FORWARD_CHECKED' => ($mailType == '2') ? $checked : '',
				'NORMAL_FORWARD_CHECKED' => ($mailType == '3') ? $checked : '',
				'PASSWORD' => isset($_POST['password']) ? tohtml($_POST['password']) : '',
				'PASSWORD_REP' => isset($_POST['password_rep']) ? tohtml($_POST['password_rep']) : '',
				'TR_QUOTA' => ($mainDmnProps['mail_quota'] == '0')
					? tr('Quota in MiB (0 for unlimited)')
					: tr('Quota in MiB (Max: %s)', bytesHuman($mainDmnProps['mail_quota'] - $quota, 'MiB')),
				'QUOTA' => isset($_POST['quota']) ? tohtml($_POST['quota']) : '',
				'FORWARD_LIST' => isset($_POST['forward_list']) ? tohtml($_POST['forward_list']) : '',
			)
		);

		foreach (_client_getDomainsList() as $domain) {
			$tpl->assign(
				array(
					'DOMAIN_NAME' => tohtml($domain['name']),
					'DOMAIN_NAME_UNICODE' => tohtml(decode_idna($domain['name'])),
					'DOMAIN_NAME_SELECTED' => (isset($_POST['domain_name']) && $_POST['domain_name'] == $domain['name'])
						? $selected : '',
				)
			);

			$tpl->parse('DOMAIN_NAME_ITEM', '.domain_name_item');
		}
	}
}

/***********************************************************************************************************************
 * Main
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

customerHasFeature('mail') or showBadRequestErrorPage();

$dmnProps = get_domain_default_props($_SESSION['user_id']);
$emailAccountsLimit = $dmnProps['domain_mailacc_limit'];

// Check for mail accounts limit

if ($emailAccountsLimit != '0') {
	list($nbEmailAccounts) = get_domain_running_mail_acc_cnt($dmnProps['domain_id']);

	if ($nbEmailAccounts >= $emailAccountsLimit) {
		set_page_message(tr('You have reached the maximum number of Email accounts allowed by your subscription.'), 'warning');
		redirectTo('mail_accounts.php');
	}
}

if (!empty($_POST)) {
	if (client_addMailAccount()) {
		redirectTo('mail_accounts.php');
	}
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'client/mail_add.tpl',
		'page_message' => 'layout',
		'mail_account' => 'page',
		'domain_name_item' => 'mail_account'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Client / Email / Add Email Account'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_MAIl_ACCOUNT_DATA' => tr('Email account data'),
		'TR_USERNAME' => tr('Username'),
		'TR_DOMAIN_NAME' => tr('Domain name'),
		'TR_MAIL_ACCOUNT_TYPE' => tr('Mail account type'),
		'TR_NORMAL_MAIL' => tr('Normal'),
		'TR_FORWARD_MAIL' => tr('Forward'),
		'TR_FORWARD_NORMAL_MAIL' => tr('Normal + Forward'),
		'TR_PASSWORD' => tr('Password'),
		'TR_PASSWORD_REPEAT' => tr('Password confirmation'),
		'TR_FORWARD_TO' => tr('Forward to'),
		'TR_FWD_HELP' => tr('Separate multiple email addresses by comma or a line-break.'),
		'TR_ADD' => tr('Add'),
		'TR_CANCEL' => tr('Cancel')
	)
);

client_generatePage($tpl, $_SESSION['user_id']);
generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
