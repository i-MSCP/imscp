
<script type="text/javascript">
/* <![CDATA[ */
function action_delete(url, domainname) {
	if (url.indexOf("delete")==-1) {
		location = url;
	} else {
		if (!confirm(sprintf("{TR_MESSAGE_DELETE}", domainname)))
			return false;
		location = url;
	}
}
/* ]]> */
</script>
	<!-- BDP: relay_message -->
	<div class="info">{RELAY_MSG}</div>
	<!-- EDP: relay_message -->

	<table>
		<thead>
			<tr>
				<th>{TR_DOMAIN}</th>
                <th>{TR_RELAY_ACTIVE}</th>
				<th>{TR_STATUS}</th>
				<th>{TR_ACTION}</th>
			</tr>
		</thead>
		<tbody>
			<!-- BDP: relay_item -->
			<tr>
				<td>{RELAY_DOMAIN}</td>
                <td>{RELAY_ACTIVE}</td>
                <td>{RELAY_STATUS}</td>
				<td>
                    <!-- BDP: relay_item_new --><a href="#" class="icon i_users" onclick="action_delete('{RELAY_CREATE_ACTION_SCRIPT}', '{RELAY_DOMAIN}')">{RELAY_CREATE_ACTION}</a><!-- EDP: relay_item_new -->
                    <!-- BDP: relay_item_edit --><a href="#" class="icon i_edit" onclick="action_delete('{RELAY_EDIT_ACTION_SCRIPT}', '{RELAY_DOMAIN}')">{RELAY_EDIT_ACTION}</a><!-- EDP: relay_item_edit -->
                    <!-- BDP: relay_item_delete --><a href="#" class="icon i_delete" onclick="action_delete('{RELAY_DELETE_ACTION_SCRIPT}', '{RELAY_DOMAIN}')">{RELAY_DELETE_ACTION}</a><!-- EDP: relay_item_delete -->
                </td>
			</tr>
			<!-- EDP: relay_item -->
		</tbody>
	</table>
