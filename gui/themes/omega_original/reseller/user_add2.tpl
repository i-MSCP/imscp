<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_CLIENT_CHANGE_PERSONAL_DATA_PAGE_TITLE}</title>
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
                      <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_user.png" width="25" height="25" alt="" /></td>
                      <td colspan="2" class="title">{TR_ADD_USER}</td>
                    </tr>
                </table></td>
                <td width="27" align="right">&nbsp;</td>
              </tr>
              <tr>
                <td valign="top"><!-- BDP: add_user -->
                    <form name="reseller_add_users_first_frm" method="post" action="user_add2.php">
                      <input type="hidden" name="uaction" value="user_add2_nxt" />
                      <table width="100%" cellpadding="5" cellspacing="5">
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td colspan="2" class="content3"><b>{TR_HOSTING_PLAN_PROPERTIES}</b></td>
                        </tr>
                        <!-- BDP: page_message -->
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td colspan="2" class="title"><span class="message">{MESSAGE}</span></td>
                        </tr>
                        <!-- EDP: page_message -->
						<tr>
                          <td width="25">&nbsp;</td>
                          <td class="content2" width="200">{TR_TEMPLATE_NAME}</td>
                          <td class="content"><input name="template" type="hidden" id="template" value="{VL_TEMPLATE_NAME}" />{VL_TEMPLATE_NAME} </td>
                        </tr>
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td class="content2" width="200">{TR_MAX_SUBDOMAIN}</td>
                          <td class="content"><input type="text" name="nreseller_max_subdomain_cnt" value="{MAX_SUBDMN_CNT}" style="width:140px" class="textinput" /></td>
                        </tr>
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td class="content2" width="175">{TR_MAX_DOMAIN_ALIAS}</td>
                          <td class="content"><input type="text" name="nreseller_max_alias_cnt" value="{MAX_DMN_ALIAS_CNT}" style="width:140px" class="textinput" /></td>
                        </tr>
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td class="content2" width="200">{TR_MAX_MAIL_COUNT}</td>
                          <td class="content"><input type="text" name="nreseller_max_mail_cnt" value="{MAX_MAIL_CNT}" style="width:140px" class="textinput" /></td>
                        </tr>
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td class="content2" width="200">{TR_MAX_FTP}</td>
                          <td class="content"><input type="text" name="nreseller_max_ftp_cnt" value="{MAX_FTP_CNT}" style="width:140px" class="textinput" /></td>
                        </tr>
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td class="content2" width="200">{TR_MAX_SQL_DB}</td>
                          <td class="content"><input type="text" name="nreseller_max_sql_db_cnt" value="{MAX_SQL_CNT}" style="width:140px" class="textinput" /></td>
                        </tr>
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td class="content2" width="200">{TR_MAX_SQL_USERS}</td>
                          <td class="content"><input type="text" name="nreseller_max_sql_user_cnt" value="{VL_MAX_SQL_USERS}" style="width:140px" class="textinput" /></td>
                        </tr>
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td class="content2" width="200">{TR_MAX_TRAFFIC}</td>
                          <td class="content"><input type="text" name="nreseller_max_traffic" value="{VL_MAX_TRAFFIC}" style="width:140px" class="textinput" /></td>
                        </tr>
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td class="content2" width="200">{TR_MAX_DISK_USAGE}</td>
                          <td class="content"><input type="text" name="nreseller_max_disk" value="{VL_MAX_DISK_USAGE}" style="width:140px" class="textinput" /></td>
                        </tr>
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td class="content2" width="200">{TR_PHP}</td>
                          <td class="content"><input name="php" type="radio" value="yes" {VL_PHPY} />
                            {TR_YES}
                            <input type="radio" name="php" value="no" {VL_PHPN} />
                            {TR_NO}</td>
                        </tr>
                        <tr>
                          <td width="25">&nbsp;</td>
                          <td class="content2" width="200">{TR_CGI}</td>
                          <td class="content"><input name="cgi" type="radio" value="yes" {VL_CGIY} />
                            {TR_YES}
                            <input type="radio" name="cgi" value="no" {VL_CGIN} />
                            {TR_NO}</td>
                        </tr>
			<tr>
                          <td width="25">&nbsp;</td>
                          <td class="content2" width="200">{TR_BACKUP}</td>
                          <td class="content"><input name="allowbackup" type="radio" value="domain" {VL_BACKUPD} />
                            {TR_BACKUP_DOMAIN}
                            <input type="radio" name="allowbackup" value="sql" {VL_BACKUPS} />
                            {TR_BACKUP_SQL}<input name="allowbackup" type="radio" value="full" {VL_BACKUPF} />
                            {TR_BACKUP_FULL}
                            <input type="radio" name="allowbackup" value="no" {VL_BACKUPN} />
                            {TR_BACKUP_NO}</td>
                        </tr>
                        <tr>
                          <td>&nbsp;</td>
                          <td colspan="2"><input name="Submit" type="submit" class="button" value="{TR_NEXT_STEP}" /></td>
                        </tr>
                      </table>
                    </form>
                  <!-- EDP: add_user -->
                </td>
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
