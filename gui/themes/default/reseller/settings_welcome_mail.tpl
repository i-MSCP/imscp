
<form name="admin_email_setup" method="post" action="settings_welcome_mail.php">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_MESSAGE_TEMPLATE_INFO}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td>{TR_USER_LOGIN_NAME}</td>
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
		</tbody>
	</table>

	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_MESSAGE_TEMPLATE}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td><label for="auto_subject">{TR_SUBJECT}</label></td>
			<td><input type="text" id="auto_subject" name="auto_subject" value="{SUBJECT_VALUE}" class="inputTitle"/>
			</td>
		</tr>
		<tr>
			<td><label for="auto_message">{TR_MESSAGE}</label></td>
			<td><textarea id="auto_message" name="auto_message">{MESSAGE_VALUE}</textarea></td>
		</tr>
		<tr>
			<td>{TR_SENDER_EMAIL}</td>
			<td>{SENDER_EMAIL_VALUE}</td>
		</tr>
		<tr>
			<td>{TR_SENDER_NAME}</td>
			<td>{SENDER_NAME_VALUE}</td>
		</tr>
		</tbody>
	</table>

	<div class="buttons">
		<input type="hidden" name="uaction" value="email_setup"/>
		<input name="Submit" type="submit" value="{TR_UPDATE}"/>
	</div>
</form>
