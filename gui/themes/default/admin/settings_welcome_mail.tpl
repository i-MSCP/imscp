
<!-- BDP: tickets_list -->
<form name="admin_email_setup" method="post" action="settings_welcome_mail.php">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_MESSAGE_TEMPLATE_INFO}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td><strong>{TR_USER_LOGIN_NAME}</strong></td>
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
		</tbody>
	</table>
	<table>
		<thead>
		<tr>
			<th colspan="2">{TR_MESSAGE_TEMPLATE}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td><label for="auto_subject"><strong>{TR_SUBJECT}</strong></label></td>
			<td><input type="text" name="auto_subject" id="auto_subject" class="inputTitle" value="{SUBJECT_VALUE}"/>
			</td>
		</tr>
		<tr>
			<td><label for="auto_message"><strong>{TR_MESSAGE}</strong></label></td>
			<td><textarea name="auto_message" id="auto_message">{MESSAGE_VALUE}</textarea></td>
		</tr>
		<tr>
			<td><strong>{TR_SENDER_EMAIL}</strong></td>
			<td>{SENDER_EMAIL_VALUE}</td>
		</tr>
		<tr>
			<td><strong>{TR_SENDER_NAME}</strong></td>
			<td>{SENDER_NAME_VALUE}</td>
		</tr>
		</tbody>
	</table>
	<div class="buttons">
		<input name="Submit" type="submit" value="{TR_UPDATE}"/>
		<input type="hidden" name="uaction" value="email_setup"/>
	</div>
</form>
