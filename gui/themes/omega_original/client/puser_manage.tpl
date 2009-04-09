<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_HTACCESS}</title>
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
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/ftp_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/email_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<!-- BDP: logged_from --><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td height="20" nowrap="nowrap" class="backButton">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.png" width="16" height="16" border="0" style="vertical-align:middle" /></a> {YOU_ARE_LOGGED_AS}</td>
      </tr>
    </table>
	<!-- EDP: logged_from -->
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
            <td colspan="3">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td align="left">
<table width="100%" cellpadding="5" cellspacing="5">
	<tr>
		<td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_user.png" width="25" height="25" /></td>
		<td colspan="2" class="title">{TR_USER_MANAGE}</td>
	</tr>
</table>
	</td>
    <td width="27" align="right">&nbsp;</td>
  </tr>
  <tr>
    <td valign="top"><table width="100%" cellpadding="5" cellspacing="5">
      <tr>
        <td width="25" align="center" nowrap="nowrap">&nbsp;</td>
        <td nowrap="nowrap" class="content3" colspan="2"><b>{TR_USERNAME}</b></td>
        <td width="80" nowrap="nowrap" align="center" class="content3"><b>{TR_STATUS}</b></td>
        <td colspan="3" align="center" nowrap="nowrap" class="content3"><b>{TR_ACTION}</b></td>
      </tr>
      <!-- BDP: usr_msg -->
      <tr>
        <td nowrap="nowrap">&nbsp;</td>
        <td colspan="5" nowrap="nowrap" class="title"><span class="message">{USER_MESSAGE}</span></td>
      </tr>
      <!-- EDP: usr_msg -->
      <!-- BDP: pusres -->
      <tr>
        <td nowrap="nowrap" align="center">&nbsp;</td>
        <td nowrap="nowrap" class="content" colspan="2">{UNAME}</td>
        <td width="80" align="center" nowrap="nowrap" class="content">{USTATUS}</td>
        <td width="60" class="content" nowrap="nowrap" align="center">
        	<img src="{THEME_COLOR_PATH}/images/icons/users.gif" width="16" height="16" style="vertical-align:middle" />
        	<a href="protected_user_assign.php?uname={USER_ID}" class="link">{TR_GROUP}</a>
        </td>
        <td width="60" class="content" nowrap="nowrap" align="center">
        	<img src="{THEME_COLOR_PATH}/images/icons/edit.png" width="16" height="16" border="0" style="vertical-align:middle" />
        	<a href="{USER_EDIT_SCRIPT}" class="link">{USER_EDIT}</a>
        </td>
        <td width="60" align="center" nowrap="nowrap" class="content">
        	<img src="{THEME_COLOR_PATH}/images/icons/delete.png" width="16" height="16" border="0" style="vertical-align:middle" />
        	<a href="#" class="link" onclick="{USER_DELETE_SCRIPT}">{USER_DELETE}</a>
        </td>
      </tr>
      <!-- EDP: pusres -->
      <tr>
        <td>&nbsp;</td>
        <td colspan="5"><input name="Button" type="button" class="button" onclick="MM_goToURL('parent','protected_user_add.php');return document.MM_returnValue" value="{TR_ADD_USER}" />
          &nbsp;&nbsp; </td>
      </tr>
    </table></td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
</table>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td align="left">
<table width="100%" cellpadding="5" cellspacing="5">
	<tr>
		<td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_users2.png" width="25" height="25" /></td>
		<td colspan="2" class="title">{TR_GROUPS}</td>
	</tr>
</table>
	</td>
    <td width="27" align="right">&nbsp;</td>
  </tr>
  <tr>
    <td valign="top"><table width="100%" cellpadding="5" cellspacing="5">
      <tr>
        <td width="25" align="center" nowrap="nowrap">&nbsp;</td>
        <td width="203" nowrap="nowrap" class="content3"><b>{TR_GROUPNAME}</b></td>
        <td nowrap="nowrap" class="content3"><b>{TR_GROUP_MEMBERS}</b></td>
        <td nowrap="nowrap" align="center" class="content3"><b>{TR_STATUS}</b></td>
        <td width="100" colspan="2" align="center" nowrap="nowrap" class="content3"><b>{TR_ACTION}</b></td>
      </tr>
      <!-- BDP: grp_msg -->
      <tr>
        <td nowrap="nowrap">&nbsp;</td>
        <td colspan="5" nowrap="nowrap" class="title"><span class="message">{GROUP_MESSAGE}</span></td>
      </tr>
      <!-- EDP: grp_msg -->
      <!-- BDP: pgroups -->
      <tr>
        <td nowrap="nowrap" align="center">&nbsp;</td>
        <td nowrap="nowrap" class="content">{GNAME}</td>
        <td nowrap="nowrap" class="content"><!-- BDP: group_members -->
          {MEMBER}
          <!-- EDP: group_members -->
        </td>
        <td width="80" align="center" nowrap="nowrap" class="content">{GSTATUS}</td>
        <td width="100" colspan="2" align="center" nowrap="nowrap" class="content">
        	<img src="{THEME_COLOR_PATH}/images/icons/delete.png" width="16" height="16" border="0" style="vertical-align:middle" />
        	<a href="#" class="link" onclick="{GROUP_DELETE_SCRIPT}">{GROUP_DELETE}</a>
        </td>
      </tr>
      <!-- EDP: pgroups -->
      <tr>
        <td>&nbsp;</td>
        <td colspan="3"><input name="Button2" type="button" class="button" value="{TR_ADD_GROUP}" onclick="MM_goToURL('parent','protected_group_add.php');return document.MM_returnValue" />
          &nbsp; </td>
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
