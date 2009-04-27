<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_ADMIN_SERVER_STATICSTICS_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
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
                            <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_stats.png" width="25" height="25" alt="" /></td>
                            <td colspan="2" class="title">{TR_SERVER_STATISTICS}</td>
                          </tr>
                      </table></td>
                      <td width="27" align="right">&nbsp;</td>
                    </tr>
                    <tr>
                      <td><form action="server_statistic.php" method="post" name="reseller_user_statistics" id="reseller_user_statistics">
                          <table width="100%" border="0" cellpadding="0" cellspacing="0">
                            <tr>
                              <td width="45">&nbsp;</td>
                              <td width="80" class="content"><label for="month">{TR_MONTH}</label></td>
                              <td width="80" class="content"><select name="month" id="month">
                                  <!-- BDP: month_list -->
                                  <option {OPTION_SELECTED}>{MONTH_VALUE}</option>
                                  <!-- EDP: month_list -->
                                </select>
                              </td>
                              <td width="80" class="content"><label for="year">{TR_YEAR}</label></td>
                              <td width="80" class="content"><select name="year" id="year">
                                  <!-- BDP: year_list -->
                                  <option {OPTION_SELECTED}>{YEAR_VALUE}</option>
                                  <!-- EDP: year_list -->
                                </select>
                              </td>
                              <td align="left" class="content"><input name="Submit" type="submit" class="button" value="  {TR_SHOW}  " />
                              </td>
                            </tr>
                          </table>
                        <input type="hidden" name="uaction" value="change_data" />
                        </form>
                          <br />
                          <table width="100%">
                            <tr align="center">
                              <td width="35" nowrap="nowrap">&nbsp;</td>
                              <td class="content3" nowrap="nowrap" height="25"><b>{TR_DAY}</b></td>
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
                              <td class="content3" nowrap="nowrap" height="25"><b>{TR_DAY}</b></td>
                            </tr>
                            <!-- BDP: day_list -->
                            <tr class="hl">
                              <td align="center">&nbsp;</td>
                              <td class="{ITEM_CLASS}" nowrap="nowrap" align="center"><b><a href="server_statistic_day.php?year={YEAR}&amp;month={MONTH}&amp;day={DAY}" class="link">{DAY}</a></b></td>
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
                              <td class="{ITEM_CLASS}" nowrap="nowrap" align="center"><b><a href="server_statistic_day.php?year={YEAR}&amp;month={MONTH}&amp;day={DAY}" class="link">{DAY}</a></b></td>
                            </tr>
                            <!-- EDP: day_list -->
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
