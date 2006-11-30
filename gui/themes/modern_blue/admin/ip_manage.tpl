<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_ADMIN_IP_MANAGE_PAGE_TITLE}</title>
<link href="{THEME_COLOR_PATH}/css/vhcs.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/vhcs.js"></script>
<script language="JavaScript" type="text/JavaScript">
<!--
function action_delete(url) {
	if (!confirm("{TR_MESSAGE_DELETE}"))
		return false;

	location = url;
}

function MM_swapImgRestore() { //v3.0
  var i,x,a=document.MM_sr; for(i=0;a&&i<a.length&&(x=a[i])&&x.oSrc;i++) x.src=x.oSrc;
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
        <td width="215" valign="top" bgcolor="#F5F5F5">
		<table width="211" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td width="60" background="{THEME_COLOR_PATH}/images/menu/menu_top_left_bckgr.jpg"><img src="{THEME_COLOR_PATH}/images/icons/webtools_big.gif" width="60" height="62"></td>
            <td width="151" background="{THEME_COLOR_PATH}/images/menu/menu_top_bckgr.jpg" class="title">{TR_MENU_SYSTEM_TOOLS}</td>
          </tr>
        </table>
		<table width="205" border="0" align="center" cellpadding="0" cellspacing="0" bgcolor="#F5F5F5">
          <tr background="{THEME_COLOR_PATH}/images/line.jpg">
            <td colspan="3" background="{THEME_COLOR_PATH}/images/line.jpg"><img src="{THEME_COLOR_PATH}/images/line.jpg" width="2" height="7"><img src="{THEME_COLOR_PATH}/images/line.jpg" width="2" height="7"></td>
          </tr>
          <tr>
            <td colspan="3"><img src="{THEME_COLOR_PATH}/images/trans.gif" width="30" height="4"></td>
          </tr>
          <tr>
            <td width="28" background="{THEME_COLOR_PATH}/images/menu/button_background.jpg" class="menu"><a href="index.php" onMouseOver="MM_swapImage('general','','{THEME_COLOR_PATH}/images/icons/general_a.gif',1)" onMouseOut="MM_swapImgRestore()"><img src="{THEME_COLOR_PATH}/images/menu/pointer.jpg" width="28" height="36" border="0"></a></td>
            <td background="{THEME_COLOR_PATH}/images/menu/button_background.jpg" class="menu"><a href="index.php" class="menu" onMouseOver="MM_swapImage('general','','{THEME_COLOR_PATH}/images/icons/general_a.gif',1)" onMouseOut="MM_swapImgRestore()">{TR_MENU_GENERAL_INFORMATION}</a></td>
            <td width="36" align="right" background="{THEME_COLOR_PATH}/images/icons/icon_bcgr.gif" class="menu"><a href="index.php" onMouseOver="MM_swapImage('general','','{THEME_COLOR_PATH}/images/icons/general_a.gif',1)" onMouseOut="MM_swapImgRestore()"><img src="{THEME_COLOR_PATH}/images/icons/general.gif" name="general" width="36" height="36" border="0" id="general"></a></td>
          </tr>
          <tr>
            <td colspan="3"><img src="{THEME_COLOR_PATH}/images/trans.gif" width="30" height="4"></td>
          </tr>
          <!-- BDP: hosting_plans -->
  <td background="{THEME_COLOR_PATH}/images/menu/button_background.jpg" class="menu"><a href="hp.php" onMouseOver="MM_swapImage('hosting_plans','','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif',1)" onMouseOut="MM_swapImgRestore()"><img src="{THEME_COLOR_PATH}/images/menu/pointer.jpg" width="28" height="36" border="0"></a></td>
      <td background="{THEME_COLOR_PATH}/images/menu/button_background.jpg" class="menu"><a href="hp.php" class="menu"  onMouseOver="MM_swapImage('hosting_plans','','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif',1)" onMouseOut="MM_swapImgRestore()">{TR_MENU_HOSTING_PLANS}</a></td>
      <td align="right" background="{THEME_COLOR_PATH}/images/icons/icon_bcgr.gif" class="menu"><a href="hp.php" onMouseOver="MM_swapImage('hosting_plans','','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif',1)" onMouseOut="MM_swapImgRestore()"><img src="{THEME_COLOR_PATH}/images/icons/hosting_plans.gif" name="hosting_plans" width="36" height="36" border="0" id="hosting_plans"></a></td>
  </tr>
  <tr>
    <td colspan="3"><img src="{THEME_COLOR_PATH}/images/trans.gif" width="30" height="4"></td>
  </tr>
  <!-- EDP: hosting_plans -->
  <tr>
    <td background="{THEME_COLOR_PATH}/images/menu/button_background.jpg" class="menu"><a href="manage_users.php" onMouseOver="MM_swapImage('domains','','{THEME_COLOR_PATH}/images/icons/manage_users_a.gif',1)" onMouseOut="MM_swapImgRestore()"><img src="{THEME_COLOR_PATH}/images/menu/pointer.jpg" name="Image1" width="28" height="36" border="0" id="Image1"></a></td>
    <td background="{THEME_COLOR_PATH}/images/menu/button_background.jpg" class="menu"><a href="manage_users.php" class="menu"  onMouseOver="MM_swapImage('domains','','{THEME_COLOR_PATH}/images/icons/manage_users_a.gif',1)" onMouseOut="MM_swapImgRestore()">{TR_MENU_MANAGE_USERS}</a></td>
    <td align="right" background="{THEME_COLOR_PATH}/images/icons/icon_bcgr.gif" class="menu"><a href="manage_users.php" onMouseOver="MM_swapImage('domains','','{THEME_COLOR_PATH}/images/icons/manage_users_a.gif',1)" onMouseOut="MM_swapImgRestore()"><img src="{THEME_COLOR_PATH}/images/icons/manage_users.gif" name="domains" width="36" height="36" border="0" id="domains"></a></td>
  </tr>
  <tr>
    <td colspan="3"><img src="{THEME_COLOR_PATH}/images/trans.gif" width="30" height="4"></td>
  </tr>
  <tr>
    <td width="28" background="{THEME_COLOR_PATH}/images/menu/button_background.jpg" class="menu"><a href="sysinfo.php" onMouseOver="MM_swapImage('webtools','','{THEME_COLOR_PATH}/images/icons/webtools_a.gif',1)" onMouseOut="MM_swapImgRestore()"><img src="{THEME_COLOR_PATH}/images/menu/open_pointer.jpg" width="28" height="36" border="0"></a></td>
    <td background="{THEME_COLOR_PATH}/images/menu/open_background.gif" class="menu"><a href="sysinfo.php" class="menu_active" onMouseOver="MM_swapImage('webtools','','{THEME_COLOR_PATH}/images/icons/webtools_a.gif',1)" onMouseOut="MM_swapImgRestore()">{TR_MENU_SYSTEM_TOOLS}</a></td>
    <td width="36" align="right" background="{THEME_COLOR_PATH}/images/menu/open_icon_bcgr.jpg" class="menu"><a href="sysinfo.php" onMouseOver="MM_swapImage('webtools','','{THEME_COLOR_PATH}/images/icons/webtools_a.gif',1)" onMouseOut="MM_swapImgRestore()"><img src="{THEME_COLOR_PATH}/images/icons/webtools_a.gif" name="webtools" width="36" height="36" border="0" id="webtools"></a></td>
  </tr>
  <tr background="{THEME_COLOR_PATH}/images/menu/open_background.jpg">
    <td colspan="3" class="menu" background="{THEME_COLOR_PATH}/images/menu/open_background.jpg"><table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td width="5" rowspan="16" background="{THEME_COLOR_PATH}/images/menu/open_background_left.gif"><img src="{THEME_COLOR_PATH}/images/trans.gif" width="12" height="1"></td>
          <td><img src="{THEME_COLOR_PATH}/images/icons/document.gif" width="12" height="15"></td>
          <td><a href="sysinfo.php" class="submenu">{TR_MENU_OVERVIEW}</a></td>
          <td width="5" rowspan="14" background="{THEME_COLOR_PATH}/images/menu/open_background_right.gif"><img src="{THEME_COLOR_PATH}/images/trans.gif" width="5" height="1"></td>
        </tr>
        <tr>
          <td colspan="2"><img src="{THEME_COLOR_PATH}/images/trans.gif" width="30" height="4"></td>
        </tr>
        <tr>
          <td><img src="{THEME_COLOR_PATH}/images/icons/document.gif" width="12" height="15"></td>
          <td><a href="ip_manage.php" class="submenu">{TR_MENU_MANAGE_IPS}</a></td>
        </tr>
              <tr>
				<td colspan="2"><img src="{THEME_COLOR_PATH}/images/trans.gif" width="30" height="4"></td>
		      </tr>
			  <tr>
          		<td><img src="{THEME_COLOR_PATH}/images/icons/document.gif" width="12" height="15"></td>
           		<td><a href="rootkit_log.php" class="submenu">{TR_MENU_ROOTKIT_LOG}</a></td>
              </tr>        
		<tr>
          <td colspan="2"><img src="{THEME_COLOR_PATH}/images/trans.gif" width="30" height="4"></td>
        </tr>
        <tr>
          <td width="15"><img src="{THEME_COLOR_PATH}/images/icons/document.gif" width="12" height="15"></td>
          <td><a href="server_traffic_settings.php" class="submenu">{TR_MENU_SERVER_TRAFFIC_SETTINGS}</a></td>
        </tr>
        <tr>
          <td colspan="2"><img src="{THEME_COLOR_PATH}/images/trans.gif" width="30" height="4"></td>
        </tr>
        <tr>
          <td><img src="{THEME_COLOR_PATH}/images/icons/document.gif" width="12" height="15"></td>
          <td><a href="server_status.php" class="submenu">{TR_MENU_SERVER_STATUS}</a></td>
        </tr>
        <tr>
          <td colspan="2"><img src="{THEME_COLOR_PATH}/images/trans.gif" width="30" height="4"></td>
        </tr>
        <tr>
          <td><img src="{THEME_COLOR_PATH}/images/icons/document.gif" width="12" height="15"></td>
          <td><a href="vhcs_updates.php" class="submenu">{TR_MENU_VHCS_UPDATE}</a></td>
        </tr>
        <tr>
          <td colspan="2"><img src="{THEME_COLOR_PATH}/images/trans.gif" width="30" height="4"></td>
        </tr>
        <tr>
          <td><img src="{THEME_COLOR_PATH}/images/icons/document.gif" width="12" height="15"></td>
          <td><a href="vhcs_debugger.php" class="submenu">{TR_MENU_VHCS_DEBUGGER}</a></td>
        </tr>
        <tr>
          <td colspan="2"><img src="{THEME_COLOR_PATH}/images/trans.gif" width="30" height="4"></td>
        </tr>
        <tr>
          <td><img src="{THEME_COLOR_PATH}/images/icons/document.gif" width="12" height="15"></td>
          <td><a href="admin_log.php" class="submenu">{TR_MENU_ADMIN_LOG}</a></td>
        </tr>
        <tr>
          <td colspan="2"><img src="{THEME_COLOR_PATH}/images/trans.gif" width="30" height="4"></td>
        </tr>
    </table></td>
  </tr>
  <tr>
    <td class="menu"><img src="{THEME_COLOR_PATH}/images/menu/open_down_left.gif" width="28" height="7"></td>
    <td background="{THEME_COLOR_PATH}/images/menu/open_down.gif" class="menu"><img src="{THEME_COLOR_PATH}/images/menu/open_down.gif" width="4" height="7"></td>
    <td align="right" class="menu"><img src="{THEME_COLOR_PATH}/images/menu/open_down_right.gif" width="36" height="7"></td>
  </tr>
  <tr>
    <td colspan="3"><img src="{THEME_COLOR_PATH}/images/trans.gif" width="30" height="4"></td>
  </tr>
  <tr>
    <td background="{THEME_COLOR_PATH}/images/menu/button_background.jpg" class="menu"><a href="server_statistic.php" onMouseOver="MM_swapImage('statistics','','{THEME_COLOR_PATH}/images/icons/statistics_a.gif',1)" onMouseOut="MM_swapImgRestore()"><img src="{THEME_COLOR_PATH}/images/menu/pointer.jpg" width="28" height="36" border="0"></a></td>
    <td background="{THEME_COLOR_PATH}/images/menu/button_background.jpg" class="menu"><a href="server_statistic.php" class="menu" onMouseOver="MM_swapImage('statistics','','{THEME_COLOR_PATH}/images/icons/statistics_a.gif',1)" onMouseOut="MM_swapImgRestore()">{TR_MENU_STATISTICS}</a></td>
    <td align="right" background="{THEME_COLOR_PATH}/images/icons/icon_bcgr.gif" class="menu"><a href="server_statistic.php" onMouseOver="MM_swapImage('statistics','','{THEME_COLOR_PATH}/images/icons/statistics_a.gif',1)" onMouseOut="MM_swapImgRestore()"><img src="{THEME_COLOR_PATH}/images/icons/statistics.gif" name="statistics" width="36" height="36" border="0" id="statistics"></a></td>
  </tr>
  <!-- BDP: support_system -->
  <tr>
    <td colspan="3"><img src="{THEME_COLOR_PATH}/images/trans.gif" width="30" height="4"></td>
  </tr>
  <tr>
    <td background="{THEME_COLOR_PATH}/images/menu/button_background.jpg" class="menu"><a href="{SUPPORT_SYSTEM_PATH}" target="{SUPPORT_SYSTEM_TARGET}"  onMouseOver="MM_swapImage('support','','{THEME_COLOR_PATH}/images/icons/support_a.gif',1)" onMouseOut="MM_swapImgRestore()"><img src="{THEME_COLOR_PATH}/images/menu/pointer.jpg" width="28" height="36" border="0"></a></td>
    <td background="{THEME_COLOR_PATH}/images/menu/button_background.jpg" class="menu"><a href="{SUPPORT_SYSTEM_PATH}" target="{SUPPORT_SYSTEM_TARGET}"  class="menu" onMouseOver="MM_swapImage('support','','{THEME_COLOR_PATH}/images/icons/support_a.gif',1)" onMouseOut="MM_swapImgRestore()">{TR_MENU_SUPPORT_SYSTEM}</a></td>
    <td align="right" background="{THEME_COLOR_PATH}/images/icons/icon_bcgr.gif" class="menu"><a href="{SUPPORT_SYSTEM_PATH}" target="{SUPPORT_SYSTEM_TARGET}"  onMouseOver="MM_swapImage('support','','{THEME_COLOR_PATH}/images/icons/support_a.gif',1)" onMouseOut="MM_swapImgRestore()"><img src="{THEME_COLOR_PATH}/images/icons/support.gif" name="support" width="36" height="36" border="0" id="support"></a></td>
  </tr>
  <!-- EDP: support_system -->
  <tr>
    <td colspan="3"><img src="{THEME_COLOR_PATH}/images/trans.gif" width="30" height="4"></td>
  </tr>
  <tr>
    <td background="{THEME_COLOR_PATH}/images/menu/button_background.jpg" class="menu"><a href="../index.php" onMouseOver="MM_swapImage('logout','','{THEME_COLOR_PATH}/images/icons/logout_a.gif',1)" onMouseOut="MM_swapImgRestore()"><img src="{THEME_COLOR_PATH}/images/menu/pointer.jpg" width="28" height="36" border="0"></a></td>
    <td background="{THEME_COLOR_PATH}/images/menu/button_background.jpg" class="menu"><a href="../index.php" class="menu" onMouseOver="MM_swapImage('logout','','{THEME_COLOR_PATH}/images/icons/logout_a.gif',1)" onMouseOut="MM_swapImgRestore()">{TR_MENU_LOGOUT}</a></td>
    <td align="right" background="{THEME_COLOR_PATH}/images/icons/icon_bcgr.gif" class="menu"><a href="../index.php" onMouseOver="MM_swapImage('logout','','{THEME_COLOR_PATH}/images/icons/logout_a.gif',1)" onMouseOut="MM_swapImgRestore()"><img src="{THEME_COLOR_PATH}/images/icons/logout.gif" name="logout" width="36" height="36" border="0" id="logout"></a></td>
  </tr>
        </table></td>
        <td valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td height="62" align="left" background="{THEME_COLOR_PATH}/images/content/table_background.jpg" class="title"><img src="{THEME_COLOR_PATH}/images/content/table_icon_ip.jpg" width="85" height="62" align="absmiddle">{MANAGE_IPS}</td>
            <td width="27" align="right" background="{THEME_COLOR_PATH}/images/content/table_background.jpg"><img src="{THEME_COLOR_PATH}/images/content/table_icon_close.jpg" width="27" height="62"></td>
          </tr>
          <tr>
            <td valign="top">
			  <table width="100%" cellpadding="5" cellspacing="5">
                <!-- BDP: page_message -->
                <tr>
                  <td width="20">&nbsp;</td>
                  <td colspan="4"><font color="#FF0000"><span class="title"><font color="#FF0000">{MESSAGE}</font></span> </font></td>
                  </tr>
                <!-- EDP: page_message -->
                <tr>
                  <td width="20">&nbsp;</td>
                  <td colspan="4" class="content3"><b>{TR_AVAILABLE_IPS}</b></td>
                  </tr>
                <tr align="center">
                  <td width="20">&nbsp;</td>
                  <td align="left" class="content3"><strong>{TR_IP}</strong></td>
                  <td class="content3"><strong>{TR_DOMAIN}</strong></td>
                  <td class="content3"><strong>{TR_ALIAS}</strong></td>
                  <td width="103" class="content3"><strong>{TR_ACTION}</strong></td>
                </tr>
                <!-- BDP: ip_row -->
                <tr>
                  <td width="20" nowrap>&nbsp;</td>
                  <td align="left" nowrap class="{IP_CLASS}">{IP}</td>
                  <td align="center" nowrap class="{IP_CLASS}">{DOMAIN}</td>
                  <td class="{IP_CLASS}" nowrap align="center">{ALIAS}</td>
                  <td class="{IP_CLASS}" nowrap align="center"><img src="{THEME_COLOR_PATH}/images/icons/delete.gif" width="16" height="16" border="0" align="absmiddle"> <a href="#" onClick="action_delete('delete_ip.php?delete_id={DELETE_ID}')" class="link">{TR_UNINSTALL}</a></td>
                </tr>
                <!-- EDP: ip_row -->
              </table>
			  <br>
              <form name="add_new_ip_frm" method="post" action="ip_manage.php">
                <table width="100%" cellpadding="5" cellspacing="5">
                  <tr>
                    <td width="20">&nbsp;</td>
                    <td colspan="2" class="content3"><b>{TR_ADD_NEW_IP}</b></td>
                    </tr>
                  <tr>
                    <td width="20" nowrap>&nbsp;</td>
                    <td width="200" class="content2" nowrap>{TR_IP}</td>
                    <td nowrap class="content"><input name="ip_number_1" type="text" class="textinput" style="width:31px" value="{VALUE_IP1}" maxlength="3">
        .
          <input name="ip_number_2" type="text" class="textinput" style="width:31px" value="{VALUE_IP2}" maxlength="3">
        .
        <input name="ip_number_3" type="text" class="textinput" style="width:31px" value="{VALUE_IP3}" maxlength="3">
        .
        <input name="ip_number_4" type="text" class="textinput" style="width:31px" value="{VALUE_IP4}" maxlength="3">
                    </td>
                  </tr>
                  <tr>
                    <td width="20" nowrap>&nbsp;</td>
                    <td width="200" class="content2" nowrap>{TR_DOMAIN}</td>
                    <td nowrap class="content"><input type="text" name="domain" value="{VALUE_DOMAIN}" style="width:180px" class="textinput">
                    </td>
                  </tr>
                  <tr>
                    <td width="20" nowrap>&nbsp;</td>
                    <td width="200" class="content2" nowrap>{TR_ALIAS}</td>
                    <td nowrap class="content"><input type="text" name="alias" value="{VALUE_ALIAS}" style="width:180px" class="textinput">
                    </td>
                  </tr>
                  <tr>
                    <td nowrap>&nbsp;</td>
                    <td colspan="2" nowrap ><input name="Submit" type="submit" class="button" value="  {TR_ADD}  "></td>
                    </tr>
                </table>
                <input type="hidden" name="uaction" value="add_ip">
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
