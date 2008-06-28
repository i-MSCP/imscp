<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_RESELLER_MAIN_INDEX_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
<script>
<!--

function change_status(dom_id) {
	if (!confirm("{TR_MESSAGE_CHANGE_STATUS}"))
		return false;

	location = ('change_status.php?domain_id=' + dom_id);
}

function delete_account(url) {
	if (!confirm("{TR_MESSAGE_DELETE_ACCOUNT}"))
		return false;

	location = url;
}
//-->
</script>

</head>
<body onLoad="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<table width="100%" border="0" cellspacing="0" cellpadding="0" height="100%" style="border-collapse: collapse;padding:0;margin:0;">
<tr>
<td align="left" valign="top" style="vertical-align: top; width: 195px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_left.jpg" border="0"></td>
<td style="height: 56px; width:100%; background-image: url({THEME_COLOR_PATH}/images/top/top_bg.jpg)"><img src="{THEME_COLOR_PATH}/images/top/top_left_bg.jpg" border="0"></td>
<td style="width: 73px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_right.jpg" border="0"></td>
</tr>
	<tr>
		<td style="width: 195px; vertical-align: top;">{SUB_MENU}</td>
	    <td colspan=2 style="vertical-align: top;"><table style="width: 100%; border-collapse: collapse;padding:0;margin:0;">
				<tr height="95";>
				  <td style="padding-left:30px; width: 100%; background-image: url({THEME_COLOR_PATH}/images/top/middle_bg.jpg);">{MAIN_MENU}</td>
					<td style="padding:0;margin:0;text-align: right; width: 73px;vertical-align: top;"><img src="{THEME_COLOR_PATH}/images/top/middle_right.jpg" border="0"></td>
				</tr>
				<tr height="*">
				  <td colspan=3><table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td align="left" class="title"><table width="100%" cellpadding="5" cellspacing="5">
                          <tr>
                            <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_serverstatus.png" width="25" height="25"></td>
                            <td colspan="2" class="title">{TR_ADD_HOSTING_PLAN}</td>
                          </tr>
                      </table></td>
                      <td width="27" align="right">&nbsp;</td>
                    </tr>
                    <tr>
                      <td valign="top"><form name="reseller_add_host_plant_frm" method="post" action="ahp.php">
                          <table width="100%" cellspacing="3">
                            <tr>
                              <td width="25" align="left">&nbsp;</td>
                              <td colspan="2" align="left" class="content3"><b>{TR_HOSTING PLAN PROPS}</b></td>
                            </tr>
                            <!-- BDP: page_message -->
                            <tr>
                              <td>&nbsp;</td>
                              <td colspan="2" class="title"><font color="#FF0000">{MESSAGE}</font></td>
                            </tr>
                            <!-- EDP: page_message -->
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td class="content2" width="250">{TR_TEMPLATE_NAME}</td>
                              <td class="content"><input type="text" name=hp_name value="{HP_NAME_VALUE}" style="width:210px" class="textinput">
                              </td>
                            </tr>
                            <tr>
                              <td>&nbsp;</td>
                              <td class="content2">{TR_TEMPLATE_DESCRIPTON}</td>
                              <td class="content"><textarea name="hp_description" class="textinput2" style="width:210px">{HP_DESCRIPTION_VALUE}</textarea></td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td class="content2" width="250">{TR_MAX_SUBDOMAINS}</i></td>
                              <td class="content"><input type="text" name=hp_sub value="{TR_MAX_SUB_LIMITS}" style="width:100px" class="textinput">
                              </td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td class="content2" width="250">{TR_MAX_ALIASES}</i></b> </td>
                              <td class="content"><input type="text" name=hp_als value="{TR_MAX_ALS_VALUES}" style="width:100px" class="textinput">
                              </td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td class="content2" width="250">{TR_MAX_MAILACCOUNTS}</td>
                              <td class="content"><input type="text" name=hp_mail value="{HP_MAIL_VALUE}" style="width:100px" class="textinput">
                              </td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td class="content2" width="250">{TR_MAX_FTP}</td>
                              <td class="content"><input type="text" name=hp_ftp value="{HP_FTP_VALUE}" style="width:100px" class="textinput">
                              </td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td class="content2" width="250">{TR_MAX_SQL}</td>
                              <td class="content"><input type="text" name=hp_sql_db value="{HP_SQL_DB_VALUE}" style="width:100px" class="textinput">
                              </td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td class="content2" width="250">{TR_MAX_SQL_USERS}</td>
                              <td class="content"><input type="text" name=hp_sql_user value="{HP_SQL_USER_VALUE}" style="width:100px" class="textinput">
                              </td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td class="content2" width="250">{TR_MAX_TRAFFIC}</td>
                              <td class="content"><input type="text" name=hp_traff value="{HP_TRAFF_VALUE}" style="width:100px" class="textinput">
                              </td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td class="content2" width="250">{TR_DISK_LIMIT}</td>
                              <td class="content"><input type="text" name=hp_disk value="{HP_DISK_VALUE}" style="width:100px" class="textinput">
                              </td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td class="content2" width="250">{TR_PHP}</td>
                              <td  class="content"><input type="radio" name="php" value="_yes_" {TR_PHP_YES}>
                                {TR_YES}
                                <input type="radio" name="php" value="_no_" {TR_PHP_NO}>
                                {TR_NO}</td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td class="content2" width="250">{TR_CGI}</td>
                              <td  class="content"><input type="radio" name="cgi" value="_yes_" {TR_CGI_YES}>
                                {TR_YES}
                                <input type="radio" name="cgi" value="_no_" {TR_CGI_NO}>
                                {TR_NO}</td>
                            </tr>
                            <tr>
                              <td>&nbsp;</td>
                              <td colspan="2" class="content3"><b>{TR_BILLING_PROPS}</b></td>
                            </tr>
                            <tr>
                              <td>&nbsp;</td>
                              <td class="content2">{TR_PRICE}</td>
                              <td class="content"><input name=hp_price type="text" class="textinput" id="hp_price" style="width:100px" value="{HP_PRICE}"></td>
                            </tr>
                            <tr>
                              <td>&nbsp;</td>
                              <td class="content2">{TR_SETUP_FEE}</td>
                              <td class="content"><input name=hp_setupfee type="text" class="textinput" id="hp_setupfee" style="width:100px" value="{HP_SETUPFEE}"></td>
                            </tr>
                            <tr>
                              <td>&nbsp;</td>
                              <td class="content2">{TR_VALUE}</td>
                              <td class="content"><input name=hp_value type="text" class="textinput" id="hp_value" style="width:100px" value="{HP_VELUE}">
                                  <small>{TR_EXAMPEL}</small></td>
                            </tr>
                            <tr>
                              <td>&nbsp;</td>
                              <td class="content2">{TR_PAYMENT}</td>
                              <td class="content"><input name=hp_payment type="text" class="textinput" id="hp_payment" style="width:100px" value="{HP_PAYMENT}"></td>
                            </tr>
                            <tr>
                              <td>&nbsp;</td>
                              <td class="content2">{TR_STATUS}</td>
                              <td class="content"><input type="radio" name="status" value="1" {TR_STATUS_YES}>
                                {TR_YES}
                                <input type="radio" name="status" value="0" {TR_STATUS_NO}>
                                {TR_NO}</td>
                            </tr>
                            <tr>
                              <td><input type="hidden" name="uaction" value="add_plan">
                              </td>
                              <td colspan="2"><input name="Submit" type="submit" class="button" value=" {TR_ADD_PLAN} "></td>
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
