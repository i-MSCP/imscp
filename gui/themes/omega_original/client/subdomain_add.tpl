<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_CLIENT_ADD_SUBDOMAIN_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/jquery.js"></script>
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/jquery.ispcpTooltips.js"></script>
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>

<script type="text/javascript">
/*<![CDATA[*/
	$(document).ready(function(){
		// Tooltips - begin
		$('#dmn_help').ispCPtooltips({msg:"{TR_DMN_HELP}"});
		// Tooltips - end
	});

	function makeUser() {
    	var subname  = document.forms[0].elements['subdomain_name'].value;
    	subname = subname.toLowerCase();
    	document.forms[0].elements['subdomain_mnt_pt'].value = "/" + subname;
	}

	function setRatioAlias(){
		document.forms[0].elements['dmn_type'][1].checked = true;
	}
/*]]>*/
</script>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/ftp_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/email_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif','{THEME_COLOR_PATH}/images/icons/custom_link_a.gif')">

<table width="100%" border="0" cellspacing="0" cellpadding="0" style="height:100%;padding:0;margin:0 auto;">
<!-- BDP: logged_from -->
<tr>
 <td colspan="3" height="20" nowrap="nowrap" class="backButton">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.png" width="16" height="16" border="0" style="vertical-align:middle" alt="" /></a> {YOU_ARE_LOGGED_AS}</td>
</tr>
<!-- EDP: logged_from -->
     <tr>
      <td align="left" valign="top" style="vertical-align: top; width: 195px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_left.jpg" width="195" height="56" border="0" alt="ispCP Logogram" /></td>
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
            <td colspan="3"><table width="100%" border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td align="left"><table width="100%" cellpadding="5" cellspacing="5">
                    <tr>
                      <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_domains.png" width="25" height="25" alt="" /></td>
                      <td colspan="2" class="title">{TR_ADD_SUBDOMAIN}</td>
                    </tr>
                </table></td>
                <td width="27" align="right">&nbsp;</td>
              </tr>
              <tr>
                <td valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td width="40">&nbsp;</td>
                      <td valign="top"><form name="client_add_subdomain_frm" method="post" action="subdomain_add.php">
                          <table width="100%" cellspacing="5">
                            <!-- BDP: page_message -->
                            <tr>
                              <td colspan="2" class="title"><span class="message">{MESSAGE}</span></td>
                            </tr>
                            <!-- EDP: page_message -->
                            <tr>
                              <td width="250" class="content2">
                               <label for="subdomain_name">{TR_SUBDOMAIN_NAME}</label> <img id="dmn_help" src="{THEME_COLOR_PATH}/images/icons/help.png" width="16" height="16" alt="" />
                              </td>
                              <td class="content"><input type="text" name="subdomain_name" id="subdomain_name" value="{SUBDOMAIN_NAME}" style="width:170px" class="textinput" onblur="makeUser();" />
                                <input type="radio" name="dmn_type" value="dmn" {SUB_DMN_CHECKED} />{DOMAIN_NAME}
                                <!-- BDP: to_alias_domain -->
                                <input type="radio" name="dmn_type" value="als" {SUB_ALS_CHECKED} />
                                <select name="als_id" onfocus='setRatioAlias();'>
                                    <!-- BDP: als_list -->
                                    <option value="{ALS_ID}" {ALS_SELECTED}>.{ALS_NAME}</option>
                                    <!-- EDP: als_list -->
                                </select>
                                <!-- EDP: to_alias_domain -->
                                </td>
                            </tr>
                            <tr>
                              <td width="250" class="content2"><label for="subdomain_mnt_pt">{TR_DIR_TREE_SUBDOMAIN_MOUNT_POINT}</label></td>
                              <td class="content"><input type="text" name="subdomain_mnt_pt" id="subdomain_mnt_pt" value="{SUBDOMAIN_MOUNT_POINT}" style="width:170px" class="textinput" /></td>
                            </tr>
                            <tr>
                              <td colspan="2">&nbsp;</td>
                            </tr>
                            <tr>
                              <td colspan="2"><input name="Submit" type="submit" class="button" value="{TR_ADD}" />
                                  <input type="hidden" name="uaction" value="add_subd" /></td>
                            </tr>
                          </table></form></td>
                    </tr>
                </table></td>
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
