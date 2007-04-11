<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_ADMIN_IP_MANAGE_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
<script language="JavaScript" type="text/JavaScript">
<!--

function action_delete(url) {
	if (!confirm("{TR_MESSAGE_DELETE}"))
		return false;

	location = url;
}
//-->
</script>
</head>

<body onLoad="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif','{THEME_COLOR_PATH}/images/icons/logout_a.gif','{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<table width="100%" border="0" cellspacing="0" cellpadding="0" height="100%" style="border-collapse: collapse;padding:0;margin:0;">
	<tr>
		<td align="left" valign="top" style="vertical-align: top; width: 195px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_left.jpg" border="0"></td>
		<td style="height: 56px; width: 785px;"><img src="{THEME_COLOR_PATH}/images/top/top_left_bg.jpg" border="0"></td>
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
                            <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_ip.png" width="25" height="25"></td>
                            <td colspan="2" class="title">{MANAGE_IPS}</td>
                          </tr>
                      </table></td>
                      <td width="27" align="right">&nbsp;</td>
                    </tr>
                    <tr>
                      <td valign="top"><table width="100%" cellpadding="5" cellspacing="5">
                          <!-- BDP: page_message -->
                          <tr>
                            <td width="25">&nbsp;</td>
                            <td colspan="4"><font color="#FF0000"><span class="title"><font color="#FF0000">{MESSAGE}</font></span> </font></td>
                          </tr>
                          <!-- EDP: page_message -->
                          <tr>
                            <td width="25">&nbsp;</td>
                            <td colspan="4" class="content3"><b>{TR_AVAILABLE_IPS}</b></td>
                          </tr>
                          <tr align="center">
                            <td width="25">&nbsp;</td>
                            <td align="left" class="content3"><strong>{TR_IP}</strong></td>
                            <td class="content3"><strong>{TR_DOMAIN}</strong></td>
                            <td class="content3"><strong>{TR_ALIAS}</strong></td>
                            <td width="103" class="content3"><strong>{TR_ACTION}</strong></td>
                          </tr>
                          <!-- BDP: ip_row -->
                          <tr>
                            <td width="25" nowrap>&nbsp;</td>
                            <td align="left" nowrap class="{IP_CLASS}">{IP}</td>
                            <td align="center" nowrap class="{IP_CLASS}">{DOMAIN}</td>
                            <td class="{IP_CLASS}" nowrap align="center">{ALIAS}</td>
                            <td class="{IP_CLASS}" nowrap align="center"><img src="{THEME_COLOR_PATH}/images/icons/delete.gif" width="16" height="16" border="0" align="absmiddle"> <a href="#" onClick="action_delete('delete_ip.php?delete_id={DELETE_ID}')" class="link">{TR_UNINSTALL}</a></td>
                          </tr>
                          <!-- EDP: ip_row -->
                        </table>
                          <br>
                          <form name="add_new_ip_frm" method="post" action="ip_manage.php">
                            <table width="100%" cellpadding="5" cellspacing="5">
                              <tr>
                                <td width="25">&nbsp;</td>
                                <td colspan="2" class="content3"><b>{TR_ADD_NEW_IP}</b></td>
                              </tr>
                              <tr>
                                <td width="25" nowrap>&nbsp;</td>
                                <td width="200" class="content2" nowrap>{TR_IP}</td>
                                <td nowrap class="content"><input name="ip_number_1" type="text" class="textinput" style="width:31px" value="{VALUE_IP1}" maxlength="3">
                                  .
                                  <input name="ip_number_2" type="text" class="textinput" style="width:31px" value="{VALUE_IP2}" maxlength="3">
                                  .
                                  <input name="ip_number_3" type="text" class="textinput" style="width:31px" value="{VALUE_IP3}" maxlength="3">
                                  .
                                  <input name="ip_number_4" type="text" class="textinput" style="width:31px" value="{VALUE_IP4}" maxlength="3">
                                </td>
                              </tr>
                              <tr>
                                <td width="25" nowrap>&nbsp;</td>
                                <td width="200" class="content2" nowrap>{TR_DOMAIN}</td>
                                <td nowrap class="content"><input type="text" name="domain" value="{VALUE_DOMAIN}" style="width:180px" class="textinput">
                                </td>
                              </tr>
                              <tr>
                                <td width="25" nowrap>&nbsp;</td>
                                <td width="200" class="content2" nowrap>{TR_ALIAS}</td>
                                <td nowrap class="content"><input type="text" name="alias" value="{VALUE_ALIAS}" style="width:180px" class="textinput">
                                </td>
                              </tr>
                              <tr>
                                <td nowrap>&nbsp;</td>
                                <td colspan="2" nowrap ><input name="Submit" type="submit" class="button" value="  {TR_ADD}  "></td>
                              </tr>
                            </table>
                            <input type="hidden" name="uaction" value="add_ip">
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
