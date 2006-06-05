<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_CLIENT_ADD_MAIL_ACC_PAGE_TITLE}</title>
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
	function checkForm(){
            var aname  = document.forms[0].elements['username'].value;
            var apass  = document.forms[0].elements['pass'].value;
            var apass2 = document.forms[0].elements['pass_rep'].value;
            var forw   = document.forms[0].elements['forward_list'].value;
            if (aname == "") {
                alert(emptyData);
            }

            if (mailtype == "normal") {
                if (apass == "" || apass2 == "") {
                    alert(emptyData)
                }
                else if (apass != apass2) {
                    alert(passerr);
                }
                else {
                    document.forms[0].submit();
                }
            }
            else {
                if (forw == "") {
                    alert(emptyData)
                }
                else {
                    document.forms[0].submit();
                }
            }
    }
	
	<!-- BDP: js_to_all_domain -->
    function begin_js(){
            document.forms[0].als_id.disabled = true;
            document.forms[0].sub_id.disabled = true;
            document.forms[0].pass.disabled = false;
            document.forms[0].pass_rep.disabled = false;
            document.forms[0].forward_list.disabled = true;
            document.forms[0].username.focus();
    }

    

    function changeDom(wath) {
        if (wath == "alias") {
            document.forms[0].als_id.disabled = false;
            document.forms[0].sub_id.disabled = true;
        }
        else if (wath == "real"){
            document.forms[0].als_id.disabled = true;
            document.forms[0].sub_id.disabled = true;
        }
        else {
            document.forms[0].als_id.disabled = true;
            document.forms[0].sub_id.disabled = false;
        }
    }
	<!-- EDP: js_to_all_domain -->
	
	<!-- BDP: js_not_domain -->
    function begin_js(){
            document.forms[0].pass.disabled = false;
            document.forms[0].pass_rep.disabled = false;
            document.forms[0].forward_list.disabled = true;
			document.forms[0].username.focus();
    }
	<!-- EDP: js_not_domain -->
	
	
	<!-- BDP: js_to_subdomain -->
    function begin_js(){
            document.forms[0].sub_id.disabled = true;
            document.forms[0].pass.disabled = false;
            document.forms[0].pass_rep.disabled = false;
            document.forms[0].forward_list.disabled = true;
            document.forms[0].username.focus();
    }

    

    function changeDom(wath) {
        if (wath == "alias") {
            document.forms[0].sub_id.disabled = true;
        }
        else if (wath == "real"){
            document.forms[0].sub_id.disabled = true;
        }
        else {
            document.forms[0].sub_id.disabled = false;
        }
    }
	<!-- EDP: js_to_subdomain -->
	
	
	<!-- BDP: js_to_alias_domain -->
    function begin_js(){
            document.forms[0].als_id.disabled = true;
            document.forms[0].pass.disabled = false;
            document.forms[0].pass_rep.disabled = false;
            document.forms[0].forward_list.disabled = true;
            document.forms[0].username.focus();
    }

    

    function changeDom(wath) {
        if (wath == "alias") {
            document.forms[0].als_id.disabled = false;
        }
        else if (wath == "real"){
            document.forms[0].als_id.disabled = true;
        }
        else {
            document.forms[0].als_id.disabled = true;
        }
    }
	<!-- EDP: js_to_alias_domain -->



    function changeType(wath){
        if (wath == "normal") {
            document.forms[0].pass.disabled = false;
            document.forms[0].pass_rep.disabled = false;
            document.forms[0].forward_list.disabled = true;
            mailtype = "normal";
        }
        else {
            document.forms[0].pass.disabled = true;
            document.forms[0].pass_rep.disabled = true;
            document.forms[0].forward_list.disabled = false;
            mailtype = "forward";
        }
    }
//-->
</script>
<style type="text/css">
<!--
.style1 {font-size: 9px}
-->
</style>
</head>

<body onLoad="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/ftp_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif','{THEME_COLOR_PATH}/images/icons/logout_a.gif','{THEME_COLOR_PATH}/images/icons/email_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif'); begin_js();">
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
	<form name="add_mail_acc_frm" method="post" action="add_mail_acc.php">
	<input type="hidden" name="uaction" value="add_user">
        <td valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td height="62" align="left" background="{THEME_COLOR_PATH}/images/content/table_background.jpg" class="title"><img src="{THEME_COLOR_PATH}/images/content/table_icon_email.jpg" width="85" height="62" align="absmiddle">{TR_ADD_MAIL_USER}</td>
            <td width="27" align="right" background="{THEME_COLOR_PATH}/images/content/table_background.jpg"><img src="{THEME_COLOR_PATH}/images/content/table_icon_close.jpg" width="27" height="62"></td>
          </tr>
          <tr>
            <td><table width="100%"  border="0" cellspacing="0" cellpadding="0">
                <tr>
                  <td width="20">&nbsp;</td>
                  <td valign="top">
            <table width="100%" cellpadding="5" cellspacing="5">
              <!-- BDP: page_message -->
              <tr> 
                <td colspan="2" class="title"><font color="#FF0000">{MESSAGE}</font></td>
              </tr>
              <!-- EDP: page_message -->
              <tr> 
                <td nowrap class="content2" width="200">{TR_USERNAME}</td>
                <td valign="middle" nowrap class="content"> 
                  <input type="text" name="username" value="{USERNAME}" style="width:170px" class="textinput">
		<td width="100%" valign=middle nowrap class="content">@{DOMAIN_NAME}
                </td>
              </tr>
              <tr> 
                <td nowrap class="content2" width="200"> 
                  <input type="radio" name="dmn_type" value="dmn" {MAIL_DMN_CHECKED} onClick="changeDom('real');">{TR_TO_MAIN_DOMAIN}</td>
                <td nowrap class="content" colspan="2">@{DOMAIN_NAME}</td>
              </tr>
			  <!-- BDP: to_alias_domain -->
              <tr> 
                <td nowrap class="content2" width="200"> 
                  <input type="radio" name="dmn_type" value="als" {MAIL_ALS_CHECKED} onClick="changeDom('alias');">{TR_TO_DMN_ALIAS}</td>
                <td nowrap class="content"> 
                  <select name="als_id">
                    <!-- BDP: als_list -->
                    <option value="{ALS_ID}" {ALS_SELECTED}>@{ALS_NAME}</option>
                    <!-- EDP: als_list -->
                  </select>
                </td>
              </tr>
			  <!-- EDP: to_alias_domain -->
			  <!-- BDP: to_subdomain -->
              <tr> 
                <td nowrap class="content2" width="200"> 
                  <input type="radio" name="dmn_type" value="sub" {MAIL_SUB_CHECKED} onClick="changeDom('subdom');">{TR_TO_SUBDOMAIN}</td>
                <td nowrap class="content"> 
                  <select name="sub_id">
                    <!-- BDP: sub_list -->
                    <option value="{SUB_ID}" {SUB_SELECTED}>@{SUB_NAME}</option>
                    <!-- EDP: sub_list -->
                  </select>
                </td>
              </tr>
			  <!-- EDP: to_subdomain -->
              <tr> 
                <td nowrap class="content2" colspan="3"> 
                  <input type="radio" name="mail_type" value="normal" onClick="changeType('normal');" {NORMAL_MAIL_CHECKED}>{TR_NORMAL_MAIL}</td>
              </tr>
              <tr> 
                <td nowrap class="content2" width="200">{TR_PASSWORD}</td>
                <td nowrap  class="content"> 
                  <input type="password" name="pass" value="" style="width:170px" class="textinput">
                </td>
              </tr>
              <tr> 
                <td nowrap class="content2" width="200">{TR_PASSWORD_REPEAT}</td>
                <td nowrap class="content"> 
                  <input type="password" name="pass_rep" value="" style="width:170px" class="textinput">
                </td>
              </tr>
              <tr> 
                <td nowrap class="content2" colspan="3"> 
                  <input type="radio" name="mail_type" value="forward" {FORWARD_MAIL_CHECKED} onClick="changeType('forward');">{TR_FORWARD_MAIL}</td>
              </tr>
              <tr> 
                <td nowrap class="content2" width="200">{TR_FORWARD_TO}</td>
                <td nowrap  class="content"> 
                  <textarea name="forward_list" cols="35" rows="5" wrap="virtual">{FORWARD_LIST}</textarea>
	    	        </td>
	              </tr>
              <tr>
             <td colspan="2">
            <input name="Submit" type="submit" class="button" value=" {TR_ADD} ">
                    </td>
                </tr>
            </table>
	</td>
          </tr>
            </table>
       	</td>
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
