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

$tpl -> define_dynamic('page', $cfg['ADMIN_TEMPLATE_PATH'].'/admin_log.tpl');

$tpl -> define_dynamic('page_message', 'page');

$tpl -> define_dynamic('log_row', 'page');

$tpl -> define_dynamic('scroll_prev_gray', 'page');

$tpl -> define_dynamic('scroll_prev', 'page');

$tpl -> define_dynamic('scroll_next_gray', 'page');

$tpl -> define_dynamic('scroll_next', 'page');

$tpl -> define_dynamic('clear_log', 'page');


global $cfg;
$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(
                array(
                        'TR_ADMIN_ADMIN_LOG_PAGE_TITLE' => tr('VHCS - Admin/Admin Log'),
                        'THEME_COLOR_PATH' => "../themes/$theme_color",
                        'THEME_CHARSET' => tr('encoding'),
						'ISP_LOGO' => get_logo($_SESSION['user_id']),
                        'VHCS_LICENSE' => $cfg['VHCS_LICENSE']
                     )
              );



 function generate_page ( &$tpl)
{

    global $sql,$cfg;

	$start_index = 0;

	$rows_per_page = 15;

	if (isset($_GET['psi']) && is_numeric($_GET['psi'])) $start_index = $_GET['psi'];


    $count_query = <<<SQL_QUERY
        SELECT
            COUNT(log_id) AS cnt
        FROM
            log
SQL_QUERY;


    $query = <<<SQL_QUERY
        SELECT
            DATE_FORMAT(log_time,'%Y-%m-%d %H:%i') AS dat, log_message
        FROM
            log
        ORDER BY
            log_time DESC
        LIMIT
            $start_index, $rows_per_page
SQL_QUERY;

    $rs = exec_query($sql, $count_query, array());

    $records_count = $rs -> fields['cnt'];

	$rs = execute_query($sql, $query);


    if ($rs -> RowCount() == 0) {

//        set_page_message(tr('Log is empty!'));
        $tpl -> assign(
            array(
                'LOG_ROW' => '',
                'PAG_MESSAGE' => tr('Log is empty!'),
				'USERS_LIST' => '',
				'SCROLL_PREV' => '',
				'SCROLL_NEXT' => '',
				'CLEAR_LOG' => ''
                )
            );
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

        $row = 1;

        while (!$rs -> EOF) {

            if ($row++ % 2 == 0) {
                $tpl -> assign(
                        array(
                            'ROW_CLASS' => 'content',
                            )
                        );
            }
            else{
                $tpl -> assign(
                        array(
                            'ROW_CLASS' => 'content2',
                            )
                        );
            }

            $log_message = str_replace('delete','<font color="#FF0000"><strong>delete</strong></font>',$rs->fields['log_message']);
            $log_message = str_replace('add','<font color="#CC6600"><strong>add</strong></font>',$log_message);
            $log_message = str_replace('change','<font color="#3300FF"><strong>change</strong></font>',$log_message);
            $log_message = str_replace('edit','<font color="#CC6600"><strong>edit</strong></font>',$log_message);
			$log_message = str_replace('unknown','<font color="#CC00FF"><strong>unknown</strong></font>',$log_message);
            $log_message = str_replace('unknow','<font color="#CC00FF"><strong>unknow</strong></font>',$log_message);
			$log_message = str_replace('logged','<font color="#339966"><strong>logged</strong></font>',$log_message);
			$log_message = str_replace('bad password login data','<font color="#FF0000"><strong>bad password login data</strong></font>',$log_message);



			$date_formt = $cfg['DATE_FORMAT']." H:i";
            $tpl -> assign(
                    array(
                        'MESSAGE' => $log_message,
                        'DATE' =>  date($date_formt, strtotime($rs->fields['dat'])),
                        )
              );

            $tpl -> parse('LOG_ROW', '.log_row');

            $rs ->MoveNext();
        }//while
    }
}


function clear_log()
{
    global $sql;

    if (isset($_POST['uaction']) && $_POST['uaction'] === 'clear_log') {

        $query = <<<SQL_QUERY
            delete
                from
            log
SQL_QUERY;

        $rs = exec_query($sql, $query, array());
    }
}



/*
 *
 * static page messages.
 *
 */

gen_admin_menu($tpl, $cfg['ADMIN_TEMPLATE_PATH'].'/menu_general_information.tpl');

clear_log();

generate_page ( $tpl );

$tpl -> assign(
                array(
                       'TR_ADMIN_LOG' => tr('Admin Log'),
                       'TR_CLEAR_LOG' => tr('Clear log'),
                       'TR_DATE' => tr('Date'),
                       'TR_MESSAGE' => tr('Message'),
                     )
              );

//gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

unset_messages();
?>