
<form name="circular_frm" method="post" action="circular.php">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_CIRCULAR}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td><label for="sender_name">{TR_SENDER_NAME}</label></td>
			<td><input type="text" name="sender_name" id="sender_name" value="{SENDER_NAME}"/></td>
		</tr>
		<tr>
			<td><label for="sender_email">{TR_SENDER_EMAIL}</label></td>
			<td><input type="text" name="sender_email" id="sender_email" value="{SENDER_EMAIL}"/></td>
		</tr>
		<tr>
			<td><label for="subject">{TR_SUBJECT}</label></td>
			<td>
				<input class="inputTitle" type="text" name="subject" id="subject" value="{SUBJECT}"/>
			</td>
		</tr>
		<tr>
			<td><label for="body">{TR_BODY}</label></td>
			<td><textarea name="body" id=body">{BODY}</textarea></td>
		</tr>
		</tbody>
	</table>
	<div class="buttons">
		<input name="submit" type="submit" value="{TR_SEND_CIRCULAR}"/>
		<a class="link_as_button" href="users.php">{TR_CANCEL}</a>
	</div>
</form>
