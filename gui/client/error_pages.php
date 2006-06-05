<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2005 by moleSoftware	|
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


function write_error_page(&$sql, &$user_id, &$eid)
{
  $error = $_POST['error'];
  $eid = $_POST['eid'];
  $eid = "error_" . $eid;

  // let's check if exist error table for this looser

  $query = <<<SQL_QUERY
        select
            user_id
        from
            error_pages
        where
            user_id = ?
SQL_QUERY;

  $rs = exec_query($sql, $query, array($user_id));

  if ($rs -> RecordCount() == 0) {
    /// oooo noooo we dont have error table ... i can't believe it ...

    $query = <<<SQL_QUERY
          insert into error_pages
              (user_id, error_401, error_403, error_404, error_500)
          values
               (?, '', '', '', '')
SQL_QUERY;

    $rs = exec_query($sql, $query, array($user_id));

  }

  check_for_lock_file();

  $query = <<<SQL_QUERY
        update
            error_pages
        set
          $eid = ?
        where
          user_id = ?
SQL_QUERY;

  $rs = exec_query($sql, $query, array($error, $user_id));

  // now save the file
  // error num (dir) = $eid
  // error text = $error
  list($temp_dmn_id,
        $temp_dmn_name,
        $temp_dmn_gid,
        $temp_dmn_uid,
        $temp_dmn_created_id,
        $temp_dmn_created,
        $temp_dmn_last_modified,
        $temp_dmn_mailacc_limit,
        $temp_dmn_ftpacc_limit,
        $temp_dmn_traff_limit,
        $temp_dmn_sqld_limit,
        $temp_dmn_sqlu_limit,
        $temp_dmn_status,
        $temp_dmn_als_limit,
        $temp_dmn_subd_limit,
        $temp_dmn_ip_id,
        $temp_dmn_disk_limit,
        $temp_dmn_disk_usage,
        $temp_dmn_php,
        $temp_dmn_cgi) = get_domain_default_props($sql, $_SESSION['user_id']);

  switch($eid) {
    case 'error_401':
      $e_dir = '401';
      break;
    case 'error_403':
      $e_dir = '403';
      break;
    case 'error_404':
      $e_dir = '404';
      break;
    case 'error_500':
      $e_dir = '500';
      break;
  }

  global $cfg;
  @$file = fopen($cfg['FTP_HOMEDIR'].'/'.$temp_dmn_name.'/errors/'.$e_dir.'/index.php','w');
  if (!$file) {
    /* cannot open file for writing */
    $error_saving = 1;
    session_register("error_saving");
  } else {
    $content = stripslashes($error);
    fputs($file,$content);
    $saved = 1;
    session_register("saved");
  }
}


function update_error_page(&$sql, $user_id)
{
  if (isset($_POST['uaction']) && $_POST['uaction'] === 'updt_error') {
    if ($_POST['eid']==401 or $_POST['eid']==403 or $_POST['eid']==404 or $_POST['eid']==500) {
      write_error_page($sql, $_SESSION['user_id'], $_POST['eid']);
      set_page_message(tr('Custom error page was updated!'));
    } else {
      set_page_message(tr('System error - custom error page was NOT updated!'));
    }
  }
}

include '../include/vhcs-lib.php';

check_login();

$tpl = new pTemplate();
$tpl -> define_dynamic('page', $cfg['CLIENT_TEMPLATE_PATH'].'/error_pages.tpl');
$tpl -> define_dynamic('page_message', 'page');
$tpl -> define_dynamic('logged_from', 'page');
global $cfg;
$theme_color = $cfg['USER_INITIAL_THEME'];
$tpl -> define_dynamic('custom_buttons', 'page');

//
// page functions.
//


//
// common page data.
//

$domain = $_SESSION['user_logged'];
$domain = "http://www.".$domain;

$tpl -> assign(array('TR_CLIENT_ERROR_PAGE_TITLE' => tr('VHCS - Client/Manage Error Custom Pages'),
                     'THEME_COLOR_PATH' => "../themes/$theme_color",
                     'THEME_CHARSET' => tr('encoding'),
                     'TID' => $_SESSION['layout_id'],
                     'VHCS_LICENSE' => $cfg['VHCS_LICENSE'],
                     'ISP_LOGO' => get_logo($_SESSION['user_id']),
                     'DOMAIN' => $domain));

//
// dynamic page data.
//

update_error_page($sql, $_SESSION['user_id']);

//
// static page messages.
//

gen_client_menu($tpl, $cfg['CLIENT_TEMPLATE_PATH'].'/menu_webtools.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$tpl -> assign(array('TR_ERROR_401' => tr('Error 401 (unauthorized)'),
                     'TR_ERROR_403' => tr('Error 403 (forbidden)'),
                     'TR_ERROR_404' => tr('Error 404 (not found)'),
                     'TR_ERROR_500' => tr('Error 500 (internal server error)'),
                     'TR_ERROR_PAGES' => tr('Error pages'),
                     'TR_EDIT' => tr('Edit'),
                     'TR_VIEW' => tr('View')));

gen_page_message($tpl);
$tpl -> parse('PAGE', 'page');
$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

unset_messages();

?>
