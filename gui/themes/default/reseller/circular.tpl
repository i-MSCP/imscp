
			<form name="admin_email_setup" method="post" action="circular.php">
				<table class="firstColFixed">
					<tr>
						<th colspan="2">{TR_CIRCULAR}</th>
					</tr>
					<tr>
						<td><label for="sender_email">{TR_SENDER_EMAIL}</label></td>
						<td><input id="sender_email" type="text" name="sender_email" value="{SENDER_EMAIL}"/></td>
					</tr>
					<tr>
						<td><label for="sender_name">{TR_SENDER_NAME}</label></td>
						<td><input id="sender_name" type="text" name="sender_name" value="{SENDER_NAME}"/></td>
					</tr>
					<tr>
						<td><label for="msg_subject">{TR_MESSAGE_SUBJECT}</label></td>
						<td><input id="msg_subject" type="text" name="msg_subject" value="{MESSAGE_SUBJECT}" class="inputTitle"/></td>
					</tr>
					<tr>
						<td><label for="msg_text">{TR_MESSAGE_TEXT}</label></td>
						<td><textarea id="msg_text" name="msg_text">{MESSAGE_TEXT}</textarea></td>
					</tr>
				</table>
				<div class="buttons">
					<input type="hidden" name="uaction" value="send_circular"/>
					<input name="Submit" type="submit" value="{TR_SEND_MESSAGE}"/>
				</div>
			</form>
