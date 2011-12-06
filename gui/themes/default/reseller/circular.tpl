<!-- INCLUDE "../shared/layout/header.tpl" -->
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
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="users.php">{TR_MENU_MANAGE_USERS}</a></li>
				<li><a href="circular.php">{TR_MENU_CIRCULAR}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<h2 class="email"><span>{TR_CIRCULAR}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<form name="admin_email_setup" method="post" action="circular.php">
				<table>
					<tr>
						<td style="width:300px;">
							<label for="sender_email">{TR_SENDER_EMAIL}</label></td>
						<td>
							<input id="sender_email" type="text" name="sender_email" value="{SENDER_EMAIL}" />
						</td>
					</tr>
					<tr>
						<td><label for="sender_name">{TR_SENDER_NAME}</label></td>
						<td>
							<input id="sender_name" type="text" name="sender_name" value="{SENDER_NAME}" />
						</td>
					</tr>
					<tr>
						<td><label for="msg_subject">{TR_MESSAGE_SUBJECT}</label>
						</td>
						<td>
							<input id="msg_subject" type="text" name="msg_subject" value="{MESSAGE_SUBJECT}" />
						</td>
					</tr>
					<tr>
						<td><label for="msg_text">{TR_MESSAGE_TEXT}</label></td>
						<td>
							<textarea id="msg_text" name="msg_text" cols="80" rows="20">{MESSAGE_TEXT}</textarea>
						</td>
					</tr>
				</table>
				<div class="buttons">
					<input name="Submit" type="submit" value="{TR_SEND_MESSAGE}" />
				</div>
				.
				<input type="hidden" name="uaction" value="send_circular" />
			</form>
		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
