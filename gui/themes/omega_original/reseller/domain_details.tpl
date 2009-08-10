<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_DETAILS_DOMAIN_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<!-- BDP: logged_from --><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td height="20" nowrap="nowrap" class="backButton">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.png" width="16" height="16" border="0" style="vertical-align:middle" alt="" /></a> {YOU_ARE_LOGGED_AS}</td>
      </tr>
    </table>
	<!-- EDP: logged_from -->
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="height:100%;padding:0;margin:0 auto;">
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
                      <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_domains.png" width="25" height="25" alt="" /></td>
                      <td colspan="2" class="title">{TR_DOMAIN_DETAILS}</td>
                    </tr>
                </table></td>
                <td width="27" align="right">&nbsp;</td>
              </tr>
              <tr>
                <td><table width="100%" cellpadding="5" cellspacing="5" class="hl">
                    <tr>
                      <td width="25">&nbsp;</td>
                      <td class="content2" width="193">{TR_DOMAIN_NAME}</td>
                      <td class="content" colspan="2">{VL_DOMAIN_NAME}</td>
                    </tr>
                    <tr>
                      <td width="25">&nbsp;</td>
                      <td class="content2" width="193">{TR_DOMAIN_IP}</td>
                      <td class="content" colspan="2">{VL_DOMAIN_IP}</td>
                    </tr>
                    <tr>
                      <td width="25">&nbsp;</td>
                      <td class="content2" width="193">{TR_STATUS}</td>
                      <td class="content" colspan="2">{VL_STATUS}</td>
                    </tr>
                    <tr>
                      <td width="25">&nbsp;</td>
                      <td class="content2" width="193">{TR_PHP_SUPP}</td>
                      <td class="content" colspan="2">{VL_PHP_SUPP}</td>
                    </tr>
                    <tr>
                      <td width="25">&nbsp;</td>
                      <td class="content2" width="193">{TR_CGI_SUPP}</td>
                      <td class="content" colspan="2">{VL_CGI_SUPP}</td>
                    </tr>
                    <tr>
                      <td width="25">&nbsp;</td>
                      <td class="content2" width="193">{TR_DNS_SUPP}</td>
                      <td class="content" colspan="2">{VL_DNS_SUPP}</td>
                    </tr>
                    <tr>
                      <td width="25">&nbsp;</td>
                      <td class="content2" width="193">{TR_MYSQL_SUPP}</td>
                      <td class="content" colspan="2">{VL_MYSQL_SUPP}</td>
                    </tr>
                    <tr>
                      <td width="25">&nbsp;</td>
                      <td class="content2" width="193">{TR_TRAFFIC}</td>
                      <td colspan="2" class="content"><table width="252" border="0" cellspacing="0" cellpadding="0">
                          <tr>
                            <td width="13"><img src="{THEME_COLOR_PATH}/images/bars/stats_left_small.gif" width="13" height="20" alt="" /></td>
                            <td class="stats"><table border="0" cellspacing="0" cellpadding="0" align="left">
                                <tr>
                                  <td width="7"><img src="{THEME_COLOR_PATH}/images/bars/stats_left.gif" width="7" height="13" alt="" /></td>
                                  <td class="statsBar"><img src="{THEME_COLOR_PATH}/images/trans.gif" width="{VL_TRAFFIC_PERCENT}" height="1" alt="" /></td>
                                  <td width="7"><img src="{THEME_COLOR_PATH}/images/bars/stats_right.gif" width="7" height="13" alt="" /></td>
                                </tr>
                            </table></td>
                            <td width="13"><img src="{THEME_COLOR_PATH}/images/bars/stats_right_small.gif" width="13" height="20" alt="" /></td>
                          </tr>
                        </table>
                          <br />
                        {VL_TRAFFIC_USED} / {VL_TRAFFIC_LIMIT} </td>
                    </tr>
                    <tr>
                      <td width="25">&nbsp;</td>
                      <td class="content2" width="193">{TR_DISK}</td>
                      <td colspan="2" class="content"><table width="252" border="0" cellspacing="0" cellpadding="0">
                          <tr>
                            <td width="13"><img src="{THEME_COLOR_PATH}/images/bars/stats_left_small.gif" width="13" height="20" alt="" /></td>
                            <td class="stats"><table border="0" cellspacing="0" cellpadding="0" align="left">
                                <tr>
                                  <td width="7"><img src="{THEME_COLOR_PATH}/images/bars/stats_left.gif" width="7" height="13" alt="" /></td>
                                  <td class="statsBar"><img src="{THEME_COLOR_PATH}/images/trans.gif" width="{VL_DISK_PERCENT}" height="1" alt="" /></td>
                                  <td width="7"><img src="{THEME_COLOR_PATH}/images/bars/stats_right.gif" width="7" height="13" alt="" /></td>
                                </tr>
                            </table></td>
                            <td width="13"><img src="{THEME_COLOR_PATH}/images/bars/stats_right_small.gif" width="13" height="20" alt="" /></td>
                          </tr>
                        </table>
                          <br />
                        {VL_DISK_USED} / {VL_DISK_LIMIT}</td>
                    </tr>
                    <tr>
                      <td>&nbsp;</td>
                      <td class="content3"><strong>{TR_FEATURE}</strong></td>
                      <td width="200" class="content3"><strong>{TR_USED}</strong></td>
                      <td class="content3"><strong>{TR_LIMIT}</strong></td>
                    </tr>
                    <tr>
                      <td width="25">&nbsp;</td>
                      <td class="content2" width="193">{TR_MAIL_ACCOUNTS}</td>
                      <td class="content">{VL_MAIL_ACCOUNTS_USED}</td>
                      <td class="content">{VL_MAIL_ACCOUNTS_LIIT}</td>
                    </tr>
                    <tr>
                      <td width="25">&nbsp;</td>
                      <td class="content2" width="193">{TR_FTP_ACCOUNTS}</td>
                      <td class="content">{VL_FTP_ACCOUNTS_USED}</td>
                      <td class="content">{VL_FTP_ACCOUNTS_LIIT}</td>
                    </tr>
                    <tr>
                      <td width="25">&nbsp;</td>
                      <td class="content2" width="193">{TR_SQL_DB_ACCOUNTS}</td>
                      <td class="content">{VL_SQL_DB_ACCOUNTS_USED}</td>
                      <td class="content">{VL_SQL_DB_ACCOUNTS_LIIT}</td>
                    </tr>
                    <tr>
                      <td width="25">&nbsp;</td>
                      <td class="content2" width="193">{TR_SQL_USER_ACCOUNTS}</td>
                      <td class="content">{VL_SQL_USER_ACCOUNTS_USED}</td>
                      <td class="content">{VL_SQL_USER_ACCOUNTS_LIIT}</td>
                    </tr>
                    <tr>
                      <td width="25">&nbsp;</td>
                      <td class="content2" width="193">{TR_SUBDOM_ACCOUNTS}</td>
                      <td class="content">{VL_SUBDOM_ACCOUNTS_USED}</td>
                      <td class="content">{VL_SUBDOM_ACCOUNTS_LIIT}</td>
                    </tr>
                    <tr>
                      <td width="25">&nbsp;</td>
                      <td class="content2" width="193">{TR_DOMALIAS_ACCOUNTS}</td>
                      <td class="content">{VL_DOMALIAS_ACCOUNTS_USED}</td>
                      <td class="content">{VL_DOMALIAS_ACCOUNTS_LIIT}</td>
                    </tr>
                    <tr>
                      <td>&nbsp;</td>
                      <td colspan="3"><form name="buttons" method="post" action="?">
                          <input name="Submit" type="submit" class="button" onclick="MM_goToURL('parent','users.php');return document.MM_returnValue" value="  {TR_BACK}  " />
                        &nbsp;&nbsp;&nbsp;
                        <!-- BDP: edit_option -->
                        <input name="Submit2" type="submit" class="button" onclick="MM_goToURL('parent','domain_edit.php?edit_id={DOMAIN_ID}');return document.MM_returnValue" value="   {TR_EDIT}   " />
                        <!-- EDP: edit_option -->
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