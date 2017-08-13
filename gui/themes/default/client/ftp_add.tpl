
<script>
    $(function () {
        $('#domain_type').change(function () {
            $.post(
                "ftp_add.php",
                { "domain_type": this.value },
                function (data) {
                    var select = $("#domain_name");
                    select.empty();
                    for (var i = 0; i < data.length; i++) {
                        select.append('<option value="' + data[i].domain_name_val + '">' + data[i].domain_name + '</option>');
                    }
                },
                "json"
            );
        });
    });
</script>
<form name="add_ftp_account_frm" method="post" action="ftp_add.php" autocomplete="off">
    <table>
        <thead>
        <tr>
            <th colspan="2">{TR_FTP_ACCOUNT_DATA}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><label for="domain_type">{TR_DOMAIN_TYPE_LABEL}</label></td>
            <td>
                <select id="domain_type" name="domain_type">
                    <!-- BDP: domain_types -->
                    <option value="{DOMAIN_TYPE}"{DOMAIN_TYPE_SELECTED}>{TR_DOMAIN_TYPE}</option>
                    <!-- EDP: domain_types -->
                </select>
            </td>
        </tr>
        <tr>
            <td><label for="username">{TR_USERNAME}</label></td>
            <td>
                <input type="text" id="username" name="username" value="{USERNAME}">
                <label for="domain_name">@</label>
                <select id="domain_name" name="domain_name">
                    <!-- BDP: domain_list -->
                    <option value="{DOMAIN_NAME_VAL}"{DOMAIN_NAME_SELECTED}>{DOMAIN_NAME}</option>
                    <!-- EDP: domain_list -->
                </select>

            </td>
        </tr>
        <tr>
            <td><label for="password">{TR_PASSWORD}</label></td>
            <td><input type="password" id="password" name="password" value="" class="pwd_generator pwd_prefill" autocomplete="new-password"></td>
        </tr>
        <tr>
            <td><label for="cpassword">{TR_PASSWORD_REPEAT}</label></td>
            <td><input type="password" id="cpassword" name="password_repeat" value="" autocomplete="new-password"></td>
        </tr>
        <tr>
            <td><label for="ftp_directory">{TR_HOME_DIR}</label></td>
            <td>
                <input type="text" id="ftp_directory" name="home_dir" value="{HOME_DIR}">
                <span class="icon i_bc_folder ftp_choose_dir clickable">{TR_CHOOSE_DIR}</span>
            </td>
        </tr>
        </tbody>
    </table>
    <div class="buttons">
        <input name="Submit" type="submit" value="{TR_ADD}">
        <a class="link_as_button" href="ftp_accounts.php">{TR_CANCEL}</a>
    </div>
</form>
