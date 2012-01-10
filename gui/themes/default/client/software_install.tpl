
			<script language="JavaScript" type="text/JavaScript">
				/*<![CDATA[*/
				function openTree() {
					libwindow=window.open("ftp_choose_dir.php","Hello","menubar=no,width=470,height=350,scrollbars=yes");
				}

				function setInstallPath() {
					var inputvars = document.forms[0].elements['selected_domain'].value;
					inputvars = inputvars.toLowerCase();
					var splitinputvars = inputvars.split(";");
					document.forms[0].elements['other_dir'].value = splitinputvars[4];
				}
				/*]]>*/
			</script>
			<form method="post" action="{SOFTWARE_INSTALL_BUTTON}">
				<table class="firstColFixed">
					<tr>
						<th colspan="2">{TR_INSTALLATION}</th>
					</tr>
					<!-- BDP: software_item -->
					<tr>
						<td>{TR_NAME}</td>
						<td>{TR_SOFTWARE_NAME}</td>
					</tr>
					<tr>
						<td>{TR_TYPE}</td>
						<td>{SOFTWARE_TYPE}</td>
					</tr>
					<tr>
						<td>{TR_DB}</td>
						<td>{SOFTWARE_DB}</td>
					</tr>
					<tr>
						<td><label for="selected_domain">{TR_SELECT_DOMAIN}</label></td>
						<td>
							<select name="selected_domain" id="selected_domain" onchange="setInstallPath();">
								<option value="{DOMAINSTANDARD_NAME_VALUES}">{DOMAINSTANDARD_NAME}</option>
								<!-- BDP: show_domain_list -->
								<option {SELECTED_DOMAIN} value="{DOMAIN_NAME_VALUES}">{DOMAIN_NAME}</option>
								<!-- EDP: show_domain_list -->
							</select>
						</td>
					</tr>
					<tr>
						<td><label for="path">{TR_PATH}</label></td>
						<td>
							<input type="text" name="other_dir" id="path" value="{VAL_OTHER_DIR}"/>
							<a href="javascript:openTree();">{CHOOSE_DIR}</a>
							<input type="checkbox" name="createdir" id="createdir" value="1" {CHECKED_CREATEDIR}/>
							<label for="createdir">{CREATEDIR_MESSAGE}</label>
						</td>
					</tr>
					<!-- BDP: require_installdb -->
					<tr>
						<td><label for="selected_db">{TR_SELECT_DB}</label></td>
						<td>
							<!-- BDP: select_installdb -->
							<select name="selected_db" id="selected_db">
								<!-- BDP: installdb_item -->
								<option {SELECTED_DB} value="{DB_NAME}">{DB_NAME}</option>
								<!-- EDP: installdb_item -->
							</select>
							<!-- EDP: select_installdb -->
							<!-- BDP: create_db -->
							<input name="Submit3" type="submit" class="button" onClick="MM_goToURL('parent','{ADD_DB_LINK}');return document.MM_returnValue" value="{BUTTON_ADD_DB}"/>
							<!-- EDP: create_db -->
						</td>
					</tr>
					<tr>
						<td><label for="sql_user">{TR_SQL_USER}<label</td>
						<td>
							<!-- BDP: select_installdbuser -->
							<select name="sql_user" id="sql_user">
								<!-- BDP: installdbuser_item -->
								<option {SELECTED_DBUSER} value="{SQLUSER_NAME}">{SQLUSER_NAME}</option>
								<!-- EDP: installdbuser_item -->
							</select>
							<!-- EDP: select_installdbuser -->

							<!-- BDP: create_message_db -->
							<span style="color:#ff0000">{ADD_DATABASE_MESSAGE}</span>
							<!-- EDP: create_message_db -->
							<!-- BDP: softwaredbuser_message -->
							<span style="color:{STATUS_COLOR}">{SQLUSER_STATUS_MESSAGE}</span>
							<!-- EDP: softwaredbuser_message -->
						</td>
					</tr>
				</table>
				<!-- EDP: require_installdb -->

				<table class="firstColFixed">
					<tr>
						<th colspan="2">{TR_INSTALLATION_INFORMATION}</th>
					</tr>
					<tr>
						<td><label for="install_username">{TR_INSTALL_USER}</label></td>
						<td><input type="text" name="install_username" id="install_username" value="{VAL_INSTALL_USERNAME}"/></td>
					</tr>
					<tr>
						<td><label for="install_password">{TR_INSTALL_PWD}</label></td>
						<td><input type="password" name="install_password" id="install_password" value="{VAL_INSTALL_PASSWORD}"/></td>
					</tr>
					<tr>
						<td><label for="install_email">{TR_INSTALL_EMAIL}</label></td>
						<td><input type="text" name="install_email" id="install_email" value="{VAL_INSTALL_EMAIL}"/></td>
					</tr>
					<!-- EDP: software_item -->
				</table>
				<div class="buttons">
					<input name="Submit" type="submit" class="button" onClick="MM_goToURL('parent','software.php');return document.MM_returnValue" value="{TR_BACK}"/>
					<!-- BDP: software_install -->
					<input name="Submit2" type="submit" class="button" value="{TR_INSTALL}"/>
					<!-- EDP: software_install -->
				</div>
			</form>
