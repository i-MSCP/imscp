<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_EDIT_DOMAIN_PAGE_TITLE}</title>
		<meta name="robots" content="nofollow, noindex" />
		<link href="{THEME_COLOR_PATH}/css/imscp.css" rel="stylesheet" type="text/css" />
		<link href="{THEME_COLOR_PATH}/css/jquery.ui.datepicker.css" rel="stylesheet" type="text/css" />
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
		<script language="JavaScript" type="text/JavaScript">
		/*<![CDATA[*/
			$(document).ready(function(){
				$('#dmn_exp_help').iMSCPtooltips({msg:"{TR_DMN_EXP_HELP}"});
				// Tooltips - end
			});
		
			$(function() {
				$( "#datepicker" ).datepicker();
			});
			$(document).ready(function(){
				$('#datepicker').change(function() {
					if($(this).val() != '') {
						$('#neverexpire').attr('disabled', 'disabled')
					} else {
						$('#neverexpire').removeAttr('disabled');
					}
				});
			
				$('#neverexpire').change(function() {
					if($(this).is(':checked')) {
						$('#datepicker').attr('disabled', 'disabled')
					} else {
						$('#datepicker').removeAttr('disabled');
					}
				});		
			});
			$(document).ready(function() {
				if($('#phpini_system_no').is(':checked')) {
					$("#phpinidetail").hide();
					}
                   		$('#phpini_system_yes').click( function() {
                                	$("#phpinidetail").show();
                        	});
                        	$('#phpini_system_no').click( function() {
                                	$("#phpinidetail").hide();
                        	});
                	});
		/*]]>*/
		</script>
	<script type="text/javascript">
        	$(document).ready(function() {
			$('#phpini_system_yes').click( function() {
                		$("#phpinidetail").show();
				
			});
			$('#phpini_system_no').click( function() {
                                $("#phpinidetail").hide();
                                
                        });
            	});
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
				<h1 class="manage_users">{TR_MENU_MANAGE_USERS}</h1>
			</div>
			<ul class="location-menu">
				<!-- <li><a class="help" href="#">Help</a></li> -->
                <!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
                <!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="manage_users.php">{TR_EDIT_DOMAIN}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<h2 class="domains"><span>{TR_EDIT_DOMAIN}</span></h2>
			<form name="reseller_edit_domain_frm" method="post" action="domain_edit.php">
				<table>
					<tr>
						<td width="330*">{TR_DOMAIN_NAME}</td>
						<td>{VL_DOMAIN_NAME}</td>

					</tr>
					<tr>
						<td>{TR_DOMAIN_EXPIRE}</td>
						<td>{VL_DOMAIN_EXPIRE}</td>

					</tr>

					<tr>
						<td>{TR_DOMAIN_NEW_EXPIRE} <span class="icon i_help" id="dmn_exp_help">Help</span></td>
						<td>
							<div class="content">
								<input type="text" id="datepicker" name="dmn_expire_date" value="{VL_DOMAIN_EXPIRE_DATE}" {VL_DISABLED}> (MM/DD/YYYY) {TR_EXPIRE_CHECKBOX} <input type="checkbox" name="neverexpire" id="neverexpire" {VL_NEVEREXPIRE} {VL_DISABLED_NE} />
 						    </div>
						</td>
					</tr>
					<tr>
						<td>{TR_DOMAIN_IP}</td>
						<td>{VL_DOMAIN_IP}</td>
					</tr>
					<tr>
						<td>{TR_PHP_SUPP}</td>
						<td><select id="domain_php" name="domain_php">
								<option value="_yes_" {PHP_YES}>{TR_YES}</option>
								<option value="_no_" {PHP_NO}>{TR_NO}</option>
							</select>
						</td>
					</tr>
					<!-- BDP: t_software_support -->
					<tr>
						<td>{SW_ALLOWED}</td>
						<td><select name="domain_software_allowed" id="domain_software_allowed">
								<option value="yes" {SOFTWARE_YES}>{TR_YES}</option>
								<option value="no" {SOFTWARE_NO}>{TR_NO}</option>
 							</select>
						</td>
					</tr>
					<!-- EDP: t_software_support -->
					<tr>
						<td>{TR_CGI_SUPP}</td>
						<td><select id="domain_cgi" name="domain_cgi">
								<option value="_yes_" {CGI_YES}>{TR_YES}</option>
								<option value="_no_" {CGI_NO}>{TR_NO}</option>
							</select>
						</td>
					</tr>
					<tr>
						<td>{TR_DNS_SUPP}</td>
						<td><select id="domain_dns" name="domain_dns">
								<option value="_yes_" {DNS_YES}>{TR_YES}</option>
								<option value="_no_" {DNS_NO}>{TR_NO}</option>
							</select>
						</td>
					</tr>
					<tr>
						<td>{TR_BACKUP}</td>
						<td><select ide="backup" name="backup">
								<option value="_dmn_" {BACKUP_DOMAIN}>{TR_BACKUP_DOMAIN}</option>
								<option value="_sql_" {BACKUP_SQL}>{TR_BACKUP_SQL}</option>
								<option value="_full_" {BACKUP_FULL}>{TR_BACKUP_FULL}</option>
								<option value="_no_" {BACKUP_NO}>{TR_BACKUP_NO}</option>
							</select>
						</td>
					</tr>
					<tr>
						<td><label for="dom_sub">{TR_SUBDOMAINS}</label></td>
						<td><input type="text" name="dom_sub" id="dom_sub" value="{VL_DOM_SUB}"/></td>
					</tr>
					<tr>
						<td><label for="dom_alias">{TR_ALIAS}</label></td>
						<td><input type="text" name="dom_alias" id="dom_alias" value="{VL_DOM_ALIAS}"/></td>
					</tr>
					<tr>
						<td><label for="dom_mail_acCount">{TR_MAIL_ACCOUNT}</label></td>
						<td><input type="text" name="dom_mail_acCount" id="dom_mail_acCount" value="{VL_DOM_MAIL_ACCOUNT}"/></td>
					</tr>
					<tr>
						<td><label for="dom_ftp_acCounts">{TR_FTP_ACCOUNTS}</label></td>
						<td><input type="text" name="dom_ftp_acCounts" id="dom_ftp_acCounts" value="{VL_FTP_ACCOUNTS}"/></td>
					</tr>
					<tr>
						<td><label for="dom_sqldb">{TR_SQL_DB}</label></td>
						<td><input type="text" name="dom_sqldb" id="dom_sqldb" value="{VL_SQL_DB}"/></td>
					</tr>
					<tr>
						<td><label for="dom_sql_users">{TR_SQL_USERS}</label></td>
						<td><input type="text" name="dom_sql_users" id="dom_sql_users" value="{VL_SQL_USERS}"/></td>
					</tr>
					<tr>
						<td><label for="dom_traffic">{TR_TRAFFIC}</label></td>
						<td><input type="text" name="dom_traffic" id="dom_traffic" value="{VL_TRAFFIC}"/></td>
					</tr>
					<tr>
						<td><label for="dom_disk">{TR_DISK}</label></td>
						<td><input type="text" name="dom_disk" id="dom_disk" value="{VL_DOM_DISK}"/></td>
					</tr>
					<!-- BDP: t_phpini_system -->
		                         <tr>
        	                                <td>{TR_PHPINI_SYSTEM}</td>
                                                    <td>
                                                        <input type="radio" name="phpini_system" id="phpini_system_yes" value="yes" {PHPINI_SYSTEM_YES} />
                                                        <label for="phpini_system_yes">{TR_YES}</label>
                                                        <input type="radio" name="phpini_system" id="phpini_system_no" value="no" {PHPINI_SYSTEM_NO}  />
                                                        <label for="support_system_no">{TR_NO}</label>
	                                          </td>
                                        </tr>
					<!-- BDP: t_phpini_allow_url_fopen -->
				      <tbody id='phpinidetail'>
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
                                                                 <option value='E_ALL ^ (E_NOTICE | E_WARNING)' {PHPINI_ERROR_REPORTING_1}>{TR_PHPINI_ER_EALL_EXCEPT_NOTICE_EXCEPT_WARN}</option>
                                                                 <option value='E_ALL ^ E_NOTICE' {PHPINI_ERROR_REPORTING_2}>{TR_PHPINI_ER_EALL_EXCEPT_NOTICE}</option>
                                                                 <option value='E_ALL' {PHPINI_ERROR_REPORTING_3}>{TR_PHPINI_ER_EALL}</option>
                                                         </select>
                                                 </td>
                                         </tr>
					<!-- EDP: t_phpini_display_errors -->
                                         <tr>
                                                 <td><label for="phpini_post_max_size">{TR_PHPINI_POST_MAX_SIZE}</label></td>
                                                 <td>
                                                         <input name="phpini_post_max_size" id="phpini_post_max_size" type="text" value="{PHPINI_POST_MAX_SIZE}" /> 
                                                 </td>
                                         </tr>

                                         <tr>
                                                 <td><label for="phpini_upload_max_filesize">{TR_PHPINI_UPLOAD_MAX_FILESIZE}</label></td>
                                                 <td>
                                                         <input name="phpini_upload_max_filesize" id="phpini_upload_max_filesize" type="text" value="{PHPINI_UPLOAD_MAX_FILESIZE}" /> 
                                                 </td>
                                         </tr>
                                         <tr>
                                                 <td><label for="phpini_max_execution_time">{TR_PHPINI_MAX_EXECUTION_TIME}</label></td>
                                                 <td>
                                                         <input name="phpini_max_execution_time" id="phpini_max_execution_time" type="text" value="{PHPINI_MAX_EXECUTION_TIME}" /> 
                                                 </td>
                                         </tr>
                                         <tr>
                                                 <td><label for="phpini_max_input_time">{TR_PHPINI_MAX_INPUT_TIME}</label></td>
                                                 <td>
                                                         <input name="phpini_max_input_time" id="phpini_max_input_time" type="text" value="{PHPINI_MAX_INPUT_TIME}" /> 
                                                 </td>
                                         </tr>
                                         <tr>
                                                 <td><label for="phpini_memory_limit">{TR_PHPINI_MEMORY_LIMIT}</label></td>
                                                 <td>
                                                         <input name="phpini_memory_limit" id="phpini_memory_limit" type="text" value="{PHPINI_MEMORY_LIMIT}" /> 
                                                 </td>
                                         </tr>
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
					</tbody>
					<!-- EDP: t_phpini_disable_functions -->
                                        <!-- EDP: t_phpini_system -->
				</table>
				<div class="buttons">
					<input name="Submit" type="submit" value="{TR_UPDATE_DATA}" />
					<input name="Submit" type="submit" onclick="MM_goToURL('parent','users.php');return document.MM_returnValue" value="{TR_CANCEL}" />
					<input type="hidden" name="uaction" value="sub_data" />
				</div>
			</form>
		</div>

		<div class="footer">
			i-MSCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
		</div>

	</body>
</html>
