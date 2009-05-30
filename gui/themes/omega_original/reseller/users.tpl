<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_CLIENT_CHANGE_PERSONAL_DATA_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
<script type="text/javascript">
<!--
function change_status(dom_id, dmn_name) {
	if (!confirm(sprintf("{TR_MESSAGE_CHANGE_STATUS}", dmn_name)))
		return false;
	location = ('domain_status_change.php?domain_id=' + dom_id);
}

function delete_account(url, dmn_name) {
	if (!confirm(sprintf("{TR_MESSAGE_DELETE_ACCOUNT}", dmn_name)))
		return false;
	location = url;
}
//-->
</script>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<!-- BDP: logged_from --><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td height="20" nowrap="nowrap" class="backButton">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.png" width="16" height="16" border="0" style="vertical-align:middle" alt="" /></a> {YOU_ARE_LOGGED_AS}</td>
      </tr>
    </table>
	<!-- EDP: logged_from -->
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="height:100%;padding:0;margin:0 auto;">
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
                      <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_users.png" width="25" height="25" alt="" /></td>
                      <td colspan="2" class="title">{TR_MANAGE_USERS}</td>
                    </tr>
                </table></td>
                <td width="27" align="right">&nbsp;</td>
              </tr>
              <tr>
                <td><form name="search_user" method="post" action="users.php">
                    <table width="100%" cellspacing="3">
                      <tr>
                        <td colspan="9" nowrap="nowrap">&nbsp;</td>
                      </tr>
                      <!-- BDP: page_message -->
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td colspan="8" class="title"><span class="message">{MESSAGE}</span></td>
                      </tr>
                        <!-- EDP: page_message -->
                      <tr>
                        <td>&nbsp;</td>
                        <td colspan="6"><table border="0" cellspacing="0" cellpadding="0">
                            <tr>
                              <td nowrap="nowrap"><input name="search_for" type="text" class="textinput" value="{SEARCH_FOR}" style="width:140px" />
                                  <select name="search_common" class="textinput">
                                    <option value="domain_name" {M_DOMAIN_NAME_SELECTED}>{M_DOMAIN_NAME}</option>
                                    <option value="customer_id" {M_CUSTOMER_ID_SELECTED}>{M_CUSTOMER_ID}</option>
                                    <option value="lname" {M_LAST_NAME_SELECTED}>{M_LAST_NAME}</option>
                                    <option value="firm" {M_COMPANY_SELECTED}>{M_COMPANY}</option>
                                    <option value="city" {M_CITY_SELECTED}>{M_CITY}</option>
                                    <option value="state" {M_STATE_SELECTED}>{M_STATE}</option>
                                    <option value="country" {M_COUNTRY_SELECTED}>{M_COUNTRY}</option>
                                  </select>
                                  <select name="search_status" class="textinput">
                                    <option value="all" {M_ALL_SELECTED}>{M_ALL}</option>
                                    <option value="ok" {M_OK_SELECTED}>{M_OK}</option>
                                    <option value="disabled" {M_SUSPENDED_SELECTED}>{M_SUSPENDED}</option>
                                  </select></td>
                              <td nowrap="nowrap"><input name="Submit" type="submit" class="button" value=" {TR_SEARCH} " />
                              </td>
                            </tr>
                        </table></td>
                        <td colspan="3" align="right"><input type="hidden" name="details" value="" />
                            <img src="{THEME_COLOR_PATH}/images/icons/show_alias.png" width="16" height="16" style="vertical-align:middle" alt="" /> <a href="#" class="link" onclick="return sbmt_details(document.forms[0],'{SHOW_DETAILS}');">{TR_VIEW_DETAILS}</a></td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content3" width="20" align="center"><b>{TR_USER_STATUS}</b></td>
                        <td class="content3"><b>{TR_USERNAME}</b></td>
                        <td class="content3" width="90" align="center"><b>{TR_CREATION_DATE}</b></td>
                        <td class="content3" width="90" align="center"><b>{TR_DISK_USAGE}</b></td>
                        <td class="content3" width="90" align="center"><b>{TR_DISK_USAGE_PERCENT}</b></td>                        
                        <td colspan="6" align="center" class="content3"><b>{TR_ACTION}</b></td>
                      </tr>
                      <!-- BDP: users_list -->
                      <!-- BDP: user_entry -->
                      <tr class="hl">
                        <td align="center">&nbsp;</td>
                        <td class="{CLASS_TYPE_ROW}" align="center"><a href="#" onclick="change_status('{URL_CHANGE_STATUS}', '{NAME}')"><img src="{THEME_COLOR_PATH}/images/icons/{STATUS_ICON}" width="16" height="16" border="0" alt="" /></a></td>
                        <td class="{CLASS_TYPE_ROW}"><img src="{THEME_COLOR_PATH}/images/icons/goto.png" width="16" height="16" border="0" alt="" /> <a href="http://{NAME}/" target="_blank" class="link">{NAME}</a></td>
                        <td class="{CLASS_TYPE_ROW}" width="90" align="center">{CREATION_DATE}</td>
						<td class="{CLASS_TYPE_ROW}" width="90" align="center">{DISK_USAGE} of {DISK_LIMIT} MB</td>
						<td class="{CLASS_TYPE_ROW}" width="90" align="center">{DISK_USAGE_PERCENT} %</td>
                        <td nowrap="nowrap" width="80" align="center" class="{CLASS_TYPE_ROW}"><img src="{THEME_COLOR_PATH}/images/icons/identity.png" width="16" height="16" border="0" style="vertical-align:middle" alt="" /> <a href="domain_details.php?domain_id={DOMAIN_ID}" class="link">{TR_DETAILS}</a></td>
                        <!-- BDP: edit_option -->
                        <td nowrap="nowrap" width="80" align="center" class="{CLASS_TYPE_ROW}"><img src="{THEME_COLOR_PATH}/images/icons/edit.png" width="16" height="16" border="0" style="vertical-align:middle" alt="" /> <a href="domain_edit.php?edit_id={DOMAIN_ID}" class="link">{TR_EDIT_DOMAIN}</a></td>
                        <td nowrap="nowrap" width="80" align="center" class="{CLASS_TYPE_ROW}"><img src="{THEME_COLOR_PATH}/images/icons/users.gif" width="16" height="16" border="0" style="vertical-align:middle" alt="" /> <a href="user_edit.php?edit_id={USER_ID}" class="link">{TR_EDIT_USER}</a></td>
                        <!-- EDP: edit_option -->
                        <td nowrap="nowrap" width="80" align="center" class="{CLASS_TYPE_ROW}"><img src="{THEME_COLOR_PATH}/images/icons/stats.gif" width="16" height="16" border="0" style="vertical-align:middle" alt="" /> <a href="domain_statistics.php?month={VL_MONTH}&amp;year={VL_YEAR}&amp;domain_id={DOMAIN_ID}" class="link">{TR_STAT}</a></td>
                        <td nowrap="nowrap" width="80" align="center" class="{CLASS_TYPE_ROW}"><img src="{THEME_COLOR_PATH}/images/icons/details.png" width="16" height="16" border="0" style="vertical-align:middle" alt="" /> <a href="change_user_interface.php?to_id={USER_ID}" class="link">{CHANGE_INTERFACE}</a></td>
                        <td nowrap="nowrap" width="80" align="center" class="{CLASS_TYPE_ROW}"><img src="{THEME_COLOR_PATH}/images/icons/delete.png" width="16" height="16" border="0" style="vertical-align:middle" alt="" /> <a href="#" onclick="delete_account('user_delete.php?id={USER_ID}', '{NAME}')" class="link">{ACTION}</a></td>
                      </tr>
                      <!-- BDP: user_details -->
                      <tr>
                        <td align="center">&nbsp;</td>
                        <td class="content4" align="center">&nbsp;</td>
                        <td colspan="8" class="content4">&nbsp;&nbsp;<a href="http://www.{ALIAS_DOMAIN}/" target="_blank" class="link"><img src="{THEME_COLOR_PATH}/images/icons/goto.png" width="16" height="16" border="0" style="vertical-align:middle" alt="" /> {ALIAS_DOMAIN}</a>&nbsp;</td>
                      </tr>
                      <!-- EDP: user_details -->
                      <!-- EDP: user_entry -->
                      <!-- EDP: users_list -->
                    </table>
                  <input type="hidden" name="uaction" value="go_search" />
                  </form>
                    <div align="right"><br />
                        <!-- BDP: scroll_prev_gray -->
                        <img src="{THEME_COLOR_PATH}/images/icons/flip/prev_gray.gif" width="20" height="20" border="0" alt="" />
                        <!-- EDP: scroll_prev_gray -->
                        <!-- BDP: scroll_prev -->
                        <a href="users.php?psi={PREV_PSI}"><img src="{THEME_COLOR_PATH}/images/icons/flip/prev.gif" width="20" height="20" border="0" alt="" /></a>
                        <!-- EDP: scroll_prev -->
                        <!-- BDP: scroll_next_gray -->
                      &nbsp;<img src="{THEME_COLOR_PATH}/images/icons/flip/next_gray.gif" width="20" height="20" border="0" alt="" />
                      <!-- EDP: scroll_next_gray -->
                      <!-- BDP: scroll_next -->
                      &nbsp;<a href="users.php?psi={NEXT_PSI}"><img src="{THEME_COLOR_PATH}/images/icons/flip/next.gif" width="20" height="20" border="0" alt="" /></a>
                      <!-- EDP: scroll_next -->
                  </div></td>
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
