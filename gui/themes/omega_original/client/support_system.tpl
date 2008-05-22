<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_CLIENT_QUESTION_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
<script language="JavaScript" type="text/JavaScript">
<!--
function MM_goToURL() { //v3.0
  var i, args=MM_goToURL.arguments; document.MM_returnValue = false;
  for (i=0; i<(args.length-1); i+=2) eval(args[i]+".location='"+args[i+1]+"'");
}
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
                      <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_support.png" width="25" height="25"></td>
                      <td colspan="2" class="title">{TR_OPEN_TICKETS}</td>
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
                            <td colspan="5" class="title"><font color="#FF0000">{MESSAGE}</font></td>
                          </tr>
                          <!-- EDP: page_message -->
                          <!-- BDP: tickets_list -->
                          <tr>
                          	<td width="30" class="content3" align="center"><b>{TR_STATUS}</b></td>
                            <td class="content3"><b>{TR_SUBJECT}</b></td>
                            <td width="150" class="content3" align="center"><b>{TR_URGENCY}</b></td>
                            <td width="100" class="content3" align="center"><b>{TR_LAST_DATA}</b></td>
                            <td width="100" align="center" class="content3"><strong>{TR_ACTION}</strong></td>
                          </tr>
                          <!-- BDP: tickets_item -->
                          <tr>
                            <td width="25" nowrap class="{CONTENT}"><b>{NEW}</b></td>
                            <td class="{CONTENT}" nowrap><img src="{THEME_COLOR_PATH}/images/icons/document.png" width="12" height="15" align="left">
                                <script language="javascript">
							document.write('<a href="view_ticket.php?ticket_id={ID}&screenwidth='+screen.width+'" class="link">');
						</script>
                              {SUBJECT}</a> </td>
                            <td class="{CONTENT}" nowrap align="center">{URGENCY}</td>
                            <td class="{CONTENT}" nowrap align="center">{LAST_DATE}</td>
                            <td class="{CONTENT}" nowrap align="center"><img src="{THEME_COLOR_PATH}/images/icons/delete.png" width="16" height="16" border="0" align="absmiddle"> <a href="#" onclick="action_delete('delete_ticket.php?ticket_id={ID}')" class="link">{TR_DELETE}</a></td>
                          </tr>
                          <!-- EDP: tickets_item -->
                          <tr>
                            <td colspan="2" nowrap><input name="Submit" type="submit" class="button" onclick="MM_goToURL('parent','delete_ticket.php?delete=open');return document.MM_returnValue" value="{TR_DELETE_ALL}"></td>
                            <td colspan="3" nowrap><div align="right">
                                <!-- BDP: scroll_prev_gray -->
                                <img src="{THEME_COLOR_PATH}/images/icons/flip/prev_gray.gif" width="20" height="20" border="0">
                                <!-- EDP: scroll_prev_gray -->
                                <!-- BDP: scroll_prev -->
                                <a href="support_system.php?psi={PREV_PSI}"><img src="{THEME_COLOR_PATH}/images/icons/flip/prev.gif" width="20" height="20" border="0"></a>
                                <!-- EDP: scroll_prev -->
                                <!-- BDP: scroll_next_gray -->
                              &nbsp;<img src="{THEME_COLOR_PATH}/images/icons/flip/next_gray.gif" width="20" height="20" border="0">
                              <!-- EDP: scroll_next_gray -->
                              <!-- BDP: scroll_next -->
                              &nbsp;<a href="support_system.php?psi={NEXT_PSI}"><img src="{THEME_COLOR_PATH}/images/icons/flip/next.gif" width="20" height="20" border="0"></a>
                              <!-- EDP: scroll_next -->
                            </div></td>
                          </tr>
                          <!-- EDP: tickets_list -->
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
