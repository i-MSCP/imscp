<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_ADMIN_MANAGE_RESELLER_OWNERS_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<table width="100%" border="0" cellspacing="0" cellpadding="0" height="100%" style="border-collapse: collapse;padding:0;margin:0;">
<tr>
<td align="left" valign="top" style="vertical-align: top; width: 195px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_left.jpg" border="0"></td>
<td style="height: 56px; width:100%; background-image: url({THEME_COLOR_PATH}/images/top/top_bg.jpg)"><img src="{THEME_COLOR_PATH}/images/top/top_left_bg.jpg" border="0"></td>
<td style="width: 73px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_right.jpg" border="0"></td>
</tr>
	<tr>
		<td style="width: 195px; vertical-align: top;">{MENU}</td>
	    <td colspan="2" style="vertical-align: top;"><table style="width: 100%; border-collapse: collapse;padding:0;margin:0;">
				<tr height="95">
				  <td style="padding-left:30px; width: 100%; background-image: url({THEME_COLOR_PATH}/images/top/middle_bg.jpg);">{MAIN_MENU}</td>
					<td style="padding:0;margin:0;text-align: right; width: 73px;vertical-align: top;"><img src="{THEME_COLOR_PATH}/images/top/middle_right.jpg" border="0"></td>
				</tr>
				<tr>
				  <td colspan="3"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td align="left"><table width="100%" cellpadding="5" cellspacing="5">
                          <tr>
                            <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_users2.png" width="25" height="25"></td>
                            <td colspan="2" class="title">{TR_RESELLER_ASSIGNMENT}</td>
                          </tr>
                      </table></td>
                      <td width="27" align="right">&nbsp;</td>
                    </tr>
                    <tr>
                      <td valign="top"><form action="manage_reseller_owners.php" method="post" name="admin_reseller_assignment" id="admin_reseller_assignment">
                          <table width="100%" cellpadding="5" cellspacing="5">
                            <!-- BDP: page_message -->
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td colspan="4" class="title"><span class="message">{MESSAGE}</span></td>
                            </tr>
                            <!-- EDP: page_message -->
                            <!-- BDP: reseller_list -->
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td class="content3" width="50" align="center"><b>{TR_NUMBER}</b></td>
                              <td class="content3" width="80" align="center"><b>{TR_MARK}</b></td>
                              <td class="content3" width="200"><b>{TR_RESELLER_NAME}</b></td>
                              <td class="content3"><b>{TR_OWNER}</b></td>
                            </tr>
                            <!-- BDP: reseller_item -->
                            <tr>
                              <td width="25" align="center">&nbsp;</td>
                              <td class="{RSL_CLASS}" width="50" align="center">{NUMBER}</td>
                              <td class="{RSL_CLASS}" width="80" align="center"><input type="checkbox" name="{CKB_NAME}" />
                              </td>
                              <td class="{RSL_CLASS}" width="200">{RESELLER_NAME}</td>
                              <td class="{RSL_CLASS}">{OWNER}</td>
                            </tr>
                            <!-- EDP: reseller_item -->
                            <!-- EDP: reseller_list -->
                            <tr>
                              <td colspan="4" align="right">{TR_TO_ADMIN}
                                <!-- BDP: select_admin -->
                                  <select name="dest_admin">
                                    <!-- BDP: select_admin_option -->
                                    <option {SELECTED} value="{VALUE}">{OPTION}</option>
                                    <!-- EDP: select_admin_option -->
                                  </select>
                                  <!-- EDP: select_admin -->
                              </td>
                              <td><input name="Submit" type="submit" class="button" value="{TR_MOVE}" />
                              </td>
                            </tr>
                          </table>
                        <input type="hidden" name="uaction" value="reseller_owner" />
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
</table>
</body>
</html>
