<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_CLIENT_ADD_FTP_ACC_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/vhcs.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/vhcs.js"></script>
<script language="JavaScript" type="text/JavaScript">
<!--
<!--
function over(number) {
  document.images["image"+number+"_1"].src='{THEME_COLOR_PATH}/images/bars/menu_button_left.gif';
  document.images["image"+number+"_2"].src='{THEME_COLOR_PATH}/images/bars/menu_button_right.gif';
  if (document.layers) {
    document.layers["m"+number].background.src='{THEME_COLOR_PATH}/images/bars/menu_button_background.gif';
  }
  else if (document.all) {
    window.document.all["id"+number].style.backgroundImage = 'url({THEME_COLOR_PATH}/images/bars/menu_button_background.gif)';
  }
}
function out(number) {
  document.images["image"+number+"_1"].src='../images/menubutton_left.gif';
  document.images["image"+number+"_2"].src='../images/menubutton_right.gif';
  if (document.layers) {
    document.layers["m"+number].background.src='../images/menubutton_background.gif';
  }
  else if (document.all) {
    window.document.all["id"+number].style.backgroundImage = 'url(../images/menubutton_background.gif)';
  }
}

function MM_jumpMenu(targ,selObj,restore){ //v3.0
  eval(targ+".location='"+selObj.options[selObj.selectedIndex].value+"'");
  if (restore) selObj.selectedIndex=0;
}

function sbmt(form, uaction) {

    form.uaction.value = uaction;
    form.submit();
    
    return false;

}

			
			

<!-- BDP: js_to_all_domain -->
    function begin_js(){
			document.forms[0].sub_id.disabled = true;
            document.forms[0].als_id.disabled = true;
            document.forms[0].username.focus();
    }

    
    function changeDom(wath) {

        if (wath == "real"){
            document.forms[0].sub_id.disabled = true;
            document.forms[0].als_id.disabled = true;
        }
        else if(wath == "subdom"){
            document.forms[0].sub_id.disabled = false;
            document.forms[0].als_id.disabled = true;
        }
        else {
            document.forms[0].sub_id.disabled = true;
			
            document.forms[0].als_id.disabled = false;
        }
    }
<!-- EDP: js_to_all_domain -->

<!-- BDP: js_not_domain -->
    function begin_js(){
            document.forms[0].username.focus();
    }

<!-- EDP: js_not_domain -->


<!-- BDP: js_to_subdomain -->
function begin_js(){
			document.forms[0].sub_id.disabled = true;
            document.forms[0].username.focus();
    }

    
    function changeDom(wath) {

        if (wath == "real"){
            document.forms[0].sub_id.disabled = true;
        }
        else if(wath == "subdom"){
            document.forms[0].sub_id.disabled = false;
        }
        else {
            document.forms[0].sub_id.disabled = true;
        }
    }
<!-- EDP: js_to_subdomain -->


<!-- BDP: js_to_alias_domain -->
    function begin_js(){
            document.forms[0].als_id.disabled = true;
            document.forms[0].username.focus();
    }

    
    function changeDom(wath) {

        if (wath == "real"){
            document.forms[0].als_id.disabled = true;
        }
        else if(wath == "subdom"){
            document.forms[0].als_id.disabled = true;
        }
        else {
            document.forms[0].als_id.disabled = false;
        }
    }
<!-- EDP: js_to_alias_domain -->
function OpenTree()
{
  libwindow=window.open("ftp_choose_dir.php","Hello","menubar=no,width=470,height=350,scrollbars=yes");
  
}
//-->
</script>
</head>

<body onLoad="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/ftp_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif','{THEME_COLOR_PATH}/images/icons/logout_a.gif','{THEME_COLOR_PATH}/images/icons/email_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif'); begin_js();">
<!-- BDP: logged_from --><table width="100%"  border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td height="20" nowrap background="{THEME_COLOR_PATH}/images/button.gif">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.gif" width="18" height="18" border="0" align="absmiddle"></a> <font color="red">{YOU_ARE_LOGGED_AS}</font> </td>
      </tr>
    </table>
	<!-- EDP: logged_from -->
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
            <td style="padding-left:30px; width: 100%; background-image: url({THEME_COLOR_PATH}/images/top/middle_bg.jpg);">{MAIN_MENU}</td>
            <td style="padding:0;margin:0;text-align: right; width: 73px;vertical-align: top;"><img src="{THEME_COLOR_PATH}/images/top/middle_right.jpg" border="0"></td>
          </tr>
          <tr height="*">
            <td colspan=3><table width="100%" border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td align="left"><table width="100%" cellpadding="5" cellspacing="5">
                    <tr>
                      <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_ftp.png" width="25" height="25"></td>
                      <td colspan="2" class="title">{TR_ADD_FTP_USER}</td>
                    </tr>
                </table></td>
                <td width="27" align="right">&nbsp;</td>
              </tr>
              <tr>
                <td><table width="100%"  border="00" cellspacing="0" cellpadding="0">
                    <tr>
                      <td width="40">&nbsp;</td>
                      <td valign="top"><form name="add_ftp_acc_frm" method="post" action="add_ftp_acc.php">
                          <table width="100%" cellspacing="7">
                            <!-- BDP: page_message -->
                            <tr>
                              <td colspan="2" class="title"><font color="#FF0000">{MESSAGE}</font></td>
                            </tr>
                            <!-- EDP: page_message -->
                            <tr>
                              <td nowrap class="content2" width="200">{TR_USERNAME}</td>
                              <td class="content" nowrap><input type="text" name="username" value="{USERNAME}" style="width:170px" class="textinput">
                              </td>
                            </tr>
                            <tr>
                              <td nowrap class="content2" width="200"><input type="radio" name="dmn_type" value="dmn" onFocus="changeDom('real');" {DMN_TYPE_CHECKED}>
                                {TR_TO_MAIN_DOMAIN}</td>
                              <td class="content" nowrap>{FTP_SEPARATOR}{DOMAIN_NAME}</td>
                            </tr>
                            <!-- BDP: to_alias_domain -->
                            <tr>
                              <td nowrap class="content2" width="200"><input type="radio" name="dmn_type" value="als"  onfocus="changeDom('alias');" {ALS_TYPE_CHECKED}>
                                {TR_TO_DOMAIN_ALIAS}</td>
                              <td class="content" nowrap><select name="als_id">
                                  <!-- BDP: als_list -->
                                  <option value="{ALS_ID}" {ALS_SELECTED}>{FTP_SEPARATOR}{ALS_NAME}</option>
                                  <!-- EDP: als_list -->
                                </select>
                              </td>
                            </tr>
                            <!-- EDP: to_alias_domain -->
                            <tr>
                              <td nowrap class="content2" width="200">{TR_PASSWORD}</td>
                              <td class="content" nowrap><input type="password" name="pass" value="" style="width:170px" class="textinput">
                              </td>
                            </tr>
                            <tr>
                              <td nowrap class="content2" width="200">{TR_PASSWORD_REPEAT}</td>
                              <td nowrap class=content><input type="password" name="pass_rep" value="" style="width:170px" class="textinput">
                              </td>
                            </tr>
                            <tr>
                              <td nowrap class="content2" width="200"><input type="checkbox" name="use_other_dir" {USE_OTHER_DIR_CHECKED}>
                                {TR_USE_OTHER_DIR}</td>
                              <td nowrap class=content><input type="text" name="other_dir" value="{OTHER_DIR}" style="width:170px" class="textinput">
                                  <br>
                                <a href="javascript:OpenTree();" class="link">{CHOOSE_DIR} </a> </td>
                            </tr>
                          </table>
                        <input name="Submit" type="submit" class="button" value=" {TR_ADD} ">
                          <input type="hidden" name="uaction" value="add_user">
                      </form></td>
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
        </table>	    <p>&nbsp;</p></td>
	</tr>
</table>
</body>
</html>
