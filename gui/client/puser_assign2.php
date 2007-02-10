<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2005 by moleSoftware	|
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

$tpl -> define_dynamic('page', $cfg['CLIENT_TEMPLATE_PATH'].'/puser_assign.tpl');

$tpl -> define_dynamic('page_message', 'page');

$tpl -> define_dynamic('logged_from', 'page');

$tpl -> define_dynamic('already_in', 'page');

$tpl -> define_dynamic('grp_avlb', 'page');

$tpl -> define_dynamic('add_button', 'page');

$tpl -> define_dynamic('remove_button', 'page');

$theme_color = $_SESSION['user_theme_color'];

$tpl -> assign(
                array(
                        'THEME_COLOR_PATH' => "../themes/$theme_color",
                        'THEME_CHARSET' => tr('encoding'),
						'TID' => $_SESSION['layout_id'],
                        'VHCS_LICENSE' => $cfg['VHCS_LICENSE'],
						'ISP_LOGO' => get_logo($_SESSION['user_id'])
                     )
              );

// ** Functions

function get_htuser_name(&$sql, &$uuser_id, &$dmn_id)
{

$query = <<<SQL_QUERY

        select

            uname

        from

            htaccess_users

        where

             dmn_id = '$dmn_id'

        and
			id = '$uuser_id'

SQL_QUERY;

    $rs = execute_query($sql, $query);

	if ($rs -> RecordCount() == 0) {
		header('Location: puser_manage.php');
		die();
	} else {

		return $rs;
	}



}


function gen_user_assign(&$tpl, &$sql, &$dmn_id)
{
	if (isset($_GET['uname']) && $_GET['uname'] !== '' && is_numeric($_GET['uname'])) {

	$uuser_id = $_GET['uname'];
	$uname = get_htuser_name($sql, $uuser_id, $dmn_id);

		$tpl -> assign(
					array(
							'UNAME' => $uname,
							 )
					);


	} else if (isset($_POST['nadmin_name']) && !empty($_POST['nadmin_name']) && is_numeric($_POST['nadmin_name'] )) {
		$uuser_id = clean_input($_POST['nadmin_name']);
		$uname = get_htuser_name($sql, $uuser_id, $dmn_id);
		$tpl -> assign(
					array(
							'UNAME' => $uname,
							 )
					);


	}else {
		header('Location: puser_manage.php');
		die();
	}

	// lets generate all groups where the user is added

	$numg = 0;
    $ingr = '';
	global $cfg;
	$homedir = $cfg['FTP_HOMEDIR'];
	$domain_name = $_SESSION['user_logged'];
    $file = $homedir.'/'.$domain_name.'/.htgroup';
    $fd   = fopen($file,'r');

	while(!feof($fd)){
       $line = fgets($fd,4096);
       $ua = explode(':',$line);
	   if (count($ua) > 1) {
           if (strstr($ua[1],$uname)) {
              $tpl -> assign(
					array(
							'GRP_IN' => $ua[0],
							 )
					);
			  $tpl -> parse('ALREADY_IN', '.already_in');
			  $tpl -> parse('REMOVE_BUTTON', 'remove_button');

			  $ingr .= ' '.$ua[0];
              $numg++;
		   }
       }
    }
    fclose($fd);
	if ($numg < 1) {
			$tpl -> assign('ALREADY_IN', '');
			$tpl -> assign('REMOVE_BUTTON', '');
	}

	//end generate all groups where the user is added


	// and now generate all groups

	$numg = 0;
    $file = $homedir.'/'.$domain_name.'/.htgroup';
    $fd   = fopen($file,'r');
	while(!feof($fd)){
         $line = fgets($fd,4096);
         $ua   = explode(':',$line);
         if (count($ua) > 1) {
              if (!strstr($ingr,$ua[0])) {
				   $tpl -> assign('GRP_NAME', $ua[0]);
				   $tpl -> parse('GRP_AVLB', '.grp_avlb');
				   $tpl -> parse('ADD_BUTTON', 'add_button');
                   $numg++;
              }
         }
     }
     fclose($fd);
	 if ($numg < 1) {
	 	$tpl -> assign('GRP_AVLB', '');
		$tpl -> assign('ADD_BUTTON', '');
	 }
	//end of all groups
}

function add_user_to_group(&$tpl)
{
	if(isset($_POST['uaction']) && $_POST['uaction'] == 'add' && isset($_POST['groups'])
		&& !empty($_POST['groups']) && isset($_POST['nadmin_name']))
	{
		$uname = clean_input($_POST['nadmin_name']);
		$groups = $_POST['groups'];

			/* add user to group */
			$content = '';
			global $cfg;
			$homedir = $cfg['FTP_HOMEDIR'];
			$domain_name = $_SESSION['user_logged'];
			$file = $homedir.'/'.$domain_name.'/.htgroup';
			@$fd  = fopen($file,'r');

			if (!$fd) {
				/* cannot open file for reading */
			   set_page_message(tr('Can not open file! Please contact your administrator !'));
			   return;
			}

			while(!feof($fd)){
				$line = fgets($fd,4096);
				$ua   = explode(':',$line);
				if (count($ua) > 1) {
					if (strstr($groups,$ua[0])) {
						$content .= substr($line,0,strlen($line)-1) . " " . $uname . "\n";
					}
					else {
						$content .= $line;
					}
				}
			}
			fclose($fd);
			$fd = fopen($file,'w');
			fputs($fd,$content);
			fclose($fd);
			set_page_message(tr('User was assigned to group ').$groups);

	} else {
		return;
	}

}

function delete_user_from_group(&$tpl)
{
	if(isset($_POST['uaction']) && $_POST['uaction'] == 'remove' && isset($_POST['groups_in'])
		&& !empty($_POST['groups_in']) && isset($_POST['nadmin_name']))
	{

		$groups_in = $_POST['groups_in'];
		$uname = clean_input($_POST['nadmin_name']);
        /* delete user from group */
        $content = '';

		global $cfg;
		$homedir = $cfg['FTP_HOMEDIR'];
		$domain_name = $_SESSION['user_logged'];
        $file = $homedir.'/'.$domain_name.'/.htgroup';
        @$fd  = fopen($file,'r');

        if (!$fd) {
           set_page_message(tr('Can not open file! Please contact your administrator !'));
			return;
        }

        while(!feof($fd)){
            $line = fgets($fd,4096);
            $ua   = explode(':',$line);
            if (count($ua) > 1) {
                if (strstr($groups_in,$ua[0])) {
                    $pos      = strpos($line, $uname);
                    $content .= substr($line,0,$pos-1);
                    $content .= substr($line,$pos+strlen($uname)+1,strlen($line)-1)."\n";
                }
                else {
                    $content .= $line;
                }
            }
        }
        fclose($fd);

        $fd = fopen($file,'w');
        fputs($fd,$content);
        fclose($fd);
       set_page_message(tr('User was deleted from group ').$groups_in);
	} else {
		return;
	}
}


// ** end of funcfions

gen_client_mainmenu($tpl, $cfg['CLIENT_TEMPLATE_PATH'].'/main_menu_webtools.tpl');
gen_client_menu($tpl, $cfg['CLIENT_TEMPLATE_PATH'].'/menu_webtools.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$dmn_id = get_user_domain_id($sql, $_SESSION['user_id']);

//add_user_to_group($tpl, $sql, $dmn_id);

//delete_user_from_group($tpl, $sql, $dmn_id);

gen_user_assign($tpl, $sql, $dmn_id);

$tpl -> assign(
                array(
						'TR_HTACCESS' => tr('Protected areas'),
						'TR_DELETE' => tr('Delete'),
						'TR_USER_ASSIGN' => tr('User assign'),
						'TR_ALLREADY' => tr('Already in:'),
						'TR_NOT_IN' => tr('Not in:'),
						'TR_BACK' => tr('Back'),
						'TR_REMOVE' => tr('Remove'),
						'TR_ADD' => tr('Add'),

					  )
				);

gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

unset_messages();
?>
