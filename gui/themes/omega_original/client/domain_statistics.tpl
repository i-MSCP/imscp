<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_CLIENT_DOMAIN_STATISTICS_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/ftp_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/email_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="height:100%;padding:0;margin:0 auto;">
<!-- BDP: logged_from -->
<tr>
 <td colspan="3" height="20" nowrap="nowrap" class="backButton">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.png" width="16" height="16" border="0" style="vertical-align:middle" alt="" /></a> {YOU_ARE_LOGGED_AS}</td>
</tr>
<!-- EDP: logged_from -->
<tr>
<td align="left" valign="top" style="vertical-align: top; width: 195px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_left.jpg" width="195" height="56" border="0" alt="ispCP Logogram" /></td>
<td style="height: 56px; width:100%; background-color: #0f0f0f"><img src="{THEME_COLOR_PATH}/images/top/top_left_bg.jpg" width="582" height="56" border="0" alt="" /></td>
<td style="width: 73px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_right.jpg" width="73" height="56" border="0" alt="" /></td>
</tr>
	<tr>
		<td style="width: 195px; vertical-align: top;">{MENU}</td>
	    <td colspan="2" style="vertical-align: top;"><table style="width: 100%; padding:0;margin:0;" cellspacing="0">
          <tr style="height:95px;">
            <td style="padding-left:30px; width: 100%; background-image: url({THEME_COLOR_PATH}/images/top/middle_bg.jpg);">{MAIN_MENU}</td>
            <td style="padding:0;margin:0;text-align: right; width: 73px;vertical-align: top;"><img src="{THEME_COLOR_PATH}/images/top/middle_right.jpg" width="73" height="95" border="0" alt="" /></td>
          </tr>
          <tr>
            <td colspan="3"><table width="100%" border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td align="left"><table width="100%" cellpadding="5" cellspacing="5">
                    <tr>
                      <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_stats.png" width="25" height="25" alt="" /></td>
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
                              <td width="80" class="content"><select name="month">
                                  <!-- BDP: month_item -->
                                  <option {MONTH_SELECTED}>{MONTH}</option>
                                  <!-- EDP: month_item -->
                                </select></td>
                              <td width="80" class="content">{TR_YEAR}</td>
                              <td width="80" class="content"><select name="year">
                                  <!-- BDP: year_item -->
                                  <option {YEAR_SELECTED}>{YEAR}</option>
                                  <!-- EDP: year_item -->
                                </select></td>
                              <td class="content"><input name="Submit" type="submit" class="button" value="{TR_SHOW}" />
                              </td>
                            </tr>
                          </table>
                        <table width="100%" cellspacing="3">
                            <tr align="center">
                              <td class="content3" nowrap="nowrap" height="25"><b>{TR_DATE}</b></td>
                              <td class="content3" nowrap="nowrap" height="25"><b>{TR_WEB_TRAFF}</b></td>
                              <td class="content3" nowrap="nowrap" height="25"><b>{TR_FTP_TRAFF}</b></td>
                              <td class="content3" nowrap="nowrap" height="25"><b>{TR_SMTP_TRAFF}</b></td>
                              <td class="content3" nowrap="nowrap" height="25"><b>{TR_POP_TRAFF}</b></td>
                              <td class="content3" nowrap="nowrap" height="25"><b>{TR_SUM}</b></td>
                            </tr>
                            <!-- BDP: page_message -->
                            <tr>
                              <td colspan="5" class="title"><span class="message">{MESSAGE}</span></td>
                            </tr>
                            <!-- EDP: page_message -->
                            <!-- BDP: traff_list -->
                            <!-- BDP: traff_item -->
                            <tr class="hl">
                              <td class="{CONTENT}" nowrap="nowrap" align="center">{DATE}</td>
                              <td class="{CONTENT}" nowrap="nowrap" align="center" valign="top">{WEB_TRAFF}</td>
                              <td class="{CONTENT}" nowrap="nowrap" align="center" valign="top">{FTP_TRAFF}</td>
                              <td class="{CONTENT}" nowrap="nowrap" align="center" valign="top">{SMTP_TRAFF}</td>
                              <td class="{CONTENT}" nowrap="nowrap" align="center" valign="top">{POP_TRAFF}</td>
                              <td class="{CONTENT}" nowrap="nowrap" align="center">{SUM_TRAFF}</td>
                            </tr>
                            <!-- EDP: traff_item -->
                            <tr>
                              <td class="content3" nowrap="nowrap" align="center"><b>{TR_ALL}</b></td>
                              <td class="content3" nowrap="nowrap" align="center" valign="top"><b>{WEB_ALL}</b></td>
                              <td class="content3" nowrap="nowrap" align="center" valign="top"><b>{FTP_ALL}</b></td>
                              <td class="content3" nowrap="nowrap" align="center" valign="top"><b>{SMTP_ALL}</b></td>
                              <td class="content3" nowrap="nowrap" align="center" valign="top"><b>{POP_ALL}</b></td>
                              <td class="content3" nowrap="nowrap" align="center"><b>{SUM_ALL}</b></td>
                            </tr>
                            <!-- EDP: traff_list -->
                          </table>
                        <!-- end of content -->
                          <input name="uaction" type="hidden" value="show_traff" /></form></td>
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
