<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2005 by moleSoftware		            	|
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



//
// THEME_COLOR managment stuff.
//

function get_user_gui_props(&$sql, $user_id) {

  global $cfg;

  $query = <<<SQL_QUERY
        select
            lang, layout
        from
            user_gui_props
        where
            user_id = ?
SQL_QUERY;

    $rs = exec_query($sql, $query, array($user_id));

    if($rs -> RecordCount() == 0){
        // values for user id
        // some default staff
        return array($cfg['USER_INITIAL_LANG'], $cfg['USER_INITIAL_THEME_COLOR']);

	} else if ($rs -> fields['lang'] === '' && $rs -> fields['layout'] === '') {

		return array($cfg['USER_INITIAL_LANG'], $cfg['USER_INITIAL_THEME_COLOR']);

	} else if ($rs -> fields['lang'] === '') {

		return array($cfg['USER_INITIAL_LANG'],  $rs -> fields['layout']);

	} else if ($rs -> fields['layout'] === '') {

		return array($rs -> fields['lang'], $cfg['USER_INITIAL_THEME_COLOR']);

	} else if ($rs -> fields['layout'] === 'blue' || $rs -> fields['layout'] === 'green' || $rs -> fields['layout'] === 'red' || $rs -> fields['layout'] === 'yellow') {

		return array($rs -> fields['lang'], $rs -> fields['layout']);

	}


    return array($rs -> fields['lang'], $cfg['USER_INITIAL_THEME_COLOR']);

}

if (isset($_GET['tc'])) {

    $tc = $_GET['tc'];

} else {

    if (isset($_SESSION['user_theme_color'])) {

        $tc = $_SESSION['user_theme_color'];

    } else {

        $tc = $cfg['USER_INITIAL_THEME_COLOR'];

    }
}

$_SESSION['user_theme_color'] = $tc;




if (isset($_SESSION['user_def_lang'])) {

	$tl = $_SESSION['user_def_lang'];

 } else {

 	$tl = $cfg['USER_INITIAL_LANG'];
}

    $_SESSION['user_def_lang'] = $tl;


if (isset($_SESSION['user_id'])) {

        global $sql;

	if (!isset($_SESSION['logged_from']) && !isset($_SESSION['logged_from_id']))
	{

		list($user_def_lang, $user_def_layout) = get_user_gui_props($sql, $_SESSION['user_id']);

		$_SESSION['user_theme_color'] = $user_def_layout;

		$_SESSION['user_def_lang'] = $user_def_lang;

	}

}

function gen_page_message(&$tpl) {

    if (!isset($_SESSION['user_page_message'])) {

        $tpl -> assign('PAGE_MESSAGE', '');

    } else {

        $tpl -> assign('MESSAGE', $_SESSION['user_page_message']);

        unset($_SESSION['user_page_message']);

    }

}

function check_language_exist($lang_table)
{

global $sql;

 			$tables = $sql->MetaTables();
            $nlang = count($tables);
            for($i=0 ; $i < $nlang; $i++){
                $data= $tables[$i];
                if($data == $lang_table){
                    return true;
                }
            }
			return false;

}


function set_page_message($message) {

    if (isset($_SESSION['user_page_message']))

        $_SESSION['user_page_message'] .= "<br><br>$message<br><br>";

    else

        $_SESSION['user_page_message'] = $message;

}


?>
