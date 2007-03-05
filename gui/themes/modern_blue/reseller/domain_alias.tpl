<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_ALIAS_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/vhcs.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/vhcs.js"></script>
<script>
<!--

function change_status(dom_id) {
	if (!confirm("{TR_MESSAGE_CHANGE_STATUS}"))
		return false;

	location = ('change_status.php?domain_id=' + dom_id);
}

function delete_account(url) {
	if (!confirm("{TR_MESSAGE_DELETE}"))
		return false;

	location = url;
}

function MM_goToURL() { //v3.0
  var i, args=MM_goToURL.arguments; document.MM_returnValue = false;
  for (i=0; i<(args.length-1); i+=2) eval(args[i]+".location='"+args[i+1]+"'");
}
//-->
</script>

</head>
<body onLoad="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif','{THEME_COLOR_PATH}/images/icons/logout_a.gif','{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<table width="100%" border="0" cellspacing="0" cellpadding="0" height="100%">
  <tr>
    <td height="80" align="left" valign="top">
	<!-- BDP: logged_from --><table width="100%"  border="00" cellspacing="0" cellpadding="0">
      <tr>
        <td height="20" nowrap background="{THEME_COLOR_PATH}/images/button.gif">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.gif" width="18" height="18" border="0" align="absmiddle"></a> <font color="red">{YOU_ARE_LOGGED_AS}</font> </td>
      </tr>
    </table>
	<!-- EDP: logged_from -->
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
            <td height="62" align="left" background="{THEME_COLOR_PATH}/images/content/table_background.jpg" class="title"><img src="{THEME_COLOR_PATH}/images/content/table_icon_domains.jpg" width="85" height="62" align="absmiddle">{TR_MANAGE_ALIAS}</td>
            <td width="27" align="right" background="{THEME_COLOR_PATH}/images/content/table_background.jpg"><img src="{THEME_COLOR_PATH}/images/content/table_icon_close.jpg" width="27" height="62"></td>
          </tr>
          <tr>
            <td valign="top">

			  <table width="100%" cellpadding="5" cellspacing="5">
                <tr>
                  <td height="25" colspan="6" nowrap><!-- serach gose here-->
                      <form name="search_alias_frm" method="post" action="domain_alias.php?psi={PSI}">
                        <table width="100%" border="0" cellpadding="0" cellspacing="0">
                          <tr>
                            <td width="30" nowrap>&nbsp;</td>
                            <td width="300" nowrap  class="content"><input name="search_for" type="text" class="textinput" value="{SEARCH_FOR}" style="width:140px">
                                <select name="search_common" class="textinput">
                                  <option value="alias_name" {M_DOMAIN_NAME_SELECTED}>{M_ALIAS_NAME}</option>
                                  <option value="account_name" {M_ACCOUN_NAME_SELECTED}>{M_ACCOUNT_NAME}</option>
                                </select>
                            </td>
                            <td nowrap class="content"><input name="Submit" type="submit" class="button" value="  {TR_SEARCH}  ">                              </td>
                          </tr>
                        </table>
                        <input type="hidden" name="uaction" value="go_search">
                      </form>
                      <!-- serach end here -->
                  </td>
                </tr>
				<tr>
                   	<td><input name="Submit" type="submit" class="button" onClick="MM_goToURL('parent','add_alias.php');return document.MM_returnValue" value="   {TR_ADD_ALIAS}   ">
                    </td>
				</tr>
                <tr>
                  <td width="20" align="center" nowrap>&nbsp;</td>
                  <td height="25" nowrap class="content3"><b>{TR_NAME}</b></td>
                  <td height="25" nowrap class="content3"><strong>{TR_REAL_DOMAIN}</strong></td>
                  <td width="80" height="25" align="center" nowrap class="content3"><b>{TR_FORWARD}</b></td>
                  <td width="80" height="25" align="center" nowrap class="content3"><b>{TR_STATUS}</b></td>
                  <td width="80" height="25" align="center" nowrap class="content3"><b>{TR_ACTION}</b></td>
                </tr>
                <!-- BDP: page_message -->
                <tr>
                  <td width="20">&nbsp;</td>
                  <td colspan="5" class="title"><font color="#FF0000">{MESSAGE}</font></td>
                  </tr>
                <!-- EDP: page_message -->
                <!-- BDP: table_list -->
                <!-- BDP: table_item -->
                <tr>
                  <td width="20" align="center">&nbsp;</td>
                  <td class="{CONTENT}" nowrap><img src="{THEME_COLOR_PATH}/images/icons/domain_icon.gif" width="15" height="14" align="left"> {NAME}<br>
      {ALIAS_IP}</td>
                  <td class="{CONTENT}" nowrap>{REAL_DOMAIN}<br>
      {REAL_DOMAIN_MOUNT}</td>
                  <td align="center" nowrap class="{CONTENT}"><a href="{EDIT_LINK}" class="link">{FORWARD} </a></td>
                  <td class="{CONTENT}" nowrap align="center">{STATUS}</td>
                  <td class="{CONTENT}" nowrap align="center"><img src="{THEME_COLOR_PATH}/images/icons/delete.gif" width="16" height="16" border="0" align="absmiddle"> <a href="#" onClick="delete_account('{DELETE_LINK}')" class="link">{DELETE}</a></td>
                </tr>
                <!-- EDP: table_item -->
                <!-- EDP: table_list -->
              </table>
			  <table width="100%"  border="0" cellspacing="3" cellpadding="0">
                <tr>
                  <td width="30">&nbsp;</td>
                  <td><input name="Submit" type="submit" class="button" onClick="MM_goToURL('parent','add_alias.php');return document.MM_returnValue" value="   {TR_ADD_ALIAS}   ">
                  </td>
                  <td><div align="right">
                      <!-- BDP: scroll_prev_gray -->
                      <img src="{THEME_COLOR_PATH}/images/icons/flip/prev_gray.gif" width="20" height="20" border="0">
                      <!-- EDP: scroll_prev_gray -->
                      <!-- BDP: scroll_prev -->
                      <a href="domain_alias.php?psi={PREV_PSI}"><img src="{THEME_COLOR_PATH}/images/icons/flip/prev.gif" width="20" height="20" border="0"></a>
                      <!-- EDP: scroll_prev -->
                      <!-- BDP: scroll_next_gray -->
&nbsp;<img src="{THEME_COLOR_PATH}/images/icons/flip/next_gray.gif" width="20" height="20" border="0">
        <!-- EDP: scroll_next_gray -->
        <!-- BDP: scroll_next -->
&nbsp;<a href="domain_alias.php?psi={NEXT_PSI}"><img src="{THEME_COLOR_PATH}/images/icons/flip/next.gif" width="20" height="20" border="0"></a>
        <!-- EDP: scroll_next -->
                  </div></td>
                </tr>
              </table>
			</td>
            <td>&nbsp;</td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
          </tr>
        </table>
          </td>
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
