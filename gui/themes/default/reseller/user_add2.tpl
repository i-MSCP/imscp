<!-- INCLUDE "../shared/layout/header.tpl" -->
	<body>
       		<script type="text/javascript">
	        <!--
        	        $(document).ready(function() {
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
        	//-->
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
                <!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
                <!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="users.php">{TR_MENU_MANAGE_USERS}</a></li>
				<li><a href="user_add1.php">{TR_ADD_USER}</a></li>
				<li>{TR_HOSTING_PLAN_PROPERTIES}</li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<h2 class="user"><span>{TR_ADD_USER}</span></h2>
			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->


			<!-- BDP: add_user -->
			<form name="reseller_add_users_second_frm" method="post" action="user_add2.php">
				<fieldset>
					<legend>{TR_HOSTING_PLAN_PROPERTIES}</legend>
					<table>
						<tr>
							<td style="width:300px;">{TR_TEMPLATE_NAME}</td>
							<td><input name="template" type="hidden" id="template" value="{VL_TEMPLATE_NAME}" />{VL_TEMPLATE_NAME}</td>
						</tr>
						<tr>
							<td><label for="nreseller_max_subdomain_cnt">{TR_MAX_SUBDOMAIN}</label></td>
							<td><input id="nreseller_max_subdomain_cnt" type="text" name="nreseller_max_subdomain_cnt" value="{MAX_SUBDMN_CNT}" /></td>
						</tr>
						<tr>
							<td><label for="nreseller_max_alias_cnt">{TR_MAX_DOMAIN_ALIAS}</label></td>
							<td><input id="nreseller_max_alias_cnt" type="text" name="nreseller_max_alias_cnt" value="{MAX_DMN_ALIAS_CNT}" /></td>
						</tr>
						<tr>
							<td><label for="nreseller_max_mail_cnt">{TR_MAX_MAIL_COUNT}</label></td>
							<td><input id="nreseller_max_mail_cnt" type="text" name="nreseller_max_mail_cnt" value="{MAX_MAIL_CNT}"/></td>
						</tr>
						<tr>
							<td><label for="nreseller_max_ftp_cnt">{TR_MAX_FTP}</label></td>
							<td><input id="nreseller_max_ftp_cnt"type="text" name="nreseller_max_ftp_cnt" value="{MAX_FTP_CNT}" /></td>
						</tr>
						<tr>
							<td><label for="nreseller_max_sql_db_cnt">{TR_MAX_SQL_DB}</label></td>
							<td><input id="nreseller_max_sql_db_cnt" type="text" name="nreseller_max_sql_db_cnt" value="{MAX_SQL_CNT}"/></td>
						</tr>
						<tr>
							<td><label for="nreseller_max_sql_user_cnt">{TR_MAX_SQL_USERS}</label></td>
							<td><input id="nreseller_max_sql_user_cnt" type="text" name="nreseller_max_sql_user_cnt" value="{VL_MAX_SQL_USERS}"/></td>
						</tr>
						<tr>
							<td><label for="nreseller_max_traffic">{TR_MAX_TRAFFIC}</label></td>
							<td><input id="nreseller_max_traffic" type="text" name="nreseller_max_traffic" value="{VL_MAX_TRAFFIC}"/></td>
						</tr>
						<tr>
							<td><label for="nreseller_max_disk">{TR_MAX_DISK_USAGE}</label></td>
							<td><input id="nreseller_max_disk" type="text" name="nreseller_max_disk" value="{VL_MAX_DISK_USAGE}"/></td>
						</tr>
						<tr>
							<td>{TR_PHP}</td>
							<td>
								<input type="radio" id="php_yes" name="php" value="_yes_" {VL_PHPY} /><label for="php_yes">{TR_YES}</label>
								<input type="radio" id="php_no" name="php" value="_no_" {VL_PHPN} /><label for="php_no">{TR_NO}</label>
							</td>
						</tr>
						<tr>
							<td>{TR_CGI}</td>
							<td>
								<input type="radio" id="cgi_yes" name="cgi" value="_yes_" {VL_CGIY} /><label for="cgi_yes">{TR_YES}</label>
								<input type="radio" id="cgi_no" name="cgi" value="_no_" {VL_CGIN} /><label for="cgi_no">{TR_NO}</label>
							</td>
						</tr>
						<tr>
							<td>{TR_DNS}</td>
							<td>
								<input type="radio" id="dns_yes" name="dns" value="_yes_" {VL_DNSY} /><label for="dns_yes">{TR_YES}</label>
								<input type="radio" id="dns_no" name="dns" value="_no_" {VL_DNSN} /><label for="dns_no">{TR_NO}</label>
							</td>
						</tr>

						<tr>
							<td>{TR_BACKUP}</td>
							<td>
								<input type="radio" id="backup_dmn" name="backup" value="_dmn_" {VL_BACKUPD} /><label for="backup_dmn">{TR_BACKUP_DOMAIN}</label>
								<input type="radio" id="backup_sql" name="backup" value="_sql_" {VL_BACKUPS} /><label for="backup_sql">{TR_BACKUP_SQL}</label>
								<input type="radio" id="backup_full" name="backup" value="_full_" {VL_BACKUPF} /><label for="backup_full">{TR_BACKUP_FULL}</label>
								<input type="radio" id="backup_no" name="backup" value="_no_" {VL_BACKUPN} /><label for="backup_no">{TR_BACKUP_NO}</label>
							</td>
						</tr>
						<!-- BDP: t_software_support -->
 						<tr>
 							<td>{TR_SOFTWARE_SUPP}</td>
 							<td>
 								<input type="radio" name="software_allowed" value="_yes_" {VL_SOFTWAREY} id="software_allowed_yes" /><label for="software_allowed_yes">{TR_YES}</label>
								<input type="radio" name="software_allowed" value="_no_" {VL_SOFTWAREN} id="software_allowed_no" /><label for="software_allowed_no">{TR_NO}</label>
							</td>
 						</tr>
 						<!-- EDP: t_software_support -->
                                                <!-- BDP: t_phpini_system -->
                                                <tr>
                                                   <td>{TR_PHPINI_SYSTEM}</td>
                                                    <td>
                                                        <input type="radio" name="phpini_system" id="phpini_system_yes" value="yes" {PHPINI_SYSTEM_YES} />
                                                        <label for="phpini_system_yes">{TR_YES}</label>
                                                        <input type="radio" name="phpini_system" id="phpini_system_no" value="no" {PHPINI_SYSTEM_NO} />
                                                        <label for="support_system_no">{TR_NO}</label>
                                                    </td>
                                                </tr>
                                               <tbody id='phpinidetail'>
                                                <!-- BDP: t_phpini_register_globals -->
                                                <tr id='php_ini_block_register_globals'>
                                                   <td>{TR_PHPINI_AL_REGISTER_GLOBALS}</td>
                                                    <td>
                                                        <input type="radio" name="phpini_al_register_globals" id="phpini_al_register_globals_yes" value="yes" {PHPINI_AL_REGISTER_GLOBALS_YES} />
                                                        <label for="phpini_al_register_globals_yes">{TR_YES}</label>
                                                        <input type="radio" name="phpini_al_register_globals" id="phpini_al_register_globals_no" value="no" {PHPINI_AL_REGISTER_GLOBALS_NO} />
                                                        <label for="phpini_al_register_globals_no">{TR_NO}</label>
                                                </tr>
                                                <!-- EDP: t_phpini_register_globals -->
                                                <!-- BDP: t_phpini_allow_url_fopen -->
                                                <tr id='php_ini_block_allow_url_fopen'>
                                                   <td>{TR_PHPINI_AL_ALLOW_URL_FOPEN}</td>
                                                    <td>
                                                        <input type="radio" name="phpini_al_allow_url_fopen" id="phpini_al_allow_url_fopen_yes" value="yes" {PHPINI_AL_ALLOW_URL_FOPEN_YES} />
                                                        <label for="phpini_al_allow_url_fopen_yes">{TR_YES}</label>
                                                        <input type="radio" name="phpini_al_allow_url_fopen" id="phpini_al_allow_url_fopen_no" value="no" {PHPINI_AL_ALLOW_URL_FOPEN_NO} />
                                                        <label for="phpini_al_allow_url_fopen_no">{TR_NO}</label>
                                                    </td>
                                                </tr>
                                                <!-- EDP: t_phpini_allow_url_fopen -->
                                                <!-- BDP: t_phpini_display_errors -->
                                                <tr id='php_ini_block_display_errors'>
                                                   <td>{TR_PHPINI_AL_DISPLAY_ERRORS}</td>
                                                    <td>
                                                        <input type="radio" name="phpini_al_display_errors" id="phpini_al_display_errors_yes" value="yes" {PHPINI_AL_DISPLAY_ERRORS_YES} />
                                                        <label for="phpini_al_display_errors_yes">{TR_YES}</label>
                                                        <input type="radio" name="phpini_al_display_errors" id="phpini_al_display_errors_no" value="no" {PHPINI_AL_DISPLAY_ERRORS_NO} />
                                                        <label for="phpini_al_display_errors_no">{TR_NO}</label>
                                                    </td>
                                                </tr>
                                                <!-- EDP: t_phpini_display_errors -->
                                                <!-- BDP: t_phpini_disable_functions -->
                                                <tr id='php_ini_block_disable_functions'>
                                                   <td>{TR_PHPINI_AL_DISABLE_FUNCTIONS}</td>
                                                    <td>
                                                        <input type="radio" name="phpini_al_disable_functions" id="phpini_al_disable_functions_yes" value="yes" {PHPINI_AL_DISABLE_FUNCTIONS_YES} />
                                                        <label for="phpini_al_disable_functions_yes">{TR_YES}</label>
                                                        <input type="radio" name="phpini_al_disable_functions" id="phpini_al_disable_functions_no" value="no" {PHPINI_AL_DISABLE_FUNCTIONS_NO} />
                                                        <label for="phpini_al_disable_functions_no">{TR_NO}</label>
                                                        <input type="radio" name="phpini_al_disable_functions" id="phpini_al_disable_functions_exec" value="exec" {PHPINI_AL_DISABLE_FUNCTIONS_EXEC} />
                                                        <label for="phpini_al_disable_functions_exec">{TR_USER_EDITABLE_EXEC}</label>
                                                    </td>
                                                </tr>
                                                <!-- EDP: t_phpini_disable_functions -->
                                                <tr>
                                                  <td><label for="phpini_post_max_size">{TR_PHPINI_POST_MAX_SIZE}</label></td>
                                                  <td>
                                                        <input name="phpini_post_max_size" id="phpini_post_max_size" type="text" value="{PHPINI_POST_MAX_SIZE}" />
                                                  </td>
                                                </tr>
                                                <tr>
                                                  <td><label for="phpini_upload_max_filesize">{TR_PHPINI_UPLOAD_MAX_FILESIZE}</label></td>
                                                  <td>
                                                        <input name="phpini_upload_max_filesize" id="phpini_upload_max_filesize" type="text" value="{PHPINI_UPLOAD_MAX_FILESIZE}" />
                                                  </td>
                                                </tr>
                                                <tr>
                                                  <td><label for="phpini_max_execution_time">{TR_PHPINI_MAX_EXECUTION_TIME}</label></td>
                                                  <td>
                                                        <input name="phpini_max_execution_time" id="phpini_max_execution_time" type="text" value="{PHPINI_MAX_EXECUTION_TIME}" />
                                                  </td>
                                                </tr>
                                                <tr>
                                                  <td><label for="phpini_max_input_time">{TR_PHPINI_MAX_INPUT_TIME}</label></td>
                                                  <td>
                                                        <input name="phpini_max_input_time" id="phpini_max_input_time" type="text" value="{PHPINI_MAX_INPUT_TIME}" />
                                                  </td>
                                                </tr>
                                                <tr>
                                                  <td><label for="phpini_memory_limit">{TR_PHPINI_MEMORY_LIMIT}</label></td>
                                                  <td>
                                                        <input name="phpini_memory_limit" id="phpini_memory_limit" type="text" value="{PHPINI_MEMORY_LIMIT}" />
                                                  </td>
                                                </tr>

                                               </tbody>
                                                <!-- EDP: t_phpini_system -->
					</table>
				</fieldset>

				<div class="buttons">
					<input name="Submit" type="submit" class="button" value="{TR_NEXT_STEP}" />
				</div>
				<input type="hidden" name="uaction" value="user_add2_nxt" />
			</form>
			<!-- EDP: add_user -->
		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
