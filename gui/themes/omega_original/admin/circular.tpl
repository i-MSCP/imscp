<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_ADMIN_CIRCULAR_PAGE_TITLE}</title>
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
                            <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_email.png" width="25" height="25"></td>
                            <td colspan="2" class="title">{TR_CIRCULAR}</td>
                          </tr>
                      </table></td>
                      <td width="27" align="right">&nbsp;</td>
                    </tr>
                    <tr>
                      <td valign="top"><form name="admin_email_setup" method="post" action="circular.php">
                          <table width="100%" cellpadding="5" cellspacing="5">
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td colspan="2" class="content3"><b>{TR_CORE_DATA}</b></td>
                            </tr>
                            <!-- BDP: page_message -->
                            <tr>
                              <td>&nbsp;</td>
                              <td colspan="2" class="title"><font color="#FF0000">{MESSAGE}</font></td>
                            </tr>
                            <!-- EDP: page_message -->
                            <tr>
                              <td>&nbsp;</td>
                              <td class="content2">{TR_SEND_TO}</td>
                              <td class="content"><select name="rcpt_to">
                                  <option value="usrs">{TR_ALL_USERS}</option>
                                  <option value="rslrs">{TR_ALL_RESELLERS}</option>
                                  <option value="usrs_rslrs">{TR_ALL_USERS_AND_RESELLERS}</option>
                              </select></td>
                            </tr>
                            <tr>
                              <td>&nbsp;</td>
                              <td class="content2" width="200">{TR_MESSAGE_SUBJECT}</td>
                              <td class="content"><input type="text" name="msg_subject" value="{MESSAGE_SUBJECT}" style="width:270px" class="textinput"></td>
                            </tr>
                            <tr>
                              <td>&nbsp;</td>
                              <td class="content2" style="width:200px; vertical-align:top;">{TR_MESSAGE_TEXT}</td>
                              <td class="content"><textarea name="msg_text" style="width:80%" class="textinput2" rows="20">{MESSAGE_TEXT}</textarea></td>
                            </tr>
                            <tr>
                              <td>&nbsp;</td>
                              <td colspan="2" class="content3"><b>{TR_ADDITIONAL_DATA}</b></td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td width="200" class="content2">{TR_SENDER_EMAIL}</td>
                              <td class="content"><input type="text" name="sender_email" value="{SENDER_EMAIL}" style="width:270px" class="textinput"></td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td width="200" class="content2">{TR_SENDER_NAME}</td>
                              <td class="content"><input type="text" name="sender_name" value="{SENDER_NAME}" style="width:270px" class="textinput"></td>
                            </tr>
                            <tr>
                              <td>&nbsp;</td>
                              <td colspan="2"><input name="Submit" type="submit" class="button" value="{TR_SEND_MESSAGE}"></td>
                            </tr>
                          </table>
                        <input type="hidden" name="uaction" value="send_circular">
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
