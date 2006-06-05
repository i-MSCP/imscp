<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_ADMIN_SETTINGS_PAGE_TITLE}</title>
<meta name="robots" content="noindex">
<meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/vhcs.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/vhcs.js"></script>
</head>

<body onLoad="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif','{THEME_COLOR_PATH}/images/icons/logout_a.gif','{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<table width="100%" border="0" cellspacing="0" cellpadding="0" height="100%">
  <tr>
    <td height="80" align="left" valign="top">
    <table width="100%"  border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td width="17"><img src="{THEME_COLOR_PATH}/images/top/left.jpg" width="17" height="80"></td>
          <td width="198" align="center" background="{THEME_COLOR_PATH}/images/top/logo_background.jpg"><img src="{ISP_LOGO}"></td>
          <td background="{THEME_COLOR_PATH}/images/top/left_fill.jpg"><img src="{THEME_COLOR_PATH}/images/top/left_fill.jpg" width="2" height="80"></td>
          <td width="766"><img src="{THEME_COLOR_PATH}/images/top/middle_background.jpg" width="766" height="80"></td>
          <td background="{THEME_COLOR_PATH}/images/top/right_fill.jpg"><img src="{THEME_COLOR_PATH}/images/top/right_fill.jpg" width="3" height="80"></td>
          <td width="9"><img src="{THEME_COLOR_PATH}/images/top/right.jpg" width="9" height="80"></td>
        </tr>
    </table></td>
  </tr>
  <tr>
    <td valign="top"><table height="100%" width="100%"  border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="215" valign="top" bgcolor="#F5F5F5"><!-- Menu begin -->
  {MENU}
    <!-- Menu end -->
        </td>
        <td valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td height="62" align="left" background="{THEME_COLOR_PATH}/images/content/table_background.jpg" class="title"><img src="{THEME_COLOR_PATH}/images/content/table_icon_settings.jpg" width="85" height="62" align="absmiddle">{TR_SETTINGS}</td>
            <td width="27" align="right" background="{THEME_COLOR_PATH}/images/content/table_background.jpg"><img src="{THEME_COLOR_PATH}/images/content/table_icon_close.jpg" width="27" height="62"></td>
          </tr>
          <tr>
            <td valign="top"><form name="frmsettings" method="post" action="settings.php">
                <table width="100%" cellpadding="5" cellspacing="5">
                  <!-- BDP: page_message -->
                  <tr>
                    <td width="20">&nbsp;</td>
                    <td colspan="2" class=title><font color="#FF0000">{MESSAGE}</font></td>
                  </tr>
                  <!-- EDP: page_message -->
                  <tr>
                    <td width="20">&nbsp;</td>
                    <td colspan="2" class="content3"><strong>{TR_LOSTPASSWORD}</strong></td>
                    </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td width="200" class="content2">{TR_LOSTPASSWORD}</td>
                  <td><select name="lostpassword">
                    <option value="0" {LOSTPASSWORD_SELECTED_OFF}>{TR_DISABLED}</option>
                    <option value="1" {LOSTPASSWORD_SELECTED_ON}>{TR_ENABLED}</option>
                  </select></td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td class="content2">{TR_LOSTPASSWORD_TIMEOUT}</td>
                  <td><span class="content">
                    <input name="lostpassword_timeout" type="text" class="textinput" id="lostpassword_timeout" style="width:50px" value="{LOSTPASSWORD_TIMEOUT_VALUE}" maxlength="3">
                  </span></td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td colspan="2" class="content3"><strong>{TR_BRUTEFORCE}</strong></td>
                    </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td class="content2">{TR_BRUTEFORCE}</td>
                  <td><select name="bruteforce" id="bruteforce">
                    <option value="0" {BRUTEFORCE_SELECTED_OFF}>{TR_DISABLED}</option>
                    <option value="1" {BRUTEFORCE_SELECTED_ON}>{TR_ENABLED}</option>
                  </select></td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td class="content2">{TR_BRUTEFORCE_BETWEEN}</td>
                  <td><span class="content">
                    <select name="bruteforce_between" id="bruteforce_between">
                      <option value="0" {BRUTEFORCE_BETWEEN_SELECTED_OFF}>{TR_DISABLED}</option>
                      <option value="1" {BRUTEFORCE_BETWEEN_SELECTED_ON}>{TR_ENABLED}</option>
                    </select>
                  </span></td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td class="content2">{TR_BRUTEFORCE_MAX_LOGIN}</td>
                  <td><span class="content">
                    <input name="bruteforce_max_login" type="text" class="textinput" id="bruteforce_max_login" style="width:50px" value="{BRUTEFORCE_MAX_LOGIN_VALUE}" maxlength="3">
                  </span></td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td class="content2">{TR_BRUTEFORCE_BLOCK_TIME}</td>
                  <td><span class="content">
				     <input name="bruteforce_block_time" type="text" class="textinput" id="bruteforce_block_time" style="width:50px" value="{BRUTEFORCE_BLOCK_TIME_VALUE}" maxlength="3">
					</span></td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td class="content2">{TR_BRUTEFORCE_BETWEEN_TIME}</td>
                  <td><span class="content">
                    <input name="bruteforce_between_time" type="text" class="textinput" id="bruteforce_between_time" style="width:50px" value="{BRUTEFORCE_BETWEEN_TIME_VALUE}" maxlength="3">
                  </span></td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td colspan="2" class="content3"><strong>{TR_OTHER_SETTINGS}</strong></td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td class="content2">{TR_USER_INITIAL_LANG}</td>
                    <td><span class="content"><select name="def_language" id="def_language">
                        <!-- BDP: def_language -->
                        <option value="{LANG_VALUE}" {LANG_SELECTED}>{LANG_NAME}</option>
                        <!-- EDP: def_language -->
                      </select></span></td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td class="content2">{TR_SUPPORT_SYSTEM}</td>
                  <td><span class="content">
                    <select name="support_system" id="support_system">
                      <option value="0" {SUPPORT_SYSTEM_SELECTED_OFF}>{TR_DISABLED}</option>
                      <option value="1" {SUPPORT_SYSTEM_SELECTED_ON}>{TR_ENABLED}</option>
                    </select>
                  </span></td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td class="content2">{TR_DOMAIN_ROWS_PER_PAGE}</td>
                  <td><span class="content">
                    <input name="domain_rows_per_page" type="text" class="textinput" id="domain_rows_per_page" style="width:50px" value="{DOMAIN_ROWS_PER_PAGE}" maxlength="3">
                  </span></td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td colspan="2"><input type="hidden" name="uaction" value="apply">
                      <input name="Submit" type="submit" class="button" value="{TR_APPLY_CHANGES}"></td></tr>
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
    </table></td>
  </tr>
  <tr>
    <td height="71"><table width="100%"  border="0" cellspacing="0" cellpadding="0">
        <tr><td width="17"><img src="{THEME_COLOR_PATH}/images/top/down_left.jpg" width="17" height="71"></td><td width="198" valign="top" background="{THEME_COLOR_PATH}/images/top/downlogo_background.jpg"><table width="100%" border="0" cellpadding="0" cellspacing="0" >
          <tr>
            <td width="55"><a href="http://www.vhcs.net" target="_blank"><img src="{THEME_COLOR_PATH}/images/vhcs.gif" alt="" width="51" height="71" border="0"></a></td>
            <td class="bottom">{VHCS_LICENSE}</td>
          </tr>
        </table>          </td>
          <td background="{THEME_COLOR_PATH}/images/top/down_left_fill.jpg"><img src="{THEME_COLOR_PATH}/images/top/down_left_fill.jpg" width="2" height="71"></td><td width="766" background="{THEME_COLOR_PATH}/images/top/middle_background.jpg"><img src="{THEME_COLOR_PATH}/images/top/down_middle_background.jpg" width="766" height="71"></td>
          <td background="{THEME_COLOR_PATH}/images/top/down_right_fill.jpg"><img src="{THEME_COLOR_PATH}/images/top/down_right_fill.jpg" width="3" height="71"></td>
          <td width="9"><img src="{THEME_COLOR_PATH}/images/top/down_right.jpg" width="9" height="71"></td></tr>
    </table></td>
  </tr>
</table>
</body>
</html>