
<!-- BDP: mail_feature -->
<script type="text/javascript">
	/* <![CDATA[ */
	$(document).ready(function () {
		var oTable = $('.datatable').dataTable(
			{
				"oLanguage": {DATATABLE_TRANSLATIONS},
				"iDisplayLength": 5,
				"bStateSave": true,
				"aoColumnDefs": [ { "bSortable": false, "aTargets": [ 5 ] } ]
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

		$("input[type=submit]").click(function() {
			var items = $(":checkbox:checked", oTable.fnGetNodes());

			if(items.length > 0) {
				if(confirm("{TR_MESSAGE_DELETE_SELECTED_ITEMS}")) {
					return true;
				}
			} else {
				alert("{TR_MESSAGE_DELETE_SELECTED_ITEMS_ERR}");
			}

			return false;
		});
	});

	function action_delete(subject) {
		return confirm(sprintf("{TR_MESSAGE_DELETE}", subject));
	}
	/* ]]> */
</script>

<!-- BDP: mail_items -->
<form action="mail_delete.php" method="post">
	<table class="datatable">
		<thead>
		<tr>
			<th>{TR_MAIL}</th>
			<th>{TR_TYPE}</th>
			<th>{TR_STATUS}</th>
			<th>{TR_QUOTA}</th>
			<th>{TR_ACTIONS}</th>
			<th style="width:21px"><label><input type="checkbox" /></label></th>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<td colspan="5">{TOTAL_MAIL_ACCOUNTS}</td>
			<td style="width:21px"><label><input type="checkbox"/></label></td>
		</tr>
		</tfoot>
		<tbody>
		<!-- BDP: mail_item -->
		<tr>
			<td>
				<span class="icon i_mail_icon">{MAIL_ADDR}</span>
				<!-- BDP: auto_respond_item -->
				<div>
					{TR_AUTORESPOND}:
					<a href="{AUTO_RESPOND_SCRIPT}" class="icon i_reload">{AUTO_RESPOND}</a>
					<!-- BDP: auto_respond_edit_link -->
					<a href="{AUTO_RESPOND_EDIT_SCRIPT}" class="icon i_edit">{AUTO_RESPOND_EDIT}</a>
					<!-- EDP: auto_respond_edit_link -->
				</div>
				<!-- EDP: auto_respond_item -->
			</td>
			<td>{MAIL_TYPE}</td>
			<td>{MAIL_STATUS}</td>
			<td>{MAIL_QUOTA_VALUE}</td>
			<td>
				<a href="{MAIL_EDIT_SCRIPT}" title="{MAIL_EDIT}" class="icon i_edit">{MAIL_EDIT}</a>
				<a href="{MAIL_DELETE_SCRIPT}" onclick="return action_delete('{MAIL_ADDR}')" title="{MAIL_DELETE}"
				   class="icon i_delete">{MAIL_DELETE}</a>
			</td>
			<td><label><input type="checkbox" name="id[]" value="{DEL_ITEM}"{DISABLED_DEL_ITEM} /></label></td>
		</tr>
		<!-- EDP: mail_item -->
		</tbody>
	</table>
	<div class="buttons">
		<input type="submit" name="Submit" value="{TR_DELETE_SELECTED_ITEMS}"/>
	</div>
</form>
<!-- EDP: mail_items -->
<!-- EDP: mail_feature -->
