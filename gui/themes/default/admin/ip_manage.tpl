
<script>
    $(function () {
        function flashMessage(type, message) {
            $("<div>", { "class": "flash_message " + type, "html": $.parseHTML(message), "hide": true }).prependTo(".body").trigger('message_timeout');
        }

        $.each(imscp_i18n.core.err_fields_stack, function () {
            $("#" + this).css('border-color', '#ca1d11');
        });

        $('.datatable').dataTable(
                {
                    language: imscp_i18n.core.dataTable,
                    stateSave: true,
                    pagingType: "simple",
                    columnDefs: [{sortable: false, searchable: false, targets: [2, 3]}]
                }
        ).on('click', 'input', function () {
            $.post("/admin/ip_manage.php", $(this).serialize(), null, 'json').done(function () {
                window.location.href = '/admin/ip_manage.php';
            }).fail(function (jqXHR) {
                if (jqXHR.status == 403) {
                    window.location.replace("/index.php");
                } else {
                    flashMessage("error", jqXHR.responseJSON.message);
                }
            });

            return false;
        });
        
        $(".i_delete").on("click", function() {
            return confirm(sprintf(imscp_i18n.core.confirm_deletion_msg, $(this).data("ip")));
        });
    });
</script>

<p class="hint" style="font-variant: small-caps;font-size: small;">{TR_TIP}</p>
<br>

<!-- BDP: ip_addresses_block -->
<table class="datatable">
    <thead>
    <tr>
        <th>{TR_IP}</th>
        <th>{TR_NETWORK_CARD}</th>
        <th>{TR_CONFIG_MODE}</th>
        <th>{TR_ACTION}</th>
    </tr>
    </thead>
    <tbody>
    <!-- BDP: ip_address_block -->
    <tr>
        <td>{IP}</td>
        <td>{NETWORK_CARD}</td>
        <td>
            <!-- BDP: ip_config_mode_block -->
                <div class="radio">
                    <input type="radio" name="ip_config_mode[{IP_ID}]" id="ip_config_mode_auto_{IP_ID}" value="auto"{IP_CONFIG_AUTO} data-ip_addr="{IP}">
                    <label for="ip_config_mode_auto_{IP_ID}">{TR_AUTO}</label>
                    <input type="radio" name="ip_config_mode[{IP_ID}]" id="ip_config_mode_manual_{IP_ID}" value="manual"{IP_CONFIG_MANUAL} data-ip_addr="{IP}">
                    <label for="ip_config_mode_manual_{IP_ID}">{TR_MANUAL}</label>
                </div>
            <!-- EDP: ip_config_mode_block -->
        </td>
        <td>
            <!-- BDP: ip_action_delete -->
            <a class="icon i_delete" href="ip_delete.php?ip_id={ACTION_IP_ID}" data-ip="{IP}" title="{ACTION_NAME}">{ACTION_NAME}</a>
            <!-- EDP: ip_action_delete -->
        </td>
    </tr>
    <!-- EDP: ip_address_block -->
    </tbody>
</table>
<!-- EDP: ip_addresses_block -->

<!-- BDP: ip_address_form_block -->
<form name="addIpFrm" method="post" action="ip_manage.php">
    <table class="firstColFixed">
        <thead>
        <tr>
            <th colspan="2">{TR_ADD_NEW_IP}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><label for="ip_card">{TR_NETWORK_CARD}</label></td>
            <td>
                <select name="ip_card" id="ip_card">
                    <!-- BDP: network_card_block -->
                    <option{SELECTED}>{NETWORK_CARD}</option>
                    <!-- EDP: network_card_block -->
                </select>
            </td>
        </tr>
        <tr>
            <td><label for="ip_number">{TR_IP}</label></td>
            <td><input name="ip_number" id="ip_number" type="text" value="{VALUE_IP}" maxlength="39"></td>
        </tr>
        <tr>
            <td>
                {TR_CONFIG_MODE}
                <span class="tips icon i_help" title="{TR_CONFIG_MODE_TOOLTIPS}"></span></td>
            <td>
                <div class="radio">
                    <input type="radio" name="ip_config_mode" id="ip_config_mode_auto" value="auto"{IP_CONFIG_AUTO}>
                    <label for="ip_config_mode_auto">{TR_AUTO}</label>
                    <input type="radio" name="ip_config_mode" id="ip_config_mode_manual" value="manual"{IP_CONFIG_MANUAL}>
                    <label for="ip_config_mode_manual">{TR_MANUAL}</label>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
    <div class="buttons">
        <button name="submit" type="submit">{TR_ADD}</button>
        <a class="link_as_button" href="settings.php">{TR_CANCEL}</a>
    </div>
</form>
<!-- EDP: ip_address_form_block -->
