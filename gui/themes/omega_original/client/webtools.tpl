<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_CLIENT_WEBTOOLS_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
  <link href="{THEME_COLOR_PATH}/css/imscp.css" rel="stylesheet" type="text/css" />
  <script type="text/javascript" src="{THEME_COLOR_PATH}/css/imscp.js"></script>
<!--[if lt IE 7.]>
<script defer type="text/javascript" src="{THEME_COLOR_PATH}/css/pngfix.js"></script>
<![endif]-->
 </head>

 <body onLoad="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.png','{THEME_COLOR_PATH}/images/icons/domains_a.png','{THEME_COLOR_PATH}/images/icons/ftp_a.png','{THEME_COLOR_PATH}/images/icons/general_a.png','{THEME_COLOR_PATH}/images/icons/email_a.png','{THEME_COLOR_PATH}/images/icons/webtools_a.png','{THEME_COLOR_PATH}/images/icons/statistics_a.png','{THEME_COLOR_PATH}/images/icons/support_a.png')">
  <table width="100%" border="0" cellspacing="0" cellpadding="0" style="height:100%;padding:0;margin:0 auto;">
   <!-- BDP: logged_from -->
   <tr>
    <td colspan="3" height="20" nowrap="nowrap" class="backButton">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.png" width="16" height="16" border="0" style="vertical-align:middle" alt="" /></a> {YOU_ARE_LOGGED_AS}</td>
   </tr>
   <!-- EDP: logged_from -->
   <tr>
    <td align="left" valign="top" style="vertical-align: top; width: 195px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_left.jpg" width="195" height="56" border="0" alt="i-MSCP Logogram" /></td>
    <td style="height: 56px; width:100%; background-color: #0f0f0f"><img src="{THEME_COLOR_PATH}/images/top/top_left_bg.jpg" style="width:582px; height:56px; border-style:none;" alt="" /></td>
    <td style="width: 73px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_right.jpg" width="73" height="56" border="0" alt="" /></td>
   </tr>
   <tr>
    <td style="width: 195px; vertical-align: top;">{MENU}</td>
    <td colspan="2" style="vertical-align: top;">
     <table style="width: 100%; padding:0;margin:0;" cellspacing="0">
      <tr style="height:95px;">
       <td style="padding-left:30px; width: 100%; background-image: url({THEME_COLOR_PATH}/images/top/middle_bg.jpg);">{MAIN_MENU}</td>
       <td style="padding:0;margin:0;text-align: right; width: 73px;vertical-align: top;"><img src="{THEME_COLOR_PATH}/images/top/middle_right.jpg" width="73" height="95" border="0" alt="" /></td>
      </tr>
      <tr>
       <td height="415" colspan="3">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
         <tr>
          <td align="left">
           <table width="100%" cellpadding="5" cellspacing="5">
            <tr>
             <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_tools.png" width="25" height="25" alt="" /></td>
             <td colspan="2" class="title">{TR_MENU_WEBTOOLS}</td>
            </tr>
           </table>
          </td>
          <td width="27" align="right">&nbsp;</td>
         </tr>
         <tr>
          <td>
           <table width="100%" cellspacing="7">
		   <tr>
             <td>&nbsp;</td>
             <td class="content">
              <table width="100%" border="0" cellspacing="0" cellpadding="0">
               <tr>
                <td width="65"><p><a href="protected_areas.php"><img src="{THEME_COLOR_PATH}/images/icons/htaccessicon.png" width="46" height="46" border="0" alt="" /></a></p></td>
                <td><a href="protected_areas.php" class="link">{TR_HTACCESS}</a><br />
                 {TR_HTACCESS_TEXT}</td>
               </tr>
              </table>
             </td>
            </tr>
		   <tr>
             <td>&nbsp;</td>
             <td class="content">
              <table width="100%" border="0" cellspacing="0" cellpadding="0">
               <tr>
                <td width="65"><p><a href="protected_user_manage.php"><img src="{THEME_COLOR_PATH}/images/icons/manage_users_a.png" width="47" height="46" border="0" alt="" /></a></p></td>
                <td><a href="protected_user_manage.php" class="link">{TR_HTACCESS_USER}</a><br />
                 {TR_HTACCESS_USER}</td>
               </tr>
              </table>
             </td>
            </tr>
            <tr>
             <td width="25">&nbsp;</td>
             <td class="content">
              <table width="100%" border="0" cellspacing="0" cellpadding="0">
               <tr>
                <td width="65"><a href="error_pages.php"><img src="{THEME_COLOR_PATH}/images/icons/errordocsicon.png" width="46" height="46" border="0" alt="" /></a></td>
                <td><a href="error_pages.php" class="link">{TR_ERROR_PAGES}</a><br />
                 {TR_ERROR_PAGES_TEXT}</td>
               </tr>
              </table>
             </td>
            </tr>
            <!-- BDP: active_backup -->
            <tr>
             <td>&nbsp;</td>
             <td class="content">
              <table width="100%" border="0" cellspacing="0" cellpadding="0">
               <tr>
                <td width="65"><a href="backup.php"><img src="{THEME_COLOR_PATH}/images/icons/backupicon.png" width="46" height="46" border="0" alt="" /></a></td>
                <td><a href="backup.php" class="link">{TR_BACKUP}</a><br />
                 {TR_BACKUP_TEXT}</td>
               </tr>
              </table>
             </td>
            </tr>
            <!-- EDP: active_backup -->
            <!-- BDP: active_email -->
            <tr>
             <td>&nbsp;</td>
             <td class="content">
              <table width="100%" border="0" cellspacing="0" cellpadding="0">
               <tr>
                <td width="65"><a href="{WEBMAIL_PATH}" target="{WEBMAIL_TARGET}"><img src="{THEME_COLOR_PATH}/images/icons/webmailicon.png" width="46" height="46" border="0" alt="" /></a></td>
                <td><a href="{WEBMAIL_PATH}" target="{WEBMAIL_TARGET}" class="link">{TR_WEBMAIL}</a><br />
                 {TR_WEBMAIL_TEXT}</td>
               </tr>
              </table>
             </td>
            </tr>
            <!-- EDP: active_email -->
            <tr>
             <td>&nbsp;</td>
             <td class="content">
			  <table width="100%" border="0" cellspacing="0" cellpadding="0">
               <tr>
                <td width="65"><a href="{FILEMANAGER_PATH}" target="{FILEMANAGER_TARGET}"><img src="{THEME_COLOR_PATH}/images/icons/filemanagericon.png" width="46" height="46" border="0" alt="" /></a></td>
                <td><a href="{FILEMANAGER_PATH}" target="{FILEMANAGER_TARGET}" class="link">{TR_FILEMANAGER}</a><br />
                 {TR_FILEMANAGER_TEXT}</td>
               </tr>
              </table>
             </td>
            </tr>
            <!-- BDP: t_software_support -->
 			<tr>
 				<td>&nbsp;</td>
 				<td class="content">
 					<table width="100%"  border="00" cellspacing="0" cellpadding="0">
 						<tr>
 							<td width="65"><a href="software.php"><img src="{THEME_COLOR_PATH}/images/icons/cd_big.png" width="46" height="46" border="0"></a></td>
 							<td><a href="software.php" class="link">{TR_SOFTWARE_MENU}</a><br>
 							{TR_SOFTWARE_SUPPORT}</td>
 						</tr>
 					</table>
 				</td>
 			</tr>
 			<!-- EDP: t_software_support -->
			<!-- BDP: active_awstats -->
            <tr>
             <td>&nbsp;</td>
             <td class="content">
			  <table width="100%" border="0" cellspacing="0" cellpadding="0">
               <tr>
                <td width="65"><a href="{AWSTATS_PATH}" target="{AWSTATS_TARGET}"><img src="{THEME_COLOR_PATH}/images/icons/awstatsicon.png" width="46" height="46" border="0" alt="" /></a></td>
                <td><a href="{AWSTATS_PATH}" target="{AWSTATS_TARGET}" class="link">{TR_AWSTATS}</a><br />
                 {TR_AWSTATS_TEXT}</td>
               </tr>
              </table>
             </td>
            </tr>
		    <!-- EDP: active_awstats -->
           </table>
          </td>
          <td>&nbsp;</td>
         </tr>
         <tr>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
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
