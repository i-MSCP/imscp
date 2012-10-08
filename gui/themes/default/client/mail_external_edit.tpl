<script type="text/javascript">
    /* <![CDATA[ */
    $(document).ready(function () {
        var i = $("tbody :checkbox").change(
                function () {
                    if ($(this).is(':checked') && $("tbody :checkbox:checked").length == i) {
                        $("th :checkbox").prop('checked', true);
                        alert('{TR_SELECT_ALL_ENTRIES_ALERT}');
                    } else {
                        $("th :checkbox").prop('checked', false);
                    }
                }
        ).length;
        $("th :checkbox").change(
                function () {
                    $("table :checkbox:not(':disabled')").prop('checked', $(this).is(':checked'));
                    if ($(this).is(':checked') && $("tbody :checkbox:checked").length == i) {
                        alert('{TR_SELECT_ALL_ENTRIES_ALERT}');
                    }
                }
        );
        $('.trigger_add').click(
                function () {
                    var str_mx = '<tr>';
                    str_mx += '	<td>';
                    str_mx += '		<label><input type="checkbox" name="to_delete[]" value="" disabled="disabled" /></label>';
                    str_mx += '	</td>';
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
    });
    /* ]]> */
</script>
<form name="edit_external_mail_server" method="post" action="mail_external_edit.php">
    <div>
        <a href="#" class="trigger_add">{TR_ADD_NEW_ENTRY}</a> | <a href="#" class="trigger_remove">{TR_REMOVE_LAST_ENTRY}</a>
    </div>
    <table>
        <thead>
        <tr>
            <th style="width:21px;">
                <a href="#" title="{TR_SELECT_ALL_ENTRIES_MESSAGE}"><label><input type="checkbox"/></label></a>
            </th>
            <th>{TR_MX_TYPE} <a href="#" class="icon i_help" title="{TR_MX_TYPE_TOOLTIP}">Help</a></th>
            <th>{TR_PRIORITY}</th>
            <th>{TR_HOST}</th>
        </tr>
        </thead>
        <tfoot>
        <tr>
            <th style="width:21px;">
                <a href="#" title="{TR_SELECT_ALL_ENTRIES_MESSAGE}"><label><input type="checkbox"/></label></a>
            </th>
            <th>{TR_MX_TYPE} <a href="#" class="icon i_help" title="{TR_MX_TYPE_TOOLTIP}">Help</a></th>
            <th>{TR_PRIORITY}</th>
            <th>{TR_HOST}</th>
        </tr>
        </tfoot>
        <tbody>
        <!-- BDP: item_entries -->
        <tr>
            <td>
                <a href="#" title="{TR_SELECT_ENTRY_MESSAGE}">
                    <label><input type="checkbox" name="to_delete[{INDEX}]" value="{ENTRY_ID}"/></label>
                </a>
                <input type="hidden" name="to_update[{INDEX}]" value="{ENTRY_ID}"/>
            </td>
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
            <td><label><input type="text" name="host[{INDEX}]" id="host_{INDEX}" value="{HOST}"/></label></td>
        </tr>
        <!-- EDP: item_entries -->
        </tbody>
    </table>
    <div style="float:left;">
        <a href="#" class="trigger_add">{TR_ADD_NEW_ENTRY}</a> | <a href="#" class="trigger_remove">{TR_REMOVE_LAST_ENTRY}</a>
    </div>
    <div class="buttons">
        <input name="cancel" type="button" onclick="location='mail_external.php'" value="{TR_CANCEL}"/>
        <input type="hidden" name="item" value="{ITEM}"/>
        <input name="submit" type="submit" value="{TR_UPDATE}"/>
    </div>
</form>
