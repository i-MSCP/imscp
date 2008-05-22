<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_ADMIN_SETTINGS_PAGE_TITLE}</title>
<meta name="robots" content="noindex">
<meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
<script language="JavaScript" type="text/javascript">
<!--
function action_delete(url) {
	if (!confirm("{TR_MESSAGE_DELETE}"))
		return false;

	location = url;
}

function enable_for_post() {
    
	for (var i = 0; i < document.frmsettings.length; i++) {
		for (var a = 0; a < document.frmsettings.elements[i].length; a++) {
			if (document.frmsettings.elements[i].name == "port_type[]") {
				document.frmsettings.elements[i].disabled = false;
			}
		}
	}
    
return true;

}
//-->
</script>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
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
				<tr height="*">
				  <td colspan="3"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td align="left"><table width="100%" cellpadding="5" cellspacing="5">
                          <tr>
                            <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_serverstatus.png" width="25" height="25"></td>
                            <td colspan="2" class="title">{TR_SERVERPORTS}</td>
                          </tr>
                      </table></td>
                      <td width="27" align="right">&nbsp;</td>
                    </tr>
                    <tr>
                      <td valign="top"><form name="frmsettings" method="post" action="settings_ports.php" onSubmit="return enable_for_post();">
                          <table width="100%" cellpadding="5" cellspacing="5">
                            <!-- BDP: page_message -->
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td colspan="2" class="title"><font color="#FF0000">{MESSAGE}</font></td>
                            </tr>
                            <!-- EDP: page_message -->
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td colspan="6" class="content3"><strong>{TR_SERVICES}</strong></td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td width="230" class="content3"><strong>{TR_SERVICE}</strong></td>
                              <td class="content3"><strong>{TR_PORT}</strong></td>
                              <td class="content3"><strong>{TR_PROTOCOL}</strong></td>
                              <td class="content3"><strong>{TR_SHOW}</strong></td>
                              <td class="content3"><strong>{TR_ACTION}</strong></td>
                            </tr>
                            <!-- BDP: service_ports -->
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td class="{CLASS}">{SERVICE}
                                <input name="var_name[]" type="hidden" id="var_name" value="{VAR_NAME}" />
                                  <input name="custom[]" type="hidden" id="custom" value="{CUSTOM}" /></td>
                              <td class="{CLASS}"><input name="port[]" type="text" class="textinput" id="port" style="width:50px" value="{PORT}" maxlength="5" {PORT_READONLY}/></td>
                              <td class="{CLASS}"><select name="port_type[]" id="port_type" {PROTOCOL_READONLY}>
                                  <option value="udp" {SELECTED_UDP}>{TR_UDP}</option>
                                  <option value="tcp" {SELECTED_TCP}>{TR_TCP}</option>
                                </select>
                              </td>
                              <td class="{CLASS}"><select name="show_val[]" id="show_val">
                                  <option value="1" {SELECTED_ON}>{TR_ENABLED}</option>
                                  <option value="0" {SELECTED_OFF}>{TR_DISABLED}</option>
                                </select>
                              </td>
                              <td class="{CLASS}" width="100" nowrap="nowrap">
                                <!-- BDP: port_delete_show -->
                                {TR_DELETE}
                                <!-- EDP: port_delete_show -->
                                <!-- BDP: port_delete_link -->
                                <img src="{THEME_COLOR_PATH}/images/icons/delete.png" width="16" height="16" border="0" align="absmiddle">
                                <a href="#" onclick="action_delete('{URL_DELETE}')" class="link">{TR_DELETE}</a>
                                <!-- EDP: port_delete_link -->
                              </td>
                            </tr>
                            <!-- EDP: service_ports -->
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td colspan="5">{TR_ADD}:</td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td class="{CLASS}"><input name="name_new" type="text" class="textinput" id="service" value="" maxlength="25"/></td>
                              <td class="{CLASS}"><input name="port_new" type="text" class="textinput" id="port" style="width:50px" value="" maxlength="6" /></td>
                              <td class="{CLASS}"><select name="port_type_new" id="port_type">
                                  <option value="udp">{TR_UDP}</option>
                                  <option value="tcp" selected="selected">{TR_TCP}</option>
                                </select>
                              </td>
                              <td class="{CLASS}"><select name="show_val_new" id="show_val">
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
                      </form></td>
                      <td>&nbsp;</td>
                    </tr>
                    <tr>
                      <td>&nbsp;</td>
                      <td>&nbsp;</td>
                    </tr>
                  </table></td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</body>
</html>
