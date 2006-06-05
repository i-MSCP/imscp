<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_HTACCESS}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/vhcs.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/vhcs.js"></script>
</head>

<body onLoad="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/ftp_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif','{THEME_COLOR_PATH}/images/icons/logout_a.gif','{THEME_COLOR_PATH}/images/icons/email_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<table width="100%" border="0" cellspacing="0" cellpadding="0" height="100%">
  <tr>
    <td height="80" align="left" valign="top">
	<!-- BDP: logged_from --><table width="100%"  border="00" cellspacing="0" cellpadding="0">
      <tr>
        <td height="20" nowrap background="{THEME_COLOR_PATH}/images/button.gif">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.gif" width="18" height="18" border="0" align="absmiddle"></a> <font color="red">{YOU_ARE_LOGGED_AS}</font> </td>
      </tr>
    </table>
	<!-- EDP: logged_from --><table width="100%"  border="0" cellspacing="0" cellpadding="0">
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
        <td valign="top">
		<form name="puser_assign" method="post" action="puser_assign.php?uname={UNAME}">
		<table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td height="62" align="left" background="{THEME_COLOR_PATH}/images/content/table_background.jpg" class="title"><img src="{THEME_COLOR_PATH}/images/content/table_icon_users.jpg" width="85" height="62" align="absmiddle">{TR_USER_ASSIGN}</td>
              <td width="27" align="right" background="{THEME_COLOR_PATH}/images/content/table_background.jpg"><img src="{THEME_COLOR_PATH}/images/content/table_icon_close.jpg" width="27" height="62"></td>
            </tr>
            <tr>
              <td valign="top"><table width="100%" cellpadding="5" cellspacing="5">
				  <!-- BDP: page_message -->
				  <tr>
				    <td>&nbsp;</td>
				    <td colspan="4" class="title"><font color="#FF0000">{MESSAGE}</font></td>
				    </tr>
					<!-- EDP: page_message -->
				  <tr>
				    <td>&nbsp;</td>
				    <td colspan="3" class="content3"><strong>{UNAME}</strong></td>
				    </tr>
				  <tr>
                      <td width="18">&nbsp;</td>
                      <td width="200" class="content2"> {TR_MEMBER_OF_GROUP}</td>
                      <td class="content"><select name="groups_in">
						<!-- BDP: already_in -->
						<option value="{GRP_IN_ID}">{GRP_IN}</option>
                      	<!-- EDP: already_in -->
					  </select></td>
                      <td class="content">
					  <!-- BDP: remove_button -->
					  <input name="Submit" type="submit" class="button" value="  {TR_REMOVE}  " onClick="return sbmt(document.forms[0],'remove');">
					  <!-- EDP: remove_button -->
					  </td>
				  </tr>
                    <tr>
                      <td>&nbsp;</td>
                      <td class="content2"> {TR_SELECT_GROUP}</td>
                      <td class="content">
					  <select name="groups">
					  
					  <!-- BDP: grp_avlb -->
					<option value="{GRP_ID}">{GRP_NAME}</option>
                      <!-- EDP: grp_avlb -->
					  
					  </select>
					  </td>
                      <td class="content">
					  <!-- BDP: add_button -->
					  <input name="Submit" type="submit" class="button" value="  {TR_ADD}  " onClick="return sbmt(document.forms[0],'add');">
					  <!-- EDP: add_button -->
					  </td>
                    </tr>
                    <tr>
                      <td>&nbsp;</td>
                      <td colspan="3">
					  	<input type="hidden" name="nadmin_name" value="{UID}">   
                        <input type="hidden" name="uaction" value="">
                        <input name="Submit" type="submit" class="button" value="  {TR_BACK}  " onClick="MM_goToURL('parent','puser_manage.php');return document.MM_returnValue"> 
                       
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
          </form>
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
