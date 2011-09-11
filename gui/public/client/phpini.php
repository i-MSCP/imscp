<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright 	2011 by i-MSCP | http://i-mscp.net
 * @version 	SVN: $Id: webtools.php 4939 2011-07-16 19:41:28Z nuxwin $
 * @link 		http://i-mscp.net
 * @author 		i-MSCP Team
 *
 * @license
*/


include 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page', $cfg->CLIENT_TEMPLATE_PATH . '/phpini.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('t_phpini_register_globals', 'page');
$tpl->define_dynamic('t_phpini_allow_url_fopen', 'page');
$tpl->define_dynamic('t_phpini_display_errors', 'page');
$tpl->define_dynamic('t_phpini_disable_functions', 'page');
$tpl->define_dynamic('t_phpini_disable_functions_exec', 'page');
$tpl->assign(
	array(
		'TR_CLIENT_PHPINI_PAGE_TITLE' => tr('i-MSCP - php.ini Settings'),
		'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
                'TR_MENUPHPINI' => tr('php.ini Setting'),
                'TR_PHPINI_TEXT' => tr('php.ini Settings'),
                'TR_PHPINI_ALLOW_URL_FOPEN'     => tr('allow_url_fopen'),
                'TR_PHPINI_REGISTER_GLOBALS'    => tr('register_globals'),
                'TR_PHPINI_DISPLAY_ERRORS'      => tr('display_errors'),
                'TR_PHPINI_ERROR_REPORTING'     => tr('error_reporting'),
                'TR_PHPINI_ER_OFF'              => tr('All off'),
                'TR_PHPINI_ER_EALL_EXCEPT_NOTICE_EXCEPT_WARN'   => tr('All errors except notices and warnings'),
                'TR_PHPINI_ER_EALL_EXCEPT_NOTICE'               => tr('All errors except notices'),
                'TR_PHPINI_ER_EALL'             => tr('All errors'),
                'TR_PHPINI_POST_MAX_SIZE'       => tr('post_max_size [MB]'),
                'TR_PHPINI_UPLOAD_MAX_FILESIZE' => tr('upload_max_filesize [MB]'),
                'TR_PHPINI_MAX_EXECUTION_TIME'  => tr('max_execution_time [sec]'),
                'TR_PHPINI_MAX_INPUT_TIME'      => tr('max_input_time [sec]'),
                'TR_PHPINI_MEMORY_LIMIT'        => tr('memory_limit [MB]'),
                'TR_PHPINI_DISABLE_FUNCTIONS'   => tr('disable_functions'),
                'TR_PHPINI_DISABLE_FUNCTIONS_EXEC'   => tr('Enable exec (disabled_functions)'),
                'TR_ENABLED'                    => tr('Enabled'),
                'TR_DISABLED'                   => tr('Disabled'),
                'TR_UPDATE_DATA'                => tr('Submit changes'),
                'TR_CANCEL'                             => tr('Cancel'),
                'TR_UPDATE_DATA'                => tr('Submit changes'),
                'TR_CANCEL'                             => tr('Cancel'),
                'TR_UPDATE_DATA'                => tr('Submit changes'),
                'TR_UPDATE_DATA'                => tr('Submit changes'),
                'TR_CANCEL'                             => tr('Cancel'),
                'TR_CANCEL'                             => tr('Cancel'),
                'TR_UPDATE_DATA'                => tr('Submit changes'),
                'TR_CANCEL'                             => tr('Cancel'),
                'TR_MENU_PHPINI'                             => tr('php.ini Settings'),
                'TR_PHPINI'                             => tr('php.ini')

	)
);

gen_client_mainmenu($tpl,$cfg->CLIENT_TEMPLATE_PATH . '/main_menu_webtools.tpl');
gen_client_menu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/menu_webtools.tpl');
gen_logged_from($tpl);

//get domain_id from user_id - maybe theres allready a shared funciton for it
$query = "SELECT `domain_id` FROM `domain` WHERE `domain_admin_id` = ?";
$stmt = exec_query($query, $_SESSION['user_id']);
$domainId = $stmt->fields('domain_id');


/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

/* iMSCP_PHPini object */
$phpini = new iMSCP_PHPini();

$phpini->loadClPerm($domainId); //load phpini client permission

if (isset($_POST['uaction']) && ($_POST['uaction'] == 'phpini_save')) { // if save if called...
	if ($phpini->getClPermVal('phpiniSystem') == 'yes' ) {
                $phpini->setData('phpiniSystem','yes');
                if ($phpini->getClPermVal('phpiniRegisterGlobals') == 'yes' && isset($_POST['phpini_register_globals'])) {
                        $phpini->setData('phpiniRegisterGlobals', clean_input($_POST['phpini_register_globals']));
                }
                if ($phpini->getClPermVal('phpiniAllowUrlFopen') == 'yes' && isset($_POST['phpini_allow_url_fopen'])) {
                        $phpini->setData('phpiniAllowUrlFopen', clean_input($_POST['phpini_allow_url_fopen']));
                }
                if ($phpini->getClPermVal('phpiniDisplayErrors') == 'yes' && isset($_POST['phpini_display_errors'])) {
                        $phpini->setData('phpiniDisplayErrors', clean_input($_POST['phpini_display_errors']));
                }
                if ($phpini->getClPermVal('phpiniDisplayErrors') == 'yes' && isset($_POST['phpini_error_reporting'])) {
                        $phpini->setData('phpiniErrorReporting', clean_input($_POST['phpini_error_reporting']));
                }
		if ($phpini->getClPermVal('phpiniDisableFunctions') == 'yes') {
			//collect all parts of disabled_function from $_POST
	                $mytmp=array();
        	        foreach($_POST as $key =>$value){
                	        if (substr($key,0,10) == "phpini_df_") {
                        	        array_push($mytmp,clean_input($value));
	                        }
        	        }
			$phpini->setData('phpiniDisableFunctions', $phpini->assembleDisableFunctions($mytmp));
		}
		if ($phpini->getClPermVal('phpiniDisableFunctions') == 'exec') {
			if ($_POST['phpini_disable_functions_exec'] == 'yes') {
				$phpini->setData('phpiniDisableFunctions',$phpini->getDataDefaultVal('phpiniDisableFunctions'));
			} else {
				$tmp_arr = array_diff($phpini->getDataDefaultVal('phpiniDisableFunctions'), array('exec'));	//remove exec from default disabled_fun..
				$phpini->setData('phpiniDisableFunctions',implode(',',$tmp_arr));
			}
		
		}

	}
	$phpini->saveCustomPHPiniIntoDb($domainId); 
	set_page_message(tr('Setting updated'), 'info');
} //end save call


if ($phpini->getClPermVal('phpiniSystem') == 'yes') { //if reseller has permission to use php.ini feature
	$phpini->loadCustomPHPini($domainId); //load custom php.ini
        if ($phpini->getClPermVal('phpiniRegisterGlobals') == 'yes') {
                $tpl->parse('T_PHPINI_REGISTER_GLOBALS', 't_phpini_register_globals');
        } else {
                $tpl->assign(array('T_PHPINI_REGISTER_GLOBALS'=> ''));
        }
        if ($phpini->getClPermVal('phpiniAllowUrlFopen') == 'yes') {
                $tpl->parse('T_PHPINI_ALLOW_URL_FOPEN', 't_phpini_allow_url_fopen');
        } else {
                $tpl->assign(array('T_PHPINI_ALLOW_URL_FOPEN'=> ''));
        }
        if ($phpini->getClPermVal('phpiniDisplayErrors') == 'yes') {
                $tpl->parse('T_PHPINI_DISPLAY_ERRORS', 't_phpini_display_errors');
        } else {
                $tpl->assign(array('T_PHPINI_DISPLAY_ERRORS'=> ''));
        }
        if ($phpini->getClPermVal('phpiniDisableFunctions') == 'yes') {
                $tpl->parse('T_PHPINI_DISABLE_FUNCTIONS', 't_phpini_disable_functions');
		$tpl->assign(array('T_PHPINI_DISABLE_FUNCTIONS_EXEC'=> ''));
        } elseif ($phpini->getClPermVal('phpiniDisableFunctions') == 'exec') {
		$tpl->assign(array('T_PHPINI_DISABLE_FUNCTIONS'=> ''));
		$tpl->parse('T_PHPINI_DISABLE_FUNCTIONS_EXEC', 't_phpini_disable_functions_exec');
	} else {
                $tpl->assign(array('T_PHPINI_DISABLE_FUNCTIONS'=> ''));
		$tpl->assign(array('T_PHPINI_DISABLE_FUNCTIONS_EXEC'=> ''));
        }
	$tpl->assign(
        	array(
	        'PHPINI_ALLOW_URL_FOPEN_ON'     => ($phpini->getDataVal('phpiniAllowUrlFopen') == 'on') ? $cfg->HTML_SELECTED : '',
        	'PHPINI_ALLOW_URL_FOPEN_OFF'    => ($phpini->getDataVal('phpiniAllowUrlFopen') != 'on') ? $cfg->HTML_SELECTED : '',
	        'PHPINI_REGISTER_GLOBALS_ON'    => ($phpini->getDataVal('phpiniRegisterGlobals') == 'on') ? $cfg->HTML_SELECTED : '',
        	'PHPINI_REGISTER_GLOBALS_OFF'   => ($phpini->getDataVal('phpiniRegisterGlobals') != 'on') ? $cfg->HTML_SELECTED : '',
	        'PHPINI_DISPLAY_ERRORS_ON'      => ($phpini->getDataVal('phpiniDisplayErrors') == 'on') ? $cfg->HTML_SELECTED : '',
        	'PHPINI_DISPLAY_ERRORS_OFF'     => ($phpini->getDataVal('phpiniDisplayErrors') != 'on') ? $cfg->HTML_SELECTED : '',
	        'PHPINI_ERROR_REPORTING_0'      => ($phpini->getDataVal('phpiniErrorReporting') == '0') ? $cfg->HTML_SELECTED : '',
        	'PHPINI_ERROR_REPORTING_1'      => ($phpini->getDataVal('phpiniErrorReporting') == 'E_ALL ^ (E_NOTICE | E_WARNING)') ? $cfg->HTML_SELECTED : '',
	        'PHPINI_ERROR_REPORTING_2'      => ($phpini->getDataVal('phpiniErrorReporting') == 'E_ALL ^ E_NOTICE') ? $cfg->HTML_SELECTED : '',
        	'PHPINI_ERROR_REPORTING_3'      => ($phpini->getDataVal('phpiniErrorReporting') == 'E_ALL') ? $cfg->HTML_SELECTED : '',
	        )
	);	
        $phpiniDf = explode(',', $phpini->getDataVal('phpiniDisableFunctions')); //deAssemble the disable_functions
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



} else { //if no permission at all
	$tpl->assign(array('T_PHPINI_REGISTER_GLOBALS'=> ''));
	$tpl->assign(array('T_PHPINI_ALLOW_URL_FOPEN'=> ''));
	$tpl->assign(array('T_PHPINI_DISPLAY_ERRORS'=> ''));
	$tpl->assign(array('T_PHPINI_DISABLE_FUNCTIONS'=> ''));	
}


check_permissions($tpl);

generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(
    iMSCP_Events::onClientScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
