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

$tpl -> define_dynamic('page', $cfg['CLIENT_TEMPLATE_PATH'].'/catchall.tpl');

$tpl -> define_dynamic('page_message', 'page');

$tpl -> define_dynamic('logged_from', 'page');

$tpl -> define_dynamic('mail_message', 'page');

$tpl -> define_dynamic('mail_item', 'page');

$tpl -> define_dynamic('mail_auto_respond', 'mail_item');

$tpl -> define_dynamic('mails_total', 'page');

$tpl -> define_dynamic('catchall_message', 'page');

$tpl -> define_dynamic('catchall_item', 'page');

$tpl -> define_dynamic('ftp_message', 'page');

$tpl -> define_dynamic('ftp_item', 'page');

$tpl -> define_dynamic('no_mails', 'page');

//
// page functions.
//

function gen_user_mail_action($mail_id, $mail_status)
{

    global $cfg;
	
	

    if ($mail_status === $cfg['ITEM_OK_STATUS']) {

         return array(tr('Delete'), "delete_mail_acc.php?id=$mail_id", "edit_mail_acc.php?id=$mail_id");

    } else {

        return array(tr('N/A'), '#', '#');

    } 
}

function gen_user_mail_auto_respond(&$tpl, $mail_id, $mail_type, $mail_status, $mail_auto_respond)
{

    global $cfg;

    if (preg_match('/_mail$/', $mail_type) == 1) {

        if ($mail_status === $cfg['ITEM_OK_STATUS']) {

            if ($mail_auto_respond === '_no_') {

                $tpl -> assign(
                                array(
                                        'AUTO_RESPOND_ACTION' => tr('Enable'),
                                        'AUTO_RESPOND_ACTION_SCRIPT' => "enable_mail_arsp.php?id=$mail_id"
                                     )
                              );

            } else {

                $tpl -> assign(
                                array(
                                        'AUTO_RESPOND_ACTION' => tr('Disable'),
                                        'AUTO_RESPOND_ACTION_SCRIPT' => "disable_mail_arsp.php?id=$mail_id"
                                     )
                              );

            }

            $tpl -> parse('MAIL_AUTO_RESPOND', 'mail_auto_respond');

        } else {

            $tpl -> assign('MAIL_AUTO_RESPOND', '');

        }

    } else {

        $tpl -> assign('MAIL_AUTO_RESPOND', '');

    }

}

function gen_page_dmn_mail_list(&$tpl, &$sql, $dmn_id, $dmn_name)
{

    $dmn_query = <<<SQL_QUERY

        select

            mail_id, mail_acc, mail_type, status, mail_auto_respond

        from

            mail_users

        where

            domain_id = '$dmn_id'

          and

            sub_id = 0
			
		and 
			
				(mail_type  = 'normal_mail'
			or
				mail_type  = 'normal_forward')

        order by

            mail_type desc,

            mail_id

SQL_QUERY;

    $rs = execute_query($sql, $dmn_query);

    if ($rs -> RecordCount() == 0) {

        return 0;

    } else {

        while (!$rs -> EOF) {

            list($mail_action, $mail_action_script, $mail_edit_script) = gen_user_mail_action($rs -> fields['mail_id'], $rs -> fields['status']);

			$mail_acc = decode_idna($rs -> fields['mail_acc']);
			
			$show_dmn_name = decode_idna($dmn_name);
			

            $tpl -> assign(
                            array(
                                    'MAIL_ACC' => $mail_acc."@".$show_dmn_name,
                                    'MAIL_TYPE' => user_trans_mail_type($rs -> fields['mail_type']),
                                    'MAIL_STATUS' => translate_dmn_status($rs -> fields['status']),
                                    'MAIL_ACTION' => $mail_action,
                                    'MAIL_ACTION_SCRIPT' => $mail_action_script,
									'MAIL_EDIT_SCRIPT' => $mail_edit_script
                                 )
                          );

            gen_user_mail_auto_respond($tpl,
                                       $rs -> fields['mail_id'],
                                       $rs -> fields['mail_type'],
                                       $rs -> fields['status'],
                                       $rs -> fields['mail_auto_respond']);

            $tpl -> parse('MAIL_ITEM', '.mail_item');

            $rs -> MoveNext();

        }

        return $rs -> RecordCount();

    }

}

function gen_page_sub_mail_list(&$tpl, &$sql, $dmn_id, $dmn_name)
{

    $sub_query = <<<SQL_QUERY

        select

            t1.subdomain_id as sub_id,
            t1.subdomain_name as sub_name,
            t2.mail_id,
            t2.mail_acc,
            t2.mail_type,
            t2.status,
            t2.mail_auto_respond

        from

            subdomain as t1,

            mail_users as t2

        where

            t1.domain_id = '$dmn_id'

          and

    	     t2.domain_id = '$dmn_id'
			
		and 
			
				(t2.mail_type = 'subdom_mail'
			or
				t2.mail_type = 'subdom_forward')

          and

            t1.subdomain_id = t2.sub_id
		

        order by

            t2.mail_type desc, t2.mail_id

SQL_QUERY;

    $rs = execute_query($sql, $sub_query);

    if ($rs -> RecordCount() == 0) {

        return 0;

    } else {

        while (!$rs -> EOF) {

            list($mail_action, $mail_action_script, $mail_edit_script) = gen_user_mail_action($rs -> fields['mail_id'], $rs -> fields['status']);

			$mail_acc = decode_idna($rs -> fields['mail_acc']);
			
			$show_sub_name = decode_idna($rs -> fields['sub_name']);
			
			$show_dmn_name = decode_idna($dmn_name);
			
            $tpl -> assign(
                            array(
                                    'MAIL_ACC' => $mail_acc."@".$show_sub_name.".".$show_dmn_name,
                                    'MAIL_TYPE' => user_trans_mail_type($rs -> fields['mail_type']),
                                    'MAIL_STATUS' => user_trans_item_status($rs -> fields['status']),
                                    'MAIL_ACTION' => $mail_action,
                                    'MAIL_ACTION_SCRIPT' => $mail_action_script,
									'MAIL_EDIT_SCRIPT' => $mail_edit_script
                                 )
                          );

            gen_user_mail_auto_respond($tpl,
                                       $rs -> fields['mail_id'],
                                       $rs -> fields['mail_type'],
                                       $rs -> fields['status'],
                                       $rs -> fields['mail_auto_respond']);

            $tpl -> parse('MAIL_ITEM', '.mail_item');

            $rs -> MoveNext();

        }

        return $rs -> RecordCount();

    }

}

function gen_page_als_mail_list(&$tpl, &$sql, $dmn_id, $dmn_name)
{

    $als_query = <<<SQL_QUERY

        select

            t1.alias_id as als_id,
            t1.alias_name as als_name,
            t2.mail_id,
            t2.mail_acc,
            t2.mail_type,
            t2.status,
            t2.mail_auto_respond

        from

            domain_aliasses as t1,
            mail_users as t2

        where

            t1.domain_id = '$dmn_id'

          and

            t2.domain_id = '$dmn_id'

          and

            t1.alias_id = t2.sub_id
			
		and 
			
				(t2.mail_type = 'alias_mail'
			or
				t2.mail_type = 'alias_forward')

        order by

            t2.mail_type desc, t2.mail_id

SQL_QUERY;

    $rs = execute_query($sql, $als_query);

    if ($rs -> RecordCount() == 0) {

        return 0;

    } else {

        while (!$rs -> EOF) {

            list($mail_action, $mail_action_script, $mail_edit_script) = gen_user_mail_action($rs -> fields['mail_id'], $rs -> fields['status']);

			
			$mail_acc = decode_idna($rs -> fields['mail_acc']);
			
			$show_dmn_name = decode_idna($dmn_name);
			
			$show_als_name = decode_idna($rs -> fields['als_name']);

			$tpl -> assign(
                            array(
                                    'MAIL_ACC' => $mail_acc."@".$show_als_name,     
                                    'MAIL_TYPE' => user_trans_mail_type($rs -> fields['mail_type']),
                                    'MAIL_STATUS' => user_trans_item_status($rs -> fields['status']),
                                    'MAIL_ACTION' => $mail_action,
                                    'MAIL_ACTION_SCRIPT' => $mail_action_script,
									'MAIL_EDIT_SCRIPT' => $mail_edit_script
                                 )
                          );

            gen_user_mail_auto_respond($tpl,
                                       $rs -> fields['mail_id'],
                                       $rs -> fields['mail_type'],
                                       $rs -> fields['status'],
                                       $rs -> fields['mail_auto_respond']);

            $tpl -> parse('MAIL_ITEM', '.mail_item');

            $rs -> MoveNext();

        }

        return $rs -> RecordCount();

    }

}

function gen_user_catchall_action($mail_id, $mail_status)
{

    global $cfg;

    if ($mail_status === $cfg['ITEM_ADD_STATUS']) {

        return array(tr('N/A'), '#');

    } else if ($mail_status === $cfg['ITEM_OK_STATUS']) {

        return array(tr('Delete CatchAll'), "delete_catchall.php?id=$mail_id");

    } else if ($mail_status === $cfg['ITEM_CHANGE_STATUS']) {

        return array(tr('N/A'), '#');

    } else if ($mail_status === $cfg['ITEM_DELETE_STATUS']) {

        return array(tr('N/A'), '#');

    }

}

function gen_catchall_item(&$tpl, $action, $dmn_id, $dmn_name, $mail_id, $mail_acc, $mail_status, $ca_type)
{

	$show_dmn_name = decode_idna($dmn_name);
			
    if ($action === 'create') {

        $tpl -> assign(
                          array(
                                  'CATCHALL_DOMAIN' => $show_dmn_name,
                                  'CATCHALL_ACC' => tr('None'),
                                  'CATCHALL_STATUS' => tr('N/A'),
                                  'CATCHALL_ACTION' => tr('Create catch all'),
                                  'CATCHALL_ACTION_SCRIPT' => "create_catchall.php?id=$dmn_id;$ca_type"
                               )
                      );
    } else {

        list($catchall_action, $catchall_action_script) = gen_user_catchall_action($mail_id, $mail_status);

		$show_dmn_name = decode_idna($dmn_name);
		$show_mail_acc = decode_idna($mail_acc);
		
        $tpl -> assign(
                        array(
                                'CATCHALL_DOMAIN' => $show_dmn_name,
                                'CATCHALL_ACC' => $show_mail_acc,
                                'CATCHALL_STATUS' => user_trans_item_status($mail_status),
                                'CATCHALL_ACTION' => $catchall_action,
                                'CATCHALL_ACTION_SCRIPT' => $catchall_action_script
                             )
                      );

    }

}

function gen_page_catchall_list(&$tpl, &$sql, $dmn_id, $dmn_name, $dmn_mails, $als_mails, $sub_mails)
{
	global $counter;
    $total_mails = $dmn_mails + $als_mails + $sub_mails;

    if ($total_mails == 0) {

        $tpl -> assign(
                        array(
                                'CATCHALL_MSG' => tr('Catch all mail account can not be created!'),
                                'CATCHALL_ITEM' => '',
								'ITEM_CLASS' => 'content',
                             )
                      );

        $tpl -> parse('CATCHALL_MESSAGE', 'catchall_message');

        return;

    }

    $tpl -> assign('CATCHALL_MESSAGE', '');

    //
    // Have We Any Domain Mails?
    //

    if ($dmn_mails > 0) {

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

        if ($rs -> RecordCount() == 0) {

            gen_catchall_item($tpl, 'create', $dmn_id, $dmn_name, '', '', '', 'dmn');

        } else {

            gen_catchall_item($tpl,
                              'delete',
                              $dmn_id,
                              $dmn_name,
                              $rs -> fields['mail_id'],
                              $rs -> fields['mail_acc'],
                              $rs -> fields['status'], 'dmn');

        }
		$tpl -> assign(
                        array(
								'ITEM_CLASS' => 'content',
                             )
                      );

        $tpl -> parse('CATCHALL_ITEM', 'catchall_item');

    }

    //
    // Have We Any Alias Mails?
    //

    if ($als_mails > 0) {

        $query = <<<SQL_QUERY

            select

                alias_id, alias_name

            from

                domain_aliasses

            where

                domain_id = '$dmn_id'

SQL_QUERY;

        $rs = execute_query($sql, $query);

        while (!$rs -> EOF) {
		
			if ($counter % 2 == 0) {
            
               		 $tpl -> assign('ITEM_CLASS', 'content2');
            
          	} else {
                
		             $tpl -> assign('ITEM_CLASS', 'content');      
           	}


            $als_id = $rs -> fields['alias_id'];

            $als_name = $rs -> fields['alias_name'];

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
    
            if ($rs_als -> RecordCount() == 0) {
    
                gen_catchall_item($tpl, 'create', $als_id, $als_name, '', '', '', 'als');
    
            } else {
    
                gen_catchall_item($tpl,
                                  'delete',
                                  $als_id,
                                  $als_name,
                                  $rs_als -> fields['mail_id'],
                                  $rs_als -> fields['mail_acc'],
                                  $rs_als -> fields['status'], 'als');
    
            }
    
            $tpl -> parse('CATCHALL_ITEM', '.catchall_item');

            $rs -> MoveNext(); $counter ++;

        }

    }
    //
    // Have We Any Subdomain Mails?
    //

    if ($sub_mails > 0) {

        $query = <<<SQL_QUERY

            select

                a.subdomain_id, CONCAT(a.subdomain_name,'.',b.domain_name) as subdomain_name

            from

                subdomain as a, domain as b

            where

                a.domain_id = '$dmn_id'
            
            and 
            	a.domain_id = b.domain_id

SQL_QUERY;

        $rs = execute_query($sql, $query);

        while (!$rs -> EOF) {
		
			if ($counter % 2 == 0) {
            
               		 $tpl -> assign('ITEM_CLASS', 'content2');
            
          	} else {
                
		             $tpl -> assign('ITEM_CLASS', 'content');      
           	}


            $als_id = $rs -> fields['subdomain_id'];

            $als_name = $rs -> fields['subdomain_name'];

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
    
            if ($rs_als -> RecordCount() == 0) {
    
                gen_catchall_item($tpl, 'create', $als_id, $als_name, '', '', '', 'sub');
    
            } else {
    
                gen_catchall_item($tpl,
                                  'delete',
                                  $als_id,
                                  $als_name,
                                  $rs_als -> fields['mail_id'],
                                  $rs_als -> fields['mail_acc'],
                                  $rs_als -> fields['status'], 'sub');
    
            }
    
            $tpl -> parse('CATCHALL_ITEM', '.catchall_item');

            $rs -> MoveNext(); $counter ++;

        }

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

    $dmn_mails = gen_page_dmn_mail_list($tpl, $sql, $dmn_id, $dmn_name);

    $sub_mails = gen_page_sub_mail_list($tpl, $sql, $dmn_id, $dmn_name);

    $als_mails = gen_page_als_mail_list($tpl, $sql, $dmn_id, $dmn_name);

    $total_mails = $dmn_mails + $sub_mails + $als_mails;

    if ($total_mails > 0) {

        $tpl -> assign(
                        array(
                                'MAIL_MESSAGE' => '',
                                'DMN_TOTAL' => $dmn_mails,
                                'SUB_TOTAL' => $sub_mails,
                                'ALS_TOTAL' => $als_mails,
                                'TOTAL_MAIL_ACCOUNTS' => $total_mails
                             )
                      );

    } else {

        $tpl -> assign(
                        array(
                                'MAIL_MSG' => tr('Mail accounts list is empty!'),
                                'MAIL_ITEM' => '',
                                'MAILS_TOTAL' => ''
                             )
                      );

        $tpl -> parse('MAIL_MESSAGE', 'mail_message');

    }

    gen_page_catchall_list($tpl, $sql, $dmn_id, $dmn_name, $dmn_mails, $als_mails, $sub_mails);

    //gen_page_ftp_list($tpl, $sql, $dmn_id, $dmn_name);

    return $total_mails;

}

//
// common page data.
//

global $cfg;
$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(
                array(
                        'TR_CLIENT_MANAGE_USERS_PAGE_TITLE' => tr('ISPCP - Client/Manage Users'),
                        'THEME_COLOR_PATH' => "../themes/$theme_color",
                        'THEME_CHARSET' => tr('encoding'),
						'TID' => $_SESSION['layout_id'],
                        'ISPCP_LICENSE' => $cfg['ISPCP_LICENSE'],
						'ISP_LOGO' => get_logo($_SESSION['user_id'])
                     )
              );

//
// dynamic page data.
//

if (isset($_SESSION['email_support']) && $_SESSION['email_support'] == "no")
{
		$tpl -> assign('NO_MAILS', '');
}

gen_page_lists($tpl, $sql, $_SESSION['user_id']);

//
// static page messages.
//

gen_client_mainmenu($tpl, $cfg['CLIENT_TEMPLATE_PATH'].'/main_menu_email_accounts.tpl');
gen_client_menu($tpl, $cfg['CLIENT_TEMPLATE_PATH'].'/menu_email_accounts.tpl');

gen_logged_from($tpl);
check_permissions($tpl);


$tpl -> assign(
                array(
                        'TR_MANAGE_USERS' => tr('Manage users'),
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
						'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete'),
                     )
              );

gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

unset_messages();

?>
