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

$tpl -> define_dynamic('page', $cfg['RESELLER_TEMPLATE_PATH'].'/domain_alias.tpl');

$tpl -> define_dynamic('page_message', 'page');

$tpl -> define_dynamic('logged_from', 'page');

$tpl -> define_dynamic('table_list', 'page');

$tpl -> define_dynamic('table_item', 'table_list');

$tpl -> define_dynamic('scroll_prev', 'page');

$tpl -> define_dynamic('scroll_next_gray', 'page');

$tpl -> define_dynamic('scroll_next', 'page');

$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(
                array(
                        'TR_ALIAS_PAGE_TITLE' => tr('ISPCP - Manage Domain/Alias'),
                        'THEME_COLOR_PATH' => "../themes/$theme_color",
                        'THEME_CHARSET' => tr('encoding'),
                        'ISPCP_LICENSE' => $cfg['ISPCP_LICENSE'],
						'ISP_LOGO' => get_logo($_SESSION['user_id']),
                     )
              );

/*
 *
 * static page messages.
 *
 */

gen_reseller_mainmenu($tpl, $cfg['RESELLER_TEMPLATE_PATH'].'/main_menu_manage_users.tpl');
gen_reseller_menu($tpl, $cfg['RESELLER_TEMPLATE_PATH'].'/menu_manage_users.tpl');

gen_logged_from($tpl);

$err_txt = "_off_";

generate_als_list($tpl, $_SESSION['user_id'], $err_txt);

generate_als_messages($tpl, $err_txt);

$tpl -> assign(
                array(
						'TR_MANAGE_ALIAS' => tr('Manage alias'),
						'TR_NAME' => tr('Name'),
						'TR_REAL_DOMAIN' => tr('Real domain'),
						'TR_FORWARD' => tr('Forward'),
						'TR_STATUS' => tr('Status'),
						'TR_ACTION' => tr('Action'),
						'TR_ADD_ALIAS' => tr('Add alias'),
						'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete'),
                     )
              );



$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if ($cfg['DUMP_GUI_DEBUG']) dump_gui_debug();

unset_messages();
//
// Function declaration
//


// Generate domain alias list
function generate_als_list(&$tpl, $reseller_id, &$als_err)
{
    global $sql, $cfg;

    $have_aliases = '_no_';

	$start_index = 0;

	$rows_per_page = $cfg['DOMAIN_ROWS_PER_PAGE'];

	$current_psi = 0;
	$_SESSION['search_for'] = '';
	$search_common = '';
	$search_for = '';

	if (isset($_GET['psi'])) {
		$start_index = $_GET['psi'];
		$current_psi = $_GET['psi'];
	}



	if (isset($_POST['uaction']) && !empty($_POST['uaction'])) {

			$_SESSION['search_for'] = trim(clean_input($_POST['search_for']));

			$_SESSION['search_common'] = $_POST['search_common'];

			$search_for = $_SESSION['search_for'];

			$search_common = $_SESSION['search_common'];

	} else {

		if (isset($_SESSION['search_for']) && !isset($_GET['psi'])) {

			unset($_SESSION['search_for']);

			unset($_SESSION['search_common']);

		}

	}
	$tpl -> assign(
                array(
						'PSI' => $current_psi,
						'SEARCH_FOR' => stripslashes($search_for),
						'TR_SEARCH' => tr('Search'),
						'M_ALIAS_NAME' => tr('Alias name'),
						'M_ACCOUNT_NAME' => tr('Account name'),
                     )
              );

	if (isset($_SESSION['search_for']) && $_SESSION['search_for'] != '') {

		if (isset($search_common) && $search_common == 'alias_name'){

			$query = <<<SQL_QUERY
        SELECT
		    t1.*,
			t2.domain_id,
			t2.domain_name,
			t2.domain_created_id
		FROM
		    domain_aliasses AS t1,
			domain AS t2
		WHERE
			alias_name RLIKE '$search_for'
		  AND
			t2.domain_created_id = ?
		  AND
			t1.domain_id = t2.domain_id
		ORDER BY
			t1.alias_name ASC
		LIMIT
			$start_index, $rows_per_page
SQL_QUERY;

			// count query
			$count_query = <<<SQL_QUERY
        SELECT
            COUNT(alias_id) AS cnt
        FROM
		   	domain_aliasses AS t1,
			domain AS t2
		WHERE
			t2.domain_created_id = ?
		  AND
			alias_name RLIKE '$search_for'
		  AND
			t1.domain_id = t2.domain_id
SQL_QUERY;

		} else {

			$query = <<<SQL_QUERY
        SELECT
		    t1.*,
			t2.domain_id,
			t2.domain_name,
			t2.domain_created_id
		FROM
		    domain_aliasses AS t1,
			domain AS t2
		WHERE
			t2.domain_name RLIKE '$search_for'
		  AND
			t1.domain_id = t2.domain_id
		  AND
			t2.domain_created_id = ?
		ORDER BY
			t1.alias_name ASC
		LIMIT
			$start_index, $rows_per_page
SQL_QUERY;


			// count query
			$count_query = <<<SQL_QUERY
        SELECT
            COUNT(alias_id) AS cnt
        FROM
		    domain_aliasses AS t1,
			domain AS t2
		WHERE
			t2.domain_created_id = ?
		  AND
			t2.domain_name RLIKE '$search_for'
		  AND
			t1.domain_id = t2.domain_id
SQL_QUERY;

		}
	} else {

		  	$query = <<<SQL_QUERY
        SELECT
             t1.*,
			 t2.domain_id,
			 t2.domain_name,
			 t2.domain_created_id
        FROM
            domain_aliasses AS t1,
            domain AS t2
        WHERE
            t1.domain_id = t2.domain_id
          AND
            t2.domain_created_id = ?
        ORDER BY
            t1.alias_name ASC
        LIMIT
            $start_index, $rows_per_page
SQL_QUERY;

		// count query
		$count_query = <<<SQL_QUERY
        SELECT
            COUNT(alias_id) AS cnt
        FROM
            domain_aliasses AS t1,
            domain AS t2
        WHERE
            t1.domain_id = t2.domain_id
          AND
            t2.domain_created_id = ?
SQL_QUERY;

	}

	// lets count
	$rs = exec_query($sql, $count_query, array($reseller_id));
	$records_count = $rs -> fields['cnt'];

	// Get all alias records
	$rs = exec_query($sql, $query, array($reseller_id));

    if ($records_count == 0) {

		$tpl -> assign(
            array(
                'TABLE_LIST' => '',
				'USERS_LIST' => '',
				'SCROLL_PREV' => '',
				'SCROLL_NEXT' => '',
                )
            );


        if (isset($_SESSION['search_for'])) {
			 $als_err = tr('Not found user records matching the search criteria!');
		} else {
			 $als_err = tr('You have no alias records.');
		}
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
	}


	$i = 1;

	 while (!$rs -> EOF) {



				$als_id = $rs -> fields['alias_id'];
				$domain_id = $rs -> fields['domain_id'];
                $als_name = $rs -> fields['alias_name'];
                $als_mount_point = $rs -> fields['alias_mount'];
                $als_status = $rs -> fields['alias_status'];
                $als_ip_id = $rs -> fields['alias_ip_id'];
                $als_fwd = $rs -> fields['url_forward'];
                $als_fwd = ($als_fwd == 'no') ? tr('disabled') : tr('enabled');

				$domain_name = decode_idna($rs -> fields['domain_name']);

				if ($als_mount_point == '') $als_mount_point = "/";

                $query = "select ip_number, ip_domain from server_ips where ip_id = ?";

                $alsip_r = exec_query($sql, $query, array($als_ip_id));
                $alsip_d = $alsip_r -> FetchRow();

                $als_ip = $alsip_d['ip_number'];
                $als_ip_name = $alsip_d['ip_domain'];

				if ($i % 2 == 0) {
					$page_cont = 'content';

				}else {
					$page_cont = 'content2';

				}

				if ($als_status === 'ok'){
					$delete_link = "delete_domainalias.php?del_id=".$als_id;
					$edit_link = "edit_alias.php?edit_id=".$als_id;
					$action_text = tr('delete');
				} else {
					$delete_link = "#";
					$edit_link = "#";
					$action_text = tr('N/A');
				}
				$als_status = translate_dmn_status($als_status);
				$als_name = decode_idna($als_name);

				if (isset($_SESSION['search_common']) && $_SESSION['search_common'] === 'account_name') {
					$domain_name_selected = "";
					$account_name_selected = "selected";
				} else {
					$domain_name_selected = "selected";
					$account_name_selected = "";
				}


                $tpl -> assign(
                                array(
                                        'NAME' => $als_name,
                                        'ALIAS_IP' => "$als_ip ($als_ip_name)",
                                        'REAL_DOMAIN' => $domain_name,
                                        'REAL_DOMAIN_MOUNT' => $als_mount_point,
                                        'FORWARD' => $als_fwd,
                                        'STATUS' => $als_status,
                                        'ID' => $als_id,
										'DELETE' => $action_text,
										'CONTENT' => $page_cont,
										'DELETE_LINK' => $delete_link,
										'EDIT_LINK' => $edit_link,
										'M_DOMAIN_NAME_SELECTED' => $domain_name_selected,
										'M_ACCOUN_NAME_SELECTED' => $account_name_selected,
                                     )
                              );


			$i++;
            $tpl -> parse('TABLE_ITEM', '.table_item');
			$rs -> MoveNext();

	 }

}// End of generate_als_list()



function generate_als_messages(&$tpl, $als_err) {

    if ($als_err != '_off_') {

        $tpl -> assign(
					array('MESSAGE'=> $als_err)
				);
		$tpl -> parse('PAGE_MESSAGE', 'page_message');
        return;

    }else 	if (isset($_SESSION["dahavemail"])) {

        $tpl -> assign('MESSAGE', tr('Domain alias you are trying to remove has email accounts !<br>First remove them!'));
        unset($_SESSION['dahavemail']);

    } else if (isset($_SESSION["dahaveftp"])) {

        $tpl -> assign('MESSAGE', tr('Domain alias you are trying to remove has FTP accounts!<br>First remove them!'));
		unset($_SESSION['dahavemail']);

    } else if (isset($_SESSION["aldel"])) {

		if('_yes_' === $_SESSION['aldel'])
        	$tpl -> assign('MESSAGE', tr('Domain alias added for termination!'));
		else
			$tpl -> assign('MESSAGE', tr('Domain alias not added for termination!'));

        unset($_SESSION['aldel']);

    }else if (isset($_SESSION['aladd'])) {

		if('_yes_' === $_SESSION['aladd'])
        	$tpl -> assign('MESSAGE', tr('Domain alias added!'));
		else
			$tpl -> assign('MESSAGE', tr('Domain alias not added!'));

        unset($_SESSION['aladd']);

    } else if(isset($_SESSION['aledit'])){
		if('_yes_' === $_SESSION['aledit'])
        	$tpl -> assign('MESSAGE', tr('Domain alias modified!'));
		else
			$tpl -> assign('MESSAGE', tr('Domain alias not modified!'));

        unset($_SESSION['aledit']);
	} else {

        $tpl -> assign('MESSAGE', '');
		$tpl -> assign('PAGE_MESSAGE', "");
    }

}// End of generate_als_messages()
?>
