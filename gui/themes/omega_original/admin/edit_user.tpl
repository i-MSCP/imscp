<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_ADMIN_EDIT_USER_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
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
                            <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_doc.png" width="25" height="25"></td>
                            <td colspan="2" class="title">{TR_EDIT_ADMIN}</td>
                          </tr>
                      </table></td>
                      <td width="27" align="right">&nbsp;</td>
                    </tr>
                    <tr>
                      <td valign="top"><form name="admin_edit_user" method="post" action="edit_user.php">
                          <table width="100%" cellspacing="5">
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td colspan="2" class="content3"><b>{TR_CORE_DATA}</b></td>
                            </tr>
                            <!-- BDP: page_message -->
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td colspan="2" class="title"><font color="#FF0000">{MESSAGE}</font></td>
                            </tr>
                            <!-- EDP: page_message -->
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td width="200" class="content2">{TR_USERNAME}</td>
                              <td class="content"> {USERNAME}</td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td width="200" class="content2">{TR_PASSWORD}</td>
                              <td class="content"><input type="password" name="pass" value="{VAL_PASSWORD}" style="width:210px" class="textinput">
                                &nbsp;&nbsp;&nbsp;
                                <input name="genpass" type="submit" class="button" value=" {TR_PASSWORD_GENERATE} ">
                              </td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td width="200" class="content2">{TR_PASSWORD_REPEAT}</td>
                              <td class="content"><input type="password" name="pass_rep" value="{VAL_PASSWORD}" style="width:210px" class="textinput">
                              </td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td width="200" class="content2">{TR_EMAIL}</td>
                              <td class="content"><input type="text" name="email" value="{EMAIL}" style="width:210px" class="textinput">
                              </td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td colspan="2" class="content3"><b>{TR_ADDITIONAL_DATA}</b></td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td width="200" class="content2">{TR_FIRST_NAME}</td>
                              <td class="content"><input type="text" name="fname" value="{FIRST_NAME}" style="width:210px" class="textinput">
                              </td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td width="200" class="content2">{TR_LAST_NAME}</td>
                              <td class="content"><input type="text" name="lname" value="{LAST_NAME}" style="width:210px" class="textinput">
                              </td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td width="200" class="content2">{TR_COMPANY}</td>
                              <td class="content"><input type="text" name="firm" value="{FIRM}" style="width:210px" class="textinput">
                              </td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td width="200" class="content2">{TR_ZIP_POSTAL_CODE}</td>
                              <td class="content"><input type="text" name="zip" value="{ZIP}" style="width:80px" class="textinput">
                              </td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td width="200" class="content2">{TR_CITY}</td>
                              <td class="content"><input type="text" name="city" value="{CITY}" style="width:210px" class="textinput">
                              </td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td width="200" class="content2">{TR_COUNTRY}</td>
                              <td class="content"><input type="text" name="country" value="{COUNTRY}" style="width:210px" class="textinput">
                              </td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td width="200" class="content2">{TR_STREET_1}</td>
                              <td class="content"><input type="text" name="street1" value="{STREET_1}" style="width:210px" class="textinput">
                              </td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td width="200" class="content2">{TR_STREET_2}</td>
                              <td class="content"><input type="text" name="street2" value="{STREET_2}" style="width:210px" class="textinput">
                              </td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td width="200" class="content2">{TR_PHONE}</td>
                              <td class="content"><input type="text" name="phone" value="{PHONE}" style="width:210px" class="textinput">
                              </td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td width="200" class="content2">{TR_FAX}</td>
                              <td class="content"><input type="text" name="fax" value="{FAX}" style="width:210px" class="textinput">
                              </td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td colspan="2"><input name="Submit" type="submit" class="button" value="  {TR_UPDATE}  ">
                                &nbsp;&nbsp;&nbsp;
                                <input type="checkbox" name="send_data" checked>
                                {TR_SEND_DATA}</td>
                            </tr>
                          </table>
                        <input type="hidden" name="uaction" value="edit_user">
                          <input type="hidden" name="edit_id" value="{EDIT_ID}">
                          <input type="hidden" name="edit_username" value="{USERNAME}">
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
