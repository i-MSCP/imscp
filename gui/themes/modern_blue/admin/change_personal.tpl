<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_ADMIN_CHANGE_PERSONAL_DATA_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/vhcs.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/vhcs.js"></script>
</head>

<body onLoad="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif','{THEME_COLOR_PATH}/images/icons/logout_a.gif','{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<table width="100%" border="0" cellspacing="0" cellpadding="0" height="100%">
  <tr>
    <td height="80" align="left" valign="top">
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
            <td height="62" align="left" background="{THEME_COLOR_PATH}/images/content/table_background.jpg" class="title"><img src="{THEME_COLOR_PATH}/images/content/table_icon_doc.jpg" width="85" height="62" align="absmiddle">{TR_PERSONAL_DATA}</td>
            <td width="27" align="right" background="{THEME_COLOR_PATH}/images/content/table_background.jpg"><img src="{THEME_COLOR_PATH}/images/content/table_icon_close.jpg" width="27" height="62"></td>
          </tr>
          <tr>
            <td height="420"><form name="client_change_personal_frm" method="post" action="change_personal.php">
                <table width="100%" cellpadding="5" cellspacing="5">
                  <!-- BDP: page_message -->
                  <tr>
                    <td>&nbsp;</td>
                    <td colspan="2" class=title><font color="#FF0000">{MESSAGE}</font></td>
                  </tr>
                  <!-- EDP: page_message -->
                  <tr>
                    <td width="20">&nbsp;</td>
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
