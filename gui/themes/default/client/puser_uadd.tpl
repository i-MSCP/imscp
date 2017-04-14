
<form name="addHtaccessUserFrm" method="post" action="protected_user_add.php" autocomplete="off">
    <table class="firstColFixed">
        <thead>
        <tr>
            <th colspan="2">{TR_HTACCESS_USER}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><label for="username">{TR_USERNAME}</label></td>
            <td><input name="username" id="username" type="text" value="{USERNAME}"></td>
        </tr>
        <tr>
            <td><label for="password">{TR_PASSWORD}</label></td>
            <td><input type="password" id="password" name="pass" value="" class="pwd_generator pwd_prefill" autocomplete="new-password"></td>
        </tr>
        <tr>
            <td><label for="cpassword">{TR_PASSWORD_REPEAT}</label></td>
            <td><input type="password" id="cpassword" name="pass_rep" value="" autocomplete="new-password"></td>
        </tr>
        </tbody>
    </table>
    <div class="buttons">
        <input name="Submit" type="submit" value="{TR_ADD_USER}">
        <a class="link_as_button" href="protected_user_manage.php">{TR_CANCEL}</a>
    </div>
</form>
