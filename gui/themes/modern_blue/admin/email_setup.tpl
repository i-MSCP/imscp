<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_ADMIN_MANAGE_EMAIL_SETUP_PAGE_TITLE}</title>
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
            <td height="62" align="left" background="{THEME_COLOR_PATH}/images/content/table_background.jpg" class="title"><img src="{THEME_COLOR_PATH}/images/content/table_icon_email.jpg" width="85" height="62" align="absmiddle">{TR_EMAIL_SETUP}</td>
            <td width="27" align="right" background="{THEME_COLOR_PATH}/images/content/table_background.jpg"><img src="{THEME_COLOR_PATH}/images/content/table_icon_close.jpg" width="27" height="62"></td>
          </tr>
          <tr>
            <td><form name="admin_email_setup" method="post" action="email_setup.php">
                <table width="100%" cellpadding="5" cellspacing="5">
                  <tr>
                    <td width="20">&nbsp;</td>
                    <td colspan="2" class="content3"><b>{TR_MESSAGE_TEMPLATE_INFO}</b></td>
                  </tr>
                  <tr>
                    <td width="20">&nbsp;</td>
                    <td width="200" class="content2">{TR_USER_LOGIN_NAME}</td>
                    <td  class="content">{USERNAME} </td>
                  </tr>
                  <tr>
                    <td width="20">&nbsp;</td>
                    <td class="content2">{TR_USER_PASSWORD}</td>
                    <td  class="content">{PASSWORD}</td>
                  </tr>
                  <tr>
                    <td width="20">&nbsp;</td>
                    <td class="content2">{TR_USER_REAL_NAME}</td>
                    <td  class="content">{NAME}</td>
                  </tr>
                </table>
                <br>
                <table width="100%" cellpadding="5" cellspacing="5">
                  <tr>
                    <td width="20">&nbsp;</td>
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
                    <td><input type="text" name="auto_subject" value="{SUBJECT_VALUE}" style="width:270px" class="textinput">
                    </td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td class="content2" width="200">{TR_MESSAGE}</td>
                    <td><textarea name="auto_message" style="width:270px" class="textinput2" rows="8">{MESSAGE_VALUE}</textarea>
                    </td>
                  </tr>
                  <tr>
                    <td width="20">&nbsp;</td>
                    <td width="200" class="content2">{TR_SENDER_EMAIL}</td>
                    <td class="content">{SENDER_EMAIL_VALUE}
                        <input type="hidden" name="sender_email" value="{SENDER_EMAIL_VALUE}" style="width:270px" class="textinput">
                    </td>
                  </tr>
                  <tr>
                    <td width="20">&nbsp;</td>
                    <td width="200" class="content2">{TR_SENDER_NAME}</td>
                    <td class="content">{SENDER_NAME_VALUE}
                        <input type="hidden" name="sender_name" value="{SENDER_NAME_VALUE}" style="width:270px" class="textinput">
                    </td>
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
