<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_CLIENT_ADD_SQL_DATABASE_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/ftp_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/email_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
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
                      <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_sql.png" width="25" height="25" /></td>
                      <td colspan="2" class="title">{TR_ADD_DATABASE}</td>
                    </tr>
                </table></td>
                <td width="27" align="right">&nbsp;</td>
              </tr>
              <tr>
                <td><table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td></td>
                      <td valign="top"><form name="add_sql_database_frm" method="post" action="sql_database_add.php">
                          <table width="100%" cellpadding="5" cellspacing="5">
                            <!-- BDP: page_message -->
                            <tr>
                              <td width="5">&nbsp;</td>
                              <td colspan="2" class="title"><span class="message">{MESSAGE}</span></td>
                            </tr>
                            <!-- EDP: page_message -->
                            <tr>
                              <td width="5">&nbsp;</td>
                              <td width="200" class="content2">{TR_DB_NAME}</td>
                              <td class="content"><input type="text" name="db_name" value="{DB_NAME}" style="width:170px" class="textinput" /></td>
                            </tr>
                            <tr>
                              <td width="5">&nbsp;</td>
                              <td width="200" class="content2"><!-- BDP: mysql_prefix_yes -->
                                  <input type="checkbox" name="use_dmn_id" {USE_DMN_ID}>
                                  <!-- EDP: mysql_prefix_yes -->
                                  <!-- BDP: mysql_prefix_no -->
                                  <input type="hidden" name="use_dmn_id" value="on">
                                  <!-- EDP: mysql_prefix_no -->
                                {TR_USE_DMN_ID}</td>
                              <td class="content"><!-- BDP: mysql_prefix_all -->
                                  <input type="radio" name="id_pos" value="start" {START_ID_POS_CHECKED}>
                                {TR_START_ID_POS}<br />
                                <input type="radio" name="id_pos" value="end" {END_ID_POS_CHECKED}>
                                {TR_END_ID_POS}
                                <!-- EDP: mysql_prefix_all -->
                                <!-- BDP: mysql_prefix_infront -->
                                <input type="hidden" name="id_pos" value="start" checked="checked">
                                {TR_START_ID_POS}
                                <!-- EDP: mysql_prefix_infront -->
                                <!-- BDP: mysql_prefix_behind -->
                                <input type="hidden" name="id_pos" value="end" checked="checked">
                                {TR_END_ID_POS}
                                <!-- EDP: mysql_prefix_behind -->
                              </td>
                            </tr>
                            <tr>
                              <td width="5">&nbsp;</td>
                              <td colspan="2"><input name="Submit" type="submit" class="button" value="  {TR_ADD}  "></td>
                            </tr>
                          </table>
                        <!-- end of content -->
                          <input type="hidden" name="uaction" value="add_db"></form></td>
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