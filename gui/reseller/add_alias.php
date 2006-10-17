<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2006 by moleSoftware		            		|
//  |			http://vhcs.net | http://www.molesoftware.com		           		|
//  |                                                                               |
//  | This program is free software; you can redistribute it and/or                 |
//  | modify it under the terms of the MPL General Public License                   |
//  | as published by the Free Software Foundation; either version 1.1              |
//  | of the License, or (at your option) any later version.                        |
//  |                                                                               |
//  | You should have received a copy of the MPL Mozilla Public License             |
//  | along with this program; if not, write to the Open Source Initiative (OSI)    |
//  | http://opensource.org | osi@opensource.org								    |
//  |                                                                               |
//   -------------------------------------------------------------------------------



include '../include/vhcs-lib.php';

check_login();

$tpl = new pTemplate();

$tpl -> define_dynamic('page', $cfg['RESELLER_TEMPLATE_PATH'].'/add_alias.tpl');

$tpl -> define_dynamic('page_message', 'page');

$tpl -> define_dynamic('logged_from', 'page');

$tpl -> define_dynamic('user_entry', 'page');

$tpl -> define_dynamic('ip_entry', 'page');

$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(
                array(
                        'THEME_COLOR_PATH' => "../themes/$theme_color",
                        'THEME_CHARSET' => tr('encoding'),
                        'VHCS_LICENSE' => $cfg['VHCS_LICENSE'],
						'ISP_LOGO' => get_logo($_SESSION['user_id']),
                     )
              );


/*
 *
 * static page messages.
 *
 */



gen_reseller_menu($tpl, $cfg['RESELLER_TEMPLATE_PATH'].'/menu_manage_users.tpl');

gen_logged_from($tpl);

$tpl -> assign(
                array(
						'TR_CLIENT_ADD_ALIAS_PAGE_TITLE' => tr('VHCS Reseller : Add Alias'),
						'TR_MANAGE_DOMAIN_ALIAS' => tr('Manage domain alias'),
						'TR_ADD_ALIAS' => tr('Add domain alias'),
						'TR_DOMAIN_NAME' => tr('Domain name'),
						'TR_DOMAIN_ACCOUNT' => tr('User account'),
						'TR_MOUNT_POINT' => tr('Directory mount point'),
						'TR_DOMAIN_IP' => tr('Domain IP'),
						'TR_FORWARD' => tr('Forward to URL'),
						'TR_ADD' => tr('Add alias'),

                     )
              );

$err_txt = '_off_';
if (isset($_POST['uaction']) && $_POST['uaction'] === 'add_alias') {


	add_domain_alias($sql, $err_txt);

} else {
// Init fileds
	init_empty_data();
	$tpl-> assign("PAGE_MESSAGE", "");
}

gen_al_page($tpl, $_SESSION['user_id']);
gen_page_msg($tpl, $err_txt);

//gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();





//
// Begin function declaration lines
//

function init_empty_data()
{
	global $cr_user_id, $alias_name, $domain_ip, $forward, $mount_point;

	$cr_user_id = "";
	$alias_name	= "";
	$domain_ip	= "";
	$forward	= "";
	$mount_point= "";

}//End of init_empty_data()


// Show data fiels
function gen_al_page(&$tpl, $reseller_id)
{
	global $cr_user_id, $alias_name, $domain_ip, $forward, $mount_point;

	if (isset($_POST['forward']))
	{
		$forward = clean_input($_POST['forward']);
	}
	else
	{
		$forward = 'no';
	}
	$tpl -> assign(
				array(
						'DOMAIN' => $alias_name,
						'MP' => $mount_point,
						'FORWARD' => $forward
					)
			);

	generate_ip_list($tpl, $reseller_id);
	gen_users_list($tpl, $reseller_id);
}// End of gen_al_page()

function add_domain_alias(&$sql, &$err_al)
{
	global $cr_user_id, $alias_name, $domain_ip, $forward, $mount_point;


	$cr_user_id = $_POST['usraccounts'];
	$alias_name	= strtolower(clean_input($_POST['ndomain_name']));
	$mount_point = strtolower(clean_input($_POST['ndomain_mpoint']));
	$forward = strtolower(clean_input($_POST['forward']));


$query = <<<SQL_QUERY
        SELECT
            domain_ip_id
        FROM
            domain
        WHERE
            domain_id = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($cr_user_id));
	$domain_ip = $rs -> fields['domain_ip_id'];

	$alias_name = get_punny($alias_name);

	//$mount_point = "/".$mount_point;

	// Fisrt check is the data correct
	if (chk_dname($alias_name) > 0) {
		$err_al = tr("Incorrect domain name syntax");
	}else if (vhcs_domain_exists($alias_name, $_SESSION['user_id'])) {
        $err_al = tr('Domain with that name already exists on the system!');
	}else if (chk_mountp($mount_point) > 0) {
		$err_al = tr("Incorrect mount point syntax");
	}else if ($forward != 'no') {
		if (chk_url($forward) > 0 ) {
			$err_al = tr("Incorrect forward syntax");
		}
	}else{
		$res = exec_query($sql, "select domain_id from domain_aliasses where alias_name=?", array($alias_name));
		$res2 = exec_query($sql, "select domain_id from domain where domain_name = ?", array($alias_name));
        if ($res->RowCount() > 0 or $res2->RowCount() > 0) {
            // we already have domain with this name
            $err_al = tr("Domain with this name already exist");
        }

        // all seems ok - add it
        $mres = exec_query($sql,
                           "select count(alias_id) as cnt from domain_aliasses where domain_id=? and alias_mount=?",
                           array($cr_user_id, $mount_point));
        $mdata = $mres->FetchRow();
        $subdomres = exec_query($sql,
                                "select count(subdomain_id) as cnt from subdomain where domain_id=? and subdomain_mount=?",
                                array($cr_user_id, $mount_point));
		$subdomdata = $subdomres->FetchRow();
        if ($mdata['cnt'] > 0 || $subdomdata['cnt'] > 0) {
            // whe have alias with same mount point !!! ERROR
            $err_al = tr("There are alias with same mount point");
        }
	}


	if('_off_' !== $err_al)
	{
		return;
	}

	// Begin add new alias domain
	$alias_name = htmlspecialchars($alias_name, ENT_QUOTES, "UTF-8");
	check_for_lock_file();
	global $cfg;
	$status = $cfg['ITEM_ADD_STATUS'];

	exec_query($sql,
             "insert into domain_aliasses(domain_id, alias_name, alias_mount, alias_status, alias_ip_id, url_forward) values (?, ?, ?, ?, ?, ?)",
             array($cr_user_id, $alias_name, $mount_point, $status, $domain_ip, $forward));
	send_request();
	$admin_login = $_SESSION['user_logged'];
	write_log("$admin_login: add domain alias: $alias_name");

	$_SESSION["aladd"] = '_yes_';
	header( "Location: domain_alias.php" );
	die();
}// End of add_domain_alias();


function gen_users_list(&$tpl, $reseller_id)
{

	global $sql;
	global $cr_user_id;

	$query = <<<SQL_QUERY
        SELECT
            admin_id
        FROM
            admin
        WHERE
                admin_type = 'user'
            AND
                created_by = ?
        ORDER BY
            admin_name
SQL_QUERY;

    $ar = exec_query($sql, $query, array($reseller_id));

    if ($ar -> RowCount() == 0) {

        set_page_message(tr('You have no user records.'));
		header( "Location: domain_alias.php" );
		die();
		$tpl -> assign('USER_ENTRY', '');
		return false;
	}

	$i =1;
    while ($ad = $ar->FetchRow()) { // Process all founded users
		$admin_id	= $ad['admin_id'];
		$selected   = '';

		// Get domain data
        $query = <<<SQL_QUERY
            SELECT
                domain_id,
                IFNULL(domain_name, '') AS domain_name
            FROM
                domain
            WHERE
                domain_admin_id = ?
SQL_QUERY;

        $dr = exec_query($sql, $query, array($admin_id));
        $dd = $dr -> FetchRow();

        $domain_id = $dd['domain_id'];
        $domain_name = $dd['domain_name'];

		if(('' == $cr_user_id) && ($i == 1))
			$selected = 'selected';
		else if($cr_user_id == $domain_id)
			$selected = 'selected';

		$IDN = new idna_convert();

		$domain_name = $IDN->decode($domain_name);

		$domain_name = utf8_decode($domain_name);

		$tpl -> assign(
					array(
							'USER' => $domain_id,
							'USER_DOMAIN_ACCOUN' => $domain_name,
							'SELECTED' => $selected
						)
				);
		$i++;
		$tpl -> parse('USER_ENTRY', '.user_entry');

	}//End of loop

	return true;
}// End of gen_users_list()


function gen_page_msg(&$tpl, $erro_txt)
{

	if ($erro_txt != '_off_') {

        $tpl -> assign('MESSAGE', $erro_txt);
		$tpl -> parse('PAGE_MESSAGE', 'page_message');

    } else {

        $tpl -> assign('PAGE_MESSAGE', '');
    }
}//End of gen_page_msg()
?>
