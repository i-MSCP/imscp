<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_ADMIN_SERVER_DAY_STATS_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
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
                            <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_stats.png" width="25" height="25"></td>
                            <td colspan="2" class="title">{TR_SERVER_DAY_STATISTICS}</td>
                          </tr>
                      </table></td>
                      <td width="27" align="right">&nbsp;</td>
                    </tr>
                    <tr>
                      <td valign="top"><table>
                          <tr>
                            <td width="40" nowrap="nowrap">&nbsp;</td>
                            <td height="25" colspan="13" nowrap="nowrap" class="content">{TR_YEAR} {YEAR}&nbsp;&nbsp;&nbsp;&nbsp; {TR_MONTH} {MONTH}&nbsp;&nbsp;&nbsp;{TR_DAY} {DAY}</td>
                          </tr>
                          <tr align="center">
                            <td nowrap="nowrap">&nbsp;</td>
                            <td class="content3" nowrap="nowrap" height="25"><b>{TR_HOUR}</b></td>
                            <td class="content3" nowrap="nowrap" height="25"><b>{TR_WEB_IN}</b></td>
                            <td class="content3" nowrap="nowrap" height="25"><b>{TR_WEB_OUT}</b></td>
                            <td class="content3" nowrap="nowrap" height="25"><b>{TR_SMTP_IN}</b></td>
                            <td class="content3" nowrap="nowrap" height="25"><b>{TR_SMTP_OUT}</b></td>
                            <td class="content3" nowrap="nowrap" height="25"><b>{TR_POP_IN}</b></td>
                            <td class="content3" nowrap="nowrap" height="25"><b>{TR_POP_OUT}</b></td>
                            <td class="content3" nowrap="nowrap" height="25"><b>{TR_OTHER_IN}</b></td>
                            <td class="content3" nowrap="nowrap" height="25"><b>{TR_OTHER_OUT}</b></td>
                            <td class="content3" nowrap="nowrap" height="25"><b>{TR_ALL_IN}</b></td>
                            <td class="content3" nowrap="nowrap" height="25"><b>{TR_ALL_OUT}</b></td>
                            <td class="content3" nowrap="nowrap" height="25"><b>{TR_ALL}</b></td>
                            <td class="content3" nowrap="nowrap" height="25"><b>{TR_HOUR}</b></td>
                          </tr>
                          <!-- BDP: hour_list -->
                          <tr>
                            <td nowrap="nowrap" align="center">&nbsp;</td>
                            <td class="{ITEM_CLASS}" nowrap="nowrap" align="center"><b>{HOUR}</b></td>
                            <td class="{ITEM_CLASS}" nowrap="nowrap" align="center">{WEB_IN}</td>
                            <td class="{ITEM_CLASS}" nowrap="nowrap" align="center">{WEB_OUT}</td>
                            <td class="{ITEM_CLASS}" nowrap="nowrap" align="center">{SMTP_IN}</td>
                            <td class="{ITEM_CLASS}" nowrap="nowrap" align="center">{SMTP_OUT}</td>
                            <td class="{ITEM_CLASS}" nowrap="nowrap" align="center">{POP_IN}</td>
                            <td class="{ITEM_CLASS}" nowrap="nowrap" align="center">{POP_OUT}</td>
                            <td class="{ITEM_CLASS}" nowrap="nowrap" align="center">{OTHER_IN}</td>
                            <td class="{ITEM_CLASS}" nowrap="nowrap" align="center">{OTHER_OUT}</td>
                            <td class="{ITEM_CLASS}" nowrap="nowrap" align="center">{ALL_IN}</td>
                            <td class="{ITEM_CLASS}" nowrap="nowrap" align="center">{ALL_OUT}</td>
                            <td class="{ITEM_CLASS}" nowrap="nowrap" align="center">{ALL}</td>
                            <td class="{ITEM_CLASS}" nowrap="nowrap" align="center"><b>{HOUR}</b></td>
                          </tr>
                          <!-- EDP: hour_list -->
                          <tr>
                            <td nowrap="nowrap" align="center">&nbsp;</td>
                            <td class="content3" nowrap="nowrap" align="center"><b>{TR_ALL}</b></td>
                            <td class="content3" nowrap="nowrap" align="center"><span class="content2"><strong>{WEB_IN_ALL}</strong></span></td>
                            <td class="content3" nowrap="nowrap" align="center"><span class="content2"><strong>{WEB_OUT_ALL}</strong></span></td>
                            <td class="content3" nowrap="nowrap" align="center"><span class="content2"><strong>{SMTP_IN_ALL}</strong></span></td>
                            <td class="content3" nowrap="nowrap" align="center"><span class="content2"><strong>{SMTP_OUT_ALL}</strong></span></td>
                            <td class="content3" nowrap="nowrap" align="center"><span class="content2"><strong>{POP_IN_ALL}</strong></span></td>
                            <td class="content3" nowrap="nowrap" align="center"><span class="content2"><strong>{POP_OUT_ALL}</strong></span></td>
                            <td class="content3" nowrap="nowrap" align="center"><span class="content2"><strong>{OTHER_IN_ALL}</strong></span></td>
                            <td class="content3" nowrap="nowrap" align="center"><span class="content2"><strong>{OTHER_OUT_ALL}</strong></span></td>
                            <td class="content3" nowrap="nowrap" align="center"><span class="content2"><strong>{ALL_IN_ALL}</strong></span></td>
                            <td class="content3" nowrap="nowrap" align="center"><span class="content2"><strong>{ALL_OUT_ALL}</strong></span></td>
                            <td class="content3" nowrap="nowrap" align="center"><span class="content2"><strong>{ALL_ALL}</strong></span></td>
                            <td class="content3" nowrap="nowrap" align="center"><b>{TR_ALL}</b></td>
                          </tr>
                      </table></td>
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
