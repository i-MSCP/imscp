<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010-2011 by i-msCP | http://i-mscp.net
 * @link 		http://i-mscp.net
 * @author 		ispCP Team
 * @author 		i-MSCP Team
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
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2011 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

/************************************************************************************
 * Main script
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		 'page' => $cfg->CLIENT_TEMPLATE_PATH . '/language.tpl',
		 'page_message' => 'page',
		 'def_language' => 'page',
		 'logged_from' => 'page'
	)
);

if (isset($_POST['uaction']) && $_POST['uaction'] == 'save_lang') {
	$user_id = $_SESSION['user_id'];
	$user_lang = clean_input($_POST['def_language']);

    $query = "
        REPLACE INTO
            `user_gui_props` (
                user_id, lang, layout
            ) VALUES (
                ?, ?, ?
            )
    ";

    exec_query($query, array($user_id, $user_lang, $_SESSION['user_theme']));

    if(!isset($_SESSION['logged_from_id'])) {
	    unset($_SESSION['user_def_lang']);
	    $_SESSION['user_def_lang'] = $user_lang;
    }

	set_page_message(tr('Language updated.'), 'success');

	// Fix to see change on next load
	redirectTo('language.php');
}

if (isset($_SESSION['logged_from']) && isset($_SESSION['logged_from_id'])) {
	list($user_def_lang) = get_user_gui_props($_SESSION['user_id']);
} else {
	$user_def_lang = $_SESSION['user_def_lang'];
}

gen_def_language($tpl, $user_def_lang);

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('i-MSCP - Client/Change Language'),
		 'TR_TITLE_CHANGE_LANGUAGE' => tr('Change language'),
		 'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		 'THEME_CHARSET' => tr('encoding'),
		 'ISP_LOGO' => layout_getUserLogo(),
		 'TR_GENERAL_INFO' => tr('General information'),
		 'TR_LANGUAGE' => tr('Language'),
		 'TR_CHOOSE_DEFAULT_LANGUAGE' => tr('Choose your default language'),
		 'TR_CHANGE' => tr('Change')
	)
);

gen_client_mainmenu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/main_menu_general_information.tpl');
gen_client_menu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/menu_general_information.tpl');
gen_logged_from($tpl);
generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
