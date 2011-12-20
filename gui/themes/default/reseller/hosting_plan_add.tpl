
			<script type="text/javascript">
			/*<![CDATA[*/
				$(document).ready(function () {
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
			<form name="hostingPlanAddFrm" method="post" action="hosting_plan_add.php">
				<table class="firstColFixed">
				<tr>
					<th colspan="2">{TR_HOSTING PLAN PROPS}</th>
				</tr>
				<tr>
					<td><label for="hp_name">{TR_TEMPLATE_NAME}</label></td><td>
						<input id="hp_name" type="text" name="hp_name" value="{HP_NAME_VALUE}" class="inputTitle"/>
					</td>
				</tr>
				<tr>
					<td><label for="hp_description">{TR_TEMPLATE_DESCRIPTON}</label></td>
					<td>
						<textarea id="hp_description" name="hp_description">{HP_DESCRIPTION_VALUE}</textarea>
					</td>
				</tr>
				<tr>
					<td><label for="hp_sub">{TR_MAX_SUBDOMAINS}</label></td>
					<td><input id="hp_sub" type="text" name="hp_sub" value="{TR_MAX_SUB_LIMITS}"/></td>
				</tr>
				<tr>
					<td><label for="hp_als">{TR_MAX_ALIASES}</label></td>
					<td><input id="hp_als" type="text" name="hp_als" value="{TR_MAX_ALS_VALUES}"/></td>
				</tr>
				<tr>
					<td><label for="hp_mail">{TR_MAX_MAILACCOUNTS}</label>
					</td>
					<td><input id="hp_mail" type="text" name="hp_mail" value="{HP_MAIL_VALUE}"/></td>
				</tr>
				<tr>
					<td><label for="hp_ftp">{TR_MAX_FTP}</label></td>
					<td><input id="hp_ftp" type="text" name="hp_ftp" value="{HP_FTP_VALUE}"/></td>
				</tr>
				<tr>
					<td><label for="hp_sql_db">{TR_MAX_SQL}</label></td>
					<td><input id="hp_sql_db" type="text" name="hp_sql_db" value="{HP_SQL_DB_VALUE}"/></td>
				</tr>
				<tr>
					<td><label for="hp_sql_user">{TR_MAX_SQL_USERS}</label></td>
					<td><input id="hp_sql_user" type="text" name="hp_sql_user" value="{HP_SQL_USER_VALUE}"/></td>
				</tr>
				<tr>
					<td><label for="hp_traff">{TR_MAX_TRAFFIC}</label></td>
					<td><input id="hp_traff" type="text" name="hp_traff" value="{HP_TRAFF_VALUE}"/></td>
				</tr>
				<tr>
					<td><label for="hp_disk">{TR_DISK_LIMIT}</label></td>
					<td><input id="hp_disk" type="text" name="hp_disk" value="{HP_DISK_VALUE}"/></td>
				</tr>
				<tr>
					<td>{TR_PHP}</td>
					<td>
						<div class="radio">
							<input type="radio" name="php" value="_yes_" {TR_PHP_YES} id="php_yes"/>
							<label for="php_yes">{TR_YES}</label>
							<input type="radio" name="php" value="_no_" {TR_PHP_NO} id="php_no"/>
							<label for="php_no">{TR_NO}</label>
						</div>
					</td>
				</tr>
				<tr>
					<td>{TR_CGI}</td>
					<td>
						<div class="radio">
							<input type="radio" name="cgi" value="_yes_" {TR_CGI_YES} id="cgi_yes"/>
							<label for="cgi_yes">{TR_YES}</label>
							<input type="radio" name="cgi" value="_no_" {TR_CGI_NO} id="cgi_no"/>
							<label for="cgi_no">{TR_NO}</label>
						</div>
					</td>
				</tr>
				<tr>
					<td>{TR_DNS}</td>
					<td>
						<div class="radio">
							<input type="radio" name="dns" value="_yes_" {TR_DNS_YES} id="dns_yes"/>
							<label for="dns_yes">{TR_YES}</label>
							<input type="radio" name="dns" value="_no_" {TR_DNS_NO} id="dns_no"/>
							<label for="dns_no">{TR_NO}</label>
						</div>
					</td>
				</tr>
				<tr>
					<td>{TR_BACKUP}</td>
					<td>
						<div class="radio">
							<input type="radio" name="backup" value="_dmn_" {VL_BACKUPD} id="backup_dmn"/>
							<label for="backup_dmn">{TR_BACKUP_DOMAIN}</label>
							<input type="radio" name="backup" value="_sql_" {VL_BACKUPS} id="backup_sql"/>
							<label for="backup_sql">{TR_BACKUP_SQL}</label>
							<input type="radio" name="backup" value="_full_" {VL_BACKUPF} id="backup_full"/>
							<label for="backup_full">{TR_BACKUP_FULL}</label>
							<input type="radio" name="backup" value="_no_" {VL_BACKUPN} id="backup_none"/>
							<label for="backup_none">{TR_BACKUP_NO}</label>
						</div>
					</td>
				</tr>
				<!-- BDP: t_software_support -->
				<tr>
					<td>{TR_SOFTWARE_SUPP}</td>
					<td>
						<div class="radio">
							<input type="radio" name="software_allowed" value="_yes_" {VL_SOFTWAREY}
								   id="software_allowed_yes"/>
							<label for="software_allowed_yes">{TR_YES}</label>
							<input type="radio" name="software_allowed" value="_no_" {VL_SOFTWAREN}
								   id="software_allowed_no"/>
							<label for="software_allowed_no">{TR_NO}</label>
						</div>
					</td>
				</tr>
				<!-- EDP: t_software_support -->
				<!-- BDP: t_phpini_system -->
				<tr>
					<td>{TR_PHPINI_SYSTEM}</td>
					<td>
						<div class="radio">
							<input type="radio" name="phpini_system" id="phpini_system_yes"
								   value="yes" {PHPINI_SYSTEM_YES} />
							<label for="phpini_system_yes">{TR_YES}</label>
							<input type="radio" name="phpini_system" id="phpini_system_no"
								   value="no" {PHPINI_SYSTEM_NO} />
							<label for="phpini_system_no">{TR_NO}</label>
						</div>
					</td>
				</tr>
				<tbody id='phpinidetail'>
				<!-- BDP: t_phpini_register_globals -->
				<tr id='php_ini_block_register_globals'>
					<td>{TR_PHPINI_AL_REGISTER_GLOBALS}</td>
					<td>
						<div class="radio">
							<input type="radio" name="phpini_al_register_globals" id="phpini_al_register_globals_yes" value="yes" {PHPINI_AL_REGISTER_GLOBALS_YES} />
							<label for="phpini_al_register_globals_yes">{TR_YES}</label>
							<input type="radio" name="phpini_al_register_globals" id="phpini_al_register_globals_no" value="no" {PHPINI_AL_REGISTER_GLOBALS_NO} />
							<label for="phpini_al_register_globals_no">{TR_NO}</label>
						</div>
					</td>
				</tr>
				<!-- EDP: t_phpini_register_globals -->
				<!-- BDP: t_phpini_allow_url_fopen -->
				<tr id='php_ini_block_allow_url_fopen'>
					<td>{TR_PHPINI_AL_ALLOW_URL_FOPEN}</td>
					<td>
						<div class="radio">
							<input type="radio" name="phpini_al_allow_url_fopen" id="phpini_al_allow_url_fopen_yes" value="yes" {PHPINI_AL_ALLOW_URL_FOPEN_YES} />
							<label for="phpini_al_allow_url_fopen_yes">{TR_YES}</label>
							<input type="radio" name="phpini_al_allow_url_fopen" id="phpini_al_allow_url_fopen_no" value="no" {PHPINI_AL_ALLOW_URL_FOPEN_NO} />
							<label for="phpini_al_allow_url_fopen_no">{TR_NO}</label>
						</div>
					</td>
				</tr>
				<!-- EDP: t_phpini_allow_url_fopen -->
				<!-- BDP: t_phpini_display_errors -->
				<tr id='php_ini_block_display_errors'>
					<td>{TR_PHPINI_AL_DISPLAY_ERRORS}</td>
					<td>
						<div class="radio">
							<input type="radio" name="phpini_al_display_errors" id="phpini_al_display_errors_yes" value="yes" {PHPINI_AL_DISPLAY_ERRORS_YES} />
							<label for="phpini_al_display_errors_yes">{TR_YES}</label>
							<input type="radio" name="phpini_al_display_errors" id="phpini_al_display_errors_no" value="no" {PHPINI_AL_DISPLAY_ERRORS_NO} />
							<label for="phpini_al_display_errors_no">{TR_NO}</label>
						</div>
					</td>
				</tr>
				<!-- EDP: t_phpini_display_errors -->
				<!-- BDP: t_phpini_disable_functions -->
				<tr id='php_ini_block_disable_functions'>
					<td>{TR_PHPINI_AL_DISABLE_FUNCTIONS}</td>
					<td>
						<div class="radio">
							<input type="radio" name="phpini_al_disable_functions" id="phpini_al_disable_functions_yes" value="yes" {PHPINI_AL_DISABLE_FUNCTIONS_YES} />
							<label for="phpini_al_disable_functions_yes">{TR_YES}</label>
							<input type="radio" name="phpini_al_disable_functions" id="disable_functions_no" value="no" {PHPINI_AL_DISABLE_FUNCTIONS_NO} />
							<label for="disable_functions_no">{TR_NO}</label>
							<input type="radio" name="phpini_al_disable_functions" id="disable_functions_exec" value="exec" {PHPINI_AL_DISABLE_FUNCTIONS_EXEC} />
							<label for="disable_functions_exec">{TR_USER_EDITABLE_EXEC}</label>
						</div>
					</td>
				</tr>
				<!-- EDP: t_phpini_disable_functions -->
				<tr>
					<td><label for="phpini_post_max_size">{TR_PHPINI_POST_MAX_SIZE}</label></td>
					<td>
						<input name="phpini_post_max_size" id="phpini_post_max_size" type="text" value="{PHPINI_POST_MAX_SIZE}"/> {TR_MIB}
					</td>
				</tr>
				<tr>
					<td><label for="phpini_upload_max_filesize">{TR_PHPINI_UPLOAD_MAX_FILESIZE}</label></td>
					<td>
						<input name="phpini_upload_max_filesize" id="phpini_upload_max_filesize" type="text" value="{PHPINI_UPLOAD_MAX_FILESIZE}"/> {TR_MIB}
					</td>
				</tr>
				<tr>
					<td>
						<label for="phpini_max_execution_time">{TR_PHPINI_MAX_EXECUTION_TIME}</label>
					</td>
					<td>
						<input name="phpini_max_execution_time" id="phpini_max_execution_time" type="text" value="{PHPINI_MAX_EXECUTION_TIME}"/> {TR_SEC}
					</td>
				</tr>
				<tr>
					<td>
						<label for="phpini_max_input_time">{TR_PHPINI_MAX_INPUT_TIME}</label>
					</td>
					<td>
						<input name="phpini_max_input_time" id="phpini_max_input_time" type="text" value="{PHPINI_MAX_INPUT_TIME}"/> {TR_SEC}
					</td>
				</tr>
				<tr>
					<td>
						<label for="phpini_memory_limit">{TR_PHPINI_MEMORY_LIMIT}</label>
					</td>
					<td>
						<input name="phpini_memory_limit" id="phpini_memory_limit" type="text" value="{PHPINI_MEMORY_LIMIT}"/> {TR_MIB}
					</td>
				</tr>
				</tbody>
				<!-- EDP: t_phpini_system -->
				</table>

				<table class="firstColFixed">
					<tr>
						<th colspan="2">{TR_BILLING_PROPS}</th>
					</tr>
					<tr>
						<td><label for="hp_price">{TR_PRICE}</label></td>
						<td><input name="hp_price" type="text" id="hp_price" value="{HP_PRICE}"/></td>
					</tr>
					<tr>
						<td><label for="hp_setupfee">{TR_SETUP_FEE}</label></td>
						<td><input name="hp_setupfee" type="text" id="hp_setupfee" value="{HP_SETUPFEE}"/></td>
					</tr>
					<tr>
						<td><label for="hp_value">{TR_VALUE}</label></td>
						<td>
							<input name="hp_value" type="text" id="hp_value" value="{HP_VELUE}"/>
							<span class="legend">{TR_EXAMPLE}</span>
						</td>
					</tr>
					<tr>
						<td><label for="hp_payment">{TR_PAYMENT}</label></td>
						<td><input name="hp_payment" type="text" id="hp_payment" value="{HP_PAYMENT}"/></td>
					</tr>
					<tr>
						<td>{TR_STATUS}</td>
						<td>
							<div class="radio">
								<input type="radio" name="status" value="1" {TR_STATUS_YES} id="status_yes"/>
								<label for="status_yes">{TR_YES}</label>
								<input type="radio" name="status" value="0" {TR_STATUS_NO} id="status_no"/>
								<label for="status_no">{TR_NO}</label>
							</div>
						</td>
					</tr>
				</table>
				<table class="firstColFixed">
					<tr>
						<th colspan="2">{TR_TOS_PROPS}</th>
					</tr>
					<!-- TOS -->
					<tr>
						<td colspan="2">{TR_TOS_NOTE}</td>
					</tr>
					<tr>
						<td><label for="hp_tos">{TR_TOS_DESCRIPTION}</label></td>
						<td><textarea name="hp_tos" id="hp_tos">{HP_TOS_VALUE}</textarea></td>
					</tr>
					<!-- TOS END -->
				</table>
				<div class="buttons">
					<input type="hidden" name="uaction" value="add_plan"/>
					<input name="submit" type="submit" value="{TR_ADD_PLAN}"/>
				</div>
			</form>
