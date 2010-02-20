<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_ADMIN_EDIT_RESELLER_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/jquery.js"></script>
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/jquery.ispcpTooltips.js"></script>
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
<script type="text/javascript">
/*<![CDATA[*/

	/*
 	 * Global variable - TRUE if a password was generated, FALSE otherwise
 	 */
	gpwd=false;

	$(document).ready(function(){
		$(':password').each(
			function(i) {
				// We ensure's the passwords fields are empty after on load because several
				// browsers fill them with the cached password (eg. Gecko's based browsers)
				$(this).val('').
				// Create text input field to display generated password on mouseover
				mouseover(
					function(){
						if(gpwd){
							// First, hide the password field
							$(this).hide();
							// Now create the text input field
							$('<input />').attr({type:'text',name:'ipwd'+i}).
							css({float:'left',width:'210px'}).addClass('textinput').
							val($(this).val()).
							insertAfter('input[name=pass'+i+']').select().
							// Create tooltip linked to the create text input field
							ispCPtooltips({msg:'{TR_CTRL+C}'}).
							// Restore input password field on mouseout
							mouseout(function(){$(this).remove();$(':password').show();
								}
							);
						}
					}
				);
			}
		);

		// Create and adds the ajax spinner
		$('<img>').attr({src:'{THEME_COLOR_PATH}/images/ajax/small-spinner.gif'}).
			addClass('small-spinner').insertAfter($(':password'));

		// Configure the request for password generation
		$.ajaxSetup({
			url: $(location).attr('pathname'),
			type:'POST',
			data:'edit_id={EDIT_ID}&uaction=genpass',
			datatype:'text',
			beforeSend:function(xhr){xhr.setRequestHeader('Accept','text/plain');},
			success:function(r){$(':password').val(r).attr('readonly',true);gpwd=true;},
			error:ispCPajxError
		});

		// Adds event handlers for the ajax spinner
		$(':password ~ img').ajaxStart(function(){$(this).show()});
		$(':password ~ img').ajaxStop(function(){$(this).hide()});

		// Adds event handler for the password generation button
		$('input[name=genpass]').click(function(){$.ajax();}).attr('disabled',false);

		// Adds event handler for the reset button
		$('input[name=pwdreset]').click(
			function(){gpwd=false;$(':password').val('').attr('readonly',false);}
		);

		// Disable the 'Enter' key to prevent multiples validation/updates process
		$(':input').live('keypress',function(e){
			if(e.keyCode==13){e.preventDefault();alert('{TR_EVENT_NOTICE}');}
		});

		// Hover effect on several input elements (type submit and button)
		$(':submit,:button').hover(
			function(){$(this).addClass('buttonHover');},
			function(){$(this).removeClass('buttonHover');}
		);
	});
/*]]>*/
</script>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">

<table width="100%" border="0" cellspacing="0" cellpadding="0" style="height:100%;padding:0;margin:0 auto;">
	<tr>
		<td align="left" valign="top" style="vertical-align: top; width: 195px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_left.jpg" width="195" height="56" border="0" alt="ispCP Logogram" /></td>
		<td style="height: 56px; width:100%; background-color: #0f0f0f"><img src="{THEME_COLOR_PATH}/images/top/top_left_bg.jpg" width="582" height="56" border="0" alt="" /></td>
		<td style="width: 73px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_right.jpg" width="73" height="56" border="0" alt="" /></td>
	</tr>
	<tr>
		<td style="width: 195px; vertical-align: top;">{MENU}</td>
		<td colspan="2" style="vertical-align: top;">
			<table style="width: 100%; padding:0;margin:0;" cellspacing="0">
				<tr style="height: 95px">
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
											<td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_doc.png" width="25" height="25" alt="" /></td>
											<td colspan="2" class="title">{TR_EDIT_RESELLER}</td>
										</tr>
									</table>
								</td>
								<td width="27" align="right">&nbsp;</td>
							</tr>
							<tr>
								<td valign="top">
									<form name="admin_edit_reseller" method="post" style="margin:0;padding:0;" action="reseller_edit.php">
										<table width="100%" cellpadding="5" cellspacing="5" style="padding:0;margin:0">
											<tr>
												<td width="25">&nbsp;</td>
												<td colspan="2" class="content3"><b>{TR_CORE_DATA}</b></td>
											</tr>
											<!-- BDP: page_message -->
											<tr>
												<td width="25">&nbsp;</td>
												<td colspan="2" class="title"><span class="message">{MESSAGE}</span></td>
											</tr>
											<!-- EDP: page_message -->
											<tr>
												<td width="25">&nbsp;</td>
												<td width="200" class="content2">{TR_USERNAME}</td>
												<td class="content">{USERNAME}</td>
											</tr>
											<tr>
												<td width="25">&nbsp;</td>
												<td width="200" class="content2">{TR_PASSWORD}</td>
												<td class="content">
 													<input type="password" name="pass0" value="{VAL_PASSWORD}" class="textinput" style="width:210px;float:left;{PWD_ERR}" />
 													<input name="pwdreset" type="button" value="{TR_RESET}" class="button" style="float:right;margin-right:10px;" />
 													<input name="genpass" type="button" value="{TR_PASSWORD_GENERATE}" class="button" style="float:right;" />
												</td>
											</tr>
											<tr>
												<td width="25">&nbsp;</td>
												<td width="200" class="content2">{TR_PASSWORD_REPEAT}</td>
												<td class="content">
													<input type="password" name="pass1" value="{VAL_PASSWORD}" style="width:210px;float:left;{PWDR_ERR}" class="textinput" />
												</td>
											</tr>
											<tr>
												<td width="25">&nbsp;</td>
												<td width="200" class="content2">{TR_EMAIL}</td>
												<td class="content">
													<input type="text" name="email" value="{EMAIL}" style="width:210px;{EMAIL_ERR}" class="textinput" />
												</td>
											</tr>
											<tr>
												<td width="25">&nbsp;</td>
												<td class="content2" width="200">{TR_MAX_DOMAIN_COUNT}</td>
												<td class="content">
													<input type="text" name="nreseller_max_domain_cnt" value="{MAX_DOMAIN_COUNT}" style="width:140px;{DMN_ERR}" class="textinput" />
												</td>
											</tr>
											<tr>
												<td width="25">&nbsp;</td>
												<td class="content2" width="200">{TR_MAX_SUBDOMAIN_COUNT}</td>
												<td class="content">
													<input type="text" name="nreseller_max_subdomain_cnt" value="{MAX_SUBDOMAIN_COUNT}" style="width:140px;{SUB_ERR}" class="textinput" />
												</td>
											</tr>
											<tr>
												<td width="25">&nbsp;</td>
												<td class="content2" width="200">{TR_MAX_ALIASES_COUNT}</td>
												<td class="content">
													<input type="text" name="nreseller_max_alias_cnt" value="{MAX_ALIASES_COUNT}" style="width:140px;{ALS_ERR}" class="textinput" />
												</td>
											</tr>
											<tr>
												<td width="25">&nbsp;</td>
												<td class="content2" width="200">{TR_MAX_MAIL_USERS_COUNT}</td>
												<td class="content">
													<input type="text" name="nreseller_max_mail_cnt" value="{MAX_MAIL_USERS_COUNT}" style="width:140px;{MAIL_ERR}" class="textinput" />
												</td>
											</tr>
											<tr>
												<td width="25">&nbsp;</td>
												<td class="content2" width="200">{TR_MAX_FTP_USERS_COUNT}</td>
												<td class="content">
													<input type="text" name="nreseller_max_ftp_cnt" value="{MAX_FTP_USERS_COUNT}" style="width:140px;{FTP_ERR}" class="textinput" />
												</td>
											</tr>
											<tr>
												<td width="25">&nbsp;</td>
												<td class="content2" width="200">{TR_MAX_SQLDB_COUNT}</td>
												<td class="content">
													<input type="text" name="nreseller_max_sql_db_cnt" value="{MAX_SQLDB_COUNT}" style="width:140px;{SQLD_ERR}" class="textinput" />
												</td>
											</tr>
											<tr>
												<td width="25">&nbsp;</td>
												<td class="content2" width="200">{TR_MAX_SQL_USERS_COUNT}</td>
												<td class="content">
													<input type="text" name="nreseller_max_sql_user_cnt" value="{MAX_SQL_USERS_COUNT}" style="width:140px;{SQLU_ERR}" class="textinput" />
												</td>
											</tr>
											<tr>
												<td width="25">&nbsp;</td>
												<td class="content2" width="200">{TR_MAX_TRAFFIC_AMOUNT}</td>
												<td class="content">
													<input type="text" name="nreseller_max_traffic" value="{MAX_TRAFFIC_AMOUNT}" style="width:140px;{TRF_ERR}" class="textinput" />
												</td>
											</tr>
											<tr>
												<td width="25">&nbsp;</td>
												<td class="content2" width="200">{TR_MAX_DISK_AMOUNT}</td>
												<td class="content">
													<input type="text" name="nreseller_max_disk" value="{MAX_DISK_AMOUNT}" style="width:140px;{DISK_ERR}" class="textinput" />
												</td>
											</tr>
											<tr>
												<td width="25">&nbsp;</td>
												<td class="content2" width="200">{TR_SUPPORT_SYSTEM}</td>
												<td class="content">
													<input name="support_system" value="yes" {SUPPORT_YES} type="radio" />{TR_YES}
													<input name="support_system" value="no" {SUPPORT_NO} type="radio" />{TR_NO}
												</td>
											</tr>
											<tr>
												<td width="25">&nbsp;</td>
												<td colspan="2">
													<table cellpadding="3" cellspacing="1" border="0" width="100%">
														<tr>
															<td class="content3" colspan="4">{TR_RESELLER_IPS}</td>
														</tr>
														<!-- BDP: rsl_ip_message -->
														<tr>
															<td colspan="4" class="title" nowrap="nowrap"><b>{MESSAGES_LABEL}</b></td>
														</tr>
														<!-- EDP: rsl_ip_message -->
														<!-- BDP: rsl_ip_list -->
														<tr>
															<td width="10%" align="center" class="content3">{TR_RSL_IP_NUMBER}</td>
															<td width="20%" align="center" class="content3">{TR_RSL_IP_ASSIGN}</td>
															<td width="35%" class="content3">{TR_RSL_IP_LABEL}</td>
															<td width="35%" class="content3">{TR_RSL_IP_IP}</td>
														</tr>
														<!-- BDP: rsl_ip_item -->
														<tr>
															<td width="10%" align="center" class="{RSL_IP_CLASS}">{RSL_IP_NUMBER}</td>
															<td width="20%" align="center" class="{RSL_IP_CLASS}"><input type="checkbox" name="{RSL_IP_CKB_NAME}" value="{RSL_IP_CKB_VALUE}" {RSL_IP_ITEM_ASSIGNED} /></td>
															<td width="35%" class="{RSL_IP_CLASS}">{RSL_IP_LABEL}</td>
															<td width="35%" class="{RSL_IP_CLASS}">{RSL_IP_IP}</td>
														</tr>
														<!-- EDP: rsl_ip_item -->
														<!-- EDP: rsl_ip_list -->
													</table>
												</td>
											</tr>
											<tr>
												<td width="25">&nbsp;</td>
												<td colspan="2" class="content3"><b>{TR_ADDITIONAL_DATA}</b></td>
											</tr>
											<tr>
												<td width="25">&nbsp;</td>
												<td width="200" class="content2">{TR_CUSTOMER_ID}</td>
												<td class="content"><input type="text" name="customer_id" value="{CUSTOMER_ID}" style="width:210px" class="textinput" /></td>
											</tr>
											<tr>
												<td width="25">&nbsp;</td>
												<td width="200" class="content2">{TR_FIRST_NAME}</td>
												<td class="content"><input type="text" name="fname" value="{FIRST_NAME}" style="width:210px" class="textinput" /></td>
											</tr>
											<tr>
												<td width="25">&nbsp;</td>
												<td width="200" class="content2">{TR_LAST_NAME}</td>
												<td class="content"><input type="text" name="lname" value="{LAST_NAME}" style="width:210px" class="textinput" /></td>
											</tr>
											<tr>
												<td width="25">&nbsp;</td>
												<td width="200" class="content2">{TR_GENDER}</td>
												<td class="content">
													<select name="gender" size="1">
														<option value="M" {VL_MALE}>{TR_MALE}</option>
														<option value="F" {VL_FEMALE}>{TR_FEMALE}</option>
														<option value="U" {VL_UNKNOWN}>{TR_UNKNOWN}</option>
													</select>
												</td>
											</tr>
											<tr>
												<td width="25">&nbsp;</td>
												<td width="200" class="content2">{TR_COMPANY}</td>
												<td class="content"><input type="text" name="firm" value="{FIRM}" style="width:210px" class="textinput" /></td>
											</tr>
											<tr>
												<td width="25">&nbsp;</td>
												<td width="200" class="content2">{TR_ZIP_POSTAL_CODE}</td>
												<td class="content"><input type="text" name="zip" value="{ZIP}" style="width:80px" class="textinput" /></td>
											</tr>
											<tr>
												<td width="25">&nbsp;</td>
												<td width="200" class="content2">{TR_CITY}</td>
												<td class="content"><input type="text" name="city" value="{CITY}" style="width:210px" class="textinput" /></td>
											</tr>
											<tr>
												<td width="25">&nbsp;</td>
												<td width="200" class="content2">{TR_STATE}</td>
												<td class="content"><input type="text" name="state" value="{STATE}" style="width:210px" class="textinput" /></td>
											</tr>
											<tr>
												<td width="25">&nbsp;</td>
												<td width="200" class="content2">{TR_COUNTRY}</td>
												<td class="content"><input type="text" name="country" value="{COUNTRY}" style="width:210px" class="textinput" /></td>
											</tr>
											<tr>
												<td width="25">&nbsp;</td>
												<td width="200" class="content2">{TR_STREET_1}</td>
												<td class="content"><input type="text" name="street1" value="{STREET_1}" style="width:210px" class="textinput" /></td>
											</tr>
											<tr>
												<td width="25">&nbsp;</td>
												<td width="200" class="content2">{TR_STREET_2}</td>
												<td class="content"><input type="text" name="street2" value="{STREET_2}" style="width:210px" class="textinput" /></td>
											</tr>
											<tr>
												<td width="25">&nbsp;</td>
												<td width="200" class="content2">{TR_PHONE}</td>
												<td class="content"><input type="text" name="phone" value="{PHONE}" style="width:210px" class="textinput" /></td>
											</tr>
											<tr>
												<td width="25">&nbsp;</td>
												<td width="200" class="content2">{TR_FAX}</td>
												<td class="content"><input type="text" name="fax" value="{FAX}" style="width:210px" class="textinput" /></td>
											</tr>
											<tr>
												<td>&nbsp;</td>
												<td colspan="2">
													<input name="Submit" type="submit" class="button" value="{TR_UPDATE}" />
													<input name="Cancel" type="submit" class="button" value="{TR_CANCEL}" />
													<input style="vertical-align:middle" type="checkbox" name="send_data" checked="checked" />{TR_SEND_DATA}
												</td>
											</tr>
										</table>
										<input type="hidden" name="uaction" value="update_reseller" />
										<input type="hidden" name="edit_id" value="{EDIT_ID}" />
										<input type="hidden" name="edit_username" value="{USERNAME}" />
									</form>
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
