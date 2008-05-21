<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_CLIENT_MANAGE_USERS_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
<script language="JavaScript" type="text/JavaScript">
<!--
function action_delete(url) {
	if (!confirm("{TR_MESSAGE_DELETE}"))
		return false;

	location = url;
}
//-->
</script>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/ftp_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/email_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<!-- BDP: logged_from --><table width="100%"  border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td height="20" nowrap background="{THEME_COLOR_PATH}/images/button.gif">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.png" width="18" height="18" border="0" align="absmiddle"></a> <font color="red">{YOU_ARE_LOGGED_AS}</font> </td>
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
	    <td colspan=2 style="vertical-align: top;"><table style="width: 100%; border-collapse: collapse;padding:0;margin:0;">
          <tr height="95">
            <td style="padding-left:30px; width: 100%; background-image: url({THEME_COLOR_PATH}/images/top/middle_bg.jpg);">{MAIN_MENU}</td>
            <td style="padding:0;margin:0;text-align: right; width: 73px;vertical-align: top;"><img src="{THEME_COLOR_PATH}/images/top/middle_right.jpg" border="0"></td>
          </tr>
          <tr height="*">
            <td colspan=3><table width="100%" border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td align="left"><table width="100%" cellpadding="5" cellspacing="5">
                    <tr>
                      <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_ftp.png" width="25" height="25"></td>
                      <td colspan="2" class="title">{TR_FTP_USERS}</td>
                    </tr>
                </table></td>
                <td width="27" align="right">&nbsp;</td>
              </tr>
              <tr>
                <td><table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td width="40">&nbsp;</td>
                      <td valign="top"><table width="100%" cellspacing="7">
                          <!-- BDP: page_message -->
                          <tr>
                            <td colspan="3" nowrap class="title"><font color="#FF0000">{MESSAGE}</font></td>
                          </tr>
                          <!-- EDP: page_message -->
                          <tr>
                            <td nowrap class="content3"><b>{TR_FTP_ACCOUNT}</b></td>
                            <td nowrap class="content3" align="center" colspan="2"><b>{TR_FTP_ACTION}</b></td>
                          </tr>
                          <!-- BDP: ftp_message -->
                          <tr>
                            <td colspan="3" class="title"><font color="#FF0000">{FTP_MSG}</font></td>
                          </tr>
                          <!-- EDP: ftp_message -->
                          <!-- BDP: ftp_item -->
                          <tr>
                            <td nowrap class="{ITEM_CLASS}"><span class="content"><img src="{THEME_COLOR_PATH}/images/icons/ftp_account.png" width="16" height="16" align="left"></span>{FTP_ACCOUNT}</td>
                            <td nowrap class="{ITEM_CLASS}" align="center" width="100"><img src="{THEME_COLOR_PATH}/images/icons/edit.png" width="16" height="16" align="absmiddle"> <a href="edit_ftp_acc.php?id={UID}" class="link">{TR_EDIT}</a></td>
                            <td nowrap class="{ITEM_CLASS}" align="center" width="100"><img src="{THEME_COLOR_PATH}/images/icons/delete.png" width="16" height="16" border="0" align="absmiddle"> <a href="#" class="link" onClick="action_delete('delete_ftp_acc.php?id={UID}')">{TR_DELETE}</a></td>
                          </tr>
                          <!-- EDP: ftp_item -->
                          <!-- BDP: ftps_total -->
                          <tr>
                            <td colspan="3" align="right" nowrap class="content3">{TR_TOTAL_FTP_ACCOUNTS}&nbsp;<b>{TOTAL_FTP_ACCOUNTS}</b></td>
                          </tr>
                          <!-- EDP: ftps_total -->
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
