<html xmlns="http://www.w3.org/1999/xhtml">
<head>
 <title>{TR_CLIENT_ADD_SUBDOMAIN_PAGE_TITLE}</title>
 <meta name="robots" content="nofollow, noindex" />
 <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
 <meta http-equiv="Content-Style-Type" content="text/css" />
 <meta http-equiv="Content-Script-Type" content="text/javascript" />
 <link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
 <script type="text/javascript" src="{THEME_COLOR_PATH}/css/jquery.js"></script>
 <script type="text/javascript" src="{THEME_COLOR_PATH}/css/jquery.ispcpTooltips.js"></script>
 <script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
 <!--[if lt IE 7.]>
 <script defer type="text/javascript" src="{THEME_COLOR_PATH}/css/pngfix.js"></script>
 <![endif]-->
 <script type="text/javascript">
/*<![CDATA[*/

	function encode_idna(dmnName){
		reg =  /[\x80-\xff]/;

		if(reg.test(dmnName)) {
			dmnName= $.ajax(
				{
					type: 'GET',
					url: $(location).attr('pathname') + '?idn=' + dmnName,
					async: false
				}
			).responseText;
		}

		return dmnName;
	}

	$(document).ready(
		function() {
			// Tooltip
			$('#dmn_help').ispCPtooltips({msg:"{TR_DMN_HELP}"});
			// Encode IDNA for mount point
			$('#subdomain_name').change(function() {
				$('#subdomain_mnt_pt').val('/' + encode_idna($(this).val()));
			});
		}
	);

	function setRatioAlias(){
		document.forms[0].elements['dmn_type'][1].checked = true;
	}

	function setForwardReadonly(obj){
		if(obj.value == 1) {
			document.forms[0].elements['forward'].readOnly = false;
			document.forms[0].elements['forward_prefix'].disabled = false;
		} else {
			document.forms[0].elements['forward'].readOnly = true;
			document.forms[0].elements['forward'].value = '';
			document.forms[0].elements['forward_prefix'].disabled = true;
		}
	}
/*]]>*/
</script>
</head>
	<body onLoad="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.png','{THEME_COLOR_PATH}/images/icons/domains_a.png','{THEME_COLOR_PATH}/images/icons/ftp_a.png','{THEME_COLOR_PATH}/images/icons/general_a.png' ,'{THEME_COLOR_PATH}/images/icons/email_a.png','{THEME_COLOR_PATH}/images/icons/webtools_a.png','{THEME_COLOR_PATH}/images/icons/statistics_a.png','{THEME_COLOR_PATH}/images/icons/support_a.png','{THEME_COLOR_PATH}/images/icons/custom_link_a.png')">
		<table width="100%" border="0" cellspacing="0" cellpadding="0" style="height:100%;padding:0;margin:0 auto;">
			<!-- BDP: logged_from -->
			<tr>
				 <td colspan="3" height="20" nowrap="nowrap" class="backButton">
					&nbsp;&nbsp;&nbsp;
					<a href="change_user_interface.php?action=go_back">
						<img src="{THEME_COLOR_PATH}/images/icons/close_interface.png" width="16" height="16" border="0" style="vertical-align:middle" alt="" />
					</a>
					&nbsp;
					{YOU_ARE_LOGGED_AS}
				</td>
			</tr>
			<!-- EDP: logged_from -->
			<tr>
				<td align="left" valign="top" style="vertical-align: top; width: 195px; height: 56px;">
					<img src="{THEME_COLOR_PATH}/images/top/top_left.jpg" width="195" height="56" border="0" alt="ispCP Logogram" />
				</td>
				<td style="height: 56px; width:100%; background-color: #0f0f0f">
					<img src="{THEME_COLOR_PATH}/images/top/top_left_bg.jpg" width="582" height="56" border="0" alt="" />
				</td>
				<td style="width: 73px; height: 56px;">
					<img src="{THEME_COLOR_PATH}/images/top/top_right.jpg" width="73" height="56" border="0" alt="" />
				</td>
			</tr>
			<tr>
				<td style="width: 195px; vertical-align: top;">
					{MENU}
				</td>
				<td colspan="2" style="vertical-align: top;">
					<table style="width: 100%; padding:0;margin:0;" cellspacing="0">
						<tr style="height:95px;">
							<td style="padding-left:30px; width: 100%; background-image: url({THEME_COLOR_PATH}/images/top/middle_bg.jpg);">
								{MAIN_MENU}
							</td>
							<td style="padding:0;margin:0;text-align: right; width: 73px;vertical-align: top;">
								<img src="{THEME_COLOR_PATH}/images/top/middle_right.jpg" width="73" height="95" border="0" alt="" />
							</td>
						</tr>
						<tr>
							<td colspan="3">
								<table width="100%" border="0" cellspacing="0" cellpadding="0">
									<tr>
										<td align="left">
											<table width="100%" cellpadding="5" cellspacing="5">
												<tr>
													<td width="25">
														<img src="{THEME_COLOR_PATH}/images/content/table_icon_domains.png" width="25" height="25" alt="" />
													</td>
													<td colspan="2" class="title">
														{TR_ADD_SUBDOMAIN}
													</td>
												</tr>
											</table>
										</td>
										<td width="27" align="right">
											&nbsp;
										</td>
									</tr>
									<tr>
										<td valign="top">
											<table width="100%" border="0" cellspacing="0" cellpadding="0">
												<tr>
													<td width="40">
														&nbsp;
													</td>
													<td valign="top">
														<form name="client_add_subdomain_frm" method="post" action="subdomain_add.php">
															<table width="100%" cellspacing="5">
																<!-- BDP: page_message -->
																<tr>
																	<td colspan="2" class="title">
																		<span class="message">
																			{MESSAGE}
																		</span>
																	</td>
																</tr>
																<!-- EDP: page_message -->
																<tr>
																	<td width="250" class="content2">
																		<label for="subdomain_name">
																			{TR_SUBDOMAIN_NAME}
																		</label>
																		&nbsp;
																		<img id="dmn_help" src="{THEME_COLOR_PATH}/images/icons/help.png" width="16" height="16" alt="" />
																	</td>
																	<td class="content">
																		<input type="text" name="subdomain_name" id="subdomain_name" value="{SUBDOMAIN_NAME}" style="width:170px" class="textinput" onBlur="makeUser();" />
																		<input type="radio" name="dmn_type" value="dmn" {SUB_DMN_CHECKED} />{DOMAIN_NAME}
																		<!-- BDP: to_alias_domain -->
																		<input type="radio" name="dmn_type" value="als" {SUB_ALS_CHECKED} />
																		<select name="als_id" onFocus="setRatioAlias();">
																			<!-- BDP: als_list -->
																			<option value="{ALS_ID}" {ALS_SELECTED}>.{ALS_NAME}</option>
																			<!-- EDP: als_list -->
																		</select>
																		<!-- EDP: to_alias_domain -->
																	</td>
																</tr>
																<tr>
																	<td width="250" class="content2">
																		<label for="subdomain_mnt_pt">
																			{TR_DIR_TREE_SUBDOMAIN_MOUNT_POINT}
																		</label>
																	</td>
																	<td class="content">
																		<input type="text" name="subdomain_mnt_pt" id="subdomain_mnt_pt" value="{SUBDOMAIN_MOUNT_POINT}" style="width:170px" class="textinput" />
																	</td>
																</tr>
																<tr>
																	<td width="250" class="content2">
																		{TR_ENABLE_FWD}
																	</td>
																	<td class="content">
																		<input type="radio" name="status" {CHECK_EN} value="1" onChange='setForwardReadonly(this);' />
																		&nbsp;{TR_ENABLE}<br />
																		<input type="radio" name="status" {CHECK_DIS} value="0" onChange='setForwardReadonly(this);' />
																		&nbsp;{TR_DISABLE}
																	</td>
																</tr>
																<tr>
																	<td width="250" class="content2">
																		<label for="forward">
																			{TR_FORWARD}
																		</label>
																	</td>
																	<td class="content">
																		<select name="forward_prefix" style="vertical-align:middle"{DISABLE_FORWARD}>
																			<option value="{TR_PREFIX_HTTP}"{HTTP_YES}>
																				{TR_PREFIX_HTTP}
																			</option>
																			<option value="{TR_PREFIX_HTTPS}"{HTTPS_YES}>
																				{TR_PREFIX_HTTPS}
																			</option>
																			<option value="{TR_PREFIX_FTP}"{FTP_YES}>
																				{TR_PREFIX_FTP}
																			</option>
																		</select>
																		<input name="forward" type="text" class="textinput" id="forward" style="width:170px" value="{FORWARD}"{READONLY_FORWARD} />
																	</td>
																</tr>
																<tr>
																	<td colspan="2">
																		&nbsp;
																	</td>
																</tr>
																<tr>
																	<td colspan="2">
																		<input name="Submit" type="submit" class="button" value="{TR_ADD}" />
																		<input type="hidden" name="uaction" value="add_subd" />
																	</td>
																</tr>
															</table>
														</form>
													</td>
												</tr>
											</table>
										</td>
										<td>
											&nbsp;
										</td>
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
