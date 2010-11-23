<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_UPLOAD_SOFTWARE_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="{THEME_COLOR_PATH}/css/imscp.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/jquery.js"></script>
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/jquery.imscpTooltips.js"></script>
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/imscp.js"></script>
<script type="text/javascript">
/*<![CDATA[*/
	$(document).ready(function(){
		// Tooltips - begin
		$('a.swtooltip').sw_iMSCPtooltips('a.title');
		// Tooltips - end
	});
	$(document).ready(function(){
		// Tooltips - begin
		$('a.swtooltipstatus').iMSCPtooltips('a.title');
		// Tooltips - end
	});
	function action_delete() {
		if (!confirm("{TR_MESSAGE_DELETE}"))
		return false;
	}
/*]]>*/
</script>
</head>
<body onLoad="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.png','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.png','{THEME_COLOR_PATH}/images/icons/domains_a.png','{THEME_COLOR_PATH}/images/icons/general_a.png' ,'{THEME_COLOR_PATH}/images/icons/manage_users_a.png','{THEME_COLOR_PATH}/images/icons/webtools_a.png','{THEME_COLOR_PATH}/images/icons/statistics_a.png','{THEME_COLOR_PATH}/images/icons/support_a.png')">
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
	    <td colspan="2" style="vertical-align: top;">
		<table style="width: 100%; padding:0;margin:0;" cellspacing="0">
          <tr style="height:95px;">
            <td style="padding-left:30px; width: 100%; background-image: url({THEME_COLOR_PATH}/images/top/middle_bg.jpg);">{MAIN_MENU}</td>
            <td style="padding:0;margin:0;text-align: right; width: 73px;vertical-align: top;"><img src="{THEME_COLOR_PATH}/images/top/middle_right.jpg" width="73" height="95" border="0" alt="" /></td>
          </tr>
          <tr>
            <td colspan="3">
				<table width="100%" border="0" cellspacing="0" cellpadding="0">
          			<tr>
            			<td align="left">
							<table width="100%" cellpadding="5" cellspacing="5">
								<tr>
									<td width="25"><img src="{THEME_COLOR_PATH}/images/icons/cd_big.png" width="25" height="25" /></td>
									<td colspan="2" class="title">{TR_UPLOADED_SOFTWARE}</td>
								</tr>
							</table>			
						</td>
            			<td width="27" align="right">&nbsp;</td>
          			</tr>
          			<tr>
            			<td>
                        	<table width="100%"  border="0" cellspacing="0" cellpadding="0">
                    			<tr>
                  					<td width="25">&nbsp;</td>
                  					<td valign="top">
                                    <table width="100%" cellspacing="7">
                    				<!-- BDP: page_message -->
									<tr>
                      					<td colspan="6" nowrap class="title"><font color="#FF0000">{MESSAGE}</font></td>
                    				</tr>
									<!-- EDP: page_message -->

                      <tr>
                      <td nowrap class="content3"><div style="float:left"><b>{TR_SOFTWARE_NAME}</b></div><div style="float:right"><a href="{TR_SOFTWARE_NAME_ASC}"><img src="{THEME_COLOR_PATH}/images/icons/asc.gif" width="16" height="16" border="0" /></a><a href="{TR_SOFTWARE_NAME_DESC}"><img src="{THEME_COLOR_PATH}/images/icons/desc.gif" width="16" height="16" border="0" /></a></div></td>
					  <td nowrap class="content3" width="100"><b>{TR_SOFTWARE_VERSION}</b></td>
					  <td nowrap class="content3" width="130"><div style="float:left"><b>{TR_SOFTWARE_LANGUAGE}</b></div><div style="float:right"><a href="{TR_LANGUAGE_ASC}"><img src="{THEME_COLOR_PATH}/images/icons/asc.gif" width="16" height="16" border="0" /></a><a href="{TR_LANGUAGE_DESC}"><img src="{THEME_COLOR_PATH}/images/icons/desc.gif" width="16" height="16" border="0" /></a></div></td>
					  <td nowrap class="content3" width="130"><div style="float:left"><b>{TR_SOFTWARE_STATUS}</b></div><div style="float:right"><a href="{TR_SOFTWARE_STATUS_ASC}"><img src="{THEME_COLOR_PATH}/images/icons/asc.gif" width="16" height="16" border="0" /></a><a href="{TR_SOFTWARE_STATUS_DESC}"><img src="{THEME_COLOR_PATH}/images/icons/desc.gif" width="16" height="16" border="0" /></a></div></td>
                      <td nowrap class="content3" width="130"><div style="float:left"><b>{TR_SOFTWARE_TYPE}</b></div><div style="float:right"><a href="{TR_SOFTWARE_TYPE_ASC}"><img src="{THEME_COLOR_PATH}/images/icons/asc.gif" width="16" height="16" border="0" /></a><a href="{TR_SOFTWARE_TYPE_DESC}"><img src="{THEME_COLOR_PATH}/images/icons/desc.gif" width="16" height="16" border="0" /></a></div></td>
                      <td nowrap class="content3" align="center" width="100"><b>{TR_SOFTWARE_DELETE}</b></td>
                    </tr>
                    <!-- BDP: no_software_list -->
                    <tr>
                      <td colspan="6" class="title"><font color="#FF0000">{NO_SOFTWARE}</font></td>
                    </tr>
                    <!-- EDP: no_software_list -->
                    <!-- BDP: list_software -->
                    <tr>
                      <td nowrap class="content"><img src="{THEME_COLOR_PATH}/images/icons/cd.png" width="16" height="16" align="middle" />&nbsp;<a href="#" class="swtooltip" title="{SW_DESCRIPTION}"><font color="{LINK_COLOR}">{SW_NAME}</font></a></td>
					  <td nowrap class="content">{SW_VERSION}</td>
					  <td nowrap class="content">{SW_LANGUAGE}</td>
					  <td nowrap class="content"><a href="#" class="swtooltipstatus" title="{SW_INSTALLED}"><font color="{LINK_COLOR}">{SW_STATUS}</font></a></td>
                      <td nowrap class="content">{SW_TYPE}</td>
                      <td nowrap class="content" align="center"><img src="{THEME_COLOR_PATH}/images/icons/{SOFTWARE_ICON}.png" width="16" height="16" border="0" align="middle" /> <a href="{DELETE}" class="link" onclick="return action_delete()">{TR_DELETE}</a></td>
                    </tr>
                    <!-- EDP: list_software -->
                    <tr>
                      <td colspan="6" align="right" nowrap class="content3">{TR_SOFTWARE_COUNT}:&nbsp;<b>{TR_SOFTWARE_NUM}</b></td>
                    </tr>
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
	  <tr>
      <td colspan="2" align="left">
      <!-- BDP: t_software_support -->
<table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td align="left">
<table width="100%" cellpadding="5" cellspacing="5">
	<tr>
		<td width="25"><img src="{THEME_COLOR_PATH}/images/icons/cd_big.png" width="25" height="25" /></td>
		<td colspan="3" class="title">{TR_UPLOAD_SOFTWARE}</td>
	</tr>
</table>			
			</td>
            <td width="27" align="right">&nbsp;</td>
          </tr>
          <tr>
            <td>
            <!-- BDP: t_software_support -->
			<table width="100%"  border="0" cellspacing="0" cellpadding="0">
                <tr>
                  <td width="25">&nbsp;</td>
                  <td valign="top">
		  <form action="software_upload.php" method="post" enctype="multipart/form-data">
		  <table width="100%" cellspacing="7">
		    <tr>
                      <td nowrap class="content3" colspan="3"><b>{TR_UPLOAD_SOFTWARE}</b></td>
                    </tr>
		    <tr>
		      <td  class="content2" width="200">{TR_SOFTWARE_FILE}</td>
		      <td  class="content"><input type="file" name="sw_file" class="textinput" size="60" /></td>
		    </tr>
		    <tr>
		      <td  class="content2" width="200">{TR_SOFTWARE_URL}</td>
		      <td  class="content"><input type="text" name="sw_wget" value="{VAL_WGET}" size="60" class="textinput" /></td>
		    </tr>
                    <tr>
                      <td colspan="3" nowrap><input name="upload" type="submit" class="button" value="{TR_UPLOAD_SOFTWARE_BUTTON}" /><input type="hidden" name="send_software_upload_token" id="send_software_upload_token" value="{SOFTWARE_UPLOAD_TOKEN}" /></td>
                    </tr>
                  </table>
		  </form>
                  </td>
                </tr>
            </table>
            <!-- EDP: t_software_support -->
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
        </table>
	  </td>
	</tr>
</table>
</body>
</html>
