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

if (isset($_GET['id'])) {
  $ftp_acc = $_GET['id'];
} else if (isset($_POST['id'])) {
  $ftp_acc = $_POST['id'];
} else {
  user_goto('ftp_accounts.php');
}

$tpl = new pTemplate();
$tpl -> define_dynamic('page', $cfg['CLIENT_TEMPLATE_PATH'].'/edit_ftp_acc.tpl');
$tpl -> define_dynamic('page_message', 'page');
$tpl -> define_dynamic('logged_from', 'page');

//
// page functions.
//
function gen_page_dynamic_data(&$tpl, &$sql, $ftp_acc)
{
  global $cfg;

  $query = <<<SQL_QUERY
        SELECT
			homedir
		FROM
			ftp_users
        WHERE
			userid = ?
SQL_QUERY;

  $rs = exec_query($sql, $query, array($ftp_acc));

  $homedir = $rs -> fields['homedir'];
  $domain_ftp =  $_SESSION['user_logged'];
  $nftp_dir = $cfg['FTP_HOMEDIR']."/".$domain_ftp;

  if ($nftp_dir == $homedir) {
    $odir = "";
    $oins = "";
	} else {
    $odir = " checked ";
    $oins = substr($homedir, strlen($nftp_dir));
	}

  $tpl -> assign(array('FTP_ACCOUNT' => $ftp_acc,
                       'ID' => $ftp_acc,
                       'USE_OTHER_DIR_CHECKED' => $odir,
                       'OTHER_DIR' => $oins));
}

function update_ftp_account(&$sql, $ftp_acc)
{
  global $cfg, $ftp_acc;
  global $other_dir;

  if (isset($_POST['uaction']) && $_POST['uaction'] === 'edit_user') {
    if (!empty($_POST['pass']) || !empty($_POST['pass_rep'])) {
      if ($_POST['pass'] !== $_POST['pass_rep']) {
        set_page_message(tr('Entered passwords differ!'));
        return;
      }
		if (chk_password($_POST['pass'])) {
  			set_page_message( tr("Incorrect password range or syntax!"));
    		return;
  		}

      $pass = crypt_user_ftp_pass($_POST['pass']);
      if (isset($_POST['use_other_dir']) && $_POST['use_other_dir'] === 'on') {

		$other_dir = $cfg['FTP_HOMEDIR']."/".$_SESSION['user_logged'].clean_input($_POST['other_dir']);

  	     $res = preg_match("/\.\./", clean_input($_POST['other_dir']), $match);
		 if (!is_dir($other_dir) || $res !== 0) {
				set_page_message(clean_input($_POST['other_dir'])." ".tr('do not exist'));
				return;
		}

        $query = <<<SQL_QUERY
                    update
                        ftp_users
                    set
                        passwd = ?,
                        homedir = ?
                    where
                        userid = ?
SQL_QUERY;

        $rs = exec_query($sql, $query, array($pass, $other_dir, $ftp_acc));

      } else {
        $query = <<<SQL_QUERY
                    update
                        ftp_users
                    set
                        passwd = ?
                    where
                        userid = ?
SQL_QUERY;
        $rs = exec_query(&$sql, $query, array($pass, $ftp_acc));

      }

      write_log($_SESSION['user_logged'].": updated FTP ".$ftp_acc." account data");
      set_page_message(tr('FTP account data updated!'));
      user_goto('ftp_accounts.php');
    } else {
      if (isset($_POST['use_other_dir']) && $_POST['use_other_dir'] === 'on') {
        $other_dir = $cfg['FTP_HOMEDIR']."/".$_SESSION['user_logged'].clean_input($_POST['other_dir']);
		$res = preg_match("/\.\./", clean_input($_POST['other_dir']), $match);
		 if (!is_dir($other_dir) || $res !== 0) {
				set_page_message(clean_input($_POST['other_dir'])." ".tr('do not exist'));
				return;
		}
    } else {
      $other_dir = $cfg['FTP_HOMEDIR']."/".$_SESSION['user_logged'];
    }

    $query = <<<SQL_QUERY
                    update
                        ftp_users
                    set
                        homedir = ?
                    where
                        userid = ?
SQL_QUERY;

      $rs = exec_query($sql, $query, array($other_dir, $ftp_acc));
      set_page_message(tr('FTP account data updated!'));
      user_goto('ftp_accounts.php');
    }
  }
}

//
// common page data.
//

global $cfg;
$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(array('TR_CLIENT_EDIT_FTP_ACC_PAGE_TITLE' => tr('VHCS - Client/Edit FTP Account'),
                     'THEME_COLOR_PATH' => "../themes/$theme_color",
                     'THEME_CHARSET' => tr('encoding'),
                     'TID' => $_SESSION['layout_id'],
                     'VHCS_LICENSE' => $cfg['VHCS_LICENSE'],
                     'ISP_LOGO' => get_logo($_SESSION['user_id'])));

//
// dynamic page data.
//
check_ftp_perms($sql, $ftp_acc);
gen_page_dynamic_data($tpl, $sql, $ftp_acc);
update_ftp_account($sql, $ftp_acc);

//
// static page messages.
//

gen_client_menu($tpl, $cfg['CLIENT_TEMPLATE_PATH'].'/menu_ftp_accounts.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$tpl -> assign(array('TR_EDIT_FTP_USER' => tr('Edit FTP user'),
                     'TR_FTP_ACCOUNT' => tr('FTP account'),
                     'TR_PASSWORD' => tr('Password'),
                     'TR_PASSWORD_REPEAT' => tr('Password repeat'),
                     'TR_USE_OTHER_DIR' => tr('Use other dir'),
                     'TR_EDIT' => tr('Save changes'),
                     'CHOOSE_DIR' => tr('Choose dir')));
gen_page_message($tpl);
$tpl -> parse('PAGE', 'page');
$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

unset_messages();

?>
