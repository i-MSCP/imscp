
<form name="sql_change_password_frm" method="post" action="sql_change_password.php?id={ID}" autocomplete="off">
    <table class="firstColFixed">
        <tr>
            <td><label for="user">{TR_DB_USER}</label></td>
            <td><input id="user" type="text" name="user" value="{USER_NAME}" readonly="readonly"></td>
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
    </table>
    <div class="buttons">
        <input type="hidden" name="uaction" value="change_pass">
        <input type="hidden" name="id" value="{ID}">
        <input name="Submit" type="submit" value="{TR_CHANGE}">
        <a class="link_as_button" href="sql_manage.php">{TR_CANCEL}</a>
    </div>
</form>
