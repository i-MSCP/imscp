
<script>
    $(function () {
        $("input[name='url_forwarding']").on('change', function () {
            if ($("#url_forwarding_no").is(':checked')) {
                $("#tr_url_forwarding_data, #tr_type_forwarding_data").hide();
                $("#document_root").show();
            } else {
                $("#tr_url_forwarding_data, #tr_type_forwarding_data").show();
                $("#document_root").hide();
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
<form name="edit_domain_frm" method="post" action="domain_edit.php?id={DOMAIN_ID}">
    <table class="firstColFixed">
        <thead>
        <tr>
            <th colspan="2">{TR_DOMAIN}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><label for="domain_name">{TR_DOMAIN_NAME}</label></td>
            <td>
                <span class="bold">www.</span>
                <input type="text" name="domain_name" id="domain_name" value="{DOMAIN_NAME}" readonly="readonly">
            </td>
        </tr>
        <!-- BDP: document_root_bloc -->
        <tr id="document_root">
            <td>
                <label for="ftp_directory">{TR_DOCUMENT_ROOT}</label>
                <span class="icon i_help" title="{TR_DOCUMENT_ROOT_TOOLTIP}"></span>
            </td>
            <td>
                <span class="bold">/htdocs</span>
                <input type="text" name="document_root" id="ftp_directory" class="textinput" placeholder="/" value="{DOCUMENT_ROOT}">
                <span class="icon i_bc_folder ftp_choose_dir clickable">{TR_CHOOSE_DIR}</span>
            </td>
        </tr>
        <!-- EDP: document_root_bloc -->
        <tr>
            <td>
                {TR_URL_FORWARDING}
                <span class="icon i_help" title="{TR_URL_FORWARDING_TOOLTIP}"></span>
            </td>
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
                <label><input name="forward_url" type="text" id="forward_url" value="{FORWARD_URL}"></label>
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
        </tbody>
    </table>
    <div class="buttons">
        <input name="Submit" type="submit" value="{TR_UPDATE}">
        <a class="link_as_button" href="domains_manage.php">{TR_CANCEL}</a>
    </div>
</form>
