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

require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();

$tpl -> define_dynamic('page', $cfg['CLIENT_TEMPLATE_PATH'].'/add_alias.tpl');

$tpl -> define_dynamic('page_message', 'page');

$tpl -> define_dynamic('logged_from', 'page');

$tpl -> define_dynamic('user_entry', 'page');

$tpl -> define_dynamic('ip_entry', 'page');

$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(
                array(
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

gen_client_mainmenu($tpl, $cfg['CLIENT_TEMPLATE_PATH'].'/main_menu_manage_domains.tpl');
gen_client_menu($tpl, $cfg['CLIENT_TEMPLATE_PATH'].'/menu_manage_domains.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$tpl -> assign(
                array(
						'TR_CLIENT_ADD_ALIAS_PAGE_TITLE' => tr('ispCP Client : Add Alias'),
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

check_domainalias_permissions($sql, $_SESSION['user_id']);

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

if ($cfg['DUMP_GUI_DEBUG']) dump_gui_debug();


//
// Begin function declaration lines
//

function check_domainalias_permissions($sql, $user_id)
{


		    list($dmn_id,
				 $dmn_name,
				 $dmn_gid,
				 $dmn_uid,
				 $dmn_created_id,
				 $dmn_created,
				 $dmn_last_modified,
				 $dmn_mailacc_limit,
				 $dmn_ftpacc_limit,
				 $dmn_traff_limit,
				 $dmn_sqld_limit,
				 $dmn_sqlu_limit,
				 $dmn_status,
				 $dmn_als_limit,
				 $dmn_subd_limit,
				 $dmn_ip_id,
				 $dmn_disk_limit,
				 $dmn_disk_usage,
				 $dmn_php,
				 $dmn_cgi) = get_domain_default_props($sql, $user_id);


				$als_cnt = get_domain_running_als_cnt($sql, $dmn_id);

					if ($dmn_als_limit != 0 &&  $als_cnt >= $dmn_als_limit)
					{

							set_page_message(tr('Domain alias limit reached!'));

							header("Location: manage_domains.php");

							die();

					}


}

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
		$forward = $_POST['forward'];
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

}// End of gen_al_page()

function add_domain_alias(&$sql, &$err_al)
{
	global $cr_user_id, $alias_name, $domain_ip, $forward, $mount_point;


	$cr_user_id = $domain_id = get_user_domain_id($sql, $_SESSION['user_id']);
	$alias_name	= strtolower($_POST['ndomain_name']);
	$mount_point = strtolower($_POST['ndomain_mpoint']);
	$forward = strtolower(clean_input($_POST['forward']));


$query = <<<SQL_QUERY
        select
            domain_ip_id
        from
            domain
        where
            domain_id = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($cr_user_id));
	$domain_ip = $rs -> fields['domain_ip_id'];

	$alias_name = encode_idna($alias_name);

	//$mount_point = "/".$mount_point;

	// Fisrt check if the data is correct
	if (!chk_dname($alias_name)) {
		$err_al = tr("Incorrect domain name syntax");
	}else if (ispcp_domain_exists($alias_name, 0)) {
        $err_al = tr('Domain with that name already exists on the system!');
	}else if (!chk_mountp($mount_point) && $mount_point != '/') {
		$err_al = tr("Incorrect mount point syntax");
	}else if ($forward != 'no') {
		if (!chk_url($forward)) {
			$err_al = tr("Incorrect forward syntax");
		}
	} else {
    $query = "select domain_id from domain_aliasses where alias_name=?";
		$res = exec_query($sql, $query, array($alias_name));
    $query = "select domain_id from domain where domain_name=?";
		$res2 = exec_query($sql, $query, array($alias_name));
        if ($res->RowCount() > 0 or $res2->RowCount() > 0) {
            // we already have domain with this name
            $err_al = tr("Domain with this name already exist");
        }

        $query = "select count(subdomain_id) as cnt from subdomain where domain_id=? and subdomain_mount=?";
        $subdomres = exec_query($sql, $query, array($cr_user_id, $mount_point));
        $subdomdata = $subdomres->FetchRow();
        if ($subdomdata['cnt'] > 0) {
            $err_al = tr("There is a subdomain with the same mount point!");
        }
	}


	if('_off_' !== $err_al) {
		return;
	}

	// Begin add new alias domain
	$alias_name = htmlspecialchars($alias_name, ENT_QUOTES, "UTF-8");
	check_for_lock_file();
	global $cfg;
	$status = $cfg['ITEM_ADD_STATUS'];

  $query = "insert into domain_aliasses(domain_id, alias_name, alias_mount, alias_status, alias_ip_id, url_forward) values (?, ?, ?, ?, ?, ?)";
	exec_query($sql, $query, array($cr_user_id, $alias_name, $mount_point, $status, $domain_ip, $forward));

	send_request();

	$admin_login = $_SESSION['user_logged'];
	write_log("$admin_login: add domain alias: $alias_name.");

	set_page_message(tr('Alias scheduled for addition!'));
	header( "Location: manage_domains.php" );
	die();
}// End of add_domain_alias();





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