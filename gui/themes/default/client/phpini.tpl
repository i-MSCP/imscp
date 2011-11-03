<!-- INCLUDE "../shared/layout/header.tpl" -->
	<body>
		<!-- BDP: js_for_exec_help -->
		<script type="text/javascript">
			/*<![CDATA[*/
				$(document).ready(function() {
					$('#exec_help').iMSCPtooltips({msg:"{TR_PHP_INI_EXEC_HELP}"});
				});
			/*]]>*/
		</script>
		<!-- EDP: js_for_exec_help -->
		<div class="header">
			{MAIN_MENU}
			<div class="logo">
				<img src="{ISP_LOGO}" alt="i-MSCP logo" />
			</div>
		</div>

		<div class="location">
			<div class="location-area">
				<h1 class="domains">{TR_MENU_PHPINI}</h1>
			</div>
			<ul class="location-menu">
				<!-- BDP: logged_from -->
				<li>
					<a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a>
				</li>
				<!-- EDP: logged_from -->
				<li>
					<a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a>
				</li>
			</ul>
			<ul class="path">
				<li><a href="domains_manage.php">{TR_MENU_MANAGE_DOMAINS}</a></li>
				<li><a href="#" onclick="return false;">{TR_LMENU_PHP_DIRECTIVES_EDITOR}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<h2 class="domains"><span>{TR_TITLE}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<p class="hint" style="font-variant: small-caps;font-size: small;">
				{TR_PAGE_TEXT}
			</p>


			<form name="client_php_ini_edit_frm" method="post" action="phpini.php">
				<table>
					<tr>
						<th>PHP directive name</th>
						<th>PHP directive value</th>
					</tr>
					<!-- BDP: t_phpini_allow_url_fopen -->
					<tr>
						<td style="width:300px;">
							<label for="phpini_allow_url_fopen">{TR_PHPINI_ALLOW_URL_FOPEN}</label>
						</td>
						<td>
							<select name="phpini_allow_url_fopen" id="phpini_allow_url_fopen">
								<option value="Off"{PHPINI_ALLOW_URL_FOPEN_OFF}>{TR_VALUE_OFF}</option>
								<option value="On"{PHPINI_ALLOW_URL_FOPEN_ON}>{TR_VALUE_ON}</option>
							</select>
						</td>
					</tr>
					<!-- EDP: t_phpini_allow_url_fopen -->

					<!-- BDP: t_phpini_register_globals -->
					<tr>
						<td style="width:300px;">
							<label for="phpini_register_globals">{TR_PHPINI_REGISTER_GLOBALS}</label>
						</td>
						<td>
							<select name="phpini_register_globals" id="phpini_register_globals">
								<option value="Off"{PHPINI_REGISTER_GLOBALS_OFF}>{TR_VALUE_OFF}</option>
								<option value="On"{PHPINI_REGISTER_GLOBALS_ON}>{TR_VALUE_ON}</option>
							</select>
						</td>
					</tr>
					<!-- EDP: t_phpini_register_globals -->

					<!-- BDP: t_phpini_display_errors -->
					<tr>
						<td style="width:300px;">
							<label for="phpini_display_errors">{TR_PHPINI_DISPLAY_ERRORS}</label>
						</td>
						<td>
							<select name="phpini_display_errors" id="phpini_display_errors">
								<option value="Off"{PHPINI_DISPLAY_ERRORS_OFF}>{TR_VALUE_OFF}</option>
								<option value="On"{PHPINI_DISPLAY_ERRORS_ON}>{TR_VALUE_ON}</option>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<label for="phpini_error_reporting">{TR_PHPINI_ERROR_REPORTING}</label>
						</td>
						<td>
							<select name="phpini_error_reporting" id="phpini_error_reporting">
								<option value='E_ALL & ~E_NOTICE'{PHPINI_ERROR_REPORTING_0}>{TR_PHPINI_ERROR_REPORTING_DEFAULT}</option>
								<option value='E_ALL | E_STRICT'{PHPINI_ERROR_REPORTING_1}>{TR_PHPINI_ERROR_REPORTING_DEVELOPEMENT}</option>
								<option value='E_ALL & ~E_DEPRECATED'{PHPINI_ERROR_REPORTING_2}>{TR_PHPINI_ERROR_REPORTING_PRODUCTION}</option>
								<option value="0"{PHPINI_ERROR_REPORTING_3}>{TR_PHPINI_ERROR_REPORTING_NONE}</option>
							</select>
						</td>
					</tr>
					<!-- EDP: t_phpini_display_errors -->
					<!-- BDP: t_phpini_disable_functions -->
					<tr>
						<td>
							<label>{TR_PHPINI_DISABLE_FUNCTIONS}</label>
						</td>
						<td>
							<input name="phpini_df_show_source" id="phpini_df_show_source" type="checkbox" {PHPINI_DF_SHOW_SOURCE_CHK} value="show_source" />
							<label for="phpini_df_show_source">show_source</label>
							<input name="phpini_df_system" id="phpini_df_system" type="checkbox"{PHPINI_DF_SYSTEM_CHK} value="system" />
							<label for="phpini_df_system">system</label>
							<input name="phpini_df_shell_exec" id="phpini_df_shell_exec" type="checkbox"{PHPINI_DF_SHELL_EXEC_CHK} value="shell_exec" />
							<label for="phpini_df_shell_exec">shell_exec</label>
							<input name="phpini_df_passthru" id="phpini_df_passthru" type="checkbox"{PHPINI_DF_PASSTHRU_CHK} value="passthru" />
							<label for="phpini_df_passthru">passthru</label>
							<input name="phpini_df_exec" id="phpini_df_exec" type="checkbox"{PHPINI_DF_EXEC_CHK} value="exec" />
							<label for="phpini_df_exec">exec</label>
							<input name="phpini_df_phpinfo" id="phpini_df_phpinfo" type="checkbox"{PHPINI_DF_PHPINFO_CHK} value="phpinfo" />
							<label for="phpini_df_phpinfo">phpinfo</label>
							<input name="phpini_df_shell" id="phpini_df_shell" type="checkbox"{PHPINI_DF_SHELL_CHK} value="shell" />
							<label for="phpini_df_shell">shell</label>
							<input name="phpini_df_symlink" id="phpini_df_symlink" type="checkbox"{PHPINI_DF_SYMLINK_CHK} value="symlink" />
							<label for="phpini_df_symlink">symlink</label>
						</td>
					</tr>
					<!-- EDP: t_phpini_disable_functions -->
				</table>
				<br /><br />
				<!-- BDP: t_phpini_disable_functions_exec -->
				<table>
					<tr>
						<th style="width:300px;">Specific parameter name</th>
						<th>Specific parameter value</th>
					</tr>
					<tr>
						<td>
							<label for="phpini_disable_functions_exec">{TR_PHPINI_DISABLE_FUNCTIONS_EXEC}</label><span class="icon i_help" id="exec_help">Help</span>
						</td>
						<td>
							<select name="phpini_disable_functions_exec" id="phpini_disable_functions_exec">
								<option value="On"{PHPINI_DISABLE_FUNCTIONS_EXEC_ON}>{TR_DISALLOWS}</option>
								<option value="Off"{PHPINI_DISABLE_FUNCTIONS_EXEC_OFF}>{TR_ALLOWS}</option>
							</select>
						</td>
					</tr>
				</table>
				<!-- EDP: t_phpini_disable_functions_exec -->
				<div class="buttons">
					<input name="Submit" type="submit" value="{TR_UPDATE_DATA}" />
					<input name="Submit" type="submit" onclick="MM_goToURL('parent','domains_manage.php');return document.MM_returnValue" value="{TR_CANCEL}" />
					<input type="hidden" name="uaction" value="update" />
				</div>
			</form>
		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
