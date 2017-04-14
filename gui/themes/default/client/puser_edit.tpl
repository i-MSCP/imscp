
<form name="editHtaccessUserFrm" method="post" action="protected_user_edit.php" autocomplete="off">
    <table>
        <thead>
        <tr>
            <th colspan="2">{TR_HTACCESS_USER}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>{TR_USERNAME}</td>
            <td>{UNAME}</td>
        </tr>
        <tr>
            <td><label for="password">{TR_PASSWORD}</label></td>
            <td><input type="password" id="password" name="pass" value="" class="pwd_generator" autocomplete="new-password">
            </td>
        </tr>
        <tr>
            <td><label for="cpassword">{TR_PASSWORD_REPEAT}</label></td>
            <td><input type="password" id="cpassword" name="pass_rep" value="" autocomplete="new-password"></td>
        </tr>
        </tbody>
    </table>
    <div class="buttons">
        <input type="hidden" name="uname" value="{UID}">
        <input name="Submit" type="submit" value="{TR_UPDATE}">
        <a class="link_as_button" href="protected_user_manage.php">{TR_CANCEL}</a>
    </div>
</form>
