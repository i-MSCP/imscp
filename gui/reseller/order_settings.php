<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010 by i-msCP | http://i-mscp.net
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

check_login(__FILE__);

$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page', $cfg->RESELLER_TEMPLATE_PATH . '/order_settings.tpl');
$tpl->define_dynamic('logged_from', 'page');
// Table with orders
$tpl->define_dynamic('purchase_header', 'page');

$tpl->define_dynamic('purchase_footer', 'page');
$tpl->define_dynamic('page_message', 'page');

$tpl->assign(
	array(
		'TR_RESELLER_MAIN_INDEX_PAGE_TITLE' => tr('i-MSCP - Reseller/Order settings'),
		'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id'])
	)
);

/*
 * Functions
 */

function save_haf(&$tpl, &$sql) {
	$user_id = $_SESSION['user_id'];
	$header = $_POST['header'];
	$footer = $_POST['footer'];

	$query = "
		SELECT
			`id`
		FROM
			`orders_settings`
		WHERE
			`user_id` = ?
	";
	$rs = exec_query($sql, $query, $user_id);

	if ($rs->recordCount() !== 0) {
		// update query
		$query = "
			UPDATE
				`orders_settings`
			SET
				`header` = ?,
				`footer` = ?
			WHERE
				`user_id` = ?
		";

		$rs = exec_query($sql, $query, array($header, $footer, $user_id));
	} else {
		// create query
		$query = "
			INSERT INTO
				`orders_settings`(`user_id`, `header`, `footer`)
			VALUES
				(?, ?, ?)
		";

		$rs = exec_query($sql, $query, array($user_id, $header, $footer));
	}
}

// end of functions

/*
 *
 * static page messages.
 *
 */
if (isset($_POST['header']) && $_POST['header'] !== ''
	&& isset ($_POST['footer']) && $_POST['footer'] !== '') {
	save_haf($tpl, $sql);
}
gen_purchase_haf($tpl, $sql, $_SESSION['user_id'], true);

gen_reseller_mainmenu($tpl, $cfg->RESELLER_TEMPLATE_PATH . '/main_menu_orders.tpl');
gen_reseller_menu($tpl, $cfg->RESELLER_TEMPLATE_PATH . '/menu_orders.tpl');

gen_logged_from($tpl);

$coid = isset($cfg->CUSTOM_ORDERPANEL_ID) ? $cfg->CUSTOM_ORDERPANEL_ID : '';

$url = $cfg->BASE_SERVER_VHOST_PREFIX . $cfg->BASE_SERVER_VHOST . '/orderpanel/index.php?';
$url .= 'coid='.$coid;
$url .= '&amp;user_id=' . $_SESSION['user_id'];

$tpl->assign(
	array(
		'TR_MANAGE_ORDERS' => tr('Manage Orders'),
		'TR_APPLY_CHANGES' => tr('Apply changes'),
		'TR_HEADER' => tr('Header'),
		'TR_PREVIEW' => tr('Preview'),
		'TR_IMPLEMENT_INFO' => tr('Implementation URL'),
		'TR_IMPLEMENT_URL' => $url,
		'TR_FOOTER' => tr('Footer')
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if ($cfg->DUMP_GUI_DEBUG) {
	dump_gui_debug();
}
unset_messages();
