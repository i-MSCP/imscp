<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_CLIENT_INSTALL_SOFTWARE_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="{THEME_COLOR_PATH}/css/imscp.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/imscp.js"></script>
<script language="JavaScript" type="text/JavaScript">
/*<![CDATA[*/
function OpenTree()
{
  libwindow=window.open("ftp_choose_dir.php","Hello","menubar=no,width=470,height=350,scrollbars=yes");
  
}
function set_installpath() {
	var inputvars = document.forms[0].elements['selected_domain'].value;
	inputvars = inputvars.toLowerCase();
	var splitinputvars = inputvars.split(";");
	document.forms[0].elements['other_dir'].value = splitinputvars[4];
}
/*]]>*/
</script>
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
		<td colspan="2" class="title">{TR_INSTALL_SOFTWARE}</td>
	</tr>
</table>
			</td>
            <td width="27" align="right">&nbsp;</td>
          </tr>
          <tr>
            <td><table width="100%"  border="00" cellspacing="0" cellpadding="0">
                <tr>
                  <td width="25">&nbsp;</td>
                  <td valign="top">
		    <form method="post" action="{SOFTWARE_INSTALL_BUTTON}">
		    <table width="100%" cellspacing="7">
                    <!-- BDP: page_message -->
		    <tr>
                      <td nowrap colspan="2" class="title"><font color="#FF0000">{MESSAGE}</font></td>
                    </tr>
		    <!-- EDP: page_message -->
                    <tr>
                      <td nowrap colspan="2" class="content3"><b>{TR_INSTALLATION}</b></td>
                    </tr>
                    <!-- BDP: software_item -->
                    <tr>
                      <td  class="content2" width="200">{TR_NAME}</td>
		      <td  class="content">{TR_SOFTWARE_NAME}</td>
		    </tr>
		    <tr>
		      <td  class="content2" width="200">{TR_TYPE}</td>
		      <td  class="content">{SOFTWARE_TYPE}</td>
		    </tr>
		    <tr>
		      <td  class="content2" width="200">{TR_DB}</td>
		      <td  class="content">{SOFTWARE_DB}</td>
		    </tr>
			<tr>
		      <td  class="content2" width="200">{TR_SELECT_DOMAIN}</td>
		      <td  class="content">
			  <select name="selected_domain" id="selected_domain" onChange="set_installpath();">
				<option value="{DOMAINSTANDARD_NAME_VALUES}">{DOMAINSTANDARD_NAME}</option>
				<!-- BDP: show_domain_list -->
				<option {SELECTED_DOMAIN} value="{DOMAIN_NAME_VALUES}">{DOMAIN_NAME}</option>
				<!-- EDP: show_domain_list -->
			</select>
			  </td>
		    </tr>
		    <tr>
		      <td  class="content2" width="200">{TR_PATH}</td>
		      <td  class="content"><input type="text" name="other_dir" value="{VAL_OTHER_DIR}" style="width:170px" class="textinput" />&nbsp;&nbsp;&nbsp;<a href="javascript:OpenTree();" class="link">{CHOOSE_DIR}</a>&nbsp;&nbsp;(<input type="checkbox" name="createdir" value="1"{CHECKED_CREATEDIR} />&nbsp;{CREATEDIR_MESSAGE})</td>
		    </tr>
			<!-- BDP: require_installdb -->
		    <tr>
		      <td  class="content2" width="200">{TR_SELECT_DB}</td>
		      <td  class="content">
			<!-- BDP: select_installdb -->
			<select name="selected_db" id="selected_db">
				<!-- BDP: installdb_item -->
				<option {SELECTED_DB} value="{DB_NAME}">{DB_NAME}</option>
				<!-- EDP: installdb_item -->
			</select> 
			&nbsp;&nbsp;&nbsp;
			<!-- EDP: select_installdb -->
			<!-- BDP: create_db -->
				&nbsp;<input name="Submit3" type="submit" class="button" onClick="MM_goToURL('parent','{ADD_DB_LINK}');return document.MM_returnValue" value="{BUTTON_ADD_DB}" /><!-- BDP: create_message_db -->&nbsp;<font color="#FF0000">{ADD_DATABASE_MESSAGE}</font><!-- EDP: create_message_db -->
			<!-- EDP: create_db -->
		      </td>
		    </tr>
		    <tr>
		      <td  class="content2" width="200">{TR_SQL_USER}</td>
		      <td  class="content"><!-- BDP: select_installdbuser -->
			<select name="sql_user" id="sql_user">
				<!-- BDP: installdbuser_item -->
				<option {SELECTED_DBUSER} value="{SQLUSER_NAME}">{SQLUSER_NAME}</option>
				<!-- EDP: installdbuser_item -->
			</select> 
			&nbsp;&nbsp;&nbsp;
			<!-- EDP: select_installdbuser -->
			<!-- BDP: create_message_db -->
			<font color="#FF0000">{ADD_DATABASE_MESSAGE}</font>
			<!-- EDP: create_message_db -->
			<!-- BDP: softwaredbuser_message -->
			<font color="{STATUS_COLOR}">{SQLUSER_STATUS_MESSAGE}</font>
			<!-- EDP: softwaredbuser_message -->
			  </td>
		    </tr>
			<!-- EDP: require_installdb -->
			<tr>
               <td nowrap colspan="2" class="content3"><b>{TR_INSTALLATION_INFORMATION}</b></td>
            </tr>
			<tr>
		      <td  class="content2" width="200">{TR_INSTALL_USER}</td>
		      <td  class="content"><input type="text" name="install_username" value="{VAL_INSTALL_USERNAME}" style="width:170px" class="textinput" /></td>
		    </tr>
		    <tr>
		      <td  class="content2" width="200">{TR_INSTALL_PWD}</td>
		      <td  class="content"><input type="password" name="install_password" value="{VAL_INSTALL_PASSWORD}" style="width:170px" class="textinput" /></td>
		    </tr>
			<tr>
		      <td  class="content2" width="200">{TR_INSTALL_EMAIL}</td>
		      <td  class="content"><input type="text" name="install_email" value="{VAL_INSTALL_EMAIL}" style="width:170px" class="textinput" /></td>
		    </tr>
		    <tr>
		      <td colspan="2">
				<input name="Submit" type="submit" class="button" onClick="MM_goToURL('parent','software.php');return document.MM_returnValue" value="{TR_BACK}" />
				&nbsp;&nbsp;&nbsp;
                    		<!-- BDP: software_install -->
				<input name="Submit2" type="submit" class="button" value="{TR_INSTALL}" />
                   		<!-- EDP: software_install -->
		      </td>
		    </tr>
                    <!-- EDP: software_item -->
                  </table>
		  </form>
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
