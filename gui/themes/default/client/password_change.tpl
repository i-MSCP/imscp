<!-- INCLUDE "../shared/layout/header.tpl" -->
		<div class="header">
			{MAIN_MENU}

			<div class="logo">
				<img src="{ISP_LOGO}" alt="i-MSCP logo" />
			</div>
		</div>

		<div class="location">
			<div class="location-area">
				<h1 class="general">{TR_MENU_GENERAL_INFORMATION}</h1>
			</div>
			<ul class="location-menu">
				<!-- BDP: logged_from -->
				<li><a class="logout" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="index.php">{TR_MENU_GENERAL_INFORMATION}</a></li>
				<li><a href="#" onclick="return false;">{TR_LMENU_CHANGE_PASSWORD}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<h2 class="password"><span>{TR_TITLE_CHANGE_PASSWORD}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<form name="client_change_pass_frm" method="post" action="password_change.php">
				<table>
					<tr>
						<th colspan="2">{TR_PASSWORD}</th>
					</tr>
					<tr>
						<td style="width: 300px;"><label for="curr_pass">{TR_CURR_PASSWORD}</label></td>
						<td><input id="curr_pass" name="curr_pass" type="password" value="" /></td>
					</tr><tr>
						<td><label for="pass">{TR_PASSWORD}</label></td>
						<td><input name="pass" id="pass" type="password" value="" /></td>
					</tr><tr>
						<td><label for="pass_rep">{TR_PASSWORD_REPEAT}</label></td>
						<td><input name="pass_rep" id="pass_rep" type="password" value="" /></td>
					</tr>
				</table>
				<div class="buttons">
					<input type="hidden" name="uaction" value="updt_pass" />
					<input type="submit" name="Submit" value="{TR_UPDATE_PASSWORD}" />
				</div>
			</form>
		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
