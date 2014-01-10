
<script type="text/javascript">
/* <![CDATA[ */
$(document).ready(function () {
	var extMailTable = $("#ext_mail_table");
	var entries = extMailTable.find('.entry');
	var initialEntries = entries.clone();
	var type = '';
	extMailTable.on("change", "select[name=type\\[\\]]", function () {
		type = $(this).val();
		if (type == "filter" || type == "domain") {
			$(".entry select[name=type\\[\\]]").each(function () {
				if ($(this).val() != "wildcard") {
					$(this).find("option").each(function () {
						if ($(this).val() == type) {
							$(this).prop("selected", true);
						}
					});
				}
			});
		}
	});
	extMailTable.find("thead :checkbox, tfoot :checkbox").click(function () {
		var checked = $(this).is(":checked");
		extMailTable.find(":checkbox:not(':disabled')").prop("checked", checked);
		if (checked && $('.entry').find(':checkbox:disabled').length == 0) alert('{TR_SELECT_ALL_ENTRIES_ALERT}');
	});
	entries.on("click", ":checkbox", function () {
		if (entries.find(":checkbox:checked:visible").length == entries.find(":checkbox:visible").length) {
			$("thead :checkbox,tfoot :checkbox").prop("checked", true);
			alert('{TR_SELECT_ALL_ENTRIES_ALERT}');
		} else {
			$("thead :checkbox,tfoot :checkbox").prop("checked", false);
		}
	});
	$(".add").click(function () {
		var entry = entries.first().clone();
		var indexNr = $('.entry').length;
		entry.find("[name='host[0]']").attr('name', 'host[' + indexNr + ']');
		entry.find("[name='priority[0]']").attr('name', 'priority[' + indexNr + ']');
		entry.find("input[type=text]").val('');
		entry.find("input[type=hidden]").remove();
		entry.find(":checkbox").prop("checked", false).prop("disabled", true);
		entry.find("select option[value='" + type + "']").prop("selected", true);
		entry.appendTo("#ext_mail_table tbody");
	});
	$(".remove").click(function () {
		var entries = $(".entry").filter(":visible");
		var nbEl = entries.length;
		var item = entries.last();
		var checkbox = item.find(":checkbox");
		if (nbEl > 1) {
			if (checkbox.is(":disabled")) {
				item.remove();
			} else {
				checkbox.prop("checked", true);
				item.hide();
			}
			nbEl--;
		} else {
			if (!checkbox.is(":checked")) checkbox.trigger("click");
		}
	});
	$(".reset").click(function () {
		$('.entry').remove();
		initialEntries.clone().appendTo("tbody");
		extMailTable.find(":checkbox").prop('checked', false);

	});
});
/* ]]> */
</script>

<form name="edit_external_mail_server" method="post" action="mail_external_edit.php?item={ITEM}">
	<div>
		<span class="add clickable" title="{TR_ADD_NEW_ENTRY}">{TR_ADD_NEW_ENTRY}</span> |
		<span class="remove clickable" title="{TR_REMOVE_LAST_ENTRY}">{TR_REMOVE_LAST_ENTRY}</span> |
		<span class="reset clickable" title="{TR_RESET_ENTRIES}">{TR_RESET_ENTRIES}</span>
	</div>
	<table id="ext_mail_table">
		<thead>
		<tr>
			<th style="width:21px;">
				<a href="#" title="{TR_SELECT_ALL_ENTRIES_MESSAGE}"><label><input type="checkbox"/></label></a>
			</th>
			<th>{TR_MX_TYPE} <a href="#" class="icon i_help" title="{TR_MX_TYPE_TOOLTIP}"></a></th>
			<th>{TR_PRIORITY}</th>
			<th>{TR_HOST}</th>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<td style="width:21px;">
				<a href="#" title="{TR_SELECT_ALL_ENTRIES_MESSAGE}"><label><input type="checkbox"/></label></a>
			</td>
			<td>{TR_MX_TYPE} <a href="#" class="icon i_help" title="{TR_MX_TYPE_TOOLTIP}"></a></td>
			<td>{TR_PRIORITY}</td>
			<td>{TR_HOST}</td>
		</tr>
		</tfoot>
		<tbody>
		<!-- BDP: item_entries -->
		<tr class="entry">
			<td>
				<a href="#" title="{TR_SELECT_ENTRY_MESSAGE}">
					<label><input type="checkbox" name="to_delete[{INDEX}]" value="{ENTRY_ID}"/></label>
				</a>
				<input type="hidden" name="to_update[{INDEX}]" value="{ENTRY_ID}"/>
			</td>
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
					<select name="priority[{INDEX}]">
						<!-- BDP: priority_options -->
						<option value="{OPTION_VALUE}"{SELECTED}>{OPTION_NAME}</option>
						<!-- EDP: priority_options -->
					</select>
				</label>
			</td>
			<td><label><input type="text" name="host[{INDEX}]" value="{HOST}"/></label></td>
		</tr>
		<!-- EDP: item_entries -->
		</tbody>
	</table>

	<div style="float:left;">
		<span class="add clickable" title="{TR_ADD_NEW_ENTRY}">{TR_ADD_NEW_ENTRY}</span> |
		<span class="remove clickable" title="{TR_REMOVE_LAST_ENTRY}">{TR_REMOVE_LAST_ENTRY}</span> |
		<span class="reset clickable" title="{TR_RESET_ENTRIES}">{TR_RESET_ENTRIES}</span>
	</div>

	<div class="buttons">
		<input name="submit" type="submit" value="{TR_UPDATE}"/>
		<a class="link_as_button" href="mail_external.php">{TR_CANCEL}</a>
	</div>
</form>
