<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2006 by moleSoftware	|
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
$tpl -> define_dynamic('page', $cfg['CLIENT_TEMPLATE_PATH'].'/domain_statistics.tpl');
$tpl -> define_dynamic('page_message', 'page');
$tpl -> define_dynamic('logged_from', 'page');
$tpl -> define_dynamic('month_item', 'page');
$tpl -> define_dynamic('year_item', 'page');
$tpl -> define_dynamic('traff_list', 'page');
$tpl -> define_dynamic('traff_item', 'traff_list');

//
// page functions.
//

function gen_page_date(&$tpl, $month, $year)
{
  for ($i = 1; $i <= 12; $i++) {
    $tpl -> assign(array('MONTH_SELECTED' => ($i == $month) ? 'selected' : '',
                         'MONTH' => $i));
    $tpl -> parse('MONTH_ITEM', '.month_item');
  }

  for ($i = $year - 1; $i <= $year + 1; $i++) {
    $tpl -> assign(array('YEAR_SELECTED' => ($i == $year) ? 'selected' : '',
                         'YEAR' => $i));
    $tpl -> parse('YEAR_ITEM', '.year_item');
  }
}

function gen_page_post_data(&$tpl, $current_month, $current_year)
{
  if (isset($_POST['uaction']) && $_POST['uaction'] === 'show_traff') {
    $current_month = $_POST['month'];
    $current_year = $_POST['year'];
  }

  gen_page_date($tpl, $current_month, $current_year);
  return array($current_month, $current_year);
}

function get_domain_trafic($from, $to, $domain_id)
{
  global $sql;

  $query = <<<SQL_QUERY
        select
            IFNULL(sum(dtraff_web), 0) as web_dr,
            IFNULL(sum(dtraff_ftp), 0) as ftp_dr,
            IFNULL(sum(dtraff_mail), 0) as mail_dr,
            IFNULL(sum(dtraff_pop), 0) as pop_dr
        from
            domain_traffic
        where
            domain_id = ?
          and
            dtraff_time > ?
          and
            dtraff_time < ?
SQL_QUERY;

  $rs = exec_query($sql, $query, array($domain_id, $from, $to));

  if ($rs -> RecordCount() == 0) {
    return array(0,0,0,0);
  } else {
    return
    array($rs->fields['web_dr'], $rs->fields['ftp_dr'],
          $rs->fields['pop_dr'], $rs->fields['mail_dr']);
  }
}

function gen_dmn_traff_list(&$tpl, &$sql, $month, $year, $user_id)
{
  global $web_trf, $ftp_trf, $smtp_trf, $pop_trf,
         $sum_web, $sum_ftp, $sum_mail, $sum_pop;

  $domain_admin_id = $_SESSION['user_id'];
  $query = <<<SQL_QUERY
        select
            domain_id
        from
            domain
        where
            domain_admin_id = ?
SQL_QUERY;

  $rs = exec_query($sql, $query, array($domain_admin_id));
  $domain_id = $rs->fields('domain_id');
  $fdofmnth = mktime(0,0,0,$month,1,$year);
  $ldofmnth = mktime(1,0,0,$month+1,0,$year);

  if ($month == date('m') && $year== date('Y')) {
    $curday = date('j');
  } else {
    $tmp = mktime(1,0,0,$month+1,0,$year);
    $curday = date('j',$tmp);
  }

  $curtimestamp = time();
  $firsttimestamp = mktime(0,0,0,$month,1,$year);
  $all[0] = 0;
  $all[1] = 0;
  $all[2] = 0;
  $all[3] = 0;
  $all[4] = 0;
  $all[5] = 0;
  $all[6] = 0;
  $all[7] = 0;
  $counter = 0;

  for ($i = 1; $i <= $curday; $i++) {
    $ftm = mktime(0,0,0,$month,$i,$year);
    $ltm = mktime(23,59,59,$month,$i,$year);
    $query = <<<SQL_QUERY
        select
            dtraff_web,dtraff_ftp,dtraff_mail,dtraff_pop,dtraff_time
        from
            domain_traffic
        where
            domain_id = ?
          and
            dtraff_time > ?
          and
            dtraff_time < ?
SQL_QUERY;

    $rs = exec_query($sql, $query, array($domain_id, $ftm, $ltm));

    $has_data = false;
    list($web_trf,
         $ftp_trf,
         $pop_trf,
         $smtp_trf) = get_domain_trafic($ftm, $ltm, $domain_id);

    if ($counter % 2 == 0) {
      $tpl -> assign('ITEM_CLASS', 'content');
    } else {
      $tpl -> assign('ITEM_CLASS', 'content2');
    }
    $sum_web += $web_trf;
    $sum_ftp += $ftp_trf;
    $sum_mail += $smtp_trf;
    $sum_pop += $pop_trf;

    global $cfg;
    $date_formt = $cfg['DATE_FORMAT'];
    $tpl -> assign(array('DATE' => date($date_formt, strtotime($year."-".$month."-".$i)),
                         'WEB_TRAFFIC' => sizeit($web_trf),
                         'FTP_TRAFFIC' => sizeit($ftp_trf),
                         'SMTP_TRAFFIC' => sizeit($smtp_trf),
                         'POP3_TRAFFIC' => sizeit($pop_trf),
                         'ALL_TRAFFIC' => sizeit($web_trf + $ftp_trf + $smtp_trf + $pop_trf),
                         'WEB_TRAFF' => sizeit($web_trf),
                         'FTP_TRAFF' => sizeit($ftp_trf),
                         'SMTP_TRAFF' => sizeit($smtp_trf),
                         'POP_TRAFF' => sizeit($pop_trf),
                         'SUM_TRAFF' => sizeit($web_trf + $ftp_trf + $smtp_trf + $pop_trf),
                         'CONTENT' => ($i % 2 == 0) ? 'content' : 'content2'));
    $tpl -> assign(array('MONTH' => $month,
                         'YEAR' => $year,
                         'DOMAIN_ID' => $domain_id,
                         'WEB_ALL' => sizeit($sum_web),
                         'FTP_ALL' => sizeit($sum_ftp),
                         'SMTP_ALL' => sizeit($sum_mail),
                         'POP_ALL' => sizeit($sum_pop),
                         'SUM_ALL' => sizeit($sum_web + $sum_ftp + $sum_mail + $sum_pop)));
    $tpl -> parse('TRAFF_ITEM', '.traff_item');
    $counter ++;
}


/*
    $start_date = mktime(0,0,0, $month, 1, $year);
    $end_date = mktime(0,0,0, $month + 1, 1, $year);
    $dmn_id = get_user_domain_id($sql, $user_id);
    $query = <<<SQL_QUERY
    	select
            dtraff_time as traff_date,
        	dtraff_web as web_traff,
            dtraff_ftp as ftp_traff,
            dtraff_mail as smtp_traff,
            dtraff_pop as pop_traff,
            (dtraff_web + dtraff_ftp + dtraff_mail + dtraff_pop) as sum_traff
        from
        	domain_traffic
        where
        	domain_id = '$dmn_id'
          and
          	dtraff_time >= '$start_date'
          and
            dtraff_time < '$end_date'
        order by
            dtraff_time
SQL_QUERY;

    $rs = execute_query($sql, $query);

    if ($rs -> RecordCount() == 0) {

        $tpl -> assign('TRAFF_LIST', '');

        set_page_message(tr('Traffic accounting for the selected month is missing!'));

    } else {

        $web_all = 0; $ftp_all = 0; $smtp_all = 0; $pop_all = 0; $sum_all = 0; $i = 1;

        while (!$rs -> EOF) {

            $tpl -> assign(
                            array(
                                    'DATE' => date("d.m.Y, G:i", $rs -> fields['traff_date']),
                                    'WEB_TRAFF' => sizeit($rs -> fields['web_traff']),
                                    'FTP_TRAFF' => sizeit($rs -> fields['ftp_traff']),
                                    'SMTP_TRAFF' => sizeit($rs -> fields['smtp_traff']),
                                    'POP_TRAFF' => sizeit($rs -> fields['pop_traff']),
                                    'SUM_TRAFF' => sizeit($rs -> fields['sum_traff']),
                                    'CONTENT' => ($i % 2 == 0) ? 'content3' : 'content2'

                                 )
                          );

            $tpl -> parse('TRAFF_ITEM', '.traff_item');

            $web_all += $rs -> fields['web_traff'];

            $ftp_all += $rs -> fields['ftp_traff'];

            $smtp_all += $rs -> fields['smtp_traff'];

            $pop_all += $rs -> fields['pop_traff'];

            $sum_all += $rs -> fields['sum_traff'];

            $rs -> MoveNext(); $i++;

        }

        $tpl -> assign(
                        array(
                                'WEB_ALL' => sizeit($web_all),
                                'FTP_ALL' => sizeit($ftp_all),
                                'SMTP_ALL' => sizeit($smtp_all),
                                'POP_ALL' => sizeit($pop_all),
                                'SUM_ALL' => sizeit($sum_all)
                             )
                      );

    }
*/

}


//
// common page data.
//

global $cfg;
$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(array('TR_CLIENT_DOMAIN_STATISTICS_PAGE_TITLE' => tr('VHCS - Client/Domain Statistics'),
                     'THEME_COLOR_PATH' => "../themes/$theme_color",
                     'THEME_CHARSET' => tr('encoding'),
                     'TID' => $_SESSION['layout_id'],
                     'VHCS_LICENSE' => $cfg['VHCS_LICENSE'],
                     'ISP_LOGO' => get_logo($_SESSION['user_id'])));

//
// dynamic page data.
//
$current_month = date("m", time());
$current_year = date("Y", time());
list ($current_month, $current_year) = gen_page_post_data($tpl, $current_month, $current_year);
gen_dmn_traff_list($tpl, $sql, $current_month, $current_year, $_SESSION['user_id']);

//
// static page messages.
//

gen_client_mainmenu($tpl, $cfg['CLIENT_TEMPLATE_PATH'].'/main_menu_statistics.tpl');
gen_client_menu($tpl, $cfg['CLIENT_TEMPLATE_PATH'].'/menu_statistics.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$tpl -> assign(array('TR_DOMAIN_STATISTICS' => tr('Domain statistics'),
					 'DOMAIN_URL' => 'http://'.$_SESSION['user_logged'].'/stats/',
					 'TR_AWSTATS' => tr('Web Stats'),
                     'TR_MONTH' => tr('Month'),
                     'TR_YEAR' => tr('Year'),
                     'TR_SHOW' => tr('Show'),
                     'TR_DATE' => tr('Date'),
                     'TR_WEB_TRAFF' => tr('WEB'),
                     'TR_FTP_TRAFF' => tr('FTP'),
                     'TR_SMTP_TRAFF' => tr('SMTP'),
                     'TR_POP_TRAFF' => tr('POP/IMAP'),
                     'TR_SUM' => tr('Sum'),
                     'TR_ALL' => tr('Total')));

gen_page_message($tpl);
$tpl -> parse('PAGE', 'page');
$tpl -> prnt();
if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();
unset_messages();

?>
