<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_ALIAS_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
<script type="text/javascript">
<!--
function delete_account(url, name) {
	if (!confirm(sprintf("{TR_MESSAGE_DELETE}", name)))
		return false;
	location = url;
}
//-->
</script>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<!-- BDP: logged_from --><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td height="20" nowrap="nowrap" class="backButton">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.png" width="16" height="16" border="0" style="vertical-align:middle" /></a> {YOU_ARE_LOGGED_AS}</td>
      </tr>
    </table>
	<!-- EDP: logged_from -->
<table width="100%" border="0" cellspacing="0" cellpadding="0" height="100%" style="border-collapse: collapse;padding:0;margin:0;">
<tr>
<td align="left" valign="top" style="vertical-align: top; width: 195px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_left.jpg" border="0" alt="ispCP Logogram" /></td>
<td style="height: 56px; width:100%; background-image: url({THEME_COLOR_PATH}/images/top/top_bg.jpg)"><img src="{THEME_COLOR_PATH}/images/top/top_left_bg.jpg" border="0" alt="" /></td>
<td style="width: 73px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_right.jpg" border="0" alt="" /></td>
</tr>
	<tr>
		<td style="width: 195px; vertical-align: top;">{MENU}</td>
	    <td colspan="2" style="vertical-align: top;"><table style="width: 100%; border-collapse: collapse;padding:0;margin:0;">
          <tr height="95">
            <td style="padding-left:30px; width: 100%; background-image: url({THEME_COLOR_PATH}/images/top/middle_bg.jpg);">{MAIN_MENU}</td>
            <td style="padding:0;margin:0;text-align: right; width: 73px;vertical-align: top;"><img src="{THEME_COLOR_PATH}/images/top/middle_right.jpg" border="0" alt="" /></td>
          </tr>
          <tr>
            <td colspan="3"><table width="100%" border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td align="left"><table width="100%" cellpadding="5" cellspacing="5">
                    <tr>
                      <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_domains.png" width="25" height="25" /></td>
                      <td colspan="2" class="title">{TR_MANAGE_ALIAS}</td>
                    </tr>
                </table></td>
                <td width="27" align="right">&nbsp;</td>
              </tr>
              <tr>
                <td valign="top"><table width="100%" cellpadding="5" cellspacing="5">
                    <tr>
                      <td height="25" colspan="6" nowrap="nowrap"><!-- search starts here-->
                          <form name="search_alias_frm" method="post" action="alias.php?psi={PSI}">
                            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                              <tr>
                                <td width="40" nowrap="nowrap">&nbsp;</td>
                                <td width="300" nowrap="nowrap" class="content"><input name="search_for" type="text" class="textinput" value="{SEARCH_FOR}" style="width:140px" />
                                    <select name="search_common" class="textinput">
                                      <option value="alias_name" {M_DOMAIN_NAME_SELECTED}>{M_ALIAS_NAME}</option>
                                      <option value="account_name" {M_ACCOUN_NAME_SELECTED}>{M_ACCOUNT_NAME}</option>
                                    </select>
                                </td>
                                <td nowrap="nowrap" class="content"><input name="Submit" type="submit" class="button" value="  {TR_SEARCH}  " />
                                </td>
                              </tr>
                            </table>
                            <input type="hidden" name="uaction" value="go_search" />
                          </form>
                        <!-- search end here -->
					 </td>
					</tr>
                    <tr>
                      <td width="25" align="center" nowrap="nowrap">&nbsp;</td>
                      <td height="25" nowrap="nowrap" class="content3"><b>{TR_NAME}</b></td>
                      <td height="25" nowrap="nowrap" class="content3"><strong>{TR_REAL_DOMAIN}</strong></td>
                      <td width="80" height="25" align="center" nowrap="nowrap" class="content3"><b>{TR_FORWARD}</b></td>
                      <td width="80" height="25" align="center" nowrap="nowrap" class="content3"><b>{TR_STATUS}</b></td>
                      <td width="80" height="25" align="center" nowrap="nowrap" class="content3"><b>{TR_ACTION}</b></td>
                    </tr>
                    <!-- BDP: page_message -->
                    <tr>
                      <td width="25">&nbsp;</td>
                      <td colspan="5" class="title"><span class="message">{MESSAGE}</span></td>
                    </tr>
                    <!-- EDP: page_message -->
                    <!-- BDP: table_list -->
                    <!-- BDP: table_item -->
                    <tr>
                      <td width="25" align="center">&nbsp;</td>
                      <td class="{CONTENT}" nowrap="nowrap"><a href="http://www.{NAME}/" target="_blank" class="link"><img src="{THEME_COLOR_PATH}/images/icons/domain_icon.png" width="16" height="16" align="left" border="0" style="vertical-align:middle" /> {NAME}</a><br />
                        {ALIAS_IP}</td>
                      <td class="{CONTENT}" nowrap="nowrap">{REAL_DOMAIN}<br />
                        {REAL_DOMAIN_MOUNT}</td>
                      <td align="center" nowrap="nowrap" class="{CONTENT}">{FORWARD}</td>
                      <td class="{CONTENT}" nowrap="nowrap" align="center">{STATUS}</td>
                      <td class="{CONTENT}" nowrap="nowrap" align="center"><img src="{THEME_COLOR_PATH}/images/icons/delete.png" width="16" height="16" border="0" style="vertical-align:middle" /> <a href="#" onclick="delete_account('{DELETE_LINK}', '{NAME}')" class="link" title="{DELETE}">{DELETE}</a>  - <img src="{THEME_COLOR_PATH}/images/icons/edit.png" width="16" height="16" border="0" style="vertical-align:middle" /> <a href="{EDIT_LINK}" class="link" title="{EDIT}">{EDIT}</a></td>
                    </tr>
                    <!-- EDP: table_item -->
                    <!-- EDP: table_list -->
                  </table>
                    <table width="100%" border="0" cellspacing="3" cellpadding="0">
                      <tr>
                        <td width="30">&nbsp;</td>
                        <td><input name="Submit" type="submit" class="button" onclick="MM_goToURL('parent','alias_add.php');return document.MM_returnValue" value="   {TR_ADD_ALIAS}   " />
                        </td>
                        <td><div align="right">
                            <!-- BDP: scroll_prev_gray -->
                            <img src="{THEME_COLOR_PATH}/images/icons/flip/prev_gray.gif" width="20" height="20" border="0" />
                            <!-- EDP: scroll_prev_gray -->
                            <!-- BDP: scroll_prev -->
                            <a href="alias.php?psi={PREV_PSI}"><img src="{THEME_COLOR_PATH}/images/icons/flip/prev.gif" width="20" height="20" border="0" /></a>
                            <!-- EDP: scroll_prev -->
                            <!-- BDP: scroll_next_gray -->
                          &nbsp;<img src="{THEME_COLOR_PATH}/images/icons/flip/next_gray.gif" width="20" height="20" border="0" />
                          <!-- EDP: scroll_next_gray -->
                          <!-- BDP: scroll_next -->
                          &nbsp;<a href="alias.php?psi={NEXT_PSI}"><img src="{THEME_COLOR_PATH}/images/icons/flip/next.gif" width="20" height="20" border="0" /></a>
                          <!-- EDP: scroll_next -->
                        </div></td>
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
