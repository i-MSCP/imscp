
<script>
    $(function () {
        if (!$("#add_update").length) {
            <!-- BDP: ssl_certificate_disabled_fields -->
            $("#selfsigned").hide();
            $("input,textarea").prop("disabled", true).trigger("change");
            <!-- EDP: ssl_certificate_disabled_fields -->
        }

        $("#allow_hsts_on,#allow_hsts_off").on('change', function () {
                if ($("#allow_hsts_on").is(':checked')) {
                    $("#tr_hsts_max_age_data, #tr_hsts_include_subdomains_data").show();
                } else {
                    $("#tr_hsts_max_age_data, #tr_hsts_include_subdomains_data").hide();
                }
            }
        ).trigger("change");

        $("#selfsigned_on,#selfsigned_off").on('change', function () {
            if ($("#selfsigned_on").is(":checked")) {
                $(".input_fields").hide();
            } else {
                $(".input_fields").show();
            }
        });
    });
</script>
<form name="ssl_cert_frm" method="post" action="cert_view.php?domain_id={DOMAIN_ID}&domain_type={DOMAIN_TYPE}" autocomplete="off">
    <table class="firstColFixed">
        <thead>
        <tr>
            <th colspan="2">{TR_CERTIFICATE_DATA}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>{TR_CERT_FOR}</td>
            <td>{DOMAIN_NAME}</td>
        </tr>
        <!-- BDP: ssl_certificate_status -->
        <tr>
            <td>{TR_STATUS}</td>
            <td>{STATUS}</td>
        </tr>
        <!-- EDP: ssl_certificate_status -->
        <!-- BDP: ssl_certificate_hsts -->
        <tr>
            <td><label>{TR_ALLOW_HSTS}</label></td>
            <td>
                <div class="radio">
                    <input type="radio" name="allow_hsts" id="allow_hsts_on" value="on"{HSTS_CHECKED_ON}>
                    <label for="allow_hsts_on">{TR_YES}</label>
                    <input type="radio" name="allow_hsts" id="allow_hsts_off" value="off"{HSTS_CHECKED_OFF}>
                    <label for="allow_hsts_off">{TR_NO}</label>
                </div>
            </td>
        </tr>
        <tr id="tr_hsts_max_age_data">
            <td><label for="hsts_max_age">{TR_HSTS_MAX_AGE}</label></td>
            <td>
                <input name="hsts_max_age" id="hsts_max_age" type="text" value="{HSTS_MAX_AGE}">
                <span>{TR_SEC}</span>
            </td>
        </tr>
        <tr id="tr_hsts_include_subdomains_data">
            <td>
                <label>{TR_HSTS_INCLUDE_SUBDOMAINS}</label>
                <span class="icon i_help" title="{TR_HSTS_INCLUDE_SUBDOMAINS_TOOLTIP}"></span>
            </td>
            <td>
                <div class="radio selfsigned">
                    <input type="radio" name="hsts_include_subdomains" id="hsts_include_subdomains_on" value="on"{HSTS_INCLUDE_SUBDOMAIN_ON}>
                    <label for="hsts_include_subdomains_on">{TR_YES}</label>
                    <input type="radio" name="hsts_include_subdomains" id="hsts_include_subdomains_off" value="off"{HSTS_INCLUDE_SUBDOMAIN_OFF}>
                    <label for="hsts_include_subdomains_off">{TR_NO}</label>
                </div>
            </td>
        </tr>
        <!-- EDP: ssl_certificate_hsts -->
        <!-- BDP: ssl_certificate_selfsigned -->
        <tr id="selfsigned">
            <td><label>{TR_GENERATE_SELFSIGNED_CERTIFICAT}</label></td>
            <td>
                <div class="radio">
                    <input type="radio" name="selfsigned" id="selfsigned_on" value="on">
                    <label for="selfsigned_on">{TR_YES}</label>
                    <input type="radio" name="selfsigned" id="selfsigned_off" value="off" checked>
                    <label for="selfsigned_off">{TR_NO}</label>
                </div>
            </td>
        </tr>
        <!-- EDP: ssl_certificate_selfsigned -->
        <!-- BDP: ssl_certificate_pk_pwd -->
        <tr class="input_fields">
            <td><label for="passphrase">{TR_PASSWORD}</label></td>
            <td><input id="passphrase" type="password" name="passphrase" value="" autocomplete="new-password"></td>
        </tr>
        <!-- EDP: ssl_certificate_pk_pwd -->
        <!-- BDP: ssl_certificate_certchain -->
        <tr class="input_fields">
            <td><label for="private_key">{TR_PRIVATE_KEY}</label></td>
            <td><textarea name="private_key" id="private_key">{KEY_CERT}</textarea></td>
        </tr>
        <tr class="input_fields">
            <td><label for="certificate">{TR_CERTIFICATE}</label></td>
            <td><textarea name="certificate" id="certificate">{CERTIFICATE}</textarea></td>
        </tr>
        <tr class="input_fields">
            <td><label for="ca_bundle">{TR_CA_BUNDLE}</label></td>
            <td><textarea name="ca_bundle" id="ca_bundle">{CA_BUNDLE}</textarea></td>
        </tr>
        <!-- EDP: ssl_certificate_certchain -->
        </tbody>
    </table>
    <div class="buttons">
        <!-- BDP: ssl_certificate_actions -->
        <!-- BDP: ssl_certificate_action_update -->
        <input name="add_update" id="add_update" type="submit" value="{TR_ACTION}">
        <!-- EDP: ssl_certificate_action_update -->
        <!-- BDP: ssl_certificate_action_delete -->
        <input name="delete" id="delete" type="submit" value="{TR_DELETE}">
        <!-- EDP: ssl_certificate_action_delete -->
        <input name="cert_id" type="hidden" value="{CERT_ID}">
        <!-- EDP: ssl_certificate_actions -->
        <a class="link_as_button" href="domains_manage.php">{TR_CANCEL}</a>
    </div>
</form>
