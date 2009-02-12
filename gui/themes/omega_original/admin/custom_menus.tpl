<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_ADMIN_CUSTOM_MENUS_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex">
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
<script type="text/javascript">
<!--
function action_delete(url, link_name) {
	if (!confirm(sprintf("{TR_MESSAGE_DELETE}", link_name)))
		return false;
	location = url;
}
//-->
</script>
<style type="text/css">
<!--
.style1 {font-weight: bold}
-->
</style>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<table width="100%" border="0" cellspacing="0" cellpadding="0" height="100%" style="border-collapse: collapse;padding:0;margin:0;">
<tr>
<td align="left" valign="top" style="vertical-align: top; width: 195px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_left.jpg" border="0"></td>
<td style="height: 56px; width:100%; background-image: url({THEME_COLOR_PATH}/images/top/top_bg.jpg)"><img src="{THEME_COLOR_PATH}/images/top/top_left_bg.jpg" border="0"></td>
<td style="width: 73px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_right.jpg" border="0"></td>
</tr>
	<tr>
		<td style="width: 195px; vertical-align: top;">{MENU}</td>
	    <td colspan="2" style="vertical-align: top;"><table style="width: 100%; border-collapse: collapse;padding:0;margin:0;">
				<tr height="95">
				  <td style="padding-left:30px; width: 100%; background-image: url({THEME_COLOR_PATH}/images/top/middle_bg.jpg);">{MAIN_MENU}</td>
					<td style="padding:0;margin:0;text-align: right; width: 73px;vertical-align: top;"><img src="{THEME_COLOR_PATH}/images/top/middle_right.jpg" border="0"></td>
				</tr>
				<tr>
				  <td colspan="3"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td align="left"><table width="100%" cellpadding="5" cellspacing="5">
                          <tr>
                            <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_flash.png" width="25" height="25"></td>
                            <td colspan="2" class="title">{TR_TITLE_CUSTOM_MENUS}</td>
                          </tr>
                      </table></td>
                      <td width="27" align="right">&nbsp;</td>
                    </tr>
                    <tr>
                      <td><table width="100%" cellpadding="5" cellspacing="5">
                          <!-- BDP: page_message -->
                          <tr>
                            <td width="25">&nbsp;</td>
                            <td colspan="4" class="title"><span class="message">{MESSAGE}</span></td>
                          </tr>
                          <!-- EDP: page_message -->
                          <tr>
                            <td width="25">&nbsp;</td>
                            <td class="content3"><b>{TR_MENU_NAME}</b></td>
                            <td class="content3" align="center"><b>{TR_LEVEL}</b></td>
                            <td colspan="2" align="center" class="content3"><b>{TR_ACTON}</b></td>
                          </tr>
                          <!-- BDP: button_list -->
                          <tr>
                            <td>&nbsp;</td>
                            <td class="{CONTENT}"><a href="{LINK}" class="link" target="_blank"><strong>{MENU_NAME}</strong></a><br>
                              {LINK}</td>
                            <td class="{CONTENT}" align="center">{LEVEL}</td>
                            <td width="100" class="{CONTENT}" align="center"><img src="{THEME_COLOR_PATH}/images/icons/edit.png" width="16" height="16" border="0" align="absmiddle"> <a href="custom_menus.php?edit_id={BUTONN_ID}" class="link">{TR_EDIT}</a></td>
                            <td width="100" class="{CONTENT}" align="center"><img src="{THEME_COLOR_PATH}/images/icons/delete.png" width="16" height="16" border="0" align="absmiddle"> <a href="#"  onclick="action_delete('custom_menus.php?delete_id={BUTONN_ID}', '{MENU_NAME}')" class="link">{TR_DELETE}</a></td>
                          </tr>
                          <!-- EDP: button_list -->
                        </table>
                          <br>
                          <form name="add_new_button_frm" method="post" action="custom_menus.php">
                            <!-- BDP: add_button -->
                            <table width="100%" cellpadding="5" cellspacing="5">
                              <tr>
                                <td width="25">&nbsp;</td>
                                <td colspan="2" class="content3"><b>{TR_ADD_NEW_BUTTON}</b></td>
                              </tr>
                              <tr>
                                <td width="25">&nbsp;</td>
                                <td width="200" class="content2">{TR_BUTTON_NAME}</td>
                                <td class="content"><input name="bname" type="text" class="textinput" id="bname" style="width:210px" /></td>
                              </tr>
                              <tr>
                                <td width="25">&nbsp;</td>
                                <td width="200" class="content2">{TR_BUTTON_LINK}</td>
                                <td class="content"><input name="blink" type="text" class="textinput" id="blink" style="width:210px" /></td>
                              </tr>
                              <tr>
                                <td width="25">&nbsp;</td>
                                <td width="200" class="content2">{TR_BUTTON_TARGET}</td>
                                <td class="content"><input name="btarget" type="text" class="textinput" id="btarget" style="width:210px" /></td>
                              </tr>
                              <tr>
                                <td width="25">&nbsp;</td>
                                <td width="200" class="content2">{TR_VIEW_FROM}</td>
                                <td class="content"><select name="bview" id="bview">
                                    <option value="admin">{ADMIN}</option>
                                    <option value="reseller">{RESELLER}</option>
                                    <option value="user">{USER}</option>
                                    <option value="all">{RESSELER_AND_USER}</option>
                                </select></td>
                              </tr>
                              <tr>
                                <td>&nbsp;</td>
                                <td colspan="2"><input name="Button" type="button" class="button" value="  {TR_SAVE}  " onclick="return sbmt(document.forms[0],'new_button');"></td>
                              </tr>
                            </table>
                            <!-- EDP: add_button -->
                            <!-- BDP: edit_button -->
                            <table width="100%" cellpadding="5" cellspacing="5">
                              <tr>
                                <td width="25">&nbsp;</td>
                                <td colspan="2" class="content3"><b>{TR_EDIT_BUTTON}</b></td>
                              </tr>
                              <tr>
                                <td width="25">&nbsp;</td>
                                <td width="200" class="content2">{TR_BUTTON_NAME}</td>
                                <td class="content"><input name="bname" type="text" class="textinput" id="bname" style="width:210px" value="{BUTON_NAME}" /></td>
                              </tr>
                              <tr>
                                <td width="25">&nbsp;</td>
                                <td width="200" class="content2">{TR_BUTTON_LINK}</td>
                                <td class="content"><input name="blink" type="text" class="textinput" id="blink" style="width:210px" value="{BUTON_LINK}" /></td>
                              </tr>
                              <tr>
                                <td width="25">&nbsp;</td>
                                <td width="200" class="content2">{TR_BUTTON_TARGET}</td>
                                <td class="content"><input name="btarget" type="text" class="textinput" id="btarget" style="width:210px" value="{BUTON_TARGET}" /></td>
                              </tr>
                              <tr>
                                <td width="25">&nbsp;</td>
                                <td width="200" class="content2">{TR_VIEW_FROM}</td>
                                <td class="content"><select name="bview" id="bview">
                                    <option value="admin" {ADMIN_VIEW}>{ADMIN}</option>
                                    <option value="reseller" {RESELLER_VIEW}>{RESELLER}</option>
                                    <option value="user" {USER_VIEW}>{USER}</option>
                                    <option value="all" {ALL_VIEW}>{RESSELER_AND_USER}</option>
                                </select></td>
                              </tr>
                              <tr>
                                <td>&nbsp;</td>
                                <td colspan="2"><input name="Button" type="button" class="button" value="  {TR_SAVE}  " onclick="return sbmt(document.forms[0],'edit_button');" /></td>
                              </tr>
                            </table>
                            <input type="hidden" name="eid" value="{EID}" />
                            <!-- EDP: edit_button -->
                            <input type="hidden" name="uaction" value="" />
                          </form>
                        <!-- end of content --></td>
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
