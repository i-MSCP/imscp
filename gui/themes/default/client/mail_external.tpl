
<script type="text/javascript">
/* <![CDATA[ */
$(document).ready(function () {
	$('.datatable').dataTable(
		{
			"oLanguage": {DATATABLE_TRANSLATIONS},
			"iDisplayLength": 5,
			"bStateSave": true
		}
	);
	$(".dataTables_paginate").click(function () {
		if ($("tbody :checkbox:checked").length == $("tbody :checkbox:not(':disabled')").length) {
			$("thead :checkbox,tfoot :checkbox").prop('checked', true);
		} else {
			$("thead :checkbox,tfoot :checkbox").prop('checked', false);
		}
	});
	$("tbody").on("click", ":checkbox:not(':disabled')", function () {
		if ($("tbody :checkbox:checked").length == $("tbody :checkbox:not(':disabled')").length) {
			$("thead :checkbox,tfoot :checkbox").prop('checked', true);
		} else {
			$("thead :checkbox,tfoot :checkbox").prop('checked', false);
		}
	});
	$("thead :checkbox, tfoot :checkbox").click(function (e) {
		if ($("tbody :checkbox:not(':disabled')").length != 0) {
			$("table :checkbox:not(':disabled')").prop('checked', $(this).is(':checked'));
		} else {
			e.preventDefault();
		}
	});
});

function onclick_action(url, domain) {
	return (url.indexOf('delete') == -1 || confirm(sprintf("{TR_DEACTIVATE_MESSAGE}", domain)));
}
/* ]]> */
</script>

<form name="mail_external_delete" action="mail_external_delete.php" method="post">
	<table class="firstColFixed datatable">
		<thead>
		<tr>
			<th style="width:21px;"><label><input type="checkbox"/></label></th>
			<th>{TR_DOMAIN}</th>
			<th>{TR_STATUS}</th>
			<th>{TR_ACTION}</th>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<td style="width:21px;"><label><input type="checkbox"/></label></td>
			<td>{TR_DOMAIN}</td>
			<td>{TR_STATUS}</td>
			<td>{TR_ACTION}</td>
		</tr>
		</tfoot>
		<tbody>
		<!-- BDP: item -->
		<tr>
			<td><label><input type="checkbox" name="{ITEM_TYPE}[]" value="{ITEM_ID}"{DISABLED}/></label></td>
			<td>{DOMAIN}</td>
			<td>{STATUS}</td>
			<td>
				<!-- BDP: activate_link -->
				<a href="{ACTIVATE_URL}" class="icon i_users"
				   onclick="return onclick_action('{ACTIVATE_URL}', '');">{TR_ACTIVATE}</a>
				<!-- EDP: activate_link -->
				<!-- BDP: edit_link -->
				<a href="{EDIT_URL}" class="icon i_edit"
				   onclick="return onclick_action('{EDIT_URL}', '');">{TR_EDIT}</a>
				<!-- EDP: edit_link -->
				<!-- BDP: deactivate_link -->
				<a href="{DEACTIVATE_URL}" class="icon i_delete"
				   onclick="return onclick_action('{DEACTIVATE_URL}', '{DOMAIN}');">{TR_DEACTIVATE}</a>
				<!-- EDP: deactivate_link -->
			</td>
		</tr>
		<!-- EDP: item -->
		</tbody>
	</table>
	<div class=buttons>
		<input type="submit" name="submit" value="{TR_DEACTIVATE_SELECTED_ITEMS}"/>
		<a href="mail_accounts.php" class="link_as_button">{TR_CANCEL}</a>
	</div>
</form>
