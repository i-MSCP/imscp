<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_EDIT_DOMAIN_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/vhcs.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/vhcs.js"></script>
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
<body onLoad="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif','{THEME_COLOR_PATH}/images/icons/logout_a.gif','{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<table width="100%" border="0" cellspacing="0" cellpadding="0" height="100%">
  <tr>
    <td height="80" align="left" valign="top">
	<!-- BDP: logged_from --><table width="100%"  border="00" cellspacing="0" cellpadding="0">
      <tr>
        <td height="20" nowrap background="{THEME_COLOR_PATH}/images/button.gif">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.gif" width="18" height="18" border="0" align="absmiddle"></a> <font color="red">{YOU_ARE_LOGGED_AS}</font> </td>
      </tr>
    </table>
	<!-- EDP: logged_from -->
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
            <td height="62" align="left" background="{THEME_COLOR_PATH}/images/content/table_background.jpg" class="title"><img src="{THEME_COLOR_PATH}/images/content/table_icon_domains.jpg" width="85" height="62" align="absmiddle">{TR_EDIT_DOMAIN}</td>
            <td width="27" align="right" background="{THEME_COLOR_PATH}/images/content/table_background.jpg"><img src="{THEME_COLOR_PATH}/images/content/table_icon_close.jpg" width="27" height="62"></td>
          </tr>
          <tr>
            <td>
			<form name="reseller_edit_domain_frm" method="post" action="edit_domain.php">
			<table width="100%" cellpadding="5" cellspacing="5">
              <tr>
                <td width="20" align="left">&nbsp;</td> 
                <td colspan="2" align="left" class="content3"><b>{TR_DOMAIN_PROPERTIES}</b></td>
                </tr>
				<!-- BDP: page_message -->
              <tr>
                <td>&nbsp;</td>
                <td colspan="2" class="title"><font color="#FF0000">{MESSAGE}</font></td>
              </tr>
			  <!-- EDP: page_message -->
              <tr>
                <td width="20">&nbsp;</td> <td class="content2" width="193">{TR_DOMAIN_NAME}</td>
                <td class="content">{VL_DOMAIN_NAME}</td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td> <td class="content2" width="193">{TR_DOMAIN_IP}</i></td>
                <td class="content">{VL_DOMAIN_IP}
				<!--
				<select name="domain_ip">
                      
                      <option value="{IP_VALUE}" {IP_SELECTED}>{IP_NUM}&nbsp;({IP_NAME})</option>
                      
                    </select>
				-->
				
				
				</td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td> <td class="content2" width="193">{TR_PHP_SUPP}</i></b> </td>
                <td class="content"><select name="domain_php" id="domain_php">
                  <option value="yes" {PHP_YES}>{TR_YES}</option>
                  <option value="no" {PHP_NO}>{TR_NO}</option>
                </select>                  </td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td> <td class="content2" width="193">{TR_CGI_SUPP}</td>
                <td class="content"><select name="domain_cgi" id="domain_cgi">
				<option value="yes" {CGI_YES}>{TR_YES}</option>
                  <option value="no" {CGI_NO}>{TR_NO}</option>
                                </select>                  </td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td> <td class="content2" width="193">{TR_SUBDOMAINS}</td>
                <td class="content"> <input type="text" name=dom_sub value="{VL_DOM_SUB}" style="width:100px" class="textinput">
                </td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td> <td class="content2" width="193">{TR_ALIAS}</td>
                <td class="content"> <input type="text" name=dom_alias value="{VL_DOM_ALIAS}" style="width:100px" class="textinput">
                </td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td> <td class="content2" width="193">{TR_MAIL_ACCOUNT}</td>
                <td class="content"> <input type="text" name=dom_mail_acCount value="{VL_DOM_MAIL_ACCOUNT}" style="width:100px" class="textinput">
                </td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td> <td class="content2" width="193">{TR_FTP_ACCOUNTS}</td>
                <td class="content"> <input type="text" name=dom_ftp_acCounts value="{VL_FTP_ACCOUNTS}" style="width:100px" class="textinput">
                </td>
              </tr>
              <tr>
                <td width="20">&nbsp;</td> <td class="content2" width="193">{TR_SQL_DB}</td>
                <td class="content"> <input type="text" name=dom_sqldb value="{VL_SQL_DB}" style="width:100px" class="textinput">
                </td>
              </tr>
			  <tr>
			    <td width="20">&nbsp;</td> <td class="content2" width="193">{TR_SQL_USERS}</td>
                <td class="content"> <input type="text" name=dom_sql_users value="{VL_SQL_USERS}" style="width:100px" class="textinput">
                </td>
              </tr>
			  <tr>
			    <td width="20">&nbsp;</td> <td class="content2" width="193">{TR_TRAFFIC}</td>
                <td class="content"> <input type="text" name=dom_traffic value="{VL_TRAFFIC}" style="width:100px" class="textinput">
                </td>
              </tr>
			  <tr>
			    <td width="20">&nbsp;</td> <td class="content2" width="193">{TR_DISK}</td>
                <td class="content"> <input type="text" name=dom_disk value="{VL_DOM_DISK}" style="width:100px" class="textinput">
                </td>
              </tr>
			  <tr>
			    <td width="20">&nbsp;</td> <td class="content2" width="193">{TR_USER_NAME}</td>
                <td class="content">{VL_USER_NAME}</td>
              </tr>
			  <tr>
			    <td>&nbsp;</td>
			    <td colspan="2"><input name="Submit" type="submit" class="button" value="{TR_UPDATE_DATA}">&nbsp;&nbsp;&nbsp;
				<input name="Submit" type="submit" class="button" onClick="MM_goToURL('parent','users.php');return document.MM_returnValue" value=" {TR_CANCEL} "></td>
			    </tr>
              <tr> 
                <td colspan="3"> 
                  <input type="hidden" name="uaction" value="sub_data">
                </td>
              </tr>
            </table>
			
			</form>
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
