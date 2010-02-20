<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
</head>

<body>
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
            <td colspan="3"><table width="100%" border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td align="left"><table width="100%" cellpadding="5" cellspacing="5">
                    <tr>
                      <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_domains.png" width="25" height="25" alt="" /></td>
                      <td colspan="2" class="title">{TR_DELETE_DOMAIN} {DOMAIN_NAME}</td>
                    </tr>
                </table></td>
                <td width="27" align="right">&nbsp;</td>
              </tr>
              <tr>
                <td><table width="100%" cellpadding="1" cellspacing="1">
					<tr>
		                <td width="25">&nbsp;</td>
						<td colspan="3"><strong>{TR_DOMAIN_SUMMARY}</strong></td>
					</tr>
					<!-- BDP: mail_list -->
					<tr>
		                <td width="25">&nbsp;</td>
						<td colspan="3"><strong><i>{TR_DOMAIN_EMAILS}</i></strong></td>
					</tr>
					<!-- BDP: mail_item -->
					<tr>
		                <td width="25">&nbsp;</td>
						<td width="150">{MAIL_ADDR}</td>
						<td>{MAIL_TYPE}</td>
						<td>&nbsp;</td>
					</tr>
                    <!-- EDP: mail_item -->
                    <!-- EDP: mail_list -->

					<!-- BDP: ftp_list -->
					<tr>
		                <td width="25">&nbsp;</td>
						<td colspan="3"><strong><i>{TR_DOMAIN_FTPS}</i></strong></td>
					</tr>
					<!-- BDP: ftp_item -->
					<tr>
		                <td width="25">&nbsp;</td>
						<td width="150">{FTP_USER}</td>
						<td>{FTP_HOME}</td>
						<td>&nbsp;</td>
					</tr>
                    <!-- EDP: ftp_item -->
                    <!-- EDP: ftp_list -->

					<!-- BDP: als_list -->
					<tr>
		                <td width="25">&nbsp;</td>
						<td colspan="3"><strong><i>{TR_DOMAIN_ALIASES}</i></strong></td>
					</tr>
					<!-- BDP: als_item -->
					<tr>
		                <td width="25">&nbsp;</td>
						<td width="150">{ALS_NAME}</td>
						<td>{ALS_MNT}</td>
						<td>&nbsp;</td>
					</tr>
                    <!-- EDP: als_item -->
                    <!-- EDP: als_list -->

					<!-- BDP: sub_list -->
					<tr>
		                <td width="25">&nbsp;</td>
						<td colspan="3"><strong><i>{TR_DOMAIN_SUBS}</i></strong></td>
					</tr>
					<!-- BDP: sub_item -->
					<tr>
		                <td width="25">&nbsp;</td>
						<td width="150">{SUB_NAME}</td>
						<td>{SUB_MNT}</td>
						<td>&nbsp;</td>
					</tr>
                    <!-- EDP: sub_item -->
                    <!-- EDP: sub_list -->

					<!-- BDP: db_list -->
					<tr>
		                <td width="25">&nbsp;</td>
						<td colspan="3"><strong><i>{TR_DOMAIN_DBS}</i></strong></td>
					</tr>
					<!-- BDP: db_item -->
					<tr>
		                <td width="25">&nbsp;</td>
						<td width="150">{DB_NAME}</td>
						<td>{DB_USERS}</td>
						<td>&nbsp;</td>
					</tr>
                    <!-- EDP: db_item -->
                    <!-- EDP: db_list -->

                    <tr>
                    	<td colspan="4">&nbsp;</td>
                    </tr>
					<tr>
		                <td width="25">&nbsp;</td>
						<td colspan="3"><form name="reseller_delete_domain_frm" method="post" action="domain_delete.php"><input type="hidden" name="domain_id" value="{DOMAIN_ID}" />
		                	{TR_REALLY_WANT_TO_DELETE_DOMAIN}<br /><br/>
		                	<input type="checkbox" value="1" name="delete" />{TR_YES_DELETE_DOMAIN}<br/><br/>
		                	<input type="submit" value="{TR_BUTTON_DELETE}" />
		                </form></td>
					</tr>

                </table></td>
                <td width="27" align="right">&nbsp;</td>
              </tr>
            </table></td>
          </tr>
        </table></td>
	</tr>
</table>
</body>
</html>
