
	<script type="text/javascript">
	/*<![CDATA[*/
		$(document).ready(function() {
			$('.radio').buttonset();

			<!-- BDP: php_editor_js -->
			$.fx.speeds._default = 500;

			// PHP Editor settings dialog
			$('#php_editor_dialog').dialog(
			{
				hide:'blind',show:'slide',focus:false,autoOpen:false,width:'auto',
				modal:true,dialogClass:'body',buttons:{'{TR_CLOSE}':function(){$(this).dialog('close');}}
			});

			// Re-add the PHP Editor to the form
			$('#addFrm2').submit(function(){$('#php_editor_dialog').parent().appendTo($(this));});

			// PHP Editor settings button
			if($('#php_no').is(':checked')){$('#php_editor_block').hide();}

			$('#php_yes,#php_no').change(function(){$('#php_editor_block').fadeToggle();});

			$('#php_editor_dialog_open').button({icons:{primary:'ui-icon-gear'}}).click(function(e){
					$('#php_editor_dialog').dialog('open');
					return false;
			});

			// Do not show PHP Editor settings button if disabled
			if($('#phpiniSystemNo').is(':checked')){$('#php_editor_dialog_open').hide();}
			$('#phpiniSystemYes,#phpiniSystemNo').change(function(){$('#php_editor_dialog_open').fadeToggle();});

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
		<!-- BDP: add_user -->
		<form id="addFrm2" name="addFrm2" method="post" action="user_add2.php">
			<table>
				<tr>
					<th colspan="2">{TR_HOSTING_PLAN}</th>
				</tr>
				<tr>
					<td style="width:300px;">{TR_NAME}</td>
					<td>
						<input name="template" type="hidden" id="template" value="{VL_TEMPLATE_NAME}"/>{VL_TEMPLATE_NAME}
					</td>
				</tr>
				<tr>
					<th colspan="2">{TR_LIMITS}</th>
				</tr>
				<!-- BDP: subdomain_feature -->
				<tr>
					<td><label for="nreseller_max_subdomain_cnt">{TR_MAX_SUBDOMAIN}</label></td>
					<td>
						<input id="nreseller_max_subdomain_cnt" type="text" name="nreseller_max_subdomain_cnt" value="{MAX_SUBDMN_CNT}"/>
					</td>
				</tr>
				<!-- EDP: subdomain_feature -->
				<!-- BDP: alias_feature -->
				<tr>
					<td><label for="nreseller_max_alias_cnt">{TR_MAX_DOMAIN_ALIAS}</label></td>
					<td>
						<input id="nreseller_max_alias_cnt" type="text" name="nreseller_max_alias_cnt" value="{MAX_DMN_ALIAS_CNT}"/>
					</td>
				</tr>
				<!-- EDP: alias_feature -->
				<!-- BDP: mail_feature -->
				<tr>
					<td><label for="nreseller_max_mail_cnt">{TR_MAX_MAIL_COUNT}</label></td>
					<td>
						<input id="nreseller_max_mail_cnt" type="text" name="nreseller_max_mail_cnt" value="{MAX_MAIL_CNT}"/>
					</td>
				</tr>
				<!-- EDP: mail_feature -->
				<!-- BDP: ftp_feature -->
				<tr>
					<td><label for="nreseller_max_ftp_cnt">{TR_MAX_FTP}</label></td>
					<td>
						<input id="nreseller_max_ftp_cnt" type="text" name="nreseller_max_ftp_cnt" value="{MAX_FTP_CNT}"/>
					</td>
				</tr>
				<!-- EDP: ftp_feature -->
				<!-- BDP: sql_feature -->
				<tr>
					<td><label for="nreseller_max_sql_db_cnt">{TR_MAX_SQL_DB}</label></td>
					<td>
						<input id="nreseller_max_sql_db_cnt" type="text" name="nreseller_max_sql_db_cnt"  value="{MAX_SQL_CNT}"/>
					</td>
				</tr>
				<tr>
					<td><label for="nreseller_max_sql_user_cnt">{TR_MAX_SQL_USERS}</label></td>
					<td>
						<input id="nreseller_max_sql_user_cnt" type="text" name="nreseller_max_sql_user_cnt" value="{VL_MAX_SQL_USERS}"/>
					</td>
				</tr>
				<!-- EDP: sql_feature -->
				<tr>
					<td><label for="nreseller_max_traffic">{TR_MAX_TRAFFIC}</label></td>
					<td>
						<input id="nreseller_max_traffic" type="text" name="nreseller_max_traffic" value="{VL_MAX_TRAFFIC}"/>
					</td>
				</tr>
				<tr>
					<td><label for="nreseller_max_disk">{TR_MAX_DISK_USAGE}</label></td>
					<td>
						<input id="nreseller_max_disk" type="text"  name="nreseller_max_disk" value="{VL_MAX_DISK_USAGE}"/>
					</td>
				</tr>
				<tr>
					<th colspan="2">{TR_FEATURES}</th>
				</tr>
				<tr>
					<td>{TR_PHP}</td>
					<td>
						<div class="radio">
							<input type="radio" id="php_yes" name="php" value="_yes_" {VL_PHPY} />
							<label for="php_yes">{TR_YES}</label>
							<input type="radio" id="php_no" name="php"  value="_no_" {VL_PHPN} />
							<label for="php_no">{TR_NO}</label>
						</div>
					</td>
				</tr>
				<!-- BDP: php_editor_block -->
				<tr id="php_editor_block">
					<td><label>{TR_PHP_EDITOR}</label></td>
					<td colspan="2">
						<div class="radio">
							<input type="radio" name="phpiniSystem" id="phpiniSystemYes" value="yes" {PHP_EDITOR_YES}/>
							<label for="phpiniSystemYes">{TR_YES}</label>
							<input type="radio" name="phpiniSystem" id="phpiniSystemNo" value="no" {PHP_EDITOR_NO}/>
							<label for="phpiniSystemNo">{TR_NO}</label>
							<input type="button" name="php_editor_dialog_open" id="php_editor_dialog_open" value="{TR_SETTINGS}" />
						</div>
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
				<tr>
					<td>{TR_CGI}</td>
					<td>
						<div class="radio">
							<input type="radio" id="cgi_yes" name="cgi" value="_yes_" {VL_CGIY} />
							<label for="cgi_yes">{TR_YES}</label>
							<input type="radio" id="cgi_no" name="cgi" value="_no_" {VL_CGIN} />
							<label for="cgi_no">{TR_NO}</label>
						</div>
					</td>
				</tr>
				<tr>
					<td>{TR_DNS}</td>
					<td>
						<div class="radio">
							<input type="radio" id="dns_yes" name="dns" value="_yes_" {VL_DNSY} />
							<label for="dns_yes">{TR_YES}</label>
							<input type="radio" id="dns_no" name="dns" value="_no_" {VL_DNSN} />
							<label for="dns_no">{TR_NO}</label>
						</div>
					</td>
				</tr>
				<!-- BDP: aps_feature -->
				<tr>
					<td>{TR_SOFTWARE_SUPP}</td>
					<td>
						<div class="radio">
							<input type="radio" name="software_allowed" value="_yes_" {VL_SOFTWAREY} id="software_allowed_yes"/>
							<label for="software_allowed_yes">{TR_YES}</label>
							<input type="radio" name="software_allowed" value="_no_" {VL_SOFTWAREN} id="software_allowed_no"/>
							<label for="software_allowed_no">{TR_NO}</label>
						</div>
					</td>
				</tr>
				<!-- EDP: aps_feature -->
				<!-- BDP: backup_feature -->
				<tr>
					<td>{TR_BACKUP}</td>
					<td>
						<div class="radio">
							<input type="radio" id="backup_dmn" name="backup"  value="_dmn_" {VL_BACKUPD} />
							<label for="backup_dmn">{TR_BACKUP_DOMAIN}</label>
							<input type="radio" id="backup_sql" name="backup" value="_sql_" {VL_BACKUPS} />
							<label for="backup_sql">{TR_BACKUP_SQL}</label>
							<input type="radio" id="backup_full" name="backup" value="_full_" {VL_BACKUPF} />
							<label for="backup_full">{TR_BACKUP_FULL}</label>
							<input type="radio" id="backup_no" name="backup" value="_no_" {VL_BACKUPN} />
							<label for="backup_no">{TR_BACKUP_NO}</label>
						</div>
					</td>
				</tr>
				<!-- EDP: backup_feature -->
			</table>
			<div class="buttons">
				<input type="hidden" name="uaction" value="user_add2_nxt"/>
				<input name="Submit" type="submit" class="button" value="{TR_NEXT_STEP}"/>
			</div>
		</form>
		<!-- EDP: add_user -->
