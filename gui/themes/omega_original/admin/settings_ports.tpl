<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_ADMIN_SETTINGS_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/jquery.js"></script>
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
<!--[if lt IE 7.]>
<script defer type="text/javascript" src="pngfix.js"></script>
<![endif]-->

<script type="text/javascript">
/*<![CDATA[*/
function action_delete(url, service) {
	if (!confirm(sprintf("{TR_MESSAGE_DELETE}", service)))
		return false;
	location = url;
}

function enable_for_post() {
	for (var i = 0; i < document.frm_to_updt.length; i++) {
		for (var j = 0; j < document.frm_to_updt.elements[i].length; j++) {
			if (document.frm_to_updt.elements[i].name == 'port_type[]') {
				document.frm_to_updt.elements[i].disabled = false;
			}
		}
	}
	return true;
}

var error_fields_ids = {ERROR_FIELDS_IDS};

$(document).ready(function(){

 $.each(error_fields_ids, function(){
  $('#'+this).css({'border' : '1px solid red', 'font-weight' : 'bolder'});
 });

 $('input[name=submitForReset]').click(
  function(){$('input[name=uaction]').val('reset');}
 );

});
/*]]>*/
</script>

</head>

<body onLoad="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.png','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.png','{THEME_COLOR_PATH}/images/icons/domains_a.png','{THEME_COLOR_PATH}/images/icons/general_a.png' ,'{THEME_COLOR_PATH}/images/icons/manage_users_a.png','{THEME_COLOR_PATH}/images/icons/webtools_a.png','{THEME_COLOR_PATH}/images/icons/statistics_a.png','{THEME_COLOR_PATH}/images/icons/support_a.png')">
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
											<td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_serverstatus.png" width="25" height="25" alt="" /></td>
											<td colspan="2" class="title">{TR_SERVERPORTS}</td>
										</tr>
									</table>
								</td>
								<td width="27" align="right">&nbsp;</td>
							</tr>
							<tr>
								<td valign="top">
	<form name="frm_to_updt" method="post" action="settings_ports.php" onSubmit="return enable_for_post();">
										<table width="100%" cellpadding="5" cellspacing="5">
											<!-- BDP: page_message -->
											<tr>
												<td width="25">&nbsp;</td>
												<td colspan="2" class="title"><span class="message">{MESSAGE}</span></td>
											</tr>
											<!-- EDP: page_message -->
											<tr>
												<td width="25">&nbsp;</td>
												<td colspan="6" class="content3"><strong>{TR_SHOW_UPDATE_SERVICE_PORT}</strong></td>
											</tr>
											<tr>
												<td width="25">&nbsp;</td>
												<td width="230" class="content3"><strong>{TR_SERVICE}</strong></td>
												<td class="content3"><strong>{TR_IP}</strong></td>
												<td class="content3"><strong>{TR_PORT}</strong></td>
												<td class="content3"><strong>{TR_PROTOCOL}</strong></td>
												<td class="content3"><strong>{TR_SHOW}</strong></td>
												<td class="content3"><strong>{TR_ACTION}</strong></td>
											</tr>
											<!-- BDP: service_ports -->
											<tr class="hl">
												<td width="25">&nbsp;</td>
												<td class="{CLASS}">
													{SERVICE}
													<input name="var_name[]" type="hidden" id="var_name{NUM}" value="{VAR_NAME}" />
													<input name="custom[]" type="hidden" id="custom{NUM}" value="{CUSTOM}" />
												</td>
												<td class="{CLASS}">
													<input name="ip[]" type="text" class="textinput" id="ip{NUM}" value="{IP}" maxlength="15" {PORT_READONLY} />
												</td>
												<td class="{CLASS}">
													<input name="port[]" type="text" class="textinput" id="port{NUM}" style="width:50px" value="{PORT}" maxlength="5" {PORT_READONLY} />
												</td>
												<td class="{CLASS}">
													<select name="port_type[]" id="port_type{NUM}" {PROTOCOL_READONLY}>
														<option value="udp" {SELECTED_UDP}>{TR_UDP}</option>
														<option value="tcp" {SELECTED_TCP}>{TR_TCP}</option>
													</select>
												</td>
												<td class="{CLASS}">
													<select name="show_val[]" id="show_val{NUM}">
														<option value="1" {SELECTED_ON}>{TR_ENABLED}</option>
														<option value="0" {SELECTED_OFF}>{TR_DISABLED}</option>
													</select>
												</td>
												<td class="{CLASS}" width="100" nowrap="nowrap">
													<!-- BDP: port_delete_show -->
													{TR_DELETE}
													<!-- EDP: port_delete_show -->
													<!-- BDP: port_delete_link -->
													<a href="#" onClick="action_delete('{URL_DELETE}', '{NAME}')" class="link">
													<img src="{THEME_COLOR_PATH}/images/icons/delete.png" width="16" height="16" border="0" style="vertical-align:middle" alt="" />{TR_DELETE}</a>
													<!-- EDP: port_delete_link -->
												</td>
											</tr>
											<!-- EDP: service_ports -->
											<tr>
												<td>&nbsp;</td>
												<td colspan="6">
												<input type="hidden" name="uaction" value="update" />
												<input name="submitForUpdate" type="submit" class="button" value="{VAL_FOR_SUBMIT_ON_UPDATE}" />
												<input name="submitForReset" type="submit" class="button" value="{VAL_FOR_SUBMIT_ON_RESET}"/>
												</td>
											</tr>
										</table>
									</form>
								</td>
								<td>&nbsp;</td>
							</tr>
							<tr>
								<td valign="top">
									<form name="frm_to_add" method="post" action="settings_ports.php">
										<table width="100%" cellpadding="5" cellspacing="5">
											<tr>
												<td width="25">&nbsp;</td>
												<td colspan="6" class="content3"><strong>{TR_ADD_NEW_SERVICE_PORT}</strong></td>
											</tr>
											<tr>
												<td width="25">&nbsp;</td>
												<td width="230" class="content3"><strong>{TR_SERVICE}</strong></td>
												<td class="content3"><strong>{TR_IP}</strong></td>
												<td class="content3"><strong>{TR_PORT}</strong></td>
												<td class="content3"><strong>{TR_PROTOCOL}</strong></td>
												<td class="content3"><strong>{TR_SHOW}</strong></td>
											</tr>
											<tr>
												<td width="25">&nbsp;</td>
												<td class="{CLASS}"><input name="name_new" type="text" class="textinput" id="name" value="{VAL_FOR_NAME_NEW}" maxlength="25"/></td>
												<td class="{CLASS}"><input name="ip_new" type="text" class="textinput" id="ip" style="" value="{VAL_FOR_IP_NEW}" maxlength="15" /></td>
												<td class="{CLASS}"><input name="port_new" type="text" class="textinput" id="port" style="width:50px" value="{VAL_FOR_PORT_NEW}" maxlength="6" /></td>
												<td class="{CLASS}">
													<select name="port_type_new" id="port_type">
														<option value="udp">{TR_UDP}</option>
														<option value="tcp" selected="selected">{TR_TCP}</option>
													</select>
												</td>
												<td class="{CLASS}">
													<select name="show_val_new" id="show_val">
														<option value="1" selected="selected">{TR_ENABLED}</option>
														<option value="0">{TR_DISABLED}</option>
													</select>
												</td>
											</tr>
											<tr>
												<td>&nbsp;</td>
												<td colspan="6">
												<input type="hidden" name="uaction" value="add" />
												<input name="submitForAdd" type="submit" class="button" value="{VAL_FOR_SUBMIT_ON_ADD}" />
												</td>
											</tr>
										</table>
									</form>
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
