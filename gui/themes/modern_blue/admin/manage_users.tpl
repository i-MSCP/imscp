<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_ADMIN_MANAGE_USERS_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/vhcs.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/vhcs.js"></script>
<script>
<!--

function action_status(url) {
	if (!confirm("{TR_MESSAGE_CHANGE_STATUS}"))
		return false;

	location = url;
}

function action_delete(url) {
	if (!confirm("{TR_MESSAGE_DELETE}"))
		return false;

	location = url;
}

function sbmt(form, uaction) {

    form.details.value = uaction;
    form.submit();

    return false;

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
            <td height="62" align="left" background="{THEME_COLOR_PATH}/images/content/table_background.jpg" class="title"><img src="{THEME_COLOR_PATH}/images/content/table_icon_users.jpg" width="85" height="62" align="absmiddle">{TR_ADMINISTRATORS}</td>
            <td width="27" align="right" background="{THEME_COLOR_PATH}/images/content/table_background.jpg"><img src="{THEME_COLOR_PATH}/images/content/table_icon_close.jpg" width="27" height="62"></td>
          </tr>
          <tr>
            <td valign="top">
			<!-- BDP: props_list -->
			<table width="100%" cellpadding="5" cellspacing="5">
              <!-- BDP: page_message -->
              <tr>
                <td width="20">&nbsp;</td>
                <td colspan="3" class="title"><font color="#FF0000">{MESSAGE}</font></td>
                </tr>
              <!-- EDP: page_message -->
              <!-- BDP: admin_message -->
              <tr>
                <td width="20" nowrap>&nbsp;</td>
                <td colspan="3" nowrap class="title"><font color="#FF0000">{ADMIN_MESSAGE}</font></td>
                </tr>
              <!-- EDP: admin_message -->
              <!-- BDP: admin_list -->
              <tr>
                <td width="20">&nbsp;</td>
                <td class="content3"><b>{TR_ADMIN_USERNAME}</b></td>
                <td class="content3" align="center"><b>{TR_ADMIN_CREATED_BY}</b></td>
                <td width="150" align="center" class="content3"><b>{TR_ADMIN_OPTIONS}</b></td>
              </tr>
              <!-- BDP: admin_item -->
              <tr>
                <td width="20">&nbsp;</td>
                <td class="{ADMIN_CLASS}"><a href="{URL_EDIT_ADMIN}" class="link">{ADMIN_USERNAME}</a> </td>
                <td class="{ADMIN_CLASS}" align="center">{ADMIN_CREATED_BY}</td>

                <td width="150" class="{ADMIN_CLASS}" align="center">
				<!-- BDP: admin_delete_show -->
				{TR_DELETE}
				<!-- EDP: admin_delete_show -->
				<!-- BDP: admin_delete_link -->
				<img src="{THEME_COLOR_PATH}/images/icons/delete.gif" width="16" height="16" border="0" align="absmiddle"> <a href="#" onClick="action_delete('{URL_DELETE_ADMIN}')" class="link">{TR_DELETE}</a>
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
              <td height="62" align="left" background="{THEME_COLOR_PATH}/images/content/table_background.jpg" class="title"><img src="{THEME_COLOR_PATH}/images/content/table_icon_users.jpg" width="85" height="62" align="absmiddle">{TR_RESELLERS}</td>
              <td width="27" align="right" background="{THEME_COLOR_PATH}/images/content/table_background.jpg"><img src="{THEME_COLOR_PATH}/images/content/table_icon_close.jpg" width="27" height="62"></td>
            </tr>
            <tr>
              <td><table width="100%" cellpadding="5" cellspacing="5">
                <!-- BDP: rsl_message -->
                <tr>
                  <td width="20" nowrap>&nbsp;</td>
                  <td colspan="5" nowrap class="title"><font color="#FF0000">{RSL_MESSAGE}</font></td>
                  </tr>
                <!-- EDP: rsl_message -->
                <!-- BDP: rsl_list -->
                <tr>
                  <td width="20">&nbsp;</td>
                  <td class="content3"><b>{TR_RSL_USERNAME}</b></td>
                  <td width="150" align="center" class="content3"><b>{TR_CREATED_ON}</b></td>
                  <td width="150" align="center" class="content3"><b>{TR_RSL_CREATED_BY}</b></td>
                  <td colspan="2" align="center" class="content3"><b>{TR_RSL_OPTIONS}</b></td>
                  </tr>
                <!-- BDP: rsl_item -->
                <tr>
                  <td width="20">&nbsp;</td>
                  <td class="{RSL_CLASS}"><a href="{URL_EDIT_RSL}" class="link">{RSL_USERNAME} </a> </td>
                  <td class="{RSL_CLASS}" align="center">{RESELLER_CREATED_ON}</td>
                  <td class="{RSL_CLASS}" align="center">{RSL_CREATED_BY}</td>
                  <td width="150" align="center" class="{RSL_CLASS}"><img src="{THEME_COLOR_PATH}/images/icons/details.gif" width="18" height="18" border="0" align="absmiddle"> <a href="{URL_CHANGE_INTERFACE}" class="link">{GO_TO_USER_INTERFACE}</a></td>
                  <td width="100" align="center" class="{RSL_CLASS}"><img src="{THEME_COLOR_PATH}/images/icons/delete.gif" width="16" height="16" border="0" align="absmiddle"> <a href="#" onClick="action_delete('{URL_DELETE_RSL}')" class="link">{TR_DELETE}</a></td>
                </tr>
                <!-- EDP: rsl_item -->
                <!-- EDP: rsl_list -->
              </table>
                <br></td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
            </tr>
          </table>
          <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td height="62" align="left" background="{THEME_COLOR_PATH}/images/content/table_background.jpg" class="title"><img src="{THEME_COLOR_PATH}/images/content/table_icon_users.jpg" width="85" height="62" align="absmiddle">{TR_USERS}</td>
              <td width="27" align="right" background="{THEME_COLOR_PATH}/images/content/table_background.jpg"><img src="{THEME_COLOR_PATH}/images/content/table_icon_close.jpg" width="27" height="62"></td>
            </tr>
            <tr>
              <td>
			  <form name="search_user" method="post" action="manage_users.php">
            <table width="100%" cellpadding="5" cellspacing="5">

              <tr>
                <td width="20" nowrap>&nbsp;</td>
                <td colspan="5" nowrap class="title">
				  <table border="0" cellspacing="0" cellpadding="0">
                  <tr>
                    <td nowrap><input name="search_for" type="text" class="textinput" value="{SEARCH_FOR}" style="width:140px">
                      <select name="search_common" class="textinput">
                        <option value="domain_name" {M_DOMAIN_NAME_SELECTED}>{M_DOMAIN_NAME}</option>
                        <option value="customer_id" {M_CUSTOMER_ID_SELECTED}>{M_CUSTOMER_ID}</option>
                        <option value="lname" {M_LAST_NAME_SELECTED}>{M_LAST_NAME}</option>
                        <option value="firm" {M_COMPANY_SELECTED}>{M_COMPANY}</option>
                        <option value="city" {M_CITY_SELECTED}>{M_CITY}</option>
                        <option value="country" {M_COUNTRY_SELECTED}>{M_COUNTRY}</option>
                          </select>
                      <select name="search_status" class="textinput">
                        <option value="all" {M_ALL_SELECTED}>{M_ALL}</option>
                        <option value="ok" {M_OK_SELECTED}>{M_OK}</option>
                        <option value="disabled" {M_SUSPENDED_SELECTED}>{M_SUSPENDED}</option>
                                </select></td>
                    <td nowrap><input name="Submit" type="submit" class="button" value="  {TR_SEARCH}  ">
                    </td>
                  </tr>
                </table>


				</td>
                <td colspan="2" align="right" nowrap>
				<input type="hidden" name="details" value="">
				<img src="{THEME_COLOR_PATH}/images/icons/show_alias.jpg" width="15" height="16" align="absmiddle"> <a href="#" class="link" onClick="return sbmt(document.forms[0],'{SHOW_DETAILS}');">{TR_VIEW_DETAILS}</a>
				</td>
                </tr>
			  <!-- BDP: usr_message -->
              <tr>
                <td width="20" nowrap>&nbsp;</td>
                <td colspan="7" nowrap class="title"><font color="#FF0000">{USR_MESSAGE}</font></td>
                </tr>
              <!-- EDP: usr_message -->
              <!-- BDP: usr_list -->
              <tr>
                <td width="20">&nbsp;</td>
                <td width="20" align="center" class="content3"><b>{TR_USER_STATUS}</b></td>
                <td class="content3"><b>{TR_USR_USERNAME}</b></td>
                <td width="100" align="center" class="content3"><b>{TR_CREATED_ON}</b></td>
                <td width="100" align="center" class="content3"><b>{TR_USR_CREATED_BY}</b></td>
                <td colspan="3" align="center" class="content3"><b>{TR_USR_OPTIONS}</b></td>
                </tr>
              <!-- BDP: usr_item -->
              <tr>
                <td width="20" align="center">&nbsp;</td>
                <td class="{USR_CLASS}" align="center"><a href="#" onClick="action_status('{URL_CHNAGE_STATUS}')" class="link"><img src="{THEME_COLOR_PATH}/images/icons/{STATUS_ICON}" width="18" height="18" border="0"></a></td>
                <td class="{USR_CLASS}"><a href="{URL_EDIT_USR}" class="link">{USR_USERNAME} </a> </td>
                <td class="{USR_CLASS}" align="center">{USER_CREATED_ON}</td>
                <td class="{USR_CLASS}" align="center">{USR_CREATED_BY}</td>

	        <td width="80" align="center" nowrap class="{USR_CLASS}"><img src="{THEME_COLOR_PATH}/images/icons/bullet.gif" width="18" height="18" border="0" align="absmiddle"> <a href="domain_details.php?domain_id={DOMAIN_ID}" class="link">{TR_DETAILS}</a></td>
		<td width="80" align="center" nowrap class="{USR_CLASS}"><img src="{THEME_COLOR_PATH}/images/icons/details.gif" width="18" height="18" border="0" align="absmiddle"> <a href="{URL_CHANGE_INTERFACE}" class="link">{GO_TO_USER_INTERFACE}</a></td>
		<td width="80" align="center" nowrap class="{USR_CLASS}">
		<!-- BDP: usr_delete_show -->
		{TR_DELETE}
		<!-- EDP: usr_delete_show -->
		<!-- BDP: usr_delete_link -->
		<img src="{THEME_COLOR_PATH}/images/icons/delete.gif" width="16" height="16" border="0" align="absmiddle"> <a href="#" onClick="action_delete('{URL_DELETE_USR}')" class="link">{TR_DELETE}</a>
		<!-- EDP: usr_delete_link -->
		</td>

              </tr>
			  <!-- BDP: user_details -->
              <tr>
                <td align="center">&nbsp;</td>
                <td class="content4" align="center">&nbsp;</td>
                <td colspan="7" class="content4">&nbsp;&nbsp;<img src="{THEME_COLOR_PATH}/images/icons/show_alias.jpg" width="15" height="16" align="absmiddle">&nbsp;{ALIAS_DOMAIN}</td>
                </tr>
			  <!-- EDP: user_details -->


              <!-- EDP: usr_item -->
              <!-- EDP: usr_list -->
            </table>
			<input type="hidden" name="uaction" value="go_search">
		  </form>
			<div align="right"><br>
                <!-- BDP: scroll_prev_gray --><img src="{THEME_COLOR_PATH}/images/icons/flip/prev_gray.gif" width="20" height="20" border="0"><!-- EDP: scroll_prev_gray --><!-- BDP: scroll_prev --><a href="manage_users.php?psi={PREV_PSI}"><img src="{THEME_COLOR_PATH}/images/icons/flip/prev.gif" width="20" height="20" border="0"></a><!-- EDP: scroll_prev --><!-- BDP: scroll_next_gray -->&nbsp;<img src="{THEME_COLOR_PATH}/images/icons/flip/next_gray.gif" width="20" height="20" border="0"><!-- EDP: scroll_next_gray --><!-- BDP: scroll_next -->&nbsp;<a href="manage_users.php?psi={NEXT_PSI}"><img src="{THEME_COLOR_PATH}/images/icons/flip/next.gif" width="20" height="20" border="0"></a><!-- EDP: scroll_next -->
            </div>
			  </td>
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
