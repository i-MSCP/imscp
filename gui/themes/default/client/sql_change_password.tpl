<!-- INCLUDE "../shared/layout/header.tpl" -->
		<div class="header">
			{MAIN_MENU}

			<div class="logo">
				<img src="{ISP_LOGO}" alt="i-MSCP logo" />
			</div>
		</div>

		<div class="location">
			<div class="location-area">
				<h1 class="database">{TR_MENU_MANAGE_SQL}</h1>
			</div>
			<ul class="location-menu">
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="sql_manage.php">{TR_LMENU_OVERVIEW}</a></li>
				<li><a href="#" onclick="return false;">{TR_CHANGE_SQL_USER_PASSWORD}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<h2 class="password"><span>{TR_CHANGE_SQL_USER_PASSWORD}</span></h2

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

>
			<form name="sql_change_password_frm" method="post" action="sql_change_password.php">
				<table>
					<tr>
						<td style="width: 300px;"><label for="user_name">{TR_USER_NAME}</label></td>
						<td><input id="user_name" type="text" name="user_name" value="{USER_NAME}" readonly="readonly" /></td>
					</tr>
					<tr>
						<td><label for="pass">{TR_PASS}</label></td>
						<td><input id="pass" type="password" name="pass" value="" /></td>
					</tr>
					<tr>
						<td><label for="pass_rep">{TR_PASS_REP}</label></td>
						<td><input id="pass_rep" type="password" name="pass_rep" value="" /></td>
					</tr>
				</table>
				<div class="buttons">
					<input name="Submit" type="submit" class="button" value="{TR_CHANGE}" />
				</div>
				<input type="hidden" name="uaction" value="change_pass" />
				<input type="hidden" name="id" value="{ID}" />
			</form>
		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
