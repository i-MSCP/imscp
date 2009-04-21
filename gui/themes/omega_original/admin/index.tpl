<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_ADMIN_MAIN_INDEX_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="height:100%;padding:0;margin:0;">
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
        <table width="100%" cellpadding="5" cellspacing="5">
          <!-- BDP: page_message -->
           <tr>
             <td>&nbsp;</td>
             <td colspan="2" class="title"><span class="message">{MESSAGE}</span></td>
           </tr>
          <!-- EDP: page_message -->
          <tr>
            <td width="25">&nbsp;</td>
            <td class="content">{TR_ACCOUNT_NAME}</td>
            <td width="230" class="content2">{ACCOUNT_NAME}</td>
          </tr>
          <tr>
            <td width="25">&nbsp;</td>
            <td class="content">{TR_ADMIN_USERS}</td>
            <td width="230" class="content2">{ADMIN_USERS}</td>
          </tr>
          <tr>
            <td width="25">&nbsp;</td>
            <td class="content">{TR_RESELLER_USERS}</td>
            <td width="230" class="content2">{RESELLER_USERS}</td>
          </tr>
          <tr>
            <td width="25">&nbsp;</td>
            <td class="content">{TR_NORMAL_USERS}</td>
            <td width="230" class="content2">{NORMAL_USERS}</td>
          </tr>
          <tr>
            <td width="25">&nbsp;</td>
            <td class="content">{TR_DOMAINS}</td>
            <td width="230" class="content2">{DOMAINS}</td>
          </tr>
          <tr>
            <td width="25">&nbsp;</td>
            <td class="content">{TR_SUBDOMAINS}</td>
            <td width="230" class="content2">{SUBDOMAINS}</td>
          </tr>
          <tr>
            <td width="25">&nbsp;</td>
            <td class="content">{TR_DOMAINS_ALIASES}</td>
            <td width="230" class="content2">{DOMAINS_ALIASES}</td>
          </tr>
          <tr>
            <td width="25">&nbsp;</td>
            <td class="content">{TR_MAIL_ACCOUNTS}</td>
            <td width="230" class="content2">{MAIL_ACCOUNTS}</td>
          </tr>
          <tr>
            <td width="25">&nbsp;</td>
            <td class="content">{TR_FTP_ACCOUNTS}</td>
            <td width="230" class="content2">{FTP_ACCOUNTS}</td>
          </tr>
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
          <!-- BDP: msg_entry -->
          <td>&nbsp;</td>
            <td colspan="2" class="title"><span class="message">{TR_NEW_MSGS}</span></td>
          </tr>
          <!-- EDP: msg_entry -->
          <!-- BDP: update_message -->
          <tr>
            <td width="25">&nbsp;</td>
            <td colspan="2" style="color:#ff0000"><b>{UPDATE}</b></td>
          </tr>
          <!-- EDP: update_message -->
          <!-- BDP: database_update_message -->
          <tr>
            <td width="25">&nbsp;</td>
            <td colspan="2" style="color:#ff0000"><b>{DATABASE_UPDATE}</b></td>
          </tr>
          <!-- EDP: database_update_message -->
          <!-- BDP: critical_update_message -->
          <tr>
            <td width="25">&nbsp;</td>
            <td colspan="2" style="color:#ff0000"><b>{CRITICAL_MESSAGE}</b></td>
          </tr>
          <!-- EDP: critical_update_message -->
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
		<td colspan="2" class="title">{TR_SERVER_TRAFFIC}</td>
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
        <td class="content">{TRAFFIC_WARNING}</td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td class="content"><table border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td width="33"><img src="{THEME_COLOR_PATH}/images/stats_left.gif" width="33" height="20" alt="" /></td>
            <td width="405" class="stats"><table border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td width="7"><img src="{THEME_COLOR_PATH}/images/bars/stats_left.gif" width="7" height="13" alt="" /></td>
                <td class="statsBar"><img src="{THEME_COLOR_PATH}/images/trans.gif" width="{BAR_VALUE}" height="1" alt="" /></td>
                <td width="7"><img src="{THEME_COLOR_PATH}/images/bars/stats_right.gif" width="7" height="13" alt="" /></td>
              </tr>
            </table></td>
            <td width="33"><img src="{THEME_COLOR_PATH}/images/stats_right.gif" width="33" height="20" alt="" /></td>
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
