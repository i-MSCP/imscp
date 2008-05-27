<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_RESELLER_LAYOUT_DATA_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/custom_link_a.gif')">
<!-- BDP: logged_from --><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td height="20" nowrap="nowrap" class="backButton">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.png" width="18" height="18" border="0" align="absmiddle"></a> {YOU_ARE_LOGGED_AS}</td>
      </tr>
    </table>
	<!-- EDP: logged_from -->
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
                      <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_layout.png" width="25" height="25"></td>
                      <td colspan="2" class="title">{TR_LAYOUT_SETTINGS}</td>
                    </tr>
                </table></td>
                <td width="27" align="right">&nbsp;</td>
              </tr>
              <tr>
                <td valign="top"><form enctype="multipart/form-data" name="set_layout" method="post" action="layout.php">
                    <table width="100%" cellspacing="3">
                      <!-- BDP: page_message -->
                      <tr>
                        <td width="40">&nbsp;</td>
                        <td colspan="2" class="title"><span class="message">{MESSAGE}</span></td>
                      </tr>
                      <!-- EDP: page_message -->
                      <tr>
                        <td>&nbsp;</td>
                        <td colspan="2" class="content3"><strong>{TR_UPLOAD_LOGO}</strong></td>
                      </tr>
                      <tr>
                        <td width="40" nowrap="nowrap">&nbsp;</td>
                        <td width="200" class="content2" nowrap="nowrap">{TR_LOGO_FILE}</td>
                        <td class="content" nowrap="nowrap"><input type="file" name="logo_file" size="40">
                        </td>
                      </tr>
                      <tr>
                        <td nowrap="nowrap">&nbsp;</td>
                        <td nowrap="nowrap"><input name="Submit" type="submit" class="button" value=" {TR_UPLOAD} " />
                                   <input type="hidden" name="uaction" value="upload_logo" />
                                   </form></td>
                        <td nowrap="nowrap">
                                   <form method="post" action="layout.php">
                                   <input type="hidden" name="uaction" value="delete_logo" />
                                   <input name="Submit" type="submit" class="button" value=" {TR_REMOVE} " />
                                   </form></td>
                      </tr>
                      <tr>
                        <td nowrap="nowrap">&nbsp;</td>
                        <td colspan="2" nowrap="nowrap">&nbsp;</td>
                      </tr>
                      <tr>
                        <td nowrap="nowrap">&nbsp;</td>
                        <td colspan="2" nowrap="nowrap"><img src="{OWN_LOGO}" alt="reseller logo"></td>
                      </tr>
                    </table>
                    <!-- end of content -->
                </td>
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
