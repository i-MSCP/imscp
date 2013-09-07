
<script type="text/javascript">
	/* <![CDATA[ */
	function action_delete(subject) {
		if (subject == '#__all__#') {
			return confirm("{TR_TICKETS_DELETE_ALL_MESSAGE}");
		} else {
			return confirm(sprintf("{TR_TICKETS_DELETE_MESSAGE}", subject));
		}
	}
	/* ]]> */
</script>

<!-- BDP: tickets_list -->
<table>
	<thead>
	<tr>
		<th>{TR_TICKET_STATUS}</th>
		<th>{TR_TICKET_FROM}</th>
		<th>{TR_TICKET_SUBJECT}</th>
		<th>{TR_TICKET_URGENCY}</th>
		<th>{TR_TICKET_LAST_ANSWER_DATE}</th>
		<th>{TR_TICKET_ACTION}</th>
	</tr>
	</thead>
	<tbody>
	<!-- BDP: tickets_item -->
	<tr>
		<td><strong>{TICKET_STATUS_VAL}</strong></td>
		<td>{TICKET_FROM_VAL}</td>
		<td>
			<a href="ticket_view.php?ticket_id={TICKET_ID_VAL}" class="icon i_document"
			   title="{TR_TICKET_READ_LINK}">{TICKET_SUBJECT_VAL}</a>
		</td>
		<td>{TICKET_URGENCY_VAL}</td>
		<td>{TICKET_LAST_DATE_VAL}</td>
		<td>
			<a href="ticket_delete.php?ticket_id={TICKET_ID_VAL}"
			   onclick="return action_delete('{TICKET_SUBJECT2_VAL}')"
			   class="icon i_delete" title="{TR_TICKET_DELETE_LINK}">{TR_TICKET_DELETE}</a>

			<a href="ticket_closed.php?ticket_id={TICKET_ID_VAL}" class="icon i_open"
			   title="{TR_TICKET_REOPEN_LINK}">{TR_TICKET_REOPEN}</a>
		</td>
	</tr>
	<!-- EDP: tickets_item -->
	<tr>
		<td colspan="6">
			<div class="buttons">
				<a href="'ticket_delete.php?delete=closed" onclick="return action_delete('#__all__#')">
					<button type="button">{TR_TICKET_DELETE_ALL}</button>
				</a>
			</div>
		</td>
	</tr>
	</tbody>
</table>

<div class="paginator">
	<!-- BDP: scroll_prev -->
	<a class="icon i_prev" href="ticket_system.php?psi={PREV_PSI}" title="{TR_PREVIOUS}">{TR_PREVIOUS}</a>
	<!-- EDP: scroll_prev -->
	<!-- BDP: scroll_prev_gray -->
	<span class="icon i_prev_gray"></span>
	<!-- EDP: scroll_prev_gray -->
	<!-- BDP: scroll_next_gray -->
	<span class="icon i_next_gray"></span>
	<!-- EDP: scroll_next_gray -->
	<!-- BDP: scroll_next -->
	<a class="icon i_next" href="ticket_system.php?psi={NEXT_PSI}" title="{TR_NEXT}">{TR_NEXT}</a>
	<!-- EDP: scroll_next -->
</div>
<!-- EDP: tickets_list -->
