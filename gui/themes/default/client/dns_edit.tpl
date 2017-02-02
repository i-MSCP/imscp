
<script>
    $(function () {
        $("#zone_id").on("change", function () {
            $("#origin").html('<strong>' + $(this).find("option:selected").text() + '.</strong>');
        }).trigger('change');
    });

    var inputFields = ['name', 'ip_address', 'ip_address_v6', 'srv_name', 'srv_protocol', 'srv_ttl', 'srv_prio',
        'srv_weight', 'srv_host', 'srv_port', 'cname', 'txt_data'];
    var inputFieldsLength = inputFields.length;

    function dns_show_rows(inputFieldsToShow) {
        for (var i = 0; i < inputFieldsLength; i++) {
            var trName = 'tr_dns_' + inputFields[i];
            var $o = $('#' + trName);

            if ($.inArray(inputFields[i], inputFieldsToShow) != -1) {
                $o.show();
            } else {
                $o.hide();
            }
        }
    }

    function change_dns_type(value) {
        if (value == 'A') {
            dns_show_rows(['name', 'srv_ttl', 'ip_address']);
        } else if (value == 'AAAA') {
            dns_show_rows(['name', 'srv_ttl', 'ip_address_v6']);
        } else if (value == 'MX') {
            dns_show_rows(['name', 'srv_ttl', 'srv_prio', 'srv_host']);
        } else if (value == 'NS') {
            dns_show_rows(['name', 'srv_ttl', 'srv_host']);
        } else if (value == 'CNAME') {
            dns_show_rows(['name', 'srv_ttl', 'cname']);
        } else if (value == 'SPF' || value == 'TXT') {
            dns_show_rows(['name', 'srv_ttl', 'txt_data']);
        } else if (value == 'SRV') {
            dns_show_rows(['srv_name', 'srv_protocol', 'name', 'srv_ttl', 'srv_prio', 'srv_weight', 'srv_host', 'srv_port']);
        }
    }

    var IPADDRESS = "[0-9.]";
    var IPv6ADDRESS = "[0-9a-f:A-F]";
    var NUMBERS = "[0-9]";

    function filterChars(e, allowed) {
        var keynum;
        if (window.event) {
            keynum = window.event.keyCode;
            e = window.event;
        } else if (e) {
            keynum = e.which;
        } else {
            return true;
        }

        if ((keynum == 8) || (keynum == 0)) {
            return true;
        }

        var keychar = String.fromCharCode(keynum);

        if (e.ctrlKey && ((keychar == "C") || (keychar == "c") || (keychar == "V") || (keychar == "v"))) {
            return true;
        }

        var regexp = new RegExp(allowed);
        return regexp.test(keychar);
    }

    $(function () {
        change_dns_type($("#dns_type").val());
    });
</script>

<p class="static_info">
    <?= tr('$ORIGIN is automatically appended to unqualified names, hosts and canonical names') ?>.<br>
    <?= tr('If the name field is filled with the @ sign or left blank, it will be automatically substituted by $ORIGIN value.') ?>
    <br>
    <?= tr('$ORIGIN value is currently set to: %s', '<span id="origin"></span>') ?>
</p>

<form name="edit_dns_frm" method="post" action="{ACTION_MODE}">
    <table class="firstColFixed">
        <thead>
        <tr>
            <th colspan="2">{TR_CUSTOM_DNS_RECORD}</th>
        </tr>
        </thead>
        <tbody>
        <!-- BDP: add_record -->
        <tr>
            <td>
                <label for="zone_id">{TR_ZONE}</label>
                <span class="icon i_help" title="{TR_ZONE_HELP}"></span>
            </td>
            <td>
                <select id="zone_id" name="zone_id">
                    {SELECT_ZONES}
                </select>
            </td>
        </tr>
        <!-- EDP: add_record -->
        <tr id="tr_dns_srv_name">
            <td><label for="dns_srv_name">{TR_DNS_SRV_NAME}</label></td>
            <td>
                <input id="dns_srv_name" type="text" name="dns_srv_name" value="{DNS_SRV_NAME}" class="inputTitle" placeholder="_sip">
            </td>
        </tr>
        <tr id="tr_dns_srv_protocol">
            <td><label for="srv_protocol">{TR_DNS_SRV_PROTOCOL}</label></td>
            <td>
                <select name="srv_proto" id="srv_protocol">
                    {SELECT_DNS_SRV_PROTOCOL}
                </select>
            </td>
        </tr>
        <tr id="tr_dns_name">
            <td><label for="dns_name">{TR_DNS_NAME}</label></td>
            <td><input id="dns_name" type="text" name="dns_name" value="{DNS_NAME}" class="inputTitle"></td>
        </tr>
        <tr id="tr_dns_ttl">
            <td><label for="dns_ttl">{TR_DNS_TTL}</label></td>
            <td>
                <input id="dns_ttl" type="number" min="60" max="2147483647" name="dns_ttl" value="{DNS_TTL}">
                <span>{TR_SEC}</span>
            </td>
        </tr>
        <tr>
            <td><label for="class">{TR_DNS_CLASS}</label></td>
            <td>
                <select id="class" name="class">
                    {SELECT_DNS_CLASS}
                </select>
            </td>
        </tr>
        <tr>
            <td><label for="dns_type">{TR_DNS_TYPE}</label></td>
            <td>
                <select id="dns_type" onchange="change_dns_type(this.value)" name="type"{DNS_TYPE_DISABLED}>
                    {SELECT_DNS_TYPE}
                </select>
            </td>
        </tr>
        <tr id="tr_dns_ip_address">
            <td><label for="dns_A_address">{TR_DNS_IP_ADDRESS}</label></td>
            <td><input id="dns_A_address" type="text" onkeypress="return filterChars(event, IPADDRESS);" name="dns_A_address" value="{DNS_ADDRESS}" class="inputTitle"></td>
        </tr>
        <tr id="tr_dns_ip_address_v6">
            <td><label for="dns_AAAA_address">{TR_DNS_IP_ADDRESS_V6}</label></td>
            <td><input id="dns_AAAA_address" type="text" onkeypress="return filterChars(event, IPv6ADDRESS);" name="dns_AAAA_address" value="{DNS_ADDRESS_V6}" class="inputTitle"></td>
        </tr>
        <tr id="tr_dns_srv_prio">
            <td><label for="dns_srv_prio">{TR_DNS_SRV_PRIO}</label></td>
            <td><input id="dns_srv_prio" type="number" min="0" max="65535" name="dns_srv_prio" value="{DNS_SRV_PRIO}"></td>
        </tr>
        <tr id="tr_dns_srv_weight">
            <td><label for="dns_srv_weight">{TR_DNS_SRV_WEIGHT}</label></td>
            <td><input id="dns_srv_weight" type="number" min="0" max="65535" name="dns_srv_weight" value="{DNS_SRV_WEIGHT}"></td>
        </tr>
        <tr id="tr_dns_srv_port">
            <td><label for="dns_srv_port">{TR_DNS_SRV_PORT}</label></td>
            <td><input id="dns_srv_port" type="text" onkeypress="return filterChars(event, NUMBERS);" name="dns_srv_port" value="{DNS_SRV_PORT}"></td>
        </tr>
        <tr id="tr_dns_srv_host">
            <td><label for="dns_srv_host">{TR_DNS_SRV_HOST}</label></td>
            <td><input id="dns_srv_host" type="text" name="dns_srv_host" value="{DNS_SRV_HOST}" class="inputTitle"></td>
        </tr>
        <tr id="tr_dns_cname">
            <td><label for="dns_cname">{TR_DNS_CNAME}</label></td>
            <td><input id="dns_cname" type="text" name="dns_cname" value="{DNS_CNAME}" class="inputTitle"></td>
        </tr>
        <tr id="tr_dns_txt_data">
            <td><label for="dns_txt_data">{TR_DNS_TXT_DATA}</label></td>
            <td><textarea id="dns_txt_data" name="dns_txt_data" style="height:200px">{DNS_TXT_DATA}</textarea></td>
        </tr>
        </tbody>
    </table>
    <div class="buttons">
        <!-- BDP: form_edit_mode -->
        <input name="Submit" type="submit" value="{TR_UPDATE}">
        <!-- EDP: form_edit_mode -->
        <!-- BDP: form_add_mode -->
        <input name="Submit" type="submit" value="{TR_ADD}">
        <!-- EDP: form_add_mode -->
        <a class="link_as_button" href="domains_manage.php">{TR_CANCEL}</a>
    </div>
</form>
