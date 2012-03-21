			<script type="text/javascript">
			/* <![CDATA[ */
			$(document).ready(function(){
				$('#bruteforce').change(function(){
					($(this).val() == '1') ? $('.display').show() : $('.display').hide();
				}).trigger('change');
			});
			/*]]>*/
			</script>
			<form action="settings.php" method="post" name="frmsettings" id="frmsettings">
				<fieldset>
					<legend>{TR_UPDATES}</legend>
					<table class="firstColFixed">
						<tr>
							<td><label for="checkforupdate">{TR_CHECK_FOR_UPDATES}</label></td>
							<td>
								<select name="checkforupdate" id="checkforupdate">
									<option value="0"{CHECK_FOR_UPDATES_SELECTED_OFF}>{TR_DISABLED}</option>
									<option value="1"{CHECK_FOR_UPDATES_SELECTED_ON}>{TR_ENABLED}</option>
								</select>
							</td>
						</tr>
					</table>
				</fieldset>

				<fieldset>
					<legend>{TR_LOSTPASSWORD}</legend>
					<table class="firstColFixed">
						<tr>
							<td><label for="lostpassword">{TR_LOSTPASSWORD}</label></td>
							<td>
								<select name="lostpassword" id="lostpassword">
									<option value="0" {LOSTPASSWORD_SELECTED_OFF}>{TR_DISABLED}</option>
									<option value="1" {LOSTPASSWORD_SELECTED_ON}>{TR_ENABLED}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="lostpassword_timeout">{TR_LOSTPASSWORD_TIMEOUT}</label></td>
							<td>
								<input type="text" name="lostpassword_timeout" id="lostpassword_timeout" value="{LOSTPASSWORD_TIMEOUT_VALUE}"/>
							</td>
						</tr>
					</table>
				</fieldset>

				<fieldset>
					<legend>{TR_PASSWORD_SETTINGS}</legend>
					<table class="firstColFixed">
						<tr>
							<td><label for="passwd_strong">{TR_PASSWD_STRONG}</label></td>
							<td>
								<select name="passwd_strong" id="passwd_strong">
									<option value="0" {PASSWD_STRONG_OFF}>{TR_DISABLED}</option>
									<option value="1" {PASSWD_STRONG_ON}>{TR_ENABLED}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="passwd_chars">{TR_PASSWD_CHARS}</label></td>
							<td>
								<input type="text" name="passwd_chars" id="passwd_chars" value="{PASSWD_CHARS}" maxlength="2"/>
							</td>
						</tr>
					</table>
				</fieldset>

				<fieldset>
					<legend>{TR_BRUTEFORCE}</legend>
					<table class="firstColFixed">
						<tr>
							<td><label for="bruteforce">{TR_BRUTEFORCE}</label></td>
							<td>
								<select name="bruteforce" id="bruteforce">
									<option value="0" {BRUTEFORCE_SELECTED_OFF}>{TR_DISABLED}</option>
									<option value="1" {BRUTEFORCE_SELECTED_ON}>{TR_ENABLED}</option>
								</select>
							</td>
						</tr>
						<tr class="display">
							<td><label for="bruteforce_between">{TR_BRUTEFORCE_BETWEEN}</label></td>
							<td>
								<select name="bruteforce_between" id="bruteforce_between">
									<option value="0" {BRUTEFORCE_BETWEEN_SELECTED_OFF}>{TR_DISABLED}</option>
									<option value="1" {BRUTEFORCE_BETWEEN_SELECTED_ON}>{TR_ENABLED}</option>
								</select>
							</td>
						</tr>
						<tr class="display">
							<td><label for="bruteforce_max_login">{TR_BRUTEFORCE_MAX_LOGIN}</label></td>
							<td>
								<input type="text" name="bruteforce_max_login" id="bruteforce_max_login" value="{BRUTEFORCE_MAX_LOGIN_VALUE}" maxlength="3"/>
							</td>
						</tr>
						<tr class="display">
							<td><label for="bruteforce_block_time">{TR_BRUTEFORCE_BLOCK_TIME}</label></td>
							<td>
								<input name="bruteforce_block_time" id="bruteforce_block_time" type="text" value="{BRUTEFORCE_BLOCK_TIME_VALUE}" maxlength="3"/>
							</td>
						</tr>
						<tr class="display">
							<td><label for="bruteforce_between_time">{TR_BRUTEFORCE_BETWEEN_TIME}</label></td>
							<td>
								<input name="bruteforce_between_time" id="bruteforce_between_time" type="text" value="{BRUTEFORCE_BETWEEN_TIME_VALUE}" maxlength="3"/>
							</td>
						</tr>
						<tr class="display">
							<td><label for="bruteforce_max_capcha">{TR_BRUTEFORCE_MAX_CAPTCHA}</label></td>
							<td>
								<input name="bruteforce_max_capcha" id="bruteforce_max_capcha" type="text" value="{BRUTEFORCE_MAX_CAPTCHA}" maxlength="3"/>
							</td>
						</tr>
					</table>
				</fieldset>

				<fieldset>
					<legend>{TR_DNAMES_VALIDATION_SETTINGS}</legend>
					<table class="firstColFixed">
						<tr>
							<td>
								<label for="tld_strict_validation">{TR_TLD_STRICT_VALIDATION}</label>
								<span class="icon i_help" title="{TR_TLD_STRICT_VALIDATION_HELP}">{TR_HELP}</span>
							</td>
							<td>
								<select name="tld_strict_validation" id="tld_strict_validation">
									<option value="0" {TLD_STRICT_VALIDATION_OFF}>{TR_DISABLED}</option>
									<option value="1" {TLD_STRICT_VALIDATION_ON}>{TR_ENABLED}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td>
								<label for="sld_strict_validation">{TR_SLD_STRICT_VALIDATION}</label>
								<span class="icon i_help" title="{TR_SLD_STRICT_VALIDATION_HELP}">{TR_HELP}</span>
							</td>
							<td>
								<select name="sld_strict_validation" id="sld_strict_validation">
									<option value="0" {SLD_STRICT_VALIDATION_OFF}>{TR_DISABLED}</option>
									<option value="1" {SLD_STRICT_VALIDATION_ON}>{TR_ENABLED}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="max_dnames_labels">{TR_MAX_DNAMES_LABELS}</label></td>
							<td>
								<input name="max_dnames_labels" id="max_dnames_labels" type="text" value="{MAX_DNAMES_LABELS_VALUE}" maxlength="2"/>
							</td>
						</tr>
						<tr>
							<td><label for="max_subdnames_labels">{TR_MAX_SUBDNAMES_LABELS}</label></td>
							<td>
								<input name="max_subdnames_labels" id="max_subdnames_labels" type="text" value="{MAX_SUBDNAMES_LABELS_VALUE}" maxlength="2"/>
							</td>
						</tr>
					</table>
				</fieldset>

				<fieldset>
					<legend>{TR_MAIL_SETTINGS}</legend>
					<table class="firstColFixed">
						<tr>
							<td><label for="create_default_email_addresses">{TR_CREATE_DEFAULT_EMAIL_ADDRESSES}</label></td>
							<td>
								<select name="create_default_email_addresses" id="create_default_email_addresses">
									<option value="0" {CREATE_DEFAULT_EMAIL_ADDRESSES_OFF}>{TR_DISABLED}</option>
									<option value="1" {CREATE_DEFAULT_EMAIL_ADDRESSES_ON}>{TR_ENABLED}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="count_default_email_addresses">{TR_COUNT_DEFAULT_EMAIL_ADDRESSES}</label></td>
							<td>
								<select name="count_default_email_addresses" id="count_default_email_addresses">
									<option value="0" {COUNT_DEFAULT_EMAIL_ADDRESSES_OFF}>{TR_DISABLED}</option>
									<option value="1" {COUNT_DEFAULT_EMAIL_ADDRESSES_ON}>{TR_ENABLED}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="hard_mail_suspension">{TR_HARD_MAIL_SUSPENSION}</label></td>
							<td>
								<select name="hard_mail_suspension" id="hard_mail_suspension">
									<option value="0" {HARD_MAIL_SUSPENSION_OFF}>{TR_DISABLED}</option>
									<option value="1" {HARD_MAIL_SUSPENSION_ON}>{TR_ENABLED}</option>
								</select>
							</td>
						</tr>
					</table>
				</fieldset>

				<fieldset>
					<legend>{TR_PHPINI_BASE_SETTINGS}</legend>
					<table class="firstColFixed">
						<tr>
							<td><label for="phpini_allow_url_fopen">{TR_PHPINI_ALLOW_URL_FOPEN}</label></td>
							<td>
								<select name="phpini_allow_url_fopen" id="phpini_allow_url_fopen">
									<option value="Off" {PHPINI_ALLOW_URL_FOPEN_OFF}>{TR_DISABLED}</option>
									<option value="On" {PHPINI_ALLOW_URL_FOPEN_ON}>{TR_ENABLED}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="phpini_register_globals">{TR_PHPINI_REGISTER_GLOBALS}</label></td>
							<td>
								<select name="phpini_register_globals" id="phpini_register_globals">
									<option value="Off" {PHPINI_REGISTER_GLOBALS_OFF}>{TR_DISABLED}</option>
									<option value="On" {PHPINI_REGISTER_GLOBALS_ON}>{TR_ENABLED}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="phpini_display_errors">{TR_PHPINI_DISPLAY_ERRORS}</label></td>
							<td>
								<select name="phpini_display_errors" id="phpini_display_errors">
									<option value="Off" {PHPINI_DISPLAY_ERRORS_OFF}>{TR_DISABLED}</option>
									<option value="On" {PHPINI_DISPLAY_ERRORS_ON}>{TR_ENABLED}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="phpini_error_reporting">{TR_PHPINI_ERROR_REPORTING}</label></td>
							<td>
								<select name="phpini_error_reporting" id="phpini_error_reporting">
									<option value="E_ALL &amp; ~E_NOTICE" {PHPINI_ERROR_REPORTING_0}>{TR_PHPINI_ERROR_REPORTING_DEFAULT}</option>
									<option value="E_ALL | E_STRICT" {PHPINI_ERROR_REPORTING_1}>{TR_PHPINI_ERROR_REPORTING_DEVELOPEMENT}</option>
									<option value="E_ALL &amp; ~E_DEPRECATED" {PHPINI_ERROR_REPORTING_2}>{TR_PHPINI_ERROR_REPORTING_PRODUCTION}</option>
									<option value="0" {PHPINI_ERROR_REPORTING_3}>{TR_PHPINI_ERROR_REPORTING_NONE}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="phpini_post_max_size">{TR_PHPINI_POST_MAX_SIZE}</label></td>
							<td>
								<input name="phpini_post_max_size" id="phpini_post_max_size" type="text" value="{PHPINI_POST_MAX_SIZE}"/> {TR_MIB}
							</td>
						</tr>
						<tr>
							<td><label for="phpini_upload_max_filesize">{TR_PHPINI_UPLOAD_MAX_FILESIZE}</label></td>
							<td>
								<input name="phpini_upload_max_filesize" id="phpini_upload_max_filesize" type="text" value="{PHPINI_UPLOAD_MAX_FILESIZE}"/> {TR_MIB}
							</td>
						</tr>
						<tr>
							<td><label for="phpini_max_execution_time">{TR_PHPINI_MAX_EXECUTION_TIME}</label></td>
							<td>
								<input name="phpini_max_execution_time" id="phpini_max_execution_time" type="text" value="{PHPINI_MAX_EXECUTION_TIME}"/> {TR_SEC}
							</td>
						</tr>
						<tr>
							<td><label for="phpini_max_input_time">{TR_PHPINI_MAX_INPUT_TIME}</label></td>
							<td>
								<input name="phpini_max_input_time" id="phpini_max_input_time" type="text" value="{PHPINI_MAX_INPUT_TIME}"/> {TR_SEC}
							</td>
						</tr>
						<tr>
							<td><label for="phpini_memory_limit">{TR_PHPINI_MEMORY_LIMIT}</label></td>
							<td>
								<input name="phpini_memory_limit" id="phpini_memory_limit" type="text" value="{PHPINI_MEMORY_LIMIT}"/> {TR_MIB}
							</td>
						</tr>
						<tr>
							<td><label for="phpini_open_basedir">{TR_PHPINI_OPEN_BASEDIR}</label>
								<span class="icon i_help" title={TR_PHPINI_OPEN_BASEDIR_TOOLTIP}></span></td>
							<td>
								<input name="phpini_open_basedir" id="phpini_open_basedir" type="text" value="{PHPINI_OPEN_BASEDIR}"/>
							</td>
						</tr>
						<!-- BDP: php_editor_disable_functions_block -->
						<tr>
							<td><label>{TR_PHPINI_DISABLE_FUNCTIONS}</label></td>
							<td>
								<div class="radio">
									<input name="show_source" id="show_source" type="checkbox" {SHOW_SOURCE} value="show_source"/>
									<label for="show_source">show_source</label>
									<input name="system" id="system" type="checkbox" {SYSTEM} value="system"/>
									<label for="system">system</label>
									<input name="shell_exec" id="shell_exec" type="checkbox" {SHELL_EXEC} value="shell_exec"/>
									<label for="shell_exec">shell_exec</label>
									<input name="passthru" id="passthru" type="checkbox" {PASSTHRU} value="passthru"/>
									<label for="passthru">passthru</label>
									<input name="exec" id="exec" type="checkbox" {EXEC} value="exec"/>
									<label for="exec">exec</label>
									<input name="phpinfo" id="phpinfo" type="checkbox" {PHPINFO} value="phpinfo"/>
									<label for="phpinfo">phpinfo</label>
									<input name="shell" id="shell" type="checkbox" {SHELL} value="shell"/>
									<label for="shell">shell</label>
									<input name="symlink" id="symlink" type="checkbox" {SYMLINK} value="symlink"/>
									<label for="symlink">symlink</label>
								</div>
							</td>
						</tr>
						<!-- EDP: php_editor_disable_functions_block -->
					</table>
				</fieldset>

				<fieldset>
					<legend>{TR_ORDERS_SETTINGS}</legend>
					<table class="firstColFixed">
						<tr>
							<td><label for="ordersExpireTime">{TR_ORDERS_EXPIRE_TIME}</label></td>
							<td>
								<input type="text" name="ordersExpireTime" id="ordersExpireTime" value="{ORDERS_EXPIRATION_TIME_VALUE}"/>
							</td>
						</tr>
						<tr>
							<td><label for="coid">{TR_CUSTOM_ORDERPANEL_ID}</label></td>
							<td><input type="text" name="coid" id="coid" value="{CUSTOM_ORDERPANEL_ID}"/></td>
						</tr>
					</table>
				</fieldset>

				<fieldset>
					<legend>{TR_OTHER_SETTINGS}</legend>
					<table class="firstColFixed">
						<tr>
							<td><label for="def_language">{TR_USER_INITIAL_LANG}</label></td>
							<td>
								<select name="def_language" id="def_language">
									<!-- BDP: def_language -->
									<option value="{LANG_VALUE}"{LANG_SELECTED}>{LANG_NAME}</option>
									<!-- EDP: def_language -->
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="support_system">{TR_SUPPORT_SYSTEM}</label></td>
							<td>
								<select name="support_system" id="support_system">
									<option value="0" {SUPPORT_SYSTEM_SELECTED_OFF}>{TR_DISABLED}</option>
									<option value="1" {SUPPORT_SYSTEM_SELECTED_ON}>{TR_ENABLED}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="compress_output">{TR_COMPRESS_OUTPUT}</label></td>
							<td>
								<select name="compress_output" id="compress_output">
									<option value="0" {COMPRESS_OUTPUT_OFF}>{TR_DISABLED}</option>
									<option value="1" {COMPRESS_OUTPUT_ON}>{TR_ENABLED}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="show_compression_size">{TR_SHOW_COMPRESSION_SIZE}</label></td>
							<td>
								<select name="show_compression_size" id="show_compression_size">
									<option value="0" {SHOW_COMPRESSION_SIZE_SELECTED_OFF}>{TR_DISABLED}</option>
									<option value="1" {SHOW_COMPRESSION_SIZE_SELECTED_ON}>{TR_ENABLED}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="hosting_plan_level">{TR_HOSTING_PLANS_LEVEL}</label></td>
							<td class="content">
								<select name="hosting_plan_level" id="hosting_plan_level">
									<option value="admin" {HOSTING_PLANS_LEVEL_ADMIN}>{TR_ADMIN}</option>
									<option value="reseller" {HOSTING_PLANS_LEVEL_RESELLER}>{TR_RESELLER}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="domain_rows_per_page">{TR_DOMAIN_ROWS_PER_PAGE}</label></td>
							<td>
								<input name="domain_rows_per_page" id="domain_rows_per_page" type="text" value="{DOMAIN_ROWS_PER_PAGE}" maxlength="3"/>
							</td>
						</tr>
						<tr>
							<td><label for="log_level">{TR_LOG_LEVEL}</label></td>
							<td>
								<select name="log_level" id="log_level">
									<option value="E_USER_OFF" {LOG_LEVEL_SELECTED_OFF}>{TR_E_USER_OFF}</option>
									<option value="E_USER_ERROR" {LOG_LEVEL_SELECTED_ERROR}>{TR_E_USER_ERROR}</option>
									<option value="E_USER_WARNING" {LOG_LEVEL_SELECTED_WARNING}>{TR_E_USER_WARNING}</option>
									<option value="E_USER_NOTICE" {LOG_LEVEL_SELECTED_NOTICE}>{TR_E_USER_NOTICE}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="prevent_external_login_admin">{TR_PREVENT_EXTERNAL_LOGIN_ADMIN}</label></td>
							<td>
								<select name="prevent_external_login_admin" id="prevent_external_login_admin">
									<option value="0" {PREVENT_EXTERNAL_LOGIN_ADMIN_SELECTED_OFF}>{TR_DISABLED}</option>
									<option value="1" {PREVENT_EXTERNAL_LOGIN_ADMIN_SELECTED_ON}>{TR_ENABLED}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="prevent_external_login_reseller">{TR_PREVENT_EXTERNAL_LOGIN_RESELLER}</label>
							</td>
							<td>
								<select name="prevent_external_login_reseller" id="prevent_external_login_reseller">
									<option value="0" {PREVENT_EXTERNAL_LOGIN_RESELLER_SELECTED_OFF}>{TR_DISABLED}</option>
									<option value="1" {PREVENT_EXTERNAL_LOGIN_RESELLER_SELECTED_ON}>{TR_ENABLED}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="prevent_external_login_client">{TR_PREVENT_EXTERNAL_LOGIN_CLIENT}</label>
							</td>
							<td>
								<select name="prevent_external_login_client" id="prevent_external_login_client">
									<option value="0" {PREVENT_EXTERNAL_LOGIN_CLIENT_SELECTED_OFF}>{TR_DISABLED}</option>
									<option value="1" {PREVENT_EXTERNAL_LOGIN_CLIENT_SELECTED_ON}>{TR_ENABLED}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td>
								<label for="enableSSL">{TR_ENABLE_SSL}</label>
								<span class="icon i_help" title="{TR_SSL_HELP}">{TR_HELP}</span>
							</td>
							<td>
								<select name="enableSSL" id="enableSSL">
									<option value="0"{ENABLE_SSL_OFF}>{TR_DISABLED}</option>
									<option value="1"{ENABLE_SSL_ON}>{TR_ENABLED}</option>
								</select>
							</td>
						</tr>
					</table>
				</fieldset>

				<div class="buttons">
					<input name="Submit" type="submit" class="button" value="{TR_APPLY_CHANGES}"/>
					<input type="hidden" name="uaction" value="apply"/>
				</div>
			</form>
