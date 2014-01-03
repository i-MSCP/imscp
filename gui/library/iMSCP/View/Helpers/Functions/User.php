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
 *
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2014 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @link        http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 */

/**
 * Helper function to generate main menu from partial template file.
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @param  string $menuTemplateFile Partial template file path
 * @return void
 */
function gen_client_mainmenu($tpl, $menuTemplateFile)
{
	$tpl->define_dynamic(
		array(
			'main_menu' => $menuTemplateFile,
			'domain_feature' => 'main_menu',
			'ftp_feature' => 'main_menu',
			'sql_feature' => 'main_menu',
			'mail_feature' => 'main_menu',
			'support_feature' => 'main_menu',
			'custom_buttons_feature' => 'main_menu'
		)
	);

	$tpl->assign(
		array(
			'TR_MENU_GENERAL_INFORMATION' => tr('General information'),
			'TR_MENU_STATISTICS' => tr('Statistics'),
			'TR_MENU_WEBTOOLS' => tr('Webtools'),

			'TR_MENU_LOGOUT' => tr('Logout')
		)
	);

	// Per feature menu -- begin

	if (customerHasFeature('domain')) {
		$tpl->assign('TR_MENU_MANAGE_DOMAINS', tr('Manage Domains'));
	} else {
		$tpl->assign('DOMAIN_FEATURE', '');
	}

	if (customerHasFeature('ftp')) {
		$tpl->assign('TR_MENU_FTP_ACCOUNTS', tr('Ftp accounts'));
	} else {
		$tpl->assign('FTP_FEATURE', '');
	}

	if (customerHasFeature('sql')) {
		$tpl->assign('TR_MENU_MANAGE_SQL', tr('Manage SQL'));
	} else {
		$tpl->assign('SQL_FEATURE', '');
	}

	if (customerHasFeature('mail')) {
		$tpl->assign('TR_MENU_MAIL_ACCOUNTS', 'Email accounts');
	} else {
		$tpl->assign('MAIL_FEATURE', '');
	}

	if (customerHasFeature('support')) {
		$tpl->assign('TR_MENU_SUPPORT', tr('Support'));
	} else {
		$tpl->assign('SUPPORT_FEATURE', '');
	}

	// Per feature menu -- end

	// Custom menus feature - begin

	$query = "SELECT * FROM `custom_menus` WHERE `menu_level` = ? OR `menu_level` = ?";
	$stmt = exec_query($query, array('user', 'all'));

	if (!$stmt->rowCount()) {
		$tpl->assign('CUSTOM_BUTTONS_FEATURE', '');
	} else {
		while (!$stmt->EOF) {
			$customMenuTarget = $stmt->fields['menu_target'];

			if ($customMenuTarget !== '') {
				$customMenuTarget = 'target="' . tohtml($customMenuTarget) . '"';
			}

			$tpl->assign(
				array(
					'BUTTON_LINK' => tohtml($stmt->fields['menu_link']),
					'BUTTON_NAME' => tohtml($stmt->fields['menu_name']),
					'BUTTON_TARGET' => $customMenuTarget
				)
			);

			$tpl->parse('CUSTOM_BUTTONS_FEATURE', '.custom_buttons_feature');
			$stmt->moveNext();
		}
	}
	// Custom menus feature - end

	$tpl->parse('MAIN_MENU', 'main_menu');
}

/**
 * Helper function to generate client left menu from partial template file.
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @param  string $menuTemplateFile menu partial template file
 * @return void
 */
function gen_client_menu($tpl, $menuTemplateFile)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$tpl->define_dynamic(
		array(
			'menu' => $menuTemplateFile,
			'subdomains_feature' => 'menu',
			'domain_aliases_features' => 'menu',
			'ftp_feature' => 'menu',
			'mail_feature' => 'menu',
			'sql_feature' => 'menu',
			'php_directives_editor_feature' => 'menu',
			'awstats_feature' => 'menu',
			'protected_areas_feature' => 'menu',
			'aps_feature' => 'menu',
			'support_system_feature' => 'menu',
			'backup_feature' => 'menu',
			'custom_dns_records_feature' => 'menu',
			'update_hosting_plan_feature' => 'menu'
		)
	);

	$tpl->assign(
		array(
			'TR_LMENU_OVERVIEW' => tr('Overview'),
			'TR_LMENU_CHANGE_PASSWORD' => tr('Password'),
			'TR_LMENU_CHANGE_PERSONAL_DATA' => tr('Personal data'),
			'TR_LMENU_LANGUAGE' => tr('Language'),
			'TR_LMENU_LAYOUT' => tr('Layout'),
			'TR_LMENU_UPDATE_HOSTING_PLAN' => tr('Update hosting plan'),
			// Todo move these entries tha don't really belong to the menu
			'VERSION' => tohtml($cfg->Version),
			'BUILDDATE' => tohtml($cfg->BuildDate),
			'CODENAME' => tohtml($cfg->CodeName)
		)
	);

	// Per feature left menu -- begin

	if (customerHasFeature('subdomains')) {
		$tpl->assign('TR_LMENU_ADD_SUBDOMAIN', tr('Add subdomain'));
	} else {
		$tpl->assign('SUBDOMAINS_FEATURE', '');
	}

	if (customerHasFeature('domain_aliases')) {
		$tpl->assign('TR_LMENU_ADD_DOMAIN_ALIAS', tr('Add domain alias'));
	} else {
		$tpl->assign('DOMAIN_ALIASES_FEATURE', '');
	}

	if (customerHasFeature('ftp')) {
		$tpl->assign(
			array(
				'TR_LMENU_ADD_FTP_USER' => tr('Add FTP user'),
				'TR_LMENU_FILEMANAGER' => tr('FileManager'),
				'TR_LMENU_FTP_ACCOUNTS' => tr('FTP accounts'),
				'FILEMANAGER_PATH' => $cfg->FILEMANAGER_PATH,
				'FILEMANAGER_TARGET' => $cfg->FILEMANAGER_TARGET
			)
		);
	} else {
		$tpl->assign('FTP_FEATURE', '');
	}

	if (customerHasFeature('mail')) {
		$tpl->assign(
			array(
				'TR_LMENU_EMAIL_ACCOUNTS' => tr('Email accounts'),
				'TR_LMENU_ADD_MAIL_USER' => tr('Add email user'),
				'TR_LMENU_MAIL_CATCH_ALL' => tr('Catch all'),
				'TR_LMENU_WEBMAIL' => tr('Webmail'),
				'WEBMAIL_PATH' => $cfg->WEBMAIL_PATH,
				'WEBMAIL_TARGET' => $cfg->WEBMAIL_TARGET
			)
		);
	} else {
		$tpl->assign('MAIL_FEATURE', '');
	}

	if (customerHasFeature('sql')) {
		$tpl->assign(
			array(
				'TR_LMENU_ADD_SQL_DATABASE' => tr('Add SQL database'),
				'TR_LMENU_PMA' => tr('PhpMyAdmin'),
				'PMA_PATH' => $cfg->PMA_PATH,
				'PMA_TARGET' => $cfg->PMA_TARGET
			)
		);
	} else {
		$tpl->assign('SQL_FEATURE', '');
	}

	if (customerHasFeature('custom_dns_records')) {
		$tpl->assign('TR_LMENU_ADD_CUSTOM_DNS_RECORD', tr('Add custom DNS record'));
	} else {
		$tpl->assign('CUSTOM_DNS_RECORDS_FEATURE', '');
	}

	if (customerHasFeature('php_editor')) {
		$tpl->assign('TR_LMENU_PHP_DIRECTIVES_EDITOR', tr('PHP Editor'));
	} else {
		$tpl->assign('PHP_DIRECTIVES_EDITOR_FEATURE', '');
	}

	// Getting domain properties
	$domainProperties = get_domain_default_props($_SESSION['user_id']);

	if (customerHasFeature('awstats')) {
		$tpl->assign(
			array(
				'TR_LMENU_WEBSTATS' => tr('Web statistics'),
				'WEBSTATS_RPATH' => 'http://' . decode_idna($domainProperties['domain_name']) . '/' . $cfg->WEBSTATS_RPATH,
				'WEBSTATS_TARGET' => $cfg->WEBSTATS_TARGET
			)
		);
	} else {
		$tpl->assign('WEBSTATS_FEATURE', '');
	}

	if (customerHasFeature('backup')) {
		$tpl->assign('TR_LMENU_DAILY_BACKUP', tr('Daily backup'));
	} else {
		$tpl->assign('BACKUP_FEATURE', '');
	}

	// Protected areas feature is available? (Always yes for now)
	// TODO add on|off option for protected areas
	$tpl->assign('TR_LMENU_HTACCESS', tr('Protected areas'));

	// Custom error pages feature is available? (Always yes for now)
	// TODO add on|off option for custom error pages feature
	$tpl->assign('TR_LMENU_CUSTOM_ERROR_PAGES', tr('Custom error pages'));

	if (customerHasFeature('aps')) {
		$tpl->assign('TR_LMENU_APS', tr('Application installer'));
	} else {
		$tpl->assign('APS_FEATURE', '');
	}

	if (customerHasFeature('support')) {
		$tpl->assign(
			array(
				'TR_LMENU_OPEN_TICKETS' => tr('Open tickets'),
				'TR_LMENU_CLOSED_TICKETS' => tr('Closed tickets'),
				'TR_LMENU_NEW_TICKET' => tr('New ticket'),
				'SUPPORT_SYSTEM_PATH' => $cfg->IMSCP_SUPPORT_SYSTEM_PATH,
				'SUPPORT_SYSTEM_TARGET' => $cfg->IMSCP_SUPPORT_SYSTEM_TARGET
			)
		);
	} else {
		$tpl->assign('SUPPORT_SYSTEM_FEATURE', '');
	}

	// Update hosting plan is available?
	// Yes if hosting plan are managed by reseller and a least one hosting plan is
	// available for update
	if ($cfg->HOSTING_PLANS_LEVEL != 'admin') {
		$query = "SELECT COUNT(`id`)`cnt` FROM `hosting_plans` WHERE `reseller_id` = ? AND `status` = '1'";
		$stmt = exec_query($query, $_SESSION['user_created_by']);

		if ($stmt->fields['cnt'] > 0) {
			$tpl->assign('TR_LMENU_UPDATE_HOSTING_PLAN', tr('Update hosting plan'));
		} else {
			$tpl->assign('UPDATE_HOSTING_PLAN_FEATURE', '');
		}
	} else {
		$tpl->assign('UPDATE_HOSTING_PLAN_FEATURE', '');
	}

	// Per feature left menu -- End

	$tpl->parse('MENU', 'menu');

	return;
}
