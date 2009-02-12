<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_ADD_USER_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex">
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
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
                      <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_user.png" width="25" height="25"></td>
                      <td colspan="2" class="title">{TR_ADD_USER}</td>
                    </tr>
                </table></td>
                <td width="27" align="right">&nbsp;</td>
              </tr>
              <tr>
                <td valign="top"><!-- BDP: add_user -->
                    <form name="reseller_add_users_first_frm" method="post" action="user_add3.php">
                      <input type="hidden" name="uaction" value="user_add3_nxt">
                      <table width="100%" cellpadding="5" cellspacing="5">
                        <!-- BDP: page_message -->
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td colspan="2" class="title"><span class="message">{MESSAGE}</span></td>
                        </tr>
                        <!-- EDP: page_message -->
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td colspan="2" class="content3"><b>{TR_CORE_DATA}</b></td>
                        </tr>
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td class="content2" width="200">{TR_USERNAME}</td>
                          <td class="content">{VL_USERNAME}</td>
                        </tr>
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td class="content2" width="200">{TR_PASSWORD}</td>
                          <td class="content"><input type="password" name="userpassword" value="{VL_USR_PASS}" style="width:210px" class="textinput">
                          </td>
                        </tr>
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td class="content2" width="200">{TR_REP_PASSWORD}</td>
                          <td class="content"><input type="password" name="userpassword_repeat" value="{VL_USR_PASS}" style="width:210px" class="textinput">
                          </td>
                        </tr>
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td class="content2" width="200">{TR_DMN_IP}</td>
                          <td class="content"><select name="domain_ip">
                              <!-- BDP: ip_entry -->
                              <option value="{IP_VALUE}" {IP_SELECTED}>{IP_NUM}&nbsp;({IP_NAME})</option>
                              <!-- EDP: ip_entry -->
                            </select>
                          </td>
                        </tr>
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td class="content2" width="200">{TR_USREMAIL}</td>
                          <td class="content"><input type="text" name="useremail" value="{VL_MAIL}" style="width:210px" class="textinput">
                          </td>
                        </tr>
                        <tr>
                          <td>&nbsp;</td>
                          <td class="content2">{TR_ADD_ALIASES}</td>
                          <td class="content"><input name="add_alias" type="checkbox" id="add_alias" value="on"></td>
                        </tr>
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td colspan="2" class="content3"><b>{TR_ADDITIONAL_DATA}</b></td>
                        </tr>
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td width="200" class="content2">{TR_CUSTOMER_ID}</td>
                          <td class="content"><input type="text" name="useruid" value="{VL_USR_ID}" style="width:210px" class="textinput">
                          </td>
                        </tr>
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td width="200" class="content2">{TR_FIRSTNAME}</td>
                          <td class="content"><input type="text" name="userfname" value="{VL_USR_NAME}" style="width:210px" class="textinput">
                          </td>
                        </tr>
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td width="200" class="content2">{TR_LASTNAME}</td>
                          <td class="content"><input type="text" name="userlname" value="{VL_LAST_USRNAME}" style="width:210px" class="textinput"></td>
                        </tr>
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td width="200" class="content2">{TR_GENDER}</td>
                          <td class="content">
                            <select name="gender" size="1">
                               <option value="M" {VL_MALE}>{TR_MALE}</option>
                               <option value="F" {VL_FEMALE}>{TR_FEMALE}</option>
                               <option value="U" {VL_UNKNOWN}>{TR_UNKNOWN}</option>
                            </select>
                          </td>
                        </tr>
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td width="200" class="content2">{TR_COMPANY}</td>
                          <td class="content"><input type="text" name="userfirm" value="{VL_USR_FIRM}" style="width:210px" class="textinput">
                          </td>
                        </tr>
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td width="200" class="content2">{TR_STREET1}</td>
                          <td class="content"><input type="text" name="userstreet1" value="{VL_STREET1}" style="width:210px" class="textinput">
                          </td>
                        </tr>
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td width="200" class="content2">{TR_STREET2}</td>
                          <td class="content"><input type="text" name="userstreet2" value="{VL_STREET2}" style="width:210px" class="textinput">
                          </td>
                        </tr>
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td width="200" class="content2">{TR_POST_CODE}</td>
                          <td class="content"><input type="text" name="userzip" value="{VL_USR_POSTCODE}" style="width:80px" class="textinput">
                          </td>
                        </tr>
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td width="200" class="content2">{TR_CITY}</td>
                          <td class="content"><input type="text" name="usercity" value="{VL_USRCITY}" style="width:210px" class="textinput">
                          </td>
                        </tr>
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td width="200" class="content2">{TR_COUNTRY}</td>
                          <td class="content"><input type="text" name="usercountry" value="{VL_COUNTRY}" style="width:210px" class="textinput">
                          </td>
                        </tr>
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td width="200" class="content2">{TR_PHONE}</td>
                          <td class="content"><input type="text" name="userphone" value="{VL_PHONE}" style="width:210px" class="textinput">
                          </td>
                        </tr>
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td width="200" class="content2">{TR_FAX}</td>
                          <td class="content"><input type="text" name="userfax" value="{VL_FAX}" style="width:210px" class="textinput">
                          </td>
                        </tr>
                        <tr>
                          <td>&nbsp;</td>
                          <td colspan="2"><input name="Submit" type="submit" class="button" value="  {TR_BTN_ADD_USER}  " /></td>
                        </tr>
                      </table>
                    </form>
                  <!-- EDP: add_user -->
                </td>
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
