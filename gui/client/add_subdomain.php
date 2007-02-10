<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2006 by moleSoftware							|
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

$tpl -> define_dynamic('page', $cfg['CLIENT_TEMPLATE_PATH'].'/add_subdomain.tpl');
$tpl -> define_dynamic('page_message', 'page');
$tpl -> define_dynamic('logged_from', 'page');

//
// page functions.
//
function check_subdomain_permissions($sql, $user_id)
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

  $sub_cnt = get_domain_running_sub_cnt($sql, $dmn_id);

  if ($dmn_subd_limit != 0 &&  $sub_cnt >= $dmn_subd_limit) {
    set_page_message(tr('Subdomain limit expired!'));
    header("Location: manage_domains.php");
    die();
  }

  return $dmn_name; // Will be used in subdmn_exists()
}

function gen_user_add_subdomain_data(&$tpl, &$sql, $user_id)
{
  $query = <<<SQL_QUERY
        select
            domain_name
        from
            domain
        where
            domain_admin_id = ?
SQL_QUERY;

  $rs = exec_query($sql, $query, array($user_id));
  $domainname = decode_idna($rs -> fields['domain_name']);
  $tpl -> assign('DOMAIN_NAME', '.'.$domainname);

  if (isset($_POST['uaction']) && $_POST['uaction'] === 'add_subd') {
    $tpl -> assign(array('SUBDOMAIN_NAME' => clean_input($_POST['subdomain_name']),
                         'SUBDOMAIN_MOUNT_POINT' => clean_input($_POST['subdomain_mnt_pt'])));
  } else {
    $tpl -> assign(array('SUBDOMAIN_NAME' => '',
                         'SUBDOMAIN_MOUNT_POINT' => ''));
  }

  return $rs -> fields['domain_name'];
}

function subdmn_exists(&$sql, $user_id, $domain_id, $sub_name)
{
	global $dmn_name;

  $query_subdomain = <<<SQL_QUERY
        select
            count(subdomain_id) as cnt
        from
            subdomain
        where
            domain_id = ?
          and
            subdomain_name = ?
SQL_QUERY;

  $rs_subdomain = exec_query($sql, $query_subdomain, array($domain_id, $sub_name));

  $query_domain = <<<SQL_QUERY
  		select
			count(domain_id) as cnt
		from
			domain
		where
			domain_name = ?
SQL_QUERY;

  $domain_name = $sub_name.".".$dmn_name;

  $rs_domain = exec_query($sql, $query_domain, array($domain_name));

  if ($rs_subdomain -> fields['cnt'] == 0 && $rs_domain -> fields['cnt'] == 0) {
	return false;
  } else {
  	return true;
  }
}

function subdmn_mnt_pt_exists(&$sql, $user_id, $domain_id, $sub_name, $sub_mnt_pt)
{

  $query = <<<SQL_QUERY
        select
            count(subdomain_id) as cnt
        from
            subdomain
        where
            domain_id = ?
          and
            subdomain_mount = ?
SQL_QUERY;

  $rs = exec_query($sql, $query, array($domain_id, $sub_mnt_pt));

   $query2 = <<<SQL_QUERY
        select
            count(alias_id) as cnt
        from
            domain_aliasses
        where
            domain_id = ?
          and
            alias_mount = ?
SQL_QUERY;

  $rs2 = exec_query($sql, $query2, array($domain_id, $sub_mnt_pt));


  if ($rs -> fields['cnt'] > 0 || $rs2 -> fields['cnt'] > 0) {
    return 1;
  }


  return 0;
}

function subdomain_schedule(&$sql, $user_id, $domain_id, $sub_name, $sub_mnt_pt)
{
  global $cfg;

  $status_add = $cfg['ITEM_ADD_STATUS'];

  check_for_lock_file();

  $query = <<<SQL_QUERY
        insert into
            subdomain
                (domain_id,
				 subdomain_name,
				 subdomain_mount,
				 subdomain_status)
            values
                (?, ?, ?, ?)
SQL_QUERY;

  $rs = exec_query($sql, $query, array($domain_id, $sub_name, $sub_mnt_pt, $status_add));

  write_log($_SESSION['user_logged'].": add new subdomain: ".$sub_name);
  send_request();
}

function check_subdomain_data(&$tpl, &$sql, $user_id)
{
  $domain_id = get_user_domain_id($sql, $user_id);

  if (isset($_POST['uaction']) && $_POST['uaction'] === 'add_subd') {
    if (empty($_POST['subdomain_name'])) {
      set_page_message(tr('Please specify subdomain name!'));
      return;
    }

    $sub_name = strtolower($_POST['subdomain_name']);
	$sub_name = get_punny($sub_name);

    if (isset($_POST['subdomain_mnt_pt']) && $_POST['subdomain_mnt_pt'] !== '') {
      $sub_mnt_pt = strtolower($_POST['subdomain_mnt_pt']);
	  $sub_mnt_pt = decode_idna($sub_mnt_pt);
    }

    if (subdmn_exists($sql, $user_id, $domain_id, $sub_name) > 0) {
      set_page_message(tr('Subdomain already exists!'));
    } else if (@chk_subdname($sub_name.".".$_SESSION['user_logged']) > 0) {
      set_page_message(tr('Wrong subdomain syntax!'));
    } else if (subdmn_mnt_pt_exists($sql, $user_id, $domain_id, $sub_name, $sub_mnt_pt)) {
      set_page_message(tr('Subdomain mount point already exists!'));
    } else if (@chk_mountp($sub_mnt_pt) > 0){
    	set_page_message(tr('Incorrect mount point syntax'));
	} else {
      subdomain_schedule($sql, $user_id, $domain_id, $sub_name, $sub_mnt_pt);
      set_page_message(tr('Subdomain scheduled for addition!'));
      header('Location:manage_domains.php');
      exit(0);
    }

  }
}

//
// common page data.
//

// check User sql permision
if (isset($_SESSION['subdomain_support']) && $_SESSION['subdomain_support'] == "no")
{
  header("Location: index.php");
}

global $cfg;
$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(array('TR_CLIENT_ADD_SUBDOMAIN_PAGE_TITLE' => tr('VHCS - Client/Add Subdomain'),
                     'THEME_COLOR_PATH' => "../themes/$theme_color",
                     'THEME_CHARSET' => tr('encoding'),
                     'TID' => $_SESSION['layout_id'],
                     'VHCS_LICENSE' => $cfg['VHCS_LICENSE'],
                     'ISP_LOGO' => get_logo($_SESSION['user_id'])));

//
// dynamic page data.
//
$dmn_name = check_subdomain_permissions($sql, $_SESSION['user_id']);
gen_user_add_subdomain_data($tpl, $sql, $_SESSION['user_id']);
check_subdomain_data($tpl, $sql, $_SESSION['user_id']);

//
// static page messages.
//

gen_client_mainmenu($tpl, $cfg['CLIENT_TEMPLATE_PATH'].'/main_menu_manage_domains.tpl');
gen_client_menu($tpl, $cfg['CLIENT_TEMPLATE_PATH'].'/menu_manage_domains.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$tpl -> assign(array('TR_ADD_SUBDOMAIN' => tr('Add subdomain'),
                     'TR_SUBDOMAIN_DATA' => tr('Subdomain data'),
                     'TR_SUBDOMAIN_NAME' => tr('Subdomain name'),
                     'TR_DIR_TREE_SUBDOMAIN_MOUNT_POINT' => tr('Directory tree<br>mount point'),
                     'TR_ADD' => tr('Add')));
gen_page_message($tpl);
$tpl -> parse('PAGE', 'page');
$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

?>
