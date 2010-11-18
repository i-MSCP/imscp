<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_ADMIN_SOFTWARE_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="{THEME_COLOR_PATH}/css/imscp.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/imscp.js"></script>
<script language="JavaScript" type="text/JavaScript">
/*<![CDATA[*/
function action_remove_right() {
	if (!confirm("{TR_MESSAGE_REMOVE}"))
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
						<td colspan="2" class="title">{TR_SOFTWARE_RIGHTS}</td>
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
                      <td colspan="4" nowrap class="title"><font color="#FF0000">{MESSAGE}</font></td>
                      </tr>
					<!-- EDP: page_message -->
					<tr>
                      <td colspan="4" nowrap class="title"><b>{TR_ADD_RIGHTS} {TR_SOFTWARE_NAME}</b></td>
                      </tr>
					  <!-- BDP: no_select_reseller -->
					   <tr>
                      <td colspan="4" nowrap class="title"><font color="#FF0000">{NO_RESELLER_AVAILABLE}</font></td>
                      </tr>
					<!-- EDP: no_select_reseller -->
					  <!-- BDP: select_reseller -->
					<tr>
					<td colspan="4" nowrap>
					<form action="software_change_rights.php" method="post">
					<table width="100%"  border="0" cellspacing="0" cellpadding="0">
					<tr>
                      <td nowrap class="title">
						<select name="selected_reseller" id="selected_reseller">
							<option value="all">{ALL_RESELLER_NAME}</option>
							<!-- BDP: reseller_item -->
							<option value="{RESELLER_ID}">{RESELLER_NAME}</option>
							<!-- EDP: reseller_item -->
						</select> 
					  </td>
                    </tr>
					<tr>
						<td colspan="4" nowrap>
							<input name="Button" type="submit" class="button" value="{TR_ADD_RIGHTS_BUTTON}" />
							<input type="hidden" value="add" name="change" />
							<input type="hidden" value="{SOFTWARE_ID_VALUE}" name="id" />
						</td>
					</tr>
					</table>
					</form>
					</td>
					</tr>
					<!-- EDP: select_reseller -->
					<tr>
                      <td nowrap class="content3"><b>{TR_RESELLER}</b></td>
                      <td nowrap class="content3" align="center" width="180"><b>{TR_ADDED_BY}</b></td>
					  <td nowrap class="content3" align="center" width="80"><b>{TR_REMOVE_RIGHTS}</b></td>
                    </tr>
                    <!-- BDP: no_reseller_list -->
                    <tr>
                      <td colspan="4" class="title"><font color="#FF0000">{NO_RESELLER}</font></td>
                    </tr>
                    <!-- EDP: no_reseller_list -->
                    <!-- BDP: list_reseller -->
                    <tr>
                      <td nowrap class="content">{RESELLER}</td>
					  <td nowrap class="content">{ADMINISTRATOR}</td>
                      <td nowrap class="content" align="center"><img src="{THEME_COLOR_PATH}/images/icons/delete.png" width="16" height="16" border="0" align="middle" /> <a href="{REMOVE_RIGHT_LINK}" class="link" onClick="return action_remove_right()">{TR_REMOVE_RIGHT}</a></td>
                    </tr>
                    <!-- EDP: list_reseller -->
                    <tr>
                      <td colspan="4" align="right" nowrap class="content3">{TR_RESELLER_COUNT}:&nbsp;<b>{TR_RESELLER_NUM}</b></td>
                    </tr>
                  </table>
                    </td>
                </tr>
            </table>
			</td>
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
