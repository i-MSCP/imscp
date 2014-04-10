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
 * @package     iMSCP_Core
 * @subpackage  Admin_Plugin
 * @copyright   2010-2014 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Send email
 *
 * @param string $senderName Sender name
 * @param string $senderEmail Sender email
 * @param string $subject Subject
 * @param string $body Body
 * @param array $rcptToData Recipient data
 */
function admin_sendEmail($senderName, $senderEmail, $subject, $body, $rcptToData)
{
	if ($rcptToData['email'] != '') {
		$senderEmail = encode_idna($senderEmail);

		if (!empty($rcptToData['fname']) && !empty($rcptToData['lname'])) {
			$to = $rcptToData['fname'] . ' ' . $rcptToData['lname'];
		} elseif (!empty($rcptToData['fname'])) {
			$to = $rcptToData['fname'];
		} elseif (!empty($rcptToData['lname'])) {
			$to = $rcptToData['lname'];
		} else {
			$to = $rcptToData['admin_name'];
		}

		$from = encode_mime_header($senderName) .  " <$senderEmail>";
		$to = encode_mime_header($to) . " <{$rcptToData['email']}>";

		$headers = "From: $from\r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: text/plain; charset=utf-8\r\n";
		$headers .= "Content-Transfer-Encoding: 8bit\r\n";
		$headers .= "X-Mailer: i-MSCP Mailer";

		mail($to, encode_mime_header($subject), $body, $headers, "-f $senderEmail");
	}
}

/**
 * Send circular to administrators
 *
 * @param string $senderName Sender name
 * @param string $senderEmail Sender email
 * @param string $subject Subject
 * @param string $body Body
 */
function admin_sendToAdministrators($senderName, $senderEmail, $subject, $body)
{
	if (systemHasManyAdmins()) {
		$stmt = exec_query(
			'SELECT `admin_name`, `fname`, `lname`, `email` FROM `admin` WHERE `admin_type` = ?', 'admin'
		);

		while ($rcptToData = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			admin_sendEmail($senderName, $senderEmail, $subject, $body, $rcptToData);
		}
	}
}

/**
 * Send circular to resellers
 *
 * @param string $senderName Sender name
 * @param string $senderEmail Sender email
 * @param string $subject Subject
 * @param string $body Body
 */
function admin_sendToResellers($senderName, $senderEmail, $subject, $body)
{
	if (systemHasResellers()) {
		$stmt = exec_query(
			'SELECT `admin_name`, `fname`, `lname`, `email` FROM `admin` WHERE `admin_type` = ?', 'reseller'
		);

		while ($rcptToData = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			admin_sendEmail($senderName, $senderEmail, $subject, $body, $rcptToData);
		}
	}
}

/**
 * Send circular to customers
 *
 * @param string $senderName Sender name
 * @param string $senderEmail Sender email
 * @param string $subject Subject
 * @param string $body Body
 */
function admin_sendToCustomers($senderName, $senderEmail, $subject, $body)
{
	if (systemHasCustomers()) {
		$stmt = exec_query(
			'SELECT `admin_name`, `fname`, `lname`, `email` FROM `admin` WHERE `admin_type` = ?', 'user'
		);

		while ($rcptToData = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			admin_sendEmail($senderName, $senderEmail, $subject, $body, $rcptToData);
		}
	}
}

/**
 * Validate circular
 *
 * @param string $senderName Sender name
 * @param string $senderEmail Sender Email
 * @param string $subject Subject
 * @param string $body Body
 * @return bool TRUE if circular is valid, FALSE otherwise
 */
function admin_isValidCircular($senderName, $senderEmail, $subject, $body)
{
	$ret = true;

	if ($senderName == '') {
		set_page_message(tr('Sender name is missing.'), 'error');
		$ret = false;
	}

	if ($senderEmail == '') {
		set_page_message(tr('Sender email is missing.'), 'error');
		$ret = false;
	} elseif (!chk_email($senderEmail)) {
		set_page_message(tr("Incorrect email length or syntax."), 'error');
		$ret = false;
	}

	if ($subject == '') {
		set_page_message(tr('Subject is missing.'), 'error');
		$ret = false;
	}

	if ($body == '') {
		set_page_message(tr('Body is missing.'), 'error');
		$ret = false;
	}

	return $ret;
}

/**
 * Send circular
 *
 * @return bool TRUE on success, FALSE otherwise
 */
function admin_sendCircular()
{
	if (
		isset($_POST['sender_name']) && isset($_POST['sender_email']) && isset($_POST['rcpt_to']) &&
		isset($_POST['subject']) && isset($_POST['body'])
	) {
		$senderName = clean_input($_POST['sender_name']);
		$senderEmail = clean_input($_POST['sender_email']);
		$rcptTo = clean_input($_POST['rcpt_to']);
		$subject = clean_input($_POST['subject'], false);
		$body = clean_input($_POST['body'], false);

		if (admin_isValidCircular($senderName, $senderEmail, $subject, $body)) {
			$responses = iMSCP_Events_Aggregator::getInstance()->dispatch(
				iMSCP_Events::onBeforeSendCircular,
				array(
					'sender_name' => $senderName, 'sender_email' => $senderEmail, 'rcpt_to' => $rcptTo,
					'subject' => $subject, 'body' => $body
				)
			);

			if (!$responses->isStopped()) {
				if (
					$rcptTo == 'all_users' || $rcptTo == 'administrators_resellers' ||
					$rcptTo == 'administrators_customers' || $rcptTo == 'administrators'
				) {
					admin_sendToAdministrators($senderName, $senderEmail, $subject, $body);
				}

				if (
					$rcptTo == 'all_users' || $rcptTo == 'administrators_resellers' || $rcptTo == 'resellers_customers'
					|| $rcptTo == 'resellers'
				) {
					admin_sendToResellers($senderName, $senderEmail, $subject, $body);
				}

				if (
					$rcptTo == 'all_users' || $rcptTo == 'administrators_customers' || $rcptTo == 'resellers_customers'
					|| $rcptTo == 'customers'
				) {
					admin_sendToCustomers($senderName, $senderEmail, $subject, $body);
				}

				iMSCP_Events_Aggregator::getInstance()->dispatch(
					iMSCP_Events::onAfterSendCircular,
					array(
						'sender_name' => $senderName, 'sender_email' => $senderEmail, 'rcpt_to' => $rcptTo,
						'subject' => $subject, 'body' => $body
					)
				);

				set_page_message(tr('Circular successfully sent.'), 'success');
				write_log('A circular has been sent by admin: ' . tohtml("$senderName <$senderEmail>"), E_USER_NOTICE);
			}
		} else {
			return false;
		}
	} else {
		showBadRequestErrorPage();
	}

	return true;
}

/**
 * Generate page data
 *
 * @param iMSCP_pTemplate $tpl
 * @return void
 */
function admin_generatePageData($tpl)
{
	$senderName = isset($_POST['sender_name']) ? $_POST['sender_name'] : '';
	$senderEmail = isset($_POST['sender_email']) ? $_POST['sender_email'] : '';
	$rcptTo = isset($_POST['rcpt_to']) ? $_POST['rcpt_to'] : '';
	$subject = isset($_POST['subject']) ? $_POST['subject'] : '';
	$body = isset($_POST['body']) ? $_POST['body'] : '';

	if ($senderName == '' && $senderEmail == '') {
		$query = 'SELECT `admin_name`, `fname`, `lname`, `email` FROM `admin` WHERE `admin_id` = ?';
		$stmt = exec_query($query, $_SESSION['user_id']);
		$data = $stmt->fetchRow();

		if (!empty($data['fname']) && !empty($data['lname'])) {
			$senderName = $data['fname'] . ' ' . $data['lname'];
		} elseif (!empty($data['fname'])) {
			$senderName = $stmt->fields['fname'];
		} elseif (!empty($data['lname'])) {
			$senderName = $stmt->fields['lname'];
		} else {
			$senderName = $data['admin_name'];
		}

		if ($data['email'] != '') {
			$senderEmail = $data['email'];
		} else {
			$config = iMSCP_Registry::get('config');

			if (isset($config['DEFAULT_ADMIN_ADDRESS']) && $config['DEFAULT_ADMIN_ADDRESS'] != '') {
				$senderEmail = $config['DEFAULT_ADMIN_ADDRESS'];
			} else {
				$senderEmail = 'webmaster@' . $config['BASE_SERVER_VHOST'];
			}
		}
	}

	$tpl->assign(
		array(
			'SENDER_NAME' => tohtml($senderName),
			'SENDER_EMAIL' => tohtml($senderEmail),
			'SUBJECT' => tohtml($subject),
			'BODY' => tohtml($body)
		)
	);

	$rcptToOptions = array(
		array('all_users', tr('All users'))
	);

	if (systemHasManyAdmins() && systemHasResellers()) {
		$rcptToOptions[] = array('administrators_resellers', tr('Administrators and resellers'));
	}

	if (systemHasManyAdmins() && systemHasCustomers()) {
		$rcptToOptions[] = array('administrators_customers', tr('Administrators and customers'));
	}

	if (systemHasResellers() && systemHasCustomers()) {
		$rcptToOptions[] = array('resellers_customers', tr('Resellers and customers'));
	}

	if (systemHasManyAdmins()) {
		$rcptToOptions[] = array('administrators', tr('Administrators'));
	}

	if (systemHasResellers()) {
		$rcptToOptions[] = array('resellers', tr('Resellers'));
	}

	if (systemHasCustomers()) {
		$rcptToOptions[] = array('customers', tr('Customers'));
	}

	foreach ($rcptToOptions as $option) {
		$tpl->assign(
			array(
				'RCPT_TO' => $option[0],
				'TR_RCPT_TO' => $option[1],
				'SELECTED' => ($rcptTo == $option[0]) ? ' selected="selected"' : ''
			)
		);

		$tpl->parse('RCPT_TO_OPTION', '.rcpt_to_option');
	}
}

/***********************************************************************************************************************
 * Main
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

if (!systemHasAdminsOrResellersOrCustomers()) {
	showBadRequestErrorPage();
}

if (!(!empty($_POST) && admin_sendCircular())) {
	$tpl = new iMSCP_pTemplate();
	$tpl->define_dynamic(
		array(
			'layout' => 'shared/layouts/ui.tpl',
			'page' => 'admin/circular.tpl',
			'page_message' => 'layout',
			'rcpt_to_option' => 'page'
		)
	);

	$tpl->assign(
		array(
			'TR_PAGE_TITLE' => tr('Admin / Users / Circular'),
			'ISP_LOGO' => layout_getUserLogo(),
			'TR_CIRCULAR' => tr('Circular'),
			'TR_SEND_TO' => tr('Send to'),
			'TR_SUBJECT' => tr('Subject'),
			'TR_BODY' => tr('Body'),
			'TR_SENDER_EMAIL' => tr('Sender email'),
			'TR_SENDER_NAME' => tr('Sender name'),
			'TR_SEND_CIRCULAR' => tr('Send circular'),
			'TR_CANCEL' => tr('Cancel')
		)
	);

	generateNavigation($tpl);
	generatePageMessage($tpl);
	admin_generatePageData($tpl);

	$tpl->parse('LAYOUT_CONTENT', 'page');

	iMSCP_Events_Aggregator::getInstance()->dispatch(
		iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl)
	);

	$tpl->prnt();

	unsetMessages();
} else {
	redirectTo('manage_users.php');
}
