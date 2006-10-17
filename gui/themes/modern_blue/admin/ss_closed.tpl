<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_SUPPORT_SYSTEM}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/vhcs.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/vhcs.js"></script>
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

<body onLoad="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif','{THEME_COLOR_PATH}/images/icons/logout_a.gif','{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<table width="100%" border="0" cellspacing="0" cellpadding="0" height="100%">
  <tr>
    <td height="80" align="left" valign="top">
    <table width="100%"  border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td width="17"><img src="{THEME_COLOR_PATH}/images/top/left.jpg" width="17" height="80"></td>
          <td width="198" align="center" background="{THEME_COLOR_PATH}/images/top/logo_background.jpg"><img src="{ISP_LOGO}"></td>
          <td background="{THEME_COLOR_PATH}/images/top/left_fill.jpg"><img src="{THEME_COLOR_PATH}/images/top/left_fill.jpg" width="2" height="80"></td>
          <td width="766"><img src="{THEME_COLOR_PATH}/images/top/middle_background.jpg" width="766" height="80"></td>
          <td background="{THEME_COLOR_PATH}/images/top/right_fill.jpg"><img src="{THEME_COLOR_PATH}/images/top/right_fill.jpg" width="3" height="80"></td>
          <td width="9"><img src="{THEME_COLOR_PATH}/images/top/right.jpg" width="9" height="80"></td>
        </tr>
    </table></td>
  </tr>
  <tr>
    <td valign="top"><table height="100%" width="100%"  border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="215" valign="top" bgcolor="#F5F5F5"><!-- Menu begin -->
  {MENU}
    <!-- Menu end -->
        </td>
        <td valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td height="62" align="left" background="{THEME_COLOR_PATH}/images/content/table_background.jpg" class="title"><img src="{THEME_COLOR_PATH}/images/content/table_icon_support.jpg" width="85" height="62" align="absmiddle">{TR_CLOSED_TICKETS}</td>
            <td width="27" align="right" background="{THEME_COLOR_PATH}/images/content/table_background.jpg"><img src="{THEME_COLOR_PATH}/images/content/table_icon_close.jpg" width="27" height="62"></td>
          </tr>
          <tr>
            <td><table width="100%"  border="00" cellspacing="0" cellpadding="0">
                <tr>
                  <td width="20">&nbsp;</td>
                  <td valign="top"><table width="100%" cellspacing="7">
                      <!-- BDP: page_message -->
                      <tr>
                        <td colspan="5" class="title"><font color="#FF0000">{MESSAGE}</font></td>
                      </tr>
                      <!-- EDP: page_message -->
                      <!-- BDP: tickets_list -->
                      <tr>
                        <td colspan="2" nowrap class="content3"><b>{TR_SUBJECT}</b></td>
                        <td width="150" class="content3" nowrap align="center"><b>{TR_URGENCY}</b></td>
                        <td width="100" class="content3" nowrap align="center"><b>{TR_LAST_DATA}</b></td>
                        <td width="100" align="center" nowrap class="content3"><strong>{TR_ACTION}</strong></td>
                      </tr>
                      <!-- BDP: tickets_item -->
                      <tr>
                        <td width="25" nowrap class="{CONTENT}"><b>{NEW}</b></td>
                        <td class="{CONTENT}" nowrap><img src="{THEME_COLOR_PATH}/images/icons/document.gif" width="12" height="15" align="left">
					  	<script language="javascript">
							document.write('<a href="view_ticket.php?ticket_id={ID}&screenwidth='+screen.width+'" class="link">');
						</script>
						{SUBJECT}</a>
					  </td>
                        <td class="{CONTENT}" nowrap align="center">{URGENCY}</td>
                        <td class="{CONTENT}" nowrap align="center">{LAST_DATE}</td>
                        <td class="{CONTENT}" nowrap align="center"><img src="{THEME_COLOR_PATH}/images/icons/delete.gif" width="16" height="16" border="0" align="absmiddle"> <a href="#" onClick="action_delete('delete_ticket.php?ticket_id={ID}')" class="link">{TR_DELETE}</a></td>
                      </tr>
                      <!-- EDP: tickets_item -->
                      <tr>
                        <td colspan="2" nowrap><input name="Submit" type="submit" class="button" onClick="MM_goToURL('parent','delete_ticket.php?delete=open');return document.MM_returnValue" value="{TR_DELETE_ALL}"></td>
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
    </table></td>
  </tr>
  <tr>
    <td height="71"><table width="100%"  border="0" cellspacing="0" cellpadding="0">
        <tr><td width="17"><img src="{THEME_COLOR_PATH}/images/top/down_left.jpg" width="17" height="71"></td><td width="198" valign="top" background="{THEME_COLOR_PATH}/images/top/downlogo_background.jpg"><table width="100%" border="0" cellpadding="0" cellspacing="0" >
          <tr>
            <td width="55"><a href="http://www.vhcs.net" target="_blank"><img src="{THEME_COLOR_PATH}/images/vhcs.gif" alt="" width="51" height="71" border="0"></a></td>
            <td class="bottom">{VHCS_LICENSE}</td>
          </tr>
        </table>          </td>
          <td background="{THEME_COLOR_PATH}/images/top/down_left_fill.jpg"><img src="{THEME_COLOR_PATH}/images/top/down_left_fill.jpg" width="2" height="71"></td><td width="766" background="{THEME_COLOR_PATH}/images/top/middle_background.jpg"><img src="{THEME_COLOR_PATH}/images/top/down_middle_background.jpg" width="766" height="71"></td>
          <td background="{THEME_COLOR_PATH}/images/top/down_right_fill.jpg"><img src="{THEME_COLOR_PATH}/images/top/down_right_fill.jpg" width="3" height="71"></td>
          <td width="9"><img src="{THEME_COLOR_PATH}/images/top/down_right.jpg" width="9" height="71"></td></tr>
    </table></td>
  </tr>
</table>
</body>
</html>
