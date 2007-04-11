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

//
// page functions.
//

function gen_def_layout(&$tpl, &$sql, $user_def_layout)
{

    $layout = 'blue';

    if ($layout === $user_def_layout) {

        $selected = 'selected';

    } else {

        $selected = '';

    }

    $tpl -> assign(
                    array(
                            'LAYOUT_VALUE' => $layout,
                            'LAYOUT_SELECTED' => $selected,
                            'LAYOUT_NAME' => $layout
                         )
                  );

    $tpl -> parse('DEF_LAYOUT', '.def_layout');

    $layout = 'green';

    if ($layout === $user_def_layout) {

        $selected = 'selected';

    } else {

        $selected = '';

    }

    $tpl -> assign(
                    array(
                            'LAYOUT_VALUE' => $layout,
                            'LAYOUT_SELECTED' => $selected,
                            'LAYOUT_NAME' => $layout
                         )
                  );

    $tpl -> parse('DEF_LAYOUT', '.def_layout');

    $layout = 'red';

    if ($layout === $user_def_layout) {

        $selected = 'selected';

    } else {

        $selected = '';

    }

    $tpl -> assign(
                    array(
                            'LAYOUT_VALUE' => $layout,
                            'LAYOUT_SELECTED' => $selected,
                            'LAYOUT_NAME' => $layout
                         )
                  );

    $tpl -> parse('DEF_LAYOUT', '.def_layout');

    $layout = 'yellow';

    if ($layout === $user_def_layout) {

        $selected = 'selected';

    } else {

        $selected = '';

    }

    $tpl -> assign(
                    array(
                            'LAYOUT_VALUE' => $layout,
                            'LAYOUT_SELECTED' => $selected,
                            'LAYOUT_NAME' => $layout
                         )
                  );

    $tpl -> parse('DEF_LAYOUT', '.def_layout');

}

function gen_traff_usage(&$tpl, $usage, $max_usage, $bars_max)
{
	if(0 !== $max_usage) {
    	list($percent, $bars) = calc_bars($usage, $max_usage, $bars_max);
		$max_usage = sizeit($max_usage);

	} else {
		$percent	=	0;
		$bars		=	0;
		$max_usage  = tr('unlimited');
	}

    $traffic_usage_data = sprintf("%s%% [%s ".tr('of')." %s]", $percent, sizeit($usage), $max_usage);

    $tpl -> assign(
                    array(
                            'TRAFFIC_USAGE_DATA' => $traffic_usage_data,
                            'TRAFFIC_BARS'  => $bars
                         )
                  );

}

function gen_disk_usage(&$tpl, $usage, $max_usage, $bars_max)
{

	if(0 !== $max_usage) {
    	list($percent, $bars) = calc_bars($usage, $max_usage, $bars_max);
		$max_usage = sizeit($max_usage);
	}

	else{
		$percent	= 0;
		$bars		= 0;
		$max_usage  = tr('unlimited');
	}

    $traffic_usage_data = sprintf("%s%% [%s ".tr('of')." %s]", $percent, sizeit($usage), $max_usage);

    $tpl -> assign(
                    array(
                            'DISK_USAGE_DATA' => $traffic_usage_data,
                            'DISK_BARS'  => $bars
                         )
                  );

}

function generate_page_data(&$tpl, $reseller_id, $reseller_name)
{
	global $sql, $crnt_month, $crnt_year;
    $crnt_month = date("m");
    $crnt_year = date("Y");
    //global

	$tmpArr = get_reseller_default_props($sql, $reseller_id);
	//$tmpArr = generate_reseller_props($reseller_id);

    if ($tmpArr != NULL) { // there are data in db

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
			) = $tmpArr;
	}else
	{
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
			) = array(0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0);
	}

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
		 ) = generate_reseller_user_props($reseller_id);

	// Convert into MB values
    $rtraff_max		= $rtraff_max * 1024 * 1024;

    $rtraff_current	= $rtraff_current * 1024 * 1024;

    $rdisk_max		= $rdisk_max * 1024 * 1024;

    $rdisk_current	= $rdisk_current * 1024 * 1024;

    $utraff_max		= $utraff_max * 1024 * 1024;

    $udisk_max		= $udisk_max * 1024 * 1024;


    list($traff_percent, $traff_red, $traff_green)	= make_usage_vals($utraff_current, $rtraff_max);

    list($disk_percent, $disk_red, $disk_green)		= make_usage_vals($udisk_current, $rdisk_max);

	gen_traff_usage($tpl, $utraff_current, $rtraff_max, 400);

    gen_disk_usage($tpl, $udisk_current, $rdisk_max, 400);

    if ($rtraff_max > 0)
	{
        if ($utraff_current > $rtraff_max)
		{
            $tpl -> assign(
                            'TR_TRAFFIC_WARNING', tr('You are exceeding your traffic limit!')
                          );

        }else
		{
            $tpl -> assign('TRAFF_WARN', '');
        }

    } else {
        if ($utraff_current > 1024 * 1024 * 1024 * 1024) {
            $tpl -> assign('TR_TRAFFIC_WARNING', tr('You are exceeding your UNLIMITED traffic limit!'));
        } else {
            $tpl -> assign('TRAFF_WARN', '');
        }
    }


	//warning HDD Usage
	if ($rdisk_max > 0)
	{
        if ($udisk_current > $rdisk_max)
		{
            $tpl -> assign(
                            'TR_DISK_WARNING', tr('You are exceeding your disk limit!')
                          );

        }else
		{
            $tpl -> assign('DISK_WARN', '');
        }

    } else {
        if ($udisk_current > 1024 * 1024 * 1024 * 1024) {
            $tpl -> assign('TR_DISK_WARNING', tr('You are exceeding your UNLIMITED disk limit!'));
        } else {
            $tpl -> assign('DISK_WARN', '');
        }
    }



	$tpl -> assign(
                    array(
                            'ACCOUNT_NAME' => tr("Account name"),
							'GENERAL_INFO' => tr("General information"),
							"DOMAINS" => tr("User accounts"),
							"SUBDOMAINS" => tr("Subdomains"),
							"ALIASES" => tr("Aliases"),
							"MAIL_ACCOUNTS" => tr("Mail account"),
							"TR_FTP_ACCOUNTS" => tr("FTP account"),
							"SQL_DATABASES" => tr("SQL databases"),
							"SQL_USERS" => tr("SQL users"),
							"TRAFFIC" => tr("Traffic"),
							"DISK" => tr("Disk"),
							"TR_EXTRAS" => tr("Extras")
	                     )
                  );

    $tpl -> assign(
                    array(
                            'RESELLER_NAME' => $reseller_name,

                            'TRAFF_RED' => $traff_red * 3,
                            'TRAFF_GREEN' => $traff_green * 3,
                            'TRAFF_PERCENT' => $traff_percent,
                            'TRAFF_USED' => make_hr($utraff_current),
                            'TRAFF_CURRENT' => make_hr($rtraff_current),
                            'TRAFF_MAX' => ($rtraff_max) ? make_hr($rtraff_max) : tr('unlimited'),

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

                            'SQL_USER_USED' => $usql_user_current,
                            'SQL_USER_CURRENT' => $rsql_user_current,
                            'SQL_USER_MAX' => ($rsql_user_max) ? $rsql_user_max : tr('unlimited'),
							'EXTRAS' => ''

                         )
                  );

}

function gen_messages_table (&$tpl, $admin_id) {

    global $sql;

    $query = <<<SQL_QUERY
          select
              ticket_id
          from
              tickets
          where
              (ticket_from = ? or ticket_to = ?)
            and
              ticket_reply = '0'
            and
              (ticket_status = '1' or ticket_status = '4')
SQL_QUERY;
    $res = exec_query($sql, $query, array($admin_id, $admin_id));

    $questions = $res -> RowCount();

    if ($questions == 0) {

        $tpl -> assign(
                         array(
                                 'TR_NO_NEW_MESSAGES' => tr('You have no new support questions!'),
                                 'MSG_ENTRY' => ''
                              )
                      );

    } else {

        $tpl -> assign(
                         array(
                                 'NO_MESSAGES' => '',
                                 'TR_YOU_HAVE' => tr('You have'),
                                 'TR_NEW' => tr('new'),
                                 'TR_VIEW' => tr('View'),
								 'TR_MSG_TYPE' => tr('support questions'),
								 'MSG_NUM' => $questions,
                              )
                      );


            $tpl -> parse('MSG_ENTRY', '.msg_entry');

        }

}

//
// Begin page path
// ###############

include '../include/ispcp-lib.php';

check_login();

$tpl = new pTemplate();

$tpl -> define_dynamic('page', $cfg['RESELLER_TEMPLATE_PATH'].'/index.tpl');

$tpl -> define_dynamic('def_language', 'page');

$tpl -> define_dynamic('def_layout', 'page');

$tpl -> define_dynamic('no_messages', 'page');

$tpl -> define_dynamic('msg_entry', 'page');

$tpl -> define_dynamic('traff_warn', 'page');

$tpl -> define_dynamic('layout', 'page');

$tpl -> define_dynamic('logged_from', 'page');

$tpl -> define_dynamic('traff_warn', 'page');

if (isset($_POST['uaction']) && $_POST['uaction'] === 'save_lang') {

    $user_id = $_SESSION['user_id'];

    $user_lang = $_POST['def_language'];

    $query = <<<SQL_QUERY
        update
            user_gui_props
        set
            lang = ?
        where
            user_id = ?
SQL_QUERY;

    $rs = exec_query($sql, $query, array($user_lang, $user_id));

	unset($_SESSION['user_def_lang']);
	$_SESSION['user_def_lang'] = $user_lang;

}

//
// common page data.
//

$theme_color = $cfg['USER_INITIAL_THEME'];



$tpl -> assign(
                array(
                        'TR_RESELLER_MAIN_INDEX_PAGE_TITLE' => tr('ISPCP - Reseller/Main Index'),
						'TR_SAVE' => tr('Save'),
						'TR_OF' => tr('of'),
						'TR_MESSAGES' => tr('Messages'),
						'TR_LANGUAGE' => tr('Language'),
						'TR_CHOOSE_DEFAULT_LANGUAGE' => tr('Choose default language'),
						'TR_CHOOSE_DEFAULT_LAYOUT' => tr('Choose default layout'),
						'TR_LAYOUT' => tr('Layout'),
						'TR_TRAFFIC_USAGE' => tr('Traffic usage'),
						'TR_DISK_USAGE' => tr ('Disk usage'),
                        'THEME_COLOR_PATH' => "../themes/$theme_color",
                        'THEME_CHARSET' => tr('encoding'),
                        'ISPCP_LICENSE' => $cfg['ISPCP_LICENSE'],
						'ISP_LOGO' => get_logo($_SESSION['user_id']),
                     )
              );

//
// dynamic page data.
//



generate_page_data($tpl, $_SESSION['user_id'], $_SESSION['user_logged']);

// ko ima jump from other user interface neka esik i optica da ostanat tezi na
// ska4ashtijat user
if (!isset($_SESSION['logged_from']) && !isset($_SESSION['logged_from_id'])) {
  list($user_def_lang, $user_def_layout) = get_user_gui_props($sql, $_SESSION['user_id']);
} else {
  $user_def_layout = $_SESSION['user_theme'];
  $user_def_lang = $_SESSION['user_def_lang'];
}

gen_messages_table($tpl, $_SESSION['user_id']);

gen_logged_from($tpl);

gen_def_language($tpl, $sql, $user_def_lang);

gen_def_layout($tpl, $sql, $user_def_layout);

gen_reseller_mainmenu($tpl, $cfg['RESELLER_TEMPLATE_PATH'].'/main_menu_general_information.tpl');
gen_reseller_menu($tpl, $cfg['RESELLER_TEMPLATE_PATH'].'/menu_general_information.tpl');


//
// static page messages.
//

// gen_page_message($tpl);

$tpl -> assign('LAYOUT', '');

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

unset_messages();
?>
