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
 *
 **/

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
        return array($cfg['USER_INITIAL_LANG'], $cfg['USER_INITIAL_THEME']);

	} else if ($rs -> fields['lang'] === '' && $rs -> fields['layout'] === '') {

		return array($cfg['USER_INITIAL_LANG'], $cfg['USER_INITIAL_THEME']);

	} else if ($rs -> fields['lang'] === '') {

		return array($cfg['USER_INITIAL_LANG'],  $rs -> fields['layout']);

	} else if ($rs -> fields['layout'] === '') {

		return array($rs -> fields['lang'], $cfg['USER_INITIAL_THEME']);

	} else if ($rs -> fields['layout'] === 'blue' || $rs -> fields['layout'] === 'green' || $rs -> fields['layout'] === 'red' || $rs -> fields['layout'] === 'yellow') {

		return array($rs -> fields['lang'], $rs -> fields['layout']);

	}


    return array($rs -> fields['lang'], $cfg['USER_INITIAL_THEME']);

}

if (isset($_GET['tc'])) {

    $tc = $_GET['tc'];

} else {

    if (isset($_SESSION['user_theme'])) {

        $tc = $_SESSION['user_theme'];

    } else {

        $tc = $cfg['USER_INITIAL_THEME'];
        $_SESSION['user_theme'] = $tc;

    }
}




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

		$_SESSION['user_theme'] = $user_def_layout;
		$_SESSION['user_def_lang'] = $user_def_lang;

	}

}

function gen_page_message(&$tpl) {

    if (!isset($_SESSION['user_page_message'])) {

        $tpl -> assign('PAGE_MESSAGE', '');
        $tpl -> assign('MESSAGE',      '');

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

function get_menu_vars($menu_link) {

	global $sql;

	$user_id = $_SESSION['user_id'];

	$query = <<<SQL_QUERY
        SELECT
            customer_id, fname, lname, firm, zip, city, country, email, phone, fax, street1, street2
        FROM
            admin
        WHERE
            admin_id = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($user_id));

	$menu_link = preg_replace("/\{uid\}/", $_SESSION['user_id'], $menu_link);
	$menu_link = preg_replace("/\{uname\}/", $_SESSION['user_logged'], $menu_link);
	$menu_link = preg_replace("/\{cid\}/", $rs -> fields['customer_id'], $menu_link);
	$menu_link = preg_replace("/\{fname\}/", $rs -> fields['fname'], $menu_link);
	$menu_link = preg_replace("/\{lname\}/", $rs -> fields['lname'], $menu_link);
	$menu_link = preg_replace("/\{company\}/", $rs -> fields['firm'], $menu_link);
	$menu_link = preg_replace("/\{zip\}/", $rs -> fields['zip'], $menu_link);
	$menu_link = preg_replace("/\{city\}/", $rs -> fields['city'], $menu_link);
	$menu_link = preg_replace("/\{country\}/", $rs -> fields['country'], $menu_link);
	$menu_link = preg_replace("/\{email\}/", $rs -> fields['email'], $menu_link);
	$menu_link = preg_replace("/\{phone\}/", $rs -> fields['phone'], $menu_link);
	$menu_link = preg_replace("/\{fax\}/", $rs -> fields['fax'], $menu_link);
	$menu_link = preg_replace("/\{street1\}/", $rs -> fields['street1'], $menu_link);
	$menu_link = preg_replace("/\{street2\}/", $rs -> fields['street2'], $menu_link);

	$query = <<<SQL_QUERY
        SELECT
            domain_name, domain_admin_id
        FROM
            domain
        WHERE
            domain_admin_id = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($user_id));

	$menu_link = preg_replace("/\{domain_name\}/", $rs -> fields['domain_name'], $menu_link);

	return $menu_link;

}

?>
