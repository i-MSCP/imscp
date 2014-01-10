
<form name="lostPasswordEmailFrm" action="settings_lostpassword.php" method="post">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="4">{TR_MESSAGE_TEMPLATE_INFO}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td colspan="2"><span class="bold">{TR_ACTIVATION_EMAIL}</span></td>
			<td colspan="2"><span class="bold">{TR_PASSWORD_EMAIL}</span></td>
		</tr>
		<tr>
			<td>{TR_USER_LOGIN_NAME}</td>
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

	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="3">{TR_MESSAGE_TEMPLATE}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td>{TR_SENDER_EMAIL}</td>
			<td colspan="2">{SENDER_EMAIL_VALUE}</td>
		</tr>
		<tr>
			<td>{TR_SENDER_NAME}</td>
			<td colspan="2">{SENDER_NAME_VALUE}</td>
		</tr>
		<tr>
			<td>{TR_SUBJECT}</td>
			<td>
				<label>
					<input name="subject1" type="text" id="subject1" value="{SUBJECT_VALUE1}" class="inputTitle"/>
				</label>
			</td>
			<td>
				<label>
					<input type="text" name="subject2" value="{SUBJECT_VALUE2}" class="inputTitle"/>
				</label>
			</td>
		</tr>
		<tr>
			<td>{TR_MESSAGE}</td>
			<td><label><textarea name="message1" id="message1">{MESSAGE_VALUE1}</textarea></label></td>
			<td><label><textarea name="message2" id="message2">{MESSAGE_VALUE2}</textarea></label></td>
		</tr>
		</tbody>
	</table>

	<div class="buttons">
		<input type="hidden" name="sender_email" value="{SENDER_EMAIL_VALUE}"/>
		<input type="hidden" name="sender_name" value="{SENDER_NAME_VALUE}"/>
		<input type="hidden" name="uaction" value="apply"/>
		<input name="Submit" type="submit" value="{TR_UPDATE}"/>
		<a class="link_as_button" href="users.php">{TR_CANCEL}</a>
	</div>
</form>
