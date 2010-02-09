<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_CLIENT_MANAGE_USERS_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
<script type="text/javascript">
<!--
function action_delete(url, mailacc) {
	if (!confirm(sprintf("{TR_MESSAGE_DELETE}", mailacc)))
		return false;
	location = url;
}
//-->
</script>
<style type="text/css">
<!--
.style1 {font-size: 9px}
-->
</style>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/ftp_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/email_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
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
<table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td align="left">
<table width="100%" cellpadding="5" cellspacing="5">
	<tr>
		<td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_email.png" width="25" height="25" alt="" /></td>
		<td colspan="2" class="title">{TR_MAIL_USERS}</td>
	</tr>
</table>
			</td>
            <td width="27" align="right">&nbsp;</td>
          </tr>
          <tr>
            <td><table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                  <td width="25">&nbsp;</td>
                  <td valign="top"><table width="100%" cellspacing="7">
                    <!-- BDP: page_message -->
                <tr>
                  <td colspan="5" nowrap="nowrap" class="title"><span class="message">{MESSAGE}</span></td>
                </tr>
                    <!-- EDP: page_message -->
                <tr>
                   <td nowrap="nowrap" class="content3" align="center" width="200"><b>{TR_MAIL}</b></td>
                   <td nowrap="nowrap" class="content3" align="center" width="210"><b>{TR_TYPE}</b></td>
                   <td nowrap="nowrap" class="content3" align="center" width="100"><b>{TR_STATUS}</b></td>
                   <td nowrap="nowrap" class="content3" align="center" width="180" colspan="5"><b>{TR_ACTION}</b></td>
                </tr>
                    <!-- BDP: mail_message -->
                <tr>
                   <td colspan="5" class="title"><span class="message">{MAIL_MSG}</span></td>
                </tr>
                    <!-- EDP: mail_message -->
                    <!-- BDP: mail_item -->
                <tr class="hl">
                   <td nowrap="nowrap" class="{ITEM_CLASS}" width="200"><img src="{THEME_COLOR_PATH}/images/icons/mail_icon.png" width="16" height="16" style="vertical-align:middle" alt="" />&nbsp;{MAIL_ACC}
                   	<!-- BDP: auto_respond -->
			<div style="display: {AUTO_RESPOND_VIS};">
				<br />
				<span class="style1">
					{TR_AUTORESPOND}: [&nbsp;&nbsp;
                          		<a href="{AUTO_RESPOND_DISABLE_SCRIPT}" class="link">{AUTO_RESPOND_DISABLE}</a>&nbsp;&nbsp;
					<a href="{AUTO_RESPOND_EDIT_SCRIPT}" class="link">{AUTO_RESPOND_EDIT}</a>]
				 </span>
			</div>
			<!-- EDP: auto_respond -->
                      </td>
                   <td nowrap="nowrap" class="{ITEM_CLASS}" width="210">{MAIL_TYPE}</td>
                   <td nowrap="nowrap" class="{ITEM_CLASS}" align="center" width="100">{MAIL_STATUS}</td>
                   <td nowrap="nowrap" class="{ITEM_CLASS}" align="center" width="85">
                   	<img src="{THEME_COLOR_PATH}/images/icons/delete.png" width="16" height="16" border="0" style="vertical-align:middle" alt="" /> <a href="#" class="link" onclick="action_delete('{MAIL_DELETE_SCRIPT}', '{MAIL_ACC}')">{MAIL_DELETE}</a>
                   </td>
                   <td nowrap="nowrap" class="{ITEM_CLASS}" align="center" width="85">
                   	<img src="{THEME_COLOR_PATH}/images/icons/edit.png" width="16" height="16" border="0" style="vertical-align:middle" alt="" /> <a href="{MAIL_EDIT_SCRIPT}" class="link">{MAIL_EDIT}</a>
                   	</td>
                    </tr>
                    <!-- EDP: mail_item -->
                    <!-- BDP: mails_total -->
                    <tr>
                      <td colspan="5" align="right" nowrap="nowrap" class="content3">{TR_TOTAL_MAIL_ACCOUNTS}:&nbsp;<b>{TOTAL_MAIL_ACCOUNTS}</b>/{ALLOWED_MAIL_ACCOUNTS}</td>
                    </tr>
		<!-- EDP: mails_total -->
		<!-- BDP: default_mails_form -->
		    <tr>
		    	<td colspan="2">
		    		<form action="mail_accounts.php" method="post" name="showdefault" id="showdefault">
		    			<input type="hidden" name="uaction" value="{VL_DEFAULT_EMAILS_BUTTON}" />
		    			<input name="Submit" type="submit" class="button" value="{TR_DEFAULT_EMAILS_BUTTON}" />
		    		</form>
		    	</td>
		    </tr>
		<!-- EDP: default_mails_form -->
                  </table></td>
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
