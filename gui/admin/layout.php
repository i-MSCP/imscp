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



function save_layout(&$sql)
{

    if (isset($_POST['uaction']) && $_POST['uaction'] === 'save_layout') {

        $user_id = $_SESSION['user_id'];

        $user_layout = $_POST['def_layout'];

        $query = <<<SQL_QUERY
            update
                user_gui_props
            set
                layout = ?
            where
                user_id = ?
SQL_QUERY;
        $rs = exec_query($sql, $query, array($user_layout, $user_id));
    	$_SESSION['user_theme_color'] = $user_layout;
        $theme_color = $user_layout;
		$user_def_layout = $user_layout;

    }
}


function update_logo()
{
    global $cfg;

    if (isset($_POST['uaction']) && $_POST['uaction'] === 'upload_logo') {


            $file_type = $_FILES['logo_file']['type'];

            if(empty($_FILES['logo_file']['name'])) {

                    set_page_message(tr('Upload file error!'));

                    return;
            }
            if (!($file_type === "image/gif" || $file_type === "image/jpeg" || $file_type === "image/pjpeg" || $file_type === "image/png")){

                    set_page_message(tr('You can upload only images!'));

                    return;

            }

            else {

                $fname = $_FILES['logo_file']['name'];

                $fsize = $_FILES['logo_file']['size'];

                $user_id = $_SESSION['user_id'];

                $path1 = substr($_SERVER['SCRIPT_FILENAME'],0, strpos($_SERVER['SCRIPT_FILENAME'], '/admin/layout.php')+1);
                $path2 = substr($cfg['ROOT_TEMPLATE_PATH'],0, strpos($cfg['ROOT_TEMPLATE_PATH'], '/tpl')+1);
                //
                move_uploaded_file($_FILES['logo_file']['tmp_name'], $path1."/themes/user_logos/".get_user_name($user_id).".jpg");

                update_user_gui_props(get_user_name($user_id).".jpg", $user_id);

                set_page_message(tr('Your logo was successful uploaded!'));
            }

    }
}


function update_user_gui_props($file_name, $user_id)
{
    global $sql;

    $query = <<<SQL_QUERY
        update
            user_gui_props
        set
            logo = ?
        where
            user_id = ?
SQL_QUERY;

    $rs = exec_query($sql, $query, array($file_name, $user_id));

}


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

include '../include/vhcs-lib.php';

check_login();

$tpl = new pTemplate();

$tpl -> define_dynamic('page', $cfg['ADMIN_TEMPLATE_PATH'].'/layout.tpl');

$tpl -> define_dynamic('page_message', 'page');
$tpl -> define_dynamic('hosting_plans', 'page');

$tpl -> define_dynamic('def_layout', 'page');





save_layout($sql);

update_logo();

global $cfg;
$theme_color = $cfg['USER_INITIAL_THEME'];

gen_def_layout($tpl, $sql, $user_def_layout);

$tpl -> assign(
                array(
                        'TR_ADMIN_CHANGE_LAYOUT_PAGE_TITLE' => tr('VHCS - Virtual Hosting Control System'),
                        'THEME_COLOR_PATH' => "../themes/$theme_color",
                        'ISP_LOGO' => get_logo($_SESSION['user_id']),
                        'THEME_CHARSET' => tr('encoding'),
                        'VHCS_LICENSE' => $cfg['VHCS_LICENSE']
                     )
              );

/*
 *
 * static page messages.
 *
 */

gen_admin_menu($tpl, $cfg['ADMIN_TEMPLATE_PATH'].'/menu_settings.tpl');

$tpl -> assign(
                array(
                        'TR_LAYOUT_SETTINGS' => tr('Layout settings'),
						'TR_INSTALLED_LAYOUTS' => tr('Installed layouts'),
						'TR_LAYOUT_NAME' => tr('Layout name'),
						'TR_DEFAULT' => tr('default'),
						'TR_YES' => tr('yes'),
						'TR_SAVE' => tr('Save'),
						'TR_UPLOAD_LOGO' => tr('Upload logo'),
						'TR_LOGO_FILE' => tr('Logo file'),
						'TR_UPLOAD' => tr('Upload'),
						'TR_CHOOSE_DEFAULT_LAYOUT' => tr('Choose default layout'),
						'TR_LAYOUT' => tr('Layout'),
                     )
              );

gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

unset_messages();
?>
