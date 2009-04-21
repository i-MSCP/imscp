<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_CLIENT_QUESTION_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
<script type="text/javascript">
<!--
function action_delete(url, subject) {
	return confirm(sprintf("{TR_MESSAGE_DELETE}", subject));
}
//-->
</script>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/ftp_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/email_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<!-- BDP: logged_from --><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td height="20" nowrap="nowrap" class="backButton">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.png" width="16" height="16" border="0" style="vertical-align:middle" alt="" /></a> {YOU_ARE_LOGGED_AS}</td>
      </tr>
    </table>
	<!-- EDP: logged_from -->
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
            <td colspan="3"><table width="100%" border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td align="left"><table width="100%" cellpadding="5" cellspacing="5">
                    <tr>
                      <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_support.png" width="25" height="25" alt="" /></td>
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
                            <td colspan="5" class="title"><span class="message">{MESSAGE}</span></td>
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
                            <td width="25" nowrap="nowrap" class="{CONTENT}"><b>{NEW}</b></td>
                            <td class="{CONTENT}" nowrap="nowrap"><img src="{THEME_COLOR_PATH}/images/icons/document.png" width="16" height="16" style="vertical-align:middle" alt="" />
								<script type="text/javascript">
<!--
								document.write('<a href="ticket_view.php?ticket_id={ID}&screenwidth='+screen.width+'" class="link">{SUBJECT}</a>');
//-->
								</script>
								<noscript><a href="ticket_view.php?ticket_id={ID}&amp;screenwidth='800'" class="link"> {SUBJECT}</a></noscript>
							</td>
                            <td class="{CONTENT}" nowrap="nowrap" align="center">{URGENCY}</td>
                            <td class="{CONTENT}" nowrap="nowrap" align="center">{LAST_DATE}</td>
                            <td class="{CONTENT}" nowrap="nowrap" align="center"><img src="{THEME_COLOR_PATH}/images/icons/delete.png" width="16" height="16" border="0" style="vertical-align:middle" alt="" /> <a href="ticket_delete.php?ticket_id={ID}" onclick="action_delete('ticket_delete.php?ticket_id={ID}', '{SUBJECT2}')" class="link">{TR_DELETE}</a></td>
                          </tr>
                          <!-- EDP: tickets_item -->
                          <tr>
                            <td colspan="2" nowrap="nowrap"><input name="Submit" type="submit" class="button" onclick="MM_goToURL('parent','ticket_delete.php?delete=open');return document.MM_returnValue" value="{TR_DELETE_ALL}" /></td>
                            <td colspan="3" nowrap="nowrap"><div align="right">
                                <!-- BDP: scroll_prev_gray -->
                                <img src="{THEME_COLOR_PATH}/images/icons/flip/prev_gray.gif" width="20" height="20" border="0" alt="" />
                                <!-- EDP: scroll_prev_gray -->
                                <!-- BDP: scroll_prev -->
                                <a href="ticket_system.php?psi={PREV_PSI}"><img src="{THEME_COLOR_PATH}/images/icons/flip/prev.gif" width="20" height="20" border="0" alt="" /></a>
                                <!-- EDP: scroll_prev -->
                                <!-- BDP: scroll_next_gray -->
                              &nbsp;<img src="{THEME_COLOR_PATH}/images/icons/flip/next_gray.gif" width="20" height="20" border="0" alt="" />
                              <!-- EDP: scroll_next_gray -->
                              <!-- BDP: scroll_next -->
                              &nbsp;<a href="ticket_system.php?psi={NEXT_PSI}"><img src="{THEME_COLOR_PATH}/images/icons/flip/next.gif" width="20" height="20" border="0" alt="" /></a>
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
        </table></td>
	</tr>
</table>
</body>
</html>
