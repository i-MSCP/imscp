<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_CLIENT_CRONJOBS_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
<script type="text/javascript">
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
<!-- BDP: logged_from --><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td height="20" nowrap="nowrap" class="backButton">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.png" width="18" height="18" border="0" align="absmiddle"></a> {YOU_ARE_LOGGED_AS}</td>
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
                      <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_tools.png" width="25" height="25"></td>
                      <td colspan="2" class="title">{TR_CRON_MANAGER}</td>
                    </tr>
                </table></td>
                <td width="27" align="right">&nbsp;</td>
              </tr>
              <tr>
                <td><table width="100%" cellpadding="5" cellspacing="5">
                    <!-- BDP: page_message -->
                    <tr>
                      <td width="25">&nbsp;</td>
                      <td colspan="4" class="title"><font color="#FF0000">{MESSAGE}</font></td>
                    </tr>
                    <!-- EDP: page_message -->
                    <tr>
                      <td width="25">&nbsp;</td>
                      <td class="content3"><strong>{TR_CRONJOBS}</strong></td>
                      <td align="center" class="content3"><strong>{TR_ACTIVE}</strong></td>
                      <td colspan="2" align="center" class="content3"><strong>{TR_ACTION}</strong></td>
                    </tr>
                    <tr>
                      <!-- BDP: cronjobs -->
                      <td nowrap="nowrap">&nbsp;</td>
                      <td nowrap="nowrap" class="{ITEM_CLASS}"><strong>{NAME}</strong><br>
                        {DESCRIPTION}</td>
                      <td width="100" align="center" nowrap="nowrap" class="{ITEM_CLASS}">{ACTIVE}</td>
                      <td width="100" nowrap="nowrap" class="{ITEM_CLASS}"><img src="{THEME_COLOR_PATH}/images/icons/edit.png" width="16" height="16" align="absmiddle"> <a href="cronjobs_edit.php?cron_id={ID}" class="link">{TR_EDIT}</a></td>
                      <td width="100" nowrap="nowrap" class="{ITEM_CLASS}"><img src="{THEME_COLOR_PATH}/images/icons/delete.png" width="16" height="16" border="0" align="absmiddle"> <a href="#" class="link" onclick="action_delete('cronjobs_delete.php?cron_id={ID}')">{TR_DELETE}</td>
                      <!-- EDP: cronjobs -->
                    </tr>
                    <tr>
                      <td nowrap="nowrap">&nbsp;</td>
                      <td colspan="4"><input name="button" type="button" class="button" onclick="MM_goToURL('parent','cronjobs_add.php');return document.MM_returnValue" value="{TR_ADD}"></td>
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
