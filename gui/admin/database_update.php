<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @version		SVN: $Id$
 * @link		http://isp-control.net
 * @author		ispCP Team
 *
 * @license
 *   This program is free software; you can redistribute it and/or
 *   modify it under the terms of the GPL General Public License
 *   as published by the Free Software Foundation; either version 2.0
 *   of the License, or (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *   GPL General Public License for more details.
 *
 *   You may have received a copy of the GPL General Public License
 *   along with this program.
 *
 *   An on-line copy of the GPL General Public License can be found
 *   http://www.fsf.org/licensing/licenses/gpl.txt
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::get('ADMIN_TEMPLATE_PATH') . '/database_update.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('hosting_plans', 'page');
$tpl->define_dynamic('database_update_message', 'page');
$tpl->define_dynamic('database_update_infos', 'page');

$tpl->assign(
	array(
		'TR_ADMIN_ISPCP_UPDATES_PAGE_TITLE'	=> tr('ispCP - Virtual Hosting Control System'),
		'THEME_COLOR_PATH'					=> "../themes/" . Config::get('USER_INITIAL_THEME'),
		'THEME_CHARSET'						=> tr('encoding'),
		'ISP_LOGO'							=> get_logo($_SESSION['user_id'])
	)
);

// $execute is false per default
$execute = false;

// If the post variable execute is set to true, $execute
// will be set to true, too
if (!empty($_POST['execute']) && $_POST['execute'])
	$execute = true;

gen_admin_mainmenu($tpl, Config::get('ADMIN_TEMPLATE_PATH') . '/main_menu_system_tools.tpl');
gen_admin_menu($tpl, Config::get('ADMIN_TEMPLATE_PATH') . '/menu_system_tools.tpl');
gen_page_message($tpl);

$tpl->assign(
	array(
		'TR_UPDATES_TITLE'		=> tr('Database updates'),
		'TR_AVAILABLE_UPDATES'	=> tr('Available database updates'),
		'TR_UPDATE'				=> tr('Update'),
		'TR_INFOS'				=> tr('Update details')
	)
);

if (databaseUpdate::getInstance()->checkUpdateExists()) {
	$tpl->assign(
		array(
			'UPDATE_MESSAGE'			=> '',
			'DATABASE_UPDATE_MESSAGE'	=> '',
			'UPDATE'					=> tr('New Database update is now available'),
			'INFOS'						=> tr('Do you want to execute the Updates now?'),
			'TR_EXECUTE_UPDATE'			=> tr('Execute updates')
		)
	);
	$tpl->parse('DATABASE_UPDATE_INFOS', 'database_update_infos');
} else {
	$tpl->assign(
		array(
			'TR_UPDATE_MESSAGE'		=> tr('No database updates available'),
			'DATABASE_UPDATE_INFOS'	=> ''
		)
	);
	$tpl->parse('DATABASE_UPDATE_MESSAGE', 'database_update_message');
}

// Execute all available db updates and redirect back to database_update.php
if ($execute) {
	databaseUpdate::getInstance()->executeUpdates();
	if (databaseUpdate::getInstance()->getErrorMessage() != "") {
		system_message(databaseUpdate::getInstance()->getErrorMessage());
	}
	header('Location:' . $_SERVER['PHP_SELF']);
}

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
