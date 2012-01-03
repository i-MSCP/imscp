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

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('layout', 'shared/layouts/ui.tpl');
$tpl->define_dynamic('page', 'client/hosting_plan_update.tpl');
$tpl->define_dynamic('page_message', 'layout');
$tpl->define_dynamic('def_language', 'page');
$tpl->define_dynamic('hosting_plans', 'page');
$tpl->define_dynamic('hp_order', 'page');

/**
 * @param $curr
 * @param $new
 * @return bool
 */
function check_update_current_value($curr, $new) {
	$result = true;

	if ($curr > 0) {
		if ($new == -1) {
			$result = false;
		} else {
			if ($new != 0 && $curr > $new) {
				$result = false;
			}
		}
	}

	return $result;
}

/**
 * @param $tpl
 * @param $user_id
 * @return
 */
function gen_hp($tpl, $user_id) {

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	// get domain id
	$query = "
		SELECT
			`domain_id`
		FROM
			`domain`
		WHERE
			`domain_admin_id` = ?
	";

	$rs = exec_query($query, $user_id);
	$domain_id = $rs->fields['domain_id'];

	// get current domain settings
	$query = "
		SELECT
			*
		FROM
			`domain`
		WHERE
			`domain_id` = ?
	";

	$rs = exec_query($query, $domain_id);
	$current = $rs->fetchRow();

	$availabe_order = 0;
	$hp_title = tr('Hosting plans available for update');
	// let's see if we have an order
	$query = "
		SELECT
			*
		FROM
			`orders`
		WHERE
			`customer_id` = ?
		AND
			`status` <> ?
	";

	$rs = exec_query($query, array($user_id, 'added'));

	if ($rs->recordCount() > 0) {
		$availabe_order = 1;
		$availabe_hp_id = $rs->fields['plan_id'];

		$query = "SELECT * FROM `hosting_plans` WHERE `id` = ?";
		$rs = exec_query($query, $availabe_hp_id);

		$count = 2;
		$purchase_text = tr('Cancel order');
		$purchase_link = 'delete_id';
		$hp_title = tr('Your order');
	} else {
		// generate all hosting plans available for purchasing
		if (isset($cfg->HOSTING_PLANS_LEVEL) && $cfg->HOSTING_PLANS_LEVEL == 'admin') {
			$query = "
				SELECT
					t1.*, t2.`admin_id`, t2.`admin_type`
				FROM
					`hosting_plans` AS t1, `admin` AS t2
				WHERE
					t2.`admin_type` = ?
				AND
					t1.`reseller_id` = t2.`admin_id`
				AND
					t1.`status` = '1'
				ORDER BY
					t1.`name`
			";

			$rs = exec_query($query, 'admin');

			$count = $rs->recordCount();
			$count++;
		} else {
			$query = "
				SELECT
					*
				FROM
					`hosting_plans`
				WHERE
					`reseller_id` = ?
				AND
					`status` = '1'
			";

			$count_query = "
				SELECT
					COUNT(`id`) AS cnum
				FROM
					`hosting_plans`
				WHERE
					`reseller_id` = ?
				AND
					`status` = '1'
			";

			$cnt = exec_query($count_query, $_SESSION['user_created_by']);
			$rs = exec_query($query, $_SESSION['user_created_by']);
			$count = $cnt->fields['cnum'] + 1;
		}

		$purchase_text = tr('Purchase');
		$purchase_link = 'order_id';
	}

	if ($rs->recordCount() == 0) {
		$tpl->assign(
			array(
				'TR_HOSTING_PLANS' => $hp_title,
				'HOSTING_PLANS' => '',
				'HP_ORDER' => '',
				'COLSPAN' => 2));

		set_page_message(tr('There are no available hosting plans for updates.'), 'info');
		return;
	}

	$tpl->assign('COLSPAN', $count);
	$i = 0;

	while (!$rs->EOF) {

		list(
			$hp_php,
			$hp_cgi,
			$hp_sub,
			$hp_als,
			$hp_mail,
			$hp_ftp,
			$hp_sql_db,
			$hp_sql_user,
			$hp_traff,
			$hp_disk,
			$hp_backup,
			$hp_dns,
			$hp_allowsoftware
		) = explode(";", $rs->fields['props']);
		
		if($hp_allowsoftware == "_yes_" && get_reseller_sw_installer($_SESSION['user_created_by']) == "no") {
			$rs->MoveNext();
		} else {
			$details = '';
			$warning_msgs = $error_msgs = array();
			
			if ($hp_php === '_yes_') {
				$details = tr('PHP Support: enabled') . "<br />";
				$php = "yes";
			} else {
				$details = tr('PHP Support: disabled') . "<br />";
				$php = "no";
				if ($current['domain_php'] == 'yes') {
					set_page_message(tr("You have PHP enabled, but the new hosting plan doesn't has this feature."), 'warning');
				}
			}

			if ($hp_cgi === '_yes_') {
				$cgi = "yes";
				$details .= tr('CGI Support: enabled') . "<br />";
			} else {
				$cgi = "no";
				$details .= tr('CGI Support: disabled') . "<br />";
	
				if ($current['domain_cgi'] == 'yes') {
					set_page_message(tr("You have CGI enabled, but the new hosting plan doesn't has this feature."), 'warning');
				}
			}

			if ($hp_dns === '_yes_') {
				$dns = "yes";
				$details .= tr('DNS Support: enabled') . "<br />";
			} else {
				$dns = "no";
				$details .= tr('DNS Support: disabled') . "<br />";
	
				if ($current['domain_dns'] == 'yes') {
					set_page_message(tr("You have DNS enabled, but the new hosting plan doesn't has this feature."), 'warning');
				}
			}
			
			if ($hp_allowsoftware === '_yes_') {
				$software = "yes";
				$details .= tr('i-MSCP application installer: enabled') . "<br />";
			} else {
				$software = "no";
				$details .= tr('i-MSCP application installer: disabled') . "<br />";
			}
			
			if ($current['domain_software_allowed'] == 'yes') {
				set_page_message(tr("You have the i-MSCP application installer enabled, but the new hosting plan doesn't has this feature."), 'warning');
			}

			$traffic = get_user_traffic($domain_id);
	
			$curr_value = $traffic[7] / 1048576; // convert disk usage to MB
	
			if (!check_update_current_value($curr_value, $hp_disk)) {
				set_page_message(tr('You have more disk space in use than the new hosting plan limits.'), 'error');
			}
	
			$hdd_usage = tr('Disk limit') . ": " . translate_limit_value($hp_disk, true) . "<br />";
	
			$curr_value = $traffic[10] / 1048576; // convert max. traffic to MB
	
			if (!check_update_current_value($curr_value, $hp_traff)) {
				set_page_message(tr('You did have more traffic than the new hosting plan limits.'), 'error');
			}
	
			$traffic_usage = tr('Traffic limit') . ": " . translate_limit_value($hp_traff, true);
	
			$curr_value = get_domain_running_als_cnt($domain_id);
	
			if (!check_update_current_value($curr_value, $hp_als)) {
				set_page_message(tr('You have more aliases in use than the new hosting plan limits'), 'error');
			}
	
			$details .= tr('Aliases') . ": " . translate_limit_value($hp_als) . "<br />";
	
			$curr_value = get_domain_running_sub_cnt($domain_id);
	
			if (!check_update_current_value($curr_value, $hp_sub)) {
				set_page_message(tr('You have more subdomains in use than the new hosting plan limits'), 'error');
			}
	
			$details .= tr('Subdomains') . ": " . translate_limit_value($hp_sub) . "<br />";
	
			$curr_value = get_domain_running_mail_acc_cnt($domain_id);
	
			if (!check_update_current_value($curr_value[0], $hp_mail)) {
				set_page_message(tr('You have more Email addresses in use than the new hosting plan limits.'), 'error');
			}
	
			$details .= tr('Emails') . ": " . translate_limit_value($hp_mail) . "<br />";
	
			$curr_value = get_domain_running_ftp_acc_cnt($domain_id);
	
			if (!check_update_current_value($curr_value[0], $hp_ftp)) {
				set_page_message(tr('You have more FTP accounts in use than the new hosting plan limits.'), 'error');
			}
	
			$details .= tr('FTPs') . ": " . translate_limit_value($hp_ftp) . "<br />";
	
			$curr_value = get_domain_running_sqld_acc_cnt($domain_id);
	
			if (!check_update_current_value($curr_value, $hp_sql_db)) {
				set_page_message(tr('You have more SQL databases in use than the new hosting plan limits.'), 'error');
			}
	
			$details .= tr('SQL Databases') . ": " . translate_limit_value($hp_sql_db) . "<br />";
	
			$curr_value = get_domain_running_sqlu_acc_cnt($domain_id);
	
			if (!check_update_current_value($curr_value, $hp_sql_user)) {
				set_page_message(tr('You have more SQL database users in use than the new hosting plan limits.'), 'error');
			}
	
			$details .= tr('SQL Users') . ": " . translate_limit_value($hp_sql_user) . "<br />";
	
			$details .= $hdd_usage . $traffic_usage;
	
			$price = $rs->fields['price'];
	
			if ($price == 0 || $price == '') {
				$price = tr('free of charge');
			} else {
				$price = $price . " " . $rs->fields['value'] . " " . $rs->fields['payment'];
			}
	
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
					$_SESSION['user_id'],
					$hp_mail, $hp_ftp, $hp_traff,
					$hp_sql_db, $hp_sql_user,
					$hp_als, $hp_sub, $hp_disk,
					$php, $cgi, $dns, $software
				)
			);
	
			if ($check->recordCount() == 0) {
	
				$link_purchase = '<a href="hosting_plan_update.php?'
					. $purchase_link.'='.$rs->fields['id']
					. '" class="linkdark">';
	
				if ($purchase_link == 'order_id' && count($error_msgs) > 0) {
					$link_purchase = tr('You cannot update to this hosting plan, see notices in text.');
					if (count($warning_msgs) > 0) {
						$warning_text = '<br /><br /><strong>'.tr('Warning:').'</strong><br />'.implode('<br />', $warning_msgs);
					} else {
						$warning_text = '';
					}
					$warning_text .= '<br /><br /><strong>'.tr('Caution:').'</strong><br />'.implode('<br />', $error_msgs);
				} elseif ($purchase_link == 'order_id' && count($warning_msgs) > 0) {
					$warning_text = '<br /><br /><strong>'.tr('Warning:').'</strong><br />'.implode('<br />', $warning_msgs);
					$link_purchase .= tr('I understand the warnings - Purchase!');
					$link_purchase .= '</a>';
				} else {
					$warning_text = '';
					$link_purchase .= '{TR_PURCHASE}</a>';
				}

				$tpl->assign(
					array(
						 'HP_NAME' => tohtml($rs->fields['name']),
						 'HP_DESCRIPTION' => tohtml($rs->fields['description']),
						 'HP_DETAILS' => $details . $warning_text,
						 'HP_COSTS' => tohtml($price),
						 'ID' => $rs->fields['id'],
						 'TR_PURCHASE' => $purchase_text,
						 'LINK' => $purchase_link,
						 'TR_HOSTING_PLANS' => $hp_title,
						 'ITHEM' => ($i % 2 == 0) ? 'content' : 'content2',
						 'LINK_PURCHASE' => $link_purchase
					)
				);

				$tpl->parse('HOSTING_PLANS', '.hosting_plans');
				$tpl->parse('HP_ORDER', '.hp_order');
				$i++;
			}
			$purchase_text = tr('Purchase');
			$purchase_link = 'order_id';
			$rs->moveNext();
		}
	}
	if ($i == 0) {
		$tpl->assign(
			array(
				 'HOSTING_PLANS' => '',
				 'HP_ORDER' => '',
				 'TR_HOSTING_PLANS' => $hp_title,
				 'COLSPAN' => '2'));

		set_page_message(tr('There are no available hosting plans for update.'), 'info');
	}
}

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('i-MSCP - Update hosting plan'),
		 'THEME_CHARSET' => tr('encoding'),
		 'ISP_LOGO' => layout_getUserLogo()));

/**
 * @todo the 2nd query has 2 identical tables in FROM-clause, is this OK?
 */
function add_new_order($tpl,$order_id, $user_id) {

	/** @var $cfg iMSCP_Config_Handler_FileCP_ */
	$cfg = iMSCP_Registry::get('config');

	// get domain id
	$query = "
		SELECT
			`domain_id`
		FROM
			`domain`
		WHERE
			`domain_admin_id` = ?
	";

	$rs = exec_query($query, $user_id);
	$domain_id = $rs->fields['domain_id'];

	// get current domain settings
	$query = "
		SELECT
			*
		FROM
			`domain`
		WHERE
			`domain_id` = ?
	";

	$rs = exec_query($query, $domain_id);
	$current = $rs->fetchRow();

	$query = "SELECT * FROM `hosting_plans` WHERE `id` = ?";

	$error_msgs = array();
	$rs = exec_query($query, $order_id);
	list($hp_php, $hp_cgi, $hp_sub, $hp_als, $hp_mail, $hp_ftp, $hp_sql_db, $hp_sql_user, $hp_traff, $hp_disk, $hp_backup, $hp_dns, $hp_allowsoftware) = explode(";", $rs->fields['props']);

	$traffic = get_user_traffic($domain_id);

	$curr_value = $traffic[7] / 1048576; // disk usage
	if (!check_update_current_value($curr_value, $hp_disk)) {
		set_page_message(tr('You have more disk space in use than the new hosting plan limits.'), 'error');
	}

	$curr_value = get_domain_running_als_cnt($domain_id);
	if (!check_update_current_value($curr_value, $hp_als)) {
		set_page_message(tr('You have more aliases in use than the new hosting plan limits.'), 'error');
	}

	$curr_value = get_domain_running_sub_cnt($domain_id);
	if (!check_update_current_value($curr_value, $hp_sub)) {
		set_page_message(tr('You have more subdomains in use than the new hosting plan limits.'), 'error');
	}

	$curr_value = get_domain_running_mail_acc_cnt($domain_id);
	if (!check_update_current_value($curr_value[0], $hp_mail)) {
		set_page_message(tr('You have more e-mail addresses in use than the new hosting plan limits.'), 'error');
	}

	$curr_value = get_domain_running_ftp_acc_cnt($domain_id);
	if (!check_update_current_value($curr_value[0], $hp_ftp)) {
		set_page_message(tr('You have more FTP accounts in use than the new hosting plan limits.'), 'error');
	}

	$curr_value = get_domain_running_sqld_acc_cnt($domain_id);
	if (!check_update_current_value($curr_value, $hp_sql_db)) {
		set_page_message(tr('You have more SQL databases in use than the new hosting plan limits.'), 'error');
	}

	$curr_value = get_domain_running_sqlu_acc_cnt($domain_id);
	if (!check_update_current_value($curr_value, $hp_sql_user)) {
		set_page_message(tr('You have more SQL database users in use than the new hosting plan limits.'), 'error');
	}

	if (Zend_Session::namespaceIsset('pageMessages')) {
		redirectTo('hosting_plan_update.php');
	}

	$date = time();
	$status = "update";
	$query = "
		INSERT INTO `orders`
			(`user_id`,
			`plan_id`,
			`date`,
			`domain_name`,
			`customer_id`,
			`fname`,
			`lname`,
			`firm`,
			`zip`,
			`city`,
			`state`,
			`country`,
			`email`,
			`phone`,
			`fax`,
			`street1`,
			`street2`,
			`status`)
		VALUES
			(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
	";

	exec_query($query, array(
		$_SESSION['user_created_by'], $order_id, $date, $_SESSION['user_logged'],
		$user_id, '', '', '', '', '', '', '', '', '', '', '', '', $status
	));

	set_page_message(tr('Your request for hosting plan update was registered.'), 'success');

	$query = "
		SELECT
			t1.`email` AS reseller_mail,
			t2.`email` AS user_mail
		FROM
			`admin` AS t1,
			`admin` AS t2
		WHERE
			t1.`admin_id` = ?
		AND
			t2.`admin_id` = ?
	";

	$rs = exec_query($query, array($_SESSION['user_created_by'], $_SESSION['user_id']));

	$to = $rs->fields['reseller_mail'];
	$from = $rs->fields['user_mail'];

	$headers = "From: " . $from . "\n";
	$headers .= "MIME-Version: 1.0\nContent-Type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 7bit\n";
	$headers .= "X-Mailer: i-MSCP auto mailer";

	$subject = tr("[i-MSCP OrderPanel] - You have an update order", true);



	$message = tr(
		"You have an update order for the account %s\n\nPlease login into your i-MSCP control panel at %s for more details",
		true,
		$_SESSION['user_logged'],
		$cfg->BASE_SERVER_VHOST_PREFIX . $cfg->BASE_SERVER_VHOST);

	$mail_result = mail($to, $subject, $message, $headers);
}

/**
 * @param $tpl
 * @param $order_id
 * @param $user_id
 * @return void
 */
function del_order($tpl, $order_id, $user_id)
{
	$query = "DELETE FROM `orders` WHERE `user_id` = ? AND `customer_id` = ?";

	exec_query($query, array($_SESSION['user_created_by'], $user_id));
	set_page_message(tr('Your request for hosting plan update was removed.'), 'success');
}

if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
	del_order($tpl, $_GET['delete_id'], $_SESSION['user_id']);
}

if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
	add_new_order($tpl, $_GET['order_id'], $_SESSION['user_id']);
}

gen_hp($tpl, $_SESSION['user_id']);
generateNavigation($tpl);

$tpl->assign(
	array(
		'TR_TITLE_MENU_UPDATE_HP' => tr('Update hosting plan'),
		'TR_LANGUAGE' => tr('Language'),
		'TR_SAVE' => tr('Save')));

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
