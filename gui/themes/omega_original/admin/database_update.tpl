<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_ADMIN_ISPCP_UPDATES_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex">
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
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
				  <td colspan="3">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td align="left">
<table width="100%" cellpadding="5" cellspacing="5">
	<tr>
		<td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_update.png" width="25" height="25"></td>
		<td colspan="2" class="title">{TR_UPDATES_TITLE}</td>
	</tr>
</table>
	</td>
    <td width="27" align="right">&nbsp;</td>
  </tr>
  <tr>
    <td valign="top"><!-- BDP: props_list -->
        <table width="100%" cellpadding="5" cellspacing="5">
          <tr>
            <td width="25">&nbsp;</td>
            <td colspan="2" class="content3"><b>{TR_AVAILABLE_UPDATES}</b></td>
          </tr>
          <!-- BDP: database_update_message -->
          <tr>
            <td width="25">&nbsp;</td>
            <td colspan="2">{TR_UPDATE_MESSAGE}</td>
          </tr>
          <!-- EDP: database_update_message -->
          <!-- BDP: database_update_infos -->
          <tr>
            <td width="25">&nbsp;</td>
            <td width="200" class="content2">{TR_UPDATE}</td>
            <td class="content">{UPDATE}</td>
          </tr>
          <tr>
            <td width="25">&nbsp;</td>
            <td class="content2">{TR_INFOS}</td>
            <td class="content">{INFOS}</td>
          </tr>
          <tr>
           <td width="25">&nbsp;</td>
 	   <form name='database_update' action='database_update.php' method='POST' enctype='application/x-www-form-urlencoded'>
	    <input type='hidden' name='execute' id='execute' value='true'>
	    <td class="content">&nbsp;</td>
    	    <td class="content2" align='left'><input type='submit' name='submit' value='{TR_EXECUTE_UPDATE}'></td>
	   </form>
          </tr>
      <!-- EDP: database_update_infos -->
      </table>
      <br />
        <!-- EDP: props_list --></td>
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
