
<script type="text/javascript">
	/* <![CDATA[ */
	function action(action, mailacc) {
		if (action == 'create') {
			return true;
		} else if(action == 'N/A') {
			return false;
		} else {
			return confirm(sprintf("{TR_MESSAGE_DELETE}", mailacc));
		}
	}

	$(document).ready(function () {
		$('.datatable').dataTable(
			{
				"oLanguage": {DATATABLE_TRANSLATIONS},
				"iDisplayLength": 5,
				"bStateSave": true
			}
		);
	});
	/* ]]> */
</script>

<table class="firstColFixed datatable">
	<thead>
	<tr>
		<th>{TR_DOMAIN}</th>
		<th>{TR_CATCHALL}</th>
		<th>{TR_STATUS}</th>
		<th>{TR_ACTION}</th>
	</tr>
	</thead>
	<tbody>
	<!-- BDP: catchall_item -->
	<tr>
		<td>{CATCHALL_DOMAIN}</td>
		<td>{CATCHALL_ACC}</td>
		<td>{TR_CATCHALL_STATUS}</td>
		<td>
			<a href="{CATCHALL_ACTION_SCRIPT}" class="icon i_users<!-- BDP: del_icon --> i_delete<!-- EDP: del_icon -->"
			   onclick="return action('{CATCHALL_ACTION}', '{CATCHALL_ACC}')">{TR_CATCHALL_ACTION}</a>
		</td>
	</tr>
	<!-- EDP: catchall_item -->
	</tbody>
</table>

<div class="buttons">
	<a href="mail_accounts.php" class="link_as_button">{TR_CANCEL}</a>
</div>
