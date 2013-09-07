
<form name="admin_delete_email" method="post" action="software_delete.php">
	<table class="firstColFixed">
		<thead>
		<tr>
			<td colspan="2">{TR_DELETE_DATA}</td>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td>{TR_DELETE_SEND_TO}</td>
			<td>{DELETE_SOFTWARE_RESELLER}</td>
		</tr>
		<tr>
			<td><label for="delete_msg_text">{TR_DELETE_MESSAGE_TEXT}</label></td>
			<td><textarea name="delete_msg_text" id="delete_msg_text">{DELETE_MESSAGE_TEXT}</textarea></td>
		</tr>
		</tbody>
	</table>
	<div class="buttons">
		<input name="Submit" type="submit" value="{TR_DELETE}"/>
		<input type="hidden" name="uaction" value="send_delmessage"/>
		<input type="hidden" name="id" value="{SOFTWARE_ID}"/>
		<input type="hidden" name="reseller_id" value="{RESELLER_ID}"/>
	</div>
</form>
