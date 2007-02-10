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


function gen_admin_mainmenu(&$tpl, $menu_file) {
		global $sql, $cfg;

		$tpl -> define_dynamic('menu', $menu_file);
		$tpl -> define_dynamic('custom_buttons', 'menu');
		$tpl -> assign(
            array(
		'TR_MENU_GENERAL_INFORMATION' => tr('General information'),
		'TR_MENU_HOSTING_PLANS' => tr('Manage hosting plans'),
		'TR_MENU_SYSTEM_TOOLS' => tr('System tools'),
    		'TR_MENU_MANAGE_USERS' => tr('Manage users'),
		'TR_MENU_STATISTICS' => tr('Statistics'),
		'SUPPORT_SYSTEM_PATH' => $cfg['VHCS_SUPPORT_SYSTEM_PATH'],
                'SUPPORT_SYSTEM_TARGET' => $cfg['VHCS_SUPPORT_SYSTEM_TARGET'],
		'TR_MENU_SUPPORT_SYSTEM' => tr('Support system'),
		'TR_MENU_SETTINGS' => tr('Settings'),
		'TR_MENU_GENERAL_INFORMATION' => tr('General information'),
		'TR_MENU_HOSTING_PLANS' => tr('Manage hosting plans'),
		'TR_MENU_SYSTEM_TOOLS' => tr('System tools'),
    		'TR_MENU_MANAGE_USERS' => tr('Manage users'),
		'TR_MENU_STATISTICS' => tr('Statistics'),
		'SUPPORT_SYSTEM_PATH' => $cfg['VHCS_SUPPORT_SYSTEM_PATH'],
                'SUPPORT_SYSTEM_TARGET' => $cfg['VHCS_SUPPORT_SYSTEM_TARGET'],
		'TR_MENU_SUPPORT_SYSTEM' => tr('Support system'),
		'TR_MENU_SETTINGS' => tr('Settings'),
                'TR_MENU_CHANGE_PASSWORD' => tr('Change password'),
                'TR_MENU_CHANGE_PERSONAL_DATA' => tr('Change pers. data'),
		'TR_MENU_ADD_ADMIN'  => tr('Add admin'),
                'TR_MENU_ADD_RESELLER'  => tr('Add reseller'),
                'TR_MENU_RESELLER_ASIGNMENT'  => tr('Reseller assignment'),
                'TR_MENU_USER_ASIGNMENT'  => tr('User assignment'),
                'TR_MENU_EMAIL_SETUP'  => tr('Email setup'),
                'TR_MENU_CIRCULAR'  => tr('Email marketing'),
		'TR_MENU_ADD_HOSTING' => tr('Add hosting plan'),
                'TR_MENU_RESELLER_STATISTICS' => tr('Reseller statistics'),
                'TR_MENU_SERVER_STATISTICS' => tr('Server statistics'),
                'TR_MENU_ADMIN_LOG' => tr('Admin log'),
                'TR_MENU_MANAGE_IPS' => tr('Manage IPs'),                
                'TR_MENU_SYSTEM_INFO' => tr('System info'),
                'TR_MENU_I18N' => tr('Multilanguage'),
                'TR_MENU_LAYOUT_TEMPLATES' => tr('Layout'),
                'TR_MENU_LOGOUT' => tr('Logout'),
                'TR_MENU_QUESTIONS_AND_COMMENTS' => tr('Support system'),              
                'TR_MENU_SERVER_TRAFFIC_SETTINGS'=> tr('Server traffic settings'),
                'TR_MENU_SERVER_STATUS'=> tr('Server status'),
                'TR_MENU_VHCS_UPDATE'=> tr('VHCS updates'),
                'TR_MENU_VHCS_DEBUGGER'=> tr('VHCS debugger'),
                'TR_CUSTOM_MENUS' => tr('Custom menus'),
                'TR_MENU_OVERVIEW' => tr('Overview'),
                'TR_MENU_MANAGE_SESSIONS' => tr('User sessions'),
		'TR_MENU_LOSTPW_EMAIL' => tr('Lostpw email setup'),
		'TR_SERVICEMODE' => tr('Servicemode'),				
                'TR_GENERAL_SETTINGS' => tr('General settings'),
                'TR_SERVERPORTS' => tr('Serverports'))
		));
	$query = <<<SQL_QUERY
        select
            *
        from
            custom_menus
        where
            menu_level = 'admin'
SQL_QUERY;

    $rs = exec_query($sql, $query, array());
	 if ($rs -> RecordCount() == 0) {

        $tpl -> assign('CUSTOM_BUTTONS', '');

    } else {

		global $i;
		$i = 100;

		while (!$rs -> EOF) {

		$menu_name = $rs -> fields['menu_name'];
		$menu_link = get_menu_vars($rs -> fields['menu_link']);
		$menu_target = $rs -> fields['menu_target'];

		if ($menu_target === ''){
			$menu_target = "";
		} else {
			$menu_target = "target=\"".$menu_target."\"";
		}

		$tpl -> assign(
                  array(
                        'BUTTON_LINK' => $menu_link,
                        'BUTTON_NAME' => $menu_name,
                        'BUTTON_TARGET' => $menu_target,
                        'BUTTON_ID' => $i,
                        )
                  );

    $tpl -> parse('CUSTOM_BUTTONS', '.custom_buttons');
    $rs -> MoveNext(); $i++;

		} // end while
	} // end else

	if ($cfg['VHCS_SUPPORT_SYSTEM'] != 1) {

		$tpl -> assign('SUPPORT_SYSTEM', '');

	}

	if ($cfg['HOSTING_PLANS_LEVEL'] != strtolower('admin')) {

		$tpl -> assign('HOSTING_PLANS', '');

	}

	$tpl -> parse('MAIN_MENU', 'menu');

}

function gen_admin_menu(&$tpl, $menu_file) {

global $sql, $cfg;

$tpl -> define_dynamic('menu', $menu_file);

$tpl -> define_dynamic('custom_buttons', 'menu');

$tpl -> assign(
            array(
                'TR_MENU_GENERAL_INFORMATION' => tr('General information'),
                'TR_MENU_CHANGE_PASSWORD' => tr('Change password'),
                'TR_MENU_CHANGE_PERSONAL_DATA' => tr('Change pers. data'),
                'TR_MENU_MANAGE_USERS' => tr('Manage users'),
                'TR_MENU_ADD_ADMIN'  => tr('Add admin'),
                'TR_MENU_ADD_RESELLER'  => tr('Add reseller'),
                'TR_MENU_RESELLER_ASIGNMENT'  => tr('Reseller assignment'),
                'TR_MENU_USER_ASIGNMENT'  => tr('User assignment'),
                'TR_MENU_EMAIL_SETUP'  => tr('Email setup'),
                'TR_MENU_CIRCULAR'  => tr('Email marketing'),
		'TR_MENU_HOSTING_PLANS' => tr('Manage hosting plans'),
		'TR_MENU_ADD_HOSTING' => tr('Add hosting plan'),
		'TR_MENU_ROOTKIT_LOG' => tr('Rootkit Log'),
                'TR_MENU_RESELLER_STATISTICS' => tr('Reseller statistics'),
                'TR_MENU_SERVER_STATISTICS' => tr('Server statistics'),
                'TR_MENU_ADMIN_LOG' => tr('Admin log'),
                'TR_MENU_MANAGE_IPS' => tr('Manage IPs'),
                'TR_MENU_SUPPORT_SYSTEM' => tr('Support system'),
                'TR_MENU_SYSTEM_INFO' => tr('System info'),
                'TR_MENU_I18N' => tr('Multilanguage'),
                'TR_MENU_LAYOUT_TEMPLATES' => tr('Layout'),
                'TR_MENU_LOGOUT' => tr('Logout'),
                'TR_MENU_QUESTIONS_AND_COMMENTS' => tr('Support system'),
                'TR_MENU_STATISTICS' => tr('Statistics'),
                'TR_MENU_SYSTEM_TOOLS' => tr('System tools'),
                'TR_MENU_SERVER_TRAFFIC_SETTINGS'=> tr('Server traffic settings'),
                'TR_MENU_SERVER_STATUS'=> tr('Server status'),
                'TR_MENU_VHCS_UPDATE'=> tr('VHCS updates'),
                'TR_MENU_VHCS_DEBUGGER'=> tr('VHCS debugger'),
                'TR_CUSTOM_MENUS' => tr('Custom menus'),
                'TR_MENU_OVERVIEW' => tr('Overview'),
                'TR_MENU_MANAGE_SESSIONS' => tr('User sessions'),
                'SUPPORT_SYSTEM_PATH' => $cfg['VHCS_SUPPORT_SYSTEM_PATH'],
                'SUPPORT_SYSTEM_TARGET' => $cfg['VHCS_SUPPORT_SYSTEM_TARGET'],
		'TR_MENU_LOSTPW_EMAIL' => tr('Lostpw email setup'),
		'TR_SERVICEMODE' => tr('Servicemode'),
		'TR_MENU_SETTINGS' => tr('Settings'),
                'TR_GENERAL_SETTINGS' => tr('General settings'),
                'TR_SERVERPORTS' => tr('Serverports')
            )
    );
$query = <<<SQL_QUERY
        select
            *
        from
            custom_menus
        where
            menu_level = 'admin1' 
SQL_QUERY;

    $rs = exec_query($sql, $query, array());
	 if ($rs -> RecordCount() == 0) {

        $tpl -> assign('CUSTOM_BUTTONS', '');

    } else {

		global $i;
		$i = 100;

		while (!$rs -> EOF) {

		$menu_name = $rs -> fields['menu_name'];
		$menu_link = get_menu_vars($rs -> fields['menu_link']);
		$menu_target = $rs -> fields['menu_target'];

		if ($menu_target === ''){
			$menu_target = "";
		} else {
			$menu_target = "target=\"".$menu_target."\"";
		}

		$tpl -> assign(
                  array(
                        'BUTTON_LINK' => $menu_link,
                        'BUTTON_NAME' => $menu_name,
                        'BUTTON_TARGET' => $menu_target,
                        'BUTTON_ID' => $i,
                        )
                  );

    $tpl -> parse('CUSTOM_BUTTONS', '.custom_buttons');
    $rs -> MoveNext(); $i++;

		} // end while
	} // end else

	if ($cfg['VHCS_SUPPORT_SYSTEM'] != 1) {

		$tpl -> assign('SUPPORT_SYSTEM', '');

	}

	if ($cfg['HOSTING_PLANS_LEVEL'] != strtolower('admin')) {

		$tpl -> assign('HOSTING_PLANS', '');

	}

	$tpl -> parse('MENU', 'menu');

}

function get_cnt_of_user(&$sql, $user_type) {

    $query = <<<SQL_QUERY
        SELECT
            count(admin_id) as cnt
        FROM
            admin
        WHERE
            admin_type=?
SQL_QUERY;

    $rs = exec_query($sql, $query, array($user_type));

    $admin_cnt = $rs -> fields['cnt'];

    return $admin_cnt;

}


function get_cnt(&$sql, $table, $field, $where, $value)
{
  if ($where != '') {
    $query = <<<SQL_QUERY
            SELECT
                count(?) as  cnt
            FROM
                $table
            WHERE
                $where = ?
SQL_QUERY;
    $rs = exec_query($sql, $query, array($field, $value));

  } else {

    $query = <<<SQL_QUERY
            SELECT
                count(?) as  cnt
            FROM
                $table
SQL_QUERY;
    $rs = exec_query($sql, $query, array($field));
  }

  $cnt = $rs -> fields['cnt'];
  return $cnt;
}

function get_sql_user_count($sql) {
	$query = <<<SQL_QUERY
		SELECT DISTINCT
			sqlu_name
		FROM
			sql_user
SQL_QUERY;

	$rs = exec_query($sql, $query, FALSE);

	return $rs -> RecordCount();
}

function get_admin_general_info(&$tpl, &$sql)
{

    $tpl -> assign(
        array(
            'TR_GENERAL_INFORMATION' => tr('General information'),
            'TR_ACCOUNT_NAME' => tr('Account name'),
            'TR_ADMIN_USERS' => tr('Admin users'),
            'TR_RESELLER_USERS' => tr('Reseller users'),
            'TR_NORMAL_USERS' => tr('Normal users'),
            'TR_DOMAINS' => tr('Domains'),
            'TR_SUBDOMAINS' => tr('Subdomains'),
            'TR_DOMAINS_ALIASES' => tr('Domain aliases'),
            'TR_MAIL_ACCOUNTS' => tr('Mail accounts'),
            'TR_FTP_ACCOUNTS' => tr('FTP accounts'),
            'TR_SQL_DATABASES' => tr('SQL databases'),
            'TR_SQL_USERS' => tr('SQL users'),

            'TR_SYSTEM_MESSAGES' => tr('System messages'),
            'TR_NO_NEW_MESSAGES' => tr('No new messages'),
            'TR_SERVER_TRAFFIC' => tr('Server traffic'),
            )
        );

    $tpl -> assign(
        array(
            'ACCOUNT_NAME' => $_SESSION['user_logged'],
            'ADMIN_USERS' => get_cnt($sql, 'admin', 'admin_id', 'admin_type', 'admin'),
            'RESELLER_USERS' => get_cnt($sql, 'admin', 'admin_id', 'admin_type', 'reseller'),
            'NORMAL_USERS' => get_cnt($sql, 'admin', 'admin_id', 'admin_type', 'user'),
            'DOMAINS' => get_cnt($sql, 'domain', 'domain_id','', ''),
            'SUBDOMAINS' => get_cnt($sql, 'subdomain', 'subdomain_id','', ''),
            'DOMAINS_ALIASES' => get_cnt($sql, 'domain_aliasses', 'alias_id', '', ''),
            'MAIL_ACCOUNTS'  => get_cnt($sql, 'mail_users','mail_id', '', ''),
            'FTP_ACCOUNTS'  => get_cnt($sql, 'ftp_users', 'userid', '', ''),
            'SQL_DATABASES'  => get_cnt($sql, 'sql_database', 'sqld_id','', ''),
            'SQL_USERS'  => get_sql_user_count($sql)
            )
        );
}


function gen_admin_list(&$tpl, &$sql)
{

    $query = <<<SQL_QUERY
            SELECT
                t1.admin_id, t1.admin_name, IFNULL(t2.admin_name, '') AS created_by
            FROM
                admin AS t1
              LEFT JOIN
                admin AS t2 ON t1.created_by = t2.admin_id
            WHERE
                t1.admin_type='admin'
            ORDER BY
                t1.admin_name
			ASC
SQL_QUERY;

    $rs = exec_query($sql, $query, array());

    $i = 0;

    if ($rs -> RecordCount() == 0) {

	$tpl -> assign(
                        array(
                                'ADMIN_MESSAGE' => tr('Adminitrators list is empty!'),
                                'ADMIN_LIST' => ''
                             )
                      );

        $tpl -> parse('ADMIN_MESSAGE', 'admin_message');

    } else {

	$tpl -> assign(
                array(
                        'TR_ADMIN_USERNAME' => tr('Username'),
                        'TR_ADMIN_CREATED_BY' => tr('Created by'),
                        'TR_ADMIN_OPTIONS' => tr('Options'),
                     )
             );
     while (!$rs -> EOF) {

        if ($i % 2 == 0) {
            $tpl -> assign(
                    array(
                        'ADMIN_CLASS' => 'content',
                        )
                    );
        }
        else{
            $tpl -> assign(
                    array(
                        'ADMIN_CLASS' => 'content2',
                        )
                    );
        }

        if( $rs -> fields['created_by'] == '' ||
            $rs -> fields['admin_id'] == $_SESSION['user_id'])
        {
            $tpl -> assign(
                            array(
                                    'TR_DELETE' => tr('Delete'),
                                    'ADMIN_DELETE_LINK' =>'',
                                 )
                          );
            $tpl -> parse('ADMIN_DELETE_SHOW', 'admin_delete_show');
        }
        else
        {
            $tpl -> assign(
                array(
                    'ADMIN_DELETE_SHOW' =>'',
                    'TR_DELETE' => tr('Delete'),
                    'URL_DELETE_ADMIN' => "delete_user.php?delete_id=".$rs -> fields['admin_id']."&delete_username=".$rs -> fields['admin_name'],
                    )
            );
            $tpl -> parse('ADMIN_DELETE_LINK', 'admin_delete_link');
        }




        $tpl -> assign(
            array(
                'ADMIN_USERNAME' => $rs -> fields['admin_name'],
                'ADMIN_CREATED_BY' => $rs -> fields['created_by'],
                'URL_EDIT_ADMIN' => "edit_user.php?edit_id=".$rs -> fields['admin_id'],
                )
        );

        $tpl -> parse('ADMIN_ITEM', '.admin_item');

        $rs -> MoveNext();

	    $i++;

        }

        $tpl -> parse('ADMIN_LIST', 'admin_list');

        $tpl -> assign('ADMIN_MESSAGE', '');

    }

}

function gen_reseller_list(&$tpl, &$sql)
{

    $query = <<<SQL_QUERY
              SELECT
                  t1.admin_id, t1.admin_name, t1.domain_created, IFNULL(t2.admin_name, '') AS created_by
              FROM
                  admin as t1
                LEFT JOIN
                  admin AS t2 ON t1.created_by = t2.admin_id
              WHERE
                  t1.admin_type='reseller'
              ORDER BY
                  t1.admin_name
			  ASC
SQL_QUERY;

    $rs = exec_query($sql, $query, array());

    $i = 0;

    if ($rs -> RecordCount() == 0) {

	$tpl -> assign(
                        array(
                                'RSL_MESSAGE' => tr('Resellers list is empty!'),
                                'RSL_LIST' => ''
                             )
                      );

        $tpl -> parse('RSL_MESSAGE', 'rsl_message');

    } else {

	$tpl -> assign(
                array(
                        'TR_RSL_USERNAME' => tr('Username'),
                        'TR_RSL_CREATED_BY' => tr('Created by'),
                        'TR_RSL_OPTIONS' => tr('Options'),
                     )
             );
     while (!$rs -> EOF) {

        if ($i % 2 == 0) {
            $tpl -> assign(
                array(
                    'RSL_CLASS' => 'content',
                    )
                );
        }
        else{
            $tpl -> assign(
                array(
                    'RSL_CLASS' => 'content2',
                    )
                );
        }

        if( $rs -> fields['created_by'] == '')
        {
            $tpl -> assign(
                array(
                        'TR_DELETE' => tr('Delete'),
                        'RSL_DELETE_LINK' =>'',
                        )
                );
	    $tpl -> parse('RSL_DELETE_SHOW', 'rsl_delete_show');
	}
	else
	{
        $tpl -> assign(
            array(
                'RSL_DELETE_SHOW' =>'',
                'TR_DELETE' => tr('Delete'),
                'URL_DELETE_RSL' => "delete_user.php?delete_id=".$rs -> fields['admin_id']."&delete_username=".$rs -> fields['admin_name'],
                'TR_CHANGE_USER_INTERFACE' => tr('Change user interface'),
                'GO_TO_USER_INTERFACE' => tr('Change'),
                'URL_CHANGE_INTERFACE' => "change_user_interface.php?to_id=".$rs -> fields['admin_id'],
                )
        );
	    $tpl -> parse('RSL_DELETE_LINK', 'rsl_delete_link');
	}



	$reseller_created = $rs -> fields['domain_created'];

		if ($reseller_created == 0) {
			$reseller_created = tr('N/A');
		} else {
			global $cfg;
			$date_formt = $cfg['DATE_FORMAT'];
			$reseller_created = date($date_formt, $reseller_created);
		}

    $tpl -> assign(
        array(
            'RSL_USERNAME' => $rs -> fields['admin_name'],
            'RESELLER_CREATED_ON' => $reseller_created,
            'RSL_CREATED_BY' => $rs -> fields['created_by'],
            'URL_EDIT_RSL' => "edit_reseller.php?edit_id=".$rs -> fields['admin_id'],
            )
    );

    $tpl -> parse('RSL_ITEM', '.rsl_item');

    $rs -> MoveNext();

    $i++;

    }

    $tpl -> parse('RSL_LIST', 'rsl_list');

    $tpl -> assign('RSL_MESSAGE', '');

    }

}

function gen_user_list(&$tpl, &$sql)
{

	global $cfg;

	$start_index = 0;

	$rows_per_page = $cfg['DOMAIN_ROWS_PER_PAGE'];

	if (isset($_GET['psi'])) $start_index = $_GET['psi'];

	//
	//  Search requet generated ?!
	//

	if (isset($_POST['uaction']) && !empty($_POST['uaction'])) {

			$_SESSION['search_for'] = trim(clean_input($_POST['search_for']));

			$_SESSION['search_common'] = $_POST['search_common'];

			$_SESSION['search_status'] = $_POST['search_status'];

			$start_index = 0;


	} else {

		if (isset($_SESSION['search_for']) && !isset($_GET['psi'])) {

			//
			// He have not got scroll through patient records.
			//

			unset($_SESSION['search_for']);

			unset($_SESSION['search_common']);

			unset($_SESSION['search_status']);

		}

	}

  $search_query = ''; $count_query = '';
  if (isset($_SESSION['search_for'])) {
    gen_admin_domain_query($search_query,
                           $count_query,
                           $start_index,
                           $rows_per_page,
                           $_SESSION['search_for'],
                           $_SESSION['search_common'],
                           $_SESSION['search_status']);

    gen_admin_domain_search_options($tpl, $_SESSION['search_for'], $_SESSION['search_common'], $_SESSION['search_status']);
    $rs = exec_query($sql, $count_query, array());

  } else {
    gen_admin_domain_query($search_query,
                           $count_query,
                           $start_index,
                           $rows_per_page,
                           'n/a',
                           'n/a',
                           'n/a');
    gen_admin_domain_search_options($tpl, 'n/a', 'n/a', 'n/a');
    $rs = exec_query($sql, $count_query, array());
  }

  $records_count = $rs -> fields['cnt'];
  // print "records count: ".$records_count."<br>";

  $rs = execute_query($sql, $search_query);

  $i = 0;
  if ($rs -> RecordCount() == 0) {
    if (isset($_SESSION['search_for'])) {
			$tpl -> assign(
							array(
									'USR_MESSAGE' => tr('Not found user records matching the search criteria!'),
									'USR_LIST' => '',
									'SCROLL_PREV' => '',
									'SCROLL_NEXT' => '',
									'TR_VIEW_DETAILS' => tr('view aliases'),
									'SHOW_DETAILS' => "show",
								 )
						  );

			unset($_SESSION['search_for']);

			unset($_SESSION['search_common']);

			unset($_SESSION['search_status']);

		} else {

			$tpl -> assign(
							array(
									'USR_MESSAGE' => tr('Users list is empty!'),
									'USR_LIST' => '',
									'SCROLL_PREV' => '',
									'SCROLL_NEXT' => '',
									'TR_VIEW_DETAILS' => tr('view aliases'),
									'SHOW_DETAILS' => "show",
								 )
						  );

		}

        $tpl -> parse('USR_MESSAGE', 'usr_message');

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
                        'TR_USR_USERNAME' => tr('Username'),
                        'TR_USR_CREATED_BY' => tr('Created by'),
                        'TR_USR_OPTIONS' => tr('Options'),
                        'TR_USER_STATUS' => tr('S'),
                        'TR_D' => tr('D'),
                        'TR_DETAILS' => tr('Details'),

                     )
             );
     while (!$rs -> EOF) {

        if ($i % 2 == 0) {
            $tpl -> assign(
                array(
                    'USR_CLASS' => 'content',
                    )
                );
        }
        else{
            $tpl -> assign(
                array(
                    'USR_CLASS' => 'content2',
                    )
                );
        }


				// user status icon

		$domain_created_id = $rs -> fields['domain_created_id'];


  $query = <<<SQL_QUERY
        SELECT
            admin_id,
            admin_name
        FROM
            admin
        WHERE
            admin_id=?
		ORDER BY
			admin_name
		ASC
SQL_QUERY;

	$rs2 = exec_query($sql, $query, array($domain_created_id));

        if( $rs2 -> fields['admin_name'] == '')
        {
            $tpl -> assign(
                array(
                    'TR_DELETE' => tr('Delete'),
                    'USR_DELETE_LINK' =>'',
                    )
                );
            $tpl -> parse('USR_DELETE_SHOW', 'usr_delete_show');
        }
        else
        {
            $tpl -> assign(
                array(
                    'USR_DELETE_SHOW' =>'',
                    'DOMAIN_ID' => $rs -> fields['domain_id'],
                    'TR_DELETE' => tr('Delete'),
                    'URL_DELETE_USR' => "delete_user.php?delete_id=".$rs -> fields['domain_admin_id']."&delete_username=".$rs -> fields['domain_name'],
                    'TR_CHANGE_USER_INTERFACE' => tr('Change user interface'),
                    'GO_TO_USER_INTERFACE' => tr('Change'),
                    'URL_CHANGE_INTERFACE' => "change_user_interface.php?to_id=".$rs -> fields['domain_admin_id'],
                    )
            );
            $tpl -> parse('USR_DELETE_LINK', 'usr_delete_link');
        }

		global $cfg;
		if ($rs -> fields['domain_status'] == $cfg['ITEM_OK_STATUS'])
		{
				$status_icon = "ok.gif";
				$status_url = "change_status.php?domain_id=".$rs -> fields['domain_id'];

		} else if ($rs -> fields['domain_status'] == $cfg['ITEM_DISABLED_STATUS']) {

				$status_icon = "disabled.gif";
				$status_url = "change_status.php?domain_id=".$rs -> fields['domain_id'];


		} else if ($rs -> fields['domain_status'] == $cfg['ITEM_ADD_STATUS'] ||
					$rs -> fields['domain_status'] == $cfg['ITEM_RESTORE_STATUS'] ||
					$rs -> fields['domain_status'] == $cfg['ITEM_CHANGE_STATUS'] ||
          			$rs -> fields['domain_status'] == $cfg['ITEM_TOENABLE_STATUS'] ||
				  	$rs -> fields['domain_status'] == $cfg['ITEM_TODISABLED_STATUS'] ||
					$rs -> fields['domain_status'] == $cfg['ITEM_DELETE_STATUS']){

				$status_icon = "reload.gif";
				$status_url = "#";


		}else {

				$status_icon = "error.gif";
				$status_url = "domain_details.php?domain_id=".$rs -> fields['domain_id'];

		}

			$tpl -> assign(
                array(
                    'STATUS_ICON' => $status_icon,
                    'URL_CHNAGE_STATUS' => $status_url,
                    )
                );


		// end of user status icon

		$admin_name = decode_idna($rs -> fields['domain_name']);

		$domain_created = $rs -> fields['domain_created'];

		if ($domain_created == 0) {
			$domain_created = tr('N/A');
		} else {
			global $cfg;
			$date_formt = $cfg['DATE_FORMAT'];
			$domain_created = date($date_formt, $domain_created);
		}

        $tpl -> assign(
            array(
                'USR_USERNAME' => $admin_name,
                'USER_CREATED_ON' => $domain_created,
                'USR_CREATED_BY' => $rs2 -> fields['admin_name'],
                'USR_OPTIONS' => '',
                'URL_EDIT_USR' => "edit_user.php?edit_id=".$rs -> fields['domain_admin_id'],
                'TR_MESSAGE_CHANGE_STATUS' => tr('Are you sure you want to change the status of domain account?'),
                'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete this account?'),
                )
        );

		gen_domain_details($tpl, $sql, $rs -> fields['domain_id']);

        $tpl -> parse('USR_ITEM', '.usr_item');

        $rs -> MoveNext();

        $i++;

        }

    $tpl -> parse('USR_LIST', 'usr_list');

    $tpl -> assign('USR_MESSAGE', '');

    }

}

function get_admin_manage_users(&$tpl, &$sql)
{

	$tpl -> assign(
        array(
            'TR_MANAGE_USERS' => tr('Manage users'),
            'TR_ADMINISTRATORS' => tr('Administrators'),
            'TR_RESELLERS' => tr('Resellers'),
            'TR_USERS' => tr('Users'),
            'TR_SEARCH' => tr('Search'),
            'TR_CREATED_ON' => tr('Creation date'),
			'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete'),
            )
    );

	gen_admin_list($tpl, $sql);

	gen_reseller_list($tpl, $sql);

	gen_user_list($tpl, $sql);
}

function generate_reseller_props ( $reseller_id ) {

    global $sql;

    $query = <<<SQL_QUERY
        SELECT
            *
        FROM
            reseller_props
        WHERE
            reseller_id = ?
SQL_QUERY;

    $rs = exec_query($sql, $query, array($reseller_id));

    if ($rs -> RowCount() == 0) {

        return array(0,0, 0,0, 0,0, 0,0, 0,0, 0,0, 0,0, 0,0, 0,0);

    }

    return array(
        $rs -> fields['current_dmn_cnt'],
        $rs -> fields['max_dmn_cnt'],

        $rs -> fields['current_sub_cnt'],
        $rs -> fields['max_sub_cnt'],

        $rs -> fields['current_als_cnt'],
        $rs -> fields['max_als_cnt'],

        $rs -> fields['current_mail_cnt'],
        $rs -> fields['max_mail_cnt'],

        $rs -> fields['current_ftp_cnt'],
        $rs -> fields['max_ftp_cnt'],

        $rs -> fields['current_sql_db_cnt'],
        $rs -> fields['max_sql_db_cnt'],

        $rs -> fields['current_sql_user_cnt'],
        $rs -> fields['max_sql_user_cnt'],

        $rs -> fields['current_traff_amnt'],
        $rs -> fields['max_traff_amnt'],

        $rs -> fields['current_disk_amnt'],
        $rs -> fields['max_disk_amnt']
    );

}

function generate_reseller_users_props ( $reseller_id ) {

    global $sql;

    $rdmn_current = 0; $rdmn_max = 0; $rdmn_uf = '_off_';
    $rsub_current = 0; $rsub_max = 0; $rsub_uf = '_off_';
    $rals_current = 0; $rals_max = 0; $rals_uf = '_off_';
    $rmail_current = 0; $rmail_max = 0; $rmail_uf = '_off_';
    $rftp_current = 0; $rftp_max = 0; $rftp_uf = '_off_';
    $rsql_db_current = 0; $rsql_db_max = 0; $rsql_db_uf = '_off_';
    $rsql_user_current = 0; $rsql_user_max = 0; $rsql_user_uf = '_off_';
    $rtraff_current = 0; $rtraff_max = 0; $rtraff_uf = '_off_';
    $rdisk_current = 0; $rdisk_max = 0; $rdisk_uf = '_off_';

    $fresult =
        array(
            $rdmn_current, $rdmn_max, $rdmn_uf,
            $rsub_current, $rsub_max, $rsub_uf,
            $rals_current, $rals_max, $rals_uf,
            $rmail_current, $rmail_max, $rmail_uf,
            $rftp_current, $rftp_max, $rftp_uf,
            $rsql_db_current, $rsql_db_max, $rsql_db_uf,
            $rsql_user_current, $rsql_user_max, $rsql_user_uf,
            $rtraff_current, $rtraff_max, $rtraff_uf,
            $rdisk_current, $rdisk_max, $rdisk_uf
        );

    $query = <<<SQL_QUERY
        SELECT
            admin_id
        FROM
            admin
        WHERE
            created_by = ?
SQL_QUERY;

    $rs = exec_query($sql, $query, array($reseller_id));

    if ($rs -> RowCount() == 0) {

        return $fresult;

    }

    while (!$rs -> EOF) {

        $admin_id = $rs -> fields['admin_id'];

        $query = <<<SQL_QUERY
            SELECT
                domain_id
            FROM
                domain
            WHERE
                domain_admin_id = ?
SQL_QUERY;

        $dres = exec_query($sql, $query, array($admin_id));

        $user_id = $dres -> fields['domain_id'];

        list (
               $sub_current, $sub_max,
               $als_current, $als_max,
               $mail_current, $mail_max,
               $ftp_current, $ftp_max,
               $sql_db_current, $sql_db_max,
               $sql_user_current, $sql_user_max,
               $traff_max, $disk_max
             ) = generate_user_props($user_id);

        list (
               $a,
               $b,
               $c,
               $d,
               $e,
               $f,
               $traff_current,
               $disk_current,
               $g,
               $h
             ) = generate_user_traffic($user_id);

        $rdmn_current += 1;

        if ($sub_max != -1) {

            if ($sub_max == 0) $rsub_uf = '_on_';

            $rsub_current += $sub_current;
            $rsub_max += $sub_max;

        }

        if ($als_max != -1) {

            if ($als_max == 0) $rals_uf = '_on_';

            $rals_current += $als_current;
            $rals_max += $als_max;

        }

        if ($mail_max == 0) $rmail_uf = '_on_';

        $rmail_current += $mail_current;
        $rmail_max += $mail_max;

        if ($ftp_max == 0) $rftp_uf = '_on_';

        $rftp_current += $ftp_current;
        $rftp_max += $ftp_max;

        if ($sql_db_max != -1) {

            if ($sql_db_max == 0) $rsql_db_uf = '_on_';

            $rsql_db_current += $sql_db_current;
            $rsql_db_max += $sql_db_max;

        }

        if ($sql_user_max != -1) {

            if ($sql_user_max == 0) $rsql_user_uf = '_on_';

            $rsql_user_current += $sql_user_current;
            $rsql_user_max += $sql_user_max;

        }

        if ($traff_max == 0) $rtraff_uf = '_on_';

        $rtraff_current += $traff_current;
        $rtraff_max += $traff_max;

        if ($disk_max == 0) $rdisk_uf = '_on_';

        $rdisk_current += $disk_current;
        $rdisk_max += $disk_max;

        $rs -> MoveNext();
    }

    $fresult =
        array(
            $rdmn_current, $rdmn_max, $rdmn_uf,
            $rsub_current, $rsub_max, $rsub_uf,
            $rals_current, $rals_max, $rals_uf,
            $rmail_current, $rmail_max, $rmail_uf,
            $rftp_current, $rftp_max, $rftp_uf,
            $rsql_db_current, $rsql_db_max, $rsql_db_uf,
            $rsql_user_current, $rsql_user_max, $rsql_user_uf,
            $rtraff_current, $rtraff_max, $rtraff_uf,
            $rdisk_current, $rdisk_max, $rdisk_uf
        );

    return $fresult;

}

function generate_user_props($user_id) {

    global $sql;

    $query = <<<SQL_QUERY
        SELECT
            *
        FROM
            domain
        WHERE
            domain_id = ?

SQL_QUERY;

    $rs = exec_query($sql, $query, array($user_id));

    if ($rs -> RowCount() == 0) {

        return array(0,0,0,0,0,0,0,0,0,0,0,0,0,0);

    }

    $sub_current = records_count('subdomain_id', 'subdomain', 'domain_id', $user_id);
    $sub_max = $rs ->fields['domain_subd_limit'];

    $als_current = records_count('alias_id', 'domain_aliasses', 'domain_id', $user_id);
    $als_max = $rs ->fields['domain_alias_limit'];

	// Sorry 4 the strange Hack, but it works - RatS
    $mail_current = records_count('mail_id', 'mail_users', 'mail_type NOT RLIKE \'_catchall\' AND domain_id', $user_id);
    $mail_max = $rs ->fields['domain_mailacc_limit'];

    $ftp_current = sub_records_rlike_count(
                                            'domain_name', 'domain', 'domain_id', $user_id,
                                            'userid', 'ftp_users', 'userid', '@', ''
                                          );

    $ftp_current += sub_records_rlike_count(
                                             'subdomain_name', 'subdomain', 'domain_id', $user_id,
                                             'userid', 'ftp_users', 'userid', '@', ''
                                           );

    $ftp_current += sub_records_rlike_count(
                                             'alias_name', 'domain_aliasses', 'domain_id', $user_id,
                                             'userid', 'ftp_users', 'userid', '@', ''
                                           );

    $ftp_max = $rs ->fields['domain_ftpacc_limit'];

    $sql_db_current = records_count('sqld_id', 'sql_database', 'domain_id', $user_id);
    $sql_db_max = $rs ->fields['domain_sqld_limit'];

    $sql_user_current = sub_records_count(
                                            'sqld_id', 'sql_database', 'domain_id', $user_id,
                                            'sqlu_id', 'sql_user', 'sqld_id', '', ''
                                         );

    $sql_user_max = $rs ->fields['domain_sqlu_limit'];

    $traff_max = $rs ->fields['domain_traffic_limit'];

    $disk_max = $rs ->fields['domain_disk_limit'];

    return array(
        $sub_current, $sub_max,
        $als_current, $als_max,
        $mail_current, $mail_max,
        $ftp_current, $ftp_max,
        $sql_db_current, $sql_db_max,
        $sql_user_current, $sql_user_max,
        $traff_max,
        $disk_max);

}

function records_count($field, $table, $where, $value) {

    global $sql;

    if ($where != '') {

        $query = <<<SQL_QUERY
            SELECT
                COUNT($field) AS cnt
            FROM
                $table
            WHERE
                $where = ?
SQL_QUERY;
        $rs = exec_query($sql, $query, array($value));
    } else {

        $query = <<<SQL_QUERY
            SELECT
                COUNT($field) AS cnt
            FROM
                $table
SQL_QUERY;
      $rs = exec_query($sql, $query, array());
    }

    return $rs->fields['cnt'];

}

function records_rlike_count ($field, $table, $where, $value, $a, $b) {

    global $sql;

    if ($where != '') {

        $query = <<<SQL_QUERY
            SELECT
                COUNT($field) AS cnt
            FROM
                $table
            WHERE
                $where RLIKE ?
SQL_QUERY;
      $rs = exec_query($sql, $query, array($a . $value . $b));
    } else {

        $query = <<<SQL_QUERY
            SELECT
                COUNT($field) AS cnt
            FROM
                $table
SQL_QUERY;
      $rs = exec_query($sql, $query, array());
    }

    return $rs->fields['cnt'];

}

function sub_records_count ($field, $table, $where, $value, $subfield, $subtable, $subwhere) {

    global $sql;

    if ($where != '') {

        $query = <<<SQL_QUERY
            SELECT
                $field AS field
            FROM
                $table
            WHERE
                $where = ?
SQL_QUERY;
      $rs = exec_query($sql, $query, array($value));
    } else {

        $query = <<<SQL_QUERY
            SELECT
                $field AS field
            FROM
                $table

SQL_QUERY;
      $rs = exec_query($sql, $query, array());
    }

    $result = 0;

    if ($rs -> RowCount() == 0) {

        return $result;

    }

    while (!$rs -> EOF) {

        $contents = $rs->fields['field'];

        if ($subwhere != '') {

            $query = <<<SQL_QUERY
                SELECT
                    COUNT($subfield) AS cnt
                FROM
                    $subtable
                WHERE
                    $subwhere = ?
SQL_QUERY;

        } else {
            return $result;
        }

        $subres = exec_query($sql, $query, array($contents));
        $result += $subres->fields['cnt'];
        $rs -> MoveNext();
    }

    return $result;

}

function generate_user_traffic ($user_id) {

    global $sql, $crnt_month, $crnt_year;

    $from_timestamp = mktime(0, 0, 0, $crnt_month, 1, $crnt_year);

    if ($crnt_month == 12) {

        $to_timestamp = mktime(0, 0, 0, 1, 1, $crnt_year + 1);

    } else {

        $to_timestamp = mktime(0, 0, 0, $crnt_month + 1, 1, $crnt_year);

    }

    $query = <<<SQL_QUERY
        SELECT
            domain_id,
            IFNULL(domain_disk_usage,0) AS domain_disk_usage,
            IFNULL(domain_traffic_limit,0) AS domain_traffic_limit,
            IFNULL(domain_disk_limit,0) AS domain_disk_limit,
            domain_name
        FROM
            domain
        WHERE
            domain_id = ?
        ORDER BY
            domain_name
SQL_QUERY;

    $rs = exec_query($sql, $query, array($user_id));

    if ($rs -> RowCount() == 0 || $rs -> RowCount() > 1) {

        write_log("TRAFFIC WARNING: ".$rs->fields['domain_name']." manages incorrect number of domains: ".$rs -> RowCount());

        return array('n/a', 0, 0, 0, 0, 0, 0, 0, 0, 0);

    } else {

        $domain_id = $rs->fields['domain_id'];

        $domain_disk_usage = $rs->fields['domain_disk_usage'];

        $domain_traff_limit = $rs->fields['domain_traffic_limit'];

        $domain_disk_limit = $rs->fields['domain_disk_limit'];

        $domain_name = $rs->fields['domain_name'];

        $query = <<<SQL_QUERY
            SELECT
                IFNULL(SUM(dtraff_web), 0) AS web,
                IFNULL(SUM(dtraff_ftp), 0) AS ftp,
                IFNULL(SUM(dtraff_mail), 0) AS smtp,
                IFNULL(SUM(dtraff_pop), 0) AS pop,
                IFNULL(SUM(dtraff_web), 0) +
                IFNULL(SUM(dtraff_ftp), 0) +
                IFNULL(SUM(dtraff_mail), 0) +
                IFNULL(SUM(dtraff_pop), 0) AS total
            FROM
                domain_traffic
            WHERE
                    domain_id = ?
                AND
                    dtraff_time >= ?
                AND
                    dtraff_time < ?
SQL_QUERY;

        $rs1 = exec_query($sql, $query, array($domain_id, $from_timestamp, $to_timestamp));

        return array(
                        $domain_name,
                        $domain_id,
                        $rs1 -> fields['web'],
                        $rs1 -> fields['ftp'],
                        $rs1 -> fields['smtp'],
                        $rs1 -> fields['pop'],
                        $rs1 -> fields['total'],
                        $domain_disk_usage,
                        $domain_traff_limit,
                        $domain_disk_limit
                    );

    }

}

function make_usage_vals ($current, $max) {

    if ($max == 0) {

        $max = 1024 * 1024 * 1024 * 1024; // 1 Tera Byte Limit ;) for Unlimited Value

    }

    $percent = 100 * $current / $max;

    $percent = sprintf("%.2f", $percent);

    $red = (int) $percent;

    if ($red > 100) {

        return array($percent, 100, 0);

    } else {

        return array($percent, $red, 100 - $red);

    }

}

function sub_records_rlike_count ($field, $table, $where, $value, $subfield, $subtable, $subwhere, $a, $b)
{
  global $sql;

  if ($where != '') {
    $query = <<<SQL_QUERY
      SELECT
          $field AS field
      FROM
          $table
      WHERE
          $where = ?
SQL_QUERY;

    $rs = exec_query($sql, $query, array($value));

  } else {
    $query = <<<SQL_QUERY
            SELECT
                $field AS field
            FROM
                $table
SQL_QUERY;

    $rs = exec_query($sql, $query, array());
  }

    $result = 0;

    if ($rs -> RowCount() == 0) {

        return $result;

    }

    while (!$rs -> EOF) {

        $contents = $rs -> fields['field'];

        if ($subwhere != '') {

            $query = <<<SQL_QUERY
                SELECT
                    COUNT($subfield) AS cnt
                FROM
                    $subtable
                WHERE
                    $subwhere RLIKE ?
SQL_QUERY;

        } else {

            return $result;

        }

        $subres = exec_query($sql, $query, array($a . $contents . $b));

        $result += $subres -> fields['cnt'];

        $rs -> MoveNext();

    }

    return $result;

}

function gen_select_lists (&$tpl, $user_month, $user_year) {

    global $crnt_month, $crnt_year;

    if (!$user_month == '' || !$user_year == '') {

        $crnt_month =  $user_month; $crnt_year = $user_year;

    } else {

        $crnt_month = date("m"); $crnt_year = date("Y");
    }


    for ($i = 1; $i <= 12; $i++) {

      $selected = ($i == $crnt_month) ? 'selected' : '';

      $tpl -> assign(
                      array(
                              'OPTION_SELECTED' => $selected,
                              'MONTH_VALUE' => $i
                           )
                    );

      $tpl -> parse('MONTH_LIST', '.month_list');

    }

    for ($i = $crnt_year - 1; $i <= $crnt_year + 1; $i++) {

        $selected = ($i == $crnt_year) ? 'selected' : '';

        $tpl -> assign(
                        array(
                                'OPTION_SELECTED' => $selected,
                                'YEAR_VALUE' => $i
                             )
                      );

        $tpl -> parse('YEAR_LIST', '.year_list');

    }

}

function get_user_name($user_id)
{

    global $sql;


    $query = <<<SQL_QUERY
        SELECT
            admin_name
        FROM
            admin
        WHERE
            admin_id = ?

SQL_QUERY;

    $rs = exec_query($sql, $query, array($user_id));

    return $rs->fields('admin_name');

}

function get_logo($user_id)
{
    global $sql;


    $query = <<<SQL_QUERY
        SELECT
            admin_id, created_by, admin_type
        FROM
            admin
        WHERE
            admin_id = ?

SQL_QUERY;

    $rs = exec_query($sql, $query, array($user_id));

    if($rs->fields['admin_type'] == 'admin')
    {

        return get_admin_logo($user_id);

    }
    else{

         return get_admin_logo($rs->fields['created_by']);

    }
}


function get_own_logo($user_id)
{
    return get_admin_logo($user_id);
}


function get_admin_logo($user_id)
{

   global $sql, $cfg;


    $query = <<<SQL_QUERY
        SELECT
            logo
        FROM
            user_gui_props
        WHERE
            user_id= ?

SQL_QUERY;

    $rs = exec_query($sql, $query, array($user_id));

	$user_logo = $rs -> fields['logo'];

    if($user_logo == '0' || $user_logo == '') { // default logo

       return "../themes/user_logos/isp_logo.gif";

    } else{ // we have logo uploaded

         return $cfg['IPS_LOGO_PATH']."/".$rs -> fields['logo'];


    }

}

function calc_bar_value($value, $value_max , $bar_width)
{


    if( $value_max== 0 )
        return 0;
    else

		$ret_value = ($value * $bar_width)/ $value_max;

        return ($ret_value > $bar_width)?$bar_width :$ret_value ;
}


function write_log($msg) {
/* log function */
    global $sql, $send_log_to, $cfg;

	if (isset($_SERVER['REMOTE_ADDR'])) {
		$client_ip = $_SERVER['REMOTE_ADDR'];
	} else {
		$client_ip = "unknown";
	}
	$msg2 = $msg."<br><small>User IP: ".$client_ip."</small>";

    $sql->Execute( "INSERT INTO log (log_time,log_message) VALUES(NOW(),'$msg2')" );


	$send_log_to = $cfg['DEFAULT_ADMIN_ADDRES'];

    /* now send email if DEFAULT_ADMIN_ADDRES != '' */
	if ($send_log_to != '') {

        global $cfg, $default_hostname, $default_base_server_ip, $Version, $VersionH, $BuildDate, $admin_login;

		$admin_email = $cfg['DEFAULT_ADMIN_ADDRES'];
        $default_hostname =  $cfg['SERVER_HOSTNAME'];
		$default_base_server_ip =  $cfg['BASE_SERVER_IP'];
		$VersionH = $cfg['VersionH'];
		$Version = $cfg['Version'];
		$BuildDate = $cfg['BuildDate'];

		$subject = "VHCS $Version on $default_hostname ($default_base_server_ip)";

        $to      = $send_log_to;

        $message = <<<AUTO_LOG_MSG

VHCS Pro Log

Server: $default_hostname ($default_base_server_ip)
Version: $VersionH ($Version - $BuildDate)

Message: ----------------[BEGIN]--------------------------

User IP: $client_ip
$msg

Message: ----------------[END]----------------------------

AUTO_LOG_MSG;

        $headers = "From: VHCS  Logging Daemon <$admin_email>\n";

		    $headers .= "MIME-Version: 1.0\nContent-Type: text/plain\nContent-Transfer-Encoding: 7bit\n";

				$headers .=	"X-Mailer: VHCS $Version Logging Mailer";

        $mail_result = mail($to, $subject, $message, $headers);

        $mail_status = ($mail_result) ? 'OK' : 'NOT OK';

        $log_message = "$admin_login: Logging Daemon Mail To: |$to|, From: |$admin_email|, Status: |$mail_status|!";

        $sql->Execute( "INSERT INTO log (log_time,log_message) VALUES(NOW(),'$log_message')" );

    }

}

function send_add_user_auto_msg($admin_id, $uname, $upass, $uemail, $ufname, $ulname, $utype) {

    global $cfg;

    $admin_login = $_SESSION['user_logged'];

		$data = get_welcome_email($admin_id);

		$from_name = $data['sender_name'];

		$from_email = $data['sender_email'];

    $subject = $data['subject'];

    $message = $data['message'];

    if ($from_name) {

        $from = $from_name . "<" . $from_email . ">";

    } else {

        $from = $from_email;
		}

    if ($ufname && $ulname) {

        $to = "$ufname $ulname <$uemail>";

        $name = "$ufname $ulname";

    } else {

        $name = $uname;

        $to = $uemail;

    }

    $username = $uname;

    $password = $upass;

    $subject = preg_replace("/\{USERNAME\}/", $username, $subject);
    $message = preg_replace("/\{USERTYPE\}/", $utype, $message);
    $message = preg_replace("/\{USERNAME\}/", $username, $message);
    $message = preg_replace("/\{NAME\}/", $name, $message);
    $message = preg_replace("/\{PASSWORD\}/", $password, $message);

    $headers = "From: $from\n";

    $headers .= "MIME-Version: 1.0\nContent-Type: text/plain\nContent-Transfer-Encoding: 7bit\n";

		$headers .=	"X-Mailer: VHCS ".$cfg['Version']." Service Mailer";

    $mail_result = mail($to, $subject, $message, $headers);

    $mail_status = ($mail_result) ? 'OK' : 'NOT OK';

    write_log("$admin_login: Auto Add User To: |$to|, From: |$from|, Status: |$mail_status|!");

}

function update_reseller_props ( $reseller_id, $props ) {

    global $sql;

    if ($props == '') {

        return;

    }

    list (
           $dmn_current, $dmn_max,
           $sub_current, $sub_max,
           $als_current, $als_max,
           $mail_current, $mail_max,
           $ftp_current, $ftp_max,
           $sql_db_current, $sql_db_max,
           $sql_user_current, $sql_user_max,
           $traff_current, $traff_max,
           $disk_current, $disk_max
         ) = explode (";", $props);

	$query = <<<SQL_QUERY
        UPDATE
            reseller_props
        SET
            current_dmn_cnt = ?,
            max_dmn_cnt = ?,
            current_sub_cnt = ?,
            max_sub_cnt = ?,
            current_als_cnt = ?,
            max_als_cnt = ?,
            current_mail_cnt = ?,
            max_mail_cnt = ?,
            current_ftp_cnt = ?,
            max_ftp_cnt = ?,
            current_sql_db_cnt = ?,
            max_sql_db_cnt = ?,
            current_sql_user_cnt = ?,
            max_sql_user_cnt = ?,
            current_traff_amnt = ?,
            max_traff_amnt = ?,
            current_disk_amnt = ?,
            max_disk_amnt = ?
        WHERE
            reseller_id = ?
SQL_QUERY;

    $res = exec_query($sql, $query, array($dmn_current,
                                          $dmn_max,
                                          $sub_current,
                                          $sub_max,
                                          $als_current,
                                          $als_max,
                                          $mail_current,
                                          $mail_max,
                                          $ftp_current,
                                          $ftp_max,
                                          $sql_db_current,
                                          $sql_db_max,
                                          $sql_user_current,
                                          $sql_user_max,
                                          $traff_current,
                                          $traff_max,
                                          $disk_current,
                                          $disk_max,
                                          $reseller_id));

}

function gen_logged_from(&$tpl)
{

	if (isset($_SESSION['logged_from']) && isset($_SESSION['logged_from_id'])) {

			$tpl -> assign(
                array(
                        'YOU_ARE_LOGGED_AS' => $_SESSION['logged_from'].", ".tr('you are logged now as')." ".$_SESSION['user_logged'],
						'TR_GO_BACK' => tr('Go back'),
                     )
              );

		$tpl -> parse('LOGGED_FROM', '.logged_from');

	} else {

		$tpl -> assign('LOGGED_FROM', '');
	}

}

function change_domain_status(&$sql, &$domain_id, &$domain_name, &$action, &$location)
{
	global $cfg;

	check_for_lock_file();


	if ($action == 'disable') {
		$new_status = $cfg['ITEM_TODISABLED_STATUS'];
	} else if ($action == 'enable') {
		$new_status = $cfg['ITEM_TOENABLE_STATUS'];
	} else {
		return;
	}

$query = <<<SQL_QUERY
      SELECT
          mail_id,
          mail_pass
      FROM
          mail_users
      WHERE
          domain_id = ?
        AND
          mail_pass != '_no_'
SQL_QUERY;

	$rs = exec_query($sql, $query, array($domain_id));




	while (!$rs -> EOF) {

			if ($action == 'disable') {

				$mail_id = $rs -> fields['mail_id'];
				$timestamp = time();
				$pass_prefix = substr(md5($timestamp),0,4);
				$mail_pass = $pass_prefix.$rs -> fields['mail_pass'];

			} else if ($action == 'enable') {

				$mail_id = $rs -> fields['mail_id'];
				$mail_pass = substr($rs -> fields['mail_pass'],4,50);

			} else {
				return;
			}



			$mail_status = $cfg['ITEM_CHANGE_STATUS'];
				// and lets update the pass
				$query = <<<SQL_QUERY
            UPDATE
                 mail_users
            SET
                mail_pass = ?,
                status = ?
            WHERE
                mail_id = ?
SQL_QUERY;

				$rs2 = exec_query($sql, $query, array($mail_pass, $mail_status, $mail_id));

		 $rs -> MoveNext();
	} // end of while => all mails account are with changed passwords :-)

  $query = <<<SQL_QUERY
          UPDATE
              domain
          SET
              domain_status = ?
          WHERE
              domain_id = ?
SQL_QUERY;

		$rs = exec_query($sql, $query, array($new_status, $domain_id));

    send_request();

	// lets get back to user overview after the system changes are finished

			$user_logged = $_SESSION['user_logged'];


			if ($action == 'disable') {

				write_log("$user_logged: suspended domain: $domain_name");

				$_SESSION['user_disabled'] = 1;

			} else if ($action == 'enable') {

				write_log("$user_logged: enabled domain: $domain_name");

				$_SESSION['user_enabled'] = 1;

			} else {
				return;
			}


    if ($location == 'admin') {
		header("Location: manage_users.php");
	} else if ($location == 'reseller') {
		header("Location: users.php");
	}

    die();

}

function gen_admin_domain_query (
                                    &$search_query,
                                    &$count_query,
                                    $start_index,
                                    $rows_per_page,
                                    $search_for,
                                    $search_common,
                                    $search_status
                                   )
								   {

	if ($search_for === 'n/a' && $search_common === 'n/a' && $search_status === 'n/a') {

		//
        // We have pure list query;
        //
		$count_query = <<<SQL_QUERY
                SELECT
                    COUNT(domain_id) AS cnt
                FROM
                    domain
SQL_QUERY;

            $search_query = <<<SQL_QUERY
                 SELECT
                    *
                 FROM
                    domain
                 ORDER BY
                    domain_name ASC
                 LIMIT
                    $start_index, $rows_per_page
SQL_QUERY;



	} else if ($search_for === '' && $search_status != '') {

		if ($search_status === 'all'){

			$add_query = <<<SQL_QUERY
SQL_QUERY;

		} else {

			$add_query = <<<SQL_QUERY
				WHERE
					domain_status = '$search_status'
SQL_QUERY;
		}

		$count_query = <<<SQL_QUERY
                SELECT
                    COUNT(domain_id) AS cnt
                FROM
                    domain
                   $add_query
SQL_QUERY;

            $search_query = <<<SQL_QUERY
                 SELECT
                    *
                 FROM
                    domain
                    $add_query
                 ORDER BY
                    domain_name ASC
                 LIMIT
                    $start_index, $rows_per_page
SQL_QUERY;

	} else if ($search_for != '') {

		if ($search_common === 'domain_name') {

            $add_query = <<<SQL_QUERY
                WHERE
					admin_name RLIKE '$search_for' %s
SQL_QUERY;
		} else if ($search_common === 'customer_id') {

            $add_query = <<<SQL_QUERY
                WHERE
					customer_id RLIKE '$search_for' %s
SQL_QUERY;
		} else if ($search_common === 'lname') {

            $add_query = <<<SQL_QUERY
                WHERE
					(lname RLIKE '$search_for' OR fname RLIKE '$search_for') %s
SQL_QUERY;
		} else if ($search_common === 'firm') {

            $add_query = <<<SQL_QUERY
                WHERE
					firm RLIKE '$search_for' %s
SQL_QUERY;
		} else if ($search_common === 'city') {

            $add_query = <<<SQL_QUERY
                WHERE
					city RLIKE '$search_for' %s
SQL_QUERY;
		} else if ($search_common === 'country') {

            $add_query = <<<SQL_QUERY
                WHERE
					country RLIKE '$search_for' %s
SQL_QUERY;
		}

		if ($search_status != 'all') {
			//$add_query = sprintf($add_query, " and t1.created_by = '$reseller_id' and t2.domain_status = '$search_status'");
			$add_query = sprintf($add_query, " and t2.domain_status = '$search_status'");

			$count_query = <<<SQL_QUERY
					SELECT
						COUNT(admin_id) AS cnt
					FROM
						admin AS t1,
						domain AS t2
					$add_query
						AND
                    t1.admin_id = t2.domain_admin_id
SQL_QUERY;

		} else {

			$add_query = sprintf($add_query, " ");

			$count_query = <<<SQL_QUERY
					SELECT
						COUNT(admin_id) AS cnt
					FROM
						admin
					$add_query
SQL_QUERY;

		}



	$search_query = <<<SQL_QUERY
			SELECT
                	t1.admin_id, t2.*
            FROM
                	admin as t1,
                 	domain as t2
                $add_query
				AND
                    t1.admin_id = t2.domain_admin_id
				ORDER BY
                    t2.domain_name ASC
                LIMIT
                    $start_index, $rows_per_page
SQL_QUERY;


	}

}

function gen_admin_domain_search_options  (&$tpl,
											$search_for,
											$search_common,
											$search_status)
{
	if ($search_for === 'n/a' && $search_common === 'n/a' && $search_status === 'n/a')
	{
		// we have no search and let's genarate search fields empty

		$domain_selected = "selected";
		$customerid_selected = "";
		$lastname_selected = "";
		$company_selected = "";
		$city_selected = "";
		$country_selected = "";

		$all_selected = "selected";
		$ok_selected = "";
		$suspended_selected = "";

	} if ($search_common === 'domain_name') {

		$domain_selected = "selected";
		$customerid_selected = "";
		$lastname_selected = "";
		$company_selected = "";
		$city_selected = "";
		$country_selected = "";

	} else if ($search_common === 'customer_id') {

		$domain_selected = "";
		$customerid_selected = "selected";
		$lastname_selected = "";
		$company_selected = "";
		$city_selected = "";
		$country_selected = "";
	} else if ($search_common === 'lname') {

		$domain_selected = "";
		$customerid_selected = "";
		$lastname_selected = "selected";
		$company_selected = "";
		$city_selected = "";
		$country_selected = "";

	} else if ($search_common === 'firm') {

		$domain_selected = "";
		$customerid_selected = "";
		$lastname_selected = "";
		$company_selected = "selected";
		$city_selected = "";
		$country_selected = "";

	} else if ($search_common === 'city') {

		$domain_selected = "";
		$customerid_selected = "";
		$lastname_selected = "";
		$company_selected = "";
		$city_selected = "selected";
		$country_selected = "";

	} else if ($search_common === 'country') {

		$domain_selected = "";
		$customerid_selected = "";
		$lastname_selected = "";
		$company_selected = "";
		$city_selected = "";
		$country_selected = "selected";

	} if ($search_status === 'all') {

		$all_selected = "selected";
		$ok_selected = "";
		$suspended_selected = "";

	} else if ($search_status === 'ok') {

		$all_selected = "";
		$ok_selected = "selected";
		$suspended_selected = "";

	} else if ($search_status === 'disabled') {

		$all_selected = "";
		$ok_selected = "";
		$suspended_selected = "selected";

	}

	if ($search_for === "n/a" || $search_for === '') {

			$tpl -> assign(
			                array(
									'SEARCH_FOR' => ""
								)
							);
	} else {
			$tpl -> assign(
			                array(
									'SEARCH_FOR' => stripslashes($search_for)
								)
							);
	}

	$tpl -> assign(
			                array(
									'M_DOMAIN_NAME' => tr('Domain name'),
									'M_CUSTOMER_ID' => tr('Customer ID'),
									'M_LAST_NAME' => tr('Last name'),
									'M_COMPANY' => tr('Company'),
									'M_CITY' => tr('City'),
									'M_COUNTRY' => tr('Country'),

									'M_ALL' => tr('All'),
									'M_OK' => tr('OK'),
									'M_SUSPENDED' => tr('Suspended'),
									'M_ERROR' => tr('Error'),

									// selected area

									'M_DOMAIN_NAME_SELECTED' => $domain_selected,
									'M_CUSTOMER_ID_SELECTED' => $customerid_selected,
									'M_LAST_NAME_SELECTED' => $lastname_selected,
									'M_COMPANY_SELECTED' => $company_selected,
									'M_CITY_SELECTED' => $city_selected,
									'M_COUNTRY_SELECTED' => $country_selected,

									'M_ALL_SELECTED' => $all_selected,
									'M_OK_SELECTED' => $ok_selected,
									'M_SUSPENDED_SELECTED' => $suspended_selected,


								  )
							  );
}
function rm_rf_user_account($id_user)
{
	global $sql, $cfg;

// get domain user data
	$query = <<<SQL_QUERY
        SELECT
            domain_id,
            domain_name,
            domain_gid,
            domain_created_id
        FROM
            domain
        WHERE
            domain_admin_id = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($id_user));

	$domain_id = $rs -> fields['domain_id'];
	$domain_name = $rs -> fields['domain_name'];
	$domain_gid = $rs -> fields['domain_gid'];
	$domain_created_id = $rs -> fields['domain_created_id'];
	// we have all needed user data - let's delete all data for this user

	substract_from_reseller_props($domain_created_id, $domain_id);

// BEGIN - DELETE ALL SYSTEM ENTRIES FOR THIS USER

	//fist we'll delete all FTP Accounts
	//delete all FTP Accounts
  $query = <<<SQL_QUERY
          DELETE FROM
              ftp_users
          WHERE
              gid = ?
SQL_QUERY;
  $rs = exec_query($sql, $query, array($domain_gid));

	while (!$rs -> EOF) {
		$rs -> MoveNext();
	}


	 // delete the group
	 $query = <<<SQL_QUERY
    	    DELETE FROM
        	    ftp_group
        	WHERE
            	gid = ?
SQL_QUERY;
	$rs = exec_query($sql, $query, array($domain_gid));

	//let's delete all Subdomains for this user
	$delete_status = $cfg['ITEM_DELETE_STATUS'];

	    $query = <<<SQL_QUERY
    	    UPDATE
        	    subdomain
	        SET
    	        subdomain_status = ?
        	WHERE
            	domain_id = ?
SQL_QUERY;
	$rs = exec_query($sql, $query, array($delete_status, $domain_id));

	//let's delete all domain aliases for this user
  $query = <<<SQL_QUERY
        UPDATE
            domain_aliasses
        SET
            alias_status = ?
        WHERE
            domain_id = ?
SQL_QUERY;
	$rs = exec_query($sql, $query, array($delete_status, $domain_id));

	while (!$rs -> EOF) {
		$rs -> MoveNext();
	}


//let's delete all mail accounts for this user

	    $query = <<<SQL_QUERY
    	    UPDATE
        	    mail_users
	        SET
    	        status = ?
        	WHERE
            	domain_id = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($delete_status, $domain_id));

	// delete all htaccess entries for this user
	$query = <<<SQL_QUERY
    	    DELETE FROM
        	    htaccess
        	WHERE
            	dmn_id = ?
SQL_QUERY;
	$rs = exec_query($sql, $query, array($domain_id));

	$query = <<<SQL_QUERY
    	    DELETE FROM
        	    htaccess_groups
        	WHERE
            	dmn_id = ?
SQL_QUERY;
	$rs = exec_query($sql, $query, array($domain_id));

	$query = <<<SQL_QUERY
    	    DELETE FROM
        	     htaccess_users
        	WHERE
            	dmn_id = ?
SQL_QUERY;
	$rs = exec_query($sql, $query, array($domain_id));

	// end of delete htaccess entires


while (!$rs -> EOF) {
		$rs -> MoveNext();
	}



	// Lets Delete SQL DBs and Users
	$query = <<<SQL_QUERY
    	SELECT
			sqld_id
    	FROM
        	sql_database
    	WHERE
        	domain_id = ?
SQL_QUERY;
	$rs = exec_query($sql, $query, array($domain_id));
	while (!$rs -> EOF) {

		$db_id = $rs -> fields['sqld_id'];
		delete_sql_database($sql, $domain_id, $db_id);

		$rs -> MoveNext();
	}

// END - DELETE ALL SYSTEM ENTRIES FOR THIS USER

// BEGIN - DELETE ALL GUI ENTRIES FOR THIS USER
	// delete the layout settings
	 $query = <<<SQL_QUERY
    	    DELETE FROM
        	    user_gui_props
        	WHERE
            	user_id = ?
SQL_QUERY;
	$rs = exec_query($sql, $query, array($id_user));


	// update reseller props


	//delete all tickets for this user
	$query = <<<SQL_QUERY
    	    DELETE FROM
        	    tickets
        	WHERE
              ticket_from = ?
            OR
              ticket_to = ?
SQL_QUERY;
	$rs = exec_query($sql, $query, array($id_user, $id_user));

//let's delete the main domain for this user

	    $query = <<<SQL_QUERY
    	    UPDATE
        	    domain
	        SET
    	        domain_status = ?
        	WHERE
            	domain_admin_id = ?
SQL_QUERY;
	$rs = exec_query($sql, $query, array($delete_status, $id_user));


	// delete the user acount
	 $query = <<<SQL_QUERY
    	    DELETE FROM
        	    admin
        	WHERE
            	admin_id = ?
SQL_QUERY;
	$rs = exec_query($sql, $query, array($id_user));


}

function substract_from_reseller_props($reseller_id, $domain_id) {
// function update reseller props bevore deleting account
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
         ) =  generate_reseller_props($reseller_id);

    list (
           $sub_current, $sub_max,
           $als_current, $als_max,
           $mail_current, $mail_max,
           $ftp_current, $ftp_max,
           $sql_db_current, $sql_db_max,
           $sql_user_current, $sql_user_max,
           $traff_max, $disk_max
         ) = generate_user_props($domain_id);

    $rdmn_current -= 1;

    if ($sub_max != -1) {

        $rsub_current -= $sub_max;

    }

    if ($als_max != -1) {

        $rals_current -= $als_max;

    }

    $rmail_current -= $mail_max;

    $rftp_current -= $ftp_max;

    if ($sql_db_max != -1) {

        $rsql_db_current -= $sql_db_max;

    }

    if ($sql_user_max != -1) {

        $rsql_user_current -= $sql_user_max;

    }

    $rtraff_current -= $traff_max;

    $rdisk_current -= $disk_max;

    $rprops  = "$rdmn_current;$rdmn_max;";
    $rprops .= "$rsub_current;$rsub_max;";
    $rprops .= "$rals_current;$rals_max;";
    $rprops .= "$rmail_current;$rmail_max;";
    $rprops .= "$rftp_current;$rftp_max;";
    $rprops .= "$rsql_db_current;$rsql_db_max;";
    $rprops .= "$rsql_user_current;$rsql_user_max;";
    $rprops .= "$rtraff_current;$rtraff_max;";
    $rprops .= "$rdisk_current;$rdisk_max;";

    update_reseller_props($reseller_id, $rprops);

}

function gen_purchase_haf(&$tpl, &$sql, $user_id)
{
	$query = <<<SQL_QUERY
			SELECT
				header, footer
			FROM
				orders_settings
			WHERE
				user_id = ?

SQL_QUERY;

  $rs = exec_query($sql, $query, array($user_id));

  if ($rs -> RecordCount() == 0) {

$header = <<<RIC
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>VHCS - Order Panel</title>
<style type="text/css">
<!--
body {
	margin-left: 0px;
	margin-top: 0px;
	margin-right: 0px;
	margin-bottom: 0px;
	background-color: #FFFFFF;
}
a.link {color:#000099;text-decoration:underline;font-weight: normal;}
a.link:link {color:#000099;text-decoration:underline;font-weight: normal;}
a.link:visited {color:#000099; text-decoration:underline; font-weight:normal}
a.link:hover {color:#000000; text-decoration: none;}
a.link:active {color:#000000;text-decoration:none;}

.title {
	font-family: Geneva, Arial, Helvetica, sans-serif;
	font-size: 16px;
	font-weight: bold;
	text-decoration: none;
}

td {
	font-family: Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
}

td.content {
	font-size: 12px;
	color: #333333;
	white-space: nowrap;
	background: #F9F9F9;
	white-space: nowrap;
	BORDER-TOP: #EFF0F7 1px solid;
	BORDER-LEFT: #EFF0F7 1px solid;
	BORDER-RIGHT: #EFF0F7 1px solid;
	BORDER-BOTTOM: #EFF0F7 1px solid;
	height: 22px;
	padding: 2px;
}

td.content2 {
	font-size: 12px;
	color: #333333;
	white-space: nowrap;
	background: #E1EFFF;
	white-space: nowrap;
	BORDER-TOP: #EFF0F7 1px solid;
	BORDER-LEFT: #EFF0F7 1px solid;
	BORDER-RIGHT: #EFF0F7 1px solid;
	BORDER-BOTTOM: #EFF0F7 1px solid;
	height: 22px;
	padding: 2px;
}

td.content3 {
	font-size: 12px;
	color: #000000;
	white-space: nowrap;
	background: #B6D5F8;
	white-space: nowrap;
	BORDER-TOP: #6C98D9 1px solid;
	BORDER-LEFT: #6C98D9 1px solid;
	BORDER-RIGHT: #6C98D9 1px solid;
	BORDER-BOTTOM: #6C98D9 1px solid;
	height: 30px;
	padding: 2px;

.button
{
	font-family: Geneva, Arial, Helvetica, sans-serif;
	height: 22px;
	font-size: 12px;
	color: #000000;
	text-align: center;
	background-image: url(/vhcs2/themes/modern_blue/images/button.gif);
	background-repeat: repeat-x;
	border: 1px solid #326BC0;
}

}
-->
</style>
</head>
<center>
<table width="100%" height="95%">
  <tr align="center">
    <td align="center">
RIC;


$footer = <<<RIC
</td>
  </tr>
</table>
</center>
<body>
</body>
</html>
RIC;

  } else {

	$header = $rs -> fields['header'];
	$footer = $rs -> fields['footer'];

	$header    = str_replace ('\\', '', "$header");
	$footer    = str_replace ('\\', '', "$footer");
  }

  $tpl -> assign('PURCHASE_HEADER', $header);
  $tpl -> assign('PURCHASE_FOOTER', $footer);
}

// Function by Tribal-Dolphin
function send_tickets_msg($to_id,$from_id,$ticket_subject) {
    global $sql;
    global $admin_login;
    global $cfg;
// To information
    $query = <<<SQL_QUERY
        SELECT
            fname, lname, email, admin_name
        FROM
            admin
        WHERE
            admin_id = '$to_id'
SQL_QUERY;

    $res = execute_query($sql, $query);
    $to_email = $res -> fields['email'];
    $to_fname = $res -> fields['fname'];
    $to_lname = $res -> fields['lname'];
    $to_uname = $res -> fields['admin_name'];
// From information
    $query = <<<SQL_QUERY
        SELECT
            fname, lname, email, admin_name
        FROM
            admin
        WHERE
            admin_id = ?
SQL_QUERY;

    $res = exec_query($sql, $query, $from_id);
    $from_email = $res -> fields['email'];
    $from_fname = $res -> fields['fname'];
    $from_lname = $res -> fields['lname'];
	$from_uname = $res -> fields['admin_name'];

// Prepare message
    $subject = tr('[Ticket]')." {SUBJ}";
    $message = tr("Hello {TO_NAME} !\n\nYou have a new ticket to read");
// Format adresses
    if ($from_fname && $from_lname) {
        $from = "$from_fname $from_lname <$from_email>";
		$fromname = "$from_fname $from_lname";
    } else {
        $from = $from_email;
		$fromname = $from_uname;
    }
    if ($to_fname && $to_lname) {
        $to = "$to_fname $to_lname <$to_email>";
        $name = "$to_fname $to_lname";
    } else {
        $name = $to_uname;
        $to = $to_email;
    }
// Prepare and send mail
    $subject = preg_replace("/\{SUBJ\}/", $ticket_subject, $subject);
    $message = preg_replace("/\{TO_NAME\}/", $name, $message);
    $message = preg_replace("/\{FROM_NAME\}/", $fromname, $message);

    $headers = "From: $from\n";

    $headers .= "MIME-Version: 1.0\nContent-Type: text/plain\nContent-Transfer-Encoding: 7bit\n";

		$headers .=	"X-Mailer: VHCS ".$cfg['Version']." Tickets Mailer";

    $mail_result = mail($to, $subject, $message, $headers);
    $mail_status = ($mail_result) ? 'OK' : 'NOT OK';
    write_log("$admin_login: Auto Ticket To: |$to|, From: |$from|, Status: |$mail_status|!");
}

function setConfig_Value($name, $value) {

	global $sql, $cfg;

	$query = "SELECT name FROM config WHERE name='" . $name . "'";

	$res = exec_query($sql, $query, array());

	if ($res -> RecordCount() == 0) {

   	$query = "INSERT INTO config (name, value) VALUES ('" . $name . "','" . $value . "')";

   	exec_query($sql, $query, array());

	} else {

		$query = "UPDATE config SET	value='" . $value . "' WHERE name='" . $name . "'";

		$res = exec_query($sql, $query, array());

	}

	$cfg[$name] = $value;

	return TRUE;

}


?>
