<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_ADMIN_ADMIN_LOG_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/vhcs.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/vhcs.js"></script>
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
            <td height="62" align="left" background="{THEME_COLOR_PATH}/images/content/table_background.jpg" class="title"><img src="{THEME_COLOR_PATH}/images/content/table_icon_adminlog.jpg" width="85" height="62" align="absmiddle">{TR_ADMIN_LOG}</td>
            <td width="27" align="right" background="{THEME_COLOR_PATH}/images/content/table_background.jpg"><img src="{THEME_COLOR_PATH}/images/content/table_icon_close.jpg" width="27" height="62"></td>
          </tr>
          <tr>
            <td valign="top">
			<form name="admin_lod" method="post" action="admin_log.php">

			  <table width="100%" cellpadding="5" cellspacing="5">
                <!-- BDP: page_message -->
                <tr align="left">
                  <td width="20">&nbsp;</td>
                  <td colspan="2"><font color="#FF0000"><span class="title"><font color="#FF0000">{PAG_MESSAGE}</font></span> </font></td>
                  </tr>
                <!-- EDP: page_message -->
                <tr>
                  <td width="20">&nbsp;</td>
                  <td width="200" class="content3"><b>{TR_DATE}</b></td>
                  <td class="content3"><b>{TR_MESSAGE}</b></td>
                </tr>
                <!-- BDP: log_row -->
                <tr>
                  <td width="20">&nbsp;</td>
                  <td width="200" class="{ROW_CLASS}">{DATE}</td>
                  <td class="{ROW_CLASS}">{MESSAGE}</td>
                </tr>
                <!-- EDP: log_row -->
              </table>
			  <div align="right"><br>
                <!-- BDP: scroll_prev_gray --><img src="{THEME_COLOR_PATH}/images/icons/flip/prev_gray.gif" width="20" height="20" border="0"><!-- EDP: scroll_prev_gray --><!-- BDP: scroll_prev --><a href="admin_log.php?psi={PREV_PSI}"><img src="{THEME_COLOR_PATH}/images/icons/flip/prev.gif" width="20" height="20" border="0"></a><!-- EDP: scroll_prev --><!-- BDP: scroll_next_gray -->&nbsp;<img src="{THEME_COLOR_PATH}/images/icons/flip/next_gray.gif" width="20" height="20" border="0"><!-- EDP: scroll_next_gray --><!-- BDP: scroll_next -->&nbsp;<a href="admin_log.php?psi={NEXT_PSI}"><img src="{THEME_COLOR_PATH}/images/icons/flip/next.gif" width="20" height="20" border="0"></a><!-- EDP: scroll_next -->
            </div>
			  <!-- BDP: clear_log -->
              <table width="100%"  border="00" cellspacing="5" cellpadding="5">
                <tr>
                  <td width="80">&nbsp;</td>
                  <td>{TR_CLEAR_LOG_MESSAGE}
                  <select name="uaction_clear">
                  	<option value="0" selected>{TR_CLEAR_LOG_EVERYTHING}</option>
                  	<option value="2">{TR_CLEAR_LOG_LAST2}</option>
                  	<option value="4">{TR_CLEAR_LOG_LAST4}</option>
                  	<option value="12">{TR_CLEAR_LOG_LAST12}</option>
                  	<option value="26">{TR_CLEAR_LOG_LAST26}</option>
                  	<option value="52">{TR_CLEAR_LOG_LAST52}</option>
                  </select>
                            
                  <input name="Submit" type="submit" class="button" value="  {TR_CLEAR_LOG}  ">
                  </td>
                </tr>
              </table>
                            <!-- EDP: clear_log -->
<input type="hidden" name="uaction" value="clear_log">
           </form>
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
