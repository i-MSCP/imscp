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
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<!-- BDP: logged_from -->
				<li>
					<a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a>
				</li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="users.php">{TR_MENU_MANAGE_USERS}</a></li>
				<li><a href="settings_welcome_mail.php">{TR_MENU_E_MAIL_SETUP}</a>
				</li>
			</ul>
		</div>
		<div class="left_menu">
			{MENU}
		</div>
		<div class="body">
			<h2 class="email"><span>{TR_EMAIL_SETUP}</span></h2>
			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->
			<form name="admin_email_setup" method="post" action="settings_welcome_mail.php">
				<fieldset>
					<legend>{TR_MESSAGE_TEMPLATE_INFO}</legend>
					<table>
						<tr>
							<td style="width:300px;">{TR_USER_LOGIN_NAME}</td>
							<td>{USERNAME}</td>
						</tr>
						<tr>
							<td>{TR_USER_PASSWORD}</td>
							<td>{PASSWORD}</td>
						</tr>
						<tr>
							<td>{TR_USER_REAL_NAME}</td>
							<td>{NAME}</td>
						</tr>
						<tr>
							<td>{TR_USERTYPE}</td>
							<td>{USERTYPE}</td>
						</tr>
						<tr>
							<td>{TR_BASE_SERVER_VHOST}</td>
							<td>{BASE_SERVER_VHOST}</td>
						</tr>
						<tr>
							<td>{TR_BASE_SERVER_VHOST_PREFIX}</td>
							<td>{BASE_SERVER_VHOST_PREFIX}</td>
						</tr>
					</table>
				</fieldset>
				<fieldset>
					<legend>{TR_MESSAGE_TEMPLATE}</legend>
					<table>
						<tr>
							<td style="width:300px;"><label for="auto_subject">{TR_SUBJECT}</label></td>
							<td>
								<input type="text" id="auto_subject" name="auto_subject" value="{SUBJECT_VALUE}" />
							</td>
						</tr>
						<tr>
							<td><label for="auto_message">{TR_MESSAGE}</label></td>
							<td>
								<textarea id="auto_message" name="auto_message" cols="80" rows="20">{MESSAGE_VALUE}</textarea>
							</td>
						</tr>
						<tr>
							<td>{TR_SENDER_EMAIL}</td>
							<td>{SENDER_EMAIL_VALUE}</td>
						</tr>
						<tr>
							<td>{TR_SENDER_NAME}</td>
							<td>{SENDER_NAME_VALUE}</td>
						</tr>
					</table>
				</fieldset>
				<div class="buttons">
					<input name="Submit" type="submit" value="{TR_APPLY_CHANGES}" />
				</div>
				<input type="hidden" name="uaction" value="email_setup" />
			</form>
		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
