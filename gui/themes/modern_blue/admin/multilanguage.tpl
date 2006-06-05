<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_ADMIN_I18N_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="{THEME_COLOR_PATH}/css/vhcs.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/vhcs.js"></script>
<script language="JavaScript" type="text/JavaScript">
<!--
function action_delete(url) {
	if (!confirm("{TR_MESSAGE_DELETE}"))
		return false;

	location = url;
}
//-->
</script>


</head>

<body onLoad="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif','{THEME_COLOR_PATH}/images/icons/logout_a.gif','{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<table width="100%" border="0" cellspacing="0" cellpadding="0" height="100%">
  <tr>
    <td height="80" align="left" valign="top">
    <table width="100%"  border="0" cellspacing="0" cellpadding="0">
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
        <td valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td height="62" align="left" background="{THEME_COLOR_PATH}/images/content/table_background.jpg" class="title"><img src="{THEME_COLOR_PATH}/images/content/table_icon_multilanguage.jpg" width="85" height="62" align="absmiddle">{TR_MULTILANGUAGE}</td>
            <td width="27" align="right" background="{THEME_COLOR_PATH}/images/content/table_background.jpg"><img src="{THEME_COLOR_PATH}/images/content/table_icon_close.jpg" width="27" height="62"></td>
          </tr>
          <tr>
            <td valign="top">

			<form enctype="multipart/form-data" name="set_layout" method="post" action="multilanguage.php">
			<table width="100%" cellpadding="5" cellspacing="5">
			<!-- BDP: page_message -->
			  <tr>
                <td width="20">&nbsp;</td>
				<td colspan="5" class="title"><font color="#FF0000">{MESSAGE}</font>
             </td>
              </tr>
			 <!-- EDP: page_message -->
              <tr>
                <td width="20">&nbsp;</td>
                <td colspan="5" class="content3"><b>{TR_INSTALLED_LANGUAGES}</b></td>
                </tr>
              <tr>
                <td width="20">&nbsp;</td>
                <td class="content2"><b>{TR_LANGUAGE}</b></td>
                <td class="content2"><b>{TR_MESSAGES}</b></td>
                <td class="content2" width="100" align="center"><b>{TR_DEFAULT}</b></td>
                <td width="100" colspan="2" align="center" class="content2"><b>{TR_ACTION}</b></td>
              </tr>
            <!-- BDP: lang_row -->
              <tr>
                <td width="20" nowrap>&nbsp;</td>
                <td class="{LANG_CLASS}" nowrap><img src="{THEME_COLOR_PATH}/images/icons/bullet.gif" width="16" height="16" align="left"> {LANGUAGE}</td>
                <td class="{LANG_CLASS}" nowrap>{MESSAGES}</td>
                <td class="{LANG_CLASS}" width="100" nowrap align="center">
				<!-- BDP: lang_def -->
				{DEFAULT}
				<!-- EDP: lang_def -->

				<!-- BDP: lang_radio -->
                  <input type="radio" name="default_language" value="{LANG_VALUE}">
				  <!-- EDP: lang_radio -->
                </td>

			 <td class="{LANG_CLASS}" width="100" nowrap>
			<img src="{THEME_COLOR_PATH}/images/icons/details.gif" width="18" height="18" border="0" align="absmiddle">
			<a href="{URL_EXPORT}" class="link" target="_blank">{TR_EXPORT}</a>
			</td>

              <td class="{LANG_CLASS}" width="100" nowrap><img src="{THEME_COLOR_PATH}/images/icons/delete.gif" width="16" height="16" border="0" align="absmiddle">
			<!-- BDP: lang_delete_show -->
			{TR_UNINSTALL}
			 <!-- EDP: lang_delete_show -->

			<!-- BDP: lang_delete_link -->
			<a href="#" onClick="action_delete('{URL_DELETE}')" class="link">{TR_UNINSTALL}</a>
			 <!-- EDP: lang_delete_link --></td>
              </tr>
            <!-- EDP: lang_row -->
            </table>
            <table width="100%" cellspacing="5" cellpadding="5">
              <tr>
                <td width="20">&nbsp;</td>
                <td><span class="{LANG_CLASS}">
                  <input name="Button" type="button" class="button" value="  {TR_SAVE}  " onClick="return sbmt(document.forms[0],'change_language');"></td>
              </tr>
            </table>
            <br>
            <br>
            <br>
            <table width="100%" cellpadding="5" cellspacing="5">
              <tr>
                <td width="20">&nbsp;</td>
                <td colspan="2" class="content3"><b>{TR_INSTALL_NEW_LANGUAGE}</b></td>
              </tr>
              <tr>
                <td width="20" nowrap>&nbsp;</td>
                <td width="230" class="content2" nowrap>{TR_LANGUAGE_FILE}</td>
                <td nowrap class="content">
                  <input type="file" name="lang_file" class="textinput">
                </td>
              </tr>
              <tr>
                <td width="20" nowrap>&nbsp;</td>
                <td colspan="2" nowrap><input name="Button" type="button" class="button" value="  {TR_INSTALL}  " onClick="return sbmt(document.forms[0],'upload_language');"></td>
              </tr>
            </table>
			<input type="hidden" name="uaction" value="">
			</form>

			</td>
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
