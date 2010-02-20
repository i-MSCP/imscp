<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_CLIENT_ADD_MAIL_ACC_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
<script type="text/javascript">
<!--
	function changeType() {
		if (document.forms[0].elements['mail_type_normal'].checked == true) {
			document.forms[0].pass.disabled = false;
			document.forms[0].pass_rep.disabled = false;
		} else {
			document.forms[0].pass.disabled = true;
			document.forms[0].pass_rep.disabled = true;
		}
		if (document.forms[0].elements['mail_type_forward'].checked == true) {
			document.forms[0].forward_list.disabled = false;
		} else {
			document.forms[0].forward_list.disabled = true;
		}
	}

	function begin_js() {
		if (document.getElementsByName('als_id').length !== 0) {
			if (document.getElementById('dmn_type2').checked) {
				document.forms[0].als_id.disabled = false;
			} else {
				document.forms[0].als_id.disabled = true;
			}
		}
		if (document.getElementsByName('sub_id').length !== 0) {
			if (document.getElementById('dmn_type3').checked) {
				document.forms[0].sub_id.disabled = false;
			} else {
				document.forms[0].sub_id.disabled = true;
			}
		}
		if (document.getElementsByName('als_sub_id').length !== 0) {
			if (document.getElementById('dmn_type4').checked) {
				document.forms[0].als_sub_id.disabled = false;
			} else {
				document.forms[0].als_sub_id.disabled = true;
			}
		}
//		document.forms[0].pass.disabled = false;
//		document.forms[0].pass_rep.disabled = false;
//		document.forms[0].forward_list.disabled = true;
		changeType();
		document.forms[0].username.focus();
	}

	function changeDom(what) {
		if (document.getElementsByName('als_id').length !== 0) {
			if (what == "alias") {
				document.forms[0].als_id.disabled = false;
			} else {
				document.forms[0].als_id.disabled = true;
			}
		}
		if (document.getElementsByName('sub_id').length !== 0) {
			if (what == "subdom") {
				document.forms[0].sub_id.disabled = false;
			} else  {
				document.forms[0].sub_id.disabled = true;
			}
		}
		if (document.getElementsByName('als_sub_id').length !== 0) {
			if (what == "als_subdom") {
				document.forms[0].als_sub_id.disabled = false;
			} else {
				document.forms[0].als_sub_id.disabled = true;
			}
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

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/ftp_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/email_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif'); begin_js();">
<!-- ToolTip -->
<div id="fwd_help" style="background-color:#ffffe0;border: 1px #000000 solid;display:none;margin:5px;padding:5px;font-size:11px;width:200px;position:absolute;">{TR_FWD_HELP}</div>
<!-- ToolTip end -->
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
            <td colspan="3">
	<form name="add_mail_acc_frm" method="post" action="mail_add.php">
	<input type="hidden" name="uaction" value="add_user" />
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td align="left">
<table width="100%" cellpadding="5" cellspacing="5">
	<tr>
		<td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_email.png" width="25" height="25" alt="" /></td>
		<td colspan="2" class="title">{TR_ADD_MAIL_USER}</td>
	</tr>
</table>
			</td>
            <td width="27" align="right">&nbsp;</td>
          </tr>
          <tr>
            <td><table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                  <td width="40">&nbsp;</td>
                  <td valign="top">
            <table width="100%" cellpadding="5" cellspacing="5">
              <!-- BDP: page_message -->
              <tr>
                <td colspan="2" class="title"><span class="message">{MESSAGE}</span></td>
              </tr>
              <!-- EDP: page_message -->
              <tr>
                <td nowrap="nowrap" class="content2" width="200">
                  <label for="username">{TR_USERNAME}</label>
                </td>
                <td valign="middle" nowrap="nowrap" class="content">
                  <input type="text" name="username" id="username" value="{USERNAME}" style="width:210px" class="textinput" />
                </td>
              </tr>
              <tr>
                <td nowrap="nowrap" class="content2" width="200">
                  <input type="radio" name="dmn_type" id="dmn_type1" value="dmn" {MAIL_DMN_CHECKED} onclick="changeDom('real');" />
                  <label for="dmn_type1">{TR_TO_MAIN_DOMAIN}</label>
                </td>
                <td nowrap="nowrap" class="content" colspan="2">@{DOMAIN_NAME}</td>
              </tr>
			  <!-- BDP: to_alias_domain -->
              <tr>
                <td nowrap="nowrap" class="content2" width="200">
                  <input type="radio" name="dmn_type" id="dmn_type2" value="als" {MAIL_ALS_CHECKED} onclick="changeDom('alias');" />
                  <label for="dmn_type2">{TR_TO_DMN_ALIAS}</label>
                </td>
                <td nowrap="nowrap" class="content"><select name="als_id">
                    <!-- BDP: als_list -->
                    <option value="{ALS_ID}" {ALS_SELECTED}>@{ALS_NAME}</option>
                    <!-- EDP: als_list -->
                  </select></td>
              </tr>
			  <!-- EDP: to_alias_domain -->
			  <!-- BDP: to_subdomain -->
              <tr>
                <td nowrap="nowrap" class="content2" width="200">
                  <input type="radio" name="dmn_type" id="dmn_type3" value="sub" {MAIL_SUB_CHECKED} onclick="changeDom('subdom');" />
                  <label for="dmn_type3">{TR_TO_SUBDOMAIN}</label>
                </td>
                <td nowrap="nowrap" class="content"><select name="sub_id">
                    <!-- BDP: sub_list -->
                    <option value="{SUB_ID}" {SUB_SELECTED}>@{SUB_NAME}</option>
                    <!-- EDP: sub_list -->
                  </select></td>
              </tr>
			  <!-- EDP: to_subdomain -->
			  <!-- BDP: to_alias_subdomain -->
              <tr>
                <td nowrap="nowrap" class="content2" width="200">
                  <input type="radio" name="dmn_type" id="dmn_type4" value="als_sub" {MAIL_ALS_SUB_CHECKED} onclick="changeDom('als_subdom');" />
                  <label for="dmn_type4">{TR_TO_ALS_SUBDOMAIN}</label>
                </td>
                <td nowrap="nowrap" class="content"><select name="als_sub_id">
                    <!-- BDP: als_sub_list -->
                    <option value="{ALS_SUB_ID}" {ALS_SUB_SELECTED}>@{ALS_SUB_NAME}</option>
                    <!-- EDP: als_sub_list -->
                  </select></td>
              </tr>
			  <!-- EDP: to_alias_subdomain -->
              <tr>
                <td nowrap="nowrap" class="content2" colspan="2">
                  &nbsp;&nbsp;<input type="checkbox" name="mail_type_normal" value="1" onclick="changeType();" {NORMAL_MAIL_CHECKED} />{TR_NORMAL_MAIL}</td>
              </tr>
              <tr>
                <td nowrap="nowrap" class="content2" width="200">&nbsp;&nbsp;&nbsp;&nbsp;{TR_PASSWORD}</td>
                <td nowrap="nowrap" class="content"><input type="password" name="pass" value="" style="width:210px" class="textinput" /></td>
              </tr>
              <tr>
                <td nowrap="nowrap" class="content2" width="200">&nbsp;&nbsp;&nbsp;&nbsp;{TR_PASSWORD_REPEAT}</td>
                <td nowrap="nowrap" class="content"><input type="password" name="pass_rep" value="" style="width:210px" class="textinput" /></td>
              </tr>
              <tr>
                <td nowrap="nowrap" class="content2" colspan="2">
                  &nbsp;&nbsp;<input type="checkbox" name="mail_type_forward" value="1" {FORWARD_MAIL_CHECKED} onclick="changeType();" />{TR_FORWARD_MAIL}</td>
              </tr>
              <tr>
                <td class="content2" style="width:200px;vertical-align:top;">
				  {TR_FORWARD_TO} <img src="{THEME_COLOR_PATH}/images/icons/help.png" width="16" height="16" onmouseover="showTip('fwd_help', event)" onmouseout="hideTip('fwd_help')" /></td>
                <td nowrap="nowrap" class="content"><textarea name="forward_list" cols="35" rows="10" style="width:400px">{FORWARD_LIST}</textarea></td>
	          </tr>
              <tr>
             <td colspan="2"><input name="Submit" type="submit" class="button" value=" {TR_ADD} " /></td>
                </tr>
            </table></td>
          </tr>
            </table></td>
	</tr>
        </table></form></td>
          </tr>
        </table></td>
	</tr>
</table>

</body>
</html>
