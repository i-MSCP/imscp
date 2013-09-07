
<!-- BDP hosting_plans_js -->
<script type="text/javascript">
	/* <![CDATA[ */
	function action_delete(subject) {
		return confirm(sprintf("{TR_MESSAGE_DELETE}", subject));
	}
	/* ]]> */
</script>
<!-- EDP hosting_plans_js -->

<!-- BDP: hosting_plans -->
<table class="firstColFixed">
	<thead>
	<tr>
		<th>{TR_NUMBER}</th>
		<th>{TR_NAME}</th>
		<th>{TR_STATUS}</th>
		<th>{TR_ACTIONS}</th>
	</tr>
	</thead>
	<tbody>
	<!-- BDP: hosting_plan -->
	<tr>
		<td>{NUMBER}</td>
		<td>{NAME}</td>
		<td>{STATUS}</td>
		<td>
			<a href="hosting_plan_edit.php?id={ID}" class="icon i_edit">{TR_EDIT}</a>
			<a href="hosting_plan_delete.php?id={ID}" onclick="return action_delete('{NAME}')"
			   class="icon i_delete">{TR_DELETE}</a>
		</td>
	</tr>
	<!-- EDP: hosting_plan -->
	</tbody>
</table>
<!-- EDP: hosting_plans -->
