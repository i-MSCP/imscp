	<script type="text/javascript">
		/*<![CDATA[*/
		$(document).ready(function() {
			errFieldsStack = {ERR_FIELDS_STACK};
			$.each(errFieldsStack, function(){$('#' + this).css('border-color', 'rgb(201, 29, 17');});
			$('#domain_expires').datepicker();
			$('#domain_never_expires').change(function(){
				if($(this).is(':checked')) {
					$('#domain_expires').css('border-color', '#cccccc').attr('disabled', 'disabled')
				} else {
					$('#domain_expires').removeAttr('disabled');
				}
			});

			<!-- BDP: php_editor_js -->
			// PHP Editor settings dialog
			$('#php_editor_dialog').dialog(
			{
				bgiframe:true,
				hide:'blind', show:'slide', focus:false, autoOpen:false, width:'650', modal:true, dialogClass:'body',
				buttons:{'{TR_CLOSE}':function(){$(this).dialog('close');}},
				open: function(e){$('#php_editor_dialog :radio').blur();}
			});

			// Re-add the PHP Editor container to the form on submit
			$('form').submit(function(){$('#php_editor_dialog').parent().appendTo($('#dialogContainer'));});

			// PHP Editor settings button
			if($('#domain_php').val()=='no'){$('#php_editor_block').hide();}
			$('#domain_php').change(function(){$('#php_editor_block').fadeToggle();});

			$('#php_editor_dialog_open').button({icons:{primary:'ui-icon-gear'}}).click(function(e){
				$('#php_editor_dialog').dialog('open');
				return false;
			});

			// Do not show PHP Editor settings button if disabled
			if($('#phpiniSystem').val()=='no'){$('#php_editor_dialog_open').hide();}
			$('#phpiniSystem').change(function(){$('#php_editor_dialog_open').fadeToggle();});

			// PHP Editor reseller max values
			phpDirectivesResellerMaxValues = {PHP_DIRECTIVES_RESELLER_MAX_VALUES};

			// PHP Editor error message
			errorMessages = $('.php_editor_error');

			// Function to show a specific message when a PHP Editor setting value is wrong
			function _updateErrorMesssages(k,t) {
				if(t!=undefined) {
					if(!$('#err_'+k).length) {
						$("#msg_default").remove();
						errorMessages.append('<span style="display:block" id="err_'+k+'">'+t+'</span>').
							removeClass('success').addClass('error');
					}
				} else if($('#err_'+k).length) {
					$('#err_'+k).remove();
				}

				if($.trim(errorMessages.text())=='') {
					errorMessages.empty().append('<span id="msg_default">{TR_FIELDS_OK}</span>').
						removeClass('error').addClass('success');
				}
			}

			// Adds an event on each PHP Editor settings input fields to display an
			// error message when a value is wrong
			$.each(phpDirectivesResellerMaxValues,function(k,v) {
				$('#'+k).keyup(function(){
					var r=/^(0|[1-9]\d*)$/; // Regexp to check value syntax
					var nv=$(this).val(); // Get new value to be checked

					if(!r.test(nv)||parseInt(nv)>parseInt(v)) {
						$(this).addClass('ui-state-error');
						_updateErrorMesssages(k,sprintf('{TR_VALUE_ERROR}',k,0,v));
					} else {
						$(this).removeClass('ui-state-error');
						_updateErrorMesssages(k);
					}
				});
				$('#'+k).trigger('keyup');
			});
			<!-- EDP: php_editor_js -->
		});
		/*]]>*/
	</script>
	<form name="editFrm" id="editFrm" method="post" action="domain_edit.php?edit_id={EDIT_ID}">
		<table class="firstColFixed">
			<tr>
				<th colspan="3">{TR_DOMAIN_OVERVIEW}</th>
			</tr>
			<tr>
				<td>{TR_DOMAIN_NAME}</td>
				<td colspan="2">{DOMAIN_NAME}</td>
			</tr>
			<tr>
				<td>{TR_DOMAIN_EXPIRE_DATE}</td>
				<td colspan="2">{DOMAIN_EXPIRE_DATE}</td>
			</tr>
			<tr>
				<td>{TR_DOMAIN_NEW_EXPIRE_DATE}</td>
				<td>
					<div style="position:relative">
						<span style="display:inline-block;">
							<input type="text" id="domain_expires" name="domain_expires" value="{DOMAIN_NEW_EXPIRE_DATE}" {DOMAIN_NEW_EXPIRE_DATE_DISABLED} />
							<label for="domain_expires" style="display:block;color:#999999;font-size: smaller;">(MM/DD/YYYY)</label>
						</span>
					</div>
				</td>
				<td>
					<input type="checkbox" name="domain_never_expires" id="domain_never_expires" {DOMAIN_NEVER_EXPIRES_CHECKED} style="vertical-align: middle;"/>
					<label for="domain_never_expires" style="vertical-align: middle;">{TR_DOMAIN_NEVER_EXPIRES}</label>
				</td>
			</tr>
			<tr>
				<td>{TR_DOMAIN_IP}</td>
				<td colspan="2">{DOMAIN_IP} {IP_DOMAIN}</td>
			</tr>
		</table>
		<table class="firstColFixed">
			<tr>
				<th>{TR_DOMAIN_LIMITS}</th>
				<th>{TR_LIMIT_VALUE}</th>
				<th>{TR_CUSTOMER_CONSUMPTION}</th>
				<th>{TR_RESELLER_CONSUMPTION}</th>
			</tr>
			<!-- BDP: subdomain_limit_block -->
			<tr>
				<td><label for="domain_subd_limit">{TR_SUBDOMAINS_LIMIT}</label></td>
				<td>
					<input type="text" name="domain_subd_limit" id="domain_subd_limit" value="{SUBDOMAIN_LIMIT}"/>
				</td>
				<td>
					<span style="font-size: smaller;">{TR_CUSTOMER_SUBDOMAINS_COMSUPTION}</span>
				</td>
				<td>
					<span style="font-size: smaller;">{TR_RESELLER_SUBDOMAINS_COMSUPTION}</span>
				</td>
			</tr>
			<!-- EDP: subdomain_limit_block -->
			<!-- BDP: domain_aliases_limit_block -->
			<tr>
				<td><label for="domain_alias_limit">{TR_ALIASSES_LIMIT}</label></td>
				<td>
					<input type="text" name="domain_alias_limit" id="domain_alias_limit" value="{DOMAIN_ALIASSES_LIMIT}"/>
				</td>
				<td>
					<span style="font-size: smaller;">{TR_CUSTOMER_DOMAIN_ALIASSES_COMSUPTION}</span>
				</td>
				<td>
					<span style="font-size: smaller;">{TR_RESELLER_DOMAIN_ALIASSES_COMSUPTION}</span>
				</td>
			</tr>
			<!-- EDP: domain_aliases_limit_block -->
			<!-- BDP: mail_accounts_limit_block -->
			<tr>
				<td><label for="domain_mailacc_limit">{TR_MAIL_ACCOUNTS_LIMIT}</label></td>
				<td>
					<input type="text" name="domain_mailacc_limit" id="domain_mailacc_limit" value="{MAIL_ACCOUNTS_LIMIT}"/>
				</td>
				<td>
					<span style="font-size: smaller;">{TR_CUSTOMER_MAIL_ACCOUNTS_COMSUPTION}</span>
				</td>
				<td>
					<span style="font-size: smaller;">{TR_RESELLER_MAIL_ACCOUNTS_COMSUPTION}</span>
				</td>
			</tr>
			<!-- EDP: mail_accounts_limit_block -->
			<!-- BDP: ftp_accounts_limit_block -->
			<tr>
				<td><label for="domain_ftpacc_limit">{TR_FTP_ACCOUNTS_LIMIT}</label></td>
				<td>
					<input type="text" name="domain_ftpacc_limit" id="domain_ftpacc_limit" value="{FTP_ACCOUNTS_LIMIT}"/>

				</td>
				<td>
					<span style="font-size: smaller;">{TR_CUSTOMER_FTP_ACCOUNTS_COMSUPTION}</span>
				</td>
				<td>
					<span style="font-size: smaller;">{TR_RESELLER_FTP_ACCOUNTS_COMSUPTION}</span>
				</td>
			</tr>
			<!-- EDP: ftp_accounts_limit_block -->
			<!-- BDP: sql_db_and_users_limit_block -->
			<tr>
				<td><label for="domain_sqld_limit">{TR_SQL_DATABASES_LIMIT}</label></td>
				<td>
					<input type="text" name="domain_sqld_limit" id="domain_sqld_limit" value="{SQL_DATABASES_LIMIT}"/>
				</td>
				<td>
					<span style="font-size: smaller;">{TR_CUSTOMER_SQL_DATABASES_COMSUPTION}</span>
				</td>
				<td>
					<span style="font-size: smaller;">{TR_RESELLER_SQL_DATABASES_COMSUPTION}</span>
				</td>
			</tr>
			<tr>
				<td><label for="domain_sqlu_limit">{TR_SQL_USERS_LIMIT}</label></td>
				<td>
					<input type="text" name="domain_sqlu_limit" id="domain_sqlu_limit" value="{SQL_USERS_LIMIT}"/>
				</td>
				<td>
					<span style="font-size: smaller;">{TR_CUSTOMER_SQL_USERS_COMSUPTION}</span>
				</td>
				<td>
					<span style="font-size: smaller;">{TR_RESELLER_SQL_USERS_COMSUPTION}</span>
				</td>
			</tr>
			<!-- EDP: sql_db_and_users_limit_block -->
			<tr>
				<td><label for="domain_traffic_limit">{TR_TRAFFIC_LIMIT}</label></td>
				<td>
					<input type="text" name="domain_traffic_limit" id="domain_traffic_limit" value="{TRAFFIC_LIMIT}"/>
				</td>
				<td>
					<span style="font-size: smaller;">{TR_CUSTOMER_TRAFFIC_COMSUPTION}</span>
				</td>
				<td>
					<span style="font-size: smaller;">{TR_RESELLER_TRAFFIC_COMSUPTION}</span>
				</td>
			</tr>
			<tr>
				<td><label for="domain_disk_limit">{TR_DISK_LIMIT}</label></td>
				<td>
					<input type="text" name="domain_disk_limit" id="domain_disk_limit" value="{DISK_LIMIT}"/>
				</td>
				<td>
					<span style="font-size: smaller;">{TR_CUSTOMER_DISKPACE_COMSUPTION}</span>
				</td>
				<td>
					<span style="font-size: smaller;">{TR_RESELLER_DISKPACE_COMSUPTION}</span>
				</td>
			</tr>
		</table>
		<table class="firstColFixed">
			<tr>
				<th>{TR_FEATURE}</th>
				<th>{TR_STATUS}</th>
			</tr>
			<!-- BDP: php_block -->
			<tr>
				<td><label for="domain_php">{TR_PHP}</label></td>
				<td>
					<select id="domain_php" name="domain_php" style="vertical-align: middle">
						<option value="yes" {PHP_YES}>{TR_YES}</option>
						<option value="no" {PHP_NO}>{TR_NO}</option>
					</select>
				</td>
			</tr>

			<!-- BDP: php_editor_block -->
			<tr id="php_editor_block">
				<td><label for="phpiniSystem">{TR_PHP_EDITOR}</label></td>
				<td id="dialogContainer" style="height: 30px;">
					<select id="phpiniSystem" name="phpiniSystem" style="vertical-align: middle;">
						<option value="yes" {PHP_EDITOR_YES}>{TR_YES}</option>
						<option value="no" {PHP_EDITOR_NO}>{TR_NO}</option>
					</select>
					<button type="button" id="php_editor_dialog_open" style="vertical-align: middle;">{TR_SETTINGS}</button>
					<div style="margin:0" id="php_editor_dialog" title="{TR_PHP_EDITOR_SETTINGS}">
						<div class="php_editor_error success">
							<span id="msg_default">{TR_FIELDS_OK}</span>
						</div>
						<table>
							<!-- BDP: php_editor_permissions_block -->
							<tr class="description">
								<th colspan="2">{TR_PERMISSIONS}</th>
							</tr>
							<!-- BDP: php_editor_register_globals_block -->
							<tr>
							   <td>{TR_CAN_EDIT_REGISTER_GLOBALS}</td>
								<td>
									<div class="radio">
										<input type="radio" name="phpini_perm_register_globals" id="phpiniRegisterGlobalsYes" value="yes" {REGISTER_GLOBALS_YES}/>
										<label for="phpiniRegisterGlobalsYes">{TR_YES}</label>
										<input type="radio" name="phpini_perm_register_globals" id="phpiniRegisterGlobalsNo" value="no" {REGISTER_GLOBALS_NO}/>
										<label for="phpiniRegisterGlobalsNo">{TR_NO}</label>
									</div>
								</td>
							</tr>
							<!-- EDP: php_editor_register_globals_block -->
							<!-- BDP: php_editor_allow_url_fopen_block -->
							<tr>
							   <td>{TR_CAN_EDIT_ALLOW_URL_FOPEN}</td>
								<td>
									<div class="radio">
										<input type="radio" name="phpini_perm_allow_url_fopen" id="phpiniAllowUrlFopenYes" value="yes" {ALLOW_URL_FOPEN_YES}/>
										<label for="phpiniAllowUrlFopenYes">{TR_YES}</label>
										<input type="radio" name="phpini_perm_allow_url_fopen" id="phpiniAllowUrlFopenNo" value="no" {ALLOW_URL_FOPEN_NO}/>
										<label for="phpiniAllowUrlFopenNo">{TR_NO}</label>
									</div>
								</td>
							</tr>
							<!-- EDP: php_editor_allow_url_fopen_block -->
							<!-- BDP: php_editor_display_errors_block -->
							<tr>
							   <td>{TR_CAN_EDIT_DISPLAY_ERRORS}</td>
								<td>
									<div class="radio">
										<input type="radio" name="phpini_perm_display_errors" id="phpiniDisplayErrorsYes" value="yes" {DISPLAY_ERRORS_YES}/>
										<label for="phpiniDisplayErrorsYes">{TR_YES}</label>
										<input type="radio" name="phpini_perm_display_errors" id="phpiniDisplayErrorsNo" value="no" {DISPLAY_ERRORS_NO}/>
										<label for="phpiniDisplayErrorsNo">{TR_NO}</label>
									</div>
								</td>
							</tr>
							<!-- EDP: php_editor_display_errors_block -->
							<!-- BDP: php_editor_disable_functions_block -->
							<tr>
							   <td>{TR_CAN_EDIT_DISABLE_FUNCTIONS}</td>
								<td>
									<div class="radio">
										<input type="radio" name="phpini_perm_disable_functions" id="phpiniDisableFunctionsYes" value="yes" {DISABLE_FUNCTIONS_YES}/>
										<label for="phpiniDisableFunctionsYes">{TR_YES}</label>
										<input type="radio" name="phpini_perm_disable_functions" id="phpiniDisableFunctionsNo" value="no" {DISABLE_FUNCTIONS_NO}/>
										<label for="phpiniDisableFunctionsNo">{TR_NO}</label>
										<input type="radio" name="phpini_perm_disable_functions" id="phpiniDisableFunctionsExec" value="exec" {DISABLE_FUNCTIONS_EXEC}/>
										<label for="phpiniDisableFunctionsExec">{TR_ONLY_EXEC}</label>
									</div>
								</td>
							</tr>
							<!-- EDP: php_editor_disable_functions_block -->
							<!-- EDP: php_editor_permissions_block -->
							<!-- BDP: php_editor_default_values_block -->
							<tr class="description">
								<th colspan="2">{TR_DIRECTIVES_VALUES}</th>
							</tr>
							<tr>
							  <td><label for="post_max_size">{TR_PHP_POST_MAX_SIZE_DIRECTIVE}</label></td>
								<td>
									<input name="post_max_size" id="post_max_size" type="text" value="{POST_MAX_SIZE}" /> <span>{TR_MIB}</span>
								</td>
							</tr>
							<tr>
							  <td><label for="upload_max_filezize">{PHP_UPLOAD_MAX_FILEZISE_DIRECTIVE}</label></td>
							  <td>
									<input name="upload_max_filezize" id="upload_max_filezize" type="text" value="{UPLOAD_MAX_FILESIZE}" /> <span>{TR_MIB}</span>
							  </td>
							</tr>
							<tr>
							  <td><label for="max_execution_time">{TR_PHP_MAX_EXECUTION_TIME_DIRECTIVE}</label></td>
							  <td>
									<input name="max_execution_time" id="max_execution_time" type="text" value="{MAX_EXECUTION_TIME}" /> <span>{TR_SEC}</span>
							  </td>
							</tr>
							<tr>
							  <td><label for="max_input_time">{TR_PHP_MAX_INPUT_TIME_DIRECTIVE}</label></td>
							  <td>
									<input name="max_input_time" id="max_input_time" type="text" value="{MAX_INPUT_TIME}" /> <span>{TR_SEC}</span>
							  </td>
							</tr>
							<tr>
							  <td><label for="memory_limit">{TR_PHP_MEMORY_LIMIT_DIRECTIVE}</label></td>
							  <td>
									<input name="memory_limit" id="memory_limit" type="text" value="{MEMORY_LIMIT}" /> <span>{TR_MIB}</span>
							  </td>
							</tr>
							<!-- EDP: php_editor_default_values_block -->
						</table>
					</div>
				</td>
			</tr>
			<!-- EDP: php_editor_block -->

			<!-- EDP: php_block -->
			<!-- BDP: cgi_block -->
			<tr>
				<td><label for="domain_cgi">{TR_CGI}</label></td>
				<td>
					<select id="domain_cgi" name="domain_cgi">
						<option value="yes" {CGI_YES}>{TR_YES}</option>
						<option value="no" {CGI_NO}>{TR_NO}</option>
					</select>
				</td>
			</tr>
			<!-- EDP: cgi_block -->
			<!-- BDP: dns_block -->
			<tr>
				<td><label for="domain_dns">{TR_DNS}</label></td>
				<td>
					<select id="domain_dns" name="domain_dns">
						<option value="yes" {DNS_YES}>{TR_YES}</option>
						<option value="no" {DNS_NO}>{TR_NO}</option>
					</select>
				</td>
			</tr>
			<!-- EDP: dns_block -->
			<!-- BDP: aps_block -->
			<tr>
				<td><label for="domain_software_allowed">{TR_APS}</label></td>
				<td>
					<select name="domain_software_allowed" id="domain_software_allowed">
						<option value="yes" {APS_YES}>{TR_YES}</option>
						<option value="no" {APS_NO}>{TR_NO}</option>
					</select>
				</td>
			</tr>
			<!-- EDP: aps_block -->
			<!-- BDP: backup_block -->
			<tr>
				<td><label for="allowbackup">{TR_BACKUP}</label></td>
				<td>
					<select id="allowbackup" name="allowbackup">
						<option value="dmn" {BACKUP_DOMAIN}>{TR_BACKUP_DOMAIN}</option>
						<option value="sql" {BACKUP_SQL}>{TR_BACKUP_SQL}</option>
						<option value="full" {BACKUP_FULL}>{TR_BACKUP_FULL}</option>
						<option value="no" {BACKUP_NO}>{TR_BACKUP_NO}</option>
					</select>
				</td>
			</tr>
			<!-- EDP: backup_block -->
		</table>
		<div class="buttons">
			<input name="submit" type="submit" value="{TR_UPDATE}"/>
			<input name="cancel" type="button" onclick="MM_goToURL('parent','/reseller/users.php');return document.MM_returnValue" value="{TR_CANCEL}"/>
		</div>
	</form>
