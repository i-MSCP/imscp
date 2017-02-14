
<script>
    $(function () {
        var $datePicker = $('#datepicker');
        var $neverExpire = $('#never_expire');

        $datePicker.datepicker().on('change', function () {
            if ($(this).val() != '') {
                $neverExpire.prop('disabled', true);
                return;
            }

            $(this).prop('disabled', true);
            $neverExpire.prop('checked', true).prop("disabled", false);
        });

        $neverExpire.on('change', function () {
            if ($(this).is(':checked')) {
                $datePicker.prop('disabled', true);
                return;
            }

            $datePicker.prop('disabled', false);
        });

        $("input[name='url_forwarding']").on('change', function () {
            if ($("#url_forwarding_no").is(':checked')) {
                $("#tr_url_forwarding_data, #tr_type_forwarding_data").hide();
            } else {
                $("#tr_url_forwarding_data, #tr_type_forwarding_data").show();
            }
        }).trigger('change');

        $("input[name='forward_type']").on('change', function () {
            if ($("#forward_type_proxy").is(':checked')) {
                $(".checkbox").show();
            } else {
                $(".checkbox").hide();
            }
        }).trigger('change');
    });
</script>
<form name="reseller_add_users_first_frm" method="post" action="user_add1.php">
    <table class="firstColFixed">
        <thead>
        <tr>
            <th colspan="2">{TR_CORE_DATA}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>
                <label for="dmn_name">{TR_DOMAIN_NAME}</label>
                <span style="display: inline-block;float: right" class="bold">www.</span>
            </td>
            <td><input type="text" name="dmn_name" id="dmn_name" value="{DOMAIN_NAME_VALUE}"></td>
        </tr>
        <tr>
            <td>{TR_URL_FORWARDING} <span class="icon i_help" title="{TR_URL_FORWARDING_TOOLTIP}"></span></td>
            <td>
                <div class="radio">
                    <input type="radio" name="url_forwarding" id="url_forwarding_yes"{FORWARD_URL_YES} value="yes">
                    <label for="url_forwarding_yes">{TR_YES}</label>
                    <input type="radio" name="url_forwarding" id="url_forwarding_no"{FORWARD_URL_NO} value="no">
                    <label for="url_forwarding_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        <tr id="tr_url_forwarding_data">
            <td>{TR_FORWARD_TO_URL}</td>
            <td>
                <label for="forward_url_scheme">
                    <select name="forward_url_scheme" id="forward_url_scheme">
                        <option value="http://"{HTTP_YES}>{TR_HTTP}</option>
                        <option value="https://"{HTTPS_YES}>{TR_HTTPS}</option>
                    </select>
                </label>
                <label>
                    <input name="forward_url" type="text" id="forward_url" value="{FORWARD_URL}">
                </label>
            </td>
        </tr>
        <tr id="tr_type_forwarding_data">
            <td>{TR_FORWARD_TYPE}</td>
            <td>
                <span class="radio">
                    <input type="radio" name="forward_type" id="forward_type_301"{FORWARD_TYPE_301} value="301">
                    <label for="forward_type_301">{TR_301}</label>
                    <input type="radio" name="forward_type" id="forward_type_302"{FORWARD_TYPE_302} value="302">
                    <label for="forward_type_302">{TR_302}</label>
                    <input type="radio" name="forward_type" id="forward_type_303"{FORWARD_TYPE_303} value="303">
                    <label for="forward_type_303">{TR_303}</label>
                    <input type="radio" name="forward_type" id="forward_type_307"{FORWARD_TYPE_307} value="307">
                    <label for="forward_type_307">{TR_307}</label>
                    <input type="radio" name="forward_type" id="forward_type_proxy"{FORWARD_TYPE_PROXY} value="proxy">
                    <label for="forward_type_proxy">{TR_PROXY}</label>
                </span>
                <span class="checkbox">
                    <input type="checkbox" name="forward_host" id="forward_host"{FORWARD_HOST}>
                    <label for="forward_host">{TR_PROXY_PRESERVE_HOST}</label>
                </span>
            </td>
        </tr>
        <tr>
            <td><label for="datepicker">{TR_DOMAIN_EXPIRE}</label></td>
            <td>
                <input type="text" name="datepicker" id="datepicker" value="{DATEPICKER_VALUE}"{DATEPICKER_DISABLED}>
                <input type="checkbox" name="never_expire" id="never_expire" value="0"{NEVER_EXPIRE_CHECKED}>
                <label for="never_expire">{TR_EXPIRE_CHECKBOX}</label>
            </td>
        </tr>
        <!-- BDP: hosting_plan_entries_block -->
        <tr>
            <td><label for="dmn_tpl">{TR_CHOOSE_HOSTING_PLAN}</label></td>
            <td>
                <select id="dmn_tpl" name="dmn_tpl">
                    <!-- BDP: hosting_plan_entry_block -->
                    <option value="{HP_ID}"{HP_SELECTED}>{HP_NAME}</option>
                    <!-- EDP: hosting_plan_entry_block -->
                </select>
            </td>
        </tr>
        <!-- BDP: customize_hosting_plan_block -->
        <tr>
            <td>{TR_PERSONALIZE_TEMPLATE}</td>
            <td>
                <div class="radio">
                    <input type="radio" id="chtpl_yes" name="chtpl" value="_yes_" {CHTPL1_VAL}>
                    <label for="chtpl_yes">{TR_YES}</label>
                    <input type="radio" id="chtpl_no" name="chtpl" value="_no_" {CHTPL2_VAL}>
                    <label for="chtpl_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        <!-- EDP: customize_hosting_plan_block -->
        <!-- EDP: hosting_plan_entries_block -->
        </tbody>
    </table>
    <div class="buttons">
        <input name="Submit" type="submit" value="{TR_NEXT_STEP}">
    </div>
</form>
