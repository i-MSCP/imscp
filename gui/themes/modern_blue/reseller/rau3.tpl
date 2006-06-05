<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_ADD_USER_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/vhcs.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/vhcs.js"></script>
<script>
<!--

function change_status(dom_id) {
	if (!confirm("{TR_MESSAGE_CHANGE_STATUS}"))
		return false;

	location = ('change_status.php?domain_id=' + dom_id);
}

function delete_account(url) {
	if (!confirm("{TR_MESSAGE_DELETE_ACCOUNT}"))
		return false;

	location = url;
}
//-->
</script>

</head>
<body onLoad="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif','{THEME_COLOR_PATH}/images/icons/logout_a.gif','{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<table width="100%" border="0" cellspacing="0" cellpadding="0" height="100%">
  <tr>
    <td height="80" align="left" valign="top">
	<!-- BDP: logged_from --><table width="100%"  border="00" cellspacing="0" cellpadding="0">
      <tr>
        <td height="20" nowrap background="{THEME_COLOR_PATH}/images/button.gif">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.gif" width="18" height="18" border="0" align="absmiddle"></a> <font color="red">{YOU_ARE_LOGGED_AS}</font> </td>
      </tr>
    </table>
	<!-- EDP: logged_from -->
    <table width="100%"  border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td width="17"><img src="{THEME_COLOR_PATH}/images/top/left.jpg" width="17" height="80"></td>
          <td width="198" align="center" background="{THEME_COLOR_PATH}/images/top/logo_background.jpg"><img src="{ISP_LOGO}"></td>
          <td background="{THEME_COLOR_PATH}/images/top/left_fill.jpg"><img src="{THEME_COLOR_PATH}/images/top/left_fill.jpg" width="2" height="80"></td>
          <td width="766"><img src="{THEME_COLOR_PATH}/images/top/middle_background.jpg" width="766" height="80"></td>
          <td background="{THEME_COLOR_PATH}/images/top/right_fill.jpg"><img src="{THEME_COLOR_PATH}/images/top/right_fill.jpg" width="3" height="80"></td>
          <td width="9"><img src="{THEME_COLOR_PATH}/images/top/right.jpg" width="9" height="80"></td>
        </tr>
    </table></td>
  </tr>
  <tr>
    <td valign="top"><table height="100%" width="100%"  border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="215" valign="top" bgcolor="#F5F5F5"><!-- Menu begin -->
  {MENU}
    <!-- Menu end -->
        </td>
        <td valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td height="62" align="left" background="{THEME_COLOR_PATH}/images/content/table_background.jpg" class="title"><img src="{THEME_COLOR_PATH}/images/content/table_icon_user.jpg" width="85" height="62" align="absmiddle">{TR_ADD_USER}</td>
            <td width="27" align="right" background="{THEME_COLOR_PATH}/images/content/table_background.jpg"><img src="{THEME_COLOR_PATH}/images/content/table_icon_close.jpg" width="27" height="62"></td>
          </tr>
          <tr>
            <td valign="top">

			<!-- BDP: add_user -->
			<form name="reseller_add_users_first_frm" method="post" action="rau3.php">
			  <input type="hidden" name="uaction" value="rau3_nxt">
              <table width="100%" cellpadding="5" cellspacing="5">
                <!-- BDP: page_message -->
				<tr>
                  <td width="20">&nbsp;</td>
                  <td colspan="2" class="title"><font color="#FF0000">{MESSAGE}</font></td>
                </tr>
				<!-- EDP: page_message -->
                <tr>
                  <td width="20">&nbsp;</td>
                  <td colspan="2" class="content3"><b>{TR_CORE_DATA}</b></td>
                </tr>
                <tr>
                  <td width="20">&nbsp;</td>
                  <td class="content2" width="200">{TR_USERNAME}</td>
                  <td class="content">{VL_USERNAME}</td>
                </tr>
                <tr>
                  <td width="20">&nbsp;</td>
                  <td class="content2" width="200">{TR_PASSWORD}</td>
                  <td class="content"><input type="password" name=userpassword value="{VL_USR_PASS}" style="width:210px" class="textinput">
                  </td>
                </tr>
                <tr>
                  <td width="20">&nbsp;</td>
                  <td class="content2" width="200">{TR_REP_PASSWORD}</td>
                  <td class="content"><input type="password" name=userpassword_repeat value="{VL_USR_PASS_REP}" style="width:210px" class="textinput">
                  </td>
                </tr>
                <tr>
                  <td width="20">&nbsp;</td>
                  <td class="content2" width="200">{TR_DMN_IP}</td>
                  <td class="content"><select name="domain_ip">
                      <!-- BDP: ip_entry -->
                      <option value="{IP_VALUE}" {ip_selected}>{IP_NUM}&nbsp;({IP_NAME})</option>
                      <!-- EDP: ip_entry -->
                    </select>
                  </td>
                </tr>
                <tr>
                  <td width="20">&nbsp;</td>
                  <td class="content2" width="200">{TR_USREMAIL}</td>
                  <td class="content"><input type="text" name=useremail value="{VL_MAIL}" style="width:210px" class="textinput">
                  </td>
                </tr>
                <tr>
                  <td>&nbsp;</td>
                  <td class="content2">{TR_ADD_ALIASES}</td>
                  <td class="content"><input name="add_alias" type="checkbox" id="add_alias" value="on"></td>
                </tr>
                <tr>
                  <td width="20">&nbsp;</td>
                  <td colspan="2" class="content3"><b>{TR_ADDITIONAL_DATA}</b></td>
                </tr>
                <tr>
                  <td width="20">&nbsp;</td>
                  <td width="200" class="content2">{TR_CUSTOMER_ID}</td>
                  <td class="content"><input type="text" name=useruid value="{VL_USR_ID}" style="width:210px" class="textinput">
                  </td>
                </tr>
                <tr>
                  <td width="20">&nbsp;</td>
                  <td width="200" class="content2">{TR_FIRSTNAME}</td>
                  <td class="content"><input type="text" name=userfname value="{VL_USR_NAME}" style="width:210px" class="textinput">
                  </td>
                </tr>
                <tr>
                  <td width="20">&nbsp;</td>
                  <td width="200" class="content2">{TR_LASTNAME}</td>
                  <td class="content"><input type="text" name=userlname value="{VL_LAST_USRNAME}" style="width:210px" class="textinput">
                  </td>
                </tr>
                <tr>
                  <td width="20">&nbsp;</td>
                  <td width="200" class="content2">{TR_COMPANY}</td>
                  <td class="content"><input type="text" name=userfirm value="{VL_USR_FIRM}" style="width:210px" class="textinput">
                  </td>
                </tr>
                <tr>
                  <td width="20">&nbsp;</td>
                  <td width="200" class="content2">{TR_STREET1}</td>
                  <td class="content"><input type="text" name=userstreet1 value="{VL_STREET1}" style="width:210px" class="textinput">
                  </td>
                </tr>
                <tr>
                  <td width="20">&nbsp;</td>
                  <td width="200" class="content2">{TR_STREET2}</td>
                  <td class="content"><input type="text" name=userstreet2 value="{VL_STREET2}" style="width:210px" class="textinput">
                  </td>
                </tr>
                <tr>
                  <td width="20">&nbsp;</td>
                  <td width="200" class="content2">{TR_POST_CODE}</td>
                  <td class="content"><input type="text" name=userzip value="{VL_USR_POSTCODE}" style="width:80px" class="textinput">
                  </td>
                </tr>
                <tr>
                  <td width="20">&nbsp;</td>
                  <td width="200" class="content2">{TR_CITY}</td>
                  <td class="content"><input type="text" name=usercity value="{VL_USRCITY}" style="width:210px" class="textinput">
                  </td>
                </tr>
                <tr>
                  <td width="20">&nbsp;</td>
                  <td width="200" class="content2">{TR_COUNTRY}</td>
                  <td class="content"><input type="text" name=usercountry value="{VL_COUNTRY}" style="width:210px" class="textinput">
                  </td>
                </tr>
                <tr>
                  <td width="20">&nbsp;</td>
                  <td width="200" class="content2">{TR_PHONE}</td>
                  <td class="content"><input type="text" name=userphone value="{VL_PHONE}" style="width:210px" class="textinput">
                  </td>
                </tr>
                <tr>
                  <td width="20">&nbsp;</td>
                  <td width="200" class="content2">{TR_FAX}</td>
                  <td class="content"><input type="text" name=userfax value="{VL_FAX}" style="width:210px" class="textinput">
                  </td>
                </tr>
                <tr>
                  <td>&nbsp;
                    </td>
                  <td colspan="2"><font color="#FF0000">
                    <input name="Submit" type="submit" class="button" value="  {TR_BTN_ADD_USER}  ">
                  </font></td>
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
        </table>
          </td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td height="71"><table width="100%"  border="0" cellspacing="0" cellpadding="0">
        <tr><td width="17"><img src="{THEME_COLOR_PATH}/images/top/down_left.jpg" width="17" height="71"></td><td width="198" valign="top" background="{THEME_COLOR_PATH}/images/top/downlogo_background.jpg"><table width="100%" border="0" cellpadding="0" cellspacing="0" >
          <tr>
            <td width="55"><a href="http://www.vhcs.net" target="_blank"><img src="{THEME_COLOR_PATH}/images/vhcs.gif" alt="" width="51" height="71" border="0"></a></td>
            <td class="bottom">{VHCS_LICENSE}</td>
          </tr>
        </table>          </td>
          <td background="{THEME_COLOR_PATH}/images/top/down_left_fill.jpg"><img src="{THEME_COLOR_PATH}/images/top/down_left_fill.jpg" width="2" height="71"></td><td width="766" background="{THEME_COLOR_PATH}/images/top/middle_background.jpg"><img src="{THEME_COLOR_PATH}/images/top/down_middle_background.jpg" width="766" height="71"></td>
          <td background="{THEME_COLOR_PATH}/images/top/down_right_fill.jpg"><img src="{THEME_COLOR_PATH}/images/top/down_right_fill.jpg" width="3" height="71"></td>
          <td width="9"><img src="{THEME_COLOR_PATH}/images/top/down_right.jpg" width="9" height="71"></td></tr>
    </table></td>
  </tr>
</table>
</body>
</html>
