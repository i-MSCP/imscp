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
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright	2010-2012 by i-MSCP | http://i-mscp.net
 * @author		ispCP Team
 * @author		i-MSCP Team
 * @link		http://i-mscp.net
 */

/*******************************************************************************
 * Script functions
 */

/**
 * Is customer limit lower thabt the hosting plan limit?
 *
 * @param $currentLimit
 * @param $hpLimit
 * @return bool
 */
function client_checkHpLimit($currentLimit, $hpLimit)
{
	$result = true;

	if ($currentLimit > 0) {
		if ($hpLimit == -1) {
			$result = false;
		} elseif ($hpLimit != 0 && $currentLimit > $hpLimit) {
			$result = false;
		}
	}

	return $result;
}

/**
 * Generates hosting plan entries (only those available for the current customer).
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $customerId
 * @return
 */
function client_generateHostingPlanEntries($tpl, $customerId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$domainProperties = get_domain_default_props($_SESSION['user_id'], true);

	//$availabe_order = 0;
	$hpTitle = tr('Hosting plans available for update');

	// Let's see if we have an order
	$query = "SELECT * FROM `orders` WHERE `customer_id` = ? AND `status` <> ?";
	$stmt = exec_query($query, array($customerId, 'added'));

	if ($stmt->rowCount()) {
		//$availabe_order = 1;
		$availableHpId = $stmt->fields['plan_id'];

		$query = "SELECT * FROM `hosting_plans` WHERE `id` = ?";
		$stmt = exec_query($query, $availableHpId);

		$count = 2;
		$purchaseText = tr('Cancel order');
		$purchaseLink = 'delete_id';
		$hpTitle = tr('Your order');
	} else {
		// Generates all hosting plans available for purchasing
		if (isset($cfg->HOSTING_PLANS_LEVEL) && $cfg->HOSTING_PLANS_LEVEL == 'admin') {
			$query = "
				SELECT
					`t1`.*, `t2`.`admin_id`, `t2`.`admin_type`
				FROM
					`hosting_plans` `t1`, `admin` `t2`
				WHERE
					`t2`.`admin_type` = ?
				AND
					`t1`.`reseller_id` = `t2`.`admin_id`
				AND
					`t1`.`status` = '1'
				ORDER BY
					`t1`.`name`
			";
			$stmt = exec_query($query, 'admin');

			$count = $stmt->rowCount();
			$count++;
		} else {
			$query = "SELECT * FROM `hosting_plans` WHERE `reseller_id` = ? AND `status` = '1'";
			$count_query = "SELECT COUNT(`id`) `cnum` FROM `hosting_plans` WHERE `reseller_id` = ? AND `status` = '1'";

			$cnt = exec_query($count_query, $_SESSION['user_created_by']);
			$stmt = exec_query($query, $_SESSION['user_created_by']);
			$count = $cnt->fields['cnum'] + 1;
		}

		$purchaseText = tr('Purchase');
		$purchaseLink = 'order_id';
	}

	if (!$stmt->rowCount()) {
		$tpl->assign(
			array(
				'TR_HOSTING_PLANS' => $hpTitle,
				'HOSTING_PLANS' => '',
				'HP_ORDER' => '',
				'COLSPAN' => 2
			)
		);

		set_page_message(tr('There are no available hosting plans for updates.'), 'info');
		return;
	}

	$tpl->assign('COLSPAN', $count);
	$i = 0;

	while (!$stmt->EOF) {
		list(
			$hpPhp, $hpCgi, $hpSubdomains, $hpAliases, $hpPop, $hpFtp, $hpSqlDatabases, $hpSqlUsers, $hpBandwich,
			$hpDiskspace, $hpBackup, $hpCustomDns, $hpAps, $hpPhpEditor
			) = explode(';', $stmt->fields['props']);


		if ($hpAps == '_yes_' && get_reseller_sw_installer($_SESSION['user_created_by']) == 'no') {
			$stmt->MoveNext();
		} else {
			$warningMessages = $errorsMessages = array();

			if ($hpPhp == '_yes_') {
				$details = tr('PHP Support: enabled') . '<br />';
				$php = 'yes';
			} else {
				$details = tr('PHP Support: disabled') . '<br />';
				$php = 'no';

				if ($domainProperties['domain_php'] == 'yes') {
					$warningMessages[] = tr("You have PHP enabled, but the new hosting plan doesn't provide this feature.");
				}
			}

			if ($hpCgi == '_yes_') {
				$cgi = 'yes';
				$details .= tr('CGI Support: enabled') . '<br />';
			} else {
				$cgi = 'no';
				$details .= tr('CGI Support: disabled') . '<br />';

				if ($domainProperties['domain_cgi'] == 'yes') {
					$warningMessages[] = tr("You have CGI enabled, but the new hosting plan doesn't provide this feature.");
				}
			}

			if ($hpCustomDns == '_yes_') {
				$dns = 'yes';
				$details .= tr('DNS Support: enabled') . '<br />';
			} else {
				$dns = 'no';
				$details .= tr('DNS Support: disabled') . '<br />';

				if ($domainProperties['domain_dns'] == 'yes') {
					$warningMessages[] = tr("You have custom DNS enabled, but the new hosting plan doesn't provide this feature.");
				}
			}

			if ($hpAps == '_yes_') {
				$software = 'yes';
				$details .= tr('Software installer: enabled') . '<br />';
			} else {
				$software = 'no';
				$details .= tr('Software installer: disabled') . '<br />';

				if ($domainProperties['domain_software_allowed'] == 'yes') {
					$warningMessages[] = tr("You have software installer enabled, but the new hosting plan doesn't provide this feature.");
				}
			}

			$traffic = get_user_traffic($domainProperties['domain_id']);
			$currentValue = $traffic[7] / 1048576; // Convert disk space usage to MiB

			if (!client_checkHpLimit($currentValue, $hpDiskspace)) {
				$errorsMessages[] = tr('You have more disk space in use than the new hosting plan limits.');
			}

			$hdd_usage = tr('Disk limit') . ': ' . translate_limit_value($hpDiskspace, true) . '<br />';
			$currentValue = $traffic[10] / 1048576; // convert max. traffic to MiB

			if (!client_checkHpLimit($currentValue, $hpBandwich)) {
				$warningMessages[] = tr('You did have more traffic than the new hosting plan limits.');
			}

			$traffic_usage = tr('Traffic limit') . ': ' . translate_limit_value($hpBandwich, true);
			$currentValue = get_domain_running_als_cnt($domainProperties['domain_id']);

			if (!client_checkHpLimit($currentValue, $hpAliases)) {
				$errorsMessages[] = tr('You have more domain aliases in use than the new hosting plan limits');
			}

			$details .= tr('Domain Aliases') . ': ' . translate_limit_value($hpAliases) . '<br />';
			$currentValue = get_domain_running_sub_cnt($domainProperties['domain_id']);

			if (!client_checkHpLimit($currentValue, $hpSubdomains)) {
				$errorsMessages[] = tr('You have more subdomains in use than the new hosting plan limits');
			}

			$details .= tr('Subdomains') . ': ' . translate_limit_value($hpSubdomains) . '<br />';
			$currentValue = get_domain_running_mail_acc_cnt($domainProperties['domain_id']);

			if (!client_checkHpLimit($currentValue[0], $hpPop)) {
				$errorsMessages[] = tr('You have more Email addresses in use than the new hosting plan limits.');
			}

			$details .= tr('Emails') . ': ' . translate_limit_value($hpPop) . '<br />';
			$currentValue = get_domain_running_ftp_acc_cnt($domainProperties['domain_id']);

			if (!client_checkHpLimit($currentValue[0], $hpFtp)) {
				$errorsMessages[] = tr('You have more FTP accounts in use than the new hosting plan limits.');
			}

			$details .= tr('FTPs') . ': ' . translate_limit_value($hpFtp) . '<br />';
			$currentValue = get_domain_running_sqld_acc_cnt($domainProperties['domain_id']);

			if (!client_checkHpLimit($currentValue, $hpSqlDatabases)) {
				$errorsMessages[] = tr('You have more SQL databases in use than the new hosting plan limits.');
			}

			$details .= tr('SQL Databases') . ': ' . translate_limit_value($hpSqlDatabases) . '<br />';
			$currentValue = get_domain_running_sqlu_acc_cnt($domainProperties['domain_id']);

			if (!client_checkHpLimit($currentValue, $hpSqlUsers)) {
				$errorsMessages[] = tr('You have more SQL database users in use than the new hosting plan limits.');
			}

			$details .= tr('SQL Users') . ': ' . translate_limit_value($hpSqlUsers) . '<br />';
			$details .= $hdd_usage . $traffic_usage;
			$price = $stmt->fields['price'];

			if ($price == 0 || $price == '') {
				$price = tr('Free of charge');
			} else {
				$price = $price . ' ' . $stmt->fields['value'] . ' ' . tr('per') . ' ' . $stmt->fields['payment'];
			}

			// Check if the hosting plan provide same features and limit thant the customer current limits and features
			$check_query = "
				SELECT
					`domain_id`
				FROM
					`domain`
				WHERE
					`domain_admin_id` = ?
				AND
					`domain_mailacc_limit` = ?
				AND
					`domain_ftpacc_limit` = ?
				AND
					`domain_traffic_limit` = ?
				AND
					`domain_sqld_limit` = ?
				AND
					`domain_sqlu_limit` = ?
				AND
					`domain_alias_limit` = ?
				AND
					`domain_subd_limit` = ?
				AND
					`domain_disk_limit` = ?
				AND
					`domain_php` = ?
				AND
					`domain_cgi` = ?
				AND
					`domain_dns` = ?
				AND
					`domain_software_allowed` = ?
			";
			$check = exec_query($check_query,
				array(
					$_SESSION['user_id'], $hpPop, $hpFtp, $hpBandwich, $hpSqlDatabases, $hpSqlUsers, $hpAliases, $hpSubdomains,
					$hpDiskspace, $php, $cgi, $dns, $software
				)
			);

			if (!$check->rowCount()) {
				$link_purchase = '<a href="hosting_plan_update.php?' . $purchaseLink . '=' . $stmt->fields['id'] . '" class="linkdark">';

				if ($purchaseLink == 'order_id' && count($errorsMessages) > 0) {
					$link_purchase = tr('You cannot update to this hosting plan, see notices in text.');

					if (count($warningMessages) > 0) {
						$warning_text = '<br /><br /><strong>' . tr('Warning:') . '</strong><br />' . implode('<br />', $warningMessages);
					} else {
						$warning_text = '';
					}

					$warning_text .= '<br /><br /><strong>' . tr('Caution:') . '</strong><br />' . implode('<br />', $errorsMessages);
				} elseif ($purchaseLink == 'order_id' && count($warningMessages) > 0) {
					$warning_text = '<br /><br /><strong>' . tr('Warning:') . '</strong><br />' . implode('<br />', $warningMessages);
					$link_purchase .= tr('I understand the warnings - Purchase!');
					$link_purchase .= '</a>';
				} else {
					$warning_text = '';
					$link_purchase .= '{TR_PURCHASE}</a>';
				}

				$tpl->assign(
					array(
						'HP_NAME' => tohtml($stmt->fields['name']),
						'HP_DESCRIPTION' => tohtml($stmt->fields['description']),
						'HP_DETAILS' => $details . $warning_text,
						'HP_COSTS' => tohtml($price),
						'ID' => $stmt->fields['id'],
						'TR_PURCHASE' => $purchaseText,
						'LINK' => $purchaseLink,
						'TR_HOSTING_PLANS' => $hpTitle,
						'LINK_PURCHASE' => $link_purchase
					)
				);

				$tpl->parse('HOSTING_PLANS', '.hosting_plans');
				$tpl->parse('HP_ORDER', '.hp_order');
				$i++;
			}

			$purchaseText = tr('Purchase');
			$purchaseLink = 'order_id';
			$stmt->moveNext();
		}
	}

	if ($i == 0) {
		$tpl->assign(
			array(
				'HOSTING_PLANS' => '',
				'HP_ORDER' => '',
				'TR_HOSTING_PLANS' => $hpTitle,
				'COLSPAN' => '2'
			)
		);

		set_page_message(tr('There are no available hosting plans for update.'), 'info');
	}
}

/**
 * Add hosting plan order.
 *
 * @param int $orderId
 * @param int $customerId
 */
function client_addNewOrder($orderId, $customerId)
{

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$domainProperties = get_domain_default_props($customerId, true);

	$query = "SELECT * FROM `hosting_plans` WHERE `id` = ?";
	$stmt = exec_query($query, $orderId);

	list(
		, , $hp_sub, $hp_als, $hp_mail, $hp_ftp, $hp_sql_db, $hp_sql_user, , $hp_disk
		) = explode(';', $stmt->fields['props']);

	$traffic = get_user_traffic($domainProperties['domain_id']);

	$currentValue = $traffic[7] / 1048576; // Disk space usage
	if (!client_checkHpLimit($currentValue, $hp_disk)) {
		set_page_message(tr('You have more disk space in use than the new hosting plan limits.'), 'error');
	}

	$currentValue = get_domain_running_als_cnt($domainProperties['domain_id']);

	if (!client_checkHpLimit($currentValue, $hp_als)) {
		set_page_message(tr('You have more aliases in use than the new hosting plan limits.'), 'error');
	}

	$currentValue = get_domain_running_sub_cnt($domainProperties['domain_id']);

	if (!client_checkHpLimit($currentValue, $hp_sub)) {
		set_page_message(tr('You have more subdomains in use than the new hosting plan limits.'), 'error');
	}

	$currentValue = get_domain_running_mail_acc_cnt($domainProperties['domain_id']);

	if (!client_checkHpLimit($currentValue[0], $hp_mail)) {
		set_page_message(tr('You have more e-mail addresses in use than the new hosting plan limits.'), 'error');
	}

	$currentValue = get_domain_running_ftp_acc_cnt($domainProperties['domain_id']);

	if (!client_checkHpLimit($currentValue[0], $hp_ftp)) {
		set_page_message(tr('You have more FTP accounts in use than the new hosting plan limits.'), 'error');
	}

	$currentValue = get_domain_running_sqld_acc_cnt($domainProperties['domain_id']);

	if (!client_checkHpLimit($currentValue, $hp_sql_db)) {
		set_page_message(tr('You have more SQL databases in use than the new hosting plan limits.'), 'error');
	}

	$currentValue = get_domain_running_sqlu_acc_cnt($domainProperties['domain_id']);

	if (!client_checkHpLimit($currentValue, $hp_sql_user)) {
		set_page_message(tr('You have more SQL database users in use than the new hosting plan limits.'), 'error');
	}

	if (Zend_Session::namespaceIsset('pageMessages')) {
		redirectTo('hosting_plan_update.php');
	}

	$date = time();
	$status = 'update';

	$query = "
		INSERT INTO `orders` (
			`user_id`, `plan_id`, `date`, `domain_name`, `customer_id`, `fname`, `lname`, `firm`, `zip`, `city`,
			`state`, `country`, `email`, `phone`, `fax`, `street1`, `street2`, `status`
		) VALUES (
			?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
		)
	";

	exec_query(
		$query,
		array(
			$_SESSION['user_created_by'], $orderId, $date, $_SESSION['user_logged'], $customerId, '', '', '', '', '', '',
			'', '', '', '', '', '', $status
		)
	);

	set_page_message(tr('Your request for hosting plan update was successfully registered.'), 'success');

	$query = "
		SELECT
			`t1`.`email` `reseller_mail`, `t2`.`email` `user_mail`
		FROM
			`admin` `t1`, `admin` `t2`
		WHERE
			`t1`.`admin_id` = ?
		AND
			`t2`.`admin_id` = ?
	";
	$stmt = exec_query($query, array($_SESSION['user_created_by'], $_SESSION['user_id']));

	$to = $stmt->fields['reseller_mail'];
	$from = $stmt->fields['user_mail'];

	$headers = "From: " . $from . "\n";
	$headers .= "MIME-Version: 1.0\nContent-Type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 7bit\n";
	$headers .= "X-Mailer: i-MSCP auto mailer";

	$subject = tr("[i-MSCP OrderPanel] - You have an update order", true);

	$message = tr(
		"You have an update order for the account %s\n\nPlease login into your i-MSCP control panel at %s for more details",
		true,
		$_SESSION['user_logged'],
		$cfg->BASE_SERVER_VHOST_PREFIX . $cfg->BASE_SERVER_VHOST);

	mail($to, $subject, $message, $headers);
	redirectTo('hosting_plan_update.php');
}

/**
 * Delete hosting plan update order for the given customer.
 *
 * @param int $customerId Customer unique identifier
 * @param int $orderId Order unique identifier
 * @return void
 */
function client_deleteOrder($orderId, $customerId)
{
	$query = "DELETE FROM `orders` WHERE `plan_id` = ? AND `user_id` = ? AND `customer_id` = ?";
	$stmt = exec_query($query, array($orderId, $_SESSION['user_created_by'], $customerId));

	if ($stmt->rowCount()) {
		set_page_message(tr('Your order for hosting plan update was successfully canceled.'), 'success');
		redirectTo('hosting_plan_update.php');
	} else {
		set_page_message(tr('No such update order found.'), 'error');
	}
}


/**************************************************************************************
 * Main script
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
	client_addNewOrder($_GET['order_id'], $_SESSION['user_id']);
} elseif (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
	client_deleteOrder($_GET['delete_id'], $_SESSION['user_id']);
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'client/hosting_plan_update.tpl',
		'page_message' => 'layout',
		'def_language' => 'page',
		'hosting_plans' => 'page',
		'hp_order' => 'page'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - client / General Information / Update hosting plan'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()));

client_generateHostingPlanEntries($tpl, $_SESSION['user_id']);
generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
