<?php
/**
 *  ispCP (OMEGA) - Virtual Hosting Control System | Omega Version
 *
 *  @copyright 	2001-2006 by moleSoftware GmbH
 *  @copyright 	2006-2007 by ispCP | http://isp-control.net
 *  @link 		http://isp-control.net
 *  @author		ispCP Team (2007)
 *
 *  @license
 *  This program is free software; you can redistribute it and/or modify it under
 *  the terms of the MPL General Public License as published by the Free Software
 *  Foundation; either version 1.1 of the License, or (at your option) any later
 *  version.
 *  You should have received a copy of the MPL Mozilla Public License along with
 *  this program; if not, write to the Open Source Initiative (OSI)
 *  http://opensource.org | osi@opensource.org
 **/


include '../include/ispcp-lib.php';

check_login();

$tpl = new pTemplate();

$tpl -> define_dynamic('page', $cfg['RESELLER_TEMPLATE_PATH'].'/rau1.tpl');

$tpl -> define_dynamic('page_message', 'page');

$tpl -> define_dynamic('logged_from', 'page');

$tpl -> define_dynamic('add_user', 'page');

$tpl -> define_dynamic('hp_entry', 'page');

$tpl -> define_dynamic('personalize', 'page');

$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(
                array(
                        'TR_CLIENT_CHANGE_PERSONAL_DATA_PAGE_TITLE' => tr('ISPCP - Users/Add user'),
                        'THEME_COLOR_PATH' => "../themes/$theme_color",
                        'THEME_CHARSET' => tr('encoding'),
                        'ISPCP_LICENSE' => $cfg['ISPCP_LICENSE'],
						'ISP_LOGO' => get_logo($_SESSION['user_id']),
                     )
              );

/*
 *
 * static page messages.
 *
 */

gen_reseller_mainmenu($tpl, $cfg['RESELLER_TEMPLATE_PATH'].'/main_menu_manage_users.tpl');
gen_reseller_menu($tpl, $cfg['RESELLER_TEMPLATE_PATH'].'/menu_manage_users.tpl');

gen_logged_from($tpl);

$tpl -> assign(
                array(
                        'TR_ADD_USER' => tr('Add user'),
						'TR_CORE_DATA' => tr('Core data'),
						'TR_DOMAIN_NAME' => tr('Domain name'),
						'TR_CHOOSE_HOSTING_PLAN' => tr('Choose hosting plan'),
						'TR_PERSONALIZE_TEMPLATE' => tr('Personalize template'),
						'TR_YES' => tr('yes'),
						'TR_NO' => tr('no'),
						'TR_NEXT_STEP' => tr('Next step')
                     )
              );

get_hp_data_list($tpl, $_SESSION['user_id']);

if (isset($_POST['uaction'])) {

    if(!check_user_data($tpl))
		get_data_au1_page($tpl);

}else
	get_empty_au1_page($tpl);


gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

unset_messages();
//
// Function declaration path
//

// Check correction of entered user's data
function check_user_data(&$tpl)
{
    global $dmn_name;			// Domain name
    global $dmn_chp;			// choosed hosting plan;
	global $dmn_pt;
	global $cfg;
	// personal template

	$even_txt	= "_off_";


	if(isset($_POST['dmn_name']))

		$dmn_name = strtolower(clean_input($_POST['dmn_name']));
		$dmn_name = get_punny($dmn_name);

	if(isset($_POST['dmn_tpl']))
		$dmn_chp  = $_POST['dmn_tpl'];

	if(isset($_POST['chtpl']))
		$dmn_pt	  = $_POST['chtpl'];

    if (!ispcp_domain_check($dmn_name)) {

        $even_txt = tr('Wrong domain name syntax!');

    } else if (ispcp_domain_exists($dmn_name, $_SESSION['user_id'])) {

        $even_txt = tr('Domain with that name already exists on the system!');

    }

	// we have plans only for admins
	if (isset($cfg['HOSTING_PLANS_LEVEL']) && $cfg['HOSTING_PLANS_LEVEL'] === 'admin'){
		$dmn_pt = '_no_';
	}


    if ($even_txt != '_off_')
	{// There are wrong input data
        set_page_message($even_txt);
		return false;
    } else if ($dmn_pt == '_yes_' || !isset($_POST['dmn_tpl'])) {

		// send through the session the data
        $_SESSION['dmn_name']	= $dmn_name;
		$_SESSION['dmn_tpl']	= $dmn_chp;
		$_SESSION['chtpl']		= $dmn_pt;
		$_SESSION['step_one']	= "_yes_";

        Header("Location: rau2.php");

        die();

    } else {

        // send through the session the data
        $_SESSION['dmn_name']	= $dmn_name;
		$_SESSION['dmn_tpl']	= $dmn_chp;
		$_SESSION['chtpl']		= $dmn_pt;
		$_SESSION['step_one']	= "_yes_";

         Header("Location: rau3.php");

        die();

    }

}// End of check_user_data()



// Show empty page
function get_empty_au1_page(&$tpl)
{
	$tpl -> assign(
				array(
						'DMN_NAME_VALUE' => '',
						'CH1' => 'selected',
						'CH2' => '',
						'CH3' => '',
						'CH4' => '',
						'CHTPL1_VAL' => '',
						'CHTPL2_VAL' => 'checked'
					)
			);

	$tpl -> assign('MESSAGE', '');

}//End of get_empty_au1_page()

// Show first page of add user with data
function get_data_au1_page(&$tpl)
{
	global $dmn_name;			// Domain name
    global $dmn_chp;			// choosed hosting plan;
	global $dmn_pt;				// personal template

	$tpl -> assign(
				array(
						'DMN_NAME_VALUE' => $dmn_name,
						'CH'.$dmn_chp => 'selected',
						'CHTPL1_VAL' => '',
						'CHTPL2_VAL' => ''
					)
			);

	if("_yes_" === $dmn_pt)
		$tpl -> assign(
					array(
							'CHTPL1_VAL' => 'checked'
						)
				);
	else
		$tpl -> assign(
					array(
						'CHTPL2_VAL' => 'checked'
						)
				);

}//End of get_data_au1_page()

// Get list with hosting plan for selection
function get_hp_data_list(&$tpl, $reseller_id)
{
	global $sql, $cfg;

	  if (isset($cfg['HOSTING_PLANS_LEVEL']) && $cfg['HOSTING_PLANS_LEVEL'] === 'admin'){
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
	$tpl -> assign('PERSONALIZE', '');

	if ($rs -> RecordCount() == 0) {
		set_page_message(tr('You have no hosting plans. Please contact your system administrator.'));
		$tpl -> assign('ADD_USER', '');
		$tpl -> assign('ADD_FORM', '');
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
		while ($data = $rs->FetchRow()) {

			$tpl -> assign(
            			array(
							'HP_NAME' => $data['name'],
							'CHN' => $data['id']
						)
			);
			$tpl -> parse('HP_ENTRY', '.hp_entry');

		}
	}
	else
	{
			//set_page_message(tr('You have no hosting plans. Please add first hosting plan or contact your system administrator.'));
			$tpl -> assign('ADD_USER', '');

	}

}// End of get_hp_data_list()


?>
