<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_ADMIN_DOMAIN_STATISTICS_PAGE_TITLE}</title>
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
            <td height="62" align="left" background="{THEME_COLOR_PATH}/images/content/table_background.jpg" class="title"><img src="{THEME_COLOR_PATH}/images/content/table_icon_stats.jpg" width="85" height="62" align="absmiddle">{TR_DOMAIN_STATISTICS}</td>
            <td width="27" align="right" background="{THEME_COLOR_PATH}/images/content/table_background.jpg"><img src="{THEME_COLOR_PATH}/images/content/table_icon_close.jpg" width="27" height="62"></td>
          </tr>
          <tr>
            <td valign="top">
			<!-- BDP: page_message -->
		    <table width="100%" cellspacing="7">
              <tr>
                <td width="20" nowrap>&nbsp;&nbsp;&nbsp;</td>
                <td>
				<form name="domain_statistics_frm" method="post" action="domain_statistics.php">
                      <table width="100%">
                        <tr>
                          <td width="80" class="content">{TR_MONTH}</td>
                          <td width="80" class="content"><span class="content2">
                            <select name="month">
                              <!-- BDP: month_list -->
                              <option {OPTION_SELECTED}>{MONTH_VALUE}</option>
                              <!-- EDP: month_list -->
                            </select>
                          </span>
                          </td>
                          <td width="80" class="content">{TR_YEAR}</td>
                          <td width="80" class="content"><span class="content2">
                            <select name="year">
                              <!-- BDP: year_list -->
                              <option {OPTION_SELECTED}>{YEAR_VALUE}</option>
                              <!-- EDP: year_list -->
                            </select>
                          </span>
                          </td>
                          <td class="content"><input name="Submit" type="submit" class="button" value="{TR_SHOW}">
                          </td>
                        </tr>
                      </table>
                      <table width="100%">
                        <tr align="center">
                          <td class="content3" nowrap height="25"><b>{TR_DAY}</b></td>
                          <td class="content3" nowrap height="25"><b>{TR_WEB_TRAFFIC}</b></td>
                          <td class="content3" nowrap height="25"><b>{TR_FTP_TRAFFIC}</b></td>
                          <td class="content3" nowrap height="25"><b>{TR_SMTP_TRAFFIC}</b></td>
                          <td class="content3" nowrap height="25"><b>{TR_POP3_TRAFFIC}</b></td>
                          <td class="content3" nowrap height="25"><b>{TR_ALL_TRAFFIC}</b></td>
                        </tr>
                        <!-- BDP: traffic_table_item -->
                        <tr>
                          <td class="{ITEM_CLASS}" nowrap align="center"><b>{DATE}</b></td>
                          <td class="{ITEM_CLASS}" nowrap align="center">{WEB_TRAFFIC}</td>
                          <td class="{ITEM_CLASS}" nowrap align="center"> {FTP_TRAFFIC} </td>
                          <td class="{ITEM_CLASS}" nowrap align="center">{SMTP_TRAFFIC}</td>
                          <td class="{ITEM_CLASS}" nowrap align="center">{POP3_TRAFFIC}</td>
                          <td class="{ITEM_CLASS}" nowrap align="center">{ALL_TRAFFIC}</td>
                        </tr>
                        <!-- EDP: traffic_table_item -->
                        <tr>
                          <td class="content3" nowrap align="center"><b> <b>{TR_ALL}</b></b></td>
                          <td class="content3" nowrap align="center"><b>{ALL_WEB_TRAFFIC}</b></td>
                          <td class="content3" nowrap align="center"><b> {ALL_FTP_TRAFFIC} </b></td>
                          <td class="content3" nowrap align="center"><b>{ALL_SMTP_TRAFFIC}</b></td>
                          <td class="content3" nowrap align="center"><b>{ALL_POP3_TRAFFIC}</b></td>
                          <td class="content3" nowrap align="center"><b>{ALL_ALL_TRAFFIC}</b></td>
                        </tr>
                      </table>
                      <!-- end of content -->
                      <input name="uaction" type="hidden" value="show_traff">
                      <input type="hidden" name="domain_id" value="{DOMAIN_ID}">
                  </form>
				</td>
              </tr>
            </table>  
		    <br>            
		    <!-- EDP: traffic_table -->
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
