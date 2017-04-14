
<script>
    function flashMessage(type, message) {
        $("<div>", {
            "class": "flash_message " + type,
            "html": $.parseHTML(message),
            "hide": true
        }).prependTo(".body").trigger('message_timeout');
    }

    $(function () {
        var $select = $("#selected_domain");
        $select.data("current", $select.val()).on("change", function () {
            var curVal = $(this).val();
            var data = curVal.split(';');

            $.post(window.location.href, {
                domain_id: data[0],
                domain_type: data[1]
            }, null, "json").done(function (data) {
                console.log(data.document_root);
                $("#document_root").html(data.document_root);
                $select.data("current", curVal);
            }).fail(function (jqXHR) {
                if (jqXHR.status == 403) window.location.replace("/index.php");
                $select.val($select.data("current"));
                flashMessage("error", jqXHR.responseJSON.message);
            });
        });
    });
</script>
<form method="post" action="{SOFTWARE_INSTALL_BUTTON}" autocomplete="off">
    <table class="firstColFixed">
        <thead>
        <tr>
            <th colspan="2">{TR_INSTALLATION}</th>
        </tr>
        </thead>
        <tbody>
        <!-- BDP: software_item -->
        <tr>
            <td>{TR_NAME}</td>
            <td>{TR_SOFTWARE_NAME}</td>
        </tr>
        <tr>
            <td>{TR_TYPE}</td>
            <td>{SOFTWARE_TYPE}</td>
        </tr>
        <tr>
            <td>{TR_DB}</td>
            <td>{SOFTWARE_DB}</td>
        </tr>
        <tr>
            <td><label for="selected_domain">{TR_SELECT_DOMAIN}</label></td>
            <td>
                <select name="selected_domain" id="selected_domain">
                    <!-- BDP: show_domain_list -->
                    <option value="{DOMAIN_NAME_VALUES}"{SELECTED_DOMAIN}>{DOMAIN_NAME}</option>
                    <!-- EDP: show_domain_list -->
                </select>
            </td>
        </tr>
        <tr>
            <td><label for="ftp_directory">{TR_PATH}</label></td>
            <td>
                <span class="bold" id="document_root">{DOCUMENT_ROOT}</span>
                <input type="text" id="ftp_directory" name="other_dir" class="textinput" placeholder="/" value="{VAL_OTHER_DIR}">
                <span class="icon i_bc_folder ftp_choose_dir clickable">{TR_CHOOSE_DIR}</span>
            </td>
        </tr>
    </table>
    <!-- BDP: require_installdb -->
    <table class="firstColFixed">
        <thead>
        <tr>
            <th colspan="2">{TR_DATABASE_DATA}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><label for="database_name">{TR_DATABASE_NAME}</label></td>
            <td><input type="text" id="database_name" name="database_name" value="{VAL_DATABASE_NAME}"></td>
        </tr>
        <tr>
            <td><label for="database_user">{TR_DATABASE_USER}</label></td>
            <td><input type="text" id="database_user" name="database_user" value="{VAL_DATABASE_USER}"></td>
        </tr>
        <tr>
            <td><label for="database_pwd">{TR_DATABASE_PWD}</label></td>
            <td><input type="password" id="database_pwd" name="database_pwd" autocomplete="new-password"></td>
        </tr>
        </tbody>
    </table>
    <!-- EDP: require_installdb -->
    <table class="firstColFixed">
        <thead>
        <tr>
            <th colspan="2">{TR_INSTALLATION_INFORMATION}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><label for="install_username">{TR_INSTALL_USER}</label></td>
            <td><input type="text" name="install_username" id="install_username" value="{VAL_INSTALL_USERNAME}"></td>
        </tr>
        <tr>
            <td><label for="password">{TR_INSTALL_PWD}</label></td>
            <td>
                <input type="password" name="install_password" id="password" value="{VAL_INSTALL_PASSWORD}" class="pwd_generator pwd_prefill" autocomplete="new-password">
            </td>
        </tr>
        <tr>
            <td><label for="install_email">{TR_INSTALL_EMAIL}</label></td>
            <td><input type="text" name="install_email" id="install_email" value="{VAL_INSTALL_EMAIL}"></td>
        </tr>
        <!-- EDP: software_item -->
        </tbody>
    </table>
    <div class="buttons">
        <!-- BDP: software_install -->
        <input name="Submit" type="submit" value="{TR_INSTALL}">
        <!-- EDP: software_install -->
        <a class="link_as_button" href="software.php">{TR_CANCEL}</a>
    </div>
</form>
