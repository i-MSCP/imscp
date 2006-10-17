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

$tpl -> define_dynamic('page_message', 'page');

$tpl -> define_dynamic('logged_from', 'page');

$tpl -> define_dynamic('dir_item', 'page');

$tpl -> define_dynamic('action_link', 'page');

$tpl -> define_dynamic('list_item', 'page');

$tpl -> define_dynamic('page', $cfg['CLIENT_TEMPLATE_PATH'].'/ftp_choose_dir.tpl');

$theme_color = $cfg['USER_INITIAL_THEME'];

// functions begin

function cleanPath($path) {
   $result = array();
   $pathTemp = explode('/', $path);
   if (!$pathTemp[0])
       $result[] = '';
   foreach ($pathTemp AS $key => $dir) {
       if ($dir == '..') {
           if (end($result) == '..') {
               $result[] = '..';
           } elseif (!array_pop($result)) {
               $result[] = '..';
           }
       } elseif ($dir && $dir != '.') {
           $result[] = $dir;
       }
   }
   if (!end($pathTemp)) 
       $result[] = '';
   return implode('/', $result);
}


function gen_directories(&$tpl)
{

	global $cur_dir, $cfg, $sql;
	
	if(!isset($path)){$path = '';}	
	
	$domain_name = $_SESSION['user_logged'];
	
	$homedir = $cfg['FTP_HOMEDIR'];

	$dtop = $homedir.'/'.$domain_name;
	
	if (!isset($_GET['cur_dir'])) {
			$cur_dir = '/';
	} else {
		$path = cleanPath($path);
		$_GET['cur_dir'] = eregi_replace("\.\./","",cleanPath($_GET['cur_dir']));
		$_GET['cur_dir'] = eregi_replace("\.\.","",$_GET['cur_dir']);
		$_GET['cur_dir'] = '/'.$_GET['cur_dir'];
		if ($_GET['cur_dir'] === "/") {
				$cur_dir = $_GET['cur_dir'];
			} else {
				$cur_dir = $_GET['cur_dir'].'/';
			}
	}
	
    
	$real_dir = $dtop . $cur_dir;
    
	$d = dir($real_dir);
	if(!$d->handle){
		set_page_message( tr('Can not open directory !<br>Please contact your administrator !'));
		
		return;
	}
	else {
		while (false !== ($entry = $d->read())) {
                                        
			if ($entry == '.') {
				/* */
			}
			else if ($entry == '..') {
                                        
				$top_dir1 = $cur_dir;
			
			if ($real_dir != $dtop) {
				$top_dir1 = substr($cur_dir,0,strlen($cur_dir)-1);
			}
		
			
			$up = substr($top_dir1,0,strlen($top_dir1) - strlen(strrchr($top_dir1,'/')));
			// 1st gose back/up button
			$tpl -> assign('ACTION_LINK', '');
			$tpl -> assign(
                array(
						'ACTION' => tr(''),
						'ICON' => "parent",
						'DIR_NAME' => tr('Parent Directory'),
						'LINK' => "ftp_choose_dir.php?cur_dir=$up",

					  )
				);
			
			 
			 $tpl -> parse('DIR_ITEM', '.dir_item');
			
		}
	
		else if (is_dir($real_dir.$entry)) {
			$dr = $cur_dir.$entry;
			$tfile = $real_dir.$entry.'/'.'.htaccess';
		
			if (file_exists($tfile)) {
				$image = "locked";
			}
			else {
				$image = "folder";
			}
		
			// here gose link to the dir
			 
			 $tpl -> assign(
                array(
						'ACTION' => tr('Protect it'),
						'PROTECT_IT' => "protect_it.php?file=$dr",
						'ICON' => $image,
						'DIR_NAME' => $entry,
						'CHOOSE_IT' => $dr,
						'LINK' => "ftp_choose_dir.php?cur_dir=$dr",
					  )
				);
			 $tpl -> parse('ACTION_LINK', 'action_link');
			 $tpl -> parse('DIR_ITEM', '.dir_item');
			 
			
		}
                                   
	}
	$d->close();
	}

	$d = dir($real_dir);

	if(!$d->handle){
		set_page_message( tr('Can not open directory !<br>Please contact your administrator !'));
		
		return;
	}
	else {

		while (false !== ($entry = $d->read())) {
                                        
                                            
			if ($entry == '.') {
				/* */
			}
			else if ($entry == '..') {
				/* */
			}
			else if (!is_dir($real_dir.$entry)) {

				// here gose document
				$tpl -> assign('LIST_ITEM', '');
			
				 
				 $tpl -> parse('DIR_ITEM', '.dir_item');
			}                     
                                   
	}
    $d->close();
  }


}



// functions end


$tpl -> assign(
                array(
                        'TR_CLIENT_WEBTOOLS_PAGE_TITLE' => tr('VHCS - Client/Webtools'),
                        'THEME_COLOR_PATH' => "../themes/$theme_color",
                        'THEME_CHARSET' => tr('encoding'),
						'TID' => $_SESSION['layout_id'],
                        'VHCS_LICENSE' => $cfg['VHCS_LICENSE'],
						'ISP_LOGO' => get_logo($_SESSION['user_id'])
                     )
              );


gen_directories($tpl);

			  
$tpl -> assign(
                array(
						'TR_DIRECTORY_TREE' => tr('Directory tree'),
						'TR_DIRS' => tr('Directories'),
						'TR__ACTION' => tr('Action'),
						'CHOOSE' => tr('Choose'),
						

						
					  )
				);

gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

unset_messages();
?>