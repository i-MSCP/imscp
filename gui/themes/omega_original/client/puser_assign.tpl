<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_HTACCESS}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
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
            <td colspan="3"><form name="puser_assign" method="post" action="protected_user_assign.php?uname={UNAME}"><table width="100%" border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td align="left"><table width="100%" cellpadding="5" cellspacing="5">
                    <tr>
                      <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_users.png" width="25" height="25" alt="" /></td>
                      <td colspan="2" class="title">{TR_USER_ASSIGN}</td>
                    </tr>
                </table></td>
                <td width="27" align="right">&nbsp;</td>
              </tr>
              <tr>
                <td valign="top"><table width="100%" cellpadding="5" cellspacing="5">
                    <!-- BDP: page_message -->
                    <tr>
                      <td>&nbsp;</td>
                      <td colspan="4" class="title"><span class="message">{MESSAGE}</span></td>
                    </tr>
                    <!-- EDP: page_message -->
                    <tr>
                      <td>&nbsp;</td>
                      <td colspan="3" class="content3"><strong>{UNAME}</strong></td>
                    </tr>
                    <!-- BDP: in_group -->
                    <tr>
                      <td width="25">&nbsp;</td>
                      <td width="200" class="content2"> {TR_MEMBER_OF_GROUP}</td>
                      <td class="content"><select name="groups_in">
                          <!-- BDP: already_in -->
                          <option value="{GRP_IN_ID}">{GRP_IN}</option>
                          <!-- EDP: already_in -->
                      </select></td>
                      <td class="content"><!-- BDP: remove_button -->
                          <input name="Submit" type="submit" class="button" value="  {TR_REMOVE}  " onclick="return sbmt(document.forms[0],'remove');" />
                          <!-- EDP: remove_button -->
                      </td>
                    </tr>
                    <!-- EDP: in_group -->
                    <!-- BDP: not_in_group -->
                    <tr>
                      <td width="25">&nbsp;</td>
                      <td width="200" class="content2"> {TR_SELECT_GROUP}</td>
                      <td class="content"><select name="groups">
                          <!-- BDP: grp_avlb -->
                          <option value="{GRP_ID}">{GRP_NAME}</option>
                          <!-- EDP: grp_avlb -->
                        </select>
                      </td>
                      <td class="content"><!-- BDP: add_button -->
                          <input name="Submit" type="submit" class="button" value="  {TR_ADD}  " onclick="return sbmt(document.forms[0],'add');" />
                          <!-- EDP: add_button -->
                      </td>
                    </tr>
                    <!-- EDP: not_in_group -->
                    <tr>
                      <td>&nbsp;</td>
                      <td colspan="3"><input type="hidden" name="nadmin_name" value="{UID}" />
                          <input type="hidden" name="uaction" value="" />
                          <input name="Submit" type="submit" class="button" value="  {TR_BACK}  " onclick="MM_goToURL('parent','protected_user_manage.php');return document.MM_returnValue" />
                      </td>
                    </tr>
                </table></td>
                <td>&nbsp;</td>
              </tr>
              <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
              </tr>
              </table></form></td>
           </tr>
        </table></td>
	</tr>
</table>
</body>
</html>
