<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
 *
 * @license
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::getInstance()->get('CLIENT_TEMPLATE_PATH') . '/language.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('def_language', 'page');
$tpl->define_dynamic('logged_from', 'page');

/*
 *
 * page actions.
 *
 */

if (isset($_POST['uaction']) && $_POST['uaction'] === 'save_lang') {
	$user_id = $_SESSION['user_id'];
	$user_lang = $_POST['def_language'];
	$query = <<<SQL_QUERY
		UPDATE
			`user_gui_props`
		SET
			`lang` = ?
		WHERE
			`user_id` = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($user_lang, $user_id));
	unset($_SESSION['user_def_lang']);
	$_SESSION['user_def_lang'] = $user_lang;
	set_page_message(tr('User language updated successfully!'));
}

$theme_color = Config::getInstance()->get('USER_INITIAL_THEME');

// Makes sure that the language selected is the client's language
if (!isset($_SESSION['logged_from']) && !isset($_SESSION['logged_from_id'])) {
	list($user_def_lang, $user_def_layout) = get_user_gui_props($sql, $_SESSION['user_id']);
} else {
	$user_def_layout = $_SESSION['user_theme'];
	$user_def_lang = $_SESSION['user_def_lang'];
}

gen_def_language($tpl, $sql, $user_def_lang);

$tpl->assign(
	array('TR_CLIENT_LANGUAGE_TITLE' => tr('ispCP - Client/Change Language'),
		'THEME_COLOR_PATH' => "../themes/$theme_color",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id'])
	)
);

/*
 *
 * static page messages.
 *
 */

gen_client_mainmenu($tpl, Config::getInstance()->get('CLIENT_TEMPLATE_PATH') . '/main_menu_general_information.tpl');
gen_client_menu($tpl, Config::getInstance()->get('CLIENT_TEMPLATE_PATH') . '/menu_general_information.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$tpl->assign(
	array('TR_LANGUAGE' => tr('Language'),
		'TR_CHOOSE_DEFAULT_LANGUAGE' => tr('Choose default language'),
		'TR_SAVE' => tr('Save'),
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');

$tpl->prnt();

if (Config::getInstance()->get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
unset_messages();
