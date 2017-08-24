<!-- BDP: sql_databases_users_list -->
<script>
    function action_delete(url, subject, object) {
        var msg;
        if (object == 'database') {
            msg = "{TR_DATABASE_MESSAGE_DELETE}"
        } else {
            msg = "{TR_USER_MESSAGE_DELETE}"
        }

        return jQuery.imscp.confirm(sprintf(msg, subject), function(ret) {
            if(ret) {
                window.location.href = url;
            }
        });
    }
</script>
<table class="firstColFixed">
    <thead>
    <tr>
        <th>{TR_DATABASE}</th>
        <th>{TR_ACTIONS}</th>
    </tr>
    </thead>
    <tbody>
    <!-- BDP: sql_databases_list -->
    <tr>
        <td><strong>{DB_NAME}</strong></td>
        <td>
            <!-- BDP: sql_user_add_link -->
            <a href="sql_user_add.php?sqld_id={SQLD_ID}" class="icon i_add_user">{TR_ADD_USER}</a>
            <!-- EDP: sql_user_add_link -->
            <a href="#" class="icon i_delete" onclick="return action_delete('sql_database_delete.php?sqld_id={SQLD_ID}', '{DB_NAME_JS}', 'database')">{TR_DELETE}</a>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <!-- BDP: sql_users_list -->
            <table>
                <tr>
                    <td>
                        <div>
                            <div style="float:left;clear:left;width: 100px;text-align: left;font-weight: bold">{TR_DB_USER}</div>
                            <div style="display: inline-block;float: left">{DB_USER}</span></div>
                            <div style="float:left;clear: left;width: 100px;text-align: left;font-weight: bold">
                                {TR_DB_USER_HOST}
                                <span style="" class="icon i_help" title="{TR_DB_USER_HOST_TOOLTIP}"></span>
                            </div>
                            <div style="display: inline-block;float: left">{DB_USER_HOST}</div>
                        </div>
                    </td>
                    <td>
                        <a href="sql_change_password.php?sqlu_id={SQLU_ID}" class="icon i_change_password">{TR_CHANGE_PASSWORD}</a>
                        <a href="#" class="icon i_delete" onclick="return action_delete('sql_delete_user.php?sqlu_id={SQLU_ID}', '{DB_USER_JS}', 'user')">{TR_DELETE}</a>
                    </td>
                </tr>
            </table>
            <!-- EDP: sql_users_list -->
        </td>
    </tr>
    <!-- EDP: sql_databases_list -->
    </tbody>
</table>
<!-- EDP: sql_databases_users_list -->
