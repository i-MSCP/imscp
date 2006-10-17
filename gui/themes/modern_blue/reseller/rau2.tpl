<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_CLIENT_CHANGE_PERSONAL_DATA_PAGE_TITLE}</title>
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
	if (!confirm("{TR_MESSAGE_DELETE_ACCOUNT}"))
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
            <td height="62" align="left" background="{THEME_COLOR_PATH}/images/content/table_background.jpg" class="title"><img src="{THEME_COLOR_PATH}/images/content/table_icon_user.jpg" width="85" height="62" align="absmiddle">{TR_ADD_USER}</td>
            <td width="27" align="right" background="{THEME_COLOR_PATH}/images/content/table_background.jpg"><img src="{THEME_COLOR_PATH}/images/content/table_icon_close.jpg" width="27" height="62"></td>
          </tr>
          <tr>
            <td valign="top">

			<!-- BDP: add_user -->
			<form name="reseller_add_users_first_frm" method="post" action="rau2.php">
			  <input type="hidden" name="uaction" value="rau2_nxt">
              <table width="100%" cellpadding="5" cellspacing="5">
                <tr>
                  <td width="20" >&nbsp;</td>
                  <td colspan="2" class="content3"><b>{TR_HOSTING_PLAN_PROPERTIES}</b></td>
                  </tr>
                <div align="center"><font color="#FF0000">{MESSAGE}</font></div>
                <font color="#FF0000">
                <tr> <td width="20">&nbsp;</td>
                  <td class="content2" width="200">{TR_TEMPLATE_NAME}</td>
                  <td class="content"><input name=template type=hidden id="template" value="{VL_TEMPLATE_NAME}">
      {VL_TEMPLATE_NAME} </td>
                </tr>
                <tr> <td width="20">&nbsp;</td>
                  <td class="content2" width="200">{TR_MAX_SUBDOMAIN}<b><i></i></b></td>
                  <td class="content"><input type="text" name=nreseller_max_subdomain_cnt value="{MAX_SUBDMN_CNT}" style="width:140px" class="textinput">
                  </td>
                </tr>
				<tr>
				<td width="20">&nbsp;</td>
                <td class="content2" width="175">{TR_MAX_DOMAIN_ALIAS}<b><i></i></b></td>
                <td class="content">
                  <input type="text" name=nreseller_max_alias_cnt value="{MAX_DMN_ALIAS_CNT}" style="width:140px" class="textinput">
                </td>
              </tr>

                <tr> <td width="20">&nbsp;</td>
                  <td class="content2" width="200">{TR_MAX_MAIL_COUNT}<b><i></i></b></td>
                  <td class="content">
				  	<input type="text" name=nreseller_max_mail_cnt value="{MAX_MAIL_CNT}" style="width:140px" class="textinput">
                  </td>
				</tr>
                <tr> <td width="20">&nbsp;</td>
                  <td class="content2" width="200">{TR_MAX_FTP}<b><i></i></b></td>
                  <td class="content">
				  	<input type="text" name=nreseller_max_ftp_cnt value="{MAX_FTP_CNT}" style="width:140px" class="textinput">
                  </td>
                </tr>
                <tr> <td width="20">&nbsp;</td>
                  <td class="content2" width="200">{TR_MAX_SQL_DB}<b><i></i></b></td>
                  <td class="content">
				  	<input type="text" name=nreseller_max_sql_db_cnt value="{MAX_SQL_CNT}" style="width:140px" class="textinput">
                  </td>
                </tr>
                <tr> <td width="20">&nbsp;</td>
                  <td class="content2" width="200">{TR_MAX_SQL_USERS}<b><i></i></b></td>
                  <td class="content">
				  	<input type="text" name=nreseller_max_sql_user_cnt value="{VL_MAX_SQL_USERS}" style="width:140px" class="textinput">
                  </td>
                </tr>
                <tr> <td width="20">&nbsp;</td>
                  <td class="content2" width="200">{TR_MAX_TRAFFIC}<b><i></i></b></td>
                  <td class="content">
				  	<input type="text" name=nreseller_max_traffic value="{VL_MAX_TRAFFIC}" style="width:140px" class="textinput">
                  </td>
                </tr>
                <tr> <td width="20">&nbsp;</td>
                  <td class="content2" width="200">{TR_MAX_DISK_USAGE}<b><i></i></b></td>
                  <td class="content">
				  	<input type="text" name=nreseller_max_disk value="{VL_MAX_DISK_USAGE}" style="width:140px" class="textinput">
                  </td>
                </tr>
                <tr> <td width="20">&nbsp;</td>
                  <td class="content2" width="200">{TR_PHP}</td>
                  <td class="content">
				  	<input name="php" type="radio" value="yes" {VL_PHPY}>
      {TR_YES}
        <input type="radio" name="php" value="no" {VL_PHPN}>
      {TR_NO}</td>
                </tr>
                <tr> <td width="20">&nbsp;</td>
                  <td class="content2" width="200">{TR_CGI}</td>
                  <td class="content">
				  	<input name="cgi" type="radio" value="yes" {VL_CGIY}>
      {TR_YES}
        <input type="radio" name="cgi" value="no" {VL_CGIN}>
      {TR_NO}</td>
                </tr>
                <tr>
                  <td>&nbsp;</td>
                  <td colspan="2"><input name="Submit" type="submit" class="button" value="{TR_NEXT_STEP}"></td>
                  </tr>
                </font>
              </table>
          			</form>
			<!-- EDP: add_user -->


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
