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

$tpl -> define_dynamic('page', $cfg['ADMIN_TEMPLATE_PATH'].'/manage_reseller_users.tpl');

$tpl -> define_dynamic('page_message', 'page');

$tpl -> define_dynamic('hosting_plans', 'page');

$tpl -> define_dynamic('reseller_list', 'page');

$tpl -> define_dynamic('reseller_item', 'reseller_list');

$tpl -> define_dynamic('src_reseller', 'page');

$tpl -> define_dynamic('src_reseller_option', 'src_reseller');

$tpl -> define_dynamic('dst_reseller', 'page');

$tpl -> define_dynamic('dst_reseller_option', 'dst_reseller');

global $cfg;
$theme_color = $cfg['USER_INITIAL_THEME'];


function gen_user_table(&$tpl, &$sql)
{

    $query = <<<SQL_QUERY
        SELECT
            admin_id, admin_name
        FROM
            admin
        WHERE
            admin_type = 'reseller'
        ORDER BY
            admin_name
SQL_QUERY;

    $rs = exec_query($sql, $query, array());

	if ($rs -> RecordCount() == 0) {

        set_page_message(tr('Reseller or user list is empty!'));
      	header('Location: manage_users.php');
		die();
    }


    $reseller_id = $rs -> fields['admin_id'];

    while (!$rs -> EOF) {

         $selected = '';

        if (isset($_POST['uaction']) && $_POST['uaction'] === 'change_src') {

            if (isset($_POST['src_reseller']) && $_POST['src_reseller'] == $rs->fields['admin_id']){

                 $selected = 'selected';

                 $reseller_id = $_POST['src_reseller'];
            }
        }
        else if (isset($_POST['uaction']) && $_POST['uaction'] === 'move_user') {

            if (isset($_POST['dst_reseller']) && $_POST['dst_reseller'] == $rs -> fields['admin_id'])
            {

                $selected = 'selected';

                $reseller_id = $_POST['dst_reseller'];
            }
        }
        $tpl -> assign(
            array(
                'SRC_RSL_OPTION' => $rs -> fields['admin_name'],
                'SRC_RSL_VALUE' => $rs -> fields['admin_id'],
                'SRC_RSL_SELECTED' =>$selected,
                )
            );

        $tpl -> assign(
            array(
                'DST_RSL_OPTION' => $rs -> fields['admin_name'],
                'DST_RSL_VALUE' => $rs -> fields['admin_id'],
                )
            );

        $tpl -> parse('SRC_RESELLER_OPTION', '.src_reseller_option');

        $tpl -> parse('DST_RESELLER_OPTION', '.dst_reseller_option');

        $rs -> MoveNext();

    }

    $query = <<<SQL_QUERY
        SELECT
            admin_id, admin_name
        FROM
            admin
        WHERE
            admin_type = 'user'
          AND
            created_by = ?
        ORDER BY
            admin_name
SQL_QUERY;

    $rs = exec_query($sql, $query, array($reseller_id));

    $i = 0;

    if ($rs -> RecordCount() == 0) {

        set_page_message(tr('User list is empty!'));

        $tpl -> assign('RESELLER_LIST', '');

    }
    else {

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

            $admin_id = $rs -> fields['admin_id'];

            $admin_id_var_name = "admin_id_$admin_id";

			$show_admin_name = decode_idna($rs -> fields['admin_name']);

            $tpl -> assign(
                array(
                    'NUMBER' => $i+1,
                    'USER_NAME' => $show_admin_name,
                    'CKB_NAME' => $admin_id_var_name,
                    )
                );

            $tpl -> parse('RESELLER_ITEM', '.reseller_item');

            $rs -> MoveNext();

            $i++;

        }

        $tpl -> parse('RESELLER_LIST', 'reseller_list');

    }

}



function update_reseller_user($sql)
{
    if (isset($_POST['uaction']) && $_POST['uaction'] === 'move_user') {

        if(check_user_data()){

            set_page_message(tr('User was moved'));
        }
    }
}

function check_user_data()
{
    global $sql;

    $query = <<<SQL_QUERY
        SELECT
            admin_id
        FROM
            admin
        WHERE
            admin_type = 'user'
        ORDER BY
            admin_name
SQL_QUERY;

    $rs = exec_query($sql, $query, array());

    $selected_users ='';

    while (!$rs -> EOF) {

        $admin_id = $rs -> fields['admin_id'];

        $admin_id_var_name = "admin_id_$admin_id";

        if(isset($_POST[$admin_id_var_name]) && $_POST[$admin_id_var_name] === 'on'){

            $selected_users .= $rs->fields['admin_id'].';';
        }

        $rs -> Movenext();
    }



    if ($selected_users == '') {

        set_page_message(tr('Please select some user(s)!'));

        return false;

    } else if ($_POST['src_reseller'] == $_POST['dst_reseller']) {

        set_page_message(tr('Source and destination reseller are the same!'));

        return false;

    }

    $dst_reseller = $_POST['dst_reseller'];

    $query = <<<SQL_QUERY
        select
            reseller_ips
        from
            reseller_props
        where
            reseller_id = ?
SQL_QUERY;

    $rs = exec_query($sql, $query, array($dst_reseller));

    $mru_error = '_off_';

    $dest_reseller_ips = $rs->fields['reseller_ips'];

    check_ip_sets($dest_reseller_ips, $selected_users, $mru_error);

    if ($mru_error == '_off_') {

        manage_reseller_limits($_POST['dst_reseller'], $_POST['src_reseller'], $selected_users, $mru_error);

    }

    if ($mru_error != '_off_') {

        set_page_message($mru_error);

        return false;

    }

    return true;
}

function manage_reseller_limits ($dest_reseller, $src_reseller, $users, &$err) {

    global $sql;

    list (
           $dest_dmn_current, $dest_dmn_max,
           $dest_sub_current, $dest_sub_max,
           $dest_als_current, $dest_als_max,
           $dest_mail_current, $dest_mail_max,
           $dest_ftp_current, $dest_ftp_max,
           $dest_sql_db_current, $dest_sql_db_max,
           $dest_sql_user_current, $dest_sql_user_max,
           $dest_traff_current, $dest_traff_max,
           $dest_disk_current, $dest_disk_max
         ) = generate_reseller_props($dest_reseller);

    list (
           $src_dmn_current, $src_dmn_max,
           $src_sub_current, $src_sub_max,
           $src_als_current, $src_als_max,
           $src_mail_current, $src_mail_max,
           $src_ftp_current, $src_ftp_max,
           $src_sql_db_current, $src_sql_db_max,
           $src_sql_user_current, $src_sql_user_max,
           $src_traff_current, $src_traff_max,
           $src_disk_current, $src_disk_max
         ) = generate_reseller_props($src_reseller);

    $users_array = explode(";", $users);

    for ($i = 0; $i < count($users_array) - 1; $i++) {

        $query = <<<SQL_QUERY
            select
                domain_id, domain_name
            from
                domain
            where
                domain_admin_id = ?
SQL_QUERY;

        $rs = exec_query($sql, $query, array($users_array[$i]));

        $domain_name = $rs->fields['domain_name'];

        $domain_id = $rs->fields['domain_id'];

        list (
                $sub_current, $sub_max,
                $als_current, $als_max,
                $mail_current, $mail_max,
                $ftp_current, $ftp_max,
                $sql_db_current, $sql_db_max,
                $sql_user_current, $sql_user_max,
                $traff_max, $disk_max
             ) = generate_user_props($domain_id);

        calculate_reseller_vals ($dest_dmn_current, $dest_dmn_max, $src_dmn_current, $src_dmn_max, 1, $err, 'Domain', $domain_name);

        if ($err == '_off_') {

            calculate_reseller_dvals ($dest_sub_current, $dest_sub_max, $src_sub_current, $src_sub_max, $sub_max, $err, 'Subdomain', $domain_name);

        }

        if ($err == '_off_') {

            calculate_reseller_dvals ($dest_als_current, $dest_als_max, $src_als_current, $src_als_max, $als_max, $err, 'Alias', $domain_name);

        }

        if ($err == '_off_') {

            calculate_reseller_vals ($dest_mail_current, $dest_mail_max, $src_mail_current, $src_mail_max, $mail_max, $err, 'Mail', $domain_name);

        }

        if ($err == '_off_') {

            calculate_reseller_vals ($dest_ftp_current, $dest_ftp_max, $src_ftp_current, $src_ftp_max, $ftp_max, $err, 'FTP', $domain_name);

        }

        if ($err == '_off_') {

            calculate_reseller_dvals ($dest_sql_db_current, $dest_sql_db_max, $src_sql_db_current, $src_sql_db_max, $sql_db_max, $err, 'SQL Database', $domain_name);

        }

        if ($err == '_off_') {

            calculate_reseller_dvals ($dest_sql_user_current, $dest_sql_user_max, $src_sql_user_current, $src_sql_user_max, $sql_user_max, $err, 'SQL User', $domain_name);

        }

        if ($err == '_off_') {

            calculate_reseller_vals ($dest_traff_current, $dest_traff_max, $src_traff_current, $src_traff_max, $traff_max, $err, 'Traffic', $domain_name);

        }

        if ($err == '_off_') {

            calculate_reseller_vals ($dest_disk_current, $dest_disk_max, $src_disk_current, $src_disk_max, $disk_max, $err, 'Disk', $domain_name);

        }

        if ($err != '_off_') {

            return false;

        }

    }

    //
    // Let's Make Necessary Updates;
    //

   $src_reseller_props  = "$src_dmn_current;$src_dmn_max;";
    $src_reseller_props .= "$src_sub_current;$src_sub_max;";
    $src_reseller_props .= "$src_als_current;$src_als_max;";
    $src_reseller_props .= "$src_mail_current;$src_mail_max;";
    $src_reseller_props .= "$src_ftp_current;$src_ftp_max;";
    $src_reseller_props .= "$src_sql_db_current;$src_sql_db_max;";
    $src_reseller_props .= "$src_sql_user_current;$src_sql_user_max;";
    $src_reseller_props .= "$src_traff_current;$src_traff_max;";
    $src_reseller_props .= "$src_disk_current;$src_disk_max;";

    update_reseller_props($src_reseller, $src_reseller_props);

    $dest_reseller_props  = "$dest_dmn_current;$dest_dmn_max;";
    $dest_reseller_props .= "$dest_sub_current;$dest_sub_max;";
    $dest_reseller_props .= "$dest_als_current;$dest_als_max;";
    $dest_reseller_props .= "$dest_mail_current;$dest_mail_max;";
    $dest_reseller_props .= "$dest_ftp_current;$dest_ftp_max;";
    $dest_reseller_props .= "$dest_sql_db_current;$dest_sql_db_max;";
    $dest_reseller_props .= "$dest_sql_user_current;$dest_sql_user_max;";
    $dest_reseller_props .= "$dest_traff_current;$dest_traff_max;";
    $dest_reseller_props .= "$dest_disk_current;$dest_disk_max;";

    update_reseller_props($dest_reseller, $dest_reseller_props);

    for ($i = 0; $i < count($users_array) - 1; $i++) {

        $query = "update admin set created_by = ? where admin_id = ?";
        exec_query($sql, $query, array($dest_reseller, $users_array[$i]));

        $query = "update domain set domain_created_id = ? where domain_admin_id = ?";
        exec_query($sql, $query, array($dest_reseller, $users_array[$i]));
    }

    return true;

}
function calculate_reseller_dvals (&$dest, $dest_max, &$src, $src_max, $umax, &$err, $obj, $uname) {

    if ($dest_max == 0 && $src_max == 0 && $umax == -1) {

        return;

    } else if ($dest_max == 0 && $src_max == 0 && $umax == 0) {

        return;

    } else if ($dest_max == 0 && $src_max == 0 && $umax > 0) {

        $src -= $umax;

        $dest += $umax;

        return;

    } else if ($dest_max == 0 && $src_max > 0 && $umax == -1) {

        return;

    } else if ($dest_max == 0 && $src_max > 0 && $umax == 0) {

        // Impossible condition;

        return;

    } else if ($dest_max == 0 && $src_max > 0 && $umax > 0) {

        $src -= $umax;

        $dest += $umax;

        return;

    } else if ($dest_max > 0 && $src_max == 0 && $umax == -1) {

        return;

    } else if ($dest_max > 0 && $src_max == 0 && $umax == 0) {

        $err = tr('<b>%1$s</b> has unlimited rights for a <b>%2$s</b> Service !<br>', $uname, $obj);

        $err .= tr('You can not move <b>%1$s</b> in a destination reseller,<br>which has limits for the <b>%2$s</b> service!', $uname, $obj);

        return;

    } else if ($dest_max > 0 && $src_max == 0 && $umax > 0) {

        if ($dest + $umax > $dest_max) {

            $err = tr('<b>%1$s</b> is exceeding limits for a <b>%2$s</b><br>service in destination reseller!<br>', $uname, $obj);

            $err .= tr('Moving aborted!');

        } else {

            $src -= $umax;

            $dest += $umax;

        }

        return;

    } else if ($dest_max > 0 && $src_max > 0 && $umax == -1) {

        return;

    } else if ($dest_max > 0 && $src_max > 0 && $umax == 0) {

        // Impossible condition;

        return;

    } else if ($dest_max > 0 && $src_max > 0 && $umax > 0) {

        if ($dest + $umax > $dest_max) {

             $err = tr('<b>%1$s</b> is exceeding limits for a <b>%2$s</b><br>service in destination reseller!<br>', $uname, $obj);

             $err .= tr('Moving aborted!');

        } else {

            $src -= $umax;

            $dest += $umax;

        }

        return;

    }

}


function calculate_reseller_vals (&$dest, $dest_max, &$src, $src_max, $umax, &$err, $obj, $uname) {

    if ($dest_max == 0 && $src_max == 0 && $umax == 0) {

        return;

    } else if ($dest_max == 0 && $src_max == 0 && $umax > 0) {

        $src -= $umax;

        $dest += $umax;

        return;

    } else if ($dest_max == 0 && $src_max > 0 && $umax == 0) {

        // Impossible condition;

        return;

    } else if ($dest_max == 0 && $src_max > 0 && $umax > 0) {

        $src -= $umax;

        $dest += $umax;

        return;

    } else if ($dest_max > 0 && $src_max == 0 && $umax == 0) {

        $err = tr('<b>%1$s</b> has unlimited rights for a <b>%2$s</b> service !<br>', $uname, $obj);

        $err .= tr('You can not move <b>%1$s</b> in a destination reseller,<br>which has limits for the <b>%2$s</b> service!', $uname, $obj);

        return;

    } else if ($dest_max > 0 && $src_max == 0 && $umax > 0) {

        if ($dest + $umax > $dest_max) {

            $err = tr('<b>%1$s</b> is exceeding limits for a <b>%2$s</b><br>service in destination reseller!<br>', $uname, $obj);

            $err .= tr('Moving aborted!');

        } else {

            $src -= $umax;

            $dest += $umax;

        }

        return;

    } else if ($dest_max > 0 && $src_max > 0 && $umax == 0) {

        // Impossible condition;

        return;

    } else if ($dest_max > 0 && $src_max > 0 && $umax > 0) {

        if ($dest + $umax > $dest_max) {

            $err = tr('<b>%1$s</b> is exceeding limits for a <b>%2$s</b><br>service in destination reseller!<br>', $uname, $obj);

            $err .= tr('Moving aborted!');

        } else {

            $src -= $umax;

            $dest += $umax;

        }

        return;

    }

}

function check_ip_sets($dest, $users, &$err)
{

    global $sql;

    $users_array = explode(";", $users);

    for ($i = 0; $i < count($users_array); $i++) {

        $query = <<<SQL_QUERY
            select
                domain_name, domain_ip_id
            from
                domain
            where
                domain_admin_id = ?
SQL_QUERY;

        $rs = exec_query($sql, $query, array($users_array[$i]));

        $domain_ip_id = $rs->fields['domain_ip_id'];

        $domain_name = $rs->fields['domain_name'];

        if (!preg_match("/$domain_ip_id;/", $dest)) {

            $err = tr('<b>%s</b> has IP address that can not be managed from the destination reseller !<br>This user can not be moved!', $domain_name);

            return false;

        }

    }

    return true;
}



/*
 *
 * static page messages.
 *
 */

$tpl -> assign(
    array(
        'TR_ADMIN_MANAGE_RESELLER_USERS_PAGE_TITLE' => tr('ISPCP - Admin/Manage users/User assignment'),
        'THEME_COLOR_PATH' => "../themes/$theme_color",
        'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id']),
        'ISPCP_LICENSE' => $cfg['ISPCP_LICENSE']
        )
    );

gen_admin_mainmenu($tpl, $cfg['ADMIN_TEMPLATE_PATH'].'/main_menu_manage_users.tpl');
gen_admin_menu($tpl, $cfg['ADMIN_TEMPLATE_PATH'].'/menu_manage_users.tpl');

update_reseller_user($sql);

gen_user_table($tpl, $sql);

$tpl -> assign(
    array(
        'TR_USER_ASSIGNMENT' => tr('User assignment'),
        'TR_RESELLER_USERS' => tr('Users'),
        'TR_NUMBER' => tr('No.'),
        'TR_MARK' => tr('Mark'),
        'TR_USER_NAME' => tr('User name'),
        'TR_FROM_RESELLER' => tr('From reseller'),
        'TR_TO_RESELLER' => tr('To reseller'),
        'TR_MOVE' => tr('Move'),
        )
    );

gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if ($cfg['DUMP_GUI_DEBUG']) dump_gui_debug();

unset_messages();
?>