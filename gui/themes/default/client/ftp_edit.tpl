<!-- INCLUDE "../shared/layout/header.tpl" -->
<body>
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
			<li><a href="ftp_accounts.php">{TR_LMENU_OVERVIEW}</a></li>
			<li><a href="#" onclick="return false;">{TR_TITLE_EDIT_FTP_USER}</a></li>
		</ul>
	</div>

	<div class="left_menu">
		{MENU}
	</div>

	<div class="body">
		<h2 class="ftp"><span>{TR_TITLE_EDIT_FTP_USER}</span></h2>

		<!-- BDP: page_message -->
		<div class="{MESSAGE_CLS}">{MESSAGE}</div>
		<!-- EDP: page_message -->

		<form name="editFrm" method="post" action="ftp_edit.php">
			<table>
				<tr>
					<th colspan="2">
						{TR_FTP_USER_DATA}
					</th>
				</tr>
				<tr>
					<td><label for="ftp_account">{TR_FTP_ACCOUNT}</label></td>
					<td>
						<input id="ftp_account" type="text" name="username" value="{FTP_ACCOUNT}" readonly="readonly"/>
					</td>
				</tr>
				<tr>
					<td><label for="pass">{TR_PASSWORD}</label></td>
					<td><input id="pass" type="password" name="pass" value=""/></td>
				</tr>
				<tr>
					<td><label for="pass_rep">{TR_PASSWORD_REPEAT}</label></td>
					<td><input id="pass_rep" type="password" name="pass_rep" value=""/></td>
				</tr>
				<tr>
					<td><input id="use_other_dir" type="checkbox" name="use_other_dir" {USE_OTHER_DIR_CHECKED} />
						<label for="use_other_dir">{TR_USE_OTHER_DIR}</label></td>
					<td>
						<input type="text" name="other_dir" value="{OTHER_DIR}"/>
						<a href="#" onclick="showFileTree();" class="icon i_bc_folder">{CHOOSE_DIR}</a>
					</td>
				</tr>
			</table>
			<div class="buttons">
				<input type="hidden" name="uaction" value="edit_user"/>
				<input type="hidden" name="id" value="{ID}"/>
				<input name="submit" type="submit" value="{TR_CHANGE}"/>
			</div>
		</form>
</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
