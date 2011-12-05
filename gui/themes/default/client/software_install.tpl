<!-- INCLUDE "../shared/layout/header.tpl" -->
		<script language="JavaScript" type="text/JavaScript">
		/*<![CDATA[*/
			function OpenTree() {
				libwindow=window.open("ftp_choose_dir.php","Hello","menubar=no,width=470,height=350,scrollbars=yes");

			}
			function set_installpath() {
				var inputvars = document.forms[0].elements['selected_domain'].value;
				inputvars = inputvars.toLowerCase();
				var splitinputvars = inputvars.split(";");
				document.forms[0].elements['other_dir'].value = splitinputvars[4];
			}
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
				<h1 class="webtools">{TR_MENU_WEBTOOLS}</h1>
			</div>
			<ul class="location-menu">
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="software.php">{TR_SOFTWARE_MENU_PATH}</a></li>
				<li><a href="software_install.php?id={SOFTWARE_ID}">{TR_INSTALL_SOFTWARE}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>


		<div class="body">
			<h2 class="apps_installer"><span>{TR_INSTALL_SOFTWARE}</span></h2>
		
			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->
			

			<table>
				<tr>
					<td>
						<form method="post" action="{SOFTWARE_INSTALL_BUTTON}">
							<table>
								<tr>
									<th colspan="2">{TR_INSTALLATION}</th>
								</tr>
                    			<!-- BDP: software_item -->
                    			<tr>
									<td width="200">{TR_NAME}</td>
		      						<td>{TR_SOFTWARE_NAME}</td>
		    					</tr>
								<tr>
									<td width="200">{TR_TYPE}</td>
									<td>{SOFTWARE_TYPE}</td>
								</tr>
								<tr>
									<td width="200">{TR_DB}</td>
									<td>{SOFTWARE_DB}</td>
								</tr>
								<tr>
									<td width="200">{TR_SELECT_DOMAIN}</td>
									<td>
										<select name="selected_domain" id="selected_domain" onChange="set_installpath();">
											<option value="{DOMAINSTANDARD_NAME_VALUES}">{DOMAINSTANDARD_NAME}</option>
											<!-- BDP: show_domain_list -->
											<option {SELECTED_DOMAIN} value="{DOMAIN_NAME_VALUES}">{DOMAIN_NAME}</option>
											<!-- EDP: show_domain_list -->
										</select>
									</td>
								</tr>
								<tr>
									<td width="200">{TR_PATH}</td>
									<td>
										<input type="text" name="other_dir" value="{VAL_OTHER_DIR}" style="width:170px" />&nbsp;<a href="javascript:OpenTree();">{CHOOSE_DIR}</a>&nbsp;(<input type="checkbox" name="createdir" value="1"{CHECKED_CREATEDIR} />{CREATEDIR_MESSAGE})
									</td>
		    					</tr>
								<!-- BDP: require_installdb -->
		    					<tr>
									<td width="200">{TR_SELECT_DB}</td>
									<td>
										<!-- BDP: select_installdb -->
										<select name="selected_db" id="selected_db">
											<!-- BDP: installdb_item -->
											<option {SELECTED_DB} value="{DB_NAME}">{DB_NAME}</option>
											<!-- EDP: installdb_item -->
										</select> 
										<!-- EDP: select_installdb -->
										<!-- BDP: create_db -->
										<input name="Submit3" type="submit" class="button" onClick="MM_goToURL('parent','{ADD_DB_LINK}');return document.MM_returnValue" value="{BUTTON_ADD_DB}" /><!-- BDP: create_message_db -->&nbsp;<font color="#FF0000">{ADD_DATABASE_MESSAGE}</font><!-- EDP: create_message_db -->
										<!-- EDP: create_db -->
									</td>
								</tr>
								<tr>
									<td width="200">{TR_SQL_USER}</td>
									<td>
										<!-- BDP: select_installdbuser -->
										<select name="sql_user" id="sql_user">
											<!-- BDP: installdbuser_item -->
											<option {SELECTED_DBUSER} value="{SQLUSER_NAME}">{SQLUSER_NAME}</option>
											<!-- EDP: installdbuser_item -->
										</select> 
										<!-- EDP: select_installdbuser -->
										<!-- BDP: create_message_db -->
										<font color="#FF0000">{ADD_DATABASE_MESSAGE}</font>
										<!-- EDP: create_message_db -->
										<!-- BDP: softwaredbuser_message -->
										<font color="{STATUS_COLOR}">{SQLUSER_STATUS_MESSAGE}</font>
										<!-- EDP: softwaredbuser_message -->
									</td>
								</tr>
								<!-- EDP: require_installdb -->
								<tr>
								<th colspan="2">{TR_INSTALLATION_INFORMATION}</th>
								</tr>
								<tr>
									<td width="200">{TR_INSTALL_USER}</td>
									<td><input type="text" name="install_username" value="{VAL_INSTALL_USERNAME}" style="width:170px" /></td>
								</tr>
								<tr>
									<td width="200">{TR_INSTALL_PWD}</td>
									<td><input type="password" name="install_password" value="{VAL_INSTALL_PASSWORD}" style="width:170px" /></td>
		    					</tr>
								<tr>
									<td width="200">{TR_INSTALL_EMAIL}</td>
									<td>
										<input type="text" name="install_email" value="{VAL_INSTALL_EMAIL}" style="width:170px" />
									</td>
		    					</tr>
								<tr>
									<td colspan="2">
										<div class="buttons">
											<input name="Submit" type="submit" class="button" onClick="MM_goToURL('parent','software.php');return document.MM_returnValue" value="{TR_BACK}" />
											<!-- BDP: software_install -->
											<input name="Submit2" type="submit" class="button" value="{TR_INSTALL}" />
	                   						<!-- EDP: software_install -->
                   						</div>
									</td>
								</tr>
								<!-- EDP: software_item -->
							</table>
						</form>
					</td>
				</tr>
			</table>
		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
