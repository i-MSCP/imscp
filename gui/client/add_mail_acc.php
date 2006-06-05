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

$tpl -> define_dynamic('page', $cfg['CLIENT_TEMPLATE_PATH'].'/add_mail_acc.tpl');
$tpl -> define_dynamic('page_message', 'page');
$tpl -> define_dynamic('logged_from', 'page');
$tpl -> define_dynamic('als_list', 'page');
$tpl -> define_dynamic('sub_list', 'page');
$tpl -> define_dynamic('custom_buttons', 'page');
$tpl -> define_dynamic('to_subdomain', 'page');
$tpl -> define_dynamic('to_alias_domain', 'page');
$tpl -> define_dynamic('js_to_subdomain', 'page'); //JavaScript have to be generatet too
$tpl -> define_dynamic('js_to_alias_domain', 'page'); //JavaScript have to be generatet too
$tpl -> define_dynamic('js_to_all_domain', 'page'); //JavaScript have to be generatet too
$tpl -> define_dynamic('js_not_domain', 'page'); //JavaScript have to be generatet too

//
// page functions.
//
function gen_page_form_data(&$tpl, $dmn_name, $post_check)
{
  $dmn_name = decode_idna($dmn_name);
  if ($post_check === 'no') {
    $tpl -> assign(array('USERNAME' => '',
                         'DOMAIN_NAME' => $dmn_name,
                         'MAIL_DMN_CHECKED' => 'checked',
                         'MAIL_ALS_CHECKED' => '',
                         'MAIL_SUB_CHECKED' => '',
                         'NORMAL_MAIL_CHECKED' => 'checked',
                         'FORWARD_MAIL_CHECKED' => '',
                         'FORWARD_LIST' => ''));
  } else {
    if (!isset($_POST['forward_list'])) {
      $f_list = "";
    } else {
      $f_list = $_POST['forward_list'];
    }

    $tpl -> assign(array('USERNAME' => $_POST['username'],
                         'DOMAIN_NAME' => $dmn_name,
                         'MAIL_DMN_CHECKED' => ($_POST['dmn_type'] === 'dmn') ? 'checked' : '',
                         'MAIL_ALS_CHECKED' => ($_POST['dmn_type'] === 'als') ? 'checked' : '',
                         'MAIL_SUB_CHECKED' => ($_POST['dmn_type'] === 'sub') ? 'checked' : '',
                         'NORMAL_MAIL_CHECKED' => ($_POST['mail_type'] === 'normal') ? 'checked' : '',
                         'FORWARD_MAIL_CHECKED' => ($_POST['mail_type'] === 'forward') ? 'checked' : '',
                         'FORWARD_LIST' => $f_list));
  }
}

function gen_dmn_als_list(&$tpl, &$sql, $dmn_id, $post_check)
{
  global $cfg;
  $ok_status = $cfg['ITEM_OK_STATUS'];

  $query = <<<SQL_QUERY
        SELECT
          alias_id, alias_name
        FROM
          domain_aliasses
        WHERE
          domain_id = ?
        AND
          alias_status = ?
        ORDER BY
          alias_name
SQL_QUERY;

    $rs = exec_query($sql, $query, array($dmn_id, $ok_status));
    if ($rs -> RecordCount() == 0) {
        $tpl -> assign(array('ALS_ID' => '0',
                             'ALS_SELECTED' => 'selected',
                             'ALS_NAME' => tr('Empty list')));
        $tpl -> parse('ALS_LIST', 'als_list');
        $tpl -> assign('TO_ALIAS_DOMAIN', '');
        $_SESSION['alias_count'] = "no";
    } else {
        $first_passed = FALSE;
        while (!$rs -> EOF) {
          if ($post_check === 'yes') {
            if (!isset($_POST['als_id'])) {
              $als_id = "";
            } else {
              $als_id = $_POST['als_id'];
            }

            if ($als_id == $rs -> fields['alias_id']) {
              $als_selected = 'selected';
            } else {
              $als_selected = '';
            }

          } else {

          if (!$first_passed) {
            $als_selected = 'selected';
          } else {
            $als_selected = '';
          }
        }

        $alias_name = decode_idna($rs -> fields['alias_name']);
        $tpl -> assign(array('ALS_ID' => $rs -> fields['alias_id'],
                             'ALS_SELECTED' => $als_selected,
                             'ALS_NAME' => $alias_name));
        $tpl -> parse('ALS_LIST', '.als_list');
        $rs -> MoveNext();

        if (!$first_passed) $first_passed = TRUE;
    }
  }
}

function gen_dmn_sub_list(&$tpl, &$sql, $dmn_id, $dmn_name, $post_check)
{
  global $cfg;
  $ok_status = $cfg['ITEM_OK_STATUS'];

  $query = <<<SQL_QUERY
        SELECT
            subdomain_id as sub_id, subdomain_name as sub_name
        FROM
            subdomain
        WHERE
            domain_id = ?
        AND
            subdomain_status = ?
        ORDER BY
            subdomain_name
SQL_QUERY;

  $rs = exec_query($sql, $query, array($dmn_id, $ok_status));

  if ($rs -> RecordCount() == 0) {
    $tpl -> assign(array('SUB_ID' => '0',
                         'SUB_SELECTED' => 'selected',
                         'SUB_NAME' => tr('Empty list')));
    $tpl -> parse('SUB_LIST', 'sub_list');
    $tpl -> assign('TO_SUBDOMAIN', '');
    $_SESSION['subdomain_count'] = "no";
  } else {
    $first_passed = FALSE;

    while (!$rs -> EOF) {
      if ($post_check === 'yes') {
        if (!isset($_POST['sub_id'])) {
          $sub_id = "";
        } else {
          $sub_id = $_POST['sub_id'];
        }

        if ($sub_id == $rs -> fields['sub_id']) {
          $sub_selected = 'selected';
        } else {
          $sub_selected = '';
        }
      } else {
        if (!$first_passed) {
          $sub_selected = 'selected';
        } else {
          $sub_selected = '';
        }
      }

      $sub_name = decode_idna($rs -> fields['sub_name']);
      $dmn_name = decode_idna($dmn_name);
      $tpl -> assign(array('SUB_ID' => $rs -> fields['sub_id'],
                           'SUB_SELECTED' => $sub_selected,
                           'SUB_NAME' => $sub_name.'.'.$dmn_name));
      $tpl -> parse('SUB_LIST', '.sub_list');
      $rs -> MoveNext();

      if (!$first_passed) $first_passed = TRUE;
    }
  }
}

function schedule_mail_account(&$sql, $dmn_id, $dmn_name)
{
  global $cfg;

  $domain_id = $dmn_id;

  // standard whithout encoding
  //$mail_acc = $_POST['username'];

  // lets encode the mail
  $mail_acc_tmp = strtolower($_POST['username']);
  $mail_acc = get_punny($mail_acc_tmp);
  //encoded

  $status = $cfg['ITEM_ADD_STATUS'];
  $mail_auto_respond = '_no_';

  if ($_POST['mail_type'] === 'normal') {
    if ($_POST['dmn_type'] === 'dmn') {
      $mail_pass = $_POST['pass'];
      $mail_forward = '_no_';
      $mail_type = 'normal_mail';
      $sub_id = '0';
    } else if ($_POST['dmn_type'] === 'sub') {
      $mail_pass = $_POST['pass'];
      $mail_forward = '_no_';
      $mail_type = 'subdom_mail';
      $sub_id = $_POST['sub_id'];
    } else if ($_POST['dmn_type'] === 'als') {
      $mail_pass = $_POST['pass'];
      $mail_forward = '_no_';
      $mail_type = 'alias_mail';
      $sub_id = $_POST['als_id'];
    }

    $check_acc_query = <<<SQL_QUERY
            SELECT
                COUNT(mail_id) AS cnt
            FROM
                mail_users
            WHERE
                mail_acc = ?
              AND
                domain_id = ?
              AND
                mail_type = ?
              AND
                sub_id = ?
SQL_QUERY;

    $rs = exec_query($sql, $check_acc_query, array($mail_acc, $domain_id, $mail_type, $sub_id));

  } else if ($_POST['mail_type'] === 'forward') {
    if ($_POST['dmn_type'] === 'dmn') {
      $mail_pass = '_no_';
      $mail_forward = $_POST['forward_list'];
      $faray = preg_split ("/[\n]+/",$mail_forward);

      foreach ($faray as $value) {
        $value = trim($value);
        if (chk_email($value) > 0 && $value !== '') {
          /* ERR .. strange :) not email in this line - warrning */
          set_page_message(tr("Mail forward list error!"));
          return;
        } else if ($value === '') {
          set_page_message(tr("Mail forward list error!"));
          return;
        }
      }

      $mail_type = 'normal_forward';
      $sub_id = '0';
    } else if ($_POST['dmn_type'] === 'sub') {
      $mail_pass = '_no_';
      $mail_forward = $_POST['forward_list'];
      $faray = preg_split ("/[\n]+/",$mail_forward);

      foreach ($faray as $value) {
        $value = trim($value);
        if (chk_email($value) > 0 && $value !== '') {
          /* ERR .. strange :) not email in this line - warrning */
          set_page_message(tr("Mail forward list error!"));
          return;
        }
      }

      $mail_type = 'subdom_forward';
      $sub_id = $_POST['sub_id'];

    } else if ($_POST['dmn_type'] === 'als') {

      $mail_pass = '_no_';
      $mail_forward = $_POST['forward_list'];
      $faray = preg_split ("/[\n]+/",$mail_forward);

      foreach ($faray as $value) {
        $value = trim($value);
        if (chk_email($value) > 0 && $value !== '') {
          /* ERR .. strange :) not email in this line - warrning */
          set_page_message(tr("Mail forward list error!"));

          return;
        }
      }

      $mail_type = 'alias_forward';
      $sub_id = $_POST['als_id'];
    }

    $check_acc_query = <<<SQL_QUERY
                  SELECT
                      COUNT(mail_id) AS cnt
                  FROM
                      mail_users
                  WHERE
                      mail_acc = ?
                    AND
                      domain_id = ?
                    AND
                      sub_id = ?
SQL_QUERY;

    $rs = exec_query($sql, $check_acc_query, array($mail_acc, $domain_id, $sub_id));
  }

  if ($rs -> fields['cnt'] > 0) {
    set_page_message(tr('Mail account already exists!'));
    return;
  }
  if (chk_username($mail_acc)) {
    set_page_message( tr("Incorrect username range or syntax!"));
    return;
  }

  check_for_lock_file();

  $query = <<<SQL_QUERY
        INSERT INTO mail_users
            (mail_acc,
             mail_pass,
             mail_forward,
             domain_id,
             mail_type,
             sub_id,
             status,
             mail_auto_respond)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?)
SQL_QUERY;

  $rs = exec_query($sql, $query, array($mail_acc,
                                       $mail_pass,
                                       $mail_forward,
                                       $domain_id,
                                       $mail_type,
                                       $sub_id,
                                       $status,
                                       $mail_auto_respond));

  write_log($_SESSION['user_logged'].": add new mail account: ".$mail_acc."@".$dmn_name);
  set_page_message(tr('Mail account scheduled for addition!'));
  send_request();
  header("Location: email_accounts.php");
  exit(0);
}


function check_mail_acc_data(&$tpl, &$sql, $dmn_id, $dmn_name)
{
  if (!isset($_POST['username']) || $_POST['username'] === '') {
    set_page_message(tr('Please enter mail account username!'));
    return;
  }

  if ($_POST['mail_type'] === 'normal') {
    if (!isset($_POST['pass']) || $_POST['pass'] === '' || !isset($_POST['pass_rep']) || $_POST['pass_rep'] === '') {
      set_page_message(tr('Password data is missing!'));
      return;
    }

    if ($_POST['pass'] !== $_POST['pass_rep']) {
      set_page_message(tr('Entered passwords differ from the another!'));
      return;
    }
  }

  if ($_POST['dmn_type'] === 'sub' && $_POST['sub_id'] == 0) {
    set_page_message(tr('Subdomain list is empty! You can not add mail accounts!'));
    return;
  }

  if ($_POST['dmn_type'] === 'als' && $_POST['als_id'] == 0) {
    set_page_message(tr('Alias list is empty! You can not add mail accounts!'));
    return;
  }

  if ($_POST['mail_type'] === 'forward' && $_POST['forward_list'] === '') {
    set_page_message(tr('Forward list is empty!'));
    return;
  }

  schedule_mail_account($sql, $dmn_id, $dmn_name);

}

function gen_page_mail_acc_props(&$tpl, &$sql, $user_id)
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

  list($mail_acc_cnt,
       $dmn_mail_acc_cnt,
       $sub_mail_acc_cnt,
       $als_mail_acc_cnt) = get_domain_running_mail_acc_cnt($sql, $dmn_id);

  if ($dmn_mailacc_limit != 0 &&  $mail_acc_cnt >= $dmn_mailacc_limit) {
    set_page_message(tr('Mail accounts limit expired!'));
    header("Location: email_accounts.php");
    die();
  } else {
    if (!isset($_POST['uaction'])) {
      gen_page_form_data($tpl, $dmn_name, 'no');
      gen_dmn_als_list($tpl, $sql, $dmn_id, 'no');
      gen_dmn_sub_list($tpl, $sql, $dmn_id, $dmn_name, 'no');
      gen_page_js($tpl);
    } else if (isset($_POST['uaction']) && $_POST['uaction'] === 'add_user') {
      gen_page_form_data($tpl, $dmn_name, 'yes');
      gen_dmn_als_list($tpl, $sql, $dmn_id, 'yes');
      gen_dmn_sub_list($tpl, $sql, $dmn_id, $dmn_name, 'yes');
      check_mail_acc_data($tpl, $sql, $dmn_id, $dmn_name);
    }
  }
}

function gen_page_js(&$tpl)
{
  if (isset($_SESSION['subdomain_count']) && isset($_SESSION['alias_count'])) { // no subdomains and no alias
    $tpl -> parse('JS_NOT_DOMAIN', 'js_not_domain');
    $tpl -> assign('JS_TO_SUBDOMAIN', '');
    $tpl -> assign('JS_TO_ALIAS_DOMAIN', '');
    $tpl -> assign('JS_TO_ALL_DOMAIN', '');
  } else if (isset($_SESSION['subdomain_count']) && !isset($_SESSION['alias_count'])) { //no subdomains - alaias available
    $tpl -> assign('JS_NOT_DOMAIN', '');
    $tpl -> assign('JS_TO_SUBDOMAIN', '');
    $tpl -> parse('JS_TO_ALIAS_DOMAIN', 'js_to_alias_domain');
    $tpl -> assign('JS_TO_ALL_DOMAIN', '');
  } else if (!isset($_SESSION['subdomain_count']) && isset($_SESSION['alias_count'])) { //no alias - subdomain available
    $tpl -> assign('JS_NOT_DOMAIN', '');
    $tpl -> parse('JS_TO_SUBDOMAIN', 'js_to_subdomain');
    $tpl -> assign('JS_TO_ALIAS_DOMAIN', '');
    $tpl -> assign('JS_TO_ALL_DOMAIN', '');
  } else { // there are subdomains and aliases
    $tpl -> assign('JS_NOT_DOMAIN', '');
    $tpl -> assign('JS_TO_SUBDOMAIN', '');
    $tpl -> assign('JS_TO_ALIAS_DOMAIN', '');
    $tpl -> parse('JS_TO_ALL_DOMAIN', 'js_to_all_domain');
  }

  if (isset($GLOBALS['subdomain_count']))
    unset($GLOBALS['subdomain_count']);
  if (isset($GLOBALS['alias_count']))
    unset($GLOBALS['alias_count']);
  if (isset($_SESSION['subdomain_count']))
    unset($_SESSION['subdomain_count']);
  if (isset($_SESSION['alias_count']))
    unset($_SESSION['alias_count']);
}

//
// common page data.
//
if (isset($_SESSION['email_support']) && $_SESSION['email_support'] == "no")
{
  header("Location: index.php");
}

global $cfg;
$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(array('TR_CLIENT_ADD_MAIL_ACC_PAGE_TITLE' => tr('VHCS - Client/Add Mail User'),
                     'THEME_COLOR_PATH' => "../themes/$theme_color",
                     'THEME_CHARSET' => tr('encoding'),
                     'TID' => $_SESSION['layout_id'],
                     'VHCS_LICENSE' => $cfg['VHCS_LICENSE'],
                     'ISP_LOGO' => get_logo($_SESSION['user_id'])));

//
// dynamic page data.
//
gen_page_mail_acc_props($tpl, $sql, $_SESSION['user_id']);

//
// static page messages.
//

gen_client_menu($tpl, $cfg['CLIENT_TEMPLATE_PATH'].'/menu_email_accounts.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$tpl -> assign(array('TR_ADD_MAIL_USER' => tr('Add mail users'),
                     'TR_USERNAME' => tr('Username'),
                     'TR_TO_MAIN_DOMAIN' => tr('To main domain'),
                     'TR_TO_DMN_ALIAS' => tr('To domain alias'),
                     'TR_TO_SUBDOMAIN' => tr('To subdomain'),
                     'TR_NORMAL_MAIL' => tr('Normal mail'),
                     'TR_PASSWORD' => tr('Password'),
                     'TR_PASSWORD_REPEAT' => tr('Password repeat'),
                     'TR_FORWARD_MAIL' => tr('Forward mail'),
                     'TR_FORWARD_TO' => tr('Forward to'),
                     'TR_ADD' => tr('Add')));

gen_page_message($tpl);
$tpl -> parse('PAGE', 'page');
$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

?>
