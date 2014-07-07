
<table class="datatable">
	<thead>
	<tr>
		<th>{TR_ALIAS_NAME}</th>
		<th>{TR_MOUNT_POINT}</th>
		<th>{TR_FORWARD_URL}</th>
		<th>{TR_CUSTOMER}</th>
		<th>{TR_STATUS}</th>
		<th>{TR_ACTIONS}</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td colspan="6" class="dataTables_empty">{TR_PROCESSING_DATA}</td>
	</tr>
	</tbody>
</table>

<!-- BDP: als_add_button -->
<div style="float:right;">
	<a class="link_as_button" href="alias_add.php">{TR_ADD_DOMAIN_ALIAS}</a>
</div>
<!-- EDP: als_add_button -->

<script>
	$(document).ready(function() {
		var oTable = $(".datatable").dataTable({
			oLanguage: {DATATABLE_TRANSLATIONS},
			iDisplayLength: 5,
			bProcessing: true,
			bServerSide: true,
			sAjaxSource: "/reseller/alias.php?action=get_table",
			bStateSave: true,
			aoColumnDefs: [
				{ bSortable: false, bSearchable: false, aTargets: [ 4 ] },
				{ bSortable: false, bSearchable: false, aTargets: [ 5 ] }
			],
			aoColumns: [
				{ mData: "alias_name" }, { mData: "alias_mount" }, { mData: "url_forward" }, { mData: "admin_name" },
				{ mData: "alias_status" }, { mData: "actions" }
			],
			fnServerData: function (sSource, aoData, fnCallback) {
				$.ajax( {
					dataType: "json",
					type: "GET",
					url: sSource,
					data: aoData,
					success: fnCallback,
					timeout: 5000
				}).done(function() {
					oTable.find("a").imscpTooltip({ extraClass: "tooltip_icon tooltip_notice" });
				});
			}
		});
	});

	function delete_alias(name) {
		return confirm(sprintf("{TR_MESSAGE_DELETE_ALIAS}", name));
	}

	function delete_alias_order(name) {
		return confirm(sprintf("{TR_MESSAGE_DELETE_ALIAS_ORDER}", name));
	}
</script>
