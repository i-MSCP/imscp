<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_ADMIN_SOFTWARE_PAGE_TITLE}</title>
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
	function action_delete() {
		if (!confirm("{TR_MESSAGE_DELETE}"))
		return false;
	}
	function action_activate() {
		if (!confirm("{TR_MESSAGE_ACTIVATE}"))
		return false;
	}
	function action_import() {
		if (!confirm("{TR_MESSAGE_IMPORT}"))
		return false;
	}
/*]]>*/
</script>
</head>
<body onLoad="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.png','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.png','{THEME_COLOR_PATH}/images/icons/domains_a.png','{THEME_COLOR_PATH}/images/icons/general_a.png' ,'{THEME_COLOR_PATH}/images/icons/manage_users_a.png','{THEME_COLOR_PATH}/images/icons/webtools_a.png','{THEME_COLOR_PATH}/images/icons/statistics_a.png','{THEME_COLOR_PATH}/images/icons/support_a.png')">
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="height:100%;padding:0;margin:0 auto;">
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
						<td colspan="2" class="title">{TR_SOFTWARE_DEPOT}</td>
					</tr>
				</table>			
			</td>
            <td width="27" align="right">&nbsp;</td>
			</tr>
			<tr>
            <td><table width="100%"  border="00" cellspacing="0" cellpadding="0">
                <tr>
                  <td width="25">&nbsp;</td>
                  <td valign="top"><table width="100%" cellspacing="7">
                    <!-- BDP: page_message -->
					<tr>
                      <td colspan="7" nowrap class="title"><font color="#FF0000">{MESSAGE}</font></td>
                      </tr>
					<!-- EDP: page_message -->
					<tr>
						<td colspan="9" valign="top">
							<form action="software_manage.php" method="post" enctype="multipart/form-data">
								<table width="100%" cellspacing="0" cellpadding="0">
									<tr>
										<td nowrap class="content3" colspan="3"><b>{TR_UPLOAD_SOFTWARE}</b></td>
									</tr>
									<tr>
										<td  class="content2" width="200">{TR_SOFTWARE_FILE}</td>
										<td  class="content"><input type="file" name="sw_file" class="textinput" size="60" /></td>
									</tr>
									<tr>
										<td  class="content2" width="200">{TR_SOFTWARE_URL}</td>
										<td  class="content"><input type="text" name="sw_wget" value="{VAL_WGET}" size="60" class="textinput" /></td>
									</tr>
									<tr>
										<td colspan="3" nowrap><input name="upload" type="submit" class="button" value="{TR_UPLOAD_SOFTWARE_BUTTON}" /><input type="hidden" name="send_software_upload_token" id="send_software_upload_token" value="{SOFTWARE_UPLOAD_TOKEN}" /></td>
									</tr>
								</table>
							</form>
						</td>
					</tr>
					<tr>
                      <td nowrap class="content3"><b>{TR_SOFTWARE_NAME}</b></td>
					  <td nowrap class="content3" width="60"><b>{TR_SOFTWARE_VERSION}</b></td>
					  <td nowrap class="content3" width="60"><b>{TR_SOFTWARE_LANGUAGE}</b></td>
                      <td nowrap class="content3" width="60"><b>{TR_SOFTWARE_TYPE}</b></td>
                      <td nowrap class="content3" width="120"><b>{TR_SOFTWARE_ADMIN}</b></td>
                      <td nowrap class="content3" align="center" width="65"><b>{TR_SOFTWARE_DOWNLOAD}</b></td>
					  <td nowrap class="content3" align="center" width="65"><b>{TR_SOFTWARE_DELETE}</b></td>
                      <td nowrap class="content3" align="center" width="90"><b>{TR_SOFTWARE_RIGHTS}</b></td>
                    </tr>
                    <!-- BDP: no_softwaredepot_list -->
                    <tr>
                      <td colspan="8" class="title"><font color="#FF0000">{NO_SOFTWAREDEPOT}</font></td>
                    </tr>
                    <!-- EDP: no_softwaredepot_list -->
                    <!-- BDP: list_softwaredepot -->
                    <tr>
                      <td nowrap class="content"><img src="{THEME_COLOR_PATH}/images/icons/cd.png" width="16" height="16" align="middle" />&nbsp;<a href="#" class="swtooltip" title="{TR_TOOLTIP}"><font color="{LINK_COLOR}">{TR_NAME}</font></a></td>
					  <td nowrap class="content">{TR_VERSION}</td>
					  <td nowrap class="content">{TR_LANGUAGE}</td>
                      <td nowrap class="content">{TR_TYPE}</td>
                      <td nowrap class="content">{TR_ADMIN}</td>
                      <td nowrap class="content" align="center"><img src="{THEME_COLOR_PATH}/images/content/table_icon_update.png" width="16" height="16" border="0" align="middle" /><a target="_blank" href="{DOWNLOAD_LINK}" class="link" >{TR_DOWNLOAD}</a></td>
                      <td nowrap class="content" align="center"><img src="{THEME_COLOR_PATH}/images/icons/delete.png" width="16" height="16" border="0" align="middle" /> <a href="{DELETE_LINK}" class="link" onClick="return action_delete()">{TR_DELETE}</a></td>
					  <td nowrap class="content" align="center"><img src="{THEME_COLOR_PATH}/images/icons/{SOFTWARE_ICON}.png" width="16" height="16" border="0" align="middle" /><a href="{SOFTWARE_RIGHTS_LINK}" class="link">{RIGHTS_LINK}</a></td>
                    </tr>
                    <!-- EDP: list_softwaredepot -->
                    <tr>
                      <td colspan="8" align="right" nowrap class="content3">{TR_SOFTWAREDEPOT_COUNT}:&nbsp;<b>{TR_SOFTWAREDEPOT_NUM}</b></td>
                    </tr>
                  </table>
                    </td>
                </tr>
            </table>
			</td>
            <td>&nbsp;</td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
          </tr>
          <tr>
            <td align="left">
				<table width="100%" cellpadding="5" cellspacing="5">
					<tr>
						<td width="25"><img src="{THEME_COLOR_PATH}/images/icons/cd_big.png" width="25" height="25" /></td>
						<td colspan="2" class="title">{TR_AWAITING_ACTIVATION}</td>
					</tr>
				</table>			
			</td>
            <td width="27" align="right">&nbsp;</td>
          </tr>
          <tr>
            <td><table width="100%"  border="00" cellspacing="0" cellpadding="0">
                <tr>
                  <td width="25">&nbsp;</td>
                  <td valign="top"><table width="100%" cellspacing="7">
                    <tr>
                      <td nowrap class="content3"><b>{TR_SOFTWARE_NAME}</b></td>
					  <td nowrap class="content3" width="60"><b>{TR_SOFTWARE_VERSION}</b></td>
					  <td nowrap class="content3" width="60"><b>{TR_SOFTWARE_LANGUAGE}</b></td>
                      <td nowrap class="content3" width="60"><b>{TR_SOFTWARE_TYPE}</b></td>
                      <td nowrap class="content3" width="120"><b>{TR_SOFTWARE_RESELLER}</b></td>
					  <td nowrap class="content3" align="center" width="65"><b>{TR_SOFTWARE_IMPORT}</b></td>
                      <td nowrap class="content3" align="center" width="65"><b>{TR_SOFTWARE_DOWNLOAD}</b></td>
                      <td nowrap class="content3" align="center" width="65"><b>{TR_SOFTWARE_ACTIVATION}</b></td>
                      <td nowrap class="content3" align="center" width="65"><b>{TR_SOFTWARE_DELETE}</b></td>
                    </tr>
                    <!-- BDP: no_software_list -->
                    <tr>
                      <td colspan="9" class="title"><font color="#FF0000">{NO_SOFTWARE}</font></td>
                    </tr>
                    <!-- EDP: no_software_list -->
                    <!-- BDP: list_software -->
                    <tr>
                      <td nowrap class="content"><img src="{THEME_COLOR_PATH}/images/icons/cd.png" width="16" height="16" align="middle" />&nbsp;<a href="#" class="swtooltip" title="{TR_TOOLTIP}">{TR_NAME}</a></td>
					  <td nowrap class="content">{TR_VERSION}</td>
					  <td nowrap class="content">{TR_LANGUAGE}</td>
                      <td nowrap class="content">{TR_TYPE}</td>
                      <td nowrap class="content">{TR_RESELLER}</td>
					  <td nowrap class="content" align="center"><img src="{THEME_COLOR_PATH}/images/content/table_icon_update.png" width="16" height="16" border="0" align="middle" /><a href="{IMPORT_LINK}" class="link" onClick="return action_import()">{TR_IMPORT}</a></td>
                      <td nowrap class="content" align="center"><img src="{THEME_COLOR_PATH}/images/content/table_icon_update.png" width="16" height="16" border="0" align="middle" /><a target="_blank" href="{DOWNLOAD_LINK}" class="link" >{TR_DOWNLOAD}</a></td>
                      <td nowrap class="content" align="center"><img src="{THEME_COLOR_PATH}/images/icons/edit.png" width="16" height="16" border="0" align="middle" /> <a href="{ACTIVATE_LINK}" class="link" onClick="return action_activate()">{TR_ACTIVATION}</a></td>
                      <td nowrap class="content" align="center"><img src="{THEME_COLOR_PATH}/images/icons/delete.png" width="16" height="16" border="0" align="middle" /> <a href="{DELETE_LINK}" class="link" onClick="return action_delete()">{TR_DELETE}</a></td>
                    </tr>
                    <!-- EDP: list_software -->
                    <tr>
                      <td colspan="9" align="right" nowrap class="content3">{TR_SOFTWARE_ACT_COUNT}:&nbsp;<b>{TR_SOFTWARE_ACT_NUM}</b></td>
                    </tr>
                  </table>
                    </td>
                </tr>
            </table>
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
	<tr>
	<td colspan=3>
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td align="left">
<table width="100%" cellpadding="5" cellspacing="5">
	<tr>
		<td width="25"><img src="{THEME_COLOR_PATH}/images/icons/cd_big.png" width="25" height="25" /></td>
		<td colspan="2" class="title">{TR_ACTIVATED_SOFTWARE}</td>
	</tr>
</table>			
			</td>
            <td width="27" align="right">&nbsp;</td>
          </tr>
          <tr>
            <td><table width="100%"  border="00" cellspacing="0" cellpadding="0">
                <tr>
                  <td width="25">&nbsp;</td>
                  <td valign="top"><table width="100%" cellspacing="4">
                    <tr>
                      <td nowrap class="content3"><b>{TR_RESELLER_NAME}</b></td>
					  <td nowrap class="content3" align="center" width="150"><b>{TR_RESELLER_COUNT_SWDEPOT}</b></td>
					  <td nowrap class="content3" align="center" width="150"><b>{TR_RESELLER_COUNT_WAITING}</b></td>
                      <td nowrap class="content3" align="center" width="150"><b>{TR_RESELLER_COUNT_ACTIVATED}</b></td>
                      <td nowrap class="content3" align="center" width="150"><b>{TR_RESELLER_SOFTWARE_IN_USE}</b></td>
                    </tr>
                    <!-- BDP: no_reseller_list -->
                    <tr>
                      <td colspan="5" class="title"><font color="#FF0000">{NO_RESELLER}</font></td>
                    </tr>
                    <!-- EDP: no_reseller_list -->
                    <!-- BDP: list_reseller -->
                    <tr>
					  <td nowrap class="content">{RESELLER_NAME}</td>
					  <td nowrap class="content" align="center" width="100">{RESELLER_COUNT_SWDEPOT}</td>
                      <td nowrap class="content" align="center" width="100">{RESELLER_COUNT_WAITING}</td>
                      <td nowrap class="content" align="center" width="100">{RESELLER_COUNT_ACTIVATED}</td>
                      <td nowrap class="content" align="center" width="100"><a href="software_reseller.php?id={RESELLER_ID}">{RESELLER_SOFTWARE_IN_USE}</a></td>
                    </tr>
                    <!-- EDP: list_reseller -->
                    <tr>
                      <td colspan="5" align="right" nowrap class="content3">{TR_RESELLER_ACT_COUNT}:&nbsp;<b>{TR_RESELLER_ACT_NUM}</b></td>
                    </tr>
                  </table>
                    </td>
                </tr>
            </table>
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
	</table>
	</td>
	</tr>
</table>
</body>
</html>
