
<form name="ticketFrm" method="post" action="ticket_create.php">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_NEW_TICKET}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td><label for="urgency">{TR_URGENCY}</label></td>
			<td>
				<select id="urgency" name="urgency">
					<option value="1"{OPT_URGENCY_1}>{TR_LOW}</option>
					<option value="2"{OPT_URGENCY_2}>{TR_MEDIUM}</option>
					<option value="3"{OPT_URGENCY_3}>{TR_HIGH}</option>
					<option value="4"{OPT_URGENCY_4}>{TR_VERY_HIGH}</option>
				</select>
			</td>
		</tr>
		<tr>
			<td><label for="subject">{TR_SUBJECT}</label></td>
			<td><input type="text" id="subject" name="subject" value="{SUBJECT}"/></td>
		</tr>
		<tr>
			<td><label for="user_message">{TR_YOUR_MESSAGE}</label></td>
			<td><textarea id="user_message" name="user_message">{USER_MESSAGE}</textarea></td>
		</tr>
		</tbody>
	</table>

	<div class="buttons">
		<input name="Submit" type="submit" value="{TR_SEND_MESSAGE}"/>
		<input name="uaction" type="hidden" value="send_msg"/>
	</div>
</form>
