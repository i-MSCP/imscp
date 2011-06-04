<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2011 by i-MSCP | http://i-mscp.net
 * @version     SVN: $Id$
 * @link        http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
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
 *
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
 * This file contains view helpers functions that are responsible to generate
 * template parts for reseller interface such as the main and left menus.
 */

/**
 * Helper function to generate main menu from partial template file.
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @param  $menu_file Partial template file path
 * @return void
 */
function gen_client_mainmenu($tpl, $menu_file)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    /** @var $db iMSCP_Database */
    $db = iMSCP_Registry::get('db');

    $tpl->define_dynamic('menu', $menu_file);
    $tpl->define_dynamic('isactive_awstats', 'menu');
    $tpl->define_dynamic('isactive_domain', 'menu');
    $tpl->define_dynamic('isactive_email', 'menu');
    $tpl->define_dynamic('isactive_ftp', 'menu');
    $tpl->define_dynamic('isactive_sql', 'menu');
    $tpl->define_dynamic('isactive_support', 'menu');
    $tpl->define_dynamic('custom_buttons', 'menu');
    $tpl->assign(array(
                      'TR_MENU_GENERAL_INFORMATION' => tr('General information'),
                      'TR_MENU_CHANGE_PASSWORD' => tr('Change password'),
                      'TR_MENU_CHANGE_PERSONAL_DATA' => tr('Change personal data'),
                      'TR_MENU_MANAGE_DOMAINS' => tr('Manage domains'),
                      'TR_MENU_ADD_SUBDOMAIN' => tr('Add subdomain'),
                      'TR_MENU_MANAGE_USERS' => tr('Email and FTP accounts'),
                      'TR_MENU_ADD_MAIL_USER' => tr('Add mail user'),
                      'TR_MENU_ADD_FTP_USER' => tr('Add FTP user'),
                      'TR_MENU_MANAGE_SQL' => tr('Manage SQL'),
                      'TR_MENU_ERROR_PAGES' => tr('Error pages'),
                      'TR_MENU_ADD_SQL_DATABASE' => tr('Add SQL database'),
                      'TR_MENU_DOMAIN_STATISTICS' => tr('Domain statistics'),
                      'TR_MENU_DAILY_BACKUP' => tr('Daily backup'),
                      'TR_MENU_QUESTIONS_AND_COMMENTS' => tr('Support system'),
                      'TR_MENU_NEW_TICKET' => tr('New ticket'),
                      'TR_MENU_LOGOUT' => tr('Logout'),
                      'PHP_MY_ADMIN' => tr('PhpMyAdmin'),
                      'TR_WEBMAIL' => tr('Webmail'),
                      'TR_FILEMANAGER' => tr('Filemanager'),
                      'TR_MENU_WEBTOOLS' => tr('Webtools'),
                      'TR_HTACCESS' => tr('Protected areas'),
                      'TR_AWSTATS' => tr('Web statistics'),
                      'TR_HTACCESS_USER' => tr('Group/User management'),
                      'TR_MENU_OVERVIEW' => tr('Overview'),
                      'TR_MENU_EMAIL_ACCOUNTS' => tr('Email Accounts'),
                      'TR_MENU_FTP_ACCOUNTS' => tr('FTP Accounts'),
                      'TR_MENU_LANGUAGE' => tr('Language'),
                      'TR_MENU_CATCH_ALL_MAIL' => tr('Catch all'),
                      'TR_MENU_ADD_ALIAS' => tr('Add alias'),
                      'TR_MENU_UPDATE_HP' => tr('Update Hosting Package'),
                      'SUPPORT_SYSTEM_PATH' => $cfg->IMSCP_SUPPORT_SYSTEM_PATH,
                      'SUPPORT_SYSTEM_TARGET' => $cfg->IMSCP_SUPPORT_SYSTEM_TARGET,
                      'WEBMAIL_PATH' => $cfg->WEBMAIL_PATH,
                      'WEBMAIL_TARGET' => $cfg->WEBMAIL_TARGET,
                      'PMA_PATH' => $cfg->PMA_PATH,
                      'PMA_TARGET' => $cfg->PMA_TARGET,
                      'FILEMANAGER_PATH' => $cfg->FILEMANAGER_PATH,
                      'FILEMANAGER_TARGET' => $cfg->FILEMANAGER_TARGET,
                      'TR_MENU_ADD_DNS' => tr("Add DNS zone's record"),
                      'TR_MENU_SSL_MANAGE' => tr('Manage SSL certificate')));

    $query = "
		SELECT
			*
		FROM
			`custom_menus`
		WHERE
			`menu_level` = 'user'
		OR
			`menu_level` = 'all'
		;
	";

    $stmt = exec_query($db, $query);

    if ($stmt->recordCount() == 0) {
        $tpl->assign('CUSTOM_BUTTONS', '');
    } else {
        global $i;
        $i = 100;

        while (!$stmt->EOF) {
            $menu_name = $stmt->fields['menu_name'];
            $menu_link = get_menu_vars($stmt->fields['menu_link']);
            $menu_target = $stmt->fields['menu_target'];
            $menu_link = str_replace('{imscp_uname}', $_SESSION['user_logged'],
                                     $menu_link);

            if ($menu_target !== '') {
                $menu_target = 'target="' . tohtml($menu_target) . '"';
            }

            $tpl->assign(array(
                              'BUTTON_LINK' => tohtml($menu_link),
                              'BUTTON_NAME' => tohtml($menu_name),
                              'BUTTON_TARGET' => $menu_target,
                              'BUTTON_ID' => $i));

            $tpl->parse('CUSTOM_BUTTONS', '.custom_buttons');
            $stmt->moveNext();
            $i++;
        }
    }

    list(,,,,,,,,$dmn_mailacc_limit, $dmn_ftpacc_limit,, $dmn_sqld_limit,,,
        $dmn_als_limit,$dmn_subd_limit,,,,,,,$domain_dns
    ) = get_domain_default_props($db, $_SESSION['user_id']);

    if ($dmn_mailacc_limit == -1)
        $tpl->assign('ISACTIVE_EMAIL', '');

    if ($dmn_als_limit == -1 && $dmn_subd_limit == -1 && $domain_dns != 'yes')
        $tpl->assign('ISACTIVE_DOMAIN', '');

    if ($dmn_ftpacc_limit == -1)
        $tpl->assign('ISACTIVE_FTP', '');

    if ($dmn_sqld_limit == -1)
        $tpl->assign('ISACTIVE_SQL', '');

    $query = "
		SELECT
			`support_system`
		FROM
			`reseller_props`
		WHERE
			`reseller_id` = ?
		;
	";

    $stmt = exec_query($db, $query, $_SESSION['user_created_by']);

    if (!$cfg->IMSCP_SUPPORT_SYSTEM || $stmt->fields['support_system'] == 'no') {
        $tpl->assign('ISACTIVE_SUPPORT', '');
    }

    if ($cfg->AWSTATS_ACTIVE == 'no') {
        $tpl->assign('ISACTIVE_AWSTATS', '');
    } else {
        $tpl->assign(array(
                          'AWSTATS_PATH' => 'http://' . $_SESSION['user_logged'] . '/stats/',
                          'AWSTATS_TARGET' => '_blank'));
    }

    $tpl->parse('MAIN_MENU', 'menu');
}

/**
 * Helper function to generate left menu from partial template file.
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @param  $menu_file Partial template file path
 * @return void
 */
function gen_client_menu($tpl, $menu_file)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    /** @var $db iMSCP_Database */
    $db = iMSCP_Registry::get('db');

    $tpl->define_dynamic('menu', $menu_file);
    $tpl->define_dynamic('custom_buttons', 'menu');
    $tpl->define_dynamic('isactive_update_hp', 'menu');
    $tpl->define_dynamic('isactive_alias_menu', 'menu');
    $tpl->define_dynamic('isactive_subdomain_menu', 'menu');
    $tpl->define_dynamic('isactive_dns_menu', 'menu');
    $tpl->define_dynamic('t_software_menu', 'menu');
    $tpl->assign(array(
                      'TR_MENU_GENERAL_INFORMATION' => tr('General information'),
                      'TR_MENU_CHANGE_PASSWORD' => tr('Change password'),
                      'TR_MENU_CHANGE_PERSONAL_DATA' => tr('Change personal data'),
                      'TR_MENU_MANAGE_DOMAINS' => tr('Manage domains'),
                      'TR_MENU_ADD_SUBDOMAIN' => tr('Add subdomain'),
                      'TR_MENU_MANAGE_USERS' => tr('Email and FTP accounts'),
                      'TR_MENU_ADD_MAIL_USER' => tr('Add mail user'),
                      'TR_MENU_ADD_FTP_USER' => tr('Add FTP user'),
                      'TR_MENU_MANAGE_SQL' => tr('Manage SQL'),
                      'TR_MENU_ERROR_PAGES' => tr('Error pages'),
                      'TR_MENU_ADD_SQL_DATABASE' => tr('Add SQL database'),
                      'TR_MENU_DOMAIN_STATISTICS' => tr('Domain statistics'),
                      'TR_MENU_DAILY_BACKUP' => tr('Daily backup'),
                      'TR_MENU_QUESTIONS_AND_COMMENTS' => tr('Support system'),
                      'TR_MENU_NEW_TICKET' => tr('New ticket'),
                      'TR_MENU_LOGOUT' => tr('Logout'),
                      'PHP_MY_ADMIN' => tr('PhpMyAdmin'),
                      'TR_WEBMAIL' => tr('Webmail'),
                      'TR_FILEMANAGER' => tr('Filemanager'),
                      'TR_MENU_WEBTOOLS' => tr('Webtools'),
                      'TR_HTACCESS' => tr('Protected areas'),
                      'TR_AWSTATS' => tr('Web statistics'),
                      'TR_HTACCESS_USER' => tr('Group/User management'),
                      'TR_MENU_OVERVIEW' => tr('Overview'),
                      'TR_MENU_EMAIL_ACCOUNTS' => tr('Email Accounts'),
                      'TR_MENU_FTP_ACCOUNTS' => tr('FTP Accounts'),
                      'TR_MENU_LANGUAGE' => tr('Language'),
                      'TR_MENU_CATCH_ALL_MAIL' => tr('Catch all'),
                      'TR_MENU_ADD_ALIAS' => tr('Add alias'),
                      'TR_MENU_UPDATE_HP' => tr('Update Hosting Package'),
                      'TR_SOFTWARE_MENU' => tr('i-MSCP application installer'),
                      'SUPPORT_SYSTEM_PATH' => $cfg->IMSCP_SUPPORT_SYSTEM_PATH,
                      'SUPPORT_SYSTEM_TARGET' => $cfg->IMSCP_SUPPORT_SYSTEM_TARGET,
                      'WEBMAIL_PATH' => $cfg->WEBMAIL_PATH,
                      'WEBMAIL_TARGET' => $cfg->WEBMAIL_TARGET,
                      'PMA_PATH' => $cfg->PMA_PATH,
                      'PMA_TARGET' => $cfg->PMA_TARGET,
                      'FILEMANAGER_PATH' => $cfg->FILEMANAGER_PATH,
                      'FILEMANAGER_TARGET' => $cfg->FILEMANAGER_TARGET,
                      'VERSION' => $cfg->Version,
                      'BUILDDATE' => $cfg->BuildDate,
                      'CODENAME' => $cfg->CodeName));

    $query = "
		SELECT
			*
		FROM
			`custom_menus`
		WHERE
			`menu_level` = 'user'
		OR
			`menu_level` = 'all'
		;
	";
    $stmt = exec_query($db, $query);

    if (!$stmt->recordCount()) {
        $tpl->assign('CUSTOM_BUTTONS', '');
    } else {
        global $i;
        $i = 100;

        while (!$stmt->EOF) {
            $menu_name = $stmt->fields['menu_name'];
            $menu_link = get_menu_vars($stmt->fields['menu_link']);
            $menu_target = $stmt->fields['menu_target'];

            if ($menu_target !== '') {
                $menu_target = 'target="' . tohtml($menu_target) . '"';
            }

            $tpl->assign(array(
                              'BUTTON_LINK' => tohtml($menu_link),
                              'BUTTON_NAME' => tohtml($menu_name),
                              'BUTTON_TARGET' => $menu_target,
                              'BUTTON_ID' => $i));

            $tpl->parse('CUSTOM_BUTTONS', '.custom_buttons');
            $stmt->moveNext();
            $i++;
        }
    }

    $query = "SELECT `support_system` FROM `reseller_props` WHERE `reseller_id` = ?;";
    $stmt = exec_query($db, $query, $_SESSION['user_created_by']);

    if (!$cfg->IMSCP_SUPPORT_SYSTEM || $stmt->fields['support_system'] == 'no') {
        $tpl->assign('SUPPORT_SYSTEM', '');
    }

    list($dmn_id,,,,,,,,$dmn_mailacc_limit,,,,,,$dmn_als_limit,$dmn_subd_limit,,,,,,,
        $dmn_dns) = get_domain_default_props($db, $_SESSION['user_id']);

    if ($dmn_mailacc_limit == -1) {
        $tpl->assign('ACTIVE_EMAIL', '');
    }

    if ($dmn_als_limit == -1) {
        $tpl->assign(array(
                          'ISACTIVE_ALIAS_MENU' => '',
                          'ALIAS_ADD' => ''));
    }

    if ($dmn_subd_limit == -1) {
        $tpl->assign(array(
                          'ISACTIVE_SUBDOMAIN_MENU' => '',
                          'SUBDOMAIN_ADD' => ''));
    }

    if ($dmn_dns != 'yes') {
        $tpl->assign(array(
                          'ISACTIVE_DNS_MENU' => '',
                          'ISACTIVE_DNS' => ''));
    }

    $sub_cnt = get_domain_running_sub_cnt($db, $dmn_id);

    if ($dmn_subd_limit != 0 && $sub_cnt >= $dmn_subd_limit) {
        $tpl->assign('ISACTIVE_SUBDOMAIN_MENU', '');
    }

    $als_cnt = get_domain_running_als_cnt($db, $dmn_id);

    if ($dmn_als_limit != 0 && $als_cnt >= $dmn_als_limit) {
        $tpl->assign('ISACTIVE_ALIAS_MENU', '');
    }

    if ($cfg->AWSTATS_ACTIVE != 'yes') {
        $tpl->assign('ACTIVE_AWSTATS', '');
    } else {
        $tpl->assign(array(
                          'AWSTATS_PATH' => 'http://' . $_SESSION['user_logged'] . '/stats/',
                          'AWSTATS_TARGET' => '_blank'));
    }

    // Hide 'Update Hosting Package'-Button, if there are none
    $query = "
		SELECT
			`id`
		FROM
			`hosting_plans`
		WHERE
			`reseller_id` = ?
		AND
			`status` = '1'
		;
	";
    $stmt = exec_query($db, $query, $_SESSION['user_created_by']);

    if (!$stmt->recordCount() ) {
        if ($cfg->HOSTING_PLANS_LEVEL != 'admin') {
            $tpl->assign('ISACTIVE_UPDATE_HP', '');
        }
    }

    $query = "
		SELECT
			`domain_software_allowed`, `domain_ftpacc_limit`
		FROM
			`domain`
		WHERE
			`domain_admin_id` = ?
		;
	";
    $stmt = exec_query($db, $query, array($_SESSION['user_id']));

    if ($stmt->fields('domain_software_allowed') == 'yes'
        && $stmt->fields('domain_ftpacc_limit') != '-1'
    ) {
        $tpl->assign(array('SOFTWARE_MENU' => tr('yes')));
        $tpl->parse('T_SOFTWARE_MENU', '.t_software_menu');
    } else {
        $tpl->assign('T_SOFTWARE_MENU', '');
    }

    $tpl->parse('MENU', 'menu');
}


