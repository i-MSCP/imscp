
<!-- BDP: ticket -->
<table class="firstColFixed">
    <thead>
    <tr>
        <th colspan="2">{TR_TICKET_INFO}</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td><strong>{TR_TICKET_SUBJECT}</strong></td>
        <td>{TICKET_SUBJECT_VAL}</td>
    </tr>
    <tr>
        <td><strong>{TR_TICKET_URGENCY}</strong></td>
        <td>{TICKET_URGENCY_VAL}</td>
    </tr>
    </tbody>
</table>
<!-- BDP: ticket_message -->
<table>
    <thead>
    <tr>
        <th>{TR_TICKET_FROM}: {TICKET_FROM_VAL}</th>
        <th style="text-align: right">{TICKET_DATE_VAL}</th>
    </tr>
    </thead>
    <tbody>
    <tr class="{TICKET_CLASS}">
        <td colspan="2">{TICKET_CONTENT_VAL}</td>
    </tr>
    </tbody>
</table>
<!-- EDP: ticket_message -->
<!-- EDP: ticket -->
<h2 class="doc"><span>{TR_TICKET_NEW_REPLY}</span></h2>
<form name="ticketFrm" method="post" action="ticket_view.php?ticket_id={TICKET_ID_VAL}">
    <label><textarea name="user_message" style="height:250px"></textarea></label>
    <div class="buttons">
        <input name="button_reply" type="button" class="button" value="{TR_TICKET_REPLY}" onclick="return sbmt(document.forms[0], 'send_msg');">
        <input name="button_action" type="button" class="button" value="{TR_TICKET_ACTION}" onclick="return sbmt(document.forms[0],'{TICKET_ACTION_VAL}');">
        <input name="uaction" type="hidden" value="">
        <input name="subject" type="hidden" value="{TICKET_SUBJECT_VAL}">
        <input name="urgency" type="hidden" value="{TICKET_URGENCY_ID_VAL}">
    </div>
</form>
