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

$tpl -> define_dynamic('page', $cfg['ADMIN_TEMPLATE_PATH'].'/reseller_statistics.tpl');

$tpl -> define_dynamic('page_message', 'page');
$tpl -> define_dynamic('hosting_plans', 'page');

$tpl -> define_dynamic('traffic_table', 'page');

$tpl -> define_dynamic('month_list', 'traffic_table');

$tpl -> define_dynamic('year_list', 'traffic_table');

$tpl -> define_dynamic('reseller_entry', 'traffic_table');

$tpl -> define_dynamic('scroll_prev_gray', 'page');

$tpl -> define_dynamic('scroll_prev', 'page');

$tpl -> define_dynamic('scroll_next_gray', 'page');

$tpl -> define_dynamic('scroll_next', 'page');


global $cfg;
$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(
                array(
                        'TR_ADMIN_RESELLER_STATISTICS_PAGE_TITLE' => tr('ISPCP - Reseller statistics'),
                        'THEME_COLOR_PATH' => "../themes/$theme_color",
                        'THEME_CHARSET' => tr('encoding'),
						'ISP_LOGO' => get_logo($_SESSION['user_id']),
                        'ISPCP_LICENSE' => $cfg['ISPCP_LICENSE']
                     )
              );

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

function generate_page ( &$tpl )
{

    global $month, $year, $sql, $cfg;


	$start_index = 0;

	$rows_per_page = $cfg['DOMAIN_ROWS_PER_PAGE'];

	if (isset($_GET['psi']) && is_numeric($_GET['psi'])) {
		$start_index = $_GET['psi'];

	} else if (isset($_POST['psi']) && is_numeric($_GET['psi'])) {
		$start_index = $_POST['psi'];
	}

	$tpl -> assign(
					array(
							'POST_PREV_PSI' => $start_index
						)
					);


// count query
    $count_query = <<<SQL_QUERY
        SELECT
            COUNT(admin_id) AS cnt
        FROM
            admin
        WHERE
            admin_type = 'reseller'
SQL_QUERY;

    $query = <<<SQL_QUERY
        SELECT
            admin_id, admin_name
        FROM
            admin
        WHERE
            admin_type = 'reseller'
        ORDER BY
            admin_name DESC
        LIMIT
            $start_index, $rows_per_page
SQL_QUERY;

    $rs = exec_query($sql, $count_query, array());
    $records_count = $rs -> fields['cnt'];

    $rs = exec_query($sql, $query, array());

    if ($rs -> RowCount() == 0) {

         /* $tpl -> assign(
            array(
                'TRAFFIC_TABLE' => '',
                'MESSAGE' => tr('Not found reseller(s) in your system!')
                )
            ); */
			$tpl -> assign(
							array(
									'TRAFFIC_TABLE' => '',
									'SCROLL_PREV' => '',
									'SCROLL_NEXT' => '',

								 )
						  );


			set_page_message(tr('Not found reseller(s) in your system!'));
			return;

    } else {

		$prev_si = $start_index - $rows_per_page;

		if ($start_index == 0) {

				$tpl -> assign('SCROLL_PREV', '');

		} else {

				$tpl -> assign(
								array(
										'SCROLL_PREV_GRAY' => '',
										'PREV_PSI' => $prev_si
									 )
							  );

		}

		$next_si = $start_index + $rows_per_page;

		if ($next_si + 1 > $records_count) {

				$tpl -> assign('SCROLL_NEXT', '');

		} else {

				$tpl -> assign(
								array(
										'SCROLL_NEXT_GRAY' => '',
										'NEXT_PSI' => $next_si
									 )
							  );

		}




        $tpl -> assign(
            array(
                'PAGE_MESSAGE' => ''
                )
            );

        gen_select_lists($tpl, @$month, @$year);

        $row = 1;

        while (!$rs -> EOF) {

            generate_reseller_entry($tpl, $rs->fields['admin_id'], $rs->fields['admin_name'], $row++);

            $rs ->MoveNext();
        }

    }

    $tpl -> parse('TRAFFIC_TABLE', 'traffic_table');

}

function generate_reseller_entry (&$tpl, $reseller_id, $reseller_name, $row) {

    global $crnt_month, $crnt_year;

    list (
           $rdmn_current, $rdmn_max,
           $rsub_current, $rsub_max,
           $rals_current, $rals_max,
           $rmail_current, $rmail_max,
           $rftp_current, $rftp_max,
           $rsql_db_current, $rsql_db_max,
           $rsql_user_current, $rsql_user_max,
           $rtraff_current, $rtraff_max,
           $rdisk_current, $rdisk_max
         ) = generate_reseller_props($reseller_id);

    list (
           $udmn_current, $udmn_max, $udmn_uf,
           $usub_current, $usub_max, $usub_uf,
           $uals_current, $uals_max, $uals_uf,
           $umail_current, $umail_max, $umail_uf,
           $uftp_current, $uftp_max, $uftp_uf,
           $usql_db_current, $usql_db_max, $usql_db_uf,
           $usql_user_current, $usql_user_max, $usql_user_uf,
           $utraff_current, $utraff_max, $utraff_uf,
           $udisk_current, $udisk_max, $udisk_uf
         ) = generate_reseller_users_props($reseller_id);

    $rtraff_max = $rtraff_max * 1024 * 1024;

    $rtraff_current = $rtraff_current * 1024 * 1024;


    $rdisk_max = $rdisk_max * 1024 * 1024;

    $rdisk_current = $rdisk_current * 1024 * 1024;


    $utraff_max = $utraff_max * 1024 * 1024;

    $udisk_max = $udisk_max * 1024 * 1024;


    $traff_percent = calc_bar_value($utraff_current, $rtraff_max , 400);

    list($traff_percent, $traff_red, $traff_green) = make_usage_vals($utraff_current, $rtraff_max);

    list($disk_percent, $disk_red, $disk_green) = make_usage_vals($udisk_current, $rdisk_max);

	$traff_show_percent = $traff_percent;

	$disk_show_percent = $disk_percent;

	if ($traff_percent > 100)
	{
		$traff_percent = 100;
	}

	if ($disk_percent > 100)
	{
		$disk_percent = 100;
	}



    if ($row % 2 == 0) {
        $tpl -> assign(
                array(
                    'ITEM_CLASS' => 'content',
                    )
                );
    }
    else{
        $tpl -> assign(
                array(
                    'ITEM_CLASS' => 'content2',
                    )
                );
    }
    $tpl -> assign(
                    array(
                            'RESELLER_NAME' => $reseller_name,
                            'RESELLER_ID' => $reseller_id,
                            'MONTH' => $crnt_month,
                            'YEAR' => $crnt_year,

                            'TRAFF_SHOW_PERCENT' => $traff_show_percent,
							'TRAFF_PERCENT' => $traff_percent,
                            'TRAFF_USED' => make_hr($utraff_current),
                            'TRAFF_CURRENT' => make_hr($rtraff_current),
                            'TRAFF_MAX' => ($rtraff_max) ? make_hr($rtraff_max) : tr('unlimited'),

                            'DISK_SHOW_PERCENT' => $disk_show_percent,
							'DISK_PERCENT' => $disk_percent,
                            'DISK_USED' => make_hr($udisk_current),
                            'DISK_CURRENT' => make_hr($rdisk_current),
                            'DISK_MAX' => ($rdisk_max) ? make_hr($rdisk_max) : tr('unlimited'),

                            'DMN_USED' => $udmn_current,
                            'DMN_CURRENT' => $rdmn_current,
                            'DMN_MAX' => ($rdmn_max) ? $rdmn_max : tr('unlimited'),

                            'SUB_USED' => $usub_current,
                            'SUB_CURRENT' => $rsub_current,
                            'SUB_MAX' => ($rsub_max) ? $rsub_max : tr('unlimited'),

                            'ALS_USED' => $uals_current,
                            'ALS_CURRENT' => $rals_current,
                            'ALS_MAX' => ($rals_max) ? $rals_max : tr('unlimited'),

                            'MAIL_USED' => $umail_current,
                            'MAIL_CURRENT' => $rmail_current,
                            'MAIL_MAX' => ($rmail_max) ? $rmail_max : tr('unlimited'),

                            'FTP_USED' => $uftp_current,
                            'FTP_CURRENT' => $rftp_current,
                            'FTP_MAX' => ($rftp_max) ? $rftp_max : tr('unlimited'),

                            'SQL_DB_USED' => $usql_db_current,
                            'SQL_DB_CURRENT' => $rsql_db_current,
                            'SQL_DB_MAX' => ($rsql_db_max) ? $rsql_db_max : tr('unlimited'),
                            'TR_OF' => tr('of'),

                            'SQL_USER_USED' => $usql_user_current,
                            'SQL_USER_CURRENT' => $rsql_user_current,
                            'SQL_USER_MAX' => ($rsql_user_max) ? $rsql_user_max : tr('unlimited')

                         )
                  );

    $tpl -> parse('RESELLER_ENTRY', '.reseller_entry');

}


/*
 *
 * static page messages.
 *
 */

$crnt_month = '';
$crnt_year = '';

gen_admin_mainmenu($tpl, $cfg['ADMIN_TEMPLATE_PATH'].'/main_menu_statistics.tpl');
gen_admin_menu($tpl, $cfg['ADMIN_TEMPLATE_PATH'].'/menu_statistics.tpl');

generate_page ( &$tpl );

$tpl -> assign(
                array(
                       'TR_RESELLER_STATISTICS' => tr('Reseller statistics table'),
                       'TR_MONTH' => tr('Month'),
                       'TR_YEAR' => tr('Year'),
                       'TR_SHOW' => tr('Show'),
                       'TR_RESELLER_NAME' => tr('Reseller name'),
                       'TR_TRAFF' => tr('Traffic'),
                       'TR_DISK' => tr('Disk'),
                       'TR_DOMAIN' => tr('Domain'),
                       'TR_SUBDOMAIN' => tr('Subdomain'),
                       'TR_ALIAS' => tr('Alias'),
                       'TR_MAIL' => tr('Mail'),
                       'TR_FTP' => tr('FTP'),
                       'TR_SQL_DB' => tr('SQL database'),
                       'TR_SQL_USER' => tr('SQL user'),
                     )
              );

gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if ($cfg['DUMP_GUI_DEBUG']) dump_gui_debug();

unset_messages();
?>