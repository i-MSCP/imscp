<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_CLIENT_MANAGE_SQL_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
  <link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
  <script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
  <script type="text/javascript">
<!--
function action_delete(url, sql) {
	if (!confirm(sprintf("{TR_MESSAGE_DELETE}", sql)))
		return false;
	location = url;
}
//-->
  </script>
 </head>

 <body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/ftp_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/email_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
  <!-- BDP: logged_from -->
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
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
   <td colspan="2" style="vertical-align: top;">
    <table style="width: 100%; border-collapse: collapse;padding:0;margin:0;">
     <tr style="height:95px;">
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
            <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_sql.png" width="25" height="25" /></td>
             <td colspan="2" class="title">{TR_MANAGE_SQL}</td>
            </tr>
           </table>
          </td>
          <td width="27" align="right">&nbsp;</td>
         </tr>
         <tr>
          <td>
           <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
             <td width="40">&nbsp;</td>
             <td valign="top">
              <table width="100%" cellpadding="5" cellspacing="5">
               <!-- BDP: page_message -->
               <tr>
                <td colspan="4" class="title"><span class="message">{MESSAGE}</span></td>
               </tr>
               <!-- EDP: page_message -->
               <!-- BDP: db_list -->
               <tr>
                <td width="60%" class="content3">&nbsp;&nbsp;<b>{TR_DATABASE}</b></td>
                <td colspan="3" align="center" class="content3"><b>{TR_ACTION}</b></td>
               </tr>
               <!-- BDP: db_list -->
               <tr>
                <td height="48" align="left" class="content4">&nbsp;&nbsp;&nbsp;<strong><img src="{THEME_COLOR_PATH}/images/icons/database_small.png" width="16" height="16" style="vertical-align:middle" />&nbsp;{DB_NAME}</strong></td>
                <td colspan="2" width="16%" align="left" class="content4">&nbsp;&nbsp;<img src="{THEME_COLOR_PATH}/images/icons/add_user.gif" alt="add_user" width="26" height="16" border="0" style="vertical-align:middle" />&nbsp;<a href="sql_user_add.php?id={DB_ID}" class="link">{TR_ADD_USER}</a></td>
                <td align="left" class="content4">&nbsp;&nbsp;<img src="{THEME_COLOR_PATH}/images/icons/delete.png" width="16" height="16" border="0" style="vertical-align:middle" />&nbsp;&nbsp;<a href="#" class="link" onclick="action_delete('sql_database_delete.php?id={DB_ID}', '{DB_NAME}')">{TR_DELETE}</a></td>
               </tr>
               <!-- BDP: db_message -->
               <tr>
                <td height="28" colspan="4" class="title"><span class="message">&nbsp;&nbsp;{DB_MSG}</span></td>
               </tr>
               <!-- EDP: db_message -->
               <!-- BDP: user_list -->
               <tr>
                <td height="48" align="left" class="content">&nbsp;&nbsp;&nbsp;<img src="{THEME_COLOR_PATH}/images/icons/users.gif" width="21" height="21" style="vertical-align:middle" />&nbsp;{DB_USER}</td>
                <td width="14%" align="left" class="content">&nbsp;&nbsp;&nbsp;<img src="{THEME_COLOR_PATH}/images/icons/pma.gif" width="16" height="16" border="0" style="vertical-align:middle" />&nbsp;<a href="sql_auth.php?id={USER_ID}" class="link" target="_blank">{TR_PHP_MYADMIN}</a></td>
                <td align="left" class="content">&nbsp;&nbsp;<img src="{THEME_COLOR_PATH}/images/icons/change_password.png" width="16" height="16" border="0" style="vertical-align:middle" />&nbsp;<a href="sql_change_password.php?id={USER_ID}" class="link">{TR_CHANGE_PASSWORD}</a></td>
                <td align="left" class="content">&nbsp;&nbsp;<img src="{THEME_COLOR_PATH}/images/icons/delete.png" width="16" height="16" border="0" style="vertical-align:middle" />&nbsp;&nbsp;<a href="#" class="link" onclick="action_delete('sql_delete_user.php?id={USER_ID}', '{DB_USER}')">{TR_DELETE}</a></td>
               </tr>
               <!-- EDP: user_list -->
               <!-- EDP: db_list -->
              </table>
             </td>
            </tr>
           </table>
          </td>
         </tr>
        </table>
       </td>
      </tr>
     </table>
    </td>
   </tr>
  </table>
 </body>
</html>
