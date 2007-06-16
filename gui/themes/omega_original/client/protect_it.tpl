<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_HTACCESS}</title>
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

function MM_goToURL() { //v3.0
  var i, args=MM_goToURL.arguments; document.MM_returnValue = false;
  for (i=0; i<(args.length-1); i+=2) eval(args[i]+".location='"+args[i+1]+"'");
}

function begin_js(){
               	document.forms[0].elements["users[]"].disabled = {USER_FORM_ELEMENS}

                document.forms[0].elements["groups[]"].disabled = {GROUP_FORM_ELEMENS}
        }


        function changeType(wath){
            if (wath == "user") {
                document.forms[0].elements["users[]"].disabled = false;
                document.forms[0].elements["groups[]"].disabled = true;
            }
            else {
                document.forms[0].elements["users[]"].disabled = true;
                document.forms[0].elements["groups[]"].disabled = false;
            }
        }

//-->
</script>
</head>

<body onLoad="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/ftp_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif','{THEME_COLOR_PATH}/images/icons/logout_a.gif','{THEME_COLOR_PATH}/images/icons/email_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif'); begin_js();">
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
                      <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_htaccess.png" width="25" height="25"></td>
                      <td colspan="2" class="title">{TR_HTACCESS}</td>
                    </tr>
                </table></td>
                <td width="27" align="right">&nbsp;</td>
              </tr>
              <tr>
                <td valign="top">&nbsp;
                    <form name="edit_ftp_acc_frm" method="post" action="protect_it.php">
                      <table width="100%" cellpadding="5" cellspacing="5">
                        <tr>
                          <td width="25" nowrap>&nbsp;</td>
                          <td colspan="2" nowrap class="content3"><b>{TR_PROTECT_DIR}</b></td>
                        </tr>
                        <!-- BDP: page_message -->
                        <tr>
                          <td width="25" nowrap>&nbsp;</td>
                          <td colspan="2" nowrap class="title"><font color="#FF0000">{MESSAGE}</font></td>
                        </tr>
                        <!-- EDP: page_message -->
                        <tr>
                          <td nowrap>&nbsp;</td>
                          <td colspan="2" nowrap class="content"><table width="100%"  border="0" cellspacing="0" cellpadding="0">
                              <tr>
                                <td width="80">{TR_PATH} </td>
                                <td><input name="other_dir" type="text" class="textinput" id="path" style="width:170px" value="{PATH}">
                                    <input type="hidden" name="use_other_dir" >
                                    <script language="JavaScript" type="text/JavaScript">
					<!--
						function OpenTree()
						{
						  libwindow=window.open("ftp_choose_dir.php","Hello","menubar=no,width=470,height=350,scrollbars=yes");
						  
						}
					//-->
					</script>
                                  <a href="javascript:OpenTree();" class="link">{CHOOSE_DIR}</a> </td>
                              </tr>
                          </table></td>
                        </tr>
                        <tr>
                          <td nowrap>&nbsp;</td>
                          <td colspan="2" nowrap class="content"><table width="100%"  border="0" cellspacing="0" cellpadding="0">
                              <tr>
                                <td width="80">{TR_AREA_NAME} </td>
                                <td><input name="paname" type="text" class="textinput" id="paname" style="width:170px" value="{AREA_NAME}"></td>
                              </tr>
                          </table></td>
                        </tr>
                        <tr>
                          <td width="25" nowrap>&nbsp;</td>
                          <td nowrap class="content2"><strong>{TR_USER}</strong></td>
                          <td nowrap class="content2"><strong>{TR_GROUPS}</strong></td>
                        </tr>
                        <tr align="center">
                          <td width="25" nowrap>&nbsp;</td>
                          <td nowrap class="content"><input type="radio" name="ptype" value="user" {USER_CHECKED} onFocus="changeType('user');">
                            {TR_USER_AUTH}</td>
                          <td nowrap class="content"><input type="radio" name="ptype" value="group" {GROUP_CHECKED} onFocus="changeType('group');">
                            {TR_GROUP_AUTH}</td>
                        </tr>
                        <tr>
                          <td width="25" align="center" nowrap>&nbsp;</td>
                          <td align="center" nowrap class="content"><select name="users[]" multiple size="5" class="textinput2">
                              <!-- BDP: user_item -->
                              <option value="{USER_VALUE}" {USER_SELECTED}>{USER_LEBEL}</option>
                              <!-- EDP: user_item -->
                            </select>
                          </td>
                          <td align="center" nowrap class="content"><select name="groups[]" multiple size="5" class="textinput2">
                              <!-- BDP: group_item -->
                              <option value="{GROUP_VALUE}" {GROUP_SELECTED}>{GROUP_LEBEL}</option>
                              <!-- EDP: group_item -->
                            </select>
                          </td>
                        </tr>
                        <tr>
                          <td nowrap>&nbsp;</td>
                          <td colspan="2" nowrap><input name="Button" type="button" class="button" value="{TR_PROTECT_IT}" onClick="return sbmt(document.forms[0],'protect_it');">
                            &nbsp;&nbsp;&nbsp;
                            <!-- BDP: unprotect_it -->
                            <input name="Button" type="button" class="button" onClick="MM_goToURL('parent','protect_delete.php?id={CDIR}');return document.MM_returnValue" value="{TR_UNPROTECT_IT}">
                            &nbsp;&nbsp;&nbsp;
                            <!-- EDP: unprotect_it -->
                            <br>
                            <br>
                            <input name="Button" type="button" class="button" onClick="MM_goToURL('parent','puser_manage.php');return document.MM_returnValue" value="{TR_MANAGE_USRES}">
                            &nbsp;&nbsp;&nbsp;
                            <input name="Button" type="button" class="button" onClick="MM_goToURL('parent','protected_areas.php');return document.MM_returnValue" value="{TR_CANCEL}">
                          </td>
                        </tr>
                      </table>
                      <input type="hidden" name="sub" value="YES">
                      <input type="hidden" name="cdir" value="{CDIR}">
                      <input type="hidden" name="uaction" value="">
                  </form></td>
                <td>&nbsp;</td>
              </tr>
              <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
              </tr>
            </table></td>
          </tr>
        </table>
	  </td>
	</tr>
</table>
</body>
</html>
