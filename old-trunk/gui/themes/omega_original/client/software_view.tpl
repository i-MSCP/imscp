<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_CLIENT_VIEW_SOFTWARE_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<link href="{THEME_COLOR_PATH}/css/imscp.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/imscp.js"></script>
</head>
<body onLoad="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.png','{THEME_COLOR_PATH}/images/icons/domains_a.png','{THEME_COLOR_PATH}/images/icons/ftp_a.png','{THEME_COLOR_PATH}/images/icons/general_a.png','{THEME_COLOR_PATH}/images/icons/email_a.png','{THEME_COLOR_PATH}/images/icons/webtools_a.png','{THEME_COLOR_PATH}/images/icons/statistics_a.png','{THEME_COLOR_PATH}/images/icons/support_a.png')">
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="height:100%;padding:0;margin:0 auto;">
<!-- BDP: logged_from -->
<tr>
 <td colspan="3" height="20" nowrap="nowrap" class="backButton">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.png" width="16" height="16" border="0" style="vertical-align:middle" alt="" /></a> {YOU_ARE_LOGGED_AS}</td>
</tr>
<!-- EDP: logged_from -->
<tr>
<td align="left" valign="top" style="vertical-align: top; width: 195px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_left.jpg" width="195" height="56" border="0" alt="i-MSCP Logogram" /></td>
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
            <td colspan=3>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td align="left">
<table width="100%" cellpadding="5" cellspacing="5">
	<tr>
		<td width="25"><img src="{THEME_COLOR_PATH}/images/icons/cd_big.png" width="25" height="25" /></td>
		<td colspan="2" class="title">{TR_VIEW_SOFTWARE}</td>
	</tr>
</table>			
			</td>
            <td width="27" align="right">&nbsp;</td>
          </tr>
          <tr>
            <td><table width="100%"  border="00" cellspacing="0" cellpadding="0">
                <tr>
                  <td width="25">&nbsp;</td>
                  <td valign="top"><table width="100%" cellspacing="7">
                    <!-- BDP: page_message -->
					<tr>
                      <td nowrap class="title"><font color="#FF0000">{MESSAGE}</font></td>
                      </tr>
                      <!-- EDP: page_message -->
                    <tr>
                      <td nowrap class="content3"><b>{TR_SOFTWARE_DESCRIPTION}</b></td>
                    </tr>
                    <!-- BDP: software_item -->
                    <tr>
                      <td nowrap class="content">
			<table width="100%"  border="00" cellspacing="0" cellpadding="6">
				<tr>
					<td width="150"><b>{TR_NAME}:</b></td>
					<td>{SOFTWARE_NAME}</td>
				</tr>
				<tr>
					<td width="150"><b>{TR_VERSION}:</b></td>
					<td>{SOFTWARE_VERSION}</td>
				</tr>
				<tr>
					<td width="150"><b>{TR_LANGUAGE}:</b></td>
					<td>{SOFTWARE_LANGUAGE}</td>
				</tr>
				<tr>
					<td><b>{TR_TYPE}:</b></td>
					<td>{SOFTWARE_TYPE}</td>
				</tr>
				<tr>
					<td><b>{TR_DB}:</b></td>
					<td><font color="{STATUS_COLOR}">{SOFTWARE_DB}</font></td>
				</tr>
				<!-- BDP: software_message -->
				<tr>
					<td colspan="2">{STATUS_MESSAGE}</td>
				</tr>
				<!-- EDP: software_message -->
				<tr>
					<td colspan="2"><b>{TR_DESC}:</b><br />
					<div style="text-align: justify;">{SOFTWARE_DESC}</div>
				<tr>
					<td><b>{TR_LINK}:</b></td>
					<td><a href="{SOFTWARE_LINK}" target="_blank" class="link">{SOFTWARE_LINK}</a></td>
				</tr>
				<!-- BDP: installed_software_info -->
				<tr>
                  <td colspan="2">&nbsp;</td>
				</tr>
				<tr>
                      <td colspan="2" nowrap class="content3"><b>{TR_SOFTWARE_INFO}</b></td>
                </tr>
				<tr>
                  <td width="150">{TR_SOFTWARE_STATUS}</td>
				  <td>{SOFTWARE_STATUS}</td>
				</tr>
				<tr>
                  <td width="150">{TR_SOFTWARE_INSTALL_PATH}</td>
				  <td>{SOFTWARE_INSTALL_PATH}</td>
				</tr>
				<tr>
                  <td width="150">{TR_SOFTWARE_INSTALL_DATABASE}</td>
				  <td>{SOFTWARE_INSTALL_DATABASE}</td>
				</tr>
				<!-- EDP: installed_software_info -->
			</table>
		      </td>
                    </tr>
		    <tr>
		      <td>
			<form name="buttons" method="post" action="#">
				<input name="Submit" type="submit" class="button" onclick="MM_goToURL('parent','software.php');return document.MM_returnValue" value="{TR_BACK}" />
				&nbsp;&nbsp;&nbsp;
                    		<!-- BDP: software_install -->
				<input name="Submit2" type="submit" class="button" onclick="MM_goToURL('parent','{SOFTWARE_INSTALL_BUTTON}');return document.MM_returnValue" value="{TR_INSTALL}" />
                   		<!-- EDP: software_install -->
			</form>
                    <!-- EDP: software_item -->
                  </table>
                    </td>
                </tr>
            </table></td>
            <td>&nbsp;</td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
          </tr>
        </table>			
			</td>
          </tr>
        </table>
	  </td>
	</tr>
</table>
</body>
</html>
