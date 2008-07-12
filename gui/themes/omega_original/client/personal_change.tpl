<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_CLIENT_CHANGE_PERSONAL_DATA_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/ftp_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/email_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<!-- BDP: logged_from --><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td height="20" nowrap="nowrap" class="backButton">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.png" width="16" height="16" border="0" align="absmiddle"></a> {YOU_ARE_LOGGED_AS}</td>
      </tr>
    </table>
	<!-- EDP: logged_from -->
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
                      <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_doc.png" width="25" height="25"></td>
                      <td colspan="2" class="title">{TR_CHANGE_PERSONAL_DATA}</td>
                    </tr>
                </table></td>
                <td width="27" align="right">&nbsp;</td>
              </tr>
              <tr>
                <td valign="top"><form name="client_personal_change_frm" method="post" action="personal_change.php">
                    <table width="100%" cellpadding="5" cellspacing="5">
                      <!-- BDP: page_message -->
                      <tr>
                        <td>&nbsp;</td>
                        <td colspan="2" class="title"><span class="message">{MESSAGE}</span></td>
                      </tr>
                      <!-- EDP: page_message -->
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td width="203" class="content2"><label for="fname">{TR_FIRST_NAME}</label></td>
                        <td class="content"><input type="text" name="fname" id="fname" value="{FIRST_NAME}" style="width:210px" class="textinput"></td>
                      </tr>
                      <tr>
                        <td>&nbsp;</td>
                        <td class="content2"><label for="lname">{TR_LAST_NAME}</label></td>
                        <td width="516" class="content"><input type="text" name="lname" id="lname" value="{LAST_NAME}" style="width:210px" class="textinput"></td>
                      </tr>
                            <tr>
                            <td width="25">&nbsp;</td>
                              <td width="200" class="content2"><label for="gender">{TR_GENDER}</label></td>
                              <td class="content"><select name="gender" id="gender" size="1">
                                      <option value="M" {VL_MALE}>{TR_MALE}</option>
                                      <option value="F" {VL_FEMALE}>{TR_FEMALE}</option>
                                      <option value="U" {VL_UNKNOWN}>{TR_UNKNOWN}</option>
                                    </select></td>
                            </tr>
                        <td>&nbsp;</td>
                        <td class="content2"><label for="firm">{TR_COMPANY}</label></td>
                        <td class="content"><input type="text" name="firm" id="firm" value="{FIRM}" style="width:210px" class="textinput"></td>
                      </tr>
                      <tr>
                        <td>&nbsp;</td>
                        <td class="content2"><label for="street1">{TR_STREET_1}</label></td>
                        <td class="content"><input type="text" name="street1" id="street1" value="{STREET_1}" style="width:210px" class="textinput"></td>
                      </tr>
                      <tr>
                        <td>&nbsp;</td>
                        <td class="content2"><label for="street2">{TR_STREET_2}</label></td>
                        <td class="content"><input type="text" name="street2" id="street2" value="{STREET_2}" style="width:210px" class="textinput"></td>
                      </tr>
                      <tr>
                        <td>&nbsp;</td>
                        <td class="content2"><label for="zip">{TR_ZIP_POSTAL_CODE}</label></td>
                        <td class="content"><input type="text" name="zip" id="zip" value="{ZIP}" style="width:80px" class="textinput"></td>
                      </tr>
                      <tr>
                        <td>&nbsp;</td>
                        <td class="content2"><label for="city">{TR_CITY}</label></td>
                        <td class="content"><input type="text" name="city" id="city" value="{CITY}" style="width:210px" class="textinput"></td>
                      </tr>
                      <tr>
                        <td>&nbsp;</td>
                        <td class="content2"><label for="country">{TR_COUNTRY}</label></td>
                        <td class="content"><input type="text" name="country" id="country" value="{COUNTRY}" style="width:210px" class="textinput"></td>
                      </tr>
                      <tr>
                        <td>&nbsp;</td>
                        <td class="content2"><label for="email1">{TR_EMAIL}</label></td>
                        <td class="content"><input type="text" name="email" id="email1" value="{EMAIL}" style="width:210px" class="textinput"></td>
                      </tr>
                      <tr>
                        <td>&nbsp;</td>
                        <td class="content2"><label for="phone">{TR_PHONE}</label></td>
                        <td class="content"><input type="text" name="phone" id="phone" value="{PHONE}" style="width:210px" class="textinput"></td>
                      </tr>
                      <tr>
                        <td>&nbsp;</td>
                        <td class="content2"><label for="fax">{TR_FAX}</label></td>
                        <td class="content"><input type="text" name="fax" id="fax" value="{FAX}" style="width:210px" class="textinput"></td>
                      </tr>
                      <tr>
                        <td>&nbsp;</td>
                        <td colspan="2"><input name="Submit" type="submit" class="button" value="{TR_UPDATE_DATA}">
                            <input type="hidden" name="uaction" value="updt_data" /></td>
                      </tr>
                    </table></form></td>
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
