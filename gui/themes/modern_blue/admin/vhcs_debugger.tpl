<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_ADMIN_VHCS_DEBUGGER_PAGE_TITLE}</title>
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
            <td height="62" align="left" background="{THEME_COLOR_PATH}/images/content/table_background.jpg" class="title"><img src="{THEME_COLOR_PATH}/images/content/table_icon_debugger.jpg" width="85" height="62" align="absmiddle">{TR_DEBUGGER_TITLE}</td>
            <td width="27" align="right" background="{THEME_COLOR_PATH}/images/content/table_background.jpg"><img src="{THEME_COLOR_PATH}/images/content/table_icon_close.jpg" width="27" height="62"></td>
          </tr>
          <tr>
            <td>
			<!-- BDP: props_list -->
			<table width="100%" cellpadding="5" cellspacing="5">
              <tr>
                <td width="20">&nbsp;</td>
                <td class="content3"><b>{TR_DOMAIN_ERRORS}</b></td>
              </tr>
              <!-- BDP: domain_message -->
              <tr>
                <td>&nbsp;</td>
                <td>{TR_DOMAIN_MESSAGE}</td>
              </tr>
              <!-- EDP: domain_message -->
              <!-- BDP: domain_list -->
              <tr>
                <td>&nbsp;</td>
                <td class="{CONTENT}">{TR_DOMAIN_NAME}<br>
                    <font color="red">{TR_DOMAIN_ERROR}</font></td>
              </tr>
              <!-- EDP: domain_list -->
            </table>
			<br>
            <table width="100%" cellpadding="5" cellspacing="5">
              <tr>
                <td width="20">&nbsp;</td>
                <td class="content3"><b>{TR_ALIAS_ERRORS}</b></td>
              </tr>
              <!-- BDP: alias_message -->
              <tr>
                <td>&nbsp;</td>
                <td>{TR_ALIAS_MESSAGE}</td>
              </tr>
              <!-- EDP: alias_message -->
              <!-- BDP: alias_list -->
              <tr>
                <td>&nbsp;</td>
                <td class="{CONTENT}">{TR_ALIAS_NAME}<br>
                    <font color="red">{TR_ALIAS_ERROR}</font></td>
              </tr>
              <!-- EDP: alias_list -->
            </table>
            <br>
            <table width="100%" cellpadding="5" cellspacing="5">
              <tr>
                <td width="20">&nbsp;</td>
                <td class="content3"><b>{TR_SUBDOMAIN_ERRORS}</b></td>
              </tr>
              <!-- BDP: subdomain_message -->
              <tr>
                <td>&nbsp;</td>
                <td>{TR_SUBDOMAIN_MESSAGE}</td>
              </tr>
              <tr>
                <!-- EDP: subdomain_message -->
                <!-- BDP: subdomain_list -->
              <tr>
                <td>&nbsp;</td>
                <td class="{CONTENT}">{TR_SUBDOMAIN_NAME}<br>
                    <font color="red">{TR_SUBDOMAIN_ERROR}</font></td>
              </tr>
              <!-- EDP: subdomain_list -->
            </table>
            <br>
            <table width="100%" cellpadding="5" cellspacing="5">
              <tr>
                <td width="20">&nbsp;</td>
                <td class="content3"><b>{TR_MAIL_ERRORS}</b></td>
              </tr>
              <!-- BDP: mail_message -->
              <tr>
                <td>&nbsp;</td>
                <td>{TR_MAIL_MESSAGE}</td>
              </tr>
              <!-- EDP: mail_message -->
              <!-- BDP: mail_list -->
              <tr>
                <td>&nbsp;</td>
                <td class="{CONTENT}">{TR_MAIL_NAME}<br>
                    <font color="red">{TR_MAIL_ERROR}</font></td>
              </tr>
              <!-- EDP: mail_list -->
            </table>
            <!-- EDP: props_list -->
			
			
			
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
