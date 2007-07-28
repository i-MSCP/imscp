<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_CLIENT_CREATE_CATCHALL_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
<script language="JavaScript" type="text/JavaScript">
<!--
	function sbmt(form, uaction) {

    	form.uaction.value = uaction;
	    form.submit();

    	return false;

	}

	function checkForm(){
            var forw   = document.forms[0].elements['forward_list'].value;

            if (mailtype == "normal") {
                 document.forms[0].submit();
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

    function begin_js(){
            document.forms[0].forward_list.disabled = true;
	        document.forms[0].mail_id.disabled = false;
			document.forms[0].mail_id.focus();
    }

    function changeType(what){
        if (what == "normal") {
	        document.forms[0].mail_id.disabled = false;
            document.forms[0].forward_list.disabled = true;
        }
        else {
	        document.forms[0].mail_id.disabled = true;
            document.forms[0].forward_list.disabled = false;
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

<body onLoad="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/ftp_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/email_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif'); changeType('normal');">
<!-- BDP: logged_from --><table width="100%"  border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td height="20" nowrap background="{THEME_COLOR_PATH}/images/button.gif">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.png" width="18" height="18" border="0" align="absmiddle"></a> <font color="red">{YOU_ARE_LOGGED_AS}</font> </td>
      </tr>
    </table>
	<!-- EDP: logged_from -->
<table width="100%" border="0" cellspacing="0" cellpadding="0" height="100%" style="border-collapse: collapse;padding:0;margin:0;">
	<tr>
		<td align="left" valign="top" style="vertical-align: top; width: 195px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_left.jpg" border="0"></td>
		<td style="height: 56px; width: 617px;"><img src="{THEME_COLOR_PATH}/images/top/top_left_bg.jpg" border="0"></td>
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
                      <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_doc.png" width="25" height="25"></td>
                      <td colspan="2" class="title">{TR_CREATE_CATCHALL_MAIL_ACCOUNT}</td>
                    </tr>
                </table></td>
                <td width="27" align="right">&nbsp;</td>
              </tr>
              <tr>
                <td><table width="100%"  border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td width="40">&nbsp;</td>
                      <td valign="top"><form name="create_catchall_frm" method="post" action="create_catchall.php">
                          <table width="100%" cellpadding="5" cellspacing="5">
                            <!-- BDP: page_message -->
                            <tr>
                              <td colspan="2" class="title"><font color="#FF0000">{MESSAGE}</font></td>
                            </tr>
                            <!-- EDP: page_message -->
                            <tr>
                              <td nowrap class="content2" width="200"><input type="radio" name="mail_type" value="normal" {NORMAL_MAIL} onClick="changeType('normal');">
                                {TR_MAIL_LIST} </td>
                              <td nowrap class="content"><select name="mail_id">
                                  <!-- BDP: mail_list -->
                                  <option value="{MAIL_ID};{MAIL_ACCOUNT_PUNNY};">{MAIL_ACCOUNT}</option>
                                  <!-- EDP: mail_list -->
                                </select>
                              </td>
                            </tr>
                            <tr>
                              <td nowrap class="content2" colspan="2"><input type="radio" name="mail_type" value="forward" {FORWARD_MAIL} onClick="changeType('forward');">
                                {TR_FORWARD_MAIL} </td>
                            </tr>
                            <tr>
                              <td nowrap class="content2" width="200">{TR_FORWARD_TO}</td>
                              <td nowrap  class="content"><textarea name="forward_list" cols="35" rows="5" wrap="virtual" style="width:210px"></textarea>
                              </td>
                            </tr>
                          </table>
                        <input name="Submit" type="submit" class="button" value="{TR_CREATE_CATCHALL}">
                          <input type="hidden" name="uaction" value="create_catchall">
                          <input type="hidden" name="id" value="{ID}">
                      </form></td>
                    </tr>
                </table></td>
              </tr>
            </table></td>
          </tr>
        </table>
	  </td>
	</tr>
</table>
</body>
</html>
