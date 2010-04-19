<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_CLIENT_ADD_ALIAS_PAGE_TITLE}</title>
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

	function makeUser() {
		var dname = document.forms[0].elements['ndomain_name'].value;
		dname = dname.toLowerCase();
		document.forms[0].elements['ndomain_mpoint'].value = "/" + dname;
	}
	function setForwardReadonly(obj){
		if(obj.value == 1) {
			document.forms[0].elements['forward'].readOnly = false;
			document.forms[0].elements['forward_prefix'].disabled = false;
		} else {
			document.forms[0].elements['forward'].readOnly = true;
			document.forms[0].elements['forward'].value = '';
			document.forms[0].elements['forward_prefix'].disabled = true;
		}
	}
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
                      <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_domains.png" width="25" height="25" alt="" /></td>
                      <td colspan="2" class="title">{TR_MANAGE_DOMAIN_ALIAS}</td>
                    </tr>
                </table></td>
                <td width="27" align="right">&nbsp;</td>
              </tr>
              <tr>
                <td valign="top"><form name="add_alias_frm" method="post" action="alias_add.php">
                    <table width="100%" cellpadding="5" cellspacing="5">
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td colspan="2" class="content3"><b>{TR_ADD_ALIAS}</b></td>
                      </tr>
                      <!-- BDP: page_message -->
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td colspan="2" class="title"><span class="message">{MESSAGE}</span></td>
                      </tr>
                      <!-- EDP: page_message -->
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2">{TR_DOMAIN_ACCOUNT}</td>
                        <td class="content"><select name="usraccounts" size="5" class="textinput2">
                            <!-- BDP: user_entry -->
                            <option value="{USER}" {SELECTED}>{USER_DOMAIN_ACCOUN}</option>
                            <!-- EDP: user_entry -->
                        </select></td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td width="200" class="content2">
						 {TR_DOMAIN_NAME} <img id="dmn_help" src="{THEME_COLOR_PATH}/images/icons/help.png" width="16" height="16" alt="" />
						</td>
                        <td class="content"><input name="ndomain_name" type="text" class="textinput" style="width:170px" value="{DOMAIN}" onblur="makeUser();" /></td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2">{TR_MOUNT_POINT}</td>
                        <td class="content"><input name="ndomain_mpoint" type="text" class="textinput" id="ndomain_mpoint" value='{MP}' style="width:170px" /></td>
					  </tr>
					  <tr>
						<td width="25" nowrap="nowrap">&nbsp;</td>
						<td width="200" nowrap="nowrap" class="content2">{TR_ENABLE_FWD}</td>
						<td class="content">
						<input type="radio" name="status" {CHECK_EN} value="1" onchange='setForwardReadonly(this);' /> {TR_ENABLE}<br />
						<input type="radio" name="status" {CHECK_DIS} value="0" onchange='setForwardReadonly(this);' /> {TR_DISABLE}</td>
                      </tr>
                      <tr>
                        <td width="25" nowrap="nowrap">&nbsp;</td>
                        <td width="200" nowrap="nowrap" class="content2">{TR_FORWARD}</td>
						<td class="content">
							<select name="forward_prefix" style="vertical-align:middle"{DISABLE_FORWARD}>
								<option value="{TR_PREFIX_HTTP}"{HTTP_YES}>{TR_PREFIX_HTTP}</option>
								<option value="{TR_PREFIX_HTTPS}"{HTTPS_YES}>{TR_PREFIX_HTTPS}</option>
								<option value="{TR_PREFIX_FTP}"{FTP_YES}>{TR_PREFIX_FTP}</option>
							</select>
							<input name="forward" type="text" class="textinput" id="forward" style="width:170px" value="{FORWARD}"{READONLY_FORWARD} />
						</td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td colspan="2"><input name="Submit" type="submit" class="button" value="  {TR_ADD}  " /></td>
                      </tr>
                    </table>
                  <input type="hidden" name="uaction" value="add_alias" />
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
