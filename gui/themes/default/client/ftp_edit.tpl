
<form name="edit_ftp_account_frm" method="post" action="ftp_edit.php?id={ID}" autocomplete="off">
    <table>
        <thead>
        <tr>
            <th colspan="2">{TR_FTP_USER_DATA}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><label for="username">{TR_USERNAME}</label></td>
            <td><input id="username" type="text" name="username" value="{USERNAME}" disabled="disabled"></td>
        </tr>
        <tr>
            <td><label for="password">{TR_PASSWORD}</label></td>
            <td><input id="password" type="password" name="password" class="pwd_generator" value="" autocomplete="new-password">
            </td>
        </tr>
        <tr>
            <td><label for="cpassword">{TR_PASSWORD_REPEAT}</label></td>
            <td><input id="cpassword" type="password" name="password_repeat" value="" autocomplete="new-password"></td>
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
        <input name="Submit" type="submit" value="{TR_CHANGE}">
        <a class="link_as_button" href="ftp_accounts.php">{TR_CANCEL}</a>
    </div>
</form>
