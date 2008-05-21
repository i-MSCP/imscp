<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_CLIENT_ADD_MAIL_ACC_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/tooltip.js"></script>

<script language="JavaScript" type="text/JavaScript">
<!--
	function sbmt(form, uaction) {
	    form.uaction.value = uaction;
	    form.submit();

	    return false;
	}

	function checkForm() {
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
            } else if (apass != apass2) {
                alert(passerr);
            } else {
                document.forms[0].submit();
            }
        } else {
            if (forw == "") {
                alert(emptyData)
            } else {
                document.forms[0].submit();
            }
        }
    }

	<!-- BDP: js_to_all_domain -->
    function begin_js() {
        document.forms[0].als_id.disabled = true;
        document.forms[0].sub_id.disabled = true;
        document.forms[0].pass.disabled = false;
        document.forms[0].pass_rep.disabled = false;
        document.forms[0].forward_list.disabled = true;
        document.forms[0].username.focus();
    }

    function changeDom(what) {
        if (what == "alias") {
            document.forms[0].als_id.disabled = false;
            document.forms[0].sub_id.disabled = true;
        } else if (what == "real"){
            document.forms[0].als_id.disabled = true;
            document.forms[0].sub_id.disabled = true;
        } else {
            document.forms[0].als_id.disabled = true;
            document.forms[0].sub_id.disabled = false;
        }
    }
	<!-- EDP: js_to_all_domain -->

	<!-- BDP: js_not_domain -->
    function begin_js() {
        document.forms[0].pass.disabled = false;
        document.forms[0].pass_rep.disabled = false;
        document.forms[0].forward_list.disabled = true;
		document.forms[0].username.focus();
    }
	<!-- EDP: js_not_domain -->

	<!-- BDP: js_to_subdomain -->
    function begin_js() {
        document.forms[0].sub_id.disabled = true;
        document.forms[0].pass.disabled = false;
        document.forms[0].pass_rep.disabled = false;
        document.forms[0].forward_list.disabled = true;
        document.forms[0].username.focus();
    }

    function changeDom(what) {
        if (what == "alias") {
            document.forms[0].sub_id.disabled = true;
        } else if (what == "real"){
            document.forms[0].sub_id.disabled = true;
        } else {
            document.forms[0].sub_id.disabled = false;
        }
    }
	<!-- EDP: js_to_subdomain -->

	<!-- BDP: js_to_alias_domain -->
    function begin_js() {
        document.forms[0].als_id.disabled = true;
        document.forms[0].pass.disabled = false;
        document.forms[0].pass_rep.disabled = false;
        document.forms[0].forward_list.disabled = true;
        document.forms[0].username.focus();
    }

    function changeDom(what) {
        if (what == "alias") {
            document.forms[0].als_id.disabled = false;
        } else if (what == "real"){
            document.forms[0].als_id.disabled = true;
        } else {
            document.forms[0].als_id.disabled = true;
        }
    }
	<!-- EDP: js_to_alias_domain -->

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
//-->
</script>
<style type="text/css">
<!--
.style1 {font-size: 9px}
-->
</style>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/ftp_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/email_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif'); begin_js();">
<!-- BDP: logged_from -->
     <table width="100%"  border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td height="20" nowrap background="{THEME_COLOR_PATH}/images/button.gif">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.png" width="18" height="18" border="0" align="absmiddle"></a> <font color="red">{YOU_ARE_LOGGED_AS}</font> </td>
      </tr>
    </table>
	<!-- EDP: logged_from -->
<!-- ToolTip -->
<div id="fwd_help" style="background-color:#ffffe0;border: 1px #000000 solid;display:none;margin:5px;padding:5px;font-size:11px;width:200px;position:absolute;">{TR_FWD_HELP}</div>
<!-- ToolTip end -->
<table width="100%" border="0" cellspacing="0" cellpadding="0" height="100%" style="border-collapse: collapse;padding:0;margin:0;">
<tr>
<td align="left" valign="top" style="vertical-align: top; width: 195px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_left.jpg" border="0"></td>
<td style="height: 56px; width:100%; background-image: url({THEME_COLOR_PATH}/images/top/top_bg.jpg)"><img src="{THEME_COLOR_PATH}/images/top/top_left_bg.jpg" border="0"></td>
<td style="width: 73px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_right.jpg" border="0"></td>
</tr>
	<tr>
		<td style="width: 195px; vertical-align: top;">{MENU}</td>
	    <td colspan=2 style="vertical-align: top;"><table style="width: 100%; border-collapse: collapse;padding:0;margin:0;">
          <tr height="95">
            <td style="padding-left:30px; width: 100%; background-image: url({THEME_COLOR_PATH}/images/top/middle_bg.jpg);">{MAIN_MENU}</td>
            <td style="padding:0;margin:0;text-align: right; width: 73px;vertical-align: top;"><img src="{THEME_COLOR_PATH}/images/top/middle_right.jpg" border="0"></td>
          </tr>
          <tr height="*">
            <td colspan=3>
	<form name="add_mail_acc_frm" method="post" action="add_mail_acc.php">
	<input type="hidden" name="uaction" value="add_user">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td align="left">
<table width="100%" cellpadding="5" cellspacing="5">
	<tr>
		<td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_email.png" width="25" height="25"></td>
		<td colspan="2" class="title">{TR_ADD_MAIL_USER}</td>
	</tr>
</table>
			</td>
            <td width="27" align="right">&nbsp;</td>
          </tr>
          <tr>
            <td><table width="100%"  border="0" cellspacing="0" cellpadding="0">
                <tr>
                  <td width="40">&nbsp;</td>
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
                  <input type="text" name="username" value="{USERNAME}" style="width:210px" class="textinput">
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
                <td nowrap class="content2" colspan="2">
                  &nbsp;&nbsp;<input type="checkbox" name="mail_type_normal" value="1" onClick="changeType();" {NORMAL_MAIL_CHECKED}>{TR_NORMAL_MAIL}</td>
              </tr>
              <tr>
                <td nowrap class="content2" width="200">&nbsp;&nbsp;&nbsp;&nbsp;{TR_PASSWORD}</td>
                <td nowrap  class="content">
                  <input type="password" name="pass" value="" style="width:210px" class="textinput">
                </td>
              </tr>
              <tr>
                <td nowrap class="content2" width="200">&nbsp;&nbsp;&nbsp;&nbsp;{TR_PASSWORD_REPEAT}</td>
                <td nowrap class="content">
                  <input type="password" name="pass_rep" value="" style="width:210px" class="textinput">
                </td>
              </tr>
              <tr>
                <td nowrap class="content2" colspan="2">
                  &nbsp;&nbsp;<input type="checkbox" name="mail_type_forward" value="1" {FORWARD_MAIL_CHECKED} onClick="changeType();">{TR_FORWARD_MAIL}</td>
              </tr>
              <tr>
                <td class="content2" style="width:200px;vertical-align:top;">
				  {TR_FORWARD_TO} <img src="{THEME_COLOR_PATH}/images/icons/help.png" width="16" height="16" onMouseOver="showTip('fwd_help', event)" onMouseOut="hideTip('fwd_help')" />
				</td>
                <td nowrap  class="content">
                  <textarea name="forward_list" cols="35" rows="5" wrap="virtual" style="width:210px">{FORWARD_LIST}</textarea>
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
        </table>
	  </td>
	</tr>
</table>

</body>
</html>