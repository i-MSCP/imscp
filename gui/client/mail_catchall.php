<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
 * @version 	SVN: $ID$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
 *
 * @license
 *   This program is free software; you can redistribute it and/or modify it under
 *   the terms of the MPL General Public License as published by the Free Software
 *   Foundation; either version 1.1 of the License, or (at your option) any later
 *   version.
 *   You should have received a copy of the MPL Mozilla Public License along with
 *   this program; if not, write to the Open Source Initiative (OSI)
 *   http://opensource.org | osi@opensource.org
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::get('CLIENT_TEMPLATE_PATH') . '/mail_catchall.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('mail_message', 'page');
$tpl->define_dynamic('mail_item', 'page');
$tpl->define_dynamic('mail_auto_respond', 'mail_item');
$tpl->define_dynamic('mails_total', 'page');
$tpl->define_dynamic('catchall_message', 'page');
$tpl->define_dynamic('catchall_item', 'page');
$tpl->define_dynamic('ftp_message', 'page');
$tpl->define_dynamic('ftp_item', 'page');
$tpl->define_dynamic('no_mails', 'page');

// page functions.

function gen_user_mail_action($mail_id, $mail_status) {
    if ($mail_status === Config::get('ITEM_OK_STATUS')) {
        return array(tr('Delete'), "mail_delete.php?id=$mail_id", "mail_edit.php?id=$mail_id");
    } else {
        return array(tr('N/A'), '#', '#');
    }
}

function gen_user_mail_auto_respond(&$tpl, $mail_id, $mail_type, $mail_status, $mail_auto_respond) {
    if (preg_match('/_mail$/', $mail_type) == 1) {
        if ($mail_status === Config::get('ITEM_OK_STATUS')) {
            if ($mail_auto_respond === '_no_') {
                $tpl->assign(
                    array('AUTO_RESPOND_ACTION' => tr('Enable'),
                        'AUTO_RESPOND_ACTION_SCRIPT' => "mail_autoresponder_enable.php?id=$mail_id"
                        )
                    );
            } else {
                $tpl->assign(
                    array('AUTO_RESPOND_ACTION' => tr('Disable'),
                        'AUTO_RESPOND_ACTION_SCRIPT' => "mail_autoresponder_disable.php?id=$mail_id"
                        )
                    );
            }
            $tpl->parse('MAIL_AUTO_RESPOND', 'mail_auto_respond');
        } else {
            $tpl->assign('MAIL_AUTO_RESPOND', '');
        }
    } else {
        $tpl->assign('MAIL_AUTO_RESPOND', '');
    }
}

function gen_user_catchall_action($mail_id, $mail_status) {
    if ($mail_status === Config::get('ITEM_ADD_STATUS')) {
        return array(tr('N/A'), '#');//Addition in progress
    } else if ($mail_status === Config::get('ITEM_OK_STATUS')) {
        return array(tr('Delete CatchAll'), "mail_catchall_delete.php?id=$mail_id");
    } else if ($mail_status === Config::get('ITEM_CHANGE_STATUS')) {
        return array(tr('N/A'), '#');
    } else if ($mail_status === Config::get('ITEM_DELETE_STATUS')) {
        return array(tr('N/A'), '#');
    } else {
        return null;
    }
}

function gen_catchall_item(&$tpl, $action, $dmn_id, $dmn_name, $mail_id, $mail_acc, $mail_status, $ca_type) {
    $show_dmn_name = decode_idna($dmn_name);

    if ($action === 'create') {
        $tpl->assign(
            array('CATCHALL_DOMAIN' => $show_dmn_name,
                'CATCHALL_ACC' => tr('None'),
                'CATCHALL_STATUS' => tr('N/A'),
                'CATCHALL_ACTION' => tr('Create catch all'),
                'CATCHALL_ACTION_SCRIPT' => "mail_catchall_add.php?id=$dmn_id;$ca_type"
                )
            );
    } else {
        list($catchall_action, $catchall_action_script) = gen_user_catchall_action($mail_id, $mail_status);

        $show_dmn_name = decode_idna($dmn_name);
        $show_mail_acc = decode_idna($mail_acc);

        $tpl->assign(
            array('CATCHALL_DOMAIN' => $show_dmn_name,
                'CATCHALL_ACC' => $show_mail_acc,
                'CATCHALL_STATUS' => translate_dmn_status($mail_status),
                'CATCHALL_ACTION' => $catchall_action,
                'CATCHALL_ACTION_SCRIPT' => $catchall_action_script
                )
            );
    }
}

function gen_page_catchall_list(&$tpl, &$sql, $dmn_id, $dmn_name) {
    global $counter;

    $tpl->assign('CATCHALL_MESSAGE', '');

        $query = <<<SQL_QUERY
            select
                mail_id, mail_acc, status
            from
                mail_users
            where
                domain_id = '$dmn_id'
              and
                sub_id = 0
              and
                mail_type = 'normal_catchall'
SQL_QUERY;

        $rs = execute_query($sql, $query);

        if ($rs->RecordCount() == 0) {
            gen_catchall_item($tpl, 'create', $dmn_id, $dmn_name, '', '', '', 'dmn');
        } else {
            gen_catchall_item($tpl,
                'delete',
                $dmn_id,
                $dmn_name,
                $rs->fields['mail_id'],
                $rs->fields['mail_acc'],
                $rs->fields['status'], 'dmn');
        }
        $tpl->assign(
            array('ITEM_CLASS' => 'content',
                )
            );

        $tpl->parse('CATCHALL_ITEM', 'catchall_item');

        $query = <<<SQL_QUERY
            select
                alias_id, alias_name
            from
                domain_aliasses
            where
                domain_id = '$dmn_id'
              and
                alias_status = 'ok'
SQL_QUERY;

        $rs = execute_query($sql, $query);

        while (!$rs->EOF) {
            if ($counter % 2 == 0) {
                $tpl->assign('ITEM_CLASS', 'content2');
            } else {
                $tpl->assign('ITEM_CLASS', 'content');
            }

            $als_id = $rs->fields['alias_id'];

            $als_name = $rs->fields['alias_name'];

            $query = <<<SQL_QUERY
                select
                    mail_id, mail_acc, status
                from
                    mail_users
                where
                    domain_id = '$dmn_id'
                  and
                    sub_id = '$als_id'
                  and
                    mail_type = 'alias_catchall'
SQL_QUERY;

            $rs_als = execute_query($sql, $query);

            if ($rs_als->RecordCount() == 0) {
                gen_catchall_item($tpl, 'create', $als_id, $als_name, '', '', '', 'als');
            } else {
                gen_catchall_item($tpl,
                    'delete',
                    $als_id,
                    $als_name,
                    $rs_als->fields['mail_id'],
                    $rs_als->fields['mail_acc'],
                    $rs_als->fields['status'], 'als');
            }

            $tpl->parse('CATCHALL_ITEM', '.catchall_item');

            $rs->MoveNext();
            $counter ++;
        }

        $query = <<<SQL_QUERY
            select
                a.subdomain_id, CONCAT(a.subdomain_name,'.',b.domain_name) as subdomain_name
            from
                subdomain as a, domain as b
            where
                a.domain_id = '$dmn_id'
            and
	           	a.domain_id = b.domain_id
	        and
	            a.subdomain_status = 'ok'
SQL_QUERY;

        $rs = execute_query($sql, $query);

        while (!$rs->EOF) {
            if ($counter % 2 == 0) {
                $tpl->assign('ITEM_CLASS', 'content2');
            } else {
                $tpl->assign('ITEM_CLASS', 'content');
            }

            $als_id = $rs->fields['subdomain_id'];

            $als_name = $rs->fields['subdomain_name'];

            $query = <<<SQL_QUERY
                select
                    mail_id, mail_acc, status
                from
                    mail_users
                where
                    domain_id = '$dmn_id'
                  and
                    sub_id = '$als_id'
                  and
                    mail_type = 'subdom_catchall'
SQL_QUERY;

            $rs_als = execute_query($sql, $query);

            if ($rs_als->RecordCount() == 0) {
                gen_catchall_item($tpl, 'create', $als_id, $als_name, '', '', '', 'sub');
            } else {
                gen_catchall_item($tpl,
                    'delete',
                    $als_id,
                    $als_name,
                    $rs_als->fields['mail_id'],
                    $rs_als->fields['mail_acc'],
                    $rs_als->fields['status'], 'sub');
            }

            $tpl->parse('CATCHALL_ITEM', '.catchall_item');

            $rs->MoveNext();
            $counter ++;
        }
}

function gen_page_lists(&$tpl, &$sql, $user_id)
{
    list($dmn_id,
        $dmn_name,
        $dmn_gid,
        $dmn_uid,
        $dmn_created_id,
        $dmn_created,
        $dmn_last_modified,
        $dmn_mailacc_limit,
        $dmn_ftpacc_limit,
        $dmn_traff_limit,
        $dmn_sqld_limit,
        $dmn_sqlu_limit,
        $dmn_status,
        $dmn_als_limit,
        $dmn_subd_limit,
        $dmn_ip_id,
        $dmn_disk_limit,
        $dmn_disk_usage,
        $dmn_php,
        $dmn_cgi) = get_domain_default_props($sql, $user_id);

    gen_page_catchall_list($tpl, $sql, $dmn_id, $dmn_name);
    // gen_page_ftp_list($tpl, $sql, $dmn_id, $dmn_name);
}

// common page data.

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
    array('TR_CLIENT_MANAGE_USERS_PAGE_TITLE' => tr('ispCP - Client/Manage Users'),
        'THEME_COLOR_PATH' => "../themes/$theme_color",
        'THEME_CHARSET' => tr('encoding'),
        'ISP_LOGO' => get_logo($_SESSION['user_id'])
        )
    );

// dynamic page data.

if (isset($_SESSION['email_support']) && $_SESSION['email_support'] == "no") {
    $tpl->assign('NO_MAILS', '');
}

gen_page_lists($tpl, $sql, $_SESSION['user_id']);

// static page messages.

gen_client_mainmenu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/main_menu_mail_accounts.tpl');
gen_client_menu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/menu_mail_accounts.tpl');

gen_logged_from($tpl);
check_permissions($tpl);

$tpl->assign(
    array('TR_MANAGE_USERS' => tr('Manage users'),
        'TR_MAIL_USERS' => tr('Mail users'),
        'TR_MAIL' => tr('Mail'),
        'TR_TYPE' => tr('Type'),
        'TR_STATUS' => tr('Status'),
        'TR_ACTION' => tr('Action'),
        'TR_AUTORESPOND' => tr('Auto respond'),
        'TR_DMN_MAILS' => tr('Domain mails'),
        'TR_SUB_MAILS' => tr('Subdomain mails'),
        'TR_ALS_MAILS' => tr('Alias mails'),
        'TR_TOTAL_MAIL_ACCOUNTS' => tr('Mails total'),
        'TR_TOTAL_FTP_ACCOUNTS' => tr('FTPs total'),
        'TR_CATCHALL_MAIL_USERS' => tr('Catch all account'),
        'TR_DOMAIN' => tr('Domain'),
        'TR_CATCHALL' => tr('Catch all'),
        'TR_FTP_USERS' => tr('FTP users'),
        'TR_FTP_ACCOUNT' => tr('FTP account'),
        'TR_FTP_ACTION' => tr('Action'),
        'TR_EDIT' => tr('Edit'),
        'TR_DELETE' => tr('Delete'),
        'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete', true),
        )
    );

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) dump_gui_debug();

unset_messages();

?>