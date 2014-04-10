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
 * Get Email account data
 *
 * @param int $mailId Mail account unique identifier
 * @return array Email account data
 */
function client_getEmailAccountData($mailId)
{
	static $mailData = null;

	if (null === $mailData) {
		$mainDmnProps = get_domain_default_props($_SESSION['user_id']);

		$stmt = exec_query(
			'SELECT * FROM mail_users WHERE mail_id = ? AND domain_id = ?', array($mailId, $mainDmnProps['domain_id'])
		);

		if (!$stmt->rowCount()) {
			showBadRequestErrorPage();
		}

		$mailData = $stmt->fetchRow(PDO::FETCH_ASSOC);
	}

	return $mailData;
}

/**
 * Edit mail account
 *
 * @throws iMSCP_Exception
 * @return bool TRUE on success, FALSE otherwise
 */
function client_editMailAccount()
{
	if (
		isset($_POST['password']) && isset($_POST['password_rep']) && isset($_POST['quota']) &&
		isset($_POST['forward_list'])
	) {
		$mailData = client_getEmailAccountData(clean_input($_GET['id']));
		$mainDmnProps = get_domain_default_props($_SESSION['user_id']);
		$password = $forwardList = '_no_';
		$mailType = '';
		$quota = null;

		if(preg_match('/^(.*?)_(?:mail|forward)/', $mailData['mail_type'], $match)) {
			$domainType = $match[1];
		} else {
			throw new iMSCP_Exception('Unable to determine mail type');
		}

		$mailTypeNormal = (isset($_POST['account_type']) && in_array($_POST['account_type'], array('1', '3')));
		$mailTypeForward = (isset($_POST['account_type']) && in_array($_POST['account_type'], array('2', '3')));

		if (!$mailTypeNormal && !$mailTypeForward) {
			showBadRequestErrorPage();
		}

		$mailAddr = $mailData['mail_addr'];

		if ($mailTypeNormal) {
			// Check for pasword
			$password = clean_input($_POST['password']);
			$password_rep = clean_input($_POST['password_rep']);

			if ($mailData['mail_pass'] == '_no_' || $password != '' || $password_rep != '') {
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
			} else {
				$password = $mailData['mail_pass'];
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

					$quotaLimit = floor($mainDmnProps['mail_quota'] - ($stmt->fields['quota'] - $mailData['quota']));

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
				case 'normal':
					$mailType = MT_NORMAL_MAIL;
					break;
				case 'subdom':
					$mailType = MT_SUBDOM_MAIL;
					break;
				case 'alias':
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

			switch($domainType) {
				case 'normal':
					$mailType .= (($mailType != '') ? ',' : '') . MT_NORMAL_FORWARD;
					break;
				case 'subdom':
					$mailType .= (($mailType != '') ? ',' : '') . MT_SUBDOM_FORWARD;
					break;
				case 'alias':
					$mailType .= (($mailType != '') ? ',' : '') . MT_ALIAS_FORWARD;
					break;
				case 'alssub':
					$mailType .= (($mailType != '') ? ',' : '') . MT_ALSSUB_FORWARD;
			}
		}

		// Update mail account into database

		iMSCP_Events_Aggregator::getInstance()->dispatch(
			iMSCP_Events::onBeforeEditMail, array('mailId' => $mailData['mail_id'])
		);

		$query = '
			UPDATE
				`mail_users`
			SET
				`mail_pass` = ?, `mail_forward` = ?, `mail_type` = ?, `status` = ?, `quota` = ?
			WHERE
				`mail_id` = ?
		';
		exec_query(
			$query, array($password, $forwardList, $mailType, 'tochange', $quota, $mailData['mail_id'])
		);

		iMSCP_Events_Aggregator::getInstance()->dispatch(
			iMSCP_Events::onAfterEditMail, array('mailId' => $mailData['mail_id'])
		);

		// Schedule mail account addition
		send_request();

		write_log("{$_SESSION['user_logged']}: Updated Email account: $mailAddr", E_USER_NOTICE);
		set_page_message(tr('Email account successfully scheduled for update.'), 'success');
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
	$mailId = clean_input($_GET['id']);
	$mainDmnProps = get_domain_default_props($_SESSION['user_id']);
	$mailData = client_getEmailAccountData($mailId);
	list($username, $domainName) = explode('@', $mailData['mail_addr']);

	$stmt = exec_query(
		'SELECT SUM(`quota`) AS `quota` FROM `mail_users` WHERE `domain_id` = ? AND `quota` IS NOT NULL',
		$mainDmnProps['domain_id']
	);

	$quota = $stmt->fields['quota'];

	/** @var iMSCP_Config_Handler_File $cfg */
	$cfg = iMSCP_Registry::get('config');

	$checked = $cfg->HTML_CHECKED;
	$selected = $cfg->HTML_SELECTED;

	$mailType = '';

	if(!isset($_POST['account_type']) || !in_array($_POST['account_type'], array('1', '2', '3'))) {
		if(preg_match('/_mail/', $mailData['mail_type'])) {
			$mailType = '1';
		}

		if(preg_match('/_forward/', $mailData['mail_type'])) {
			$mailType = ($mailType == '1') ? '3' : '2';
		}
	} else {
		$mailType = $_POST['account_type'];
	}

	$tpl->assign(
		array(
			'MAIL_ID' => tohtml($mailId),
			'USERNAME' => tohtml($username),
			'NORMAL_CHECKED' => ($mailType == '1') ? $checked : '',
			'FORWARD_CHECKED' => ($mailType == '2') ? $checked : '',
			'NORMAL_FORWARD_CHECKED' => ($mailType == '3') ? $checked : '',
			'PASSWORD' => isset($_POST['password']) ? tohtml($_POST['password']) : '',
			'PASSWORD_REP' => isset($_POST['password_rep']) ? tohtml($_POST['password_rep']) : '',
			'TR_QUOTA' => ($mainDmnProps['mail_quota'] == '0')
				? tr('Quota in MiB (0 for unlimited)')
				: tr('Quota in MiB (Max: %s)', bytesHuman($mainDmnProps['mail_quota'] - ($quota - $mailData['quota']), 'MiB')),
			'QUOTA' => isset($_POST['quota']) ? tohtml($_POST['quota']) : ($quota !== NULL ? floor($mailData['quota'] / 1048576) : ''),
			'FORWARD_LIST' => isset($_POST['forward_list'])
				? tohtml($_POST['forward_list'])
				: ($mailData['mail_forward'] != '_no_' ? tohtml($mailData['mail_forward']) : '')
		)
	);

	$tpl->assign(
		array(
			'DOMAIN_NAME' => tohtml($domainName),
			'DOMAIN_NAME_UNICODE' => tohtml(decode_idna($domainName)),
			'DOMAIN_NAME_SELECTED' => $selected,
		)
	);
}

/***********************************************************************************************************************
 * Main
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

if (isset($_GET['id']) && customerHasFeature('mail')) {
	if (!empty($_POST)) {
		if (client_editMailAccount()) {
			redirectTo('mail_accounts.php');
		}
	}

	$tpl = new iMSCP_pTemplate();
	$tpl->define_dynamic(
		array(
			'layout' => 'shared/layouts/ui.tpl',
			'page' => 'client/mail_edit.tpl',
			'page_message' => 'layout'
		)
	);

	$tpl->assign(
		array(
			'TR_PAGE_TITLE' => tr('Client / Email / Edit Email Account'),
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
			'TR_UPDATE' => tr('Update'),
			'TR_CANCEL' => tr('Cancel')
		)
	);

	client_generatePage($tpl, $_SESSION['user_id']);
	generateNavigation($tpl);
	generatePageMessage($tpl);

	$tpl->parse('LAYOUT_CONTENT', 'page');

	iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

	$tpl->prnt();
} else {
	showBadRequestErrorPage();
}
