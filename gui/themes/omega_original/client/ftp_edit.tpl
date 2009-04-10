<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_CLIENT_EDIT_FTP_ACC_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
  <link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
  <script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
<script type="text/javascript">
<!--
function over(number) {
	document.images["image"+number+"_1"].src = '{THEME_COLOR_PATH}/images/bars/menu_button_left.gif';
	document.images["image"+number+"_2"].src = '{THEME_COLOR_PATH}/images/bars/menu_button_right.gif';
	if (document.layers) {
		document.layers["m"+number].background.src = '{THEME_COLOR_PATH}/images/bars/menu_button_background.gif';
	} else if (document.all) {
		window.document.all["id"+number].style.backgroundImage = 'url({THEME_COLOR_PATH}/images/bars/menu_button_background.gif)';
	}
}

function out(number) {
	document.images["image"+number+"_1"].src = '../images/menubutton_left.gif';
	document.images["image"+number+"_2"].src = '../images/menubutton_right.gif';
	if (document.layers) {
		document.layers["m"+number].background.src = '../images/menubutton_background.gif';
	} else if (document.all) {
		window.document.all["id"+number].style.backgroundImage = 'url(../images/menubutton_background.gif)';
	}
}
//-->
</script>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/ftp_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/email_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<!-- BDP: logged_from --><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td height="20" nowrap="nowrap" class="backButton">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.png" width="16" height="16" border="0" style="vertical-align:middle" /></a> {YOU_ARE_LOGGED_AS}</td>
      </tr>
    </table>
	<!-- EDP: logged_from -->
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="height:100%;border-collapse: collapse;padding:0;margin:0;">
<tr>
<td align="left" valign="top" style="vertical-align: top; width: 195px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_left.jpg" border="0" alt="ispCP Logogram" /></td>
<td style="height: 56px; width:100%; background-image: url({THEME_COLOR_PATH}/images/top/top_bg.jpg)"><img src="{THEME_COLOR_PATH}/images/top/top_left_bg.jpg" border="0" alt="" /></td>
<td style="width: 73px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_right.jpg" border="0" alt="" /></td>
</tr>
	<tr>
		<td style="width: 195px; vertical-align: top;">{MENU}</td>
	    <td colspan="2" style="vertical-align: top;"><table style="width: 100%; border-collapse: collapse;padding:0;margin:0;">
          <tr style="height:95px;">
            <td style="padding-left:30px; width: 100%; background-image: url({THEME_COLOR_PATH}/images/top/middle_bg.jpg);">{MAIN_MENU}</td>
            <td style="padding:0;margin:0;text-align: right; width: 73px;vertical-align: top;"><img src="{THEME_COLOR_PATH}/images/top/middle_right.jpg" border="0" alt="" /></td>
          </tr>
          <tr>
            <td colspan="3"><table width="100%" border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td align="left"><table width="100%" cellpadding="5" cellspacing="5">
                    <tr>
                      <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_doc.png" width="25" height="25" /></td>
                      <td colspan="2" class="title">{TR_EDIT_FTP_USER}</td>
                    </tr>
                </table></td>
                <td width="27" align="right">&nbsp;</td>
              </tr>
              <tr>
                <td><table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td width="40">&nbsp;</td>
                      <td valign="top"><form name="edit_ftp_acc_frm" method="post" action="ftp_edit.php">
                          <table width="100%" cellpadding="5" cellspacing="5">
                            <!-- BDP: page_message -->
                            <tr>
                              <td colspan="2" class="title"><span class="message">{MESSAGE}</span></td>
                            </tr>
                            <!-- EDP: page_message -->
                            <tr>
                              <td nowrap="nowrap" class="content2" width="200">{TR_FTP_ACCOUNT}</td>
                              <td nowrap="nowrap" class="content"><input type="text" name="ftp_account" value="{FTP_ACCOUNT}" style="width:170px" class="textinput" readonly="readonly" />
                              </td>
                            </tr>
                            <tr>
                              <td nowrap="nowrap" class="content2" width="200"><label for="pass">{TR_PASSWORD}</label></td>
                              <td nowrap="nowrap" class="content"><input type="password" name="pass" id="pass" value="" style="width:170px" class="textinput" />
                              </td>
                            </tr>
                            <tr>
                              <td nowrap="nowrap" class="content2" width="200"><label for="pass_rep">{TR_PASSWORD_REPEAT}</label></td>
                              <td nowrap="nowrap" class="content"><input type="password" name="pass_rep" id="pass_rep" value="" style="width:170px" class="textinput" />
                              </td>
                            </tr>
                            <tr>
                              <td nowrap="nowrap" class="content2" width="200"><input type="checkbox" name="use_other_dir" {USE_OTHER_DIR_CHECKED} />
                                {TR_USE_OTHER_DIR}</td>
                              <td nowrap="nowrap" class="content"><input type="text" name="other_dir" value="{OTHER_DIR}" style="width:170px" class="textinput" />
                                  <br />
                                <a href="#" onclick="showFileTree();" class="link">{CHOOSE_DIR}</a></td>
                            </tr>
                          </table>
                        <input type="hidden" name="uaction" value="edit_user" />
                          <input type="hidden" name="id" value="{ID}" />
                          <input name="Submit" type="submit" class="button" value=" {TR_EDIT} " />
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
        </table></td>
	</tr>
</table>
</body>
</html>
