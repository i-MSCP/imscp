<!-- INCLUDE "../shared/layout/header.tpl" -->
        <div class="header">
            {MAIN_MENU}

            <div class="logo">
                <img src="{ISP_LOGO}" alt="i-MSCP logo" />
            </div>
        </div>
        <div class="location">
            <div class="location-area">
                <h1 class="settings">{TR_MENU_SETTINGS}</h1>
            </div>
            <ul class="location-menu">
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
            </ul>
            <ul class="path">
				<li><a href="settings.php">{TR_GENERAL_SETTINGS}</a></li>
                <li><a href="ip_manage.php">{TR_EMAIL_SETUP}</a></li>
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

            <!-- BDP: tickets_list -->
			<form name="admin_email_setup" method="post" action="settings_welcome_mail.php">
				<fieldset>
					<legend>{TR_MESSAGE_TEMPLATE_INFO}</legend>
					<table>
						<tr>
							<td style="width:300px;"><strong>{TR_USER_LOGIN_NAME}</strong></td>
							<td>{USERNAME}</td>
						</tr>
						<tr>
							<td><strong>{TR_USER_PASSWORD}</strong></td>
							<td>{PASSWORD}</td>
						</tr>
						<tr>
							<td><strong>{TR_USER_REAL_NAME}</strong></td>
							<td>{NAME}</td>
						</tr>
						<tr>
							<td><strong>{TR_USERTYPE}</strong></td>
							<td>{USERTYPE}</td>
						</tr>
						<tr>
							<td><strong>{TR_BASE_SERVER_VHOST}</strong></td>
							<td>{BASE_SERVER_VHOST}</td>
						</tr>
						<tr>
							<td><strong>{TR_BASE_SERVER_VHOST_PREFIX}</strong></td>
							<td>{BASE_SERVER_VHOST_PREFIX}</td>
						</tr>
					</table>
				</fieldset>
				<fieldset>
					<legend>{TR_MESSAGE_TEMPLATE}</legend>
					<table>
						<tr>
							<td style="width:300px;">
								<label for="auto_subject"><strong>{TR_SUBJECT}</strong></label>
							</td>
							<td><input type="text" name="auto_subject" id="auto_subject" value="{SUBJECT_VALUE}" style="width:80%"/>
							</td>
						</tr>
						<tr>
							<td><label for="auto_message"<strong>{TR_MESSAGE}</strong></label></td>
							<td>
								<textarea name="auto_message" id="auto_message" style="width:80%" cols="80" rows="20">{MESSAGE_VALUE}</textarea>
							</td>
						</tr>
						<tr>
							<td><strong>{TR_SENDER_EMAIL}</strong></td>
							<td>{SENDER_EMAIL_VALUE}</td>
						</tr>
						<tr>
							<td><strong>{TR_SENDER_NAME}</strong></td>
							<td>{SENDER_NAME_VALUE}</td>
						</tr>
					</table>
				</fieldset>
				<div class="buttons">
					<input name="Submit" type="submit" value="{TR_APPLY_CHANGES}"/>
					<input type="hidden" name="uaction" value="email_setup"/>
				</div>
			</form>
        </div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
