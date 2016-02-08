
<!-- BDP: hosting_plans -->
<table>
    <thead>
    <tr>
        <th>{TR_NUMBER}</th>
        <th>{TR_NAME}</th>
        <th>{TR_STATUS}</th>
        <th>{TR_ACTION}</th>
    </tr>
    </thead>
    <tbody>
    <!-- BDP: hosting_plan -->
    <tr>
        <td>{NUMBER}</td>
        <td>{NAME}</td>
        <td>{STATUS}</td>
        <td>
            <a href="hosting_plan_edit.php?id={ID}" class="icon i_edit">{TR_EDIT}</a>
            <a href="hosting_plan_delete.php?id={ID}" onclick="return action_delete('{NAME}')" class="icon i_delete">{TR_DELETE}</a>
        </td>
    </tr>
    <!-- EDP: hosting_plan -->
    </tbody>
</table>

<script>
    function action_delete(subject) {
        return confirm(sprintf(imscp_i18n.core.hp_delete_confirmation, subject));
    }
</script>
<!-- EDP: hosting_plans -->
