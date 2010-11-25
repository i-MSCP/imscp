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
 * @author		i-MSCP Team
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

/**
 * @var $cfg iMSCP_Config_Handler_File
 */
$cfg = iMSCP_Registry::get('Config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page', $cfg->RESELLER_TEMPLATE_PATH . '/user_add1.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('add_user', 'page');
$tpl->define_dynamic('hp_entry', 'page');
$tpl->define_dynamic('personalize', 'page');

$tpl->assign(
	array(
		'TR_CLIENT_CHANGE_PERSONAL_DATA_PAGE_TITLE'	=> tr('i-MSCP - Users/Add user'),
		'THEME_COLOR_PATH'							=> "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET'								=> tr('encoding'),
		'ISP_LOGO'									=> get_logo($_SESSION['user_id']),
	)
);

/**
 * static page messages.
 */

gen_reseller_mainmenu($tpl, $cfg->RESELLER_TEMPLATE_PATH . '/main_menu_users_manage.tpl');
gen_reseller_menu($tpl, $cfg->RESELLER_TEMPLATE_PATH . '/menu_users_manage.tpl');

gen_logged_from($tpl);

$tpl->assign(
	array(
		'TR_ADD_USER'				=> tr('Add user'),
		'TR_CORE_DATA'				=> tr('Core data'),
		'TR_DOMAIN_NAME'			=> tr('Domain name'),
		'TR_DOMAIN_EXPIRE'			=> tr('Domain expire date'),
		'TR_EXPIRE_CHECKBOX'		=> tr('or Check for <strong>never Expire</strong>'),
		'TR_CHOOSE_HOSTING_PLAN'	=> tr('Choose hosting plan'),
		'TR_PERSONALIZE_TEMPLATE'	=> tr('Personalise template'),
		'TR_YES'					=> tr('yes'),
		'TR_NO'						=> tr('no'),
		'TR_NEXT_STEP'				=> tr('Next step'),
		'TR_DMN_HELP'				=> tr("You do not need 'www.' i-MSCP will add it on its own.")
	)
);

if (isset($_POST['uaction'])) {

	if (!check_user_data()) {
		get_data_au1_page($tpl);
	}
} else {
	get_empty_au1_page($tpl);
}

get_hp_data_list($tpl, $_SESSION['user_id']);
gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if ($cfg->DUMP_GUI_DEBUG) {
	dump_gui_debug();
}
unset_messages();

// Function declaration path

/**
 * Check correction of entered users data
 */
function check_user_data() {

	global $dmn_name, $dmn_expire, $neverexpire, $dmn_chp, $dmn_pt, $validation_err_msg;

	/**
 	 * @var $cfg iMSCP_Config_Handler_File
 	 */
	$cfg = iMSCP_Registry::get('Config');

	/**
 	 * @var $sql iMSCP_Database
 	 */
	$sql = iMSCP_Registry::get('Db');

	// personal template
	$even_txt = '';

	if (isset($_POST['dmn_name'])) {
		$dmn_name = strtolower(trim($_POST['dmn_name']));
	}

	if (isset($_POST['dmn_expire'])) {
		$dmn_expire = $_POST['dmn_expire'];
	}

	if (isset($_POST['neverexpire'])) {
		$neverexpire = $_POST['neverexpire'];
	}

	if (isset($_POST['dmn_tpl'])) {
		$dmn_chp = $_POST['dmn_tpl'];
	}

	if (isset($_POST['chtpl'])) {
		$dmn_pt = $_POST['chtpl'];
	}

	// Check if input string is a valid domain names
	if (!validates_dname($dmn_name)) {
		set_page_message($validation_err_msg);
		return false;
	}

	// Should be perfomed after domain names syntax validation now
	$dmn_name = encode_idna($dmn_name);

	if (imscp_domain_exists($dmn_name, $_SESSION['user_id'])) {
		$even_txt = tr('Domain with that name already exists on the system!');
	} else if ($dmn_name == $cfg->BASE_SERVER_VHOST) {
		$even_txt = tr('Master domain cannot be used!');
	}

	// we have plans only for admins
	if (isset($cfg->HOSTING_PLANS_LEVEL) && $cfg->HOSTING_PLANS_LEVEL == 'admin') {
		$dmn_pt = '_no_';
	}

	if (!empty($even_txt)) { // There are wrong input data
		set_page_message($even_txt);
		return false;
	} else if ($dmn_pt == '_yes_' || !isset($_POST['dmn_tpl'])) {
		// send through the session the data
		$_SESSION['dmn_name']	= $dmn_name;
		$_SESSION['dmn_expire']	= $dmn_expire;
		$_SESSION['neverexpire']= $neverexpire;
		$_SESSION['dmn_tpl']	= $dmn_chp;
		$_SESSION['chtpl']		= $dmn_pt;
		$_SESSION['step_one']	= "_yes_";

		user_goto('user_add2.php');
	} else {
		// check if reseller limits are not touched
		$ehp_error = ''; // fill dummy to satisfy warning...
		if (reseller_limits_check($sql, $ehp_error, $_SESSION['user_id'], $dmn_chp)) {
			// send through the session the data
			$_SESSION['dmn_name']	= $dmn_name;
			$_SESSION['dmn_expire']	= $dmn_expire;
			$_SESSION['neverexpire']= $neverexpire;
			$_SESSION['dmn_tpl']	= $dmn_chp;
			$_SESSION['chtpl']		= $dmn_pt;
			$_SESSION['step_one']	= "_yes_";

			user_goto('user_add3.php');
		} else {
			set_page_message(tr("Hosting plan values exceed reseller maximum values!"));
			return false;
		}
	}
} // End of check_user_data()

/**
 * Show empty page
 */
function get_empty_au1_page(&$tpl) {

	/**
	 * @var $cfg iMSCP_Config_Handler_File
	 */
	$cfg = iMSCP_Registry::get('Config');

	$tpl->assign(
		array(
			'DMN_NAME_VALUE'	=> '',
			'CHTPL1_VAL'		=> '',
			'CHTPL2_VAL'		=> $cfg->HTML_CHECKED
		)
	);

	$tpl->assign('MESSAGE', '');
} // End of get_empty_au1_page()

/**
 * Show first page of add user with data
 */
function get_data_au1_page($tpl) {

	global $dmn_name, $dmn_pt;

	/**
	 * @var $cfg iMSCP_Config_Handler_File
	 */
	$cfg = iMSCP_Registry::get('Config');

	$tpl->assign(
		array(
			'DMN_NAME_VALUE' => tohtml($dmn_name),
			'CHTPL1_VAL' => $dmn_pt === "_yes_" ? $cfg->HTML_CHECKED : '',
			'CHTPL2_VAL' => $dmn_pt === "_yes_" ? '' : $cfg->HTML_CHECKED,
		)
	);
} // End of get_data_au1_page()

/**
 * Get list with hosting plan for selection
 */
function get_hp_data_list($tpl, $reseller_id) {

	global $dmn_chp;

	/**
	 * @var $cfg iMSCP_Config_Handler_File
	 */
	$cfg = iMSCP_Registry::get('Config');

	/**
	 * @var $sql iMSCP_Database
	 */
	$sql = iMSCP_Registry::get('Db');


	if (isset($cfg->HOSTING_PLANS_LEVEL)
		&& $cfg->HOSTING_PLANS_LEVEL === 'admin') {
		$query = "
			SELECT
				t1.`id`, t1.`reseller_id`, t1.`name`, t1.`props`, t1.`status`, t2.`admin_id`, t2.`admin_type`
			FROM
				`hosting_plans` AS t1, `admin` AS t2
			WHERE
				t2.`admin_type` = ?
			AND
				t1.`reseller_id` = t2.`admin_id`
			AND
				t1.`status` = 1
			ORDER BY
				t1.`name`
		";

		$rs = exec_query($sql, $query, 'admin');
		$tpl->assign('PERSONALIZE', '');

		if ($rs->recordCount() == 0) {
			set_page_message(tr('You have no hosting plans. Please contact your system administrator.'));
			$tpl->assign('ADD_USER', '');
			$tpl->assign('ADD_FORM', '');
		}

	} else {
		$query = "
			SELECT
				`id`, `name`, `props`, `status`
			FROM
				`hosting_plans`
			WHERE
				`reseller_id` = ?
			ORDER BY
				`name`
		";

		$rs = exec_query($sql, $query, $reseller_id);
	}

	if (0 !== $rs->rowCount()) { // There are data
		$orders_count = 0;
		while (($data = $rs->fetchRow())) {
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
			) = explode(";", $data['props']);
			
			if($hp_allowsoftware == "_no_" || $hp_allowsoftware == "" || $hp_allowsoftware == "_yes_" && get_reseller_sw_installer($reseller_id) == "yes") {
				$orders_count++;
				$dmn_chp = isset($dmn_chp) ? $dmn_chp : $data['id'];
				$tpl->assign(
						array(
							'HP_NAME'			=> tohtml($data['name']),
							'CHN'				=> $data['id'],
							'CH'.$data['id']	=> ($data['id'] == $dmn_chp) ? $cfg->HTML_SELECTED : ''
						)
				);
	
				$tpl->parse('HP_ENTRY', '.hp_entry');
			}
		}
		if ($orders_count == 0) {
			$tpl->assign('ADD_USER', '');
		}

	} else {
		$tpl->assign('ADD_USER', '');
	}
} // End of get_hp_data_list()
