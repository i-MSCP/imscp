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

$tpl -> define_dynamic('page', $cfg['RESELLER_TEMPLATE_PATH'].'/rau4.tpl');

$tpl -> define_dynamic('page_message', 'page');

$tpl -> define_dynamic('logged_from', 'page');

$tpl -> define_dynamic('alias_list', 'page');

$tpl -> define_dynamic('alias_entry', 'alias_list');

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

if (isset($_SESSION['dmn_id']) && $_SESSION['dmn_id'] !== '') {

	$reseller_id = $_SESSION['user_id'];
	$domain_id = $_SESSION['dmn_id'];

	$query = <<<SQL_QUERY
      select
          domain_id
      from
          domain
      where
          domain_id = ?
        and
          domain_created_id = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($domain_id, $reseller_id));

	if ($rs -> RecordCount() == 0) {

		set_page_message(tr('User does not exist or you do not have permission to access this interface!'));
		header('Location: users.php');
		die();
	}
	// check main domain status
	global $cfg;
	$ok_status = $cfg['ITEM_OK_STATUS'];
	$add_status = $cfg['ITEM_ADD_STATUS'];

	$query = <<<SQL_QUERY
        select
            domain_id
        from
            domain
        where
            domain_id = ?
          and
            (domain_status = ? or domain_status = ?)
SQL_QUERY;

	$rs = exec_query($sql, $query, array($domain_id, $ok_status, $add_status));
	if ($rs -> RecordCount() == 0) {

		set_page_message(tr('System error with Domain ID ')."$domain_id");
		header('Location: users.php');
		die();
	}


} else {

	set_page_message(tr('User does not exist or you do not have permission to access this interface!'));
	header('Location: users.php');
	die();

}


$err_txt = '_off_';
if (isset($_POST['uaction']) && $_POST['uaction'] === 'add_alias') {


	add_domain_alias($sql, $err_txt);

}

init_empty_data();

gen_al_page($tpl, $_SESSION['user_id']);

gen_page_message($tpl);

gen_reseller_mainmenu($tpl, $cfg['RESELLER_TEMPLATE_PATH'].'/main_menu_manage_users.tpl');
gen_reseller_menu($tpl, $cfg['RESELLER_TEMPLATE_PATH'].'/menu_manage_users.tpl');

gen_logged_from($tpl);

$tpl -> assign(
                array(
						'TR_ADD_USER_PAGE_TITLE' => tr('ISPCP - User/Add user'),
						'TR_MANAGE_DOMAIN_ALIAS' => tr('Manage domain alias'),
						'TR_ADD_ALIAS' => tr('Add domain alias'),
						'TR_DOMAIN_NAME' => tr('Domain name'),
						'TR_DOMAIN_ACCOUNT' => tr('User account'),
						'TR_MOUNT_POINT' => tr('Directory mount point'),
						'TR_DOMAIN_IP' => tr('Domain IP'),
						'TR_FORWARD' => tr('Forward to URL'),
						'TR_ADD' => tr('Add alias'),
						'TR_DOMAIN_ALIS'  => tr('Domain alias'),
						'TR_STATUS' => tr('Status'),
						'TR_ADD_USER' => tr('Add user'),
						'TR_GO_USERS' => tr('Done'),

                     )
              );

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if ($cfg['DUMP_GUI_DEBUG']) dump_gui_debug();


//
// Begin function declaration lines
//


function init_empty_data()
{
	global $cr_user_id, $alias_name, $domain_ip, $forward, $mount_point, $tpl;

	$cr_user_id = "";
	$alias_name	= "";
	$domain_ip	= "";
	$forward	= "";
	$mount_point= "";

	$tpl -> assign(
				array(
						'DOMAIN' => $alias_name,
						'MP' => $mount_point,
						'FORWARD' => 'no'
					)
			);
}//End of init_empty_data()


// Show data fiels
function gen_al_page(&$tpl, $reseller_id)
{
	global $sql;

	$dmn_id = $_SESSION['dmn_id'];

	$query = <<<SQL_QUERY
        select
            alias_id,
            alias_name,
            alias_status
        from
            domain_aliasses
        where
            domain_id = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($dmn_id));

	if ($rs -> RecordCount() == 0) {

			$tpl -> assign('ALIAS_LIST', '');

	} else {

		$i = 0;
		 while (!$rs -> EOF) {

			$alias_name = decode_idna($rs -> fields['alias_name']);
			$alias_status  = translate_dmn_status($rs -> fields['alias_status']);

			if ($i % 2 == 0) {
					$page_cont = 'content';

			}else {
					$page_cont = 'content2';
			}

			$tpl -> assign(
                                array(
                                        'DOMAIN_ALIS' => $alias_name,
                                        'STATUS' => $alias_status,
										'CLASS' => $page_cont,

                                     )
                              );

		 	$i++;
            $tpl -> parse('ALIAS_ENTRY', '.alias_entry');
			$rs -> MoveNext();
		 }
	}


}// End of gen_al_page()

function add_domain_alias(&$sql, &$err_al)
{
	global $cr_user_id, $alias_name, $domain_ip, $forward, $mount_point, $tpl;


	$cr_user_id = $_SESSION['dmn_id'];
	$alias_name	= strtolower(clean_input($_POST['ndomain_name']));
	$domain_ip = $_SESSION['dmn_ip'];
	$mount_point = strtolower(clean_input($_POST['ndomain_mpoint']));
	$forward = strtolower(clean_input($_POST['forward']));

	$alias_name = get_punny($alias_name);


	// Fisrt check is the data correct
	if (!chk_dname($alias_name)) {
		$err_al = tr("Incorrect domain name syntax");
	}else if (ispcp_domain_exists($alias_name, $_SESSION['user_id'])) {
        $err_al = tr('Domain with that name already exists on the system!');
	}else if (!chk_mountp($mount_point)) {
		$err_al = tr("Incorrect mount point syntax");
	}else if ($forward != 'no') {
		if (!chk_url($forward)) {
			$err_al = tr("Incorrect forward syntax");
		}
	}else{
		$res = exec_query($sql, "select domain_id from domain_aliasses where alias_name=?", array($alias_name));
		$res2 = exec_query($sql, "select domain_id from domain where domain_name =?", array($alias_name));
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
		set_page_message($err_al);
		return;
	}

	// Begin add new alias domain
	check_for_lock_file();
	global $cfg;
	$status = $cfg['ITEM_ADD_STATUS'];


	exec_query($sql,
             "insert into domain_aliasses(domain_id, alias_name, alias_mount, alias_status, alias_ip_id, url_forward) values (?, ?, ?, ?, ?, ?)",
             array($cr_user_id, $alias_name, $mount_point, $status, $domain_ip, $forward));
	send_request();
	$admin_login = $_SESSION['user_logged'];
	write_log("$admin_login: add domain alias: $alias_name");

	set_page_message(tr('Domain alias added!'));

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
