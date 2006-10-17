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

include 'include/vhcs-lib.php';


if ($cfg['LOSTPASSWORD'] != 1) {
	system_message(tr('Lost password function currently disabled'));
	die();
}

// check for gd >= 2.x
if (check_gd() == false) system_message("ERROR: php-extension 'gd' not loaded !");

if (captcha_fontfile_exists() == false) system_message("ERROR: captcha fontfile not found !");

// remove old uniqkeys
removeOldKeys($cfg['LOSTPASSWORD_TIMEOUT']);

if (isset($_SESSION['user_theme_color'])) {

	$theme_color = $cfg['USER_INITIAL_THEME'];

} else {

	$theme_color = $cfg['USER_INITIAL_THEME_COLOR'];

}

if (isset($_GET['key'])) {
	if ($_GET['key'] != "") {

		check_input($_GET['key']);

		$tpl = new pTemplate();
		$tpl -> define('page', $cfg['LOGIN_TEMPLATE_PATH'].'/lostpassword_message.tpl');
		$tpl -> assign(array(
							'TR_MAIN_INDEX_PAGE_TITLE' => tr('VHCS - Virtual Hosting Control System'),
							'THEME_COLOR_PATH' => "themes/$theme_color",
							'THEME_CHARSET' => tr('encoding'),
							'TR_TIME' => gettimestr(),
							'TR_DATE' => getdatestr()
							)
						);

		if (sendpassword($_GET['key'])) {
			$tpl -> assign(array(
								'TR_MESSAGE' => tr('Password send'),
								'TR_LINK' => "<a class=\"submenu\" href=\"index.php\">".tr('Login')."</a>"
								)
							);

		} else {
			$tpl -> assign(array(
								'TR_MESSAGE' => tr('ERROR: Password not send'),
								'TR_LINK' => "<a class=\"submenu\" href=\"index.php\">".tr('Login')."</a>"
								)
							);
		}
		$tpl -> parse('PAGE', 'page');

		$tpl -> prnt();

		if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();
		exit(0);
	}
}

if (isset($_POST['uname'])) {
	if (($_POST['uname'] != "") AND isset($_SESSION['image']) AND isset($_POST['capcode'])) {

		check_input($_POST['uname']);

		check_input($_POST['capcode']);

		$tpl = new pTemplate();
		$tpl -> define('page', $cfg['LOGIN_TEMPLATE_PATH'].'/lostpassword_message.tpl');
		$tpl -> assign(array(
							'TR_MAIN_INDEX_PAGE_TITLE' => tr('VHCS - Virtual Hosting Control System'),
							'THEME_COLOR_PATH' => "themes/$theme_color",
							'THEME_CHARSET' => tr('encoding'),
							'TR_TIME' => gettimestr(),
							'TR_DATE' => getdatestr()
							)
						);

		if ($_SESSION['image'] == $_POST['capcode']) {
			if (requestpassword($_POST['uname'])) {
				$tpl -> assign(array(
									'TR_MESSAGE' => tr('The password was requested'),
									'TR_LINK' => "<a class=\"submenu\" href=\"index.php\">".tr('Login')."</a>"
									)
								);
			} else {
				$tpl -> assign(array(
									'TR_MESSAGE' => tr('ERROR: Unknown user'),
									'TR_LINK' => "<a class=\"submenu\" href=\"index.php\">".tr('Retry')."</a>"
									)
								);
			}
		} else {
			$tpl -> assign(array(
								'TR_MESSAGE' => tr('ERROR: Security code was not correct!').' '. $_SESSION['image'],
								'TR_LINK' => "<a class=\"submenu\" href=\"index.php\">".tr('Retry')."</a>"
								)
							);
		}
		$tpl -> parse('PAGE', 'page');

		$tpl -> prnt();

		if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();
		exit(0);
	}
}



$tpl = new pTemplate();

$tpl -> define('page', $cfg['LOGIN_TEMPLATE_PATH'].'/lostpassword.tpl');

$tpl -> assign(
                array(
					'TR_MAIN_INDEX_PAGE_TITLE' => tr('VHCS - Virtual Hosting Control System'),
					'THEME_COLOR_PATH' => $cfg['LOGIN_TEMPLATE_PATH'],
					'THEME_CHARSET' => tr('encoding'),
					'TR_CAPCODE' => tr('Security code'),
					'TR_IMGCAPCODE_DESCRIPTION' => tr('(To avoid abuse, we ask you to write the combination of letters on the above picture into the field "Security code")'),
					'TR_IMGCAPCODE' => "<img src=\"include/imagecode.php\" border=\"0\" nosave alt=\"\">",
					'TR_USERNAME' => tr('Username'),
					'TR_SEND' => tr('Request password'),
					'TR_BACK' => tr('Back'),
					'TR_TIME' => gettimestr(),
					'TR_DATE' => getdatestr()
					)
				);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

?>
