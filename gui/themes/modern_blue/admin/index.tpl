<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_ADMIN_MAIN_INDEX_PAGE_TITLE}</title>
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
            <td height="62" align="left" background="{THEME_COLOR_PATH}/images/content/table_background.jpg" class="title"><img src="{THEME_COLOR_PATH}/images/content/table_icon_general.jpg" width="85" height="62" align="absmiddle">{TR_GENERAL_INFORMATION}</td>
            <td width="27" align="right" background="{THEME_COLOR_PATH}/images/content/table_background.jpg"><img src="{THEME_COLOR_PATH}/images/content/table_icon_close.jpg" width="27" height="62"></td>
          </tr>
          <tr>
            <td>
			<!-- BDP: props_list -->
			<table width="100%" cellpadding="5" cellspacing="5">
              <tr>
                <td width="20">&nbsp;</td>
                <td class="content">{TR_ACCOUNT_NAME}</td>
                <td width="230" class="content2">{ACCOUNT_NAME}</td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td>
                <td class="content">{TR_ADMIN_USERS}</td>
                <td width="230" class="content2">{ADMIN_USERS}</td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td>
                <td class="content">{TR_RESELLER_USERS}</td>
                <td width="230" class="content2">{RESELLER_USERS}</td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td>
                <td class="content">{TR_NORMAL_USERS}</td>
                <td width="230" class="content2">{NORMAL_USERS}</td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td>
                <td class="content">{TR_DOMAINS}</td>
                <td width="230" class="content2">{DOMAINS}</td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td>
                <td class="content">{TR_SUBDOMAINS}</td>
                <td width="230" class="content2">{SUBDOMAINS}</td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td>
                <td class="content">{TR_DOMAINS_ALIASES}</td>
                <td width="230" class="content2">{DOMAINS_ALIASES}</td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td>
                <td class="content">{TR_MAIL_ACCOUNTS}</td>
                <td width="230" class="content2">{MAIL_ACCOUNTS}</td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td>
                <td class="content">{TR_FTP_ACCOUNTS}</td>
                <td width="230" class="content2">{FTP_ACCOUNTS}</td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td>
                <td class="content">{TR_SQL_DATABASES}</td>
                <td width="230" class="content2">{SQL_DATABASES}</td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td>
                <td class="content">{TR_SQL_USERS}</td>
                <td width="230" class="content2">{SQL_USERS}</td>
              </tr>
			  <!-- BDP: msg_entry -->
                <td>&nbsp;</td>
                <td colspan="2" class="title"><font color="#FF0000">{TR_YOU_HAVE}&nbsp;<b>{MSG_NUM}</b>&nbsp;{TR_NEW}&nbsp;{TR_MSG_TYPE}</font></td>
                </tr>
				<!-- EDP: msg_entry -->
				<!-- BDP: update_message -->
              <tr>
                <td width="20">&nbsp;</td>
                <td colspan="2" style="color:ff0000"><b>{UPDATE}</b></td>
                </tr>
              <!-- EDP: update_message -->
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
          <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td height="62" align="left" background="{THEME_COLOR_PATH}/images/content/table_background.jpg" class="title"><img src="{THEME_COLOR_PATH}/images/content/table_icon_traffic.jpg" width="85" height="62" align="absmiddle">{TR_SERVER_TRAFFIC}</td>
              <td width="27" align="right" background="{THEME_COLOR_PATH}/images/content/table_background.jpg"><img src="{THEME_COLOR_PATH}/images/content/table_icon_close.jpg" width="27" height="62"></td>
            </tr>
            <tr>
              <td><table width="100%" cellspacing="7">
                  <!-- BDP: traff_warn -->
				  <tr>
                    <td>&nbsp;</td>
                    <td class="title"><font color="#FF0000">{TR_TRAFFIC_WARNING}</font></td>
                  </tr>
				  <!-- EDP: traff_warn -->
                  <tr>
                    <td width="20">&nbsp;</td>
                    <td class="content">{PERCENT} % [{VALUE} {TR_OF} {MAX_VALUE}]</td>
                    </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td class="content"><table border="0" cellspacing="0" cellpadding="0">
                      <tr>
                        <td width="33"><img src="{THEME_COLOR_PATH}/images/stats_left.gif" width="33" height="20"></td>
                        <td width="405" background="{THEME_COLOR_PATH}/images/stats_background.gif"><table border="0" cellspacing="0" cellpadding="0">
                          <tr>
                            <td width="7"><img src="{THEME_COLOR_PATH}/images/bars/stats_left.gif" width="7" height="13"></td>
                            <td background="{THEME_COLOR_PATH}/images/bars/stats_background.gif"><img src="../images/trans.gif" width="{BAR_VALUE}" height="1"></td>
                            <td width="7"><img src="{THEME_COLOR_PATH}/images/bars/stats_right.gif" width="7" height="13"></td>
                          </tr>
                        </table></td>
                        <td width="33"><img src="{THEME_COLOR_PATH}/images/stats_right.gif" width="33" height="20"></td>
                      </tr>
                    </table></td>
                    </tr>
              </table></td>
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
        <tr><td width="17"><img src="{THEME_COLOR_PATH}/images/top/down_left.jpg" width="17" height="71"></td>
		<td width="198" valign="top" background="{THEME_COLOR_PATH}/images/top/downlogo_background.jpg">
		<table border="0" cellpadding="0" cellspacing="0" >
          <tr>
            <td width="55"><a href="http://www.vhcs.net" target="_blank"><img src="{THEME_COLOR_PATH}/images/vhcs.gif" alt="" width="51" height="71" border="0"></a></td>
            <td class="bottom">{VHCS_LICENSE}</td>
          </tr>
        </table></td>
          <td background="{THEME_COLOR_PATH}/images/top/down_left_fill.jpg"><img src="{THEME_COLOR_PATH}/images/top/down_left_fill.jpg" width="2" height="71"></td><td width="766" background="{THEME_COLOR_PATH}/images/top/middle_background.jpg"><img src="{THEME_COLOR_PATH}/images/top/down_middle_background.jpg" width="766" height="71"></td>
          <td background="{THEME_COLOR_PATH}/images/top/down_right_fill.jpg"><img src="{THEME_COLOR_PATH}/images/top/down_right_fill.jpg" width="3" height="71"></td>
          <td width="9"><img src="{THEME_COLOR_PATH}/images/top/down_right.jpg" width="9" height="71"></td></tr>
    </table></td>
  </tr>
</table>
</body>
</html>
