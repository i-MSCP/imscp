
<!-- BDP: tickets_list -->
<table>
	<thead>
	<tr>
		<th colspan="2">{TR_TICKET_INFO}</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td>{TR_TICKET_URGENCY}</td>
		<td>{TICKET_URGENCY_VAL}</td>
	</tr>
	<tr>
		<td>{TR_TICKET_SUBJECT}</td>
		<td>{TICKET_SUBJECT_VAL}</td>
	</tr>
	<!-- BDP: tickets_item -->
	<tr>
		<td>{TR_TICKET_FROM}</td>
		<td>{TICKET_FROM_VAL}</td>
	</tr>
	<tr>
		<td>{TR_TICKET_DATE}</td>
		<td>{TICKET_DATE_VAL}</td>
	</tr>
	<tr>
		<td><label for="message_body">{TR_TICKET_CONTENT}</label></td>
		<td><textarea name="message_body" id="message_body" readonly="readonly">{TICKET_CONTENT_VAL}</textarea></td>
	</tr>
	<!-- EDP: tickets_item -->
	</tbody>
</table>

<h2 class="doc"><span>{TR_TICKET_NEW_REPLY}</span></h2>

<form name="ticketFrm" method="post" action="ticket_view.php?ticket_id={TICKET_ID_VAL}">

	<label><textarea name="user_message"></textarea></label>

	<div class="buttons">
		<input name="button_reply" type="button" value="{TR_TICKET_REPLY}"
			   onclick="return sbmt(document.forms[0], 'send_msg');"/>
		<input name="button_action" type="button" value="{TR_TICKET_ACTION}"
			   onclick="return sbmt(document.forms[0], '{TICKET_ACTION_VAL}');"/>
	</div>
	<input name="uaction" type="hidden" value=""/>
	<input name="subject" type="hidden" value="{TICKET_SUBJECT_VAL}"/>
	<input name="urgency" type="hidden" value="{TICKET_URGENCY_ID_VAL}"/>
</form>
<!-- EDP: tickets_list -->
