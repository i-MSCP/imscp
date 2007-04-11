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
<style type="text/css">
<!--
.style1 {font-size: 9px}
-->
</style>
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
		<td style="height: 56px; width: 785px;"><img src="{THEME_COLOR_PATH}/images/top/top_left_bg.jpg" border="0"></td>
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
            <td colspan=3>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td align="left">
<table width="100%" cellpadding="5" cellspacing="5">
	<tr>
		<td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_email.png" width="25" height="25"></td>
		<td colspan="2" class="title">{TR_MAIL_USERS}</td>
	</tr>
</table>			
			</td>
            <td width="27" align="right">&nbsp;</td>
          </tr>
          <tr>
            <td><table width="100%"  border="00" cellspacing="0" cellpadding="0">
                <tr>
                  <td width="25">&nbsp;</td>
                  <td valign="top"><table width="100%" cellspacing="7">
                    <!-- BDP: page_message -->
					<tr>
                      <td colspan="4" nowrap class="title"><font color="#FF0000">{MESSAGE}</font></td>
                      </tr>
                    <tr>
					<!-- EDP: page_message -->
                      <td nowrap class="content3"><b>{TR_MAIL}</b></td>
                      <td nowrap class="content3" width="150"><b>{TR_TYPE}</b></td>
                      <td nowrap class="content3" align="center" width="180"><b>{TR_STATUS}</b></td>
                      <td nowrap class="content3" align="center" width="100"><b>{TR_ACTION}</b></td>
                    </tr>
                    <!-- BDP: mail_message -->
                    <tr>
                      <td colspan="4" class="title"><font color="#FF0000">{MAIL_MSG}</font></td>
                    </tr>
                    <!-- EDP: mail_message -->
                    <!-- BDP: mail_item -->
                    <tr>
                      <td nowrap class="{ITEM_CLASS}"><img src="{THEME_COLOR_PATH}/images/icons/mail_icon.gif" width="16" height="14" align="absmiddle"> <a href="{MAIL_EDIT_SCRIPT}" class="link">{MAIL_ACC}</a>
                          <!-- BDP: auto_respond -->
						  <br><span class="style1">
						  {TR_AUTORESPOND}: [&nbsp;&nbsp;
                          <a href="{AUTO_RESPOND_DISABLE_SCRIPT}" class="link">{AUTO_RESPOND_DISABLE}</a>&nbsp;&nbsp;
						  <a href="{AUTO_RESPOND_EDIT_SCRIPT}" class="link">{AUTO_RESPOND_EDIT}</a>
						  ]
						  <!-- EDP: auto_respond -->
						  </span>
                      </td>
                      <td nowrap class="{ITEM_CLASS}" width="150">{MAIL_TYPE}</td>
                      <td nowrap class="{ITEM_CLASS}" align="center" width="180">{MAIL_STATUS}</td>
                      <td nowrap class="{ITEM_CLASS}" align="center" width="100"><img src="{THEME_COLOR_PATH}/images/icons/delete.gif" width="16" height="16" border="0" align="absmiddle"> <a href="#" class="link" onClick="action_delete('{MAIL_ACTION_SCRIPT}')">{MAIL_ACTION}</a></td>
					</td>
                    </tr>
                    <!-- EDP: mail_item -->
                    <!-- BDP: mails_total -->
                    <tr>
                      <td colspan="4" align="right" nowrap class="content3">{TR_TOTAL_MAIL_ACCOUNTS}:&nbsp;<b>{TOTAL_MAIL_ACCOUNTS}</b></td>
                    </tr>
                    <!-- EDP: mails_total -->
                  </table>
                    </td>
                </tr>
            </table></td>
            <td>&nbsp;</td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
          </tr>
        </table>			
			</td>
          </tr>
        </table>
	  </td>
	</tr>
</table>
</body>
</html>
