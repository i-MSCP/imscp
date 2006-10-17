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
$tpl -> define_dynamic('page', $cfg['CLIENT_TEMPLATE_PATH'].'/create_catchall.tpl');
$tpl -> define_dynamic('page_message', 'page');
$tpl -> define_dynamic('logged_from', 'page');
$tpl -> define_dynamic('mail_list', 'page');

if (isset($_GET['id'])) {
  $item_id = $_GET['id'];
} else if (isset($_POST['id'])) {
  $item_id = $_POST['id'];
} else {
  user_goto('catchall.php');
}

//
// page functions.
//

function gen_dynamic_page_data(&$tpl, &$sql, $id)
{
  global $_SESSION, $cfg;
  global $domain_id;

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
       $dmn_cgi) = get_domain_default_props($sql, $_SESSION['user_id']);

	 $domain_id = $dmn_id;

  list($mail_acc_cnt,
       $dmn_mail_acc_cnt,
       $sub_mail_acc_cnt,
       $als_mail_acc_cnt) = get_domain_running_mail_acc_cnt($sql, $dmn_id);

	if ($dmn_mailacc_limit != 0 &&  $mail_acc_cnt >= $dmn_mailacc_limit) {
		set_page_message(tr('Mail accounts limit expired!'));
		header("Location: catchall.php");
		die();
	}

  $ok_status = $cfg['ITEM_OK_STATUS'];
  if (preg_match("/(\d+);(dmn|als|sub)/", $id, $match) == 1) {
    $item_id = $match[1];
    $item_type = $match[2];
    
    if ($item_type === 'dmn') {
      $query = <<<SQL_QUERY
                SELECT
                    t1.mail_id, t1.mail_type, t2.domain_name, t1.mail_acc
                FROM
                    mail_users AS t1,
                    domain AS t2
                WHERE
                    t1.domain_id = ?
                  AND
                    t2.domain_id = ?
                  AND
                    t1.sub_id = '0'
                  AND
                    t1.status = ?
                ORDER BY
                    t1.mail_type DESC, t1.mail_acc
SQL_QUERY;

      $rs = exec_query($sql, $query, array($item_id, $item_id, $ok_status));
      if ($rs -> RecordCount() == 0) {
        user_goto('catchall.php');
      }

      while (!$rs -> EOF) {
        $show_mail_acc = decode_idna($rs -> fields['mail_acc']);
        $show_domain_name = decode_idna($rs -> fields['domain_name']);
        $mail_acc = $rs -> fields['mail_acc'];
        $domain_name = $rs -> fields['domain_name'];
        $tpl -> assign(array('MAIL_ID' => $rs -> fields['mail_id'],
                             'MAIL_ACCOUNT' => $show_mail_acc."@".$show_domain_name, // this will be show in the templates
                             'MAIL_ACCOUNT_PUNNY' => $mail_acc."@".$domain_name //this will be updated wenn we crate cach all
                            )
                      );

        $tpl -> parse('MAIL_LIST', '.mail_list');
        $rs -> MoveNext();
      }

    } else if ($item_type === 'als') {

      $query = <<<SQL_QUERY
                SELECT
                    t1.mail_id, t1.mail_type, t2.alias_name, t1.mail_acc
                FROM
                    mail_users AS t1,
                    domain_aliasses AS t2
                WHERE
                    t1.sub_id = t2.alias_id
                  AND
                    t1.status = ?
                  AND
                    t2.alias_id = ?
                ORDER BY
                  t1.mail_type DESC, t1.mail_acc
SQL_QUERY;

      $rs = exec_query($sql, $query, array($ok_status, $item_id));

      if ($rs -> RecordCount() == 0) {
        user_goto('catchall.php');
      }

      while (!$rs -> EOF) {
        $show_mail_acc = decode_idna($rs -> fields['mail_acc']);
        $show_alias_name = decode_idna($rs -> fields['alias_name']);
        $mail_acc = $rs -> fields['mail_acc'];
        $alias_name = $rs -> fields['alias_name'];
        $tpl -> assign(array('MAIL_ID' => $rs -> fields['mail_id'],
                             'MAIL_ACCOUNT' => $show_mail_acc."@".$show_alias_name, // this will be show in the templates
                             'MAIL_ACCOUNT_PUNNY' =>  $mail_acc."@".$alias_name //this will be updated wenn we crate cach all
                             )
                      );

        $tpl -> parse('MAIL_LIST', '.mail_list');
        $rs -> MoveNext();
      }

    } else if ($item_type === 'sub') {

      $query = <<<SQL_QUERY
                SELECT
                    t1.mail_id, t1.mail_type, CONCAT( t2.subdomain_name, '.', t3.domain_name ) AS subdomain_name, t1.mail_acc
                FROM
                    mail_users AS t1,
                  	subdomain AS t2,
                  	domain AS t3 
                WHERE
                    t1.sub_id = t2.subdomain_id
                  AND 
                  	t2.domain_id = t3.domain_id
                  AND
                    t1.status = ?
                  AND
                  	(t1.mail_type = 'subdom_mail'
					or
					t1.mail_type = 'subdom_forward')
				  AND
                    t2.subdomain_id = ?               
                ORDER BY
                  t1.mail_type DESC, t1.mail_acc
SQL_QUERY;

      $rs = exec_query($sql, $query, array($ok_status, $item_id));

      if ($rs -> RecordCount() == 0) {
        user_goto('catchall.php');
      }

      while (!$rs -> EOF) {
        $show_mail_acc = decode_idna($rs -> fields['mail_acc']);
        $show_alias_name = decode_idna($rs -> fields['subdomain_name']);
        $mail_acc = $rs -> fields['mail_acc'];
        $alias_name = $rs -> fields['subdomain_name'];
        $tpl -> assign(array('MAIL_ID' => $rs -> fields['mail_id'],
                             'MAIL_ACCOUNT' => $show_mail_acc."@".$show_alias_name, // this will be show in the templates
                             'MAIL_ACCOUNT_PUNNY' =>  $mail_acc."@".$alias_name //this will be updated wenn we create catch all
                             )
                      );

        $tpl -> parse('MAIL_LIST', '.mail_list');
        $rs -> MoveNext();
      }
    }
      
  } else {
    user_goto('catchall.php');
  }

}

function create_catchall_mail_account(&$sql, $id)
{
	// Check if user is owner of the domain
/*	$query = <<<SQL_QUERY
		SELECT
			COUNT(mail_id) as cnt
		FROM
			mail_users
		WHERE
			domain_id = ?
		AND
			mail_id = ?
SQL_QUERY;

	global $domain_id;
	$eid = explode(';', $_POST['id']);
	$mail_id = $eid[0];
	$rs = exec_query($sql, $query, array($domain_id, $mail_id));

	if ($rs -> fields['cnt'] == 0) {
		set_page_message(tr('0!'.$domain_id.$mail_id));
		header("Location: catchall.php");
		die();
		header("Location: catchall.php");
	}
*/
  global $cfg;

  if (isset($_POST['uaction']) && $_POST['uaction'] === 'create_catchall' && $_POST['mail_type'] === 'normal') {
    if (preg_match("/(\d+);(dmn|als|sub)/", $_POST['id'], $match) == 1) {
      $item_id = $match[1];
      $item_type = $match[2];
      $post_mail_id = $_POST['mail_id'];

      if (preg_match("/(\d+);([^;]+);/", $post_mail_id, $match) == 1) {
        $mail_id = $match[1];
        $mail_acc = $match[2];
        if ($item_type === 'dmn') {
          $mail_type = 'normal_catchall';
        } elseif ($item_type === 'als') {
          $mail_type = 'alias_catchall';
        } elseif ($item_type === 'sub') {
          $mail_type = 'subdom_catchall';	
        }

        $query = <<<SQL_QUERY
                    select
                        domain_id, sub_id
                    from
                        mail_users
                    where
                        mail_id = ?
SQL_QUERY;

        $rs = exec_query($sql, $query, array($mail_id));
        $domain_id = $rs -> fields['domain_id'];
        $sub_id = $rs -> fields['sub_id'];
        $status = $cfg['ITEM_ADD_STATUS'];
        check_for_lock_file();

        $query = <<<SQL_QUERY
                    insert into mail_users
                        (mail_acc,
                         mail_pass,
                         mail_forward,
                         domain_id,
                         mail_type,
                         sub_id,
                         status,
                         mail_auto_respond)
                    values
                        (?, ?, ?, ?, ?, ?, ?, ?)
SQL_QUERY;

        $rs = exec_query($sql, $query, array($mail_acc, '_no_', '_no_', $domain_id, $mail_type, $sub_id, $status, '_no_'));

        send_request();
        write_log($_SESSION['user_logged'].": add new email catch all");
        set_page_message(tr('Catch all account scheduled for creation!'));
        user_goto('catchall.php');
      } else {
        user_goto('catchall.php');
      }
	}
	} else if (isset($_POST['uaction']) && $_POST['uaction'] === 'create_catchall' && $_POST['mail_type'] === 'forward' && isset($_POST['forward_list'])) {

     if (preg_match("/(\d+);(dmn|als|sub)/", $id, $match) == 1) {
        $item_id = $match[1];
	    $item_type = $match[2];

        if ($item_type === 'dmn') {
          $mail_type = 'normal_catchall';
          $sub_id = '0';
        } elseif ($item_type === 'als') {
          $mail_type = 'alias_catchall';
	      $sub_id = $item_id;
        } elseif ($item_type === 'sub') {
          $mail_type = 'subdom_catchall';
	      $sub_id = $item_id;
        }
    	  $mail_forward = clean_input($_POST['forward_list']);
	      $faray = preg_split ("/[\n]+/",$mail_forward);

    	  foreach ($faray as $value) {
	        $value = trim($value);
        	if (chk_email($value) > 0 && $value !== '') {
    	      /* ERR .. strange :) not email in this line - warning */
	          set_page_message(tr("Mail forward list error!"));
        	  return;
    	    } else if ($value === '') {
	          set_page_message(tr("Mail forward list error!"));
        	  return;
    	    }
	      }

		$mail_acc = clean_input($_POST['forward_list']);
        //$domain_id = $item_id;
        $status = $cfg['ITEM_ADD_STATUS'];
        check_for_lock_file();

        $query = <<<SQL_QUERY
                    insert into mail_users
                        (mail_acc,
                         mail_pass,
                         mail_forward,
                         domain_id,
                         mail_type,
                         sub_id,
                         status,
                         mail_auto_respond)
                    values
                        (?, ?, ?, ?, ?, ?, ?, ?)
SQL_QUERY;

        $rs = exec_query($sql, $query, array($mail_acc, '_no_', '_no_', $domain_id, $mail_type, $sub_id, $status, '_no_'));

        send_request();
        write_log($_SESSION['user_logged'].": add new email catch all ");
        set_page_message(tr('Catch all account scheduled for creation!'));
        user_goto('catchall.php');
      } else {
        user_goto('catchall.php');
      }
    }
}


//
// common page data.
//
global $cfg;
$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(array('TR_CLIENT_CREATE_CATCHALL_PAGE_TITLE' => tr('VHCS - Client/Create CatchAll Mail Account'),
                     'THEME_COLOR_PATH' => "../themes/$theme_color",
                     'THEME_CHARSET' => tr('encoding'),
                     'TID' => $_SESSION['layout_id'],
                     'VHCS_LICENSE' => $cfg['VHCS_LICENSE'],
                     'ISP_LOGO' => get_logo($_SESSION['user_id'])));

//
// dynamic page data.
//
gen_dynamic_page_data($tpl, $sql, $item_id);
create_catchall_mail_account($sql, $item_id);
$tpl -> assign('ID', $item_id);

//
// static page messages.
//

gen_client_menu($tpl, $cfg['CLIENT_TEMPLATE_PATH'].'/menu_email_accounts.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$tpl -> assign(array('TR_CREATE_CATCHALL_MAIL_ACCOUNT' => tr('Create catch all mail account'),
                     'TR_MAIL_LIST' => tr('Mail accounts list'),
                     'TR_CREATE_CATCHALL' => tr('Create catch all'),
                     'TR_FORWARD_MAIL' => tr('Forward mail'),
                     'TR_FORWARD_TO' => tr('Forward to')));

gen_page_message($tpl);
$tpl -> parse('PAGE', 'page');
$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

unset_messages();

?>
