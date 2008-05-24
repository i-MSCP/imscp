<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_ADMIN_DOMAIN_STATISTICS_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<!-- BDP: logged_from --><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td height="20" nowrap background="{THEME_COLOR_PATH}/images/button.gif">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.png" width="18" height="18" border="0" align="absmiddle"></a> <font color="red">{YOU_ARE_LOGGED_AS}</font></td>
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
                      <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_stats.png" width="25" height="25"></td>
                      <td colspan="2" class="title">{TR_DOMAIN_STATISTICS}</td>
                    </tr>
                </table></td>
                <td width="27" align="right">&nbsp;</td>
              </tr>
              <tr>
                <td><table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td width="40">&nbsp;</td>
                      <td valign="top"><form name="domain_statistics_frm" method="post" action="domain_statistics.php">
                          <table width="100%">
                            <tr>
                              <td width="80" class="content">{TR_MONTH}</td>
                              <td width="80" class="content"><span class="content2"><select name="month">
                                  <!-- BDP: month_list -->
                                  <option {OPTION_SELECTED}>{MONTH_VALUE}</option>
                                  <!-- EDP: month_list -->
                                </select></span></td>
                              <td width="80" class="content">{TR_YEAR}</td>
                              <td width="80" class="content"><span class="content2"><select name="year">
                                  <!-- BDP: year_list -->
                                  <option {OPTION_SELECTED}>{YEAR_VALUE}</option>
                                  <!-- EDP: year_list -->
                                </select></span></td>
                              <td class="content"><input name="Submit" type="submit" class="button" value="{TR_SHOW}" /></td>
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
                              <td class="{ITEM_CLASS}" nowrap align="center"> {FTP_TRAFFIC}</td>
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
                          <input name="uaction" type="hidden" value="show_traff" />
                          <input type="hidden" name="domain_id" value="{DOMAIN_ID}" />
                      </form></td>
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
</table>
</body>
</html>
