<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_ADMIN_CHANGE_LAYOUT_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/vhcs.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/vhcs.js"></script>
<script>
<!--

function sbmt(form, uaction) {

    form.uaction.value = uaction;
    form.submit();

    return false;

}
//-->
</script>
</head>

<body onLoad="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif','{THEME_COLOR_PATH}/images/icons/logout_a.gif','{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<table width="100%" border="0" cellspacing="0" cellpadding="0" height="100%" style="border-collapse: collapse;padding:0;margin:0;">
	<tr>
		<td align="left" valign="top" style="vertical-align: top; width: 195px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_left.jpg" border="0"></td>
		<td style="height: 56px; width: 785px;"><img src="{THEME_COLOR_PATH}/images/top/top_left_bg.jpg" border="0"></td>
		<td style="width:100%; background-image: url({THEME_COLOR_PATH}/images/top/top_bg.jpg)">&nbsp;</td>
		<td style="width: 73px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_right.jpg" border="0"></td>
	</tr>
	<tr>
		<td style="width: 195px; vertical-align: top;">{MENU}</td>
	    <td colspan=3 style="vertical-align: top;"><table style="width: 100%; border-collapse: collapse;padding:0;margin:0;">
				<tr height="95";>
				  <td style="padding-left:30px; width: 100%; background-image: url({THEME_COLOR_PATH}/images/top/middle_bg.jpg);"><span style="width: 195px; vertical-align: top;">{MAIN_MENU}</span></td>
					<td style="padding:0;margin:0;text-align: right; width: 73px;vertical-align: top;"><img src="{THEME_COLOR_PATH}/images/top/middle_right.jpg" border="0"></td>
				</tr>
				<tr height="*">
				  <td colspan=3><table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td align="left"><table width="100%" cellpadding="5" cellspacing="5">
                          <tr>
                            <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_layout.png" width="25" height="25"></td>
                            <td colspan="2" class="title">{TR_LAYOUT_SETTINGS}</td>
                          </tr>
                      </table></td>
                      <td width="27" align="right">&nbsp;</td>
                    </tr>
                    <tr>
                      <td><!-- BDP: props_list -->
                          <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                              <td width="40">&nbsp;</td>
                              <td align="left"><form enctype="multipart/form-data" name="set_layout" method="post" action="layout.php">
                                  <!-- BDP: page_message -->
                                  <div align="lext" class="title">
                                  <font color="#FF0000">{MESSAGE}</font></span>
                                  <!-- EDP: page_message -->
                                  <table width="100%" cellpadding="5" cellspacing="5">
                                    <tr>
                                      <td colspan="2" class="content3"><b>{TR_UPLOAD_LOGO}</b></td>
                                    </tr>
                                    <tr>
                                      <td width="230" class="content2" nowrap>{TR_LOGO_FILE}</td>
                                      <td class="content" nowrap><input type="file" name="logo_file">
                                      </td>
                                    </tr>
                                  </table>
                                <input name="Button" type="button" class="button" value="  {TR_SAVE}  " onClick="return sbmt(document.forms[0],'upload_logo');">
                                  <input type="hidden" name="uaction" value="">
                                </form>
                                  <!-- end of content -->
                                  <p><img src="{ISP_LOGO}" alt="reseller logo"><br>
                                </p></td>
                            </tr>
                          </table>
                        <!-- EDP: props_list -->
                      </td>
                      <td>&nbsp;</td>
                    </tr>
                    <tr>
                      <td>&nbsp;</td>
                      <td>&nbsp;</td>
                    </tr>
                  </table></td>
				</tr>
			</table>
			
	    <p>&nbsp;</p></td>
	</tr>
</table>
</body>
</html>
