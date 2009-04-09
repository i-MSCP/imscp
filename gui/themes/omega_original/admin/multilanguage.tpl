<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_ADMIN_I18N_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
<script type="text/javascript">
<!--
function action_delete(url, language) {
	if (!confirm(sprintf("{TR_MESSAGE_DELETE}", language)))
		return false;
	location = url;
}
//-->
</script>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<table width="100%" border="0" cellspacing="0" cellpadding="0" height="100%" style="border-collapse: collapse;padding:0;margin:0;">
<tr>
<td align="left" valign="top" style="vertical-align: top; width: 195px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_left.jpg" border="0" alt="ispCP Logogram" /></td>
<td style="height: 56px; width:100%; background-image: url({THEME_COLOR_PATH}/images/top/top_bg.jpg)"><img src="{THEME_COLOR_PATH}/images/top/top_left_bg.jpg" border="0" alt="" /></td>
<td style="width: 73px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_right.jpg" border="0" alt="" /></td>
</tr>
	<tr>
		<td style="width: 195px; vertical-align: top;">{MENU}</td>
	    <td colspan="2" style="vertical-align: top;"><table style="width: 100%; border-collapse: collapse;padding:0;margin:0;">
				<tr height="95">
				  <td style="padding-left:30px; width: 100%; background-image: url({THEME_COLOR_PATH}/images/top/middle_bg.jpg);">{MAIN_MENU}</td>
					<td style="padding:0;margin:0;text-align: right; width: 73px;vertical-align: top;"><img src="{THEME_COLOR_PATH}/images/top/middle_right.jpg" border="0" alt="" /></td>
				</tr>
				<tr>
				  <td colspan="3"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td align="left"><table width="100%" cellpadding="5" cellspacing="5">
                          <tr>
                            <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_multilanguage.png" width="25" height="25"></td>
                            <td colspan="2" class="title">{TR_MULTILANGUAGE}</td>
                          </tr>
                      </table></td>
                      <td width="27" align="right">&nbsp;</td>
                    </tr>
                    <tr>
                      <td valign="top"><form action="multilanguage.php" method="post" enctype="multipart/form-data" name="set_layout" id="set_layout">
                          <table width="100%" cellpadding="5" cellspacing="5">
                            <!-- BDP: page_message -->
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td colspan="5" class="title"><span class="message">{MESSAGE}</span></td>
                            </tr>
                            <!-- EDP: page_message -->
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td colspan="5" class="content3"><b>{TR_INSTALLED_LANGUAGES}</b></td>
                            </tr>
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td class="content2"><b>{TR_LANGUAGE}</b></td>
                              <td class="content2"><b>{TR_MESSAGES}</b></td>
                              <td class="content2" width="100" align="center"><b>{TR_DEFAULT}</b></td>
                              <td width="100" colspan="2" align="center" class="content2"><b>{TR_ACTION}</b></td>
                            </tr>
                            <!-- BDP: lang_row -->
                            <tr>
                              <td width="25" nowrap="nowrap">&nbsp;</td>
                              <td class="{LANG_CLASS}" nowrap="nowrap"><img src="{THEME_COLOR_PATH}/images/icons/locale.png" width="16" height="16" align="left" /> {LANGUAGE}</td>
                              <td class="{LANG_CLASS}" nowrap="nowrap">{MESSAGES}</td>
                              <td class="{LANG_CLASS}" width="100" nowrap="nowrap" align="center"><!-- BDP: lang_def -->
                                {DEFAULT}
                                <!-- EDP: lang_def -->
                <!-- BDP: lang_radio -->
                <input type="radio" name="default_language" value="{LANG_VALUE}" />
                <!-- EDP: lang_radio -->
                              </td>
                              <td class="{LANG_CLASS}" width="100" nowrap="nowrap"><img src="{THEME_COLOR_PATH}/images/icons/details.png" width="16" height="16" border="0" style="vertical-align:middle" /> <a href="{URL_EXPORT}" class="link" target="_blank">{TR_EXPORT}</a> </td>
                              <td class="{LANG_CLASS}" width="100" nowrap="nowrap"><img src="{THEME_COLOR_PATH}/images/icons/delete.png" width="16" height="16" border="0" style="vertical-align:middle" />
                                <!-- BDP: lang_delete_show -->
                                {TR_UNINSTALL} <!--{LANGUAGE}-->
                                <!-- EDP: lang_delete_show -->
                                <!-- BDP: lang_delete_link -->
                                <a href="#" onclick="action_delete('{URL_DELETE}', '{LANGUAGE}')" class="link">{TR_UNINSTALL}</a>
                                <!-- EDP: lang_delete_link --></td>
                            </tr>
                            <!-- EDP: lang_row -->
                          </table>
                        <table width="100%" cellspacing="5" cellpadding="5">
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td><span class="{LANG_CLASS}">
                                  <input name="Button" type="button" class="button" value="  {TR_SAVE}  " onclick="return sbmt(document.forms[0],'change_language');" /></span></td>
                            </tr>
                        </table>
                        <br />
                          <br />
                          <br />
                          <table width="100%" cellpadding="5" cellspacing="5">
                            <tr>
                              <td width="25">&nbsp;</td>
                              <td colspan="2" class="content3"><b>{TR_INSTALL_NEW_LANGUAGE}</b></td>
                            </tr>
                            <tr>
                              <td width="25" nowrap="nowrap">&nbsp;</td>
                              <td width="230" class="content2" nowrap="nowrap">{TR_LANGUAGE_FILE}</td>
                              <td nowrap="nowrap" class="content"><input type="file" name="lang_file" class="textinput" size="60" />
                              </td>
                            </tr>
                            <tr>
                              <td width="25" nowrap="nowrap">&nbsp;</td>
                              <td colspan="2" nowrap="nowrap"><input name="Button" type="button" class="button" value="  {TR_INSTALL}  " onclick="return sbmt(document.forms[0],'upload_language');" /></td>
                            </tr>
                          </table>
                        <input type="hidden" name="uaction" value="" />
                      </form></td>
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
