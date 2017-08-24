<!-- BDP: show_sqluser_list -->
<form method="post" action="sql_user_add.php">
    <table class="firstColFixed">
        <thead>
        <tr>
            <th colspan="2">{TR_ASSIGN_EXISTING_SQL_USER}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><label for="sqluser_id">{TR_SQL_USER_NAME}</label></td>
            <td>
                <!--email_off-->
                <select name="sqluser_id" id="sqluser_id">
                    <!-- BDP: sqluser_list -->
                    <option value="{SQLUSER_ID}">{SQLUSER_IDN}</option>
                    <!-- EDP: sqluser_list -->
                </select>
                <!--/email_off-->
            </td>
        </tr>
        </tbody>
    </table>
    <div class="buttons">
        <input type="hidden" name="uaction" value="add_user">
        <input type="hidden" name="sqld_id" value="{SQLD_ID}">
        <input name="reuse_sqluser" type="submit" id="reuse_sqluser" value="{TR_ADD_EXIST}">
        <a class="link_as_button" href="sql_manage.php">{TR_CANCEL}</a>
    </div>
</form>
<!-- EDP: show_sqluser_list -->
<!-- BDP: create_sqluser -->
<form method="post" action="sql_user_add.php" autocomplete="off">
    <table class="firstColFixed">
        <thead>
        <tr>
            <th colspan="2">{TR_NEW_SQL_USER_DATA}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><label for="user_name">{TR_USER_NAME}</label></td>
            <td><input type="text" id="user_name" name="user_name" value="{USER_NAME}"></td>
        </tr>
        <tr>
            <td><label for="user_host">{TR_USER_HOST} <span class="icon i_help" title="{TR_USER_HOST_TIP}"></span></label></td>
            <td><input type="text" id="user_host" name="user_host" value="{USER_HOST}"></td>
        </tr>
        <tr>
            <td>
                <!-- BDP: mysql_prefix_yes -->
                <label><input type="checkbox" name="use_dmn_id" {USE_DMN_ID}></label>
                <!-- EDP: mysql_prefix_yes -->
                <!-- BDP: mysql_prefix_no -->
                <input type="hidden" name="use_dmn_id" value="on">
                <!-- EDP: mysql_prefix_no -->
                {TR_USE_DMN_ID}
            </td>
            <td>
                <!-- BDP: mysql_prefix_all -->
                <label>
                    <select name="id_pos">
                        <option value="start"{START_ID_POS_SELECTED}>{TR_START_ID_POS}</option>
                        <option value="end"{END_ID_POS_SELECTED}>{TR_END_ID_POS}</option>
                    </select>
                </label>
                <!-- EDP: mysql_prefix_all -->
                <!-- BDP: mysql_prefix_infront -->
                <input type="hidden" name="id_pos" value="start" checked>{TR_START_ID_POS}
                <!-- EDP: mysql_prefix_infront -->
                <!-- BDP: mysql_prefix_behind -->
                <input type="hidden" name="id_pos" value="end" checked>{TR_END_ID_POS}
                <!-- EDP: mysql_prefix_behind -->
            </td>
        </tr>
        <tr>
            <td><label for="password">{TR_PASS}</label></td>
            <td><input id="password" type="password" name="pass" value="" class="pwd_generator pwd_prefill" autocomplete="new-password"></td>
        </tr>
        <tr>
            <td><label for="cpassword">{TR_PASS_REP}</label></td>
            <td><input id="cpassword" type="password" name="pass_rep" value="" autocomplete="new-password"></td>
        </tr>
        </tbody>
    </table>
    <div class="buttons">
        <input type="hidden" name="uaction" value="add_user">
        <input type="hidden" name="sqld_id" value="{SQLD_ID}">
        <input name="Add_New" type="submit" id="Add_New" value="{TR_ADD}">
        <a class="link_as_button" href="sql_manage.php">{TR_CANCEL}</a>
    </div>
</form>
<!-- EDP: create_sqluser -->
