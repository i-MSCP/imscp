<!-- INCLUDE "../shared/layout/header.tpl" -->
<body>
	<script type="text/javascript">
	/* <![CDATA[ */
		<!-- BDP: js_to_all_domain -->
		function begin_js() {
			document.forms[0].sub_id.disabled = true;
			document.forms[0].als_id.disabled = true;
			document.forms[0].username.focus();
		}

		function changeDom(wath) {
			if (wath == 'real') {
				document.forms[0].sub_id.disabled = true;
				document.forms[0].als_id.disabled = true;
			} else if (wath == 'subdom') {
				document.forms[0].sub_id.disabled = false;
				document.forms[0].als_id.disabled = true;
			} else {
				document.forms[0].sub_id.disabled = true;
				document.forms[0].als_id.disabled = false;
			}
		}
		<!-- EDP: js_to_all_domain -->

		<!-- BDP: js_not_domain -->
		function begin_js() {
			document.forms[0].username.focus();
		}
		<!-- EDP: js_not_domain -->

		<!-- BDP: js_to_subdomain -->
		function begin_js() {
			document.forms[0].sub_id.disabled = true;
			document.forms[0].username.focus();
		}

		function changeDom(wath) {
			if (wath == "real") {
				document.forms[0].sub_id.disabled = true;
			} else if (wath == "subdom") {
				document.forms[0].sub_id.disabled = false;
			} else {
				document.forms[0].sub_id.disabled = true;
			}
		}
		<!-- EDP: js_to_subdomain -->

		<!-- BDP: js_to_alias_domain -->
		function begin_js() {
			document.forms[0].als_id.disabled = true;
			document.forms[0].username.focus();
		}

		function changeDom(wath) {
			if (wath == "real") {
				document.forms[0].als_id.disabled = true;
			} else if (wath == "subdom") {
				document.forms[0].als_id.disabled = true;
			} else {
				document.forms[0].als_id.disabled = false;
			}
		}
		<!-- EDP: js_to_alias_domain -->

		$(window).load(function() {begin_js();});
	});
	/*]]>*/
	</script>
	<div class="header">
		{MAIN_MENU}
		<div class="logo">
			<img src="{ISP_LOGO}" alt="i-MSCP logo"/>
		</div>
	</div>

	<div class="location">
		<div class="location-area">
			<h1 class="ftp">{TR_MENU_FTP_ACCOUNTS}</h1>
		</div>
		<ul class="location-menu">
			<!-- BDP: logged_from -->
			<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
			<!-- EDP: logged_from -->
			<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
		</ul>
		<ul class="path">
			<li><a href="ftp_accounts.php">{TR_MENU_FTP_ACCOUNTS}</a></li>
			<li><a href="#" onclick="return false;">{TR_TITLE_ADD_FTP_USER}</a></li>
		</ul>
	</div>

	<div class="left_menu">
		{MENU}
	</div>

	<div class="body">
		<h2 class="ftp"><span>{TR_TITLE_ADD_FTP_USER}</span></h2>

		<!-- BDP: page_message -->
		<div class="{MESSAGE_CLS}">{MESSAGE}</div>
		<!-- EDP: page_message -->

		<form name="addFrm" method="post" action="ftp_add.php">
			<table>
				<tr>
					<th colspan="2">{TR_FTP_USER_DATA}</th>
				</tr>
				<tr>
					<td><label for="username">{TR_USERNAME}</label></td>
					<td><input id="username" type="text" name="username" value="{USERNAME}"/></td>
				</tr>
				<tr>
					<td><input type="radio" name="dmn_type" value="dmn" onfocus="changeDom('real');" {DMN_TYPE_CHECKED} />{TR_TO_MAIN_DOMAIN}</td>
					<td>{FTP_SEPARATOR}{DOMAIN_NAME}</td>
				</tr>
				<!-- BDP: to_alias_domain -->
				<tr>
					<td><input type="radio" name="dmn_type" value="als" onfocus="changeDom('alias');" {ALS_TYPE_CHECKED} />{TR_TO_DOMAIN_ALIAS}</td>
					<td>
						<select name="als_id">
							<!-- BDP: als_list -->
							<option value="{ALS_ID}" {ALS_SELECTED}>{FTP_SEPARATOR}{ALS_NAME}</option>
							<!-- EDP: als_list -->
						</select>
					</td>
				</tr>
				<!-- EDP: to_alias_domain -->
				<tr>
					<td><label for="pass">{TR_PASSWORD}</label></td>
					<td><input id="pass" type="password" name="pass" value=""/></td>
				</tr>
				<tr>
					<td><label for="pass_rep">{TR_PASSWORD_REPEAT}</label></td>
					<td><input id="pass_rep" type="password" name="pass_rep" value=""/></td>
				</tr>
				<tr>
					<td>
						<input id="use_other_dir" type="checkbox" name="use_other_dir" {USE_OTHER_DIR_CHECKED} />
						<label for="use_other_dir">{TR_USE_OTHER_DIR}</label></td>
					<td>
						<input type="text" name="other_dir" value="{OTHER_DIR}"/>
						<a href="#" onclick="showFileTree();" class="icon i_bc_folder">{CHOOSE_DIR}</a>
					</td>
				</tr>
			</table>
			<div class="buttons">
				<input type="hidden" name="uaction" value="add_user"/>
				<input name="submit" type="submit" value="{TR_ADD}"/>
			</div>
		</form>
	</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
