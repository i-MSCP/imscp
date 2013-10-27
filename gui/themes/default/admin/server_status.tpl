<script type="text/javascript">
	/*<![CDATA[*/
	$(document).ready(function () {
		$('.datatable').dataTable(
			{
				"oLanguage": {DATATABLE_TRANSLATIONS},
				"iDisplayLength": 5,
				"bStateSave": true
			}
		);

		$.each(error_fields_ids, function () {
			$('#' + this).css({ 'border': '1px solid red', 'font-weight': 'bolder'});
		});

		$('input[name=submitForReset]').click(function () {
			$('input[name=uaction]').val('reset');
		});
	});
	/*]]>*/
</script>

<!-- BDP: props_list -->
<table class="datatable firstColFixed">
	<thead>
	<tr>
		<th>{TR_SERVICE}</th>
		<th>{TR_IP}</th>
		<th>{TR_PORT}</th>
		<th>{TR_STATUS}</th>
	</tr>
	</thead>
	<tbody>
	<!-- BDP: service_status -->
	<tr>
		<td class="{CLASS}">{SERVICE}</td>
		<td class="{CLASS}">{IP}</td>
		<td class="{CLASS}">{PORT}</td>
		<td class="{CLASS}">{STATUS}</td>
	</tr>
	<!-- EDP: service_status -->
	</tbody>
</table>
<!-- EDP: props_list -->
