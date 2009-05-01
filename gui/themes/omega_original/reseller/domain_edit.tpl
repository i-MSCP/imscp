<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_EDIT_DOMAIN_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
 <!-- BDP: logged_from -->
 <table width="100%" border="0" cellspacing="0" cellpadding="0">
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
                      <td colspan="2" class="title">{TR_EDIT_DOMAIN}</td>
                    </tr>
                </table></td>
                <td width="27" align="right">&nbsp;</td>
              </tr>
              <tr>
                <td><form name="reseller_edit_domain_frm" method="post" action="domain_edit.php">
                    <table width="100%" cellpadding="5" cellspacing="5" class="hl">
                      <tr>
                        <td width="25" align="left">&nbsp;</td>
                        <td colspan="2" align="left" class="content3"><b>{TR_DOMAIN_PROPERTIES}</b></td>
                      </tr>
                      <!-- BDP: page_message -->
                      <tr>
                        <td>&nbsp;</td>
                        <td colspan="2" class="title"><span class="message">{MESSAGE}</span></td>
                      </tr>
                      <!-- EDP: page_message -->
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2" width="193">{TR_DOMAIN_NAME}</td>
                        <td class="content">{VL_DOMAIN_NAME}</td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2" width="193">{TR_DOMAIN_IP}</td>
                        <td class="content">{VL_DOMAIN_IP}
                          <!--
				<select name="domain_ip">

                      <option value="{IP_VALUE}" {IP_SELECTED}>{IP_NUM}&nbsp;({IP_NAME})</option>

                    </select>
				-->
                        </td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2" width="193">{TR_PHP_SUPP}</td>
                        <td class="content"><select name="domain_php" id="domain_php">
                            <option value="yes" {PHP_YES}>{TR_YES}</option>
                            <option value="no" {PHP_NO}>{TR_NO}</option>
                          </select>
                        </td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2" width="193">{TR_CGI_SUPP}</td>
                        <td class="content"><select name="domain_cgi" id="domain_cgi">
                            <option value="yes" {CGI_YES}>{TR_YES}</option>
                            <option value="no" {CGI_NO}>{TR_NO}</option>
                          </select>
                        </td>
                      </tr>
		      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2" width="193">{TR_BACKUP}</td>
                        <td class="content"><select name="backup">
                            <option value="domain" {BACKUP_DOMAIN}>{TR_BACKUP_DOMAIN}</option>
                            <option value="sql" {BACKUP_SQL}>{TR_BACKUP_SQL}</option>
                            <option value="full" {BACKUP_FULL}>{TR_BACKUP_FULL}</option>
                            <option value="no" {BACKUP_NO}>{TR_BACKUP_NO}</option>
                          </select>
                        </td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2" width="193">{TR_SUBDOMAINS}</td>
                        <td class="content"><input type="text" name="dom_sub" value="{VL_DOM_SUB}" style="width:100px" class="textinput" />
                        </td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2" width="193">{TR_ALIAS}</td>
                        <td class="content"><input type="text" name="dom_alias" value="{VL_DOM_ALIAS}" style="width:100px" class="textinput" />
                        </td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2" width="193">{TR_MAIL_ACCOUNT}</td>
                        <td class="content"><input type="text" name="dom_mail_acCount" value="{VL_DOM_MAIL_ACCOUNT}" style="width:100px" class="textinput" />
                        </td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2" width="193">{TR_FTP_ACCOUNTS}</td>
                        <td class="content"><input type="text" name="dom_ftp_acCounts" value="{VL_FTP_ACCOUNTS}" style="width:100px" class="textinput" />
                        </td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2" width="193">{TR_SQL_DB}</td>
                        <td class="content"><input type="text" name="dom_sqldb" value="{VL_SQL_DB}" style="width:100px" class="textinput" />
                        </td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2" width="193">{TR_SQL_USERS}</td>
                        <td class="content"><input type="text" name="dom_sql_users" value="{VL_SQL_USERS}" style="width:100px" class="textinput" />
                        </td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2" width="193">{TR_TRAFFIC}</td>
                        <td class="content"><input type="text" name="dom_traffic" value="{VL_TRAFFIC}" style="width:100px" class="textinput" />
                        </td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2" width="193">{TR_DISK}</td>
                        <td class="content"><input type="text" name="dom_disk" value="{VL_DOM_DISK}" style="width:100px" class="textinput" />
                        </td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2" width="193">{TR_USER_NAME}</td>
                        <td class="content">{VL_USER_NAME}</td>
                      </tr>
                      <tr>
                        <td>&nbsp;</td>
                        <td colspan="2"><input name="Submit" type="submit" class="button" value="{TR_UPDATE_DATA}" />
                          &nbsp;&nbsp;&nbsp;
                          <input name="Submit" type="submit" class="button" onclick="MM_goToURL('parent','users.php');return document.MM_returnValue" value=" {TR_CANCEL} " /></td>
                      </tr>
                      <tr>
                        <td colspan="3"><input type="hidden" name="uaction" value="sub_data" />
                        </td>
                      </tr>
                    </table></form></td>
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
