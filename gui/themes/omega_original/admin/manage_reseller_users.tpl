<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_ADMIN_MANAGE_RESELLER_USERS_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
</head>

<body onLoad="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<table width="100%" border="0" cellspacing="0" cellpadding="0" height="100%" style="border-collapse: collapse;padding:0;margin:0;">
<tr>
<td align="left" valign="top" style="vertical-align: top; width: 195px; height: 56px;"><img src="../themes/omega_original/images/top/top_left.jpg" border="0"></td>
<td style="height: 56px; width:100%; background-image: url(../themes/omega_original/images/top/top_bg.jpg)"><img src="../themes/omega_original/images/top/top_left_bg.jpg" border="0"></td>
<td style="width: 73px; height: 56px;"><img src="../themes/omega_original/images/top/top_right.jpg" border="0"></td>
</tr>
	<tr>
		<td style="width: 195px; vertical-align: top;">{MENU}</td>
	    <td colspan=2 style="vertical-align: top;"><table style="width: 100%; border-collapse: collapse;padding:0;margin:0;">
				<tr height="95";>
				  <td style="padding-left:30px; width: 100%; background-image: url({THEME_COLOR_PATH}/images/top/middle_bg.jpg);">{MAIN_MENU}</td>
					<td style="padding:0;margin:0;text-align: right; width: 73px;vertical-align: top;"><img src="{THEME_COLOR_PATH}/images/top/middle_right.jpg" border="0"></td>
				</tr>
				<tr height="*">
				  <td colspan=3><table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td align="left"><table width="100%" cellpadding="5" cellspacing="5">
                          <tr>
                            <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_users2.png" width="25" height="25"></td>
                            <td colspan="2" class="title">{TR_USER_ASSIGNMENT}</td>
                          </tr>
                      </table></td>
                      <td width="27" align="right" >&nbsp;</td>
                    </tr>
                    <tr>
                      <td valign="top"><form action="manage_reseller_users.php" method="post" name="admin_user_assignment" id="admin_user_assignment">
                          <table width="100%"  border="00" cellspacing="0" cellpadding="0">
                            <tr>
                              <td width="40">&nbsp;</td>
                              <td><table width="100%" cellpadding="5" cellspacing="5">
                                  <!-- BDP: page_message -->
                                  <tr>
                                    <td colspan="3" class="title"><font color="#FF0000">{MESSAGE}</font></td>
                                  </tr>
                                  <!-- EDP: page_message -->
                                  <!-- BDP: src_reseller -->
                                  <tr>
                                    <td colspan="3"><b>{TR_FROM_RESELLER}</b>
                                        <select name="src_reseller" onChange="return sbmt(document.forms[0],'change_src');;">
                                          <!-- BDP: src_reseller_option -->
                                          <option {SRC_RSL_SELECTED} value="{SRC_RSL_VALUE}">{SRC_RSL_OPTION}</option>
                                          <!-- EDP: src_reseller_option -->
                                        </select>
                                    </td>
                                  </tr>
                                  <!-- EDP: src_reseller -->
                                  <!-- BDP: reseller_list -->
                                  <tr>
                                    <td class="content3" width="80" align="center"><b>{TR_NUMBER}</b></td>
                                    <td class="content3" width="80" align="center"><b>{TR_MARK}</b></td>
                                    <td class="content3"><b>{TR_USER_NAME}</b></td>
                                  </tr>
                                  <!-- BDP: reseller_item -->
                                  <tr>
                                    <td class="{RSL_CLASS}" width="80" align="center">{NUMBER}</td>
                                    <td class="{RSL_CLASS}" width="80" align="center"><input type="checkbox" name="{CKB_NAME}" />
                                    </td>
                                    <td class="{RSL_CLASS}">{USER_NAME}</td>
                                  </tr>
                                  <!-- EDP: reseller_item -->
                                  <!-- EDP: reseller_list -->
                                </table>
                                  <!-- BDP: dst_reseller -->
                                  <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                    <tr>
                                      <td width="200" ><b>{TR_TO_RESELLER}</b>
                                          <select name="dst_reseller">
                                            <!-- BDP: dst_reseller_option -->
                                            <option {DST_RSL_SELECTED} value="{DST_RSL_VALUE}">{DST_RSL_OPTION}</option>
                                            <!-- EDP: dst_reseller_option -->
                                          </select>
                                          <!-- EDP: dst_reseller -->
                                      </td>
                                      <td><input name="Submit" type="submit" class="button" value="  {TR_MOVE}  " />
                                      </td>
                                    </tr>
                                  </table>
                                <input type="hidden" name="uaction" value="move_user" /></td>
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
