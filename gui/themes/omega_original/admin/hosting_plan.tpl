<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_RESELLER_MAIN_INDEX_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
<script type="text/javascript">
<!--
function delete_account(url, plan) {
	return confirm(sprintf("{TR_MESSAGE_DELETE}", plan))
}
//-->
</script>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="height:100%;padding:0;margin:0 auto;">
<tr>
<td align="left" valign="top" style="vertical-align: top; width: 195px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_left.jpg" width="195" height="56" border="0" alt="ispCP Logogram" /></td>
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
				  <td colspan="3"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td align="left"><table width="100%" cellpadding="5" cellspacing="5">
                          <tr>
                            <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_serverstatus.png" width="25" height="25" alt="" /></td>
                            <td colspan="2" class="title">{TR_HOSTING_PLANS}</td>
                          </tr>
                      </table></td>
                      <td width="27" align="right">&nbsp;</td>
                    </tr>
                    <tr>
                      <td valign="top"><table width="100%" cellspacing="3">
                          <!-- BDP: page_message -->
                          <tr>
                      <td width="35">&nbsp;</td>
                            <td colspan="5" class="title"><span class="message">{MESSAGE}</span></td>
                          </tr>
                          <!-- EDP: page_message -->
                          <!-- BDP: hp_table -->
                          <tr>
                      <td width="35" align="center">&nbsp;</td>
                            <td class="content3" width="50" align="center"><span class="menu"><b>{TR_NOM}</b></span></td>
                            <td class="content3"><b>{TR_PLAN_NAME}</b></td>
                            <td width="100" align="center" class="content3"><strong>{TR_PURCHASING}</strong></td>
                            <td width="200" colspan="2" align="center" class="content3"><b>{TR_ACTION}</b></td>
                          </tr>
                          <!-- BDP: hp_entry -->
                          <tr class="hl">
                      		<td width="35" align="center">&nbsp;</td>
                            <td class="{CLASS_TYPE_ROW}" width="50" align="center">{PLAN_NOM}</td>
                      		<td class="{CLASS_TYPE_ROW}"><a href="../orderpanel/package_info.php?user_id={ADMIN_ID}&amp;id={HP_ID}" target="_blank" title="{PLAN_SHOW}">{PLAN_NAME}</a></td>
                            <td align="center" class="{CLASS_TYPE_ROW}">{PURCHASING}</td>
                            <td class="{CLASS_TYPE_ROW}" align="center"><img src="{THEME_COLOR_PATH}/images/icons/edit.png" width="16" height="16" border="0" style="vertical-align:middle" alt="" /> <a href="hosting_plan_edit.php?hpid={HP_ID}" class="link">{TR_EDIT}</a></td>
                      		<!-- BDP: hp_delete -->
                            <td class="{CLASS_TYPE_ROW}" align="center"><img src="{THEME_COLOR_PATH}/images/icons/delete.png" width="16" height="16" border="0" style="vertical-align:middle" alt="" /> <a href="hosting_plan_delete.php?hpid={HP_ID}" onclick="return delete_account('hosting_plan_delete.php?hpid={HP_ID}', '{PLAN_NAME2}')" class="link">{PLAN_ACTION}</a></td>
                      		<!-- EDP: hp_delete -->
                          </tr>
                          <!-- EDP: hp_entry -->
                          <!-- EDP: hp_table -->
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
