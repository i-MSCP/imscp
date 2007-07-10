<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_ADMIN_MANAGE_EMAIL_SETUP_PAGE_TITLE}</title>
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
                            <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_email.png" width="25" height="25"></td>
                            <td colspan="2" class="title">{TR_EMAIL_SETUP}</td>
                          </tr>
                      </table></td>
                      <td width="27" align="right">&nbsp;</td>
                    </tr>
                    <tr>
                      <td><form name="admin_email_setup" method="post" action="email_setup.php">
                          <table width="100%" cellpadding="5" cellspacing="5">
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td colspan="2" class="content3"><b>{TR_MESSAGE_TEMPLATE_INFO}</b></td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td width="200" class="content2">{TR_USER_LOGIN_NAME}</td>
                              <td  class="content">{USERNAME} </td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td class="content2">{TR_USER_PASSWORD}</td>
                              <td  class="content">{PASSWORD}</td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td class="content2">{TR_USER_REAL_NAME}</td>
                              <td  class="content">{NAME}</td>
                            </tr>
                          </table>
                        <br>
                          <table width="100%" cellpadding="5" cellspacing="5">
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td colspan="2" class="content3"><b>{TR_MESSAGE_TEMPLATE}</b></td>
                            </tr>
                            <!-- BDP: page_message -->
                            <tr>
                              <td>&nbsp;</td>
                              <td colspan="2" class="title"><font color="#FF0000">{MESSAGE}</font></td>
                            </tr>
                            <!-- EDP: page_message -->
                            <tr>
                              <td>&nbsp;</td>
                              <td class="content2" width="200">{TR_SUBJECT}</td>
                              <td class="content"><input type="text" name="auto_subject" value="{SUBJECT_VALUE}" style="width:80%" class="textinput">
                              </td>
                            </tr>
                            <tr>
                              <td>&nbsp;</td>
                              <td class="content2" style="width:200px;vertical-align:top;">{TR_MESSAGE}</td>
                              <td class="content"><textarea name="auto_message" style="width:80%" class="textinput2" rows="30">{MESSAGE_VALUE}</textarea>
                              </td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td width="200" class="content2">{TR_SENDER_EMAIL}</td>
                              <td class="content">{SENDER_EMAIL_VALUE}</td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td width="200" class="content2">{TR_SENDER_NAME}</td>
                              <td class="content">{SENDER_NAME_VALUE}</td>
                            </tr>
                            <tr>
                              <td>&nbsp;</td>
                              <td colspan="2"><input name="Submit" type="submit" class="button" value="{TR_APPLY_CHANGES}"></td>
                            </tr>
                          </table>
                        <input type="hidden" name="uaction" value="email_setup">
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
