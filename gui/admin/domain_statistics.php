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

$tpl -> define_dynamic('page', $cfg['ADMIN_TEMPLATE_PATH'].'/domain_statistics.tpl');

$tpl -> define_dynamic('page_message', 'page');
$tpl -> define_dynamic('hosting_plans', 'page');

$tpl -> define_dynamic('month_list', 'page');

$tpl -> define_dynamic('year_list', 'page');

$tpl -> define_dynamic('traffic_table', 'page');

$tpl -> define_dynamic('traffic_table_item', 'traffic_table');


global $cfg;
$theme_color = $cfg['USER_INITIAL_THEME'];


$tpl -> assign(
                array(
                        'TR_ADMIN_DOMAIN_STATISTICS_PAGE_TITLE' => tr('ISPCP - Domain Statistics Data'),
                        'THEME_COLOR_PATH' => "../themes/$theme_color",
                        'THEME_CHARSET' => tr('encoding'),
						'ISP_LOGO' => get_logo($_SESSION['user_id']),
                        'ISPCP_LICENSE' => $cfg['ISPCP_LICENSE']
                     )
              );


if (isset($_POST['domain_id'])){

    $domain_id= $_POST['domain_id'];
}
else if (isset($_GET['domain_id'])){

    $domain_id= $_GET['domain_id'];
}

$year= 0;
$month = 0;


if (isset($_POST['month']) && isset($_POST['year'])){

    $year= $_POST['year'];

    $month = $_POST['month'];
}
else if (isset($_GET['month']) && isset($_GET['year'])){

    $month= $_GET['month'];

    $year= $_GET['year'];
}


if ( !is_numeric($domain_id) || !is_numeric($month) || !is_numeric($year)) {

    header("Location: reseller_statistics.php");

    die();

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
            domain_id=? and dtraff_time>? and dtraff_time<?
SQL_QUERY;

        $rs =  exec_query($sql, $query, array($domain_id, $from, $to));

        if($rs -> RecordCount() == 0)
        {
            return array(0,0,0,0);
        }
        else{
            return
                array(
                    $rs->fields['web_dr'], $rs->fields['ftp_dr'],

                    $rs->fields['pop_dr'], $rs->fields['mail_dr'],
                );
        }

}


function generate_page (&$tpl, $domain_id) {

  global $sql, $month,$year,$cfg;
  global $web_trf, $ftp_trf, $smtp_trf, $pop_trf,
  		 $sum_web, $sum_ftp, $sum_mail, $sum_pop;

  $fdofmnth = mktime(0,0,0,$month,1,$year);
			$ldofmnth = mktime(1,0,0,$month+1,0,$year);

    if ($month == date('m') && $year== date('Y')) {
        $curday = date('j');
    }
    else {
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
	for($i=1;$i<=$curday;$i++){

        $ftm = mktime(0,0,0,$month,$i,$year);

        $ltm = mktime(23,59,59,$month,$i,$year);


        $query = <<<SQL_QUERY
            select
                dtraff_web,dtraff_ftp,dtraff_mail,dtraff_pop,dtraff_time
            from
                domain_traffic
            where
                domain_id=? and dtraff_time>? and dtraff_time<?
SQL_QUERY;

        $rs =  exec_query($sql, $query, array($domain_id, $ftm, $ltm));

        $has_data = false;

            list($web_trf,
                 $ftp_trf,
                 $pop_trf,
                 $smtp_trf) = get_domain_trafic($ftm, $ltm, $domain_id);


			$date_formt = $cfg['DATE_FORMAT'];
			if ($web_trf == 0 && $ftp_trf == 0 && $smtp_trf == 0 && $pop_trf ==0){
				$tpl -> assign(
						array(
							'MONTH' => $month,
							'YEAR' => $year,
							'DOMAIN_ID' => $domain_id,
							'DATE' => date($date_formt, strtotime($year."-".$month."-".$i)),
							'WEB_TRAFFIC' => 0,
							'FTP_TRAFFIC' => 0,
							'SMTP_TRAFFIC' => 0,
							'POP3_TRAFFIC' => 0,
							'ALL_TRAFFIC' => 0,


						)
					);
			} else {

				  if ($counter % 2 == 0) {

               			 $tpl -> assign('ITEM_CLASS', 'content');

          		  } else {

		                $tpl -> assign('ITEM_CLASS', 'content2');
           		  }
				$sum_web += $web_trf;
				$sum_ftp += $ftp_trf;
				$sum_mail += $smtp_trf;
				$sum_pop += $pop_trf;

				$tpl -> assign(
					array(
						'DATE' => date($date_formt, strtotime($year."-".$month."-".$i)),
						'WEB_TRAFFIC' => sizeit($web_trf),
						'FTP_TRAFFIC' => sizeit($ftp_trf),
						'SMTP_TRAFFIC' => sizeit($smtp_trf),
						'POP3_TRAFFIC' => sizeit($pop_trf),
						'ALL_TRAFFIC' => sizeit($web_trf + $ftp_trf + $smtp_trf + $pop_trf),
				   )
				);
				$tpl -> parse('TRAFFIC_TABLE_ITEM', '.traffic_table_item');

				$counter ++;
		}

        $tpl -> assign(
            array(
                'MONTH' => $month,
                'YEAR' => $year,
                'DOMAIN_ID' => $domain_id,

                'ALL_WEB_TRAFFIC' => sizeit($sum_web),
                'ALL_FTP_TRAFFIC' => sizeit($sum_ftp),
                'ALL_SMTP_TRAFFIC' => sizeit($sum_mail),
                'ALL_POP3_TRAFFIC' => sizeit($sum_pop),
                'ALL_ALL_TRAFFIC' => sizeit($sum_web + $sum_ftp + $sum_mail + $sum_pop),
                )
            );

            $tpl -> parse('TRAFFIC_TABLE', 'traffic_table');


        }

    }





/*
 *
 * static page messages.
 *
 */

gen_admin_mainmenu($tpl, $cfg['ADMIN_TEMPLATE_PATH'].'/main_menu_statistics.tpl');
gen_admin_menu($tpl, $cfg['ADMIN_TEMPLATE_PATH'].'/menu_statistics.tpl');

$tpl -> assign(
        array(
                    'TR_DOMAIN_STATISTICS' => tr('Domain statistics'),
                    'TR_MONTH' => tr('Month'),
                    'TR_YEAR' => tr('Year'),
                    'TR_SHOW' => tr('Show'),
                    'TR_WEB_TRAFFIC' => tr('Web traffic'),
                    'TR_FTP_TRAFFIC' => tr('FTP traffic'),
                    'TR_SMTP_TRAFFIC' => tr('SMTP traffic'),
                    'TR_POP3_TRAFFIC' => tr('POP3/IMAP traffic'),
                    'TR_ALL_TRAFFIC' => tr('All traffic'),
                    'TR_ALL' => tr('All'),
                    'TR_DAY' => tr('Day'),
                )
        );

gen_select_lists($tpl, $month, $year);

generate_page($tpl, $domain_id);


gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if ($cfg['DUMP_GUI_DEBUG']) dump_gui_debug();

unset_messages();
?>
