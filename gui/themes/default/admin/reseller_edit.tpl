<!-- INCLUDE "../shared/layout/header.tpl" -->
	<body>
		<script language="JavaScript" type="text/JavaScript">
			/*<![CDATA[*/
			$(document).ready(function() {
				$('.radio').buttonset();

				gpwd = false;

				$(':password').each(function(i) {
					$(this).val('').mouseover(function() {
						if(gpwd){
							$(this).hide();
							$('<input />').attr({type:'text',name:'ipwd'+i}).css({float:'left',width:'200px'}).addClass('textinput').
								val($(this).val()).insertAfter('input[name=pass'+i+']').select().iMSCPtooltips({msg:'{TR_CTRL+C}'}).
								mouseout(function(){$(this).remove();$(':password').show();});
						}
					});
				});

				$('<img>').attr({src:'{THEME_COLOR_PATH}/images/ajax/small-spinner.gif'}).addClass('small-spinner').insertAfter($(':password'));

				$.ajaxSetup({
					url: $(location).attr('pathname'),
					type:'POST',
					data:'edit_id={EDIT_ID}&uaction=genpass',
					datatype:'text',
					beforeSend:function(xhr){xhr.setRequestHeader('Accept','text/plain');},
					success:function(r){$(':password').val(r).attr('readonly',true);gpwd=true;},
					error:iMSCPajxError
				});

				$(':password ~ img').ajaxStart(function(){
					$(this).show()
				});

				$(':password ~ img').ajaxStop(function(){
					$(this).hide()
				});

				$('input[name=genpass]').click(function(){
					$.ajax();
				}).attr('disabled', false);

				$('input[name=pwdreset]').click(function(){
					gpwd=false;
					$(':password').val('').attr('readonly', false);
				});

				$(':input').live('keypress', function(e){
					if(e.keyCode==13){
						e.preventDefault();
						alert('{TR_EVENT_NOTICE}');
					}
				});

				$(':submit,:button').hover(
					function(){
						$(this).addClass('buttonHover');
					},
					function(){
						$(this).removeClass('buttonHover');
					}
				);

				$('.permission_help').iMSCPtooltips({msg:'{TR_PHPINI_PERMISSION_HELP}'});

				if($('#phpini_system_no').is(':checked')) {
					$("#phpinidetail").hide();
				}

				$('#phpini_system_yes').click( function() {
					$("#phpinidetail").show();
				});

				$('#phpini_system_no').click( function() {
					$("#phpinidetail").hide();
				});
			});
			/*]]>*/
		</script>
		<div class="header">
			{MAIN_MENU}

			<div class="logo">
				<img src="{ISP_LOGO}" alt="i-MSCP logo" />
			</div>
		</div>

		<div class="location">
			<div class="location-area">
				<h1 class="manage_users">{TR_MENU_MANAGE_USERS}</h1>
			</div>
			<ul class="location-menu">
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a>
				</li>
			</ul>
			<ul class="path">
				<li><a href="manage_users.php">{TR_MENU_MANAGE_USERS}</a></li>
				<li><a href="#" onclick="return false">{TR_EDIT_RESELLER}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<h2 class="user_green"><span>{TR_EDIT_RESELLER}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<form name="admin_edit_reseller" method="post" action="reseller_edit.php">
				<fieldset>
					<legend>{TR_CORE_DATA}</legend>
					<table>
						<tr>
							<td style="width:315px;">{TR_USERNAME}</td>
							<td>{USERNAME}</td>
						</tr>
						<tr>
							<td><label for="password">{TR_PASSWORD}</label></td>
							<td>
								<input type="password" name="pass0" id="password" value="{VAL_PASSWORD}" style="float:left;{PWD_ERR}" />
								<div class="buttons" style="float:right">
									<input name="genpass" type="button" id="genpass" value="{TR_PASSWORD_GENERATE}" style="margin-right:5px;" />
									<input name="pwdreset" type="button" id="pwdreset" value="{TR_RESET}" />
								</div>
							</td>
						</tr>
						<tr>
							<td><label for="pass_rep">{TR_PASSWORD_REPEAT}</label>
							</td>
							<td>
								<input type="password" name="pass1" id="pass_rep" value="{VAL_PASSWORD}" style="float:left;{PWDR_ERR}" />
							</td>
						</tr>

						<tr>
							<td><label for="email">{TR_EMAIL}</label></td>
							<td>
								<input type="text" name="email" id="email" value="{EMAIL}" />
							</td>
						</tr>
						<tr>
							<td>
								<label for="nreseller_max_domain_cnt">{TR_MAX_DOMAIN_COUNT}</label>
							</td>
							<td>
								<input type="text" name="nreseller_max_domain_cnt" id="nreseller_max_domain_cnt" value="{MAX_DOMAIN_COUNT}" />
							</td>
						</tr>
						<tr>
							<td>
								<label for="nreseller_max_subdomain_cnt">{TR_MAX_SUBDOMAIN_COUNT}</label>
							</td>
							<td>
								<input type="text" name="nreseller_max_subdomain_cnt" id="nreseller_max_subdomain_cnt" value="{MAX_SUBDOMAIN_COUNT}" />
							</td>
						</tr>
						<tr>
							<td>
								<label for="nreseller_max_alias_cnt">{TR_MAX_ALIASES_COUNT}</label>
							</td>
							<td>
								<input type="text" name="nreseller_max_alias_cnt" id="nreseller_max_alias_cnt" value="{MAX_ALIASES_COUNT}" />
							</td>
						</tr>
						<tr>
							<td>
								<label for="nreseller_max_mail_cnt">{TR_MAX_MAIL_USERS_COUNT}</label>
							</td>
							<td>
								<input type="text" name="nreseller_max_mail_cnt" id="nreseller_max_mail_cnt" value="{MAX_MAIL_USERS_COUNT}" />
							</td>
						</tr>
						<tr>
							<td>
								<label for="nreseller_max_ftp_cnt">{TR_MAX_FTP_USERS_COUNT}</label>
							</td>
							<td>
								<input type="text" name="nreseller_max_ftp_cnt" id="nreseller_max_ftp_cnt" value="{MAX_FTP_USERS_COUNT}" />
							</td>
						</tr>
						<tr>
							<td>
								<label for="nreseller_max_sql_db_cnt">{TR_MAX_SQLDB_COUNT}</label>
							</td>
							<td>
								<input type="text" name="nreseller_max_sql_db_cnt" id="nreseller_max_sql_db_cnt" value="{MAX_SQLDB_COUNT}" />
							</td>
						</tr>
						<tr>
							<td>
								<label for="nreseller_max_sql_user_cnt">{TR_MAX_SQL_USERS_COUNT}</label>
							</td>
							<td>
								<input type="text" name="nreseller_max_sql_user_cnt" id="nreseller_max_sql_user_cnt" value="{MAX_SQL_USERS_COUNT}" />
							</td>
						</tr>
						<tr>
							<td>
								<label for="nreseller_max_traffic">{TR_MAX_TRAFFIC_AMOUNT}</label>
							</td>
							<td>
								<input type="text" name="nreseller_max_traffic" id="nreseller_max_traffic" value="{MAX_TRAFFIC_AMOUNT}" />
							</td>
						</tr>
						<tr>
							<td>
								<label for="nreseller_max_disk">{TR_MAX_DISK_AMOUNT}</label>
							</td>
							<td>
								<input type="text" name="nreseller_max_disk" id="nreseller_max_disk" value="{MAX_DISK_AMOUNT}" />
							</td>
						</tr>
						<tr>
							<td>{TR_SOFTWARE_SUPP}</td>
							<td>
								<div class="radio">
									<input type="radio" name="domain_software_allowed" id="software_allowed_yes" value="yes" {SOFTWARE_YES} />
									<label for="software_allowed_yes">{TR_YES}</label>
									<input type="radio" name="domain_software_allowed" id="software_allowed_no" value="no" {SOFTWARE_NO} />
									<label for="software_allowed_no">{TR_NO}</label>
								</div>
							</td>
						</tr>
						<tr>
							<td>{TR_SOFTWAREDEPOT_SUPP}</td>
							<td>
								<div class="radio">
									<input type="radio" name="domain_softwaredepot_allowed" id="softwaredepot_allowed_yes" value="yes" {SOFTWAREDEPOT_YES} />
									<label for="softwaredepot_allowed_yes">{TR_YES}</label>
									<input type="radio" name="domain_softwaredepot_allowed" id="softwaredepot_allowed_no" value="no" {SOFTWAREDEPOT_NO} />
									<label for="softwaredepot_allowed_no">{TR_NO}</label>
								</div>
							</td>
						</tr>
						<tr>
							<td>{TR_WEBSOFTWAREDEPOT_SUPP}</td>
							<td>
								<div class="radio">
									<input type="radio" name="domain_websoftwaredepot_allowed" id="websoftwaredepot_allowed_yes" value="yes" {WEBSOFTWAREDEPOT_YES} />
									<label for="websoftwaredepot_allowed_yes">{TR_YES}</label>
									<input type="radio" name="domain_websoftwaredepot_allowed" id="websoftwaredepot_allowed_no" value="no" {WEBSOFTWAREDEPOT_NO} />
									<label for="websoftwaredepot_allowed_no">{TR_NO}</label>
								</div>
							</td>
						</tr>
						<tr>
							<td>{TR_SUPPORT_SYSTEM}</td>
							<td>
								<div class="radio">
									<input type="radio" name="support_system" id="support_system_yes" value="yes" {SUPPORT_YES} />
									<label for="support_system_yes">{TR_YES}</label>
									<input type="radio" name="support_system" id="support_system_no" value="no" {SUPPORT_NO}/>
									<label for="support_system_no">{TR_NO}</label>
								</div>
							</td>
						</tr>
						<tr>
							<td>{TR_PHPINI_SYSTEM}</td>
							<td>
								<div class="radio">
									<input type="radio" name="phpini_system" id="phpini_system_yes" value="yes" {PHPINI_SYSTEM_YES} />
									<label for="phpini_system_yes">{TR_YES}</label>
									<input type="radio" name="phpini_system" id="phpini_system_no" value="no" {PHPINI_SYSTEM_NO} />
									<label for="phpini_system_no">{TR_NO}</label>
								</div>
							</td>
						</tr>
						<tbody id='phpinidetail'>
							<tr id='php_ini_block_register_globals'>
								<td>{TR_PHPINI_AL_REGISTER_GLOBALS}<span class="permission_help icon i_help">{TR_HELP}</span></td>
								<td>
									<div class="radio">
										<input type="radio" name="phpini_al_register_globals" id="phpini_al_register_globals_yes" value="yes" {PHPINI_AL_REGISTER_GLOBALS_YES} />
										<label for="phpini_al_register_globals_yes">{TR_YES}</label>
										<input type="radio" name="phpini_al_register_globals" id="phpini_al_register_globals_no" value="no" {PHPINI_AL_REGISTER_GLOBALS_NO} />
										<label for="phpini_al_register_globals_no">{TR_NO}</label>
									</div>
								</td>
							</tr>
							<tr id='php_ini_block_allow_url_fopen'>
								<td>{TR_PHPINI_AL_ALLOW_URL_FOPEN}<span class="permission_help icon i_help">{TR_HELP}</span></td>
								<td>
									<div class="radio">
										<input type="radio" name="phpini_al_allow_url_fopen" id="phpini_al_allow_url_fopen_yes" value="yes" {PHPINI_AL_ALLOW_URL_FOPEN_YES} />
										<label for="phpini_al_allow_url_fopen_yes">{TR_YES}</label>
										<input type="radio" name="phpini_al_allow_url_fopen" id="phpini_al_allow_url_fopen_no" value="no" {PHPINI_AL_ALLOW_URL_FOPEN_NO} />
										<label for="phpini_al_allow_url_fopen_no">{TR_NO}</label>
									</div>
								</td>
							</tr>
							<tr id='php_ini_block_display_errors'>
								<td>{TR_PHPINI_AL_DISPLAY_ERRORS}<span class="permission_help icon i_help">{TR_HELP}</span></td>
								<td>
									<div class="radio">
										<input type="radio" name="phpini_al_display_errors" id="phpini_al_display_errors_yes" value="yes" {PHPINI_AL_DISPLAY_ERRORS_YES} />
										<label for="phpini_al_display_errors_yes">{TR_YES}</label>
										<input type="radio" name="phpini_al_display_errors" id="phpini_al_display_errors_no" value="no" {PHPINI_AL_DISPLAY_ERRORS_NO} />
										<label for="phpini_al_display_errors_no">{TR_NO}</label>
									</div>
								</td>
							</tr>
							<tr id='php_ini_block_disable_functions'>
								<td>{TR_PHPINI_AL_DISABLE_FUNCTIONS}<span class="permission_help icon i_help">{TR_HELP}</span></td>
								<td>
									<div class="radio">
										<input type="radio" name="phpini_al_disable_functions" id="phpini_al_disable_functions_yes" value="yes" {PHPINI_AL_DISABLE_FUNCTIONS_YES} />
										<label for="phpini_al_disable_functions_yes">{TR_YES}</label>
										<input type="radio" name="phpini_al_disable_functions" id="disable_functions_no" value="no" {PHPINI_AL_DISABLE_FUNCTIONS_NO} />
										<label for="disable_functions_no">{TR_NO}</label>
									</div>
								</td>
							</tr>
							<tr id='php_ini_block_memory_limit'>
								<td>
									<label for="phpini_max_memory_limit">{TR_PHPINI_MAX_MEMORY_LIMIT}</label>
								</td>
								<td>
									<input type="text" name="phpini_max_memory_limit" id="phpini_max_memory_limit" value="{PHPINI_MAX_MEMORY_LIMIT_VAL}" />
								</td>
							</tr>
							<tr id='php_ini_block_upload_max_filesize'>
								<td>
									<label for="phpini_max_upload_max_filesize">{TR_PHPINI_MAX_UPLOAD_MAX_FILESIZE}</label>
								</td>
								<td>
									<input type="text" name="phpini_max_upload_max_filesize" id="phpini_max_upload_max_filesize" value="{PHPINI_MAX_UPLOAD_MAX_FILESIZE_VAL}" />
								</td>
							</tr>
							<tr id='php_ini_block_post_max_size'>
								<td>
									<label for="phpini_max_post_max_size">{TR_PHPINI_MAX_POST_MAX_SIZE}</label>
								</td>
								<td>
									<input type="text" name="phpini_max_post_max_size" id="phpini_max_post_max_size" value="{PHPINI_MAX_POST_MAX_SIZE_VAL}" />
								</td>
							</tr>
							<tr id='php_ini_block_max_execution_time'>
								<td>
									<label for="phpini_max_max_execution_time">{TR_PHPINI_MAX_MAX_EXECUTION_TIME}</label>
								</td>
								<td>
									<input type="text" name="phpini_max_max_execution_time" id="phpini_max_max_execution_time" value="{PHPINI_MAX_MAX_EXECUTION_TIME_VAL}" />
								</td>
							</tr>
							<tr id='php_ini_block_max_input_time'>
								<td>
									<label for="phpini_max_max_input_time">{TR_PHPINI_MAX_MAX_INPUT_TIME}</label>
								</td>
								<td>
									<input type="text" name="phpini_max_max_input_time" id="phpini_max_max_input_time" value="{PHPINI_MAX_MAX_INPUT_TIME_VAL}" />
								</td>
							</tr>
						</tbody>


					</table>
				</fieldset>

				<fieldset>
					<legend>{TR_RESELLER_IPS}</legend>

					<!-- BDP: rsl_ip_message -->
					<div class="warning">{MESSAGE}</div>
					<!-- EDP: rsl_ip_message -->

					<!-- BDP: rsl_ip_list -->
					<table>
						<tr>
							<th>{TR_RSL_IP_NUMBER}</th>
							<th>{TR_RSL_IP_ASSIGN}</th>
							<th>{TR_RSL_IP_LABEL}</th>
							<th>{TR_RSL_IP_IP}</th>
						</tr>
						<!-- BDP: rsl_ip_item -->
						<tr>
							<td style="width:300px;">{RSL_IP_NUMBER}</td>
							<td>
								<input type="checkbox" id="{RSL_IP_CKB_NAME}" name="{RSL_IP_CKB_NAME}" value="{RSL_IP_CKB_VALUE}" {RSL_IP_ITEM_ASSIGNED} />
							</td>
							<td><label for="{RSL_IP_CKB_NAME}">{RSL_IP_LABEL}</label>
							</td>
							<td>{RSL_IP_IP}</td>
						</tr>
						<!-- EDP: rsl_ip_item -->
					</table>
					<!-- EDP: rsl_ip_list -->
				</fieldset>

				<fieldset>
					<legend>{TR_ADDITIONAL_DATA}</legend>
					<table>
						<tr>
							<td style="width:300px;">
								<label for="customer_id">{TR_CUSTOMER_ID}</label>
							</td>
							<td>
								<input type="text" name="customer_id" id="customer_id" value="{CUSTOMER_ID}" />
							</td>
						</tr>
						<tr>
							<td><label for="first_name">{TR_FIRST_NAME}</label></td>
							<td>
								<input type="text" name="fname" id="first_name" value="{FIRST_NAME}" />
							</td>
						</tr>
						<tr>
							<td><label for="last_name">{TR_LAST_NAME}</label></td>
							<td>
								<input type="text" name="lname" id="last_name" value="{LAST_NAME}" />
							</td>
						</tr>
						<tr>
							<td><label for="gender">{TR_GENDER}</label></td>
							<td><select id="gender" name="gender">
								<option value="M" {VL_MALE}>{TR_MALE}</option>
								<option value="F" {VL_FEMALE}>{TR_FEMALE}</option>
								<option value="U" {VL_UNKNOWN}>{TR_UNKNOWN}</option>
							</select>
							</td>
						<tr>
							<td><label for="firm">{TR_COMPANY}</label></td>
							<td>
								<input type="text" name="firm" id="firm" value="{FIRM}" />
							</td>
						</tr>
						<tr>
							<td><label for="street1">{TR_STREET_1}</label></td>
							<td>
								<input type="text" name="street1" id="street1" value="{STREET_1}" />
							</td>
						</tr>
						<tr>
							<td><label for="street2">{TR_STREET_2}</label></td>
							<td>
								<input type="text" name="street2" id="street2" value="{STREET_2}" />
							</td>
						</tr>
						<tr>
							<td>
								<label for="zip_postal_code">{TR_ZIP_POSTAL_CODE}</label>
							</td>
							<td>
								<input type="text" name="zip" id="zip_postal_code" value="{ZIP}" />
							</td>
						</tr>
						<tr>
							<td><label for="city">{TR_CITY}</label></td>
							<td>
								<input type="text" name="city" id="city" value="{CITY}" />
							</td>
						</tr>
						<tr>
							<td><label for="state">{TR_STATE}</label></td>
							<td>
								<input type="text" name="state" id="state" value="{STATE}" />
							</td>
						</tr>
						<tr>
							<td><label for="country">{TR_COUNTRY}</label></td>
							<td>
								<input type="text" name="country" id="country" value="{COUNTRY}" />
							</td>
						</tr>
						<tr>
							<td><label for="phone">{TR_PHONE}</label></td>
							<td>
								<input type="text" name="phone" id="phone" value="{PHONE}" />
							</td>
						</tr>
						<tr>
							<td><label for="fax">{TR_FAX}</label></td>
							<td>
								<input type="text" name="fax" id="fax" value="{FAX}" />
							</td>
						</tr>
					</table>
				</fieldset>

				<div class="buttons">
					<input name="Submit" type="submit" class="button" value="{TR_UPDATE}" />
					<input id="send_data" type="checkbox" name="send_data" checked="checked" />
					<label for="send_data">{TR_SEND_DATA}</label>
					<input type="hidden" name="uaction" value="update_reseller" />
					<input type="hidden" name="edit_id" value="{EDIT_ID}" />
					<input type="hidden" name="edit_username" value="{USERNAME}" />
				</div>
			</form>
		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
