<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_CLIENT_MANAGE_SQL_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
<script>
<!--
function action_delete(url) {
	if (!confirm("{TR_MESSAGE_DELETE}"))
		return false;

	location = url;
}
//-->
</script>
</head>
<body onLoad="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/ftp_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif','{THEME_COLOR_PATH}/images/icons/logout_a.gif','{THEME_COLOR_PATH}/images/icons/email_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<!-- BDP: logged_from --><table width="100%"  border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td height="20" nowrap background="{THEME_COLOR_PATH}/images/button.gif">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.gif" width="18" height="18" border="0" align="absmiddle"></a> <font color="red">{YOU_ARE_LOGGED_AS}</font> </td>
      </tr>
    </table>
	<!-- EDP: logged_from -->
<table width="100%" border="0" cellspacing="0" cellpadding="0" height="100%" style="border-collapse: collapse;padding:0;margin:0;">
	<tr>
		<td align="left" valign="top" style="vertical-align: top; width: 195px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_left.jpg" border="0"></td>
		<td style="height: 56px; width: 617px;"><img src="{THEME_COLOR_PATH}/images/top/top_left_bg.jpg" border="0"></td>
		<td style="width:100%; background-image: url({THEME_COLOR_PATH}/images/top/top_bg.jpg)">&nbsp;</td>
		<td style="width: 73px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_right.jpg" border="0"></td>
	</tr>
	<tr>
		<td style="width: 195px; vertical-align: top;">{MENU}</td>
	    <td colspan=3 style="vertical-align: top;"><table style="width: 100%; border-collapse: collapse;padding:0;margin:0;">
          <tr height="95";>
            <td style="padding-left:30px; width: 100%; background-image: url({THEME_COLOR_PATH}/images/top/middle_bg.jpg);">{MAIN_MENU}</td>
            <td style="padding:0;margin:0;text-align: right; width: 73px;vertical-align: top;"><img src="{THEME_COLOR_PATH}/images/top/middle_right.jpg" border="0"></td>
          </tr>
          <tr height="*">
            <td colspan=3><table width="100%" border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td align="left"><table width="100%" cellpadding="5" cellspacing="5">
                    <tr>
                      <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_sql.png" width="25" height="25"></td>
                      <td colspan="2" class="title">{TR_MANAGE_SQL}</td>
                    </tr>
                </table></td>
                <td width="27" align="right">&nbsp;</td>
              </tr>
              <tr>
                <td><table width="100%"  border="00" cellspacing="0" cellpadding="0">
                    <tr>
                      <td width="40">&nbsp;</td>
                      <td valign="top"><table width="100%" cellpadding="5" cellspacing="5">
                          <!-- BDP: page_message -->
                          <tr>
                            <td colspan="4" class="title"><font color="#FF0000">{MESSAGE}</font></td>
                          </tr>
                          <!-- EDP: page_message -->
                          <!-- BDP: db_list -->
                          <tr>
                            <td class="content3" colspan="3"><b><strong><img src="{THEME_COLOR_PATH}/images/icons/database_small.gif" width="15" height="16" align="left"></strong>{DB_NAME}</b></td>
                            <td class="content3" width="150" align="center"><img src="{THEME_COLOR_PATH}/images/icons/delete.gif" width="16" height="16" border="0" align="absmiddle"> <a href="#" class="linkdark" onClick="action_delete('delete_sql_database.php?id={DB_ID}')"><b>{TR_DELETE}</b></a> </td>
                          </tr>
                          <tr>
                            <td class="content2" colspan="3"><img src="{THEME_COLOR_PATH}/images/icons/users.gif" width="16" height="16" align="absmiddle"> {TR_DATABASE_USERS}</td>
                            <td class="content2" align="center"><a href="sql_add_user.php?id={DB_ID}" class="link">{TR_ADD_USER}</a></td>
                          </tr>
                          <!-- BDP: db_message -->
                          <tr>
                            <td colspan="4" class="content3"><font color="#FF0000">{DB_MSG}</font></td>
                          </tr>
                          <!-- EDP: db_message -->
                          <!-- BDP: user_list -->
                          <tr>
                            <td class="content">{DB_USER}</td>
                            <td width="150" align="center" class="content"><img src="{THEME_COLOR_PATH}/images/icons/execute_query.gif" width="16" height="16" align="absmiddle"> <a href="sql_execute_query.php?id={USER_ID}" class="link" >{TR_EXECUTE_QUERY}</a></td>
                            <td width="150" align="center" class="content"><img src="{THEME_COLOR_PATH}/images/icons/change_pass.gif" width="16" height="15" align="absmiddle"> <a href="sql_change_password.php?id={USER_ID}" class="link" >{TR_CHANGE_PASSWORD}</a></td>
                            <td class="content" align="center"><img src="{THEME_COLOR_PATH}/images/icons/delete.gif" width="16" height="16" border="0" align="absmiddle"> <a href="#" class="link" onClick="action_delete('sql_delete_user.php?id={USER_ID}')">{TR_DELETE}</a></td>
                          </tr>
                          <!-- EDP: user_list -->
                          <tr>
                            <td colspan="4">&nbsp;</td>
                          </tr>
                          <!-- EDP: db_list -->
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
        </table>
	  </td>
	</tr>
</table>
</body>
</html>
