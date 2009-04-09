<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_ADMIN_MANAGE_USERS_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
<script type="text/javascript">
<!--
function action_status(url, dmn_name) {
	if (!confirm(sprintf("{TR_MESSAGE_CHANGE_STATUS}", dmn_name)))
		return false;
	location = url;
}

function action_delete(url, dmn_name) {
	if (!confirm(sprintf("{TR_MESSAGE_DELETE}", dmn_name)))
		return false;
	location = url;
}
//-->
</script>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
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
    <td align="left">
    <table width="100%" cellpadding="5" cellspacing="5">
        <!-- BDP: page_message -->
        <tr>
            <td width="25">&nbsp;</td>
            <td colspan="3" class="title"><span class="message">{MESSAGE}</span></td>
        </tr>
        <!-- EDP: page_message -->
	    <tr>
		    <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_users.png" width="25" height="25"></td>
		    <td colspan="2" class="title">{TR_ADMINISTRATORS}</td>
	    </tr>
    </table></td>
    <td width="27" align="right">&nbsp;</td>
  </tr>
  <tr>
    <td valign="top"><!-- BDP: props_list -->
        <table width="100%" cellpadding="5" cellspacing="5">
          <!-- BDP: admin_message -->
          <tr>
            <td width="25">&nbsp;</td>
            <td colspan="3" class="title"><span class="message">{ADMIN_MESSAGE}</span></td>
          </tr>
          <!-- EDP: admin_message -->
          <!-- BDP: admin_list -->
          <tr>
            <td width="25">&nbsp;</td>
            <td class="content3"><b>{TR_ADMIN_USERNAME}</b></td>
            <td class="content3"><b>{TR_CREATED_ON}</b></td>
            <td class="content3" align="center"><b>{TR_ADMIN_CREATED_BY}</b></td>
            <td colspan="2" align="center" class="content3"><b>{TR_ADMIN_OPTIONS}</b></td>
          </tr>
          <!-- BDP: admin_item -->
          <tr>
            <td width="25">&nbsp;</td>
            <td class="{ADMIN_CLASS}">{ADMIN_USERNAME}</td>
            <td class="{ADMIN_CLASS}" align="center">{ADMIN_CREATED_ON}</td>
            <td class="{ADMIN_CLASS}" align="center">{ADMIN_CREATED_BY}</td>
            <td width="100" class="{ADMIN_CLASS}" align="center">
              <img src="{THEME_COLOR_PATH}/images/icons/edit.png" width="16" height="16" border="0" style="vertical-align:middle" /> <a href="{URL_EDIT_ADMIN}" class="link">{TR_EDIT}</a>
			</td>
            <td width="100" class="{ADMIN_CLASS}" align="center">
			  <!-- BDP: admin_delete_show -->
              -
              <!-- EDP: admin_delete_show -->
              <!-- BDP: admin_delete_link -->
              <img src="{THEME_COLOR_PATH}/images/icons/delete.png" width="16" height="16" border="0" style="vertical-align:middle" /> <a href="#" onclick="action_delete('{URL_DELETE_ADMIN}', '{ADMIN_USERNAME}')" class="link">{TR_DELETE}</a>
              <!-- EDP: admin_delete_link -->
            </td>
          </tr>
          <!-- EDP: admin_item -->
          <!-- EDP: admin_list -->
        </table>
      <!-- EDP: props_list -->
    </td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
</table>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td align="left"><table width="100%" cellpadding="5" cellspacing="5">
	<tr>
		<td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_users.png" width="25" height="25"></td>
		<td colspan="2" class="title">{TR_RESELLERS}</td>
	</tr>
</table></td>
    <td width="27" align="right">&nbsp;</td>
  </tr>
  <tr>
    <td><table width="100%" cellpadding="5" cellspacing="5">
      <!-- BDP: rsl_message -->
      <tr>
        <td width="25">&nbsp;</td>
        <td colspan="5" class="title"><span class="message">{RSL_MESSAGE}</span></td>
      </tr>
      <!-- EDP: rsl_message -->
      <!-- BDP: rsl_list -->
      <tr>
        <td width="25">&nbsp;</td>
        <td class="content3"><b>{TR_RSL_USERNAME}</b></td>
        <td width="150" align="center" class="content3"><b>{TR_CREATED_ON}</b></td>
        <td width="150" align="center" class="content3"><b>{TR_RSL_CREATED_BY}</b></td>
        <td colspan="3" align="center" class="content3"><b>{TR_RSL_OPTIONS}</b></td>
      </tr>
      <!-- BDP: rsl_item -->
      <tr>
        <td width="25">&nbsp;</td>
        <td class="{RSL_CLASS}">{RSL_USERNAME} </td>
        <td class="{RSL_CLASS}" align="center">{RESELLER_CREATED_ON}</td>
        <td class="{RSL_CLASS}" align="center">{RSL_CREATED_BY}</td>
        <td width="100" align="center" class="{RSL_CLASS}"><img src="{THEME_COLOR_PATH}/images/icons/details.png" width="16" height="16" border="0" style="vertical-align:middle" /> <a href="{URL_CHANGE_INTERFACE}" class="link" title="{TR_CHANGE_USER_INTERFACE}">{GO_TO_USER_INTERFACE}</a></td>
        <td width="100" align="center" class="{RSL_CLASS}"><img src="{THEME_COLOR_PATH}/images/icons/edit.png" width="16" height="16" border="0" style="vertical-align:middle" /> <a href="{URL_EDIT_RSL}" class="link">{TR_EDIT}</a></td>
        <td width="100" align="center" class="{RSL_CLASS}"><img src="{THEME_COLOR_PATH}/images/icons/delete.png" width="16" height="16" border="0" style="vertical-align:middle" /> <a href="#" onclick="action_delete('{URL_DELETE_RSL}', '{RSL_USERNAME}')" class="link">{TR_DELETE}</a></td>
      </tr>
      <!-- EDP: rsl_item -->
      <!-- EDP: rsl_list -->
    </table>
        <br /></td>
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
		<td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_users.png" width="25" height="25"></td>
		<td colspan="2" class="title">{TR_USERS}</td>
	</tr>
</table>
	</td>
    <td width="27" align="right">&nbsp;</td>
  </tr>
  <tr>
    <td><form action="manage_users.php" method="post" name="search_user" id="search_user">
      <table width="100%" cellpadding="5" cellspacing="5">
        <tr>
          <td width="25">&nbsp;</td>
          <td colspan="7" class="title"><table border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td><input name="search_for" type="text" class="textinput" value="{SEARCH_FOR}" style="width:140px" />
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
              <td><input name="Submit" type="submit" class="button" value="  {TR_SEARCH}  " /></td>
            </tr>
          </table>
		  </td>
          <td align="right"><input type="hidden" name="details" value="" />
            <img src="{THEME_COLOR_PATH}/images/icons/show_alias.png" width="16" height="16" style="vertical-align:middle" /> <a href="#" class="link" onclick="return sbmt_details(document.forms[0],'{SHOW_DETAILS}');">{TR_VIEW_DETAILS}</a>
		  </td>
        </tr>
        <!-- BDP: usr_message -->
        <tr>
          <td width="25">&nbsp;</td>
          <td colspan="7" class="title"><span class="message">{USR_MESSAGE}</span></td>
        </tr>
        <!-- EDP: usr_message -->
        <!-- BDP: usr_list -->
        <tr>
          <td width="25">&nbsp;</td>
          <td width="25" align="center" class="content3"><b>{TR_USER_STATUS}</b></td>
          <td class="content3"><b>{TR_USR_USERNAME}</b></td>
          <td width="100" align="center" class="content3"><b>{TR_CREATED_ON}</b></td>
          <td width="100" align="center" class="content3"><b>{TR_USR_CREATED_BY}</b></td>
          <td colspan="5" align="center" class="content3"><b>{TR_USR_OPTIONS}</b></td>
        </tr>
        <!-- BDP: usr_item -->
        <tr>
          <td width="25" align="center">&nbsp;</td>
          <td class="{USR_CLASS}" align="center"><a href="#" onclick="action_status('{URL_CHANGE_STATUS}', '{USR_USERNAME}')" class="link"><img src="{THEME_COLOR_PATH}/images/icons/{STATUS_ICON}" width="16" height="16" border="0" /></a></td>
          <td class="{USR_CLASS}"> <a href="http://www.{USR_USERNAME}/" target="_blank" class="link"><img src="{THEME_COLOR_PATH}/images/icons/goto.png" width="16" height="16" border="0"/>{USR_USERNAME}</a></td>
          <td class="{USR_CLASS}" align="center">{USER_CREATED_ON}</td>
          <td class="{USR_CLASS}" align="center">{USR_CREATED_BY}</td>
          <td width="100" align="center" class="{USR_CLASS}"><img src="{THEME_COLOR_PATH}/images/icons/identity.png" width="16" height="16" border="0" style="vertical-align:middle" /> <a href="domain_details.php?domain_id={DOMAIN_ID}" class="link">{TR_DETAILS}</a></td>
          <td width="100" align="center" class="{USR_CLASS}"><img src="{THEME_COLOR_PATH}/images/icons/details.png" width="16" height="16" border="0" style="vertical-align:middle" /> <a href="{URL_CHANGE_INTERFACE}" class="link" title="{TR_CHANGE_USER_INTERFACE}">{GO_TO_USER_INTERFACE}</a></td>
          <!-- BDP: edit_option -->
	  <td width="100" align="center" class="{USR_CLASS}"><img src="{THEME_COLOR_PATH}/images/icons/edit.png" width="16" height="16" border="0" style="vertical-align:middle" /> <a href="domain_edit.php?edit_id={DOMAIN_ID}" class="link">{TR_EDIT_DOMAIN}</a></td>
          <!-- EDP: edit_option -->
          <td width="100" align="center" class="{USR_CLASS}"><img src="{THEME_COLOR_PATH}/images/icons/users.gif" width="16" height="16" border="0" style="vertical-align:middle" /> <a href="{URL_EDIT_USR}" class="link">{TR_EDIT_USR}</a></td>
          <td width="100" align="center" class="{USR_CLASS}">
		    <!-- BDP: usr_delete_show -->
            -
            <!-- EDP: usr_delete_show -->
            <!-- BDP: usr_delete_link -->
              <img src="{THEME_COLOR_PATH}/images/icons/delete.png" width="16" height="16" border="0" style="vertical-align:middle" /> <a href="#" onclick="action_delete('{URL_DELETE_USR}', '{USR_USERNAME}')" class="link">{TR_DELETE}</a>
            <!-- EDP: usr_delete_link -->
          </td>
        </tr>
        <!-- BDP: user_details -->
        <tr>
          <td align="center">&nbsp;</td>
          <td class="content4" align="center">&nbsp;</td>
          <td colspan="7" class="content4">&nbsp;<a href="http://www.{ALIAS_DOMAIN}/" target="_blank" class="link"><img src="{THEME_COLOR_PATH}/images/icons/goto.png" width="16" height="16" border="0" style="vertical-align:middle" /> {ALIAS_DOMAIN}</a>&nbsp;</td>
        </tr>
        <!-- EDP: user_details -->
        <!-- EDP: usr_item -->
        <!-- EDP: usr_list -->
      </table>
      <input type="hidden" name="uaction" value="go_search" />
    </form>
        <div align="right"><br />
            <!-- BDP: scroll_prev_gray -->
          <img src="{THEME_COLOR_PATH}/images/icons/flip/prev_gray.gif" width="20" height="20" border="0" />
          <!-- EDP: scroll_prev_gray -->
          <!-- BDP: scroll_prev -->
          <a href="manage_users.php?psi={PREV_PSI}"><img src="{THEME_COLOR_PATH}/images/icons/flip/prev.gif" width="20" height="20" border="0" /></a>
          <!-- EDP: scroll_prev -->
          <!-- BDP: scroll_next_gray -->
          &nbsp;<img src="{THEME_COLOR_PATH}/images/icons/flip/next_gray.gif" width="20" height="20" border="0" />
          <!-- EDP: scroll_next_gray -->
          <!-- BDP: scroll_next -->
          &nbsp;<a href="manage_users.php?psi={NEXT_PSI}"><img src="{THEME_COLOR_PATH}/images/icons/flip/next.gif" width="20" height="20" border="0" /></a>
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
