
			<form action="settings_lostpassword.php" method="post" name="frmlostpassword" id="frmlostpassword">
				<table class="firstColFixed">
					<tr>
						<th colspan="4">{TR_MESSAGE_TEMPLATE_INFO}</th>
					</tr>

					<tr>
						<td colspan="2">{TR_ACTIVATION_EMAIL}</td>
						<td colspan="2">{TR_PASSWORD_EMAIL}</td>
					</tr>
					<tr>
						<td><strong>{TR_USER_LOGIN_NAME}</strong></td>
						<td>{USERNAME}</td>
						<td><strong>{TR_USER_LOGIN_NAME}</strong></td>
						<td>{USERNAME}</td>
					</tr>
					<tr>
						<td><strong>{TR_LOSTPW_LINK}</strong></td>
						<td>{LINK}</td>
						<td><strong>{TR_USER_PASSWORD}</strong></td>
						<td>{PASSWORD}</td>
					</tr>
					<tr>
						<td><strong>{TR_USER_REAL_NAME}</strong></td>
						<td>{NAME}</td>
						<td><strong>{TR_USER_REAL_NAME}</strong></td>
						<td>{NAME}</td>
					</tr>
					<tr>
						<td><strong>{TR_BASE_SERVER_VHOST}</strong></td>
						<td>{BASE_SERVER_VHOST}</td>
						<td><strong>{TR_BASE_SERVER_VHOST}</strong></td>
						<td>{BASE_SERVER_VHOST}</td>
					</tr>
					<tr>
						<td><strong>{TR_BASE_SERVER_VHOST_PREFIX}</strong></td>
						<td>{BASE_SERVER_VHOST_PREFIX}</td>
						<td><strong>{TR_BASE_SERVER_VHOST_PREFIX}</strong></td>
						<td>{BASE_SERVER_VHOST_PREFIX}</td>
					</tr>
				</table>
				<table>
					<tr>
						<th colspan="3">{TR_MESSAGE_TEMPLATE}</th>
					</tr>
					<tr>
						<td><strong>{TR_SUBJECT}</strong></td>
						<td><input name="subject1" type="text" id="subject1" class="inputTitle" value="{SUBJECT_VALUE1}"/></td>
						<td><input type="text" name="subject2" class="inputTitle" value="{SUBJECT_VALUE2}"/></td>
					</tr>
					<tr>
						<td><strong>{TR_MESSAGE}</strong></td>
						<td><textarea name="message1" id="message1">{MESSAGE_VALUE1}</textarea></td>
						<td><textarea name="message2" id="message2">{MESSAGE_VALUE2}</textarea></td>
					</tr>
					<tr style="display:none;">
						<td><strong>{TR_SENDER_EMAIL}</strong></td>
						<td>{SENDER_EMAIL_VALUE}</td>
						<td><input type="hidden" name="sender_email" value="{SENDER_EMAIL_VALUE}"/></td>
					</tr>
					<tr>
						<td><strong>{TR_SENDER_NAME}</strong></td>
						<td>{SENDER_NAME_VALUE}</td>
						<td><input type="hidden" name="sender_name" value="{SENDER_NAME_VALUE}"/></td>
					</tr>
				</table>
				<div class="buttons">
					<input name="Submit" type="submit" value="{TR_APPLY_CHANGES}"/>
					<input type="hidden" name="uaction" value="apply"/>
				</div>
			</form>
