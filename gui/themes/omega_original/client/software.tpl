<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_CLIENT_SOFTWARE_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="{THEME_COLOR_PATH}/css/imscp.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/jquery.js"></script>
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/jquery.imscpTooltips.js"></script>
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/imscp.js"></script>
<script language="JavaScript" type="text/JavaScript">
/*<![CDATA[*/
	$(document).ready(function(){
		// Tooltips - begin
		$('a.swtooltip').sw_iMSCPtooltips('a.title');
		// Tooltips - end
	});
	function action_delete(url) {
		if (!confirm("{TR_MESSAGE_DELETE}"))
		return false;
		location = url;
	}
	function action_install(url) {
		if (!confirm("{TR_MESSAGE_INSTALL}"))
		return false;
		location = url;
	}
	function action_res_delete(url) {
		if (!confirm("{TR_RES_MESSAGE_DELETE}"))
		return false;
		location = url;
	}
/*]]>*/
</script>
</head>
<body onLoad="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.png','{THEME_COLOR_PATH}/images/icons/domains_a.png','{THEME_COLOR_PATH}/images/icons/ftp_a.png','{THEME_COLOR_PATH}/images/icons/general_a.png','{THEME_COLOR_PATH}/images/icons/email_a.png','{THEME_COLOR_PATH}/images/icons/webtools_a.png','{THEME_COLOR_PATH}/images/icons/statistics_a.png','{THEME_COLOR_PATH}/images/icons/support_a.png')">
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="height:100%;padding:0;margin:0 auto;">
<!-- BDP: logged_from -->
<tr>
 <td colspan="3" height="20" nowrap="nowrap" class="backButton">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.png" width="16" height="16" border="0" style="vertical-align:middle" alt="" /></a> {YOU_ARE_LOGGED_AS}</td>
</tr>
<!-- EDP: logged_from -->
<tr>
<td align="left" valign="top" style="vertical-align: top; width: 195px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_left.jpg" width="195" height="56" border="0" alt="i-MSCP Logogram" /></td>
<td style="height: 56px; width:100%; background-color: #0f0f0f"><img src="{THEME_COLOR_PATH}/images/top/top_left_bg.jpg" width="582" height="56" border="0" alt="" /></td>
<td style="width: 73px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_right.jpg" width="73" height="56" border="0" alt="" /></td>
</tr>
	<tr>
		<td style="width: 195px; vertical-align: top;">{MENU}</td>
	    <td colspan="2" style="vertical-align: top;"><table style="width: 100%; padding:0;margin:0;" cellspacing="0">
          <tr style="height:95px;">
            <td style="padding-left:30px; width: 100%; background-image: url({THEME_COLOR_PATH}/images/top/middle_bg.jpg);">{MAIN_MENU}</td>
            <td style="padding:0;margin:0;text-align: right; width: 73px;vertical-align: top;"><img src="{THEME_COLOR_PATH}/images/top/middle_right.jpg" width="73" height="95" border="0" alt="" /></td>
          </tr>
          <tr>
            <td colspan=3>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td align="left">
<table width="100%" cellpadding="5" cellspacing="5">
	<tr>
		<td width="25"><img src="{THEME_COLOR_PATH}/images/icons/cd_big.png" width="25" height="25" /></td>
		<td colspan="2" class="title">{TR_INSTALL_SOFTWARE}</td>
	</tr>
</table>			
			</td>
            <td width="27" align="right">&nbsp;</td>
          </tr>
          <tr>
            <td><table width="100%"  border="0" cellspacing="0" cellpadding="0">
                <tr>
                  <td width="25">&nbsp;</td>
                  <td valign="top"><table width="100%" cellspacing="7">
                    <!-- BDP: page_message -->
					<tr>
                      <td colspan="7" nowrap class="title"><font color="#FF0000">{MESSAGE}</font></td>
                      </tr>
                    <!-- EDP: page_message -->
                    <tr>
                      <td nowrap class="content3"><div style="float:left"><b>{TR_SOFTWARE}</b></div><div style="float:right"><a href="{TR_SOFTWARE_ASC}"><img src="{THEME_COLOR_PATH}/images/icons/asc.gif" width="16" height="16" border="0" /></a><a href="{TR_SOFTWARE_DESC}"><img src="{THEME_COLOR_PATH}/images/icons/desc.gif" width="16" height="16" border="0" /></a></div></td>
					  <td nowrap class="content3" align="center" width="70"><b>{TR_VERSION}</b></td>
					  <td nowrap class="content3" align="center" width="100"><div style="float:left"><b>{TR_LANGUAGE}</b></div><div style="float:right"><a href="{TR_LANGUAGE_ASC}"><img src="{THEME_COLOR_PATH}/images/icons/asc.gif" width="16" height="16" border="0" /></a><a href="{TR_LANGUAGE_DESC}"><img src="{THEME_COLOR_PATH}/images/icons/desc.gif" width="16" height="16" border="0" /></a></div></td>
                      <td nowrap class="content3" align="center" width="70"><div style="float:left"><b>{TR_TYPE}</b></div><div style="float:right"><a href="{TR_TYPE_ASC}"><img src="{THEME_COLOR_PATH}/images/icons/asc.gif" width="16" height="16" border="0" /></a><a href="{TR_TYPE_DESC}"><img src="{THEME_COLOR_PATH}/images/icons/desc.gif" width="16" height="16" border="0" /></a></div></td>
					  <td nowrap class="content3" align="center" width="90"><div style="float:left"><b>{TR_NEED_DATABASE}</b></div><div style="float:right"><a href="{TR_NEED_DATABASE_ASC}"><img src="{THEME_COLOR_PATH}/images/icons/asc.gif" width="16" height="16" border="0" /></a><a href="{TR_NEED_DATABASE_DESC}"><img src="{THEME_COLOR_PATH}/images/icons/desc.gif" width="16" height="16" border="0" /></a></div></td>
                      <td nowrap class="content3" align="center" width="90"><b>{TR_STATUS}</b></td>
                      <td nowrap class="content3" align="center" width="150"><b>{TR_ACTION}</b></td>
                    </tr>
					<!-- BDP: t_software_support -->
                    <!-- BDP: software_item -->
                    <tr>
                      <td nowrap class="{ITEM_CLASS}"><img src="{THEME_COLOR_PATH}/images/icons/cd.png" width="14" height="14" align="middle" /> <a href="{VIEW_SOFTWARE_SCRIPT}" class="swtooltip" title="{SOFTWARE_DESCRIPTION}">{SOFTWARE_NAME}</a></td>
                      <td nowrap class="{ITEM_CLASS}" align="center">{SOFTWARE_VERSION}</td>
					  <td nowrap class="{ITEM_CLASS}" align="center">{SOFTWARE_LANGUAGE}</td>
					  <td nowrap class="{ITEM_CLASS}" align="center">{SOFTWARE_TYPE}</td>
					  <td nowrap class="{ITEM_CLASS}" align="center">{SOFTWARE_NEED_DATABASE}</td>
                      <td nowrap class="{ITEM_CLASS}" align="center">{SOFTWARE_STATUS}</td>
                      <td nowrap class="{ITEM_CLASS}" align="center"><img src="{THEME_COLOR_PATH}/images/icons/{SOFTWARE_ICON}.png" width="16" height="16" border="0" align="middle" /> <a href="#" class="link" <!-- BDP: software_action_delete -->  onClick="return action_delete('{SOFTWARE_ACTION_SCRIPT}')" <!-- EDP: software_action_delete --><!-- BDP: software_action_install -->  onClick="return action_install('{SOFTWARE_ACTION_SCRIPT}')" <!-- EDP: software_action_install --> >{SOFTWARE_ACTION}</a>
                      </td>
                    </tr>
                    <!-- EDP: software_item -->
					<!-- EDP: t_software_support -->
                    <!-- BDP: no_software_support -->
                    <tr>
                      <td nowrap colspan="7" class="content">{NO_SOFTWARE_AVAIL}</td>
                    </tr>
                    <!-- EDP: no_software_support -->
                    <!-- BDP: software_total -->
                    <tr>
                      <td colspan="7" align="right" nowrap class="content3">{TR_SOFTWARE_AVAILABLE}:&nbsp;<b>{TOTAL_SOFTWARE_AVAILABLE}</b></td>
                    </tr>
                    <!-- EDP: software_total -->
					<!-- BDP: del_software_support -->
					<tr>
                      <td colspan="7" nowrap>&nbsp;</td>
                      </tr>
					<tr>
                      <td colspan="5" nowrap class="content3"><b>{TR_DEL_SOFTWARE}</b></td>
                      <td nowrap class="content3" align="center" width="150"><b>{TR_DEL_STATUS}</b></td>
                      <td nowrap class="content3" align="center" width="150"><b>{TR_DEL_ACTION}</b></td>
                    </tr>
					<!-- BDP: del_software_item -->
                    <tr>
                      <td colspan="5" nowrap class="{DEL_ITEM_CLASS}">{SOFTWARE_DEL_RES_MESSAGE}</td>
                      <td nowrap class="{DEL_ITEM_CLASS}" align="center" width="150">{DEL_SOFTWARE_STATUS}</td>
                      <td nowrap class="{DEL_ITEM_CLASS}" align="center" width="150"><img src="{THEME_COLOR_PATH}/images/icons/delete.png" width="16" height="16" border="0" align="middle" /> <a href="#" class="link" onClick="return action_res_delete('{DEL_SOFTWARE_ACTION_SCRIPT}')">{DEL_SOFTWARE_ACTION}</a>
                      </td>
                    </tr>
                    <!-- EDP: del_software_item -->
					<!-- EDP: del_software_support -->
                  </table>
                    </td>
                </tr>
            </table></td>
            <td>&nbsp;</td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
          </tr>
        </table>			
			</td>
          </tr>
        </table>
	  </td>
	</tr>
</table>
</body>
</html>
