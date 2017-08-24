<form method="post" action="sql_change_password.php?sqlu_id={SQLU_ID}" autocomplete="off">
    <table class="firstColFixed">
        <thead>
        <th colspan="2">{TR_SQL_USER_PASSWORD}</th>
        </thead>
        <tbody>
        <tr>
            <td><label for="user">{TR_DB_USER}</label></td>
            <td><input id="user" type="text" name="user" value="{USER_NAME}" readonly></td>
        </tr>
        <tr>
            <td><label for="password">{TR_PASSWORD}</label></td>
            <td><input id="password" type="password" name="password" value="" class="pwd_generator" autocomplete="new-password">
            </td>
        </tr>
        <tr>
            <td><label for="cpassword">{TR_PASSWORD_CONFIRMATION}</label></td>
            <td><input id="cpassword" type="password" name="password_confirmation" value="" autocomplete="new-password"></td>
        </tr>
        </tbody>
    </table>
    <div class="buttons">
        <input type="hidden" name="sqlu_id" value="{SQLU_ID}">
        <input name="Submit" type="submit" value="{TR_UPDATE}">
        <a class="link_as_button" href="sql_manage.php">{TR_CANCEL}</a>
    </div>
</form>
