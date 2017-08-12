
<script>
    function action_delete(link, service) {
        return jQuery.imscp.confirmOnclick(link, sprintf("{TR_MESSAGE_DELETE}", service));
    }

    var error_fields_ids = {ERROR_FIELDS_IDS};

    $(function () {
        $('.datatable').dataTable(
            {
                language: imscp_i18n.core.dataTable,
                displayLength: 10,
                stateSave: true,
                filter: false,
                pagingType: "simple",
                sort: false
            }
        );

        $.each(error_fields_ids, function () {
            $("#" + this).css({ "border": "1px solid red", "font-weight": "bolder" });
        });

        $('input[name=submitForReset]').click(function () {
            $('input[name=uaction]').val('reset');
        });
    });
</script>
<!-- start form edit -->
<form name="editFrm" method="post" action="settings_ports.php"">
    <table class="datatable">
        <thead>
        <tr>
            <th>{TR_SERVICE}</th>
            <th>{TR_IP}</th>
            <th>{TR_PORT}</th>
            <th>{TR_PROTOCOL}</th>
            <th>{TR_SHOW}</th>
            <th>{TR_ACTION}</th>
        </tr>
        </thead>
        <tbody>
        <!-- BDP: service_ports -->
        <tr>
            <td>
                <label for="name{NUM}"><input name="name[]" type="text" id="name{NUM}" value="{NAME}" class="textinput" maxlength="25"></label>
                <input name="var_name[]" type="hidden" id="var_name{NUM}" value="{VAR_NAME}">
            </td>
            <td>
                <label><input name="ip[]" type="text" id="ip{NUM}" value="{IP}" maxlength="40"></label>
            </td>
            <td>
                <label><input name="port[]" type="number" id="port{NUM}" value="{PORT}" min="1" max="65535"></label>
            </td>
            <td>
                <label>
                    <select name="port_type[]" id="port_type{NUM}">
                        <option value="udp"{SELECTED_UDP}>UDP</option>
                        <option value="tcp"{SELECTED_TCP}>TCP</option>
                    </select>
                </label>
            </td>
            <td>
                <label>
                    <select name="show_val[]" id="show_val{NUM}">
                        <option value="1"{SELECTED_ON}>{TR_YES}</option>
                        <option value="0"{SELECTED_OFF}>{TR_NO}</option>
                    </select>
                </label>
            </td>
            <td>
                <a href="settings_ports.php?delete={DELETE_ID}" class="icon i_delete" onclick="return action_delete(this, '{NAME}')">{TR_DELETE}</a>
            </td>
        </tr>
        <!-- EDP: service_ports -->
        </tbody>
    </table>
    <div class="buttons">
        <input type="hidden" name="uaction" value="update">
        <input name="submitForUpdate" type="submit" value="{VAL_FOR_SUBMIT_ON_UPDATE}">
        <input name="submitForReset" type="submit" value="{VAL_FOR_SUBMIT_ON_RESET}">
    </div>
</form>
<form name="addFrm" method="post" action="settings_ports.php">
    <table>
        <thead>
        <tr>
            <th>{TR_SERVICE}</th>
            <th>{TR_IP}</th>
            <th>{TR_PORT}</th>
            <th>{TR_PROTOCOL}</th>
            <th>{TR_SHOW}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><label><input name="name_new" type="text" id="name" value="{VAL_FOR_NAME_NEW}" maxlength="25"></label></td>
            <td><label><input name="ip_new" type="text" id="ip" value="{VAL_FOR_IP_NEW}" maxlength="45"></label></td>
            <td><label><input name="port_new" type="number" id="port" value="{VAL_FOR_PORT_NEW}" min="1" max="65535"></label></td>
            <td>
                <label>
                    <select name="port_type_new" id="port_type">
                        <option value="udp">UDP</option>
                        <option value="tcp" selected>TCP</option>
                    </select>
                </label>
            </td>
            <td>
                <label>
                    <select name="show_val_new" id="show_val">
                        <option value="1" selected>{TR_YES}</option>
                        <option value="0">{TR_NO}</option>
                    </select>
                </label>
            </td>
        </tr>
        </tbody>
    </table>
    <div class="buttons">
        <input type="hidden" name="uaction" value="add">
        <input name="submitForAdd" type="submit" value="{VAL_FOR_SUBMIT_ON_ADD}">
    </div>
</form>
