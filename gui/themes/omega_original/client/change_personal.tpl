<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_CLIENT_CHANGE_PERSONAL_DATA_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
</head>

<body onLoad="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/ftp_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif','{THEME_COLOR_PATH}/images/icons/logout_a.gif','{THEME_COLOR_PATH}/images/icons/email_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<!-- BDP: logged_from --><table width="100%"  border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td height="20" nowrap background="{THEME_COLOR_PATH}/images/button.gif">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.gif" width="18" height="18" border="0" align="absmiddle"></a> <font color="red">{YOU_ARE_LOGGED_AS}</font> </td>
      </tr>
    </table>
	<!-- EDP: logged_from -->
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
                      <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_doc.png" width="25" height="25"></td>
                      <td colspan="2" class="title">{TR_CHANGE_PERSONAL_DATA}</td>
                    </tr>
                </table></td>
                <td width="27" align="right">&nbsp;</td>
              </tr>
              <tr>
                <td valign="top"><form name="client_change_personal_frm" method="post" action="change_personal.php">
                    <table width="100%" cellpadding="5" cellspacing="5">
                      <!-- BDP: page_message -->
                      <tr>
                        <td>&nbsp;</td>
                        <td colspan="2" class=title><font color="#FF0000">{MESSAGE}</font></td>
                      </tr>
                      <!-- EDP: page_message -->
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td width="203" class="content2"> {TR_FIRST_NAME}</td>
                        <td class="content"><input type="text" name="fname" value="{FIRST_NAME}" style="width:210px" class="textinput"></td>
                      </tr>
                      <tr>
                        <td>&nbsp;</td>
                        <td class="content2"> {TR_LAST_NAME}</td>
                        <td width="516" class="content"><input type="text" name="lname" value="{LAST_NAME}" style="width:210px" class="textinput"></td>
                      </tr>
                      <tr>
                        <td>&nbsp;</td>
                        <td class="content2">{TR_COMPANY}</td>
                        <td class="content"><input type="text" name="firm" value="{FIRM}" style="width:210px" class="textinput"></td>
                      </tr>
                      <tr>
                        <td>&nbsp;</td>
                        <td class="content2">{TR_STREET_1}</td>
                        <td class="content"><input type="text" name="street1" value="{STREET_1}" style="width:210px" class="textinput"></td>
                      </tr>
                      <tr>
                        <td>&nbsp;</td>
                        <td class="content2">{TR_STREET_2}</td>
                        <td class="content"><input type="text" name="street2" value="{STREET_2}" style="width:210px" class="textinput"></td>
                      </tr>
                      <tr>
                        <td>&nbsp;</td>
                        <td class="content2">{TR_ZIP_POSTAL_CODE}</td>
                        <td class="content"><input type="text" name="zip" value="{ZIP}" style="width:80px" class="textinput"></td>
                      </tr>
                      <tr>
                        <td>&nbsp;</td>
                        <td class="content2">{TR_CITY}</td>
                        <td class="content"><input type="text" name="city" value="{CITY}" style="width:210px" class="textinput"></td>
                      </tr>
                      <tr>
                        <td>&nbsp;</td>
                        <td class="content2">{TR_COUNTRY}</td>
                        <td class="content"><input type="text" name="country" value="{COUNTRY}" style="width:210px" class="textinput"></td>
                      </tr>
                      <tr>
                        <td>&nbsp;</td>
                        <td class="content2">{TR_EMAIL}</td>
                        <td class="content"><input type="text" name="email" value="{EMAIL}" style="width:210px" class="textinput"></td>
                      </tr>
                      <tr>
                        <td>&nbsp;</td>
                        <td class="content2">{TR_PHONE}</td>
                        <td class="content"><input type="text" name="phone" value="{PHONE}" style="width:210px" class="textinput"></td>
                      </tr>
                      <tr>
                        <td>&nbsp;</td>
                        <td class="content2">{TR_FAX}</td>
                        <td class="content"><input type="text" name="fax" value="{FAX}" style="width:210px" class="textinput"></td>
                      </tr>
                      <tr>
                        <td>&nbsp;</td>
                        <td colspan="2"><input name="Submit" type="submit" class="button" value="{TR_UPDATE_DATA}">
                            <input type="hidden" name="uaction" value="updt_data"></td>
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
