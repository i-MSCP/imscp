<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_CLIENT_VIEW_TICKET_PAGE_TITLE}</title>
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

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<!-- BDP: logged_from --><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td height="20" nowrap="nowrap" class="backButton">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.png" width="16" height="16" border="0" align="absmiddle"></a> {YOU_ARE_LOGGED_AS}</td>
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
            <td colspan="3">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td align="left">
<table width="100%" cellpadding="5" cellspacing="5">
	<tr>
		<td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_support.png" width="25" height="25"></td>
		<td colspan="2" class="title">{TR_VIEW_SUPPORT_TICKET}</td>
	</tr>
</table>
	</td>
    <td width="27" align="right">&nbsp;</td>
  </tr>
  <tr>
    <td><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="40">&nbsp;</td>
        <td valign="top"><table width="100%" cellpadding="5" cellspacing="5">
          <!-- BDP: page_message -->
          <tr>
            <td class="title"><span class="message">{MESSAGE}</span></td>
          </tr>
          <!-- EDP: page_message -->
          <!-- BDP: tickets_list -->
          <tr>
            <td nowrap="nowrap" class="content3"> {TR_TICKET_URGENCY}: {URGENCY}<br>
              {TR_TICKET_SUBJECT}: {SUBJECT}</td>
          </tr>
          <!-- BDP: tickets_item -->
          <tr>
            <td nowrap="nowrap" class="content2"><span class="content">{TR_TICKET_FROM}: {FROM}</span><br>
              {TR_TICKET_DATE}: {DATE}</td>
          </tr>
          <tr>
            <td nowrap="nowrap" class="content">{TICKET_CONTENT}</td>
          </tr>
          <!-- EDP: tickets_item -->
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
		<td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_doc.png" width="25" height="25"></td>
		<td colspan="2" class="title">{TR_NEW_TICKET_REPLY}</td>
	</tr>
</table>
	</td>
    <td width="27" align="right">&nbsp;</td>
  </tr>
  <tr>
    <td><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="40">&nbsp;</td>
        <td valign="top"><form name="question_frm" method="post" action="ticket_view.php?ticket_id={ID}">
          <table width="100%" cellspacing="5">
            <tr>
              <td colspan="2" class="content"><textarea name="user_message" style="width:80%" class="textinput2" rows="20"></textarea>
                      <input name="subject" type="hidden" value="{SUBJECT}">
                      <input name="urgency" type="hidden" value="{URGENCY_ID}">
              </td>
            </tr>
            <tr>
              <td width="100"><input name="Button" type="button" class="button" value="{TR_REPLY}" onclick="return sbmt(document.forms[0],'send_msg');">
              </td>
              <td width="383"><input name="Button" type="button" class="button" value="{TR_ACTION}" onclick="return sbmt(document.forms[0],'{ACTION}');">
              </td>
            </tr>
            <!-- EDP: tickets_list -->
          </table>
          <!-- end of content -->
          <input name="uaction" type="hidden" value="">
          <input name="screenwidth" type="hidden" value="{SCREENWIDTH}">
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
