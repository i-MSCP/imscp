<script>
    $(function () {
        function fixQuotaField() {
            // Make sure that hidden quota field will pass browser validations on submit
            var $quotaInput = $("#quota");
            var quotaInputValue = parseInt($quotaInput.val());
            var quotaInputMinValue = parseInt($quotaInput.attr('min'));
            var quotaInputMaxValue = parseInt($quotaInput.attr('max'));

            if (isNaN(quotaInputValue)
                || quotaInputValue < quotaInputMinValue
                || quotaInputValue > quotaInputMaxValue
            ) {
                $quotaInput.val(quotaInputMinValue);
            }
        }

        if(imscp_i18n.core.mail_add_forward_only) {
            $("#forward").prop('checked', true).closest('tr').hide();
            $("#tr_forward_list").show();
            $("#tr_password, #tr_password_rep, #tr_quota").hide();
            fixQuotaField();
        } else {
            $("input[name='account_type']").on('change', function () {
                    fixQuotaField();

                    if ($(this).val() === '1') { // Normal mail account
                        $("#tr_password, #tr_password_rep, #tr_quota").show();
                        $("#tr_forward_list").hide();
                        return;
                    }

                    if ($(this).val() === '2') { // Forward only mail account
                        $("#tr_forward_list").show();
                        $("#tr_password, #tr_password_rep, #tr_quota").hide();
                        return;
                    }

                    // Normal + Forward mail account
                    $("#tr_password, #tr_password_rep, #tr_quota").show();
                    $("#tr_forward_list").show();
                }
            ).parent().find(':checked').trigger('change'); // Initialize form
        }
    });
</script>
<form name="client_mail_add" action="mail_add.php" method="post" autocomplete="off">
    <table class="firstColFixed">
        <thead>
        <tr>
            <th colspan="2">{TR_MAIl_ACCOUNT_DATA}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>{TR_MAIL_ACCOUNT_TYPE}</td>
            <td>
                <div class="radio">
                    <input type="radio" name="account_type" id="normal" value="1"{NORMAL_CHECKED}>
                    <label for="normal">{TR_NORMAL_MAIL}</label>
                    <input type="radio" name="account_type" id="forward" value="2"{FORWARD_CHECKED}>
                    <label for="forward">{TR_FORWARD_MAIL}</label>
                    <input type="radio" name="account_type" id="normal_forward" value="3"{NORMAL_FORWARD_CHECKED}>
                    <label for="normal_forward">{TR_FORWARD_NORMAL_MAIL}</label>
                </div>
            </td>
        </tr>
        <tr>
            <td><label for="username">{TR_USERNAME}</label></td>
            <td><input type="text" name="username" id="username" value="{USERNAME}"></td>
        </tr>
        <tr>
            <td><label for="domain_name">{TR_DOMAIN_NAME}</label></td>
            <td>
                <select name="domain_name" id="domain_name">
                    <!-- BDP: domain_name_item -->
                    <option value="{DOMAIN_NAME}"{DOMAIN_NAME_SELECTED}>{DOMAIN_NAME_UNICODE}</option>
                    <!-- EDP: domain_name_item -->
                </select>
            </td>
        </tr>
        <tr id="tr_password">
            <td><label for="password">{TR_PASSWORD}</label></td>
            <td><input id="password" type="password" name="password" value="" class="pwd_generator pwd_prefill" autocomplete="new-password"></td>
        </tr>
        <tr id="tr_password_rep">
            <td><label for="cpassword">{TR_PASSWORD_REPEAT}</label></td>
            <td><input id="cpassword" type="password" name="password_rep" value="" autocomplete="new-password"></td>
        </tr>
        <tr id="tr_quota">
            <td><label for="quota">{TR_QUOTA}</label></td>
            <td><input name="quota" id="quota" type="number" min="{MIN_QUOTA}" max="{MAX_QUOTA}" value="{QUOTA}"></td>
        </tr>
        <tr id="tr_forward_list">
            <td>
                <label for="forward_list">{TR_FORWARD_TO}</label>
                <span class="icon i_help" id="fwd_help" title="{TR_FWD_HELP}"></span>
            </td>
            <td><textarea name="forward_list" id="forward_list">{FORWARD_LIST}</textarea></td>
        </tr>
        </tbody>
    </table>
    <div class="buttons">
        <input type="submit" name="Submit" value="{TR_ADD}">
        <a href="mail_accounts.php" class="link_as_button">{TR_CANCEL}</a>
    </div>
</form>
