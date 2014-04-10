<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2014 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Admin
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/language.tpl',
		'page_message' => 'layout',
		'def_language' => 'page',
		'logged_from' => 'page'));

// Getting current admin language
$adminCurrentLanguage = $_SESSION['user_def_lang'];

if (!empty($_POST)) {
    $adminNewLanguage = clean_input($_POST['def_language']);

	if ($adminCurrentLanguage != $adminNewLanguage) {
		$query = "UPDATE `user_gui_props` SET `lang` = ? WHERE `user_id` = ?";
		exec_query($query, array($adminNewLanguage, $_SESSION['user_id']));

		$_SESSION['user_def_lang'] = $adminNewLanguage;

		set_page_message(tr('Language successfully updated.'), 'success');
	} else {
		set_page_message(tr("Nothing has been changed."), 'info');
	}

	// Force update on next load
	redirectTo('profile.php');
}

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('Admin / Profile / Language'),
		 'ISP_LOGO' => layout_getUserLogo(),
		 'TR_LANGUAGE' => tr('Language'),
		 'TR_CHOOSE_LANGUAGE' => tr('Choose your language'),
		 'TR_UPDATE' => tr('Update')));

generateNavigation($tpl);
generateLoggedFrom($tpl);
gen_def_language($tpl, $adminCurrentLanguage);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
