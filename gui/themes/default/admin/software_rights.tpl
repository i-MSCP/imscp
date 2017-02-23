
<script>
    function action_remove_right(link) {
        return jQuery.imscp.confirmOnclick(link, "{TR_MESSAGE_REMOVE}");
    }
</script>
<!-- BDP: no_select_reseller -->
<div class="static_info">{NO_RESELLER_AVAILABLE}</div>
<!-- EDP: no_select_reseller -->
<!-- BDP: no_reseller_list -->
<div class="static_info">{NO_RESELLER}</div>
<!-- EDP: no_reseller_list -->
<table>
    <thead>
    <tr>
        <th>{TR_RESELLER}</th>
        <th>{TR_ADDED_BY}</th>
        <th>{TR_REMOVE_RIGHTS}</th>
    </tr>
    </thead>
    <tfoot>
    <tr>
        <td colspan="3">{TR_RESELLER_COUNT}: {TR_RESELLER_NUM}</td>
    </tr>
    </tfoot>
    <tbody>
    <!-- BDP: select_reseller -->
    <tr>
        <td colspan="3">
            <form action="software_change_rights.php" method="post">
                <label style="display: inline-block;">
                    <select name="selected_reseller" id="selected_reseller">
                        <option value="all">{ALL_RESELLER_NAME}</option>
                        <!-- BDP: reseller_item -->
                        <option value="{RESELLER_ID}">{RESELLER_NAME}</option>
                        <!-- EDP: reseller_item -->
                    </select>
                </label>
                <div style="display: inline-block;">
                    <input name="Submit" type="submit" value="{TR_ADD_RIGHTS_BUTTON}">
                    <input type="hidden" value="add" name="change">
                    <input type="hidden" value="{SOFTWARE_ID_VALUE}" name="id">
                </div>
            </form>
        </td>
    </tr>
    <!-- EDP: select_reseller -->
    <!-- BDP: list_reseller -->
    <tr>
        <td>{RESELLER}</td>
        <td>{ADMINISTRATOR}</td>
        <td>
            <span class="icon i_delete"><a href="{REMOVE_RIGHT_LINK}" onClick="return action_remove_right(this)">{TR_REMOVE_RIGHT}</a></span>
        </td>
    </tr>
    <!-- EDP: list_reseller -->
    </tbody>
</table>
