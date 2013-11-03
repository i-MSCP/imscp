
<script type="text/javascript">
/* <![CDATA[ */
$(document).ready(function () {
	var extMailTable = $("#ext_mail_table");
	var entries = extMailTable.find('.entry');
	var initialEntries = entries.clone();
	var type = '';
	extMailTable.on("change", "select[name=type\\[\\]]",function () {
		type = $(this).val();
		if (type == "filter" || type == "domain") {
			$(".entry select[name=type\\[\\]]").each(function () {
				if ($(this).val() != "wildcard") {
					$(this).find('option').each(function () {
						if ($(this).val() == type) {
							$(this).prop("selected", true);
						}
					});
				}
			});
		}
	}).trigger("change");
	$('.add').click(function () {
		var entry = entries.first().clone();
		entry.find("input[type=text]").val('');
		entry.find("select option[value='" + type + "']").prop('selected', true);
		entry.appendTo("#ext_mail_table tbody");
	});
	$(".remove").click(function () {
		var entries = $(".entry");
		var nbEl = entries.length;
		var item = entries.last();
		if (nbEl > 1) {
			item.remove();
			nbEl--;
		} else {
			alert("{TR_TRIGGER_REMOVE_ALERT}");
		}
	});
	$(".reset").click(function () {
		$(".entry").remove();
		initialEntries.clone().appendTo("#ext_mail_table tbody");
	});
});
/* ]]> */
</script>

<form name="add_external_mail_server" method="post" action="mail_external_add.php?item={ITEM}">
	<div>
		<span class="add clickable" title="{TR_ADD_NEW_ENTRY}">{TR_ADD_NEW_ENTRY}</span> |
		<span class="remove clickable" title="{TR_REMOVE_LAST_ENTRY}">{TR_REMOVE_LAST_ENTRY}</span> |
		<span class="reset clickable" title="{TR_RESET_ENTRIES}">{TR_RESET_ENTRIES}</span>
	</div>
	<table class="firstColFixed" id="ext_mail_table">
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
		<tr class="entry">
			<td>
				<label>
					<select name="type[]">
						<!-- BDP: type_options -->
						<option value="{OPTION_VALUE}"{SELECTED}>{OPTION_NAME}</option>
						<!-- EDP: type_options -->
					</select>
				</label>
			</td>
			<td>
				<label>
					<select name="priority[]">
						<!-- BDP: priority_options -->
						<option value="{OPTION_VALUE}"{SELECTED}>{OPTION_NAME}</option>
						<!-- EDP: priority_options -->
					</select>
				</label>
			</td>
			<td>
				<label><input type="text" name="host[]" value="{HOST}"/></label>
			</td>
		</tr>
		<!-- EDP: item_entries -->
		</tbody>
	</table>

	<div style="float: left;">
		<span class="add clickable" title="{TR_ADD_NEW_ENTRY}">{TR_ADD_NEW_ENTRY}</span> |
		<span class="remove clickable" title="{TR_REMOVE_LAST_ENTRY}">{TR_REMOVE_LAST_ENTRY}</span> |
		<span class="reset clickable" title="{TR_RESET_ENTRIES}">{TR_RESET_ENTRIES}</span>
	</div>

	<div class="buttons">
		<input name="submit" type="submit" value="{TR_ADD}"/>
		<a class="link_as_button" href="mail_external.php">{TR_CANCEL}</a>
	</div>
</form>
