
			<form name="orderEmailFrm" method="post" action="order_email.php">
				<table class="firstColFixed">
					<tr>
						<th colspan="2">{TR_MESSAGE_TEMPLATE_INFO}</th>
					</tr>
					<tr>
						<td>{TR_USER_DOMAIN}</td>
						<td>{DOMAIN}</td>
					</tr>
					<tr>
						<td>{TR_USER_REAL_NAME}</td>
						<td>{NAME}</td>
					</tr>
					<tr>
						<td>{TR_ACTIVATION_LINK}</td>
						<td>{ACTIVATION_LINK}</td>
					</tr>
				</table>

				<table class="firstColFixed">
					<tr>
						<th colspan="2">{TR_MESSAGE_TEMPLATE}</th>
					</tr>
					<tr>
						<td><label for="auto_subject">{TR_SUBJECT}</label></td>
						<td><input id="auto_subject" type="text" name="auto_subject" value="{SUBJECT_VALUE}" class="inputTitle"/></td>
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
				</table>
				<div class="buttons">
					<input type="hidden" name="uaction" value="order_email"/>
					<input name="submit" type="submit" value="{TR_APPLY_CHANGES}"/>
				</div>
			</form>
