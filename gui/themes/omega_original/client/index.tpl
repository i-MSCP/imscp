<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_CLIENT_MAIN_INDEX_PAGE_TITLE}</title>
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
            <td colspan="3">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td align="left">
<table width="100%" cellpadding="5" cellspacing="5">
	<tr>
		<td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_general.png" width="25" height="25" alt="" /></td>
		<td colspan="2" class="title">{TR_GENERAL_INFORMATION}</td>
	</tr>
</table>
	</td>
    <td width="27" align="right">&nbsp;</td>
  </tr>
  <tr>
    <td><!-- BDP: props_list -->
        <table width="100%" cellspacing="7" class="hl">
          <!-- BDP: page_message -->
           <tr>
             <td>&nbsp;</td>
             <td colspan="2" class="title"><span class="message">{MESSAGE}</span></td>
           </tr>
          <!-- EDP: page_message -->
          <tr>
            <td width="25">&nbsp;</td>
            <td class="content">{TR_ACCOUNT_NAME} / {TR_MAIN_DOMAIN}</td>
            <td width="230" class="content2">{ACCOUNT_NAME}</td>
          </tr>
          <tr>
            <td width="25">&nbsp;</td>
            <td class="content">{TR_DOMAIN_EXPIRE}</td>
            <td width="230" class="content2">{DMN_EXPIRES} ( <strong style="text-decoration:underline;">{DMN_EXPIRES_DATE}</strong> )</td>
          </tr>
          <!-- BDP: t_php_support -->
          <tr>
            <td width="25">&nbsp;</td>
            <td class="content">{TR_PHP_SUPPORT}</td>
            <td width="230" class="content2">{PHP_SUPPORT}</td>
          </tr>
          <!-- EDP: t_php_support -->
          <!-- BDP: t_cgi_support -->
          <tr>
            <td width="25">&nbsp;</td>
            <td class="content">{TR_CGI_SUPPORT}</td>
            <td width="230" class="content2">{CGI_SUPPORT}</td>
          </tr>
          <!-- EDP: t_cgi_support -->
          <!-- BDP: t_dns_support -->
          <tr>
            <td width="25">&nbsp;</td>
            <td class="content">{TR_DNS_SUPPORT}</td>
            <td width="230" class="content2">{DNS_SUPPORT}</td>
          </tr>
          <!-- EDP: t_dns_support -->
          <!-- BDP: t_backup_support -->
          <tr>
            <td width="25">&nbsp;</td>
            <td class="content">{TR_BACKUP_SUPPORT}</td>
            <td width="230" class="content2">{BACKUP_SUPPORT}</td>
          </tr>
          <!-- EDP: t_backup_support -->
          <!-- BDP: t_sql1_support -->
          <tr>
            <td width="25">&nbsp;</td>
            <td class="content">{TR_MYSQL_SUPPORT}</td>
            <td width="230" class="content2">{MYSQL_SUPPORT}</td>
          </tr>
          <!-- EDP: t_sql1_support -->
          <!-- BDP: t_sdm_support -->
          <tr>
            <td width="25">&nbsp;</td>
            <td class="content">{TR_SUBDOMAINS}</td>
            <td width="230" class="content2">{SUBDOMAINS}</td>
          </tr>
          <!-- EDP: t_sdm_support -->
          <!-- BDP: t_alias_support -->
          <tr>
            <td width="25">&nbsp;</td>
            <td class="content">{TR_DOMAIN_ALIASES}</td>
            <td width="230" class="content2">{DOMAIN_ALIASES}</td>
          </tr>
          <!-- EDP: t_alias_support -->
          <!-- BDP: t_mails_support -->
          <tr>
            <td width="25">&nbsp;</td>
            <td class="content">{TR_MAIL_ACCOUNTS}</td>
            <td width="230" class="content2">{MAIL_ACCOUNTS}</td>
          </tr>
          <!-- EDP: t_mails_support -->
          <tr>
            <td width="25">&nbsp;</td>
            <td class="content">{TR_FTP_ACCOUNTS}</td>
            <td width="230" class="content2">{FTP_ACCOUNTS}</td>
          </tr>
          <!-- BDP: t_sql2_support -->
          <tr>
            <td width="25">&nbsp;</td>
            <td class="content">{TR_SQL_DATABASES}</td>
            <td width="230" class="content2">{SQL_DATABASES}</td>
          </tr>
          <tr>
            <td width="25">&nbsp;</td>
            <td class="content">{TR_SQL_USERS}</td>
            <td width="230" class="content2">{SQL_USERS}</td>
          </tr>
          <!-- EDP: t_sql2_support -->
          <!-- BDP: msg_entry -->
          <td>&nbsp;</td>
            <td colspan="2" class="title"><span class="message">{TR_NEW_MSGS}</span></td>
          </tr>
          <!-- EDP: msg_entry -->
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
    <td align="left">
<table width="100%" cellpadding="5" cellspacing="5">
	<tr>
		<td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_traffic.png" width="25" height="25" alt="" /></td>
		<td colspan="2" class="title">{TR_TRAFFIC_USAGE}</td>
	</tr>
</table>
	</td>
    <td width="27" align="right">&nbsp;</td>
  </tr>
  <tr>
    <td><table width="100%" cellspacing="7">
      <!-- BDP: traff_warn -->
      <tr>
        <td>&nbsp;</td>
        <td class="title"><span class="message">{TR_TRAFFIC_WARNING}</span></td>
      </tr>
      <!-- EDP: traff_warn -->
      <tr>
        <td width="25">&nbsp;</td>
        <td class="content">{TRAFFIC_USAGE_DATA}</td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td class="content"><table border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td width="3"><img src="{THEME_COLOR_PATH}/images/bars/stats_left.png" width="3" height="20" /></td>
            <td width="405" class="statsBar"><table border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td class="statsBar"><img src="{THEME_COLOR_PATH}/images/bars/stats_progress.png" width="{TRAFFIC_BARS}" height="20" /></td>
              </tr>
            </table></td>
            <td width="3"><img src="{THEME_COLOR_PATH}/images/bars/stats_right.png" width="3" height="20" /></td>
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
    <td align="left">
<table width="100%" cellpadding="5" cellspacing="5">
	<tr>
		<td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_diskusage.png" width="25" height="25" alt="" /></td>
		<td colspan="2" class="title">{TR_DISK_USAGE}</td>
	</tr>
</table>
	</td>
    <td width="27" align="right">&nbsp;</td>
  </tr>
  <tr>
    <td><table width="100%" cellspacing="7">
      <!-- BDP: disk_warn -->
      <tr>
        <td>&nbsp;</td>
        <td class="title"><span class="message">{TR_DISK_WARNING}</span></td>
      </tr>
      <!-- EDP: disk_warn -->
      <tr>
        <td width="25">&nbsp;</td>
        <td class="content">{DISK_USAGE_DATA}</td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td class="content"><table border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td width="3"><img src="{THEME_COLOR_PATH}/images/bars/stats_left.png" width="3" height="20" /></td>
            <td width="405" class="statsBar"><table border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td class="statsBar"><img src="{THEME_COLOR_PATH}/images/bars/stats_progress.png" width="{DISK_BARS}" height="20" /></td>
              </tr>
            </table></td>
            <td width="3"><img src="{THEME_COLOR_PATH}/images/bars/stats_right.png" width="3" height="20" /></td>
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
</table></td>
          </tr>
        </table></td>
	</tr>
</table>
</body>
</html>