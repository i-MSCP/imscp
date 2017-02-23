
<!-- BDP: protected_areas -->
<script>
    $(function () {
        $('.datatable').dataTable(
            {
                language: imscp_i18n.core.dataTable,
                stateSave: true,
                pagingType: "simple",
                columnDefs: [
                    {
                        type: "natural",
                        targets: [0, 1, 2]
                    }
                ]
            }
        );

        $(".i_delete").on('click', function () {
            return jQuery.imscp.confirmOnclick(this, sprintf(imscp_i18n.core.deletion_confirm_msg, $(this).data("name")));
        });
    });
</script>
<table class="datatable">
    <thead>
    <tr>
        <th>{TR_NAME}</th>
        <th>{TR_PATH}</th>
        <th>{TR_STATUS}</th>
        <th>{TR_ACTIONS}</th>
    </tr>
    </thead>
    <tbody>
    <!-- BDP: dir_item -->
    <tr>
        <td>{AREA_NAME}</td>
        <td>{AREA_PATH}</td>
        <td>{STATUS}</td>
        <td>
            <!-- BDP: action_links -->
            <a href="protected_areas_add.php?id={ID}" class="icon i_edit">{TR_EDIT}</a>
            <a href="protected_areas_delete.php?id={ID}" data-name="{DATA_AREA_NAME}"
               class="icon i_delete">{TR_DELETE}</a>
            <!-- EDP: action_links -->
        </td>
    </tr>
    <!-- EDP: dir_item -->
    </tbody>
</table>
<!-- EDP: protected_areas -->
<div class="buttons">
    <a class="link_as_button" href="protected_areas_add.php">{TR_ADD_PROTECTED_AREA}</a>
    <a class="link_as_button" href="protected_user_manage.php">{TR_MANAGE_USERS_AND_GROUPS}</a>
</div>
