<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_RESELLER_MAIN_INDEX_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/vhcs.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/vhcs.js"></script>
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
            <td height="62" align="left" background="{THEME_COLOR_PATH}/images/content/table_background.jpg" class="title"><img src="{THEME_COLOR_PATH}/images/content/table_icon_general.jpg" width="85" height="62" align="absmiddle">{GENERAL_INFO}</td>
            <td width="27" align="right" background="{THEME_COLOR_PATH}/images/content/table_background.jpg"><img src="{THEME_COLOR_PATH}/images/content/table_icon_close.jpg" width="27" height="62"></td>
          </tr>
          <tr>
            <td>
			<!-- BDP: props_list -->
			<table width="100%" cellspacing="7">
              <tr>
                <td width="20">&nbsp;</td>
                <td class="content">{ACCOUNT_NAME}</td>
                <td width="230" class="content2">{RESELLER_NAME}</td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td>
                <td class="content">{DOMAINS}</td>
                <td width="230" class="content2">{DMN_USED}&nbsp;/&nbsp;{DMN_CURRENT}&nbsp;{TR_OF}&nbsp;<b>{DMN_MAX}</b></td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td>
                <td class="content">{SUBDOMAINS}</td>
                <td width="230" class="content2">{SUB_USED}&nbsp;/&nbsp;{SUB_CURRENT}&nbsp;{TR_OF}&nbsp;<b>{SUB_MAX}</b></td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td>
                <td class="content">{ALIASES}</td>
                <td width="230" class="content2">{ALS_USED}&nbsp;/&nbsp;{ALS_CURRENT}&nbsp;{TR_OF}&nbsp;<b>{ALS_MAX}</b></td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td>
                <td class="content">{MAIL_ACCOUNTS}</td>
                <td width="230" class="content2">{MAIL_USED}&nbsp;/&nbsp;{MAIL_CURRENT}&nbsp;{TR_OF}&nbsp;<b>{MAIL_MAX}</b></td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td>
                <td class="content">{TR_FTP_ACCOUNTS}</td>
                <td width="230" class="content2">{FTP_USED}&nbsp;/&nbsp;{FTP_CURRENT}&nbsp;{TR_OF}&nbsp;<b>{FTP_MAX}</b></td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td>
                <td class="content">{SQL_DATABASES}</td>
                <td width="230" class="content2">{SQL_DB_USED}&nbsp;/&nbsp;{SQL_DB_CURRENT}&nbsp;{TR_OF}&nbsp;<b>{SQL_DB_MAX}</b></td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td>
                <td class="content">{SQL_USERS}</td>
                <td width="230" class="content2">{SQL_USER_USED}&nbsp;/&nbsp;{SQL_USER_CURRENT}&nbsp;{TR_OF}&nbsp;<b>{SQL_USER_MAX}</b></td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td>
                <td class="content">{TRAFFIC}</td>
                <td width="230" class="content2">{TRAFF_USED}&nbsp;/&nbsp;{TRAFF_CURRENT}&nbsp;{TR_OF}&nbsp;<b>{TRAFF_MAX}</b></td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td>
                <td class="content">{DISK}</td>
                <td width="230" class="content2">{DISK_USED}&nbsp;/&nbsp;{DISK_CURRENT}&nbsp;{TR_OF}&nbsp;<b>{DISK_MAX}</b></td>
              </tr>
			  <!-- BDP: msg_entry -->
                <td>&nbsp;</td>
                <td colspan="2" class="title"><font color="#FF0000">{TR_YOU_HAVE}&nbsp;<b>{MSG_NUM}</b>&nbsp;{TR_NEW}&nbsp;{TR_MSG_TYPE}</font></td>
                </tr>
				<!-- EDP: msg_entry -->
			  
              <!--
			  <tr> 
                <td width="132" class="content">{TR_EXTRAS}</td>
                <td width="291" class="content2">{EXTRAS}</td>
              </tr>
			  -->
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
              <td height="62" align="left" background="{THEME_COLOR_PATH}/images/content/table_background.jpg" class="title"><img src="{THEME_COLOR_PATH}/images/content/table_icon_traffic.jpg" width="85" height="62" align="absmiddle">{TR_TRAFFIC_USAGE}</td>
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
                    <td class="content">{TRAFFIC_USAGE_DATA}</td>
                    </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td class="content"><table border="0" cellspacing="0" cellpadding="0">
                      <tr>
                        <td width="33"><img src="{THEME_COLOR_PATH}/images/stats_left.gif" width="33" height="20"></td>
                        <td width="405" background="{THEME_COLOR_PATH}/images/stats_background.gif"><table border="0" cellspacing="0" cellpadding="0">
                            <tr>
                              <td width="7"><img src="{THEME_COLOR_PATH}/images/bars/stats_left.gif" width="7" height="13"></td>
                              <td background="{THEME_COLOR_PATH}/images/bars/stats_background.gif"><img src="{THEME_COLOR_PATH}/images/trans.gif" width="{TRAFFIC_BARS}" height="1"></td>
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
          <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td height="62" align="left" background="{THEME_COLOR_PATH}/images/content/table_background.jpg" class="title"><img src="{THEME_COLOR_PATH}/images/content/table_icon_diskusage.jpg" width="85" height="62" align="absmiddle"><span class="title">{TR_DISK_USAGE}</span></td>
              <td width="27" align="right" background="{THEME_COLOR_PATH}/images/content/table_background.jpg"><img src="{THEME_COLOR_PATH}/images/content/table_icon_close.jpg" width="27" height="62"></td>
            </tr>
            <tr>
              <td><table width="100%" cellspacing="7">
			  	   <!-- BDP: disk_warn -->
				  <tr>
                    <td>&nbsp;</td>
                    <td class="title"><font color="#FF0000">{TR_DISK_WARNING}</font></td>
                  </tr>
				  <!-- EDP: disk_warn -->
                  <tr>
                    <td width="20">&nbsp;</td>
                    <td class="content">{DISK_USAGE_DATA}</td>
                    </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td class="content"><table border="0" cellspacing="0" cellpadding="0">
                      <tr>
                        <td width="33"><img src="{THEME_COLOR_PATH}/images/stats_left.gif" width="33" height="20"></td>
                        <td width="405" background="{THEME_COLOR_PATH}/images/stats_background.gif"><table border="0" cellspacing="0" cellpadding="0">
                          <tr>
                            <td width="7"><img src="{THEME_COLOR_PATH}/images/bars/stats_left.gif" width="7" height="13"></td>
                            <td background="{THEME_COLOR_PATH}/images/bars/stats_background.gif"><img src="{THEME_COLOR_PATH}/images/trans.gif" width="{DISK_BARS}" height="1"></td>
                            <td width="7"><img src="{THEME_COLOR_PATH}/images/bars/stats_right.gif" width="7" height="13"></td>
                          </tr>
                        </table></td>
                        <td width="33"><img src="{THEME_COLOR_PATH}/images/stats_right.gif" width="33" height="20"></td>
                      </tr>
                    </table>
                      </td>
                    </tr>
              </table></td>
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
