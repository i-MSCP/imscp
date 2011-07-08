<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010 by i-MSCP | http://i-mscp.net
 * @version 	SVN: $Id$
 * @link 		http://i-mscp.net
 * @author 		ispCP Team
 * @author 		i-MSCP Team
 *
 * @license
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
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 * Portions created by the i-MSCP Team are Copyright (C) 2010 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

require '../include/imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login(__FILE__);

$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();

$interfaces=new iMSCP_NetworkCard();

$tpl->define_dynamic('page', $cfg->ADMIN_TEMPLATE_PATH . '/ip_manage.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('hosting_plans', 'page');
$tpl->define_dynamic('ip_row', 'page');
$tpl->define_dynamic('card_list', 'page');
$tpl->define_dynamic('ip_delete_show', 'ip_row');
$tpl->define_dynamic('ip_delete_link', 'ip_row');

$tpl->assign(
	array(
		'TR_ADMIN_IP_MANAGE_PAGE_TITLE'	=> tr('i-MSCP - Admin/IP manage'),
		'THEME_COLOR_PATH'				=> "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET'					=> tr('encoding'),
		'ISP_LOGO'						=> get_logo($_SESSION['user_id'])
	)
);

function gen_ip_action($ip_id, $status) {

	$cfg = iMSCP_Registry::get('config');

	if ($status == $cfg->ITEM_OK_STATUS) {
		return array(tr('Remove IP'), 'ip_delete.php?delete_id=' . $ip_id);
	} else {
		return array(tr('N/A'), '#');
	}
}

function show_IPs($tpl) {

	$cfg = iMSCP_Registry::get('config');

	$query = "
		SELECT
			*
		FROM
			`server_ips`
	";

	$rs = exec_query($query);

	$row = 1;
	$single = false;

	if ($rs->recordCount() < 2) {
		$single = true;
	}

    if($rs->recordCount() > 0) {
        while (!$rs->EOF) {
            $tpl->assign('IP_CLASS', ($row++ % 2 == 0) ? 'content' : 'content2');

            list($ip_action, $ip_action_script) = gen_ip_action($rs->fields['ip_id'], $rs->fields['ip_status']);

            $tpl->assign(
                array(
                     'IP' => $rs->fields['ip_number'],
                     'DOMAIN' => tohtml($rs->fields['ip_domain']),
                     'ALIAS' => tohtml($rs->fields['ip_alias']),
                     'NETWORK_CARD' => ($rs->fields['ip_card'] === NULL) ? ''
                         : tohtml($rs->fields['ip_card'])
                )
            );

            if ($single == true) {
                $tpl->assign(
                    array(
                         'IP_DELETE_LINK' => '',
                         'IP_ACTION' => tr('N/A')
                    )
                );
                $tpl->parse('IP_DELETE_SHOW', 'ip_delete_show');
            } else {
                $tpl->assign(
                    array(
                         'IP_DELETE_SHOW' => '',
                         'IP_ACTION' => ($cfg->BASE_SERVER_IP == $rs->fields['ip_number'])
                             ? tr('N/A') : $ip_action,
                         'IP_ACTION_SCRIPT' => ($cfg->BASE_SERVER_IP == $rs->fields['ip_number'])
                             ? '#' : $ip_action_script
                    )
                );
                $tpl->parse('IP_DELETE_LINK', 'ip_delete_link');
            }

            $tpl->parse('IP_ROW', '.ip_row');

            $rs->moveNext();
        } // end while
    } else { // Can occur only if the 'server_ips' database table was dropped
        $tpl->assign('IP_AVAILABLE', '');
        set_page_message(tr("No IP's available"), 'warning');
    }
}

function add_ip($tpl) {

	global $ip_number, $domain, $alias, $ip_card;
	$cfg = iMSCP_Registry::get('config');

	if (isset($_POST['uaction']) && $_POST['uaction'] === 'add_ip') {
		if (check_user_data()) {

			$query = "
				INSERT INTO `server_ips`
					(`ip_number`, `ip_domain`, `ip_alias`, `ip_card`,
					`ip_ssl_domain_id`, `ip_status`)
				VALUES
					(?, ?, ?, ?, ?, ?)
			";

			exec_query($query, array($ip_number, htmlspecialchars($domain, ENT_QUOTES, "UTF-8"),
			htmlspecialchars($alias, ENT_QUOTES, "UTF-8"), htmlspecialchars($ip_card, ENT_QUOTES, "UTF-8"), NULL, $cfg->ITEM_ADD_STATUS));

			send_request();

			set_page_message(tr('New IP was added!'), 'success');

			write_log("{$_SESSION['user_logged']}: adds new IPv4 address: {$ip_number}!", E_USER_NOTICE);

			$sucess = true;
		}
	}

	if (!isset($sucess) && isset($_POST['ip_number_1'])) {
		$tpl->assign(
			array(
				'VALUE_IP1'		=> tohtml($_POST['ip_number_1']),
				'VALUE_IP2'		=> tohtml($_POST['ip_number_2']),
				'VALUE_IP3'		=> tohtml($_POST['ip_number_3']),
				'VALUE_IP4'		=> tohtml($_POST['ip_number_4']),
				'VALUE_DOMAIN'	=> clean_input($_POST['domain'], true),
				'VALUE_ALIAS'	=> clean_input($_POST['alias'], true),
			)
		);
	} else {
		$tpl->assign(
			array(
				'VALUE_IP1'		=> '',
				'VALUE_IP2'		=> '',
				'VALUE_IP3'		=> '',
				'VALUE_IP4'		=> '',
				'VALUE_DOMAIN'	=> '',
				'VALUE_ALIAS'	=> '',
			)
		);
	}
}

function check_user_data() {
	global $ip_number, $interfaces;

	$ip_number = trim($_POST['ip_number_1'])
		. '.' . trim($_POST['ip_number_2'])
		. '.' . trim($_POST['ip_number_3'])
		. '.' . trim($_POST['ip_number_4']);

	global $domain, $alias, $ip_card;

	$domain = clean_input($_POST['domain']);
	$alias = clean_input($_POST['alias']);
	$ip_card = clean_input($_POST['ip_card']);

	$err_msg = '_off_';

	if (filter_var($ip_number, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
		$err_msg = tr('Wrong IP number!');
	} elseif ($domain == '') {
		$err_msg = tr('Please specify domain!');
	} elseif ($alias == '') {
		$err_msg = tr('Please specify alias!');
	} elseif (IP_exists()) {
		$err_msg = tr('This IP already exist!');
	} elseif (!in_array($ip_card, $interfaces->getAvailableInterface())) {
		$err_msg = tr('Please select nework interface!');
	}

	if ($err_msg == '_off_') {
		return true;
	} else {
		set_page_message($err_msg, 'error');
		return false;
	}
}

function IP_exists() {

	global $ip_number;

	$query = "
		SELECT
			*
		FROM
			`server_ips`
		WHERE
			`ip_number` = ?
	";

	$rs = exec_query($query, $ip_number);

	if ($rs->rowCount() == 0) {
		return false;
	}
	return true;
}

function show_Network_Cards($tpl, $interfaces) {

	if ($interfaces->getErrors() != '') {
		set_page_message($interfaces->getErrors(), 'error');
	}
	if ($interfaces->getAvailableInterface() != array()) {
		foreach ($interfaces->getAvailableInterface() as $interface) {
			$tpl->assign(
				array(
					'NETWORK_CARDS'	=> $interface
				)
			);
			$tpl->parse('CARD_LIST', '.card_list');
		}
	} else {
		$tpl->assign(
			array(
				'NETWORK_CARDS'	=> ''
			)
		);
		$tpl->parse('CARD_LIST', '.card_list');
	}
}

/*
 *
 * static page messages.
 *
 */
gen_admin_mainmenu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/main_menu_settings.tpl');
gen_admin_menu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/menu_settings.tpl');

show_Network_Cards($tpl, $interfaces);

add_ip($tpl);

show_IPs($tpl);

$tpl->assign(
	array(
		'MANAGE_IPS'		=> tr('Manage IPs'),
		'TR_AVAILABLE_IPS'	=> tr('Available IPs'),
		'TR_IP'				=> tr('IP'),
		'TR_DOMAIN'			=> tr('Domain'),
		'TR_ALIAS'			=> tr('Alias'),
		'TR_ACTION'			=> tr('Action'),
		'TR_NETWORK_CARD'	=> tr('Network interface'),
		'TR_ADD'			=> tr('Add'),
		'TR_ADD_NEW_IP'		=> tr('Add new IP'),
		'TR_MESSAGE_DELETE'	=> tr('Are you sure you want to delete this IP: %s?', true, '%s')
	)
);

generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(
    iMSCP_Events::onAdminScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
