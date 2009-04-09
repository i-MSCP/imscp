<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_CLIENT_WEBTOOLS_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
  <link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
  <script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
 </head>

 <body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/ftp_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif','{THEME_COLOR_PATH}/images/icons/email_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
  <!-- BDP: logged_from -->
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
   <tr>
    <td height="20" nowrap="nowrap" class="backButton">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.png" width="16" height="16" border="0" style="vertical-align:middle" /></a> {YOU_ARE_LOGGED_AS}</td>
   </tr>
  </table>
  <!-- EDP: logged_from -->
  <table width="100%" border="0" cellspacing="0" cellpadding="0" height="100%" style="border-collapse: collapse;padding:0;margin:0;">
   <tr>
    <td align="left" valign="top" style="vertical-align: top; width: 195px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_left.jpg" border="0" alt="ispCP Logogram" /></td>
    <td colspan="2" style="height: 56px; width:100%; background-image: url({THEME_COLOR_PATH}/images/top/top_bg.jpg)"><img src="{THEME_COLOR_PATH}/images/top/top_left_bg.jpg" border="0" alt="" /></td>
    <td style="width: 73px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_right.jpg" border="0" alt="" /></td>
   </tr>
   <tr>
    <td style="width: 195px; vertical-align: top;">{MENU}</td>
    <td colspan="3" style="vertical-align: top;"><table style="width: 100%; border-collapse: collapse;padding:0;margin:0;">
      <tr height="95">
       <td style="padding-left:30px; width: 100%; background-image: url({THEME_COLOR_PATH}/images/top/middle_bg.jpg);">{MAIN_MENU}</td>
       <td style="padding:0;margin:0;text-align: right; width: 73px;vertical-align: top;"><img src="{THEME_COLOR_PATH}/images/top/middle_right.jpg" border="0" alt="" /></td>
      </tr>
      <tr>
       <td height="415" colspan="3"><table width="100%" border="0" cellspacing="0" cellpadding="0">
         <tr>
          <td align="left"><table width="100%" cellpadding="5" cellspacing="5">
            <tr>
             <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_tools.png" width="25" height="25" /></td>
             <td colspan="2" class="title">{TR_MENU_WEBTOOLS}</td>
            </tr>
           </table></td>
          <td width="27" align="right">&nbsp;</td>
         </tr>
         <tr>
          <td><table width="100%" cellspacing="7">
		   <tr>
             <td>&nbsp;</td>
             <td class="content"><table width="100%" border="0" cellspacing="0" cellpadding="0">
               <tr>
                <td width="65"><p><a href="protected_areas.php"><img src="{THEME_COLOR_PATH}/images/icons/htaccessicon.gif" width="46" height="46" border="0" /></a></p></td>
                <td><a href="protected_areas.php" class="link">{TR_HTACCESS}</a><br />
                 {TR_HTACCESS_TEXT}</td>
               </tr>
              </table></td>
            </tr>
		   <tr>
             <td>&nbsp;</td>
             <td class="content"><table width="100%" border="0" cellspacing="0" cellpadding="0">
               <tr>
                <td width="65"><p><a href="protected_user_manage.php"><img src="{THEME_COLOR_PATH}/images/icons/manage_users_a.gif" width="46" height="46" border="0" /></a></p></td>
                <td><a href="protected_user_manage.php" class="link">{TR_HTACCESS_USER}</a><br />
                 {TR_HTACCESS_USER}</td>
               </tr>
              </table></td>
            </tr>
            <tr>
             <td width="25">&nbsp;</td>
             <td class="content"><table width="100%" border="0" cellspacing="0" cellpadding="0">
               <tr>
                <td width="65"><a href="error_pages.php"><img src="{THEME_COLOR_PATH}/images/icons/errordocsicon.gif" width="46" height="46" border="0" /></a></td>
                <td><a href="error_pages.php" class="link">{TR_ERROR_PAGES}</a><br />
                 {TR_ERROR_PAGES_TEXT}</td>
               </tr>
              </table></td>
            </tr>
            <tr>
             <td>&nbsp;</td>
             <td class="content"><table width="100%" border="0" cellspacing="0" cellpadding="0">
               <tr>
                <td width="65"><a href="backup.php"><img src="{THEME_COLOR_PATH}/images/icons/backupicon.gif" width="46" height="46" border="0" /></a></td>
                <td><a href="backup.php" class="link">{TR_BACKUP}</a><br />
                 {TR_BACKUP_TEXT}</td>
               </tr>
              </table></td>
            </tr>
            <!-- BDP: active_email -->
            <tr>
             <td>&nbsp;</td>
             <td class="content"><table width="100%" border="0" cellspacing="0" cellpadding="0">
               <tr>
                <td width="65"><a href="{WEBMAIL_PATH}" target="{WEBMAIL_TARGET}"><img src="{THEME_COLOR_PATH}/images/icons/webmailicon.gif" width="46" height="46" border="0" /></a></td>
                <td><a href="{WEBMAIL_PATH}" target="{WEBMAIL_TARGET}" class="link">{TR_WEBMAIL}</a><br />
                 {TR_WEBMAIL_TEXT}</td>
               </tr>
              </table></td>
            </tr>
            <!-- EDP: active_email -->
            <tr>
             <td>&nbsp;</td>
             <td class="content">
			  <table width="100%" border="0" cellspacing="0" cellpadding="0">
               <tr>
                <td width="65"><a href="{FILEMANAGER_PATH}" target="{FILEMANAGER_TARGET}"><img src="{THEME_COLOR_PATH}/images/icons/filemanagericon.gif" width="46" height="46" border="0" /></a></td>
                <td><a href="{FILEMANAGER_PATH}" target="{FILEMANAGER_TARGET}" class="link">{TR_FILEMANAGER}</a><br />
                 {TR_FILEMANAGER_TEXT}</td>
               </tr>
              </table></td>
            </tr>
			<!-- BDP: active_awstats -->
            <tr>
             <td>&nbsp;</td>
             <td class="content">
			  <table width="100%" border="0" cellspacing="0" cellpadding="0">
               <tr>
                <td width="65"><a href="{AWSTATS_PATH}" target="{AWSTATS_TARGET}"><img src="{THEME_COLOR_PATH}/images/icons/awstatsicon.gif" alt="awstats" width="46" height="46" border="0" /></a></td>
                <td><a href="{AWSTATS_PATH}" target="{AWSTATS_TARGET}" class="link">{TR_AWSTATS}</a><br />
                 {TR_AWSTATS_TEXT}</td>
               </tr>
              </table></td>
            </tr>
		    <!-- EDP: active_awstats -->
           </table></td>
          <td>&nbsp;</td>
         </tr>
         <tr>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
         </tr>
        </table>
	   </td>
      </tr>
     </table></td>
   </tr>
  </table>
 </body>
</html>
