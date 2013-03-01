<form name="passwordUpdate" method="post" action="password_update.php">
    <table>
        <tr>
            <th colspan="2">{TR_PASSWORD_DATA}</th>
        </tr>
        <tr>
            <td style="width: 300px;"><label for="currentPassword">{TR_CURRENT_PASSWORD}</label></td>
            <td><input id="currentPassword" name="current_password" type="password" value=""/></td>
        </tr>
        <tr>
            <td><label for="password">{TR_PASSWORD}</label></td>
            <td><input name="password" id="password" type="password" value=""/></td>
        </tr>
        <tr>
            <td><label for="passwordConfirmation">{TR_PASSWORD_CONFIRMATION}</label></td>
            <td><input name="password_confirmation" id="passwordConfirmation" type="password" value=""/></td>
        </tr>
    </table>
    <div class="buttons">
        <input type="submit" name="Submit" value="{TR_UPDATE}"/>
    </div>
</form>
