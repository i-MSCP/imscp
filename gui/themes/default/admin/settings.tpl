
<script type="text/javascript">
	/* <![CDATA[ */
	$(document).ready(function () {
		$('#bruteforce').change(function () {
			($(this).val() == '1') ? $('.display').show() : $('.display').hide();
		}).trigger('change');

		$(".accordion").accordion({
			heightStyle: "content",
			collapsible: true
		});
	});
	/*]]>*/
</script>

<form action="settings.php" method="post" name="frmsettings" id="frmsettings">
<div class="accordion">
<h1><strong>{TR_UPDATES}</strong></h1>
<div style="padding: 0">
	<div class="odd">
		<div class="left"><label for="checkforupdate">{TR_CHECK_FOR_UPDATES}</label></div>
		<div class="right">
			<select name="checkforupdate" id="checkforupdate">
				<option value="0"{CHECK_FOR_UPDATES_SELECTED_OFF}>{TR_DISABLED}</option>
				<option value="1"{CHECK_FOR_UPDATES_SELECTED_ON}>{TR_ENABLED}</option>
			</select>
		</div>
	</div>
</div>
<h1><strong>{TR_LOSTPASSWORD}</strong></h1>

<div style="padding: 0">
	<div class="odd">
		<div class="left"><label for="lostpassword">{TR_LOSTPASSWORD}</label></div>
		<div class="right">
			<select name="lostpassword" id="lostpassword">
				<option value="0" {LOSTPASSWORD_SELECTED_OFF}>{TR_DISABLED}</option>
				<option value="1" {LOSTPASSWORD_SELECTED_ON}>{TR_ENABLED}</option>
			</select>
		</div>
	</div>
	<div class="even">
		<div class="left">
			<label for="lostpassword_timeout">{TR_LOSTPASSWORD_TIMEOUT}</label>
		</div>
		<div class="right">
			<input type="text" name="lostpassword_timeout" id="lostpassword_timeout"
				   value="{LOSTPASSWORD_TIMEOUT_VALUE}"/>
		</div>
	</div>
</div>
<h1><strong>{TR_PASSWORD_SETTINGS}</strong></h1>

<div style="padding: 0">
	<div class="odd">
		<div class="left"><label for="passwd_strong">{TR_PASSWD_STRONG}</label></div>
		<div class="right">
			<select name="passwd_strong" id="passwd_strong">
				<option value="0" {PASSWD_STRONG_OFF}>{TR_DISABLED}</option>
				<option value="1" {PASSWD_STRONG_ON}>{TR_ENABLED}</option>
			</select>
		</div>
		<div class="even" style="width: 100%">
			<div class="left"><label for="passwd_chars">{TR_PASSWD_CHARS}</label></div>
			<div class="right">
				<input type="text" name="passwd_chars" id="passwd_chars" value="{PASSWD_CHARS}" maxlength="2"/>
			</div>
		</div>
	</div>
</div>
<h1><strong>{TR_BRUTEFORCE}</strong></h1>

<div style="padding: 0">
	<div class="odd">
		<div class="left"><label for="bruteforce">{TR_BRUTEFORCE}</label></div>
		<div class="right">
			<select name="bruteforce" id="bruteforce">
				<option value="0" {BRUTEFORCE_SELECTED_OFF}>{TR_DISABLED}</option>
				<option value="1" {BRUTEFORCE_SELECTED_ON}>{TR_ENABLED}</option>
			</select>
		</div>
	</div>
	<div class="even">
		<div class="left"><label for="bruteforce_between">{TR_BRUTEFORCE_BETWEEN}</label></div>
		<div class="right">
			<select name="bruteforce_between" id="bruteforce_between">
				<option value="0" {BRUTEFORCE_BETWEEN_SELECTED_OFF}>{TR_DISABLED}</option>
				<option value="1" {BRUTEFORCE_BETWEEN_SELECTED_ON}>{TR_ENABLED}</option>
			</select>
		</div>
	</div>
	<div class="odd">
		<div class="left"><label for="bruteforce_max_login">{TR_BRUTEFORCE_MAX_LOGIN}</label></div>
		<div class="right">
			<input type="text" name="bruteforce_max_login" id="bruteforce_max_login"
				   value="{BRUTEFORCE_MAX_LOGIN_VALUE}" maxlength="3"/>
		</div>
	</div>
	<div class="even">
		<div class="left"><label for="bruteforce_block_time">{TR_BRUTEFORCE_BLOCK_TIME}</label></div>
		<div class="right">
			<input name="bruteforce_block_time" id="bruteforce_block_time" type="text"
				   value="{BRUTEFORCE_BLOCK_TIME_VALUE}" maxlength="3"/>
		</div>
	</div>
	<div class="odd">
		<div class="left"><label for="bruteforce_between_time">{TR_BRUTEFORCE_BETWEEN_TIME}</label></div>
		<div class="right">
			<input name="bruteforce_between_time" id="bruteforce_between_time" type="text"
				   value="{BRUTEFORCE_BETWEEN_TIME_VALUE}" maxlength="3"/>
		</div>
	</div>
	<div class="even">
		<div class="left">
			<label for="bruteforce_max_attempts_before_wait">{TR_BRUTEFORCE_MAX_ATTEMPTS_BEFORE_WAIT}</label>
		</div>
		<div class="right">
			<input name="bruteforce_max_attempts_before_wait" id="bruteforce_max_attempts_before_wait" type="text"
				   value="{BRUTEFORCE_MAX_ATTEMPTS_BEFORE_WAIT}" maxlength="3"/>
		</div>
	</div>
	<div class="odd">
		<div class="left"><label for="bruteforce_max_capcha">{TR_BRUTEFORCE_MAX_CAPTCHA}</label></div>
		<div class="right">
			<input name="bruteforce_max_capcha" id="bruteforce_max_capcha" type="text" value="{BRUTEFORCE_MAX_CAPTCHA}"
				   maxlength="3"/>
		</div>
	</div>
</div>

<h1><strong>{TR_MAIL_SETTINGS}</strong></h1>

<div style="padding: 0">
	<div class="odd">
		<div class="left"><label for="create_default_email_addresses">{TR_CREATE_DEFAULT_EMAIL_ADDRESSES}</label></div>
		<div class="right">
			<select name="create_default_email_addresses" id="create_default_email_addresses">
				<option value="0" {CREATE_DEFAULT_EMAIL_ADDRESSES_OFF}>{TR_DISABLED}</option>
				<option value="1" {CREATE_DEFAULT_EMAIL_ADDRESSES_ON}>{TR_ENABLED}</option>
			</select>
		</div>
	</div>
	<div class="even">
		<div class="left"><label for="count_default_email_addresses">{TR_COUNT_DEFAULT_EMAIL_ADDRESSES}</label></div>
		<div class="right">
			<select name="count_default_email_addresses" id="count_default_email_addresses">
				<option value="0" {COUNT_DEFAULT_EMAIL_ADDRESSES_OFF}>{TR_DISABLED}</option>
				<option value="1" {COUNT_DEFAULT_EMAIL_ADDRESSES_ON}>{TR_ENABLED}</option>
			</select>
		</div>
	</div>
	<div class="odd">
		<div class="left"><label for="hard_mail_suspension">{TR_HARD_MAIL_SUSPENSION}</label></div>
		<div class="right">
			<select name="hard_mail_suspension" id="hard_mail_suspension">
				<option value="0" {HARD_MAIL_SUSPENSION_OFF}>{TR_DISABLED}</option>
				<option value="1" {HARD_MAIL_SUSPENSION_ON}>{TR_ENABLED}</option>
			</select>
		</div>
	</div>
	<div class="even">
		<div class="left"><label for="email_quota_sync_mode">{TR_EMAIL_QUOTA_SYNC_MODE}</label></div>
		<div class="right">
			<select name="email_quota_sync_mode" id="email_quota_sync_mode">
				<option value="0" {REDISTRIBUTE_EMAIl_QUOTA_NO}>{TR_NO}</option>
				<option value="1" {REDISTRIBUTE_EMAIl_QUOTA_YES}>{TR_YES}</option>
			</select>
		</div>
	</div>
</div>
<h1><strong>{TR_PHPINI_BASE_SETTINGS}</strong></h1>

<div style="padding: 0">
	<div class="odd">
		<div class="left"><label for="phpini_allow_url_fopen">{TR_PHPINI_ALLOW_URL_FOPEN}</label></div>
		<div class="right">
			<select name="phpini_allow_url_fopen" id="phpini_allow_url_fopen">
				<option value="off" {PHPINI_ALLOW_URL_FOPEN_OFF}>{TR_DISABLED}</option>
				<option value="on" {PHPINI_ALLOW_URL_FOPEN_ON}>{TR_ENABLED}</option>
			</select>
		</div>
	</div>
	<div class="even">
		<div class="left"><label for="phpini_display_errors">{TR_PHPINI_DISPLAY_ERRORS}</label></div>
		<div class="right">
			<select name="phpini_display_errors" id="phpini_display_errors">
				<option value="off" {PHPINI_DISPLAY_ERRORS_OFF}>{TR_DISABLED}</option>
				<option value="on" {PHPINI_DISPLAY_ERRORS_ON}>{TR_ENABLED}</option>
			</select>
		</div>
	</div>
	<div class="odd">
		<div class="left"><label for="phpini_error_reporting">{TR_PHPINI_ERROR_REPORTING}</label></div>
		<div class="right">
			<select name="phpini_error_reporting" id="phpini_error_reporting">
				<option
					value="E_ALL &amp; ~E_NOTICE" {PHPINI_ERROR_REPORTING_0}>{TR_PHPINI_ERROR_REPORTING_DEFAULT}</option>
				<option
					value="E_ALL | E_STRICT" {PHPINI_ERROR_REPORTING_1}>{TR_PHPINI_ERROR_REPORTING_DEVELOPEMENT}</option>
				<option
					value="E_ALL &amp; ~E_DEPRECATED" {PHPINI_ERROR_REPORTING_2}>{TR_PHPINI_ERROR_REPORTING_PRODUCTION}</option>
				<option value="0" {PHPINI_ERROR_REPORTING_3}>{TR_PHPINI_ERROR_REPORTING_NONE}</option>
			</select>
		</div>
	</div>
	<div class="even">
		<div class="left"><label for="phpini_post_max_size">{TR_PHPINI_POST_MAX_SIZE}</label></div>
		<div class="right">
			<input name="phpini_post_max_size" id="phpini_post_max_size" type="text"
				   value="{PHPINI_POST_MAX_SIZE}"/> {TR_MIB}
		</div>
	</div>
	<div class="odd">
		<div class="left"><label for="phpini_upload_max_filesize">{TR_PHPINI_UPLOAD_MAX_FILESIZE}</label>
		</div>
		<div class="right">
			<input name="phpini_upload_max_filesize" id="phpini_upload_max_filesize" type="text"
				   value="{PHPINI_UPLOAD_MAX_FILESIZE}"/> {TR_MIB}
		</div>
	</div>
	<div class="even">
		<div class="left">
			<label for="phpini_max_execution_time">{TR_PHPINI_MAX_EXECUTION_TIME}</label>
		</div>
		<div class="right">
			<input name="phpini_max_execution_time" id="phpini_max_execution_time" type="text"
				   value="{PHPINI_MAX_EXECUTION_TIME}"/> {TR_SEC}
		</div>
	</div>
	<div class="odd">
		<div class="left"><label for="phpini_max_input_time">{TR_PHPINI_MAX_INPUT_TIME}</label></div>
		<div class="right">
			<input name="phpini_max_input_time" id="phpini_max_input_time" type="text"
				   value="{PHPINI_MAX_INPUT_TIME}"/> {TR_SEC}
		</div>
	</div>
	<div class="even">
		<div class="left"><label for="phpini_memory_limit">{TR_PHPINI_MEMORY_LIMIT}</label></div>
		<div class="right">
			<input name="phpini_memory_limit" id="phpini_memory_limit" type="text"
				   value="{PHPINI_MEMORY_LIMIT}"/> {TR_MIB}
		</div>
	</div>
	<div class="odd">
		<div class="left">
			<label for="phpini_open_basedir">{TR_PHPINI_OPEN_BASEDIR}</label>
			<span class="tips icon i_help" title={TR_PHPINI_OPEN_BASEDIR_TOOLTIP}></span>
		</div>
		<div class="right">
			<input name="phpini_open_basedir" id="phpini_open_basedir" type="text" value="{PHPINI_OPEN_BASEDIR}"/>
		</div>
	</div>
	<!-- BDP: php_editor_disable_functions_block -->
	<div class="even">
		<div class="left"><label>{TR_PHPINI_DISABLE_FUNCTIONS}</label></div>
		<div class="right">
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
				<input name="proc_open" id="proc_open" type="checkbox"{PROC_OPEN} value="proc_open"/>
				<label for="proc_open">proc_open</label>
				<input name="popen" id="popen" type="checkbox"{POPEN} value="popen"/>
				<label for="popen">popen</label>
			</div>
		</div>
	</div>
	<!-- EDP: php_editor_disable_functions_block -->
</div>
<h1><strong>{TR_OTHER_SETTINGS}</strong></h1>

<div style="padding: 0">
	<div class="odd">
		<div class="left"><label for="def_language">{TR_USER_INITIAL_LANG}</label></div>
		<div class="right">
			<select name="def_language" id="def_language">
				<!-- BDP: def_language -->
				<option value="{LANG_VALUE}"{LANG_SELECTED}>{LANG_NAME}</option>
				<!-- EDP: def_language -->
			</select>
		</div>
	</div>
	<div class="even">
		<div class="left"><label for="support_system">{TR_SUPPORT_SYSTEM}</label></div>
		<div class="right">
			<select name="support_system" id="support_system">
				<option value="0" {SUPPORT_SYSTEM_SELECTED_OFF}>{TR_DISABLED}</option>
				<option value="1" {SUPPORT_SYSTEM_SELECTED_ON}>{TR_ENABLED}</option>
			</select>
		</div>
	</div>
	<div class="odd">
		<div class="left"><label for="compress_output">{TR_COMPRESS_OUTPUT}</label></div>
		<div class="right">
			<select name="compress_output" id="compress_output">
				<option value="0" {COMPRESS_OUTPUT_OFF}>{TR_DISABLED}</option>
				<option value="1" {COMPRESS_OUTPUT_ON}>{TR_ENABLED}</option>
			</select>
		</div>
	</div>
	<div class="even">
		<div class="left"><label for="show_compression_size">{TR_SHOW_COMPRESSION_SIZE}</label></div>
		<div class="right">
			<select name="show_compression_size" id="show_compression_size">
				<option value="0" {SHOW_COMPRESSION_SIZE_SELECTED_OFF}>{TR_DISABLED}</option>
				<option value="1" {SHOW_COMPRESSION_SIZE_SELECTED_ON}>{TR_ENABLED}</option>
			</select>
		</div>
	</div>
	<div class="odd">
		<div class="left"><label for="hosting_plan_level">{TR_HOSTING_PLANS_LEVEL}</label></div>
		<div class="right">
			<select name="hosting_plan_level" id="hosting_plan_level">
				<option value="admin" {HOSTING_PLANS_LEVEL_ADMIN}>{TR_ADMIN}</option>
				<option value="reseller" {HOSTING_PLANS_LEVEL_RESELLER}>{TR_RESELLER}</option>
			</select>
		</div>
	</div>
	<div class="even">
		<div class="left"><label for="domain_rows_per_page">{TR_DOMAIN_ROWS_PER_PAGE}</label></div>
		<div class="right">
			<input name="domain_rows_per_page" id="domain_rows_per_page" type="text" value="{DOMAIN_ROWS_PER_PAGE}"
				   maxlength="3"/>
		</div>
	</div>
	<div class="odd">
		<div class="left"><label for="log_level">{TR_LOG_LEVEL}</label></div>
		<div class="right">
			<select name="log_level" id="log_level">
				<option value="E_USER_OFF" {LOG_LEVEL_SELECTED_OFF}>{TR_E_USER_OFF}</option>
				<option value="E_USER_ERROR" {LOG_LEVEL_SELECTED_ERROR}>{TR_E_USER_ERROR}</option>
				<option value="E_USER_WARNING" {LOG_LEVEL_SELECTED_WARNING}>{TR_E_USER_WARNING}</option>
				<option value="E_USER_NOTICE" {LOG_LEVEL_SELECTED_NOTICE}>{TR_E_USER_NOTICE}</option>
			</select>
		</div>
	</div>
	<div class="even">
		<div class="left">
			<label for="prevent_external_login_admin">{TR_PREVENT_EXTERNAL_LOGIN_ADMIN}</label>
		</div>
		<div class="right">
			<select name="prevent_external_login_admin" id="prevent_external_login_admin">
				<option value="0" {PREVENT_EXTERNAL_LOGIN_ADMIN_SELECTED_OFF}>{TR_DISABLED}</option>
				<option value="1" {PREVENT_EXTERNAL_LOGIN_ADMIN_SELECTED_ON}>{TR_ENABLED}</option>
			</select>
		</div>
	</div>
	<div class="odd">
		<div class="left">
			<label for="prevent_external_login_reseller">{TR_PREVENT_EXTERNAL_LOGIN_RESELLER}</label>
		</div>
		<div class="right">
			<select name="prevent_external_login_reseller" id="prevent_external_login_reseller">
				<option value="0" {PREVENT_EXTERNAL_LOGIN_RESELLER_SELECTED_OFF}>{TR_DISABLED}</option>
				<option value="1" {PREVENT_EXTERNAL_LOGIN_RESELLER_SELECTED_ON}>{TR_ENABLED}</option>
			</select>
		</div>
	</div>
	<div class="even">
		<div class="left">
			<label for="prevent_external_login_client">{TR_PREVENT_EXTERNAL_LOGIN_CLIENT}</label>
		</div>
		<div class="right">
			<select name="prevent_external_login_client" id="prevent_external_login_client">
				<option value="0" {PREVENT_EXTERNAL_LOGIN_CLIENT_SELECTED_OFF}>{TR_DISABLED}</option>
				<option value="1" {PREVENT_EXTERNAL_LOGIN_CLIENT_SELECTED_ON}>{TR_ENABLED}</option>
			</select>
		</div>
	</div>
	<div class="odd">
		<div class="left"><label for="enableSSL">{TR_ENABLE_SSL}</label></div>
		<div class="right">
			<select name="enableSSL" id="enableSSL">
				<option value="0"{ENABLE_SSL_OFF}>{TR_DISABLED}</option>
				<option value="1"{ENABLE_SSL_ON}>{TR_ENABLED}</option>
			</select>
		</div>
	</div>
</div>
</div>
<div class="buttons">
	<input name="Submit" type="submit" value="{TR_UPDATE}"/>
	<input type="hidden" name="uaction" value="apply"/>
</div>
</form>
