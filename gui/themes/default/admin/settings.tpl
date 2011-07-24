<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_ADMIN_SETTINGS_PAGE_TITLE}</title>
        <meta name="robots" content="nofollow, noindex" />
        <link href="{THEME_COLOR_PATH}/css/imscp.css" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/jquery.js"></script>
		<script type="text/javascript" src="{THEME_COLOR_PATH}/js/jquery.imscpTooltips.js"></script>
        <!--[if IE 6]>
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/DD_belatedPNG_0.0.8a-min.js"></script>
        <script type="text/javascript">
            DD_belatedPNG.fix('*');
        </script>
        <![endif]-->
        <script type="text/javascript">
		/*<![CDATA[*/
			$(document).ready(function() {
				jQuery('#tld_help').iMSCPtooltips({msg:"{TR_TLD_STRICT_VALIDATION_HELP}"});
				jQuery('#sld_help').iMSCPtooltips({msg:"{TR_SLD_STRICT_VALIDATION_HELP}"});
			});
		/*]]>*/
	</script>
    </head>
	<body>
		<div class="header">
			{MAIN_MENU}
			<div class="logo"><img src="{ISP_LOGO}" alt="i-MSCP logo" /></div>
		</div>
		<div class="location">
			<div class="location-area icons-left"><h1 class="settings">{TR_MENU_SETTINGS}</h1></div>
			<ul class="location-menu">
			<!-- <li><a class="help" href="#">Help</a></li> -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="settings.php">{TR_SETTINGS}</a></li>
			</ul>
		</div>
		<div class="left_menu">
			{MENU}
		</div>
		<div class="body">
            <h2 class="general"><span>{TR_SETTINGS}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<form action="settings.php" method="post" name="frmsettings" id="frmsettings">
				<fieldset>
					<legend>Core data</legend>
					<table>
						<tr>
							<td style="width:300px;"><label for="checkforupdate">{TR_CHECK_FOR_UPDATES}</label></td>
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
					<table>
						<tr>
							<td style="width:300px;"><label for="lostpassword">{TR_LOSTPASSWORD}</label></td>
							<td>
								<select name="lostpassword" id="lostpassword">
									<option value="0" {LOSTPASSWORD_SELECTED_OFF}>{TR_DISABLED}</option>
									<option value="1" {LOSTPASSWORD_SELECTED_ON}>{TR_ENABLED}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td width="300"><label for="lostpassword_timeout">{TR_LOSTPASSWORD_TIMEOUT}</label></td>
							<td><input type="text" name="lostpassword_timeout" id="lostpassword_timeout" value="{LOSTPASSWORD_TIMEOUT_VALUE}"/></td>
						</tr>
					</table>
				</fieldset>
				<fieldset>
					<legend>{TR_PASSWORD_SETTINGS}</legend>
					<table>
						<tr>
							<td style="width:300px;"><label for="passwd_strong">{TR_PASSWD_STRONG}</label></td>
							<td>
								<select name="passwd_strong" id="passwd_strong">
									<option value="0" {PASSWD_STRONG_OFF}>{TR_DISABLED}</option>
									<option value="1" {PASSWD_STRONG_ON}>{TR_ENABLED}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td width="300"><label for="passwd_chars">{TR_PASSWD_CHARS}</label></td>
							<td><input type="text" name="passwd_chars" id="passwd_chars" value="{PASSWD_CHARS}" maxlength="2" /></td>
						</tr>
					</table>
				</fieldset>
				<fieldset>
					<legend>{TR_BRUTEFORCE}</legend>
					<table>
						<tr>
							<td style="width:300px;"><label for="bruteforce">{TR_BRUTEFORCE}</label></td>
							<td>
								<select name="bruteforce" id="bruteforce">
									<option value="0" {BRUTEFORCE_SELECTED_OFF}>{TR_DISABLED}</option>
									<option value="1" {BRUTEFORCE_SELECTED_ON}>{TR_ENABLED}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td style="width:300px;"><label for="bruteforce_between">{TR_BRUTEFORCE_BETWEEN}</label></td>
							<td>
								<select name="bruteforce_between" id="bruteforce_between">
									<option value="0" {BRUTEFORCE_BETWEEN_SELECTED_OFF}>{TR_DISABLED}</option>
									<option value="1" {BRUTEFORCE_BETWEEN_SELECTED_ON}>{TR_ENABLED}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td style="width:300px;"><label for="bruteforce_max_login">{TR_BRUTEFORCE_MAX_LOGIN}</label></td>
							<td><input type="text" name="bruteforce_max_login" id="bruteforce_max_login" value="{BRUTEFORCE_MAX_LOGIN_VALUE}" maxlength="3" /></td>
						</tr>
						<tr>
							<td><label for="bruteforce_block_time">{TR_BRUTEFORCE_BLOCK_TIME}</label></td>
							<td><input name="bruteforce_block_time" id="bruteforce_block_time" type="text" value="{BRUTEFORCE_BLOCK_TIME_VALUE}" maxlength="3" /></td>
						</tr>
						<tr>
							<td><label for="bruteforce_between_time">{TR_BRUTEFORCE_BETWEEN_TIME}</label></td>
							<td><input name="bruteforce_between_time" id="bruteforce_between_time" type="text"  value="{BRUTEFORCE_BETWEEN_TIME_VALUE}" maxlength="3" /></td>
						</tr>
						<tr>
							<td><label for="bruteforce_max_capcha">{TR_BRUTEFORCE_MAX_CAPTCHA}</label></td>
							<td><input name="bruteforce_max_capcha" id="bruteforce_max_capcha" type="text" value="{BRUTEFORCE_MAX_CAPTCHA}" maxlength="3" /></td>
						</tr>
					</table>
				</fieldset>
				<fieldset>
					<legend>{TR_DNAMES_VALIDATION_SETTINGS}</legend>
					<table>
						<tr>
							<td style="width:300px;"><label for="tld_strict_validation">{TR_TLD_STRICT_VALIDATION}</label><span class="icon i_help" id="tld_help">Help</span></td>
							<td>
								<select name="tld_strict_validation" id="tld_strict_validation">
									<option value="0" {TLD_STRICT_VALIDATION_OFF}>{TR_DISABLED}</option>
									<option value="1" {TLD_STRICT_VALIDATION_ON}>{TR_ENABLED}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="sld_strict_validation">{TR_SLD_STRICT_VALIDATION}</label><span class="icon i_help" id="sld_help">Help</span></td>
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
								<input name="max_dnames_labels" id="max_dnames_labels" type="text" value="{MAX_DNAMES_LABELS_VALUE}" maxlength="2" />
							</td>
						</tr>
						<tr>
							<td><label for="max_subdnames_labels">{TR_MAX_SUBDNAMES_LABELS}</label></td>
							<td><input name="max_subdnames_labels" id="max_subdnames_labels" type="text" value="{MAX_SUBDNAMES_LABELS_VALUE}" maxlength="2" /></td>
						</tr>
					</table>
				</fieldset>
				<fieldset>
					<legend>{TR_MAIL_SETTINGS}</legend>
						<table>
						<tr>
							<td style="width:300px;"><label for="create_default_email_addresses">{TR_CREATE_DEFAULT_EMAIL_ADDRESSES}</label></td>
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
                    <legend>{TR_ORDERS_SETTINGS}</legend>
                    <table>
                        <tr>
                            <td style="width:300px;">
                                <label for="ordersExpireTime">{TR_ORDERS_EXPIRE_TIME}</label>
                            </td>
                            <td>
                                <input type="text" name="ordersExpireTime" id="ordersExpireTime" value="{ORDERS_EXPIRATION_TIME_VALUE}" />
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label for="coid">{TR_CUSTOM_ORDERPANEL_ID}</label>
                            </td>
                            <td>
                                <input type="text" name="coid" id="coid" value="{CUSTOM_ORDERPANEL_ID}" />
                            </td>
                        </tr>
                    </table>
                </fieldset>
				<fieldset>
					<legend>{TR_OTHER_SETTINGS}</legend>
					<table>
						<tr>
							<td style="width:300px;"><label for="def_language">{TR_USER_INITIAL_LANG}</label></td>
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
							<td><input name="domain_rows_per_page" id="domain_rows_per_page" type="text" value="{DOMAIN_ROWS_PER_PAGE}" maxlength="3" /></td>
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
							<td><label for="prevent_external_login_reseller">{TR_PREVENT_EXTERNAL_LOGIN_RESELLER}</label></td>
							<td>
								<select name="prevent_external_login_reseller" id="prevent_external_login_reseller">
									<option value="0" {PREVENT_EXTERNAL_LOGIN_RESELLER_SELECTED_OFF}>{TR_DISABLED}</option>
									<option value="1" {PREVENT_EXTERNAL_LOGIN_RESELLER_SELECTED_ON}>{TR_ENABLED}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="prevent_external_login_client">{TR_PREVENT_EXTERNAL_LOGIN_CLIENT}</label></td>
							<td>
								<select name="prevent_external_login_client" id="prevent_external_login_client">
									<option value="0" {PREVENT_EXTERNAL_LOGIN_CLIENT_SELECTED_OFF}>{TR_DISABLED}</option>
									<option value="1" {PREVENT_EXTERNAL_LOGIN_CLIENT_SELECTED_ON}>{TR_ENABLED}</option>
								</select>
							</td>
						</tr>
					</table>
				</fieldset>
				<div class="buttons">
					<input name="Submit" type="submit" class="button" value="{TR_APPLY_CHANGES}" />
					<input type="hidden" name="uaction" value="apply" />
				</div>
			</form>
		</div>
		<div class="footer">i-MSCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}</div>
    </body>
</html>
