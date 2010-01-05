<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $ID$
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

include '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::get('CLIENT_TEMPLATE_PATH') . '/webtools.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('active_awstats', 'page');
$tpl->define_dynamic('active_email', 'page');
$tpl->define_dynamic('logged_from', 'page');

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_CLIENT_WEBTOOLS_PAGE_TITLE' => tr('ISPCP - Client/Webtools'),
		'THEME_COLOR_PATH' => "../themes/$theme_color",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id'])
	)
);

// Check, if e-mail is active for this user

// Check, if e-mail is active for this user
list(
		$dmn_id, $dmn_name, $dmn_gid, $dmn_uid, $dmn_created_id, $dmn_created, $dmn_last_modified,
		$dmn_mailacc_limit, $dmn_ftpacc_limit, $dmn_traff_limit, $dmn_sqld_limit, $dmn_sqlu_limit,
		$dmn_status, $dmn_als_limit, $dmn_subd_limit, $dmn_ip_id, $dmn_disk_limit, $dmn_disk_usage,
		$dmn_php, $dmn_cgi, $backup
) = get_domain_default_props($sql, $_SESSION['user_id']);

if ($dmn_mailacc_limit == -1) {
	$tpl->assign('ACTIVE_EMAIL', '');
}

if($backup == 'no') {
	$tpl->assign('ACTIVE_BACKUP', '');
}

/*
 *
 * static page messages.
 *
 */

gen_client_mainmenu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/main_menu_webtools.tpl');
gen_client_menu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/menu_webtools.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$tpl->assign(
	array(
		'TR_WEBTOOLS' => tr('Webtools'),
		'TR_BACKUP' => tr('Backup'),
		'TR_ERROR_PAGES' => tr('Error pages'),
		'TR_ERROR_PAGES_TEXT' => tr('Customize error pages for your domain'),
		'TR_BACKUP_TEXT' => tr('Backup and restore settings'),
		'TR_WEBMAIL_TEXT' => tr('Access your mail through the web interface'),
		'TR_FILEMANAGER_TEXT' => tr('Access your files through the web interface'),
		'TR_AWSTATS_TEXT' => tr('Access your Awstats statistics'),
		'TR_HTACCESS_TEXT' => tr('Manage protected areas, users and groups')
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
unset_messages();
