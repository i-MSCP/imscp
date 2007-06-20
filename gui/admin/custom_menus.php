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


// site functions

function gen_button_list(&$tpl, &$sql)
{
  $query = <<<SQL_QUERY
    	select
          *
      from
          custom_menus
SQL_QUERY;

    $rs = exec_query($sql, $query, array());
	 if ($rs -> RecordCount() == 0) {

        $tpl -> assign('BUTTON_LIST', '');

        set_page_message(tr('You have no custom menus.'));

    } else {

		global $i ;

		while (!$rs -> EOF) {

		$menu_id = $rs -> fields['menu_id'];
		$menu_level = $rs -> fields['menu_level'];
		$menu_name = $rs -> fields['menu_name'];
		$menu_link = $rs -> fields['menu_link'];


		if ($menu_level === 'admin'){

			$menu_level = tr('Administrator');

		} else if ($menu_level === 'reseller'){

			$menu_level = tr('Reseller');

		} else if ($menu_level === 'user'){

			$menu_level = tr('User');

		} else if ($menu_level === 'all'){

			$menu_level = tr('All');

		}

		$tpl -> assign(
                            array(
                                    'BUTTON_LINK' => $menu_link,
									'BUTONN_ID' => $menu_id,
									'LEVEL' => $menu_level,
									'MENU_NAME' => $menu_name,
									'LINK' => $menu_link,
									'CONTENT' => ($i % 2 == 0) ? 'content' : 'content2',
									'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete'),

                                 )
                          );

            $tpl -> parse('BUTTON_LIST', '.button_list');
            $rs->MoveNext();
            $i++;
		} // end while
	} // end else


}

function add_new_button(&$sql)
{

    if (!isset($_POST['uaction'])) {
        return;
    } else if ($_POST['uaction'] != 'new_button') {
        return;
    } else {

		$button_name = clean_input($_POST['bname']);
		$button_link = clean_input($_POST['blink']);
		$button_target = clean_input($_POST['btarget']);
		$button_view = $_POST['bview'];


		if (empty($button_name) || empty($button_link)){

			set_page_message(tr('Missing or incorrect data input!'));
			return;

		}

		$query = <<<SQL_QUERY
    	insert into custom_menus
          (
            menu_level,
            menu_name,
            menu_link,
            menu_target
          )
        values
          (
            ?,?,?,?
          )
SQL_QUERY;

    $rs = exec_query($sql, $query, array($button_view,
                                         $button_name,
                                         $button_link,
                                         $button_target));

		set_page_message(tr('Custom menu data updated successful!'));
		return;
	}
}

function delete_button(&$sql)
{

	 if ($_GET['delete_id'] === '' || !is_numeric($_GET['delete_id']))  {

		set_page_message(tr('Missing or incorrect data input!'));
		return;

	} else {

	$delete_id = $_GET['delete_id'];

 $query = <<<SQL_QUERY
            delete
                from custom_menus
            where
                menu_id  = ?
SQL_QUERY;

        $rs = exec_query($sql, $query, array($delete_id));

			set_page_message(tr('Custom menu deleted successful!'));
			return;

	}
}

function edit_button(&$tpl, &$sql)
{

	if ($_GET['edit_id'] === '' || !is_numeric($_GET['edit_id']))  {

		set_page_message(tr('Missing or incorrect data input!'));
		return;

	} else {

		$edit_id = $_GET['edit_id'];

	$query = <<<SQL_QUERY
      select
            *
      from
            custom_menus
      where
            menu_id = ?
SQL_QUERY;

    $rs = exec_query($sql, $query, array($edit_id));
		 if ($rs -> RecordCount() == 0) {

			set_page_message(tr('Missing or incorrect data input!'));
			$tpl -> assign('EDIT_BUTTON', '');
			return;

		} else {

			$tpl -> assign('ADD_BUTTON', '');

			$button_name =  $rs -> fields['menu_name'];
			$button_link =  $rs -> fields['menu_link'];
			$button_target =  $rs -> fields['menu_target'];
			$button_view = $rs -> fields['menu_level'];

			if ($button_view === 'admin'){
				$admin_view = "selected";
				$reseller_view = "";
				$user_view = "";
				$all_view = "";

			} else if ($button_view === 'reseller'){
				$admin_view = "";
				$reseller_view = "selected";
				$user_view = "";
				$all_view = "";

			} else if ($button_view === 'user'){
				$admin_view = "";
				$reseller_view = "";
				$user_view = "selected";
				$all_view = "";

			} else {
				$admin_view = "";
				$reseller_view = "";
				$user_view = "";
				$all_view = "selected";
			}


			$tpl -> assign(
                            array(
                                    'BUTON_NAME' => $button_name,
									'BUTON_LINK' => $button_link,
									'BUTON_TARGET' => $button_target,
									'ADMIN_VIEW' => $admin_view,
									'RESELLER_VIEW' => $reseller_view,
									'USER_VIEW' => $user_view,
									'ALL_VIEW' => $all_view,
									'EID' => $_GET['edit_id']

                                 )
                          );

            $tpl -> parse('EDIT_BUTTON', '.edit_button');
		}
	}
}

function update_button(&$sql)
{

	if (!isset($_POST['uaction'])) { return; }

	else if ($_POST['uaction'] != 'edit_button')  { return; }

	else {

		$button_name = clean_input($_POST['bname']);
		$button_link = clean_input($_POST['blink']);
		$button_target = clean_input($_POST['btarget']);
		$button_view = $_POST['bview'];
		$button_id =$_POST['eid'];

			if (empty($button_name) || empty($button_link) || empty($button_id)){

				set_page_message(tr('Missing or incorrect data input!'));
				return;
			}

$query = <<<SQL_QUERY
      update
          custom_menus
      set
          menu_level = ?,
          menu_name = ?,
          menu_link = ?,
          menu_target = ?
      where
          menu_id = ?
SQL_QUERY;

		$rs = exec_query($sql, $query, array($button_view,
                                         $button_name,
                                         $button_link,
                                         $button_target,
                                         $button_id));

		set_page_message(tr('Custom menu data updated successful!'));
		return;
	}

}

// end site functions

require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();

$tpl -> define_dynamic('page', $cfg['ADMIN_TEMPLATE_PATH'].'/custom_menus.tpl');

$tpl -> define_dynamic('page_message', 'page');
$tpl -> define_dynamic('hosting_plans', 'page');

$tpl -> define_dynamic('button_list', 'page');

$tpl -> define_dynamic('button_list', 'page');

$tpl -> define_dynamic('add_button', 'page');

$tpl -> define_dynamic('edit_button', 'page');

global $cfg;
$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(
    array(
            'TR_ADMIN_CUSTOM_MENUS_PAGE_TITLE' => tr('ISPCP - Admin - Manage custom menus'),
            'THEME_COLOR_PATH' => "../themes/$theme_color",
            'THEME_CHARSET' => tr('encoding'),
			'ISP_LOGO' => get_logo($_SESSION['user_id']),
            'ISPCP_LICENSE' => $cfg['ISPCP_LICENSE']
            )
    );
gen_admin_mainmenu($tpl, $cfg['ADMIN_TEMPLATE_PATH'].'/main_menu_settings.tpl');
gen_admin_menu($tpl, $cfg['ADMIN_TEMPLATE_PATH'].'/menu_settings.tpl');

add_new_button($sql);

if (isset($_GET['delete_id'])){

	delete_button($sql);
}

if (isset($_GET['edit_id'])){
	edit_button($tpl, $sql);
}

update_button($sql);

gen_button_list($tpl, $sql);



$tpl -> assign(
    array(
        'TR_TITLE_CUSTOM_MENUS' => tr('Manage custom menus'),
		'TR_ADD_NEW_BUTTON' => tr('Add new button'),
		'TR_BUTTON_NAME' => tr('Button name'),
		'TR_BUTTON_LINK' => tr('Button link'),
		'TR_BUTTON_TARGET' => tr('Button target'),
		'TR_VIEW_FROM' => tr('Show in'),
		'ADMIN' => tr('Administrator level'),
		'RESELLER' => tr('Reseller level'),
		'USER' => tr('Enduser level'),
		'RESSELER_AND_USER' => tr('Reseller and enduser level'),
		'TR_ADD' => tr('Add'),
		'TR_MENU_NAME' => tr('Menu button'),
		'TR_ACTON' => tr('Action'),
		'TR_EDIT' => tr('Edit'),
		'TR_DELETE' => tr('Delete'),
		'TR_LEVEL' => tr('Level'),
		'TR_SAVE' => tr('Save'),
		'TR_EDIT_BUTTON' => tr('Edit button'),

        )
    );

gen_page_message($tpl);

if (isset($_GET['edit_id'])){

	  $tpl -> assign('ADD_BUTTON', '');

} else {

	$tpl -> assign('EDIT_BUTTON', '');
}


$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if ($cfg['DUMP_GUI_DEBUG']) dump_gui_debug();

unset_messages();
?>