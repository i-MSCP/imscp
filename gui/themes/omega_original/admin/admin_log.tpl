<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_ADMIN_ADMIN_LOG_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="height:100%;padding:0;margin:0;">
<tr>
<td align="left" valign="top" style="vertical-align: top; width: 195px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_left.jpg" border="0" alt="ispCP Logogram" /></td>
<td style="height: 56px; width:100%; background-image: url({THEME_COLOR_PATH}/images/top/top_bg.jpg)"><img src="{THEME_COLOR_PATH}/images/top/top_left_bg.jpg" border="0" alt="" /></td>
<td style="width: 73px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_right.jpg" border="0" alt="" /></td>
</tr>
	<tr>
		<td style="width: 195px; vertical-align: top;">{MENU}</td>
	    <td colspan="2" style="vertical-align: top;"><table style="width: 100%; padding:0;margin:0;" cellspacing="0">
				<tr style="height:95px;">
				  <td style="padding-left:30px; width: 100%; background-image: url({THEME_COLOR_PATH}/images/top/middle_bg.jpg);">{MAIN_MENU}</td>
					<td style="padding:0;margin:0;text-align: right; width: 73px;vertical-align: top;"><img src="{THEME_COLOR_PATH}/images/top/middle_right.jpg" border="0" alt="" /></td>
				</tr>
				<tr>
				  <td colspan="3"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td align="left"><table width="100%" cellpadding="5" cellspacing="5">
                          <tr>
                            <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_adminlog.png" width="25" height="25" /></td>
                            <td colspan="2" class="title">{TR_ADMIN_LOG}</td>
                          </tr>
                      </table></td>
                      <td width="27" align="right">&nbsp;</td>
                    </tr>
                    <tr>
                      <td valign="top"><form name="admin_lod" method="post" action="admin_log.php">
                          <table width="100%" cellpadding="5" cellspacing="5">
                            <!-- BDP: page_message -->
                            <tr align="left">
                              <td width="25">&nbsp;</td>
                              <td colspan="2" class="title"><span class="message">{PAG_MESSAGE}</span></td>
                            </tr>
                            <!-- EDP: page_message -->
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td width="200" class="content3"><b>{TR_DATE}</b></td>
                              <td class="content3"><b>{TR_MESSAGE}</b></td>
                            </tr>
                            <!-- BDP: log_row -->
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td width="200" class="{ROW_CLASS}">{DATE}</td>
                              <td class="{ROW_CLASS}">{MESSAGE}</td>
                            </tr>
                            <!-- EDP: log_row -->
                          </table>
                        <div align="right"><br />
                              <!-- BDP: scroll_prev_gray -->
                              <img src="{THEME_COLOR_PATH}/images/icons/flip/prev_gray.gif" width="20" height="20" border="0" alt="" />
                              <!-- EDP: scroll_prev_gray -->
                              <!-- BDP: scroll_prev -->
                              <a href="admin_log.php?psi={PREV_PSI}"><img src="{THEME_COLOR_PATH}/images/icons/flip/prev.gif" width="20" height="20" border="0" alt="" /></a>
                              <!-- EDP: scroll_prev -->
                              <!-- BDP: scroll_next_gray -->
                          &nbsp;<img src="{THEME_COLOR_PATH}/images/icons/flip/next_gray.gif" width="20" height="20" border="0" alt="" />
                          <!-- EDP: scroll_next_gray -->
                          <!-- BDP: scroll_next -->
                          &nbsp;<a href="admin_log.php?psi={NEXT_PSI}"><img src="{THEME_COLOR_PATH}/images/icons/flip/next.gif" width="20" height="20" border="0" alt="" /></a>
                          <!-- EDP: scroll_next -->
                        </div>
                        <!-- BDP: clear_log -->
                          <table width="100%" border="0" cellspacing="5" cellpadding="5">
                            <tr>
                              <td width="80">&nbsp;</td>
                              <td><label for="uaction_clear">{TR_CLEAR_LOG_MESSAGE}</label>
                                <select name="uaction_clear" id="uaction_clear">
                                    <option value="0" selected="selected">{TR_CLEAR_LOG_EVERYTHING}</option>
                                    <option value="2">{TR_CLEAR_LOG_LAST2}</option>
                                    <option value="4">{TR_CLEAR_LOG_LAST4}</option>
                                    <option value="12">{TR_CLEAR_LOG_LAST12}</option>
                                    <option value="26">{TR_CLEAR_LOG_LAST26}</option>
                                    <option value="52">{TR_CLEAR_LOG_LAST52}</option>
                                </select>
                                <input name="Submit" type="submit" class="button" value="  {TR_CLEAR_LOG}  " /></td>
                            </tr>
                          </table>
                        <!-- EDP: clear_log -->
                          <input type="hidden" name="uaction" value="clear_log" />
                      </form></td>
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
