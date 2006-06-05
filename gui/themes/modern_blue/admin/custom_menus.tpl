<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_ADMIN_CUSTOM_MENUS_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/vhcs.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/vhcs.js"></script>
<script language="JavaScript" type="text/JavaScript">
<!--

function action_delete(url) {
	if (!confirm("{TR_MESSAGE_DELETE}"))
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

<body onLoad="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif','{THEME_COLOR_PATH}/images/icons/logout_a.gif','{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<table width="100%" border="0" cellspacing="0" cellpadding="0" height="100%">
  <tr>
    <td height="80" align="left" valign="top">
    <table width="100%"  border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td width="17"><img src="{THEME_COLOR_PATH}/images/top/left.jpg" width="17" height="80"></td>
          <td width="198" align="center" background="{THEME_COLOR_PATH}/images/top/logo_background.jpg"><img src="{ISP_LOGO}"></td>
          <td background="{THEME_COLOR_PATH}/images/top/left_fill.jpg"><img src="{THEME_COLOR_PATH}/images/top/left_fill.jpg" width="2" height="80"></td>
          <td width="766"><img src="{THEME_COLOR_PATH}/images/top/middle_background.jpg" width="766" height="80"></td>
          <td background="{THEME_COLOR_PATH}/images/top/right_fill.jpg"><img src="{THEME_COLOR_PATH}/images/top/right_fill.jpg" width="3" height="80"></td>
          <td width="9"><img src="{THEME_COLOR_PATH}/images/top/right.jpg" width="9" height="80"></td>
        </tr>
    </table></td>
  </tr>
  <tr>
    <td valign="top"><table height="100%" width="100%"  border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="215" valign="top" bgcolor="#F5F5F5"><!-- Menu begin -->
  {MENU}
    <!-- Menu end -->
        </td>
        <td valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td height="62" align="left" background="{THEME_COLOR_PATH}/images/content/table_background.jpg" class="title"><img src="{THEME_COLOR_PATH}/images/content/table_icon_flash.jpg" width="85" height="62" align="absmiddle">{TR_TITLE_CUSTOM_MENUS}</td>
            <td width="27" align="right" background="{THEME_COLOR_PATH}/images/content/table_background.jpg"><img src="{THEME_COLOR_PATH}/images/content/table_icon_close.jpg" width="27" height="62"></td>
          </tr>
          <tr>
            <td>
			 <table width="100%" cellpadding="5" cellspacing="5">
			 <!-- BDP: page_message -->
              <tr>
                <td width="20">&nbsp;</td>
                <td colspan="4" class="title"><font color="#FF0000">{MESSAGE}</font></td>
                </tr>
              <!-- EDP: page_message -->

			  <tr>
			    <td width="20">&nbsp;</td>
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
                <td width="100" class="{CONTENT}" align="center"><img src="{THEME_COLOR_PATH}/images/icons/edit.gif" width="16" height="16" border="0" align="absmiddle"> <a href="custom_menus.php?edit_id={BUTONN_ID}"  class="link">{TR_EDIT}</a></td>
                <td width="100" class="{CONTENT}" align="center"><img src="{THEME_COLOR_PATH}/images/icons/delete.gif" width="16" height="16" border="0" align="absmiddle"> <a href="#"  onClick="action_delete('custom_menus.php?delete_id={BUTONN_ID}')" class="link">{TR_DELETE}</a></td>
              </tr>
			  <!-- EDP: button_list -->
            </table>
            <br>
			<form name="add_new_button_frm" method="post" action="custom_menus.php">
			<!-- BDP: add_button -->
            <table width="100%" cellpadding="5" cellspacing="5">
              <tr>
                <td width="20">&nbsp;</td>
                <td colspan="2" class="content3"><b>{TR_ADD_NEW_BUTTON}</b></td>
                </tr>
              <tr>
                <td width="20">&nbsp;</td>
                <td width="200" class="content2">{TR_BUTTON_NAME}</td>
                <td class="content"><input name="bname" type="text" class="textinput" id="bname" style="width:210px"></td>
              </tr>
              <tr>
                <td width="20" >&nbsp;</td>
                <td width="200" class="content2">{TR_BUTTON_LINK}</td>
                <td class="content"><input name="blink" type="text" class="textinput" id="blink" style="width:210px"></td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td>
                <td width="200" class="content2">{TR_BUTTON_TARGET}</td>
                <td class="content"><input name="btarget" type="text" class="textinput" id="btarget" style="width:210px"></td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td>
                <td width="200" class="content2">{TR_VIEW_FROM}</td>
                <td  class="content"><select name="bview" id="bview">
                  <option value="reseller">{RESELLER}</option>
                  <option value="user">{USER}</option>
                  <option value="all">{RESSELER_AND_USER}</option>
                                                </select></td>
              </tr>
              <tr>
                <td>&nbsp;</td>
                <td colspan="2"><input name="Button" type="button" class="button" value="  {TR_SAVE}  " onClick="return sbmt(document.forms[0],'new_button');"></td>
                </tr>
            </table>
              <!-- EDP: add_button -->
              <!-- BDP: edit_button -->
<table width="100%" cellpadding="5" cellspacing="5">
              <tr>
                <td width="20">&nbsp;</td>
                <td colspan="2" class="content3"><b>{TR_EDIT_BUTTON}</b></td>
                </tr>
              <tr>
                <td width="20">&nbsp;</td>
                <td width="200" class="content2">{TR_BUTTON_NAME}</td>
                <td class="content"><input name="bname" type="text" class="textinput" id="bname" style="width:210px" value="{BUTON_NAME}"></td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td>
                <td width="200" class="content2">{TR_BUTTON_LINK}</td>
                <td class="content"><input name="blink" type="text" class="textinput" id="blink" style="width:210px" value="{BUTON_LINK}"></td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td>
                <td width="200" class="content2">{TR_BUTTON_TARGET}</td>
                <td class="content"><input name="btarget" type="text" class="textinput" id="btarget" style="width:210px" value="{BUTON_TARGET}"></td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td>
                <td width="200" class="content2">{TR_VIEW_FROM}</td>
                <td  class="content"><select name="bview" id="bview">
                  <option value="reseller" {RESELLER_VIEW}>{RESELLER}</option>
                  <option value="user" {USER_VIEW}>{USER}</option>
                  <option value="all" {ALL_VIEW}>{RESSELER_AND_USER}</option>
                                                </select></td>
              </tr>
              <tr>
                <td>&nbsp;</td>
                <td colspan="2"><input name="Button" type="button" class="button" value="  {TR_SAVE}  " onClick="return sbmt(document.forms[0],'edit_button');"></td>
                </tr>
            </table>
              <input type="hidden" name="eid" value="{EID}">
			<!-- EDP: edit_button -->

			 <input type="hidden" name="uaction" value="">
            </form>
              <!-- end of content -->
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
    </table></td>
  </tr>
  <tr>
    <td height="71"><table width="100%"  border="0" cellspacing="0" cellpadding="0">
        <tr><td width="17"><img src="{THEME_COLOR_PATH}/images/top/down_left.jpg" width="17" height="71"></td><td width="198" valign="top" background="{THEME_COLOR_PATH}/images/top/downlogo_background.jpg"><table width="100%" border="0" cellpadding="0" cellspacing="0" >
          <tr>
            <td width="55"><a href="http://www.vhcs.net" target="_blank"><img src="{THEME_COLOR_PATH}/images/vhcs.gif" alt="" width="51" height="71" border="0"></a></td>
            <td class="bottom">{VHCS_LICENSE}</td>
          </tr>
        </table>          </td>
          <td background="{THEME_COLOR_PATH}/images/top/down_left_fill.jpg"><img src="{THEME_COLOR_PATH}/images/top/down_left_fill.jpg" width="2" height="71"></td><td width="766" background="{THEME_COLOR_PATH}/images/top/middle_background.jpg"><img src="{THEME_COLOR_PATH}/images/top/down_middle_background.jpg" width="766" height="71"></td>
          <td background="{THEME_COLOR_PATH}/images/top/down_right_fill.jpg"><img src="{THEME_COLOR_PATH}/images/top/down_right_fill.jpg" width="3" height="71"></td>
          <td width="9"><img src="{THEME_COLOR_PATH}/images/top/down_right.jpg" width="9" height="71"></td></tr>
    </table></td>
  </tr>
</table>
</body>
</html>
