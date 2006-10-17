<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_ADMIN_SETTINGS_PAGE_TITLE}</title>
<meta name="robots" content="noindex">
<meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/vhcs.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/vhcs.js"></script>
<script language="JavaScript" type="text/JavaScript">
<!--
function action_delete(url) {
	if (!confirm("{TR_MESSAGE_DELETE}"))
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
            <td height="62" align="left" background="{THEME_COLOR_PATH}/images/content/table_background.jpg" class="title"><img src="{THEME_COLOR_PATH}/images/content/table_icon_serverstatus.jpg" width="85" height="62" align="absmiddle">{TR_SERVERPORTS}</td>
            <td width="27" align="right" background="{THEME_COLOR_PATH}/images/content/table_background.jpg"><img src="{THEME_COLOR_PATH}/images/content/table_icon_close.jpg" width="27" height="62"></td>
          </tr>
          <tr>
            <td valign="top"><form name="frmsettings" method="post" action="settings_ports.php">
                <table width="100%" cellpadding="5" cellspacing="5">
                  <!-- BDP: page_message -->
                  <tr>
                    <td width="20">&nbsp;</td>
                    <td colspan="2" class=title><font color="#FF0000">{MESSAGE}</font></td>
                  </tr>
                  <!-- EDP: page_message -->
                  <tr>
                    <td width="20">&nbsp;</td>
                    <td colspan="6" class="content3"><strong>{TR_SERVICES}</strong></td>
                  </tr>
                  <tr>
                    <td width="20">&nbsp;</td>
                    <td width="230" class="content3"><strong>{TR_SERVICE}</strong></td>
                    <td class="content3"><strong>{TR_PORT}</strong></td>
                    <td class="content3"><strong>{TR_PROTOCOL}</strong></td>
                    <td class="content3"><strong>{TR_SHOW}</strong></td>
                    <td class="content3"><strong>{TR_ACTION}</strong></td>
                  </tr>
                  <!-- BDP: service_ports -->
                  <tr>
                    <td width="20">&nbsp;</td>
                    <td class="{CLASS}">{SERVICE}<input name="var_name[]" type="hidden" id="var_name" value="{VAR_NAME}" /><input name="custom[]" type="hidden" id="custom" value="{CUSTOM}" /></td>
                    <td class="{CLASS}"><input name="port[]" type="text" class="textinput" id="port" style="width:50px" value="{PORT}" maxlength="5" {PORT_READONLY}/></td>
                    <td class="{CLASS}">
                     <select name="port_type[]" id="port_type" {PROTOCOL_READONLY}>
                      <option value="udp" {SELECTED_UDP}>{TR_UDP}</option>
                      <option value="tcp" {SELECTED_TCP}>{TR_TCP}</option>
                     </select>
                    </td>
                    <td class="{CLASS}">
                     <select name="show_val[]" id="show_val">
                      <option value="1" {SELECTED_ON}>{TR_ENABLED}</option>
                      <option value="0" {SELECTED_OFF}>{TR_DISABLED}</option>
                     </select>
                    </td>
                    <td class="{CLASS}" width="100" nowrap="nowrap">
                     <img src="{THEME_COLOR_PATH}/images/icons/delete.gif" width="16" height="16" border="0" align="absmiddle">
                     <!-- BDP: port_delete_show -->
                     {TR_DELETE}
                     <!-- EDP: port_delete_show -->
                     <!-- BDP: port_delete_link -->
                      <a href="#" onClick="action_delete('{URL_DELETE}')" class="link">{TR_DELETE}</a>
                     <!-- EDP: port_delete_link -->
                    </td>
                  </tr>
                  <!-- EDP: service_ports -->
                  <tr>
                   <td width="20">&nbsp;</td>
                   <td colspan="5">{TR_ADD}:</td>
                  </tr>
                  <tr>
                    <td width="20">&nbsp;</td>
                    <td class="{CLASS}"><input name="name_new" type="text" class="textinput" id="service" value="" maxlength="25"/></td>
                    <td class="{CLASS}"><input name="port_new" type="text" class="textinput" id="port" style="width:50px" value="" maxlength="6" /></td>
                    <td class="{CLASS}">
                     <select name="port_type_new" id="port_type">
                      <option value="udp">{TR_UDP}</option>
                      <option value="tcp" selected="selected">{TR_TCP}</option>
                     </select>
                    </td>
                    <td class="{CLASS}">
                     <select name="show_val_new" id="show_val">
                      <option value="1" selected="selected">{TR_ENABLED}</option>
                      <option value="0">{TR_DISABLED}</option>
                     </select>
                    </td>
                    <td class="{CLASS}" width="100" nowrap="nowrap">&nbsp;</td>
                  </tr>
                  <tr>
                   <td>&nbsp;</td>
                   <td colspan="5"><input type="hidden" name="uaction" value="apply">
                    <input name="Submit" type="submit" class="button" value="{TR_APPLY_CHANGES}">
                   </td>
                  </tr>
                 </table>
                </form>
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