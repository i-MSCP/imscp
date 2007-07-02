<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_ADMIN_ADMIN_LOG_PAGE_TITLE}</title>
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
                            <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_adminlog.png" width="25" height="25"></td>
                            <td colspan="2" class="title">{TR_ADMIN_LOG}</td>
                          </tr>
                      </table></td>
                      <td width="27" align="right">&nbsp;</td>
                    </tr>
                    <tr>
                      <td valign="top"><form name="admin_lod" method="post" action="admin_log.php">
                          <table width="100%" cellpadding="5" cellspacing="5">
                            <!-- BDP: page_message -->
                            <tr align="left">
                              <td width="25">&nbsp;</td>
                              <td colspan="2"><font color="#FF0000"><span class="title"><font color="#FF0000">{PAG_MESSAGE}</font></span> </font></td>
                            </tr>
                            <!-- EDP: page_message -->
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td width="200" class="content3"><b>{TR_DATE}</b></td>
                              <td class="content3"><b>{TR_MESSAGE}</b></td>
                            </tr>
                            <!-- BDP: log_row -->
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td width="200" class="{ROW_CLASS}">{DATE}</td>
                              <td class="{ROW_CLASS}">{MESSAGE}</td>
                            </tr>
                            <!-- EDP: log_row -->
                          </table>
                        <div align="right"><br>
                              <!-- BDP: scroll_prev_gray -->
                              <img src="{THEME_COLOR_PATH}/images/icons/flip/prev_gray.gif" width="20" height="20" border="0">
                              <!-- EDP: scroll_prev_gray -->
                              <!-- BDP: scroll_prev -->
                              <a href="admin_log.php?psi={PREV_PSI}"><img src="{THEME_COLOR_PATH}/images/icons/flip/prev.gif" width="20" height="20" border="0"></a>
                              <!-- EDP: scroll_prev -->
                              <!-- BDP: scroll_next_gray -->
                          &nbsp;<img src="{THEME_COLOR_PATH}/images/icons/flip/next_gray.gif" width="20" height="20" border="0">
                          <!-- EDP: scroll_next_gray -->
                          <!-- BDP: scroll_next -->
                          &nbsp;<a href="admin_log.php?psi={NEXT_PSI}"><img src="{THEME_COLOR_PATH}/images/icons/flip/next.gif" width="20" height="20" border="0"></a>
                          <!-- EDP: scroll_next -->
                        </div>
                        <!-- BDP: clear_log -->
                          <table width="100%"  border="00" cellspacing="5" cellpadding="5">
                            <tr>
                              <td width="80">&nbsp;</td>
                              <td>{TR_CLEAR_LOG_MESSAGE}
                                <select name="uaction_clear">
                                    <option value="0" selected>{TR_CLEAR_LOG_EVERYTHING}</option>
                                    <option value="2">{TR_CLEAR_LOG_LAST2}</option>
                                    <option value="4">{TR_CLEAR_LOG_LAST4}</option>
                                    <option value="12">{TR_CLEAR_LOG_LAST12}</option>
                                    <option value="26">{TR_CLEAR_LOG_LAST26}</option>
                                    <option value="52">{TR_CLEAR_LOG_LAST52}</option>
                                </select>
                                  <input name="Submit" type="submit" class="button" value="  {TR_CLEAR_LOG}  ">
                              </td>
                            </tr>
                          </table>
                        <!-- EDP: clear_log -->
                          <input type="hidden" name="uaction" value="clear_log">
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
