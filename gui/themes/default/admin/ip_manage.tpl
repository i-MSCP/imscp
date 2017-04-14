<script>
    $(function () {
        function flashMessage(type, message) {
            $(".flash_message").remove();
            $("<div>", {
                "class": "flash_message " + type,
                "html": $.parseHTML(message)
            }).prependTo(".body").trigger("message_timeout");
        }
        
        function doRequest(data) {
            return $.post("/admin/ip_manage.php", data, null, "json").done(function () {
                window.location.href = "/admin/ip_manage.php";
            }).fail(function (jqXHR) {
                if (jqXHR.status == 403) {
                    window.location.replace("/index.php");
                } else {
                    flashMessage("error", jqXHR.responseJSON.message);
                }
            });
        }

        $.each(imscp_i18n.core.err_fields_stack, function () {
            $("#" + this).css("border-color", "#ca1d11");
        });

        var $ipNumber = $("#ip_number");
        var $netmask = $("#ip_netmask");
        var $ipCard = $("#ip_card");
        
        $('.datatable').dataTable({
            language: imscp_i18n.core.dataTable,
            stateSave: true,
            pagingType: "simple",
            columnDefs: [{ sortable: false, searchable: false, targets: [3, 4] }]
        }).on("change", ".radio", function () {
            var $this = $(this);
            doRequest($this.parent().find("input").serialize()).fail(function () {
                $this.find('input:not(:checked)').prop('checked', true).button('refresh');
             });
            return false;
        }).find("tbody > tr").each(function () { // Make some fields editable at runtime
            $(this).find("td").slice(1, 3).each(function () {
                var $el = $(this).find("span").filter(":first");
                if (!$el.data("editable"))
                    return;

                $el.addClass("tips");
                $el.before($("<span>", { "class": "icon i_help", "title": imscp_i18n.core.edit_tooltip }).tooltip({
                    tooltipClass: "ui-tooltip-notice", track: true
                }), "&nbsp;").on("click", function () {
                    var $elDeepCopy = $el.clone(true);
                    var $newEl = $('<span>');

                    $(this).replaceWith(function () {
                        switch ($(this).data("type")) {
                            case "netmask":
                                $newEl.append($netmask.clone().attr(
                                    'max', $(this).data("ip").indexOf(":") != -1 ? 64 : 32
                                ).val($(this).text()).css({ "min-width": "unset", "width": "40px" }));
                                break;
                            case "card":
                                $newEl.append($ipCard.clone().val($el.text()));
                                break;
                        }

                        $newEl.append($('<input>', { "type": "hidden", "name": "ip_id", "value": $(this).data("ip-id") }));
                        $newEl.on("blur", "input, select", function () {
                            if ($(this).val() != $el.text()) {
                                doRequest($(this).parent().find('input, select').serialize());
                            }

                            $el = $elDeepCopy;
                            $el.text($(this).val());
                            $(this).parent().replaceWith($el);
                        });

                        return $newEl;
                    });

                    $newEl.children(":first").focus();
                });
            });
        });

        var ipv6colC = $ipNumber.val().split(':', 3).length;
        $ipNumber.on("keyup paste copy cut", function (e, keepNetmaskVal) {
            var element = this;
            setTimeout(function() {
                var isIPv6 = $(element).val().indexOf(":") != -1;
                $netmask.attr(isIPv6 ? { min: 1, max: 128} : { min: 1, max: 32 });
                var ipv6NColC = $(element).val().split(':', 3).length;
                if (!keepNetmaskVal
                    && ((ipv6colC < 3 || (ipv6colC < 3 && ipv6NColC < 3)) || parseInt($netmask.val()) > parseInt($netmask.attr("max")) )) {
                    $netmask.val(isIPv6 ? 64 : 24)
                }

                ipv6colC = ipv6NColC;
            }, 0)
        }).trigger("change", true);

        $(".i_delete").on("click", function () {
            return jQuery.imscp.confirmOnclick(this, sprintf(imscp_i18n.core.confirm_deletion_msg, $(this).data("ip")));
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
        <th>{TR_IP_NETMASK}</th>
        <th>{TR_NETWORK_CARD}</th>
        <th>{TR_CONFIG_MODE}</th>
        <th>{TR_ACTION}</th>
    </tr>
    </thead>
    <tbody>
    <!-- BDP: ip_address_block -->
    <tr>
        <td>{IP}</td>
        <td><span data-editable="{IP_EDITABLE}" data-type="netmask" data-ip="{IP}" data-ip-id="{IP_ID}">{IP_NETMASK}</span></td>
        <td><span data-editable="{IP_EDITABLE}" data-type="card" data-ip-id="{IP_ID}">{NETWORK_CARD}</span></td>
        <td>
            <!-- BDP: ip_config_mode_block -->
            <div class="radio">
                <input type="radio" name="ip_config_mode[{IP_ID}]" id="ip_config_mode_auto_{IP_ID}" value="auto"{IP_CONFIG_AUTO}>
                <label for="ip_config_mode_auto_{IP_ID}">{TR_AUTO}</label>
                <input type="radio" name="ip_config_mode[{IP_ID}]" id="ip_config_mode_manual_{IP_ID}" value="manual"{IP_CONFIG_MANUAL}>
                <label for="ip_config_mode_manual_{IP_ID}">{TR_MANUAL}</label>
            </div>
            <input type="hidden" name="ip_id" value="{IP_ID}">
            <!-- EDP: ip_config_mode_block -->
        </td>
        <td>
            <!-- BDP: ip_action_delete -->
            <a class="icon i_delete" href="ip_delete.php?ip_id={ACTION_IP_ID}" data-ip="{IP}"
               title="{ACTION_NAME}">{ACTION_NAME}</a>
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
            <td><label for="ip_number">{TR_IP} / {TR_IP_NETMASK}</label></td>
            <td>
                <input name="ip_number" id="ip_number" type="text" value="{VALUE_IP}" style="min-width:300px" maxlength="45">
                <strong>/</strong>
                <label><input name="ip_netmask" id="ip_netmask" type="number" value="{VALUE_IP_NETMASK}" style="min-width:40px" min="1" max="32"></label>
            </td>
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
        <button name="Submit" type="submit">{TR_ADD}</button>
        <a class="link_as_button" href="settings.php">{TR_CANCEL}</a>
    </div>
</form>
<!-- EDP: ip_address_form_block -->
