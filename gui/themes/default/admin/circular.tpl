<form name="admin_email_setup" method="post" action="circular.php">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_CORE_DATA}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td><label for="rcpt_to">{TR_SEND_TO}</label></td>
			<td>
				<select id="rcpt_to" name="rcpt_to">
					<option value="all_resellers">{TR_ALL_RESELLERS}</option>
					<!-- BDP: all_customers -->
					<option value="all_users">{TR_ALL_CUSTOMERS}</option>
					<!-- EDP: all_customers -->
					<!-- BDP: all_resellers_and_customers -->
					<option value="all_resellers_and_users">{TR_ALL_RESELLERS_AND_CUSTOMERS}</option>
					<!-- EDP: all_resellers_and_customers -->
				</select>
			</td>
		</tr>
		<tr>
			<td><label for="msg_subject">{TR_MESSAGE_SUBJECT}</label></td>
			<td>
				<input class="inputTitle" type="text" name="msg_subject" id="msg_subject" value="{MESSAGE_SUBJECT}"/>
			</td>
		</tr>
		<tr>
			<td style="vertical-align: top"><label for="msg_text">{TR_MESSAGE_TEXT}</label></td>
			<td><textarea name="msg_text" id="msg_text">{MESSAGE_TEXT}</textarea></td>
		</tr>
		</tbody>
	</table>
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_ADDITIONAL_DATA}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td><label for="sender_email">{TR_SENDER_EMAIL}</label></td>
			<td><input type="text" name="sender_email" id="sender_email" value="{SENDER_EMAIL}"/></td>
		</tr>
		<tr>
			<td><label for="sender_name">{TR_SENDER_NAME}</label></td>
			<td><input type="text" name="sender_name" id="sender_name" value="{SENDER_NAME}"/></td>
		</tr>
		</tbody>
	</table>
	<div class="buttons">
		<input name="submit" type="submit" value="{TR_SEND_MESSAGE}"/>
		<input type="hidden" name="uaction" value="send_circular"/>
	</div>
</form>
