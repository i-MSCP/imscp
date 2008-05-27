<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_CLIENT_SQL_ADD_USER_PAGE_TITLE}</title>
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

  document.captureEvents(Event.KEYPRESS);

  function Check_Key(e) {
  document.captureEvents(Event.KEYPRESS);
    if (document.layers && event.keyCode == 13) {
        document.sql_add_user_frm.Add_New.click();
        return false;
      }
    else {
      if (e.which == 13) {
        document.sql_add_user_frm.Add_New.click();
        return false;
      }
  	}
  } 
//-->
</script>
</head>
<body onkeypress="Check_Key(event);" onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/ftp_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/email_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<!-- BDP: logged_from --><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td height="20" nowrap="nowrap" class="backButton">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.png" width="18" height="18" border="0" align="absmiddle"></a> {YOU_ARE_LOGGED_AS}</td>
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
            <td colspan="3"><table width="100%" border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td align="left"><table width="100%" cellpadding="5" cellspacing="5">
                    <tr>
                      <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_sql.png" width="25" height="25"></td>
                      <td colspan="2" class="title">{TR_ADD_SQL_USER}</td>
                    </tr>
                </table></td>
                <td width="27" align="right">&nbsp;</td>
              </tr>
              <tr>
                <td><table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td width="40">&nbsp;</td>
                      <td valign="top"><form name="sql_add_user_frm" method="post" action="sql_add_user.php">
                          <table width="100%" cellspacing="5">
                            <!-- BDP: page_message -->
                            <tr>
                              <td colspan="2" class="title"><span class="message">{MESSAGE}</span></td>
                            </tr>
                            <!-- EDP: page_message -->
                            <!-- BDP: show_sqluser_list -->
                            <tr>
                              <td class="content2">{TR_SQL_USER_NAME}</td>
                              <td class="content"><select name="sqluser_id" id="sqluser_id">
                                  <!-- BDP: sqluser_list -->
                                  <option value="{SQLUSER_ID}" {SQLUSER_SELECTED}>{SQLUSER_NAME}</option>
                                  <!-- EDP: sqluser_list -->
                                </select>
                                &nbsp;&nbsp;
                                <input name="Add_Exist" type="submit" id="Add_Exist" value="{TR_ADD_EXIST}" class="button" tabindex="1"></td>
                            </tr>
                            <!-- EDP: show_sqluser_list -->
                            <!-- BDP: create_sqluser -->
                            <tr>
                              <td width="200" class="content2">{TR_USER_NAME}</td>
                              <td class="content"><input type="text" name="user_name" value="{USER_NAME}" style="width:170px" class="textinput">
                              </td>
                            </tr>
                            <tr>
                              <td width="200" class="content2"><!-- BDP: mysql_prefix_yes -->
                                  <input type="checkbox" name="use_dmn_id" {USE_DMN_ID}>
                                  <!-- EDP: mysql_prefix_yes -->
                                  <!-- BDP: mysql_prefix_no -->
                                  <input type="hidden" name="use_dmn_id" value="on">
                                  <!-- EDP: mysql_prefix_no -->
                                {TR_USE_DMN_ID}</td>
                              <td class="content"><!-- BDP: mysql_prefix_all -->
                                  <input type="radio" name="id_pos" value="start" {START_ID_POS_CHECKED}>
                                {TR_START_ID_POS}<br>
                                <input type="radio" name="id_pos" value="end" {END_ID_POS_CHECKED}>
                                {TR_END_ID_POS}
                                <!-- EDP: mysql_prefix_all -->
                                <!-- BDP: mysql_prefix_infront -->
                                <input type="radio" name="id_pos" value="start" checked="checked">
                                {TR_START_ID_POS}
                                <!-- EDP: mysql_prefix_infront -->
                                <!-- BDP: mysql_prefix_behind -->
                                <input type="radio" name="id_pos" value="end" checked="checked">
                                {TR_END_ID_POS}
                                <!-- EDP: mysql_prefix_behind -->
                              </td>
                            </tr>
                            <tr>
                              <td width="200" class="content2">{TR_PASS}</td>
                              <td class="content"><input type="password" name="pass" value="" style="width:170px" class="textinput">
                              </td>
                            </tr>
                            <tr>
                              <td width="200" class="content2">{TR_PASS_REP}</td>
                              <td class="content"><input type="password" name="pass_rep" value="" style="width:170px" class="textinput">
                              </td>
                            </tr>
                            <tr>
                              <td colspan="2">&nbsp;</td>
                            </tr>
                            <tr>
                              <td colspan="2"><input accesskey="#13" name="Add_New" type="submit" class="button" id="Add_New" value="  {TR_ADD}  ">
                                &nbsp;&nbsp;&nbsp;
                                <input type="button" name="Submit" value="   {TR_CANCEL}   " onclick="location.href = 'manage_sql.php'" class="button"></td>
                            </tr>
                            <!-- EDP: create_sqluser -->
                          </table>
                        <!-- end of content -->
                          <input type="hidden" name="uaction" value="add_user">
                          <input type="hidden" name="id" value="{ID}">
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
