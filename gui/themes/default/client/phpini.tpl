<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_CLIENT_PHPINI_PAGE_TITLE}</title>
        <meta name="robots" content="nofollow, noindex" />
        <link href="{THEME_COLOR_PATH}/css/imscp.css" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/imscp.js"></script>
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/jquery.js"></script>
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/jquery.imscpTooltips.js"></script>
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/jquery.ui.core.js"></script>
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/jquery.ui.datepicker.js"></script>
        <!--[if IE 6]>
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/DD_belatedPNG_0.0.8a-min.js"></script>
        <script type="text/javascript">
            DD_belatedPNG.fix('*');
        </script>
        <![endif]-->
        <script type="text/javascript">
                /*<![CDATA[*/
                        $(document).ready(function() {
                                jQuery('#exec_help').iMSCPtooltips({msg:"{TR_PHP_INI_EXEC_HELP}"});
                        });
                /*]]>*/
        </script>
    </head>

    <body>
        <div class="header">
            {MAIN_MENU}
            <div class="logo">
                <img src="{ISP_LOGO}" alt="i-MSCP logo" />
            </div>
        </div>
        <div class="location">
            <div class="location-area icons-left">
                <h1 class="webtools">{TR_PHPINI}</h1>
            </div>
            <ul class="location-menu">
                <!-- BDP: logged_from -->
                <li>
                    <a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a>
                </li>
                <!-- EDP: logged_from -->
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a>
                </li>
            </ul>
            <ul class="path">
                <li><a href="webtools.php">{TR_MENU_MANAGE_DOMAINS}</a></li>
                <li><a href="phpini.php">{TR_MENU_PHPINI}</a></li>
            </ul>
        </div>
        <div class="left_menu">
            {MENU}
        </div>
        <div class="body">
            <h2 class="tools"><span>{TR_MENU_PHPINI}</span></h2>
            <!-- BDP: page_message -->
            <div class="{MESSAGE_CLS}">{MESSAGE}</div>
            <!-- EDP: page_message -->
           <!-- BDP: t_update_ok -->
           <form name="client_php_ini_edit_frm" method="post" action="phpini.php">
           <table>
           <!-- BDP: t_phpini_allow_url_fopen -->
           <tr>
                           <td style="width:300px;"><label for="phpini_allow_url_fopen">{TR_PHPINI_ALLOW_URL_FOPEN}</label></td>
                    <td>
                            <select name="phpini_allow_url_fopen" id="phpini_allow_url_fopen">
                                    <option value="off" {PHPINI_ALLOW_URL_FOPEN_OFF}>{TR_DISABLED}</option>
                                    <option value="on" {PHPINI_ALLOW_URL_FOPEN_ON}>{TR_ENABLED}</option>
                            </select>
                    </td>
            </tr>
           <!-- EDP: t_phpini_allow_url_fopen -->
           <!-- BDP: t_phpini_register_globals -->
           <tr>
                    <td style="width:300px;"><label for="phpini_register_globals">{TR_PHPINI_REGISTER_GLOBALS}</label></td>
                    <td>
                            <select name="phpini_register_globals" id="phpini_register_globals">
                                    <option value="off" {PHPINI_REGISTER_GLOBALS_OFF}>{TR_DISABLED}</option>
                                    <option value="on" {PHPINI_REGISTER_GLOBALS_ON}>{TR_ENABLED}</option>
                            </select>
                    </td>
            </tr>
           <!-- EDP: t_phpini_register_globals -->
           <!-- BDP: t_phpini_display_errors -->
           <tr>
                    <td style="width:300px;"><label for="phpini_display_errors">{TR_PHPINI_DISPLAY_ERRORS}</label></td>
                    <td>
                            <select name="phpini_display_errors" id="phpini_display_errors">
                                    <option value="off" {PHPINI_DISPLAY_ERRORS_OFF}>{TR_DISABLED}</option>
                                    <option value="on" {PHPINI_DISPLAY_ERRORS_ON}>{TR_ENABLED}</option>
                            </select>
                    </td>
            </tr>
            <tr>
                    <td><label for="phpini_error_reporting">{TR_PHPINI_ERROR_REPORTING}</label></td>
                    <td>
                            <select name="phpini_error_reporting" id="phpini_error_reporting">
                                    <option value="0" {PHPINI_ERROR_REPORTING_0}>{TR_PHPINI_ER_OFF}</option>
                                    <option value='E_ALL & ~E_NOTICE & ~E_WARNING' {PHPINI_ERROR_REPORTING_1}>{TR_PHPINI_ER_EALL_EXCEPT_NOTICE_EXCEPT_WARN}</option>
                                    <option value='E_ALL & ~E_NOTICE' {PHPINI_ERROR_REPORTING_2}>{TR_PHPINI_ER_EALL_EXCEPT_NOTICE}</option>
                                    <option value='E_ALL' {PHPINI_ERROR_REPORTING_3}>{TR_PHPINI_ER_EALL}</option>
                            </select>
                    </td>
            </tr>
           <!-- EDP: t_phpini_display_errors -->
           <!-- BDP: t_phpini_disable_functions -->
            <tr>
                    <td><label for="phpini_disable_functions">{TR_PHPINI_DISABLE_FUNCTIONS}</label></td>
                    <td>
                            <input name="phpini_df_show_source" id="phpini_df_show_source" type="checkbox" {PHPINI_DF_SHOW_SOURCE_CHK} value="show_source"/> show_source
                            <input name="phpini_df_system" id="phpini_df_system" type="checkbox" {PHPINI_DF_SYSTEM_CHK} value="system"/> system
                            <input name="phpini_df_shell_exec" id="phpini_df_shell_exec" type="checkbox" {PHPINI_DF_SHELL_EXEC_CHK} value="shell_exec"/> shell_exec
                            <input name="phpini_df_passthru" id="phpini_df_passthru" type="checkbox" {PHPINI_DF_PASSTHRU_CHK} value="passthru"/> passthru
                            <input name="phpini_df_exec" id="phpini_df_exec" type="checkbox" {PHPINI_DF_EXEC_CHK} value="exec"/> exec
                            <input name="phpini_df_phpinfo" id="phpini_df_phpinfo" type="checkbox" {PHPINI_DF_PHPINFO_CHK} value="phpinfo"/> phpinfo
                            <input name="phpini_df_shell" id="phpini_df_shell" type="checkbox" {PHPINI_DF_SHELL_CHK} value="shell"/> shell
                            <input name="phpini_df_symlink" id="phpini_df_symlink" type="checkbox" {PHPINI_DF_SYMLINK_CHK} value="symlink"/> symlink
                    </td>
            </tr>
           <!-- EDP: t_phpini_disable_functions -->
           <!-- BDP: t_phpini_disable_functions_exec -->
            <tr>
                    <td><label for="phpini_disable_functions_exec">{TR_PHPINI_DISABLE_FUNCTIONS_EXEC}</label><span class="icon i_help" id="exec_help">Help</span></td>
                    <td>
			    <select name="phpini_disable_functions_exec" id="phpini_disable_functions_exec">
                                    <option value="off" {PHPINI_DISABLE_FUNCTIONS_EXEC_OFF}>{TR_DISABLED}</option>
                                    <option value="on" {PHPINI_DISABLE_FUNCTIONS_EXEC_ON}>{TR_ENABLED}</option>
                            </select>
                    </td>
            </tr>
           <!-- EDP: t_phpini_disable_functions_exec -->
	</table>
        <div class="buttons">
	        <input name="Submit" type="submit" value="{TR_UPDATE_DATA}" />
        	<input name="Submit" type="submit" onclick="MM_goToURL('parent','domains_manage.php');return document.MM_returnValue" value="{TR_CANCEL}" />
	        <input type="hidden" name="uaction" value="phpini_save" />
        </div>
       </form>
       <!-- EDP: t_update_ok -->
        </div>

        <div class="footer">
            i-MSCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
        </div>

    </body>
</html>
         
