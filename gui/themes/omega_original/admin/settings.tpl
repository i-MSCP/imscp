<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_ADMIN_SETTINGS_PAGE_TITLE}</title>
<meta name="robots" content="noindex">
<meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
</head>

<body onLoad="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<table width="100%" border="0" cellspacing="0" cellpadding="0" height="100%" style="border-collapse: collapse;padding:0;margin:0;">
	<tr>
		<td align="left" valign="top" style="vertical-align: top; width: 195px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_left.jpg" border="0"></td>
		<td style="height: 56px; width: 617px;"><img src="{THEME_COLOR_PATH}/images/top/top_left_bg.jpg" border="0"></td>
		<td style="width:100%; background-image: url({THEME_COLOR_PATH}/images/top/top_bg.jpg)">&nbsp;</td>
		<td style="width: 73px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_right.jpg" border="0"></td>
	</tr>
	<tr>
		<td style="width: 195px; vertical-align: top;">{MENU}</td>
	    <td colspan=3 style="vertical-align: top;"><table style="width: 100%; border-collapse: collapse;padding:0;margin:0;">
				<tr height="95";>
				  <td style="padding-left:30px; width: 100%; background-image: url({THEME_COLOR_PATH}/images/top/middle_bg.jpg);">{MAIN_MENU}</td>
					<td style="padding:0;margin:0;text-align: right; width: 73px;vertical-align: top;"><img src="{THEME_COLOR_PATH}/images/top/middle_right.jpg" border="0"></td>
				</tr>
				<tr height="*">
				  <td colspan=3><table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td align="left"><table width="100%" cellpadding="5" cellspacing="5">
                          <tr>
                            <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_settings.png" width="25" height="25"></td>
                            <td colspan="2" class="title">{TR_SETTINGS}</td>
                          </tr>
                      </table></td>
                      <td width="27" align="right">&nbsp;</td>
                    </tr>
                    <tr>
                      <td valign="top"><form action="settings.php" method="post" name="frmsettings" id="frmsettings">
                          <table width="100%" cellpadding="5" cellspacing="5">
                            <!-- BDP: page_message -->
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td colspan="2" class="title"><font color="#FF0000">{MESSAGE}</font></td>
                            </tr>
                            <!-- EDP: page_message -->
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td colspan="2" class="content3"><strong>{TR_LOSTPASSWORD}</strong></td>
                            </tr>
                            <tr>
                              <td>&nbsp;</td>
                              <td width="200" class="content2">{TR_LOSTPASSWORD}</td>
                              <td class="content">
							   <select name="lostpassword">
                                  <option value="0" {LOSTPASSWORD_SELECTED_OFF}>{TR_DISABLED}</option>
                                  <option value="1" {LOSTPASSWORD_SELECTED_ON}>{TR_ENABLED}</option>
                              </select></td>
                            </tr>
                            <tr>
                              <td>&nbsp;</td>
                              <td class="content2">{TR_LOSTPASSWORD_TIMEOUT}</td>
                              <td class="content">
                                <input name="lostpassword_timeout" type="text" class="textinput" id="lostpassword_timeout" style="width:50px" value="{LOSTPASSWORD_TIMEOUT_VALUE}" maxlength="3" />
                              </td>
                            </tr>
                            <tr>
                              <td>&nbsp;</td>
                              <td colspan="2" class="content3"><strong>{TR_BRUTEFORCE}</strong></td>
                            </tr>
                            <tr>
                              <td>&nbsp;</td>
                              <td class="content2">{TR_BRUTEFORCE}</td>
                              <td class="content">
							    <select name="bruteforce" id="bruteforce">
                                  <option value="0" {BRUTEFORCE_SELECTED_OFF}>{TR_DISABLED}</option>
                                  <option value="1" {BRUTEFORCE_SELECTED_ON}>{TR_ENABLED}</option>
                                </select></td>
                            </tr>
                            <tr>
                              <td>&nbsp;</td>
                              <td class="content2">{TR_BRUTEFORCE_BETWEEN}</td>
                              <td class="content">
                                <select name="bruteforce_between" id="bruteforce_between">
                                  <option value="0" {BRUTEFORCE_BETWEEN_SELECTED_OFF}>{TR_DISABLED}</option>
                                  <option value="1" {BRUTEFORCE_BETWEEN_SELECTED_ON}>{TR_ENABLED}</option>
                                </select>
                              </td>
                            </tr>
                            <tr>
                              <td>&nbsp;</td>
                              <td class="content2">{TR_BRUTEFORCE_MAX_LOGIN}</td>
                              <td class="content">
                                <input name="bruteforce_max_login" type="text" class="textinput" id="bruteforce_max_login" style="width:50px" value="{BRUTEFORCE_MAX_LOGIN_VALUE}" maxlength="3" />
                              </td>
                            </tr>
                            <tr>
                              <td>&nbsp;</td>
                              <td class="content2">{TR_BRUTEFORCE_BLOCK_TIME}</td>
                              <td class="content">
                                <input name="bruteforce_block_time" type="text" class="textinput" id="bruteforce_block_time" style="width:50px" value="{BRUTEFORCE_BLOCK_TIME_VALUE}" maxlength="3" />
                              </td>
                            </tr>
                            <tr>
                              <td>&nbsp;</td>
                              <td class="content2">{TR_BRUTEFORCE_BETWEEN_TIME}</td>
                              <td class="content">
                                <input name="bruteforce_between_time" type="text" class="textinput" id="bruteforce_between_time" style="width:50px" value="{BRUTEFORCE_BETWEEN_TIME_VALUE}" maxlength="3" />
                              </td>
                            </tr>
                            <tr>
                              <td>&nbsp;</td>
                              <td colspan="2" class="content3"><strong>{TR_OTHER_SETTINGS}</strong></td>
                            </tr>
                            <tr>
                              <td>&nbsp;</td>
                              <td class="content2">{TR_USER_INITIAL_LANG}</td>
                              <td class="content">
                                <select name="def_language" id="def_language">
                                  <!-- BDP: def_language -->
                                  <option value="{LANG_VALUE}" {LANG_SELECTED}>{LANG_NAME}</option>
                                  <!-- EDP: def_language -->
                                </select>
                              </td>
                            </tr>
                            <tr>
                              <td>&nbsp;</td>
                              <td class="content2">{TR_SUPPORT_SYSTEM}</td>
                              <td class="content">
                                <select name="support_system" id="support_system">
                                  <option value="0" {SUPPORT_SYSTEM_SELECTED_OFF}>{TR_DISABLED}</option>
                                  <option value="1" {SUPPORT_SYSTEM_SELECTED_ON}>{TR_ENABLED}</option>
                                </select>
							 </td>
                            </tr>
                            <tr>
                              <td>&nbsp;</td>
                              <td class="content2">{TR_DOMAIN_ROWS_PER_PAGE}</td>
                              <td class="content">
                                <input name="domain_rows_per_page" type="text" class="textinput" id="domain_rows_per_page" style="width:50px" value="{DOMAIN_ROWS_PER_PAGE}" maxlength="3" />
                              </td>
                            </tr>
                            <tr>
                              <td>&nbsp;</td>
                              <td colspan="2"><input type="hidden" name="uaction" value="apply" />
                                  <input name="Submit" type="submit" class="button" value="{TR_APPLY_CHANGES}" /></td>
                            </tr>
                          </table>
                      </form></td>
                      <td>&nbsp;</td>
                    </tr>
                    <tr>
                      <td>&nbsp;</td>
                      <td>&nbsp;</td>
                    </tr>
                  </table></td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</body>
</html>
