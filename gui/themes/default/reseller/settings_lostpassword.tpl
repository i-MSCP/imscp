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
				<li><a href="settings_lostpassword.php">{TR_MENU_LOSTPW_EMAIL}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<h2 class="email"><span>{TR_LOSTPW_EMAIL}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<form action="settings_lostpassword.php" method="post" name="frmlostpassword" id="frmlostpassword">
				<fieldset>
					<legend>{TR_MESSAGE_TEMPLATE_INFO}</legend>
					<table>
						<thead>
							<tr>
								<th colspan="2">{TR_ACTIVATION_EMAIL}</th>
								<th colspan="2">{TR_PASSWORD_EMAIL}</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td style="width:200px;">{TR_USER_LOGIN_NAME}</td>
								<td>{USERNAME}</td>
								<td>{TR_USER_LOGIN_NAME}</td>
								<td>{USERNAME}</td>
							</tr>
							<tr>
								<td>{TR_LOSTPW_LINK}</td>
								<td>{LINK}</td>
								<td>{TR_USER_PASSWORD}</td>
								<td>{PASSWORD}</td>
							</tr>
							<tr>
								<td>{TR_USER_REAL_NAME}</td>
								<td>{NAME}</td>
								<td>{TR_USER_REAL_NAME}</td>
								<td>{NAME}</td>
							</tr>
							<tr>
								<td>{TR_BASE_SERVER_VHOST}</td>
								<td>{BASE_SERVER_VHOST}</td>
								<td>{TR_BASE_SERVER_VHOST}</td>
								<td>{BASE_SERVER_VHOST}</td>
							</tr>
							<tr>
								<td>{TR_BASE_SERVER_VHOST_PREFIX}</td>
								<td>{BASE_SERVER_VHOST_PREFIX}</td>
								<td>{TR_BASE_SERVER_VHOST_PREFIX}</td>
								<td>{BASE_SERVER_VHOST_PREFIX}</td>
							</tr>
						</tbody>
					</table>
				</fieldset>
				<fieldset>
					<legend>{TR_MESSAGE_TEMPLATE}</legend>
					<table>
						<tr>
							<td style="width:200px;">{TR_SENDER_EMAIL}</td>
							<td colspan="2">{SENDER_EMAIL_VALUE}</td>
						</tr>
						<tr>
							<td>{TR_SENDER_NAME}</td>
							<td colspan="2">{SENDER_NAME_VALUE}</td>
						</tr>
						<tr>
							<td>{TR_SUBJECT}</td>
							<td><input name="subject1" type="text" id="subject1" value="{SUBJECT_VALUE1}" /></td>
							<td><input type="text" name="subject2" value="{SUBJECT_VALUE2}" /></td>
						</tr>
						<tr>
							<td>{TR_MESSAGE}</td>
							<td><textarea name="message1" cols="40" rows="20" id="message1">{MESSAGE_VALUE1}</textarea></td>
							<td><textarea name="message2" cols="40" rows="20" id="message2">{MESSAGE_VALUE2}</textarea></td>
						</tr>
					</table>
				</fieldset>
				<div class="buttons">
					<input name="Submit" type="submit" value="{TR_APPLY_CHANGES}" />
				</div>
				<input type="hidden" name="sender_email" value="{SENDER_EMAIL_VALUE}" />
				<input type="hidden" name="sender_name" value="{SENDER_NAME_VALUE}" />
				<input type="hidden" name="uaction" value="apply" />
			</form>

		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
