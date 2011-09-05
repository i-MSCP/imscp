<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010 by i-msCP | http://i-mscp.net
 * @version 	SVN: $Id$
 * @link 		http://i-mscp.net
 * @author 		ispCP Team
 * @author		i-MSCP Team
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

require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page', $cfg->RESELLER_TEMPLATE_PATH . '/domain_edit.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('ip_entry', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('subdomain_edit', 'page');
$tpl->define_dynamic('alias_edit', 'page');
$tpl->define_dynamic('mail_edit', 'page');
$tpl->define_dynamic('ftp_edit', 'page');
$tpl->define_dynamic('sql_db_edit', 'page');
$tpl->define_dynamic('sql_user_edit', 'page');
$tpl->define_dynamic('t_software_support', 'page');
$tpl->define_dynamic('t_phpini_system', 'page');
$tpl->define_dynamic('t_phpini_register_globals', 'page');
$tpl->define_dynamic('t_phpini_allow_url_fopen', 'page');
$tpl->define_dynamic('t_phpini_display_errors', 'page');
$tpl->define_dynamic('t_phpini_disable_functions', 'page');

if (isset($cfg->HOSTING_PLANS_LEVEL)
	&& $cfg->HOSTING_PLANS_LEVEL === 'admin') {
	redirectTo('users.php?psi=last');
}

$tpl->assign(
	array(
		'TR_EDIT_DOMAIN_PAGE_TITLE'	=> tr('i-MSCP - Domain/Edit'),
		'THEME_COLOR_PATH'			=> "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET'				=> tr('encoding'),
		'ISP_LOGO'					=> layout_getUserLogo()
	)
);

/**
 * static page messages.
 */
$tpl->assign(
	array(
		'TR_EDIT_DOMAIN'		=> tr('Edit Domain'),
		'TR_DOMAIN_PROPERTIES'	=> tr('Domain properties'),
		'TR_DOMAIN_NAME'		=> tr('Domain name'),
		'TR_DOMAIN_EXPIRE'		=> tr('Domain expire'),
		'TR_DOMAIN_NEW_EXPIRE'	=> tr('New expire date'),
		'TR_DOMAIN_IP'			=> tr('Domain IP'),
		'TR_PHP_SUPP'			=> tr('PHP support'),
		'TR_CGI_SUPP'			=> tr('CGI support'),
		'TR_DNS_SUPP'			=> tr('Manual DNS support (EXPERIMENTAL)'),
		'TR_SUBDOMAINS'			=> tr('Max subdomains<br /><i>(-1 disabled, 0 unlimited)</i>'),
		'TR_ALIAS'				=> tr('Max aliases<br /><i>(-1 disabled, 0 unlimited)</i>'),
		'TR_MAIL_ACCOUNT'		=> tr('Mail accounts limit <br /><i>(-1 disabled, 0 unlimited)</i>'),
		'TR_FTP_ACCOUNTS'		=> tr('FTP accounts limit <br /><i>(-1 disabled, 0 unlimited)</i>'),
		'TR_SQL_DB'				=> tr('SQL databases limit <br /><i>(-1 disabled, 0 unlimited)</i>'),
		'TR_SQL_USERS'			=> tr('SQL users limit <br /><i>(-1 disabled, 0 unlimited)</i>'),
		'TR_TRAFFIC'			=> tr('Traffic limit [MB] <br /><i>(0 unlimited)</i>'),
		'TR_DISK'				=> tr('Disk limit [MB] <br /><i>(0 unlimited)</i>'),
		'TR_USER_NAME'			=> tr('Username'),
		'TR_BACKUP'				=> tr('Backup'),
		'TR_BACKUP_DOMAIN'		=> tr('Domain'),
		'TR_BACKUP_SQL'			=> tr('SQL'),
		'TR_BACKUP_FULL'		=> tr('Full'),
		'TR_BACKUP_NO'			=> tr('No'),
		'TR_UPDATE_DATA'		=> tr('Submit changes'),
		'TR_CANCEL'				=> tr('Cancel'),
		'TR_YES'				=> tr('Yes'),
		'TR_NO'					=> tr('No'),
		'TR_EXPIRE_CHECKBOX'		=> tr('or Check for <strong>never Expire</strong>'),
		'TR_DMN_EXP_HELP'		=> tr("In case 'Domain expire' is 'N/A', the expiration date will be set from today."),
		'TR_PHPINI_SYSTEM'		=> tr('Custom php.ini'),
                'TR_PHPINI_ALLOW_URL_FOPEN' 	=> tr('allow_url_fopen'),
                'TR_PHPINI_REGISTER_GLOBALS' 	=> tr('register_globals'),
                'TR_PHPINI_DISPLAY_ERRORS' 	=> tr('display_errors'),
                'TR_PHPINI_ERROR_REPORTING' 	=> tr('error_reporting'),
                'TR_PHPINI_ER_OFF' 		=> tr('All off'),
                'TR_PHPINI_ER_EALL_EXCEPT_NOTICE_EXCEPT_WARN' 	=> tr('All errors except notices and warnings'),
                'TR_PHPINI_ER_EALL_EXCEPT_NOTICE' 		=> tr('All errors except notices'),
                'TR_PHPINI_ER_EALL' 		=> tr('All errors'),
                'TR_PHPINI_POST_MAX_SIZE' 	=> tr('post_max_size [MB]'),
                'TR_PHPINI_UPLOAD_MAX_FILESIZE' => tr('upload_max_filesize [MB]'),
                'TR_PHPINI_MAX_EXECUTION_TIME' 	=> tr('max_execution_time [sec]'),
                'TR_PHPINI_MAX_INPUT_TIME' 	=> tr('max_input_time [sec]'),
                'TR_PHPINI_MEMORY_LIMIT' 	=> tr('memory_limit [MB]'),
                'TR_PHPINI_DISABLE_FUNCTIONS' 	=> tr('disable_functions'),
                'TR_ENABLED' 			=> tr('Enabled'),
                'TR_DISABLED' 			=> tr('Disabled')
	)
);

gen_reseller_mainmenu($tpl, $cfg->RESELLER_TEMPLATE_PATH . '/main_menu_users_manage.tpl');
gen_reseller_menu($tpl, $cfg->RESELLER_TEMPLATE_PATH . '/menu_users_manage.tpl');
get_reseller_software_permission($tpl, $_SESSION['user_id']);
gen_logged_from($tpl);

if (isset($_POST['uaction']) && ('sub_data' === $_POST['uaction'])) {
	// Process data
	if (isset($_SESSION['edit_id'])) {
		$editid = $_SESSION['edit_id'];
	} else {
		unset($_SESSION['edit_id']);
		$_SESSION['edit'] = '_no_';

		redirectTo('users.php?psi=last');
	}

	if (check_user_data($tpl, $_SESSION['user_id'], $editid)) { // Save data to db
		$_SESSION['dedit'] = "_yes_";
		redirectTo('users.php?psi=last');
	}
	load_additional_data($_SESSION['user_id'], $editid);
} else {
	// Get user id that comes for edit
	if (isset($_GET['edit_id'])) {
		$editid = $_GET['edit_id'];
	}

	load_user_data($_SESSION['user_id'], $editid);

	$_SESSION['edit_id'] = $editid;
	$tpl->assign('MESSAGE', "");
}
gen_editdomain_page($tpl);
generatePageMessage($tpl); // Fix - old position was to early to generate erorr message from check_user_data()

// Begin function block

/**
 * Load domain properties.
 *
 * @param  $user_id
 * @param  $domain_id
 * @return void
 */
function load_user_data($user_id, $domain_id) {

	global $sub, $als, $mail, $ftp, $sql_db, $sql_user, $traff, $disk, $software_supp;
        global $phpiniSystem, $phpiniRegisterGlobals, $phpiniAllowUrlFopen, $phpiniDisplayErrors, $phpiniErrorReporting,
        $phpiniDisableFunctions, $phpiniPostMaxSize, $phpiniUploadMaxFileSize, $phpiniMaxExecutionTime, $phpiniMaxInputTime,
        $phpMemoryLimit;

	$query = "
		SELECT
			`domain_id`
		FROM
			`domain`
		WHERE
			`domain_id` = ?
		AND
			`domain_created_id` = ?
	";

	$rs = exec_query($query, array($domain_id, $user_id));

	if ($rs->recordCount() == 0) {
		set_page_message(tr('User does not exist or you do not have permission to access this interface!'));

		redirectTo('users.php?psi=last');
	}

	list(,$sub,,$als,,$mail,,$ftp,,$sql_db,,$sql_user,$traff,$disk) = generate_user_props($domain_id);
        if ($phpiniData = get_custom_phpini_data($domain_id)) { //Get row from table php_ini - if no row than theres no custom php.ini till now
                $phpiniSystem = 'yes'; //if row than custom php ini yes
                $phpiniRegisterGlobals = $phpiniData->fields('register_globals');
                $phpiniAllowUrlFopen = $phpiniData->fields('allow_url_fopen');
                $phpiniDisplayErrors = $phpiniData->fields('display_errors');
                $phpiniErrorReporting = $phpiniData->fields('error_reporting');
                $phpiniDisableFunctions = $phpiniData->fields('disable_functions');
                $phpiniPostMaxSize = $phpiniData->fields('post_max_size');
                $phpiniUploadMaxFileSize = $phpiniData->fields('upload_max_filesize');
                $phpiniMaxExecutionTime = $phpiniData->fields('max_execution_time');
                $phpiniMaxInputTime = $phpiniData->fields('max_input_time');
                $phpMemoryLimit = $phpiniData->fields('memory_limit');
        } else {
                $phpiniDefaultData = get_default_phpini_data(); //Get the default php ini values from config table
                $phpiniSystem = 'no';
                $phpiniRegisterGlobals = $phpiniDefaultData['phpiniRegisterGlobals'];
                $phpiniAllowUrlFopen = $phpiniDefaultData['phpiniAllowUrlFopen'];
                $phpiniDisplayErrors = $phpiniDefaultData['phpiniDisplayErrors'];
                $phpiniErrorReporting = $phpiniDefaultData['phpiniErrorReporting'];
                $phpiniDisableFunctions =  $phpiniDefaultData['phpiniDisableFunctions'];
                $phpiniPostMaxSize = $phpiniDefaultData['phpiniPostMaxSize'];
                $phpiniUploadMaxFileSize = $phpiniDefaultData['phpiniUploadMaxFilesize'];
                $phpiniMaxExecutionTime = $phpiniDefaultData['phpiniMaxExecutionTime'];
                $phpiniMaxInputTime = $phpiniDefaultData['phpiniMaxInputTime'];
                $phpMemoryLimit = $phpiniDefaultData['phpiniMemoryLimit'];
        }

	load_additional_data($user_id, $domain_id);
}


/**
 * Load additional domain properties.
 *
 * @param  $user_id
 * @param  $domain_id
 * @return void
 */
function load_additional_data($user_id, $domain_id) {

	global $domain_name, $domain_expires, $domain_ip, $php_sup, $cgi_supp, $username, $allowbackup, $dns_supp,
	$domain_expires_date, $software_supp;

    /** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	// Get domain data
	$query = "
		SELECT
			`domain_name`, `domain_expires`, `domain_ip_id`, `domain_php`, `domain_cgi`, `domain_admin_id`,
			`allowbackup`, `domain_dns`, `domain_software_allowed`
		FROM
			`domain`
		WHERE
			`domain_id` = ?
	";

	$res = exec_query($query, $domain_id);
	$data = $res->fetchRow();

	$domain_name = $data['domain_name'];

	$domain_expires = $data['domain_expires'];
	$_SESSION['domain_expires'] = $domain_expires;

	if ($domain_expires == 0) {
		$domain_expires = tr('N/A');
		$domain_expires_date = '0';
	} else {
		$date_formt = $cfg->DATE_FORMAT;
        $domain_expires_date = date("m/d/Y", $domain_expires);
        $domain_expires = date($date_formt, $domain_expires);
    }

	$domain_ip_id		= $data['domain_ip_id'];
	$php_sup			= $data['domain_php'];
	$cgi_supp			= $data['domain_cgi'];
	$allowbackup		= $data['allowbackup'];
	$domain_admin_id	= $data['domain_admin_id'];
	$dns_supp			= $data['domain_dns'];
	$software_supp 		= $data['domain_software_allowed'];

	// Get IP of domain
	$query = "
		SELECT
			`ip_number`, `ip_domain`
		FROM
			`server_ips`
		WHERE
			`ip_id` = ?
	";

	$res = exec_query($query, $domain_ip_id);
	$data = $res->fetchRow();

	$domain_ip = $data['ip_number'] . '&nbsp;(' . $data['ip_domain'] . ')';

	// Get username of domain
	$query = "
		SELECT
			`admin_name`
		FROM
			`admin`
		WHERE
			`admin_id` = ?
		AND
			`admin_type` = 'user'
		AND
			`created_by` = ?
	";

	$res = exec_query($query, array($domain_admin_id, $user_id));
	$data = $res->fetchRow();

	$username = $data['admin_name'];

} // End of load_additional_data()

/**
 * Generates edit page.
 *
 * @param  iMSCP_pTemplate $tpl
 * @return void
 */
function gen_editdomain_page($tpl) {

	global $domain_name, $domain_expires, $domain_ip, $php_sup, $cgi_supp , $sub, $als, $mail, $ftp,
		$sql_db,$sql_user, $traff, $disk, $username, $allowbackup, $dns_supp, $domain_expires_date, $software_supp;
	global $phpiniSystem, $phpiniRegisterGlobals, $phpiniAllowUrlFopen, $phpiniDisplayErrors, $phpiniErrorReporting,
        $phpiniDisableFunctions, $phpiniPostMaxSize, $phpiniUploadMaxFileSize, $phpiniMaxExecutionTime, $phpiniMaxInputTime,
        $phpMemoryLimit;
    /** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	// Fill in the fields
	$domain_name = decode_idna($domain_name);
	$username = decode_idna($username);

	generate_ip_list($tpl, $_SESSION['user_id']);

	if ($allowbackup === 'dmn') {
		$tpl->assign(
			array(
				'BACKUP_DOMAIN' => $cfg->HTML_SELECTED,
				'BACKUP_SQL' 	=> '',
				'BACKUP_FULL' 	=> '',
				'BACKUP_NO' 	=> '',
			)
		);
	} else if ($allowbackup === 'sql')  {
		$tpl->assign(
			array(
				'BACKUP_DOMAIN' => '',
				'BACKUP_SQL' 	=> $cfg->HTML_SELECTED,
				'BACKUP_FULL' 	=> '',
				'BACKUP_NO' 	=> '',
			)
		);
	} else if ($allowbackup === 'full')  {
		$tpl->assign(
			array(
				'BACKUP_DOMAIN' => '',
				'BACKUP_SQL' 	=> '',
				'BACKUP_FULL' 	=> $cfg->HTML_SELECTED,
				'BACKUP_NO' 	=> '',
			)
		);
	} else if ($allowbackup === 'no')  {
		$tpl->assign(
			array(
				'BACKUP_DOMAIN' => '',
				'BACKUP_SQL' 	=> '',
				'BACKUP_FULL' 	=> '',
				'BACKUP_NO' 	=> $cfg->HTML_SELECTED,
			)
		);
	}

    if($domain_expires_date === '0')    {
        $tpl->assign(
            array(
                'VL_DOMAIN_EXPIRE_DATE' => '',
                'VL_NEVEREXPIRE'        => 'checked',
                'VL_DISABLED'           => 'disabled',
            )
        );
    } else {
        $tpl->assign(
            array(
                'VL_DOMAIN_EXPIRE_DATE'	=> $domain_expires_date,
                'VL_NEVEREXPIRE'        => '',
                'VL_DISABLED_NE'        => 'disabled',
            )
        );
    }

	list($rsub_max, $rals_max, $rmail_max, $rftp_max, $rsql_db_max, $rsql_user_max) = check_reseller_permissions(
		$_SESSION['user_id'], 'all_permissions'
	);

	if ($rsub_max == "-1") $tpl->assign('ALIAS_EDIT', '');
	if ($rals_max == "-1") $tpl->assign('SUBDOMAIN_EDIT', '');
	if ($rmail_max == "-1") $tpl->assign('MAIL_EDIT', '');
	if ($rftp_max == "-1") $tpl->assign('FTP_EDIT', '');
	if ($rsql_db_max == "-1") $tpl->assign('SQL_DB_EDIT', '');
	if ($rsql_user_max == "-1") $tpl->assign('SQL_USER_EDIT', '');

	$tpl->assign(
		array(
			'PHP_YES'				=> ($php_sup == 'yes') ? $cfg->HTML_SELECTED : '',
			'PHP_NO'				=> ($php_sup != 'yes') ? $cfg->HTML_SELECTED : '',
			'SOFTWARE_YES'			=> ($software_supp == 'yes') ? $cfg->HTML_SELECTED : '',
			'SOFTWARE_NO'			=> ($software_supp != 'yes') ? $cfg->HTML_SELECTED : '',
			'CGI_YES'				=> ($cgi_supp == 'yes') ? $cfg->HTML_SELECTED : '',
			'CGI_NO'				=> ($cgi_supp != 'yes') ? $cfg->HTML_SELECTED : '',
			'DNS_YES'				=> ($dns_supp == 'yes') ? $cfg->HTML_SELECTED : '',
			'DNS_NO'				=> ($dns_supp != 'yes') ? $cfg->HTML_SELECTED : '',
			'VL_DOMAIN_NAME'		=> tohtml($domain_name),
			'VL_DOMAIN_EXPIRE'		=> $domain_expires,
			'VL_DOMAIN_IP'			=> $domain_ip,
			'DOMAIN_EXPIRES_DATE'	=> $domain_expires_date,
			'VL_DOM_SUB'			=> $sub,
			'VL_DOM_ALIAS'			=> $als,
			'VL_DOM_MAIL_ACCOUNT'	=> $mail,
			'VL_FTP_ACCOUNTS'		=> $ftp,
			'VL_SQL_DB'				=> $sql_db,
			'VL_SQL_USERS'			=> $sql_user,
			'VL_TRAFFIC'			=> $traff,
			'VL_DOM_DISK'			=> $disk,
			'VL_USER_NAME'			=> tohtml($username),
                        'PHPINI_SYSTEM_YES'		=> ($phpiniSystem == 'yes') ? $cfg->HTML_CHECKED : '',
                        'PHPINI_SYSTEM_NO'		=> ($phpiniSystem == 'no') ? $cfg->HTML_CHECKED : '',
			'PHPINI_ALLOW_URL_FOPEN_ON' 	=> ($phpiniAllowUrlFopen == 'on') ? $cfg->HTML_SELECTED : '',
                        'PHPINI_ALLOW_URL_FOPEN_OFF'    => ($phpiniAllowUrlFopen != 'on') ? $cfg->HTML_SELECTED : '',
                        'PHPINI_REGISTER_GLOBALS_ON'	=> ($phpiniRegisterGlobals == 'on') ? $cfg->HTML_SELECTED : '',
                        'PHPINI_REGISTER_GLOBALS_OFF'	=> ($phpiniRegisterGlobals != 'on') ? $cfg->HTML_SELECTED : '',
                        'PHPINI_DISPLAY_ERRORS_ON'	=> ($phpiniDisplayErrors == 'on') ? $cfg->HTML_SELECTED : '',
			'PHPINI_DISPLAY_ERRORS_OFF'	=> ($phpiniDisplayErrors != 'on') ? $cfg->HTML_SELECTED : '',
                        'PHPINI_ERROR_REPORTING_0'	=> ($phpiniErrorReporting == '0') ? $cfg->HTML_SELECTED : '',
                        'PHPINI_ERROR_REPORTING_1'      => ($phpiniErrorReporting == 'E_ALL ^ (E_NOTICE | E_WARNING)') ? $cfg->HTML_SELECTED : '',
                        'PHPINI_ERROR_REPORTING_2'      => ($phpiniErrorReporting == 'E_ALL ^ E_NOTICE') ? $cfg->HTML_SELECTED : '',
                        'PHPINI_ERROR_REPORTING_3'      => ($phpiniErrorReporting == 'E_ALL') ? $cfg->HTML_SELECTED : '',
	                'PHPINI_POST_MAX_SIZE' 		=> $phpiniPostMaxSize,
        	        'PHPINI_UPLOAD_MAX_FILESIZE' 	=> $phpiniUploadMaxFileSize,
                	'PHPINI_MAX_EXECUTION_TIME' 	=> $phpiniMaxExecutionTime,
	                'PHPINI_MAX_INPUT_TIME' 	=> $phpiniMaxInputTime,
        	        'PHPINI_MEMORY_LIMIT' 		=> $phpMemoryLimit,
		)
	);

	$phpiniDf = explode(',', $phpiniDisableFunctions); //deAssemble the disable_functions
	$phpiniDfAll = array( 'PHPINI_DF_SHOW_SOURCE_CHK',
	                      'PHPINI_DF_SYSTEM_CHK',
                	      'PHPINI_DF_SHELL_EXEC_CHK',
        	              'PHPINI_DF_PASSTHRU_CHK',
	                      'PHPINI_DF_EXEC_CHK',
                	      'PHPINI_DF_PHPINFO_CHK',
        	              'PHPINI_DF_SHELL_CHK',
	                      'PHPINI_DF_SYMLINK_CHK' );


	foreach($phpiniDfAll as $phpiniDfVar){
        	$phpiniDfShortVar = substr($phpiniDfVar,10);
	        $phpiniDfShortVar = strtolower(substr($phpiniDfShortVar,0,-4));
        	if (in_array($phpiniDfShortVar,$phpiniDf)){
                	$tpl->assign(array(
                        	        $phpiniDfVar => 'CHECKED'));
	        }
        	else {
                	$tpl->assign(array(
                        	        $phpiniDfVar => ''));
	        }
	}

        if ($phpiniPerm = get_reseller_phpini_permission($_SESSION['user_id'])) { //get reseller permission detail on php.ini
		$tpl->parse('T_PHPINI_SYSTEM', 't_phpini_system');
                if ($phpiniPerm->fields('php_ini_al_register_globals') == 'yes'){
                        $tpl->parse('T_PHPINI_REGISTER_GLOBALS', 't_phpini_register_globals');

                } else {
                        $tpl->assign(array('T_PHPINI_REGISTER_GLOBALS'=> ''));
                }
                if ($phpiniPerm->fields('php_ini_al_allow_url_fopen') == 'yes'){
                        $tpl->parse('T_PHPINI_ALLOW_URL_FOPEN', 't_phpini_allow_url_fopen');
                } else {
                        $tpl->assign(array('T_PHPINI_ALLOW_URL_FOPEN'=> ''));
                }
                if ($phpiniPerm->fields('php_ini_al_display_errors') == 'yes'){
                        $tpl->parse('T_PHPINI_DISPLAY_ERRORS', 't_phpini_display_errors');
                } else {
                        $tpl->assign(array('T_PHPINI_DISPLAY_ERRORS'=> ''));
                }
                if ($phpiniPerm->fields('php_ini_al_disable_functions') == 'yes'){
                        $tpl->parse('T_PHPINI_DISABLE_FUNCTIONS', 't_phpini_disable_functions');
                } else {
                        $tpl->assign(array('T_PHPINI_DISABLE_FUNCTIONS'=> ''));
                }

        } else { //if no permission at all
                $tpl->assign(array('T_PHPINI_SYSTEM' => ''));
        }

	
}

/**
 * @param  iMSCP_pTemplate $tpl
 * @param  $reseller_id Reseller unique identifier
 * @param  $user_id
 * @return bool
 */
function check_user_data($tpl, $reseller_id, $user_id) {

	global $sub, $als, $mail, $ftp, $sql_db, $sql_user, $traff, $disk, $domain_php, $domain_cgi, $allowbackup,
		$domain_dns, $domain_expires, $domain_new_expire, $domain_software_allowed;
	global $phpiniSystem, $phpiniRegisterGlobals, $phpiniAllowUrlFopen, $phpiniDisplayErrors, $phpiniErrorReporting,
        $phpiniDisableFunctions, $phpiniPostMaxSize, $phpiniUploadMaxFileSize, $phpiniMaxExecutionTime, $phpiniMaxInputTime,
        $phpMemoryLimit;

	$datepicker			= (isset($_POST['dmn_expire_date'])) ? clean_input($_POST['dmn_expire_date']):''; //Fix PHP NOTICE display 
	$domain_new_expire		= (isset($_POST['dmn_expire'])) ? clean_input($_POST['dmn_expire']):''; //Fix PHP NOTICE display 
	$sub				= clean_input($_POST['dom_sub']);
	$als				= clean_input($_POST['dom_alias']);
	$mail				= clean_input($_POST['dom_mail_acCount']);
	$ftp				= clean_input($_POST['dom_ftp_acCounts']);
	$sql_db				= clean_input($_POST['dom_sqldb']);
	$sql_user			= clean_input($_POST['dom_sql_users']);
	$traff				= clean_input($_POST['dom_traffic']);
	$disk				= clean_input($_POST['dom_disk']);

	$domain_php		= preg_replace("/\_/", '', $_POST['domain_php']);
	$domain_cgi		= preg_replace("/\_/", '', $_POST['domain_cgi']);
	$domain_dns		= preg_replace("/\_/", '', $_POST['domain_dns']);
	$allowbackup	= preg_replace("/\_/", '', $_POST['backup']);
	$domain_software_allowed = preg_replace("/\_/", '', $_POST['domain_software_allowed']);

	$ed_error = '';

	list($rsub_max, $rals_max, $rmail_max, $rftp_max, $rsql_db_max, $rsql_user_max) = check_reseller_permissions(
		$_SESSION['user_id'], 'all_permissions'
	);

	if ($rsub_max == "-1") {
		$sub = "-1";
	} elseif (!imscp_limit_check($sub, -1)) {
		$ed_error .= tr('Incorrect subdomains limit!');
	}

	if ($rals_max == "-1") {
		$als = "-1";
	} elseif (!imscp_limit_check($als, -1)) {
		$ed_error .= tr('Incorrect aliases limit!');
	}

	if ($rmail_max == "-1") {
		$mail = "-1";
	} elseif (!imscp_limit_check($mail, -1)) {
		$ed_error .= tr('Incorrect mail accounts limit!');
	}

	if ($rftp_max == "-1") {
		$ftp = "-1";
	} elseif (!imscp_limit_check($ftp, -1)) {
		$ed_error .= tr('Incorrect FTP accounts limit!');
	}

	if ($rsql_db_max == "-1") {
		$sql_db = "-1";
	} elseif (!imscp_limit_check($sql_db, -1)) {
		$ed_error .= tr('Incorrect SQL users limit!');
	} else if ($sql_db == -1 && $sql_user != -1) {
		$ed_error .= tr('SQL databases limit is <i>disabled</i>!');
	}

	if ($rsql_user_max == "-1") {
		$sql_user = "-1";
	} elseif (!imscp_limit_check($sql_user, -1)) {
		$ed_error .= tr('Incorrect SQL databases limit!');
	} else if ($sql_user == -1 && $sql_db != -1) {
		$ed_error .= tr('SQL users limit is <i>disabled</i>!');
	}

	if (!imscp_limit_check($traff, null)) {
		$ed_error .= tr('Incorrect traffic limit!');
	}
	if (!imscp_limit_check($disk, null)) {
		$ed_error .= tr('Incorrect disk quota limit!');
	}
	if ($domain_php == "no" && $domain_software_allowed == "yes") {
		$ed_error .= tr('The i-MSCP application installer needs PHP to enable it!');
	}

	list(
		$usub_current, $usub_max, $uals_current, $uals_max, $umail_current, $umail_max, $uftp_current, $uftp_max,
		$usql_db_current, $usql_db_max, $usql_user_current, $usql_user_max, $utraff_max, $udisk_max
	) = generate_user_props($user_id);

	$previous_utraff_max = $utraff_max;

	list(
		$rdmn_current, $rdmn_max, $rsub_current, $rsub_max, $rals_current, $rals_max, $rmail_current, $rmail_max,
		$rftp_current, $rftp_max, $rsql_db_current, $rsql_db_max, $rsql_user_current, $rsql_user_max, $rtraff_current,
		$rtraff_max, $rdisk_current, $rdisk_max
	) = get_reseller_default_props($reseller_id);

	list(,,,,,,$utraff_current, $udisk_current) = generate_user_traffic($user_id);

	if (empty($ed_error)) {
		calculate_user_dvals($sub, $usub_current, $usub_max, $rsub_current, $rsub_max, $ed_error, tr('Subdomain'));
		calculate_user_dvals($als, $uals_current, $uals_max, $rals_current, $rals_max, $ed_error, tr('Alias'));
		calculate_user_dvals($mail, $umail_current, $umail_max, $rmail_current, $rmail_max, $ed_error, tr('Mail'));
		calculate_user_dvals($ftp, $uftp_current, $uftp_max, $rftp_current, $rftp_max, $ed_error, tr('FTP'));
		calculate_user_dvals($sql_db, $usql_db_current, $usql_db_max, $rsql_db_current, $rsql_db_max, $ed_error, tr('SQL Database'));
	}

	if (empty($ed_error)) {
		$query = "
			SELECT
				COUNT(su.`sqlu_id`) AS cnt
			FROM
				`sql_user` AS su,
				`sql_database` AS sd
			WHERE
				su.`sqld_id` = sd.`sqld_id`
			AND
				sd.`domain_id` = ?
		";

		$rs = exec_query($query, $_SESSION['edit_id']);
		calculate_user_dvals($sql_user, $rs->fields['cnt'], $usql_user_max, $rsql_user_current, $rsql_user_max, $ed_error, tr('SQL User'));
	}

	if (empty($ed_error)) {
		calculate_user_dvals(
			$traff, $utraff_current / 1024 / 1024 , $utraff_max, $rtraff_current, $rtraff_max, $ed_error, tr('Traffic')
		);

		calculate_user_dvals(
			$disk, $udisk_current / 1024 / 1024, $udisk_max, $rdisk_current, $rdisk_max, $ed_error, tr('Disk')
		);
	}
	
	//phpini check and safe into db
        if ($phpiniPerm = get_reseller_phpini_permission($_SESSION['user_id'])) { //get reseller permission detail on php.ini

                $phpiniDefaultData = get_default_phpini_data(); //Get the default php ini values from config table

                $phpiniSystem = (isset($_POST['phpini_system'])) ? clean_input($_POST['phpini_system']) : 'no';
                $phpiniRegisterGlobals = (isset($_POST['phpini_register_globals'])) ? clean_input($_POST['phpini_register_globals']) : $phpiniDefaultData['phpiniRegisterGlobals'];
                $phpiniAllowUrlFopen = (isset($_POST['phpini_allow_url_fopen'])) ? clean_input($_POST['phpini_allow_url_fopen']) : $phpiniDefaultData['phpiniAllowUrlFopen'];
                $phpiniDisplayErrors = (isset($_POST['phpini_display_errors'])) ? clean_input($_POST['phpini_display_errors']) : $phpiniDefaultData['phpiniDisplayErrors'];
                $phpiniErrorReporting = (isset($_POST['phpini_error_reporting'])) ? clean_input($_POST['phpini_error_reporting']) : $phpiniDefaultData['phpiniErrorReporting'];
                $phpiniPostMaxSize = (isset($_POST['phpini_post_max_size'])) ? clean_input($_POST['phpini_post_max_size']) : $phpiniDefaultData['phpiniPostMaxSize'];
                $phpiniUploadMaxFileSize = (isset($_POST['phpini_upload_max_filesize'])) ? clean_input($_POST['phpini_upload_max_filesize']) : $phpiniDefaultData['phpiniUploadMaxFilesize'];
                $phpiniMaxExecutionTime = (isset($_POST['phpini_max_execution_time'])) ? clean_input($_POST['phpini_max_execution_time']) : $phpiniDefaultData['phpiniMaxExecutionTime'];
                $phpiniMaxInputTime = (isset($_POST['phpini_max_input_time'])) ? clean_input($_POST['phpini_max_input_time']) : $phpiniDefaultData['phpiniMaxInputTime'];
                $phpMemoryLimit = (isset($_POST['phpini_memory_limit'])) ? clean_input($_POST['phpini_memory_limit']) : $phpiniDefaultData['phpiniMemoryLimit'];
		
                //assemble $phpini_disable_functions 
                $phpiniDisableFunctions = '';
                $phpiniDisableFunctionsTmp = array();
                foreach($_POST as $key =>$value){
                        if (substr($key,0,10) == "phpini_df_") {
                                array_push($phpiniDisableFunctionsTmp,clean_input($value));
                        }
                }
                if (count($phpiniDisableFunctionsTmp) > 0) {
                        $phpiniDisableFunctions = implode(',',$phpiniDisableFunctionsTmp);
                } else {
                        $phpiniDisableFunctions = $phpiniDefaultData['phpiniDisableFunctions'];
                }

                if ($phpiniPostMaxSize >= $phpiniPerm->fields('php_ini_max_post_max_size')){
                        $ed_error .= tr('post_max_size out of range');
                }
                if ($phpiniUploadMaxFileSize >= $phpiniPerm->fields('php_ini_max_upload_max_filesize')){
                        $ed_error .= tr('upload_max_filesize out of range');
                }
                if ($phpiniMaxExecutionTime >= $phpiniPerm->fields('php_ini_max_max_execution_time') == 'yes'){
                        $ed_error .= tr('max_execution_time out of range');
                }
                if ($phpiniMaxInputTime >= $phpiniPerm->fields('php_ini_max_max_input_time')){
                        $ed_error .= tr('max_input_time out of range');
                }
                if ($phpMemoryLimit >= $phpiniPerm->fields('php_ini_max_memory_limit')){
                        $ed_error .= tr('memory_limit out of range');
                }
		
                // if all OK Update data in php_ini table
                //Need to make a query to check if custom php.ini allready exist - rowCount() doenst work because if no data change it give 0 back 
                if (get_custom_phpini_data($_SESSION['edit_id']) && $phpiniSystem == "yes" && empty($ed_error)) {
                        $query = "UPDATE 
                                        `php_ini` 
                                SET 
                                        `status` = 'change',
                                        `disable_functions` = ?,
                                        `allow_url_fopen` = ?,
                                        `register_globals` = ?,
                                        `display_errors` = ?,
                                        `error_reporting` = ?,
                                        `post_max_size` = ?,
                                        `upload_max_filesize` = ?,
                                        `max_execution_time` = ?,
                                        `max_input_time` = ?,
                                        `memory_limit` = ?
                                WHERE   
                                        `domain_id` = ?
                                ";
                        exec_query($query, array($phpiniDisableFunctions,
                                        $phpiniAllowUrlFopen,
                                        $phpiniRegisterGlobals,
                                        $phpiniDisplayErrors,
                                        $phpiniErrorReporting,
                                        $phpiniPostMaxSize,
	                                $phpiniUploadMaxFileSize,
                                        $phpiniMaxExecutionTime,
                                        $phpiniMaxInputTime,
                                        $phpMemoryLimit,
                                        $_SESSION['edit_id']));

                } elseif (get_custom_phpini_data($_SESSION['edit_id']) && $phpiniSystem == "no" && empty($ed_error)) { //if custom php.ini exist and reseller choose no than del it
			$query = "DELETE FROM 
                                                `php_ini` 
                                          WHERE   
                                                `domain_id` = ?
                                        ";
			exec_query($query, $_SESSION['edit_id']);

		} elseif ($phpiniSystem == "yes" && empty($ed_error)) {  //if now custom php.ini exist and reseller choose yes than create it
                        $query = "INSERT INTO
                                        `php_ini` (
                                                `status`,
                                                `disable_functions`,
                                                `allow_url_fopen`,
                                                `register_globals`,
                                                `display_errors`,
                                                `error_reporting`,
                                                `post_max_size`,
                                                `upload_max_filesize`,
                                                `max_execution_time`,
                                                `max_input_time`,
                                                `memory_limit`,
                                                `domain_id`
                                        ) VALUES (
                                                'new', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                                        )
                                ";
                        exec_query($query, array($phpiniDisableFunctions,
                                        $phpiniAllowUrlFopen,
                                        $phpiniRegisterGlobals,
                                        $phpiniDisplayErrors,
                                        $phpiniErrorReporting,
                                        $phpiniPostMaxSize,
                                        $phpiniUploadMaxFileSize,
                                        $phpiniMaxExecutionTime,
                                        $phpiniMaxInputTime,
                                        $phpMemoryLimit,
                                        $_SESSION['edit_id']));
                } 

        } else { //if no permission at all - do nothing with the saved phpini data but load the default vars
                $phpiniDefaultData = get_default_phpini_data(); //Get the default php ini values from config table
                $phpiniSystem = 'no';
                $phpiniRegisterGlobals = $phpiniDefaultData['phpiniRegisterGlobals'];
                $phpiniAllowUrlFopen = $phpiniDefaultData['phpiniAllowUrlFopen'];
                $phpiniDisplayErrors = $phpiniDefaultData['phpiniDisplayErrors'];
                $phpiniErrorReporting = $phpiniDefaultData['phpiniErrorReporting'];
                $phpiniDisableFunctions =  $phpiniDefaultData['phpiniDisableFunctions'];
                $phpiniPostMaxSize = $phpiniDefaultData['phpiniPostMaxSize'];
                $phpiniUploadMaxFileSize = $phpiniDefaultData['phpiniUploadMaxFilesize'];
                $phpiniMaxExecutionTime = $phpiniDefaultData['phpiniMaxExecutionTime'];
                $phpiniMaxInputTime = $phpiniDefaultData['phpiniMaxInputTime'];
                $phpMemoryLimit = $phpiniDefaultData['phpiniMemoryLimit'];		
        }

	if (empty($ed_error)) {
		// Set domains status to 'change' to update mod_cband's limit
		if ($previous_utraff_max != $utraff_max) {
			$query = "UPDATE `domain` SET `domain_status` = 'change' WHERE `domain_id` = ?";
			exec_query($query, $user_id);
			$query = "UPDATE `subdomain` SET `subdomain_status` = 'change' WHERE `domain_id` = ?";
			exec_query($query, $user_id);
			send_request();
		}

		$user_props = "$usub_current;$usub_max;";
		$user_props .= "$uals_current;$uals_max;";
		$user_props .= "$umail_current;$umail_max;";
		$user_props .= "$uftp_current;$uftp_max;";
		$user_props .= "$usql_db_current;$usql_db_max;";
		$user_props .= "$usql_user_current;$usql_user_max;";
		$user_props .= "$utraff_max;";
		$user_props .= "$udisk_max;";
		// $user_props .= "$domain_ip;";
		$user_props .= "$domain_php;";
		$user_props .= "$domain_cgi;";
		$user_props .= "$allowbackup;";
		$user_props .= "$domain_dns;";
		$user_props .= "$domain_software_allowed";
		update_user_props($user_id, $user_props);


        	// Date-Picker domain expire update
	        if($_POST['neverexpire'] != "on"){
	            $domain_expires = datepicker_reseller_convert($datepicker);
        	} else {
	            $domain_expires = "0";
        	}
		update_expire_date($user_id, $domain_expires);

		$reseller_props = "$rdmn_current;$rdmn_max;";
		$reseller_props .= "$rsub_current;$rsub_max;";
		$reseller_props .= "$rals_current;$rals_max;";
		$reseller_props .= "$rmail_current;$rmail_max;";
		$reseller_props .= "$rftp_current;$rftp_max;";
		$reseller_props .= "$rsql_db_current;$rsql_db_max;";
		$reseller_props .= "$rsql_user_current;$rsql_user_max;";
		$reseller_props .= "$rtraff_current;$rtraff_max;";
		$reseller_props .= "$rdisk_current;$rdisk_max";

		if (!update_reseller_props($reseller_id, $reseller_props)) {
			set_page_message(tr('Domain properties could not be updated!'));

			return false;
		}

		// Backup Settings
		$query = "UPDATE `domain` SET `allowbackup` = ? WHERE `domain_id` = ?";
		exec_query($query, array($allowbackup, $user_id));

		// update the sql quotas, too
		$query = "SELECT `domain_name` FROM `domain` WHERE `domain_id` = ?";
		$rs = exec_query($query, $user_id);
		$temp_dmn_name = $rs->fields['domain_name'];

		$query = "SELECT COUNT(`name`) AS cnt FROM `quotalimits` WHERE `name` = ?";
		$rs = exec_query($query, $temp_dmn_name);

		if ($rs->fields['cnt'] > 0) {
			// we need to update it
			if ($disk == 0) {
				$dlim = 0;
			} else {
				$dlim = $disk * 1024 * 1024;
			}

			$query = "UPDATE `quotalimits` SET `bytes_in_avail` = ? WHERE `name` = ?";
			exec_query($query, array($dlim, $temp_dmn_name));
		}

		set_page_message(tr('Domain properties updated successfully!'), 'success');

		return true;
	} else {
		//$tpl->assign('MESSAGE', $ed_error);
		//$tpl->parse('PAGE_MESSAGE', 'page_message');
		set_page_message(tr($ed_error), 'error');
		return false;
	}
}


/**
 * Must be documented.
 *
 * @throws iMSCP_Exception
 * @param  $data
 * @param  $u
 * @param  $umax
 * @param  $r
 * @param  $rmax
 * @param  $err
 * @param  $obj
 * @return void
 */
function calculate_user_dvals($data, $u, &$umax, &$r, $rmax, &$err, $obj) {

	if ($rmax == -1 && $umax >= 0) {
		if ($u > 0) {
			$err .= tr('The <em>%s</em> service cannot be disabled!', $obj) . tr('There are <em>%s</em> records on system!', $obj);
			return;
		} else if ($data != -1){
			$err .= tr('The <em>%s</em> have to be disabled!', $obj) . tr('The admin has <em>%s</em> disabled on this system!', $obj);
			return;
		} else {
			$umax = $data;
		}
		return;
	} else if ($rmax == 0 && $umax == -1) {
		if ($data == -1) {
			return;
		} else if ($data == 0) {
			$umax = $data;
			return;
		} else if ($data > 0) {
			$umax = $data;
			$r += $umax;
			return;
		}
	} else if ($rmax == 0 && $umax == 0) {
		if ($data == -1) {
			if ($u > 0) {
				$err .= tr('The <em>%s</em> service cannot be disabled!', $obj) . tr('There are <em>%s</em> records on system!', $obj);
			} else {
				$umax = $data;
			}

			return;
		} else if ($data == 0) {
			return;
		} else if ($data > 0) {
			if ($u > $data) {
				$err .= tr('The <em>%s</em> service cannot be limited!', $obj) . tr('Specified number is smaller than <em>%s</em> records, present on the system!', $obj);
			} else {
				$umax = $data;
				$r += $umax;
			}
			return;
		}
	} else if ($rmax == 0 && $umax > 0) {
		if ($data == -1) {
			if ($u > 0) {
				$err .= tr('The <em>%s</em> service cannot be disabled!', $obj) . tr('There are <em>%s</em> records on the system!', $obj);
			} else {
				$r -= $umax;
				$umax = $data;
			}
			return;
		} else if ($data == 0) {
			$r -= $umax;
			$umax = $data;
			return;
		} else if ($data > 0) {
			if ($u > $data) {
				$err .= tr('The <em>%s</em> service cannot be limited!', $obj) . tr('Specified number is smaller than <em>%s</em> records, present on the system!', $obj);
			} else {
				if ($umax > $data) {
					$data_dec = $umax - $data;
					$r -= $data_dec;
				} else {
					$data_inc = $data - $umax;
					$r += $data_inc;
				}
				$umax = $data;
			}
			return;
		}
	} else if ($rmax > 0 && $umax == -1) {
		if ($data == -1) {
			return;
		} else if ($data == 0) {
			$err .= tr('The <em>%s</em> service cannot be unlimited!', $obj) . tr('There are reseller limits for the <em>%s</em> service!', $obj);
			return;
		} else if ($data > 0) {
			if ($r + $data > $rmax) {
				$err .= tr('The <em>%s</em> service cannot be limited!', $obj) . tr('You are exceeding reseller limits for the <em>%s</em> service!', $obj);
			} else {
				$r += $data;

				$umax = $data;
			}

			return;
		}
	} else if ($rmax > 0 && $umax == 0) {
		throw new iMSCP_Exception("FIXME: ". __FILE__ .":". __LINE__);
	} else if ($rmax > 0 && $umax > 0) {
		if ($data == -1) {
			if ($u > 0) {
				$err .= tr('The <em>%s</em> service cannot be disabled!', $obj) . tr('There are <em>%s</em> records on the system!', $obj);
			} else {
				$r -= $umax;
				$umax = $data;
			}

			return;
		} else if ($data == 0) {
			$err .= tr('The <em>%s</em> service cannot be unlimited!', $obj) . tr('There are reseller limits for the <em>%s</em> service!', $obj);

			return;
		} else if ($data > 0) {
			if ($u > $data) {
				$err .= tr('The <em>%s</em> service cannot be limited!', $obj) . tr('Specified number is smaller than <em>%s</em> records, present on the system!', $obj);
			} else {
				if ($umax > $data) {
					$data_dec = $umax - $data;
					$r -= $data_dec;
				} else {
					$data_inc = $data - $umax;

					if ($r + $data_inc > $rmax) {
						$err .= tr('The <em>%s</em> service cannot be limited!', $obj) . tr('You are exceeding reseller limits for the <em>%s</em> service!', $obj);
						return;
					}

					$r += $data_inc;
				}

				$umax = $data;
			}

			return;
		}
	}
}

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(
    iMSCP_Events::onResellerScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
