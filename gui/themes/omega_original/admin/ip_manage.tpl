<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_ADMIN_IP_MANAGE_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
<script type="text/javascript">
<!--
function action_delete(url, ip) {
	if (!confirm(sprintf("{TR_MESSAGE_DELETE} ", ip)))
		return false;
	location = url;
}
//-->
</script>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
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
                            <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_ip.png" width="25" height="25" /></td>
                            <td colspan="2" class="title">{MANAGE_IPS}</td>
                          </tr>
                      </table></td>
                      <td width="27" align="right">&nbsp;</td>
                    </tr>
                    <tr>
                      <td valign="top"><table width="100%" cellpadding="5" cellspacing="5">
                          <!-- BDP: page_message -->
                          <tr>
                            <td width="25">&nbsp;</td>
                            <td colspan="4" class="title"><span class="message">{MESSAGE}</span></td>
                          </tr>
                          <!-- EDP: page_message -->
                          <tr>
                            <td width="25">&nbsp;</td>
                            <td colspan="4" class="content3"><b>{TR_AVAILABLE_IPS}</b></td>
                          </tr>
                          <tr align="center">
                            <td width="25">&nbsp;</td>
                            <td align="left" class="content3"><strong>{TR_IP}</strong></td>
                            <td class="content3"><strong>{TR_DOMAIN}</strong></td>
                            <td class="content3"><strong>{TR_ALIAS}</strong></td>
                            <td width="103" class="content3"><strong>{TR_ACTION}</strong></td>
                          </tr>
                          <!-- BDP: ip_row -->
                          <tr>
                            <td width="25" nowrap="nowrap">&nbsp;</td>
                            <td align="left" nowrap="nowrap" class="{IP_CLASS}">{IP}</td>
                            <td align="center" nowrap="nowrap" class="{IP_CLASS}">{DOMAIN}</td>
                            <td class="{IP_CLASS}" nowrap="nowrap" align="center">{ALIAS}</td>
                            <td class="{IP_CLASS}" nowrap="nowrap" align="center">
							<!-- BDP: ip_delete_show -->
            				-
            				<!-- EDP: ip_delete_show -->
            				<!-- BDP: ip_delete_link -->
              				<img src="{THEME_COLOR_PATH}/images/icons/delete.png" width="16" height="16" border="0" style="vertical-align:middle" /> <a href="#" onclick="action_delete('ip_delete.php?delete_id={DELETE_ID}', '{IP}')" class="link">{TR_UNINSTALL}</a></td>
            				<!-- EDP: ip_delete_link -->
                          </tr>
                          <!-- EDP: ip_row -->
                        </table>
                          <br />
                          <form name="add_new_ip_frm" method="post" action="ip_manage.php">
                            <table width="100%" cellpadding="5" cellspacing="5">
                              <tr>
                                <td width="25">&nbsp;</td>
                                <td colspan="2" class="content3"><b>{TR_ADD_NEW_IP}</b></td>
                              </tr>
                              <tr>
                                <td width="25" nowrap="nowrap">&nbsp;</td>
                                <td width="200" class="content2" nowrap="nowrap">{TR_IP}</td>
                                <td nowrap="nowrap" class="content"><input name="ip_number_1" type="text" class="textinput" style="width:31px" value="{VALUE_IP1}" maxlength="3" />
                                  .
                                  <input name="ip_number_2" type="text" class="textinput" style="width:31px" value="{VALUE_IP2}" maxlength="3" />
                                  .
                                  <input name="ip_number_3" type="text" class="textinput" style="width:31px" value="{VALUE_IP3}" maxlength="3" />
                                  .
                                  <input name="ip_number_4" type="text" class="textinput" style="width:31px" value="{VALUE_IP4}" maxlength="3" />
                                </td>
                              </tr>
                              <tr>
                                <td width="25" nowrap="nowrap">&nbsp;</td>
                                <td width="200" class="content2" nowrap="nowrap">{TR_DOMAIN}</td>
                                <td nowrap="nowrap" class="content"><input type="text" name="domain" value="{VALUE_DOMAIN}" style="width:180px" class="textinput" />
                                </td>
                              </tr>
                              <tr>
                                <td width="25" nowrap="nowrap">&nbsp;</td>
                                <td width="200" class="content2" nowrap="nowrap">{TR_ALIAS}</td>
                                <td nowrap="nowrap" class="content"><input type="text" name="alias" value="{VALUE_ALIAS}" style="width:180px" class="textinput" />
                                </td>
                              </tr>
                              <tr>
                                <td nowrap="nowrap">&nbsp;</td>
                                <td colspan="2" nowrap="nowrap"><input name="Submit" type="submit" class="button" value="  {TR_ADD}  " /></td>
                              </tr>
                            </table>
                            <input type="hidden" name="uaction" value="add_ip" />
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
