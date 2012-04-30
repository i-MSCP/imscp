<script type="text/javascript">
/* <![CDATA[ */
    $(document).ready(function(){
        var i = $('#relay_lines tr').length + 1;

    	$('#add').click(function() {
            switch ($('#relay_type_add').val()) {
                case "mx_add":
                    var str_mx = '<tr class="relay_new_field">';
                    str_mx += '<td colspan="2">{TR_MX}<input type="hidden" name="relay_type[]" id="relay_type_' + i + '" value="MX" /></td>';
                    str_mx += '<td><select name="mx_alias[]" id="mx_alias_' + i + '"><option value="empty">empty</option><option value="*">*</option></select><input type="hidden" name="cname_name[]" id="cname_' + i + '" value="" /></td>';
                    str_mx += '<td><select name="mx_priority[]" id="mx_priority_' + i + '"><option value="10">10</option><option value="15">15</option><option value="20">20</option></select><input type="hidden" name="cname_priority[]" id="cname_priority_' + i + '" value="" /></td>';
                    str_mx += '<td><input type="text" name="srv_dnsrecord[]" id="srv_dnsrecord_' + i + '" value="" /></td>';
                    str_mx += '</tr>';
                    $(".inputs tbody").append(str_mx);
                    break;
                case "cname_add":
                    var str_cname = '<tr class="relay_new_field">';
                    str_cname += '<td colspan="2">{TR_CNAME}<input type="hidden" name="relay_type[]" id="relay_type_' + i + '" value="CNAME" /></td>';
                    str_cname += '<td><input type="text" name="cname_name[]" id="cname_name_' + i + '" value="" /><input type="hidden" name="mx_alias[]" id="mx_alias_' + i + '" value="" /></td>';
                    str_cname += '<td><input type="text" name="cname_priority[]" id="cname_priority_' + i + '" value="{CNAME_PRIORITY}" readonly="readonly" /><input type="hidden" name="mx_priority[]" id="mx_priority_' + i + '" value="" /></td>';
                    str_cname += '<td><input type="text" name="srv_dnsrecord[]" id="srv_dnsrecord_' + i + '" value="" /></td>';
                    str_cname += '</tr>';
                    $(".inputs tbody").append(str_cname);
                    break;
            }


    		i++;
    	})
        $('#remove').click(function() {
            if(i > 1) {
                $('.relay_new_field:last').remove();
                i--;
            }
    	});

    	$('#reset').click(function() {
            while(i > 2) {
                $('.relay_new_field:last').remove();
                i--;
            }
    	});
    });
/* ]]> */
</script>
<table>
    <thead>
        <tr>
            <th>{TR_RELAY_TYPE}</th>
            <th>{TR_ACTION}</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <select name="relay_type_add" id="relay_type_add">
                    <option value="mx_add">{TR_MX}</option>
                    <option value="cname_add">{TR_CNAME}</option>
                </select>
            </td>
            <td>
                <a href="#" id="add">{TR_ADD_NEW}</a> | <a href="#" id="remove">{TR_REMOVE_LAST}</a>  | <a href="#" id="reset">{TR_RESET}</a>
            </td>
        </tr>
    </tbody>
</table>
<form name="create_relay_frm" method="post" action="mail_external_edit.php">
<table class="inputs">
        <thead>
            <tr>
                <th width="10">{TR_REMOVE_RELAY_ITEM}</th>
                <th>{TR_RELAY_TYPE}</th>
                <th>{TR_RELAY_DNS}</th>
                <th>{TR_MX_PRIORITY}</th>
                <th>{TR_RELAY_SERVER}</th>
            </tr>
        </thead>
        <tbody id="relay_lines">
            <!-- BDP: relay_server_entry_item -->
            <tr>
                <!-- BDP: mx_entry_item -->
                <td><input type="checkbox" name="del_item[]" id="{DEL_ITEM_ID}" value="{DEL_ITEM}" /></td>
                <td>{TR_MX}<input type="hidden" name="relay_type[]" id="{RELAY_TYPE_ID}" value="MX" /></td>
                <td>
                    <select name="mx_alias[]" id="{MX_ALIAS_ID}">{SELECT_MX_ALIAS_ITEM}</select>
                    <input type="hidden" name="cname_name[]" id="{CNAME_NAME_ID}" value="" />
                </td>
                <td>
                    <select name="mx_priority[]" id="{MX_PRIORITY_ID}">{SELECT_MX_PRIO_ITEM}</select>
                    <input type="hidden" name="cname_priority[]" id="{CNAME_PRIORITY_ID}" value="" />
                </td>
                <td><input type="text" name="srv_dnsrecord[]" id="{SRV_DNSRECORD_ID}" value="{SRV_DNSRECORD_ITEM}" /></td>
                <!-- EDP: mx_entry_item -->
                <!-- BDP: cname_entry_item -->
                <td><input type="checkbox" name="del_item[]" id="{DEL_ITEM_ID}" value="{DEL_ITEM}" /></td>
                <td>{TR_CNAME}<input type="hidden" name="relay_type[]" id="{RELAY_TYPE_ID}" value="CNAME" /></td>
                <td>
                    <input type="text" name="cname_name[]" id="{CNAME_NAME_ID}" value="{CNAME_NAME_ITEM}" />
                    <input type="hidden" name="mx_alias[]" id="{MX_ALIAS_ID}" value="" />
                </td>
                <td>
                    <input type="text" name="cname_priority[]" id="{CNAME_PRIORITY_ID}" value="{CNAME_PRIORITY}" readonly="readonly" />
                    <input type="hidden" name="mx_priority[]" id="{MX_PRIORITY_ID}" value="" />
                </td>
                <td><input type="text" name="srv_dnsrecord[]" id="{SRV_DNSRECORD_ID}" value="{SRV_DNSRECORD_ITEM}" /></td>
                <!-- EDP: cname_entry_item -->
            </tr>
            <!-- EDP: relay_server_entry_item -->
        </tbody>
</table>

<div class="buttons">
<input type="hidden" name="uaction" value="edit_external_mail" />
<input type="hidden" name="id" value="{ID}" />
<input type="hidden" name="old_dns_entry_ids" value="{OLD_DNS_ENTRY_IDS}" />
<input name="Submit" type="submit" value="{TR_CHANGE_RELAY}" />
</div>
</form>
