<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_RESELLER_LAYOUT_DATA_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/custom_link_a.gif')">
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
	    <td colspan="2" style="vertical-align: top;">
			<table style="width: 100%; padding:0;margin:0;" cellspacing="0">
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
											<td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_layout.png" width="25" height="25" alt="" /></td>
											<td colspan="2" class="title">{TR_LAYOUT_SETTINGS}</td>
										</tr>
									</table>
								</td>
								<td width="27" align="right">&nbsp;</td>
							</tr>
							<tr>
								<td>
									<table width="100%" cellpadding="0" cellspacing="0">
										<tr>
											<td width="40">&nbsp;</td>
											<td align="left">
											<!-- BDP: page_message -->
												<div align="left" class="title"><span class="message">{MESSAGE}</span></div>
											<!-- EDP: page_message -->
												<table width="100%" cellpadding="5" cellspacing="5">
													<tr>
														<td colspan="2" class="content3"><b>{TR_UPLOAD_LOGO}</b></td>
													</tr>
													<tr>
														<td width="230" class="content2" nowrap="nowrap">{TR_LOGO_FILE}</td>
														<td class="content" nowrap="nowrap">
															<form enctype="multipart/form-data" name="set_layout" method="post" action="settings_layout.php">
																<input type="file" name="logo_file" size="40" />
																<input type="hidden" name="uaction" value="upload_logo" />
																<input name="Submit" type="submit" class="button" value=" {TR_UPLOAD} " />
															</form>
														</td>
													</tr>
													<tr>
														<td nowrap="nowrap">&nbsp;</td>
														<td nowrap="nowrap">&nbsp;</td>
													</tr>
													<!-- BDP: logo_remove_button -->
													<tr>
														<td class="content2"><img src="{OWN_LOGO}" alt="reseller logo" /></td>
														<td class="content">
															<form method="post" action="settings_layout.php">
																<input type="hidden" name="uaction" value="delete_logo" />
																<input name="Submit" type="submit" class="button" value=" {TR_REMOVE} " />
															</form>
														</td>
													</tr>
													<!-- EDP: logo_remove_button -->
												</table>
												<!-- end of content -->
											</td>
										</tr>
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
