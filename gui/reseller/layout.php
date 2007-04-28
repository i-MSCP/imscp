<?php
/**
 *  ispCP (OMEGA) a Virtual Hosting Control Panel
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

include '../include/ispcp-lib.php';

check_login();

$tpl = new pTemplate();

$tpl -> define_dynamic('page', $cfg['RESELLER_TEMPLATE_PATH'].'/layout.tpl');

$tpl -> define_dynamic('page_message', 'page');

$tpl -> define_dynamic('logged_from', 'page');

$tpl -> define_dynamic('def_layout', 'page');

$theme_color = $cfg['USER_INITIAL_THEME'];


function save_layout()
{

    global $sql, $theme_color;

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

        $theme_color = $user_layout;

        $_SESSION['user_theme_color'] = $user_layout;
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

                $path1 = substr($_SERVER['SCRIPT_FILENAME'],0, strpos($_SERVER['SCRIPT_FILENAME'], '/reseller/layout.php')+1);

                $path2 = substr($cfg['ROOT_TEMPLATE_PATH'],0, strpos($cfg['ROOT_TEMPLATE_PATH'], '/tpl')+1);

                $logoFile = $path1."/themes/user_logos/".get_user_name($user_id).".jpg";
				move_uploaded_file($_FILES['logo_file']['tmp_name'], $logoFile);
				chmod ($logoFile, 0644);

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

save_layout();

gen_def_layout($tpl, $sql, $theme_color);

$tpl -> assign(
                array(
                        'TR_RESELLER_LAYOUT_DATA_PAGE_TITLE' => tr('ISPCP - Reseller/Change Personal Data'),
                        'THEME_COLOR_PATH' => "../themes/$theme_color",
                        'OWN_LOGO' => get_own_logo($_SESSION['user_id']),
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

gen_reseller_mainmenu($tpl, $cfg['RESELLER_TEMPLATE_PATH'].'/main_menu_general_information.tpl');
gen_reseller_menu($tpl, $cfg['RESELLER_TEMPLATE_PATH'].'/menu_general_information.tpl');

gen_logged_from($tpl);

update_logo();

$tpl -> assign(
                array(
                        'TR_LAYOUT_SETTINGS' => tr('Layout settings'),
						'TR_INSTALLED_LAYOUTS' => tr('Installed layouts'),
						'TR_LAYOUT_NAME' => tr('Layout name'),
						'TR_LAYOUT' => tr('Layout'),
						'TR_DEFAULT' => tr('default'),
						'TR_YES' => tr('yes'),
						'TR_SAVE' => tr('Save'),
						'TR_UPLOAD_LOGO' => tr('Upload logo'),
						'TR_LOGO_FILE' => tr('Logo file'),
						'TR_UPLOAD' => tr('Upload'),
						'TR_CHOOSE_DEFAULT_LAYOUT' => tr('Choose default layout'),
                     )
              );

gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

unset_messages();
?>
