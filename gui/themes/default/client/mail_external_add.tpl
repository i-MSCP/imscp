
<script type="text/javascript">
	/* <![CDATA[ */
	$(document).ready(function () {
		var i = $("tbody tr").length;
		$('.trigger_add').click(
			function () {
				var str_mx = '<tr>';
				str_mx += '	<td>';
				str_mx += '		<select name="name[]" id="name_' + i + '">';
				str_mx += '			<option value="{DOMAIN}">{TR_DOMAIN}</option>';
				str_mx += '			<option value="{WILDCARD}">{TR_WILDCARD}</option>';
				str_mx += '		</select>';
				str_mx += '	</td>';
				str_mx += '	<td>';
				str_mx += '		<select name="priority[]" id="priority_' + i + '">';
				str_mx += '			<option value="10" selected>10</option>';
				str_mx += '			<option value="15">15</option>';
				str_mx += '			<option value="20">20</option>';
				str_mx += '			<option value="25">25</option>';
				str_mx += '			<option value="30">30</option>';
				str_mx += '		</select>';
				str_mx += '	</td>';
				str_mx += '	<td>';
				str_mx += '		<label><input type="text" name="host[]" id="host_' + i + '" value="" /></label>';
				str_mx += '	</td>';
				str_mx += '</tr>';
				$("tbody").append(str_mx);
				i++;
			}
		);
		$('.trigger_remove').click(function () {
			if (i > 1) {
				$("tbody tr:last").remove();
				i--;
			} else {
				alert('{TR_TRIGGER_REMOVE_ALERT}');
			}
		});
		$('.trigger_reset').click(function () {
			while (i > 1) {
				$("tbody tr:last").remove();
				i--;
			}
		});
	});
	/* ]]> */
</script>

<form name="add_external_mail_server" method="post" action="mail_external_add.php">
	<div>
		<a href="#" class="trigger_add">{TR_ADD_NEW_ENTRY}</a> | <a href="#"
																	class="trigger_remove">{TR_REMOVE_LAST_ENTRY}</a> |
		<a href="#" class="trigger_reset">{TR_RESET_ENTRIES}</a>
	</div>
	<table>
		<thead>
		<tr>
			<th>{TR_MX_TYPE} <a href="#" class="icon i_help" title="{TR_MX_TYPE_TOOLTIP}"></a></th>
			<th>{TR_PRIORITY}</th>
			<th>{TR_HOST}</th>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<td>{TR_MX_TYPE} <a href="#" class="icon i_help" title="{TR_MX_TYPE_TOOLTIP}"></a></td>
			<td>{TR_PRIORITY}</td>
			<td>{TR_HOST}</td>
		</tr>
		</tfoot>
		<tbody>
		<!-- BDP: item_entries -->
		<tr>
			<td>
				<label>
					<select name="name[{INDEX}]" id="name_{INDEX}">
						<!-- BDP: name_options -->
						<option value="{OPTION_VALUE}"{SELECTED}>{OPTION_NAME}</option>
						<!-- EDP: name_options -->
					</select>
				</label>
			</td>
			<td>
				<label>
					<select name="priority[{INDEX}]" id="priority_{INDEX}">
						<!-- BDP: priority_options -->
						<option value="{OPTION_VALUE}"{SELECTED}>{OPTION_NAME}</option>
						<!-- EDP: priority_options -->
					</select>
				</label>
			</td>
			<td>
				<label><input type="text" name="host[{INDEX}]" id="host_{INDEX}" value="{HOST}"/></label>
			</td>
		</tr>
		<!-- EDP: item_entries -->
		</tbody>
	</table>

	<div style="float:left;">
		<a href="#" class="trigger_add">{TR_ADD_NEW_ENTRY}</a> | <a href="#"
																	class="trigger_remove">{TR_REMOVE_LAST_ENTRY}</a> |
		<a href="#" class="trigger_reset">{TR_RESET_ENTRIES}</a>
	</div>

	<div class="buttons">
		<input type="hidden" name="item" value="{ITEM}"/>
		<input name="submit" type="submit" value="{TR_ADD}"/>
		<a class ="link_as_button" href="mail_external.php">{TR_CANCEL}</a>
	</div>
</form>
