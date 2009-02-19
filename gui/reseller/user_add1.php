<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
 *
 * @license
 *   This program is free software; you can redistribute it and/or modify it under
 *   the terms of the MPL General Public License as published by the Free Software
 *   Foundation; either version 1.1 of the License, or (at your option) any later
 *   version.
 *   You should have received a copy of the MPL Mozilla Public License along with
 *   this program; if not, write to the Open Source Initiative (OSI)
 *   http://opensource.org | osi@opensource.org
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::get('RESELLER_TEMPLATE_PATH') . '/user_add1.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('add_user', 'page');
$tpl->define_dynamic('hp_entry', 'page');
$tpl->define_dynamic('personalize', 'page');

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
		array(
			'TR_CLIENT_CHANGE_PERSONAL_DATA_PAGE_TITLE' => tr('ispCP - Users/Add user'),
			'THEME_COLOR_PATH' => "../themes/$theme_color",
			'THEME_CHARSET' => tr('encoding'),
			'ISP_LOGO' => get_logo($_SESSION['user_id']),
		)
	);

/*
 *
 * static page messages.
 *
 */

gen_reseller_mainmenu($tpl, Config::get('RESELLER_TEMPLATE_PATH') . '/main_menu_users_manage.tpl');
gen_reseller_menu($tpl, Config::get('RESELLER_TEMPLATE_PATH') . '/menu_users_manage.tpl');

gen_logged_from($tpl);

$tpl->assign(
		array(
			'TR_ADD_USER' => tr('Add user'),
			'TR_CORE_DATA' => tr('Core data'),
			'TR_DOMAIN_NAME' => tr('Domain name'),
			'TR_CHOOSE_HOSTING_PLAN' => tr('Choose hosting plan'),
			'TR_PERSONALIZE_TEMPLATE' => tr('Personalise template'),
			'TR_YES' => tr('yes'),
			'TR_NO' => tr('no'),
			'TR_NEXT_STEP' => tr('Next step'),
			'TR_DMN_HELP' => tr("You do not need 'www.' ispCP will add it on its own.")
			)
	);

get_hp_data_list($tpl, $_SESSION['user_id']);

if (isset($_POST['uaction'])) {
	if (!check_user_data())
		get_data_au1_page($tpl);
} else {
	get_empty_au1_page($tpl);
}

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG'))
	dump_gui_debug();

unset_messages();

// Function declaration path

// Check correction of entered user's data
function check_user_data() {
	global $dmn_name; // Domain name
	global $dmn_chp; // choosed hosting plan;
	global $dmn_pt;
	$sql = Database::getInstance();
	// personal template
	$even_txt = "";

	if (isset($_POST['dmn_name'])) {
		$dmn_name = strtolower(trim($_POST['dmn_name']));
		$dmn_name = encode_idna($dmn_name);
	}
	if (isset($_POST['dmn_tpl']))
		$dmn_chp = $_POST['dmn_tpl'];

	if (isset($_POST['chtpl']))
		$dmn_pt = $_POST['chtpl'];

	if (!chk_dname($dmn_name)) {
		$even_txt = tr('Wrong domain name syntax!');
	} else if (ispcp_domain_exists($dmn_name, $_SESSION['user_id'])) {
		$even_txt = tr('Domain with that name already exists on the system!');
	} else if ($dmn_name == Config::get('BASE_SERVER_VHOST')) {
		$even_txt = tr('Master domain cannot be used!');
	}
	// we have plans only for admins
	if (Config::exists('HOSTING_PLANS_LEVEL') && Config::get('HOSTING_PLANS_LEVEL') === 'admin') {
		$dmn_pt = '_no_';
	}

	if (!empty($even_txt)) { // There are wrong input data
		set_page_message($even_txt);
		return false;
	}
	else if ($dmn_pt == '_yes_' || !isset($_POST['dmn_tpl'])) {
		// send through the session the data
		$_SESSION['dmn_name'] = $dmn_name;
		$_SESSION['dmn_tpl'] = $dmn_chp;
		$_SESSION['chtpl'] = $dmn_pt;
		$_SESSION['step_one'] = "_yes_";

		header("Location: user_add2.php");
		die();
	} else {
		// check if reseller timits are not touched
		if (reseller_limits_check($sql, $ehp_error, $_SESSION['user_id'], $dmn_chp)) {
			// send through the session the data
			$_SESSION['dmn_name'] = $dmn_name;
			$_SESSION['dmn_tpl'] = $dmn_chp;
			$_SESSION['chtpl'] = $dmn_pt;
			$_SESSION['step_one'] = "_yes_";

			header("Location: user_add3.php");
			die();
		}
		else {
			set_page_message(tr("Hosting plan values exceed reseller maximum values!"));
			return false;
		}
	}
} // End of check_user_data()

// Show empty page
function get_empty_au1_page(&$tpl) {
	$tpl->assign(
		array('DMN_NAME_VALUE' => '',
			'CH1' => 'selected="selected"',
			'CH2' => '',
			'CH3' => '',
			'CH4' => '',
			'CHTPL1_VAL' => '',
			'CHTPL2_VAL' => 'checked="checked"'
			)
		);
	$tpl->assign('MESSAGE', '');
} //End of get_empty_au1_page()

// Show first page of add user with data
function get_data_au1_page(&$tpl) {
	global $dmn_name; // Domain name
	global $dmn_chp; // choosed hosting plan;
	global $dmn_pt; // personal template

	$tpl->assign(
		array('DMN_NAME_VALUE' => $dmn_name,
			'CH' . $dmn_chp => 'selected="selected"',
			'CHTPL1_VAL' => '',
			'CHTPL2_VAL' => ''
			)
		);

	if ("_yes_" === $dmn_pt)
		$tpl->assign(
			array('CHTPL1_VAL' => 'checked="checked"'
				)
			);
	else
		$tpl->assign(
			array('CHTPL2_VAL' => 'checked="checked"'
				)
			);
} //End of get_data_au1_page()

// Get list with hosting plan for selection
function get_hp_data_list(&$tpl, $reseller_id) {
	$sql = Database::getInstance();

	if (Config::exists('HOSTING_PLANS_LEVEL') && Config::get('HOSTING_PLANS_LEVEL') === 'admin') {
		$query = <<<SQL_QUERY
        SELECT
			t1.id,
			t1.reseller_id,
			t1.name,
			t1.props,
			t1.status,
			t2.admin_id,
			t2.admin_type
        FROM
            hosting_plans AS t1,
			admin AS t2
        WHERE
            t2.admin_type = ?
		  AND
			t1.reseller_id = t2.admin_id
		  AND
			t1.status = 1
        ORDER BY
            t1.name
SQL_QUERY;
		$rs = exec_query($sql, $query, array('admin'));
		$tpl->assign('PERSONALIZE', '');

		if ($rs->RecordCount() == 0) {
			set_page_message(tr('You have no hosting plans. Please contact your system administrator.'));
			$tpl->assign('ADD_USER', '');
			$tpl->assign('ADD_FORM', '');
		}
	} else {
		$query = <<<SQL_QUERY
		SELECT
			id,
			name,
			props,
			status
        FROM
            hosting_plans
        WHERE
            reseller_id = ?
        ORDER BY
            name
SQL_QUERY;
		$rs = exec_query($sql, $query, array($reseller_id));
	}

	/*
	$query = "SELECT name, id FROM hosting_plans WHERE reseller_id=?;";

	$res = exec_query($sql, $query, array($reseller_id));
	*/
	if (0 !== $rs->RowCount()) { // There are data
		while (($data = $rs->FetchRow())) {
			$tpl->assign(
				array('HP_NAME' => $data['name'],
					'CHN' => $data['id']
					)
				);
			$tpl->parse('HP_ENTRY', '.hp_entry');
		}
	} else {
		// set_page_message(tr('You have no hosting plans. Please add first hosting plan or contact your system administrator.'));
		$tpl->assign('ADD_USER', '');
	}
} // End of get_hp_data_list()

?>