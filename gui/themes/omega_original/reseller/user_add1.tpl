<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_CLIENT_CHANGE_PERSONAL_DATA_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/jquery.js"></script>
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/jquery.ispcpTooltips.js"></script>
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>

<script type="text/javascript">
/*<![CDATA[*/
	$(document).ready(function(){
		// Tooltips - begin
		$('#dmn_help').ispCPtooltips({msg:"{TR_DMN_HELP}"});
		// Tooltips - end
	});
/*]]>*/
</script>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">

<table width="100%" border="0" cellspacing="0" cellpadding="0" style="height:100%;padding:0;margin:0 auto;">
<!-- BDP: logged_from -->
<tr>
 <td colspan="3" height="20" nowrap="nowrap" class="backButton">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.png" width="16" height="16" border="0" style="vertical-align:middle" alt="" /></a> {YOU_ARE_LOGGED_AS}</td>
</tr>
<!-- EDP: logged_from -->
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
                      <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_user.png" width="25" height="25" alt="" /></td>
                      <td colspan="2" class="title">{TR_ADD_USER}</td>
                    </tr>
                </table></td>
                <td width="27" align="right">&nbsp;</td>
              </tr>
              <tr>
                <td><form name="reseller_add_users_first_frm" method="post" action="user_add1.php">
                    <table width="100%" cellpadding="5" cellspacing="5">
                      <!-- BDP: page_message -->
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td colspan="2" class="title"><span class="message">{MESSAGE}</span></td>
                      </tr>
                      <!-- EDP: page_message -->
                      <!-- BDP: add_form -->
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td colspan="2" class="content3">{TR_CORE_DATA}</td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2" width="200">
						 {TR_DOMAIN_NAME} <img id="dmn_help" src="{THEME_COLOR_PATH}/images/icons/help.png" width="16" height="16" alt="" />
						</td>
                        <td class="content">
                          <input type="text" name="dmn_name" value="{DMN_NAME_VALUE}" style="width:210px" class="textinput" />
                        </td>
                      </tr>
		      <!-- BDP: expire -->
		      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2" width="200">{TR_DOMAIN_EXPIRE}</td>
                        <td class="content"><select name="dmn_expire">
							<option value="0" {EXPIRE_NEVER_SET}>{TR_DOMAIN_EXPIRE_NEVER}</option>
							<option value="1" {EXPIRE_1_MONTH_SET}>{TR_DOMAIN_EXPIRE_1_MONTH}</option>
							<option value="2" {EXPIRE_2_MONTH_SET}>{TR_DOMAIN_EXPIRE_2_MONTHS}</option>
							<option value="3" {EXPIRE_3_MONTH_SET}>{TR_DOMAIN_EXPIRE_3_MONTHS}</option>
							<option value="6" {EXPIRE_6_MONTH_SET}>{TR_DOMAIN_EXPIRE_6_MONTHS}</option>
							<option value="12" {EXPIRE_1_YEAR_SET}>{TR_DOMAIN_EXPIRE_1_YEAR}</option>
							<option value="24" {EXPIRE_2_YEARS_SET}>{TR_DOMAIN_EXPIRE_2_YEARS}</option>
						</select>
                        </td>
                      </tr>
                      <!-- BDP: add_user -->
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2" width="200">{TR_CHOOSE_HOSTING_PLAN}</td>
                        <td class="content"><select name="dmn_tpl">
                            <!-- BDP: hp_entry -->
                            <option value="{CHN}" {CH{CHN}}>{HP_NAME}</option>
                            <!-- EDP: hp_entry -->
                          </select>
                        </td>
                      </tr>
                      <!-- BDP: personalize -->
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2" width="200">{TR_PERSONALIZE_TEMPLATE}</td>
                        <td class="content">{TR_YES}
                          <input type="radio" name="chtpl" value="_yes_" {CHTPL1_VAL} />
                          {TR_NO}
                          <input type="radio" name="chtpl" value="_no_" {CHTPL2_VAL} />
                        </td>
                      </tr>
                      <!-- EDP: personalize -->
                      <!-- EDP: add_user -->
                      <tr>
                        <td>&nbsp;</td>
                        <td colspan="2"><input name="Submit" type="submit" class="button" value="{TR_NEXT_STEP}" /></td>
                      </tr>
                      <!-- EDP: add_form -->
                    </table>
                  <input type="hidden" name="uaction" value="user_add_nxt" />
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
