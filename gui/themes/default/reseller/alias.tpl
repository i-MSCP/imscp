
<script>
    $(function () {
        var $dataTable = $(".datatable").dataTable({
            language: imscp_i18n.core.dataTable,
            displayLength: 10,
            processing: true,
            serverSide: true,
            ajaxSource: "alias.php?action=get_table",
            stateSave: true,
            pagingType: "simple",
            columnDefs: [
                {sortable: false, searchable: false, targets: [4]},
                {sortable: false, searchable: false, targets: [5]}
            ],
            columns: [
                {data: "alias_name"}, {data: "alias_mount"}, {data: "url_forward"}, {data: "admin_name"},
                {data: "alias_status"}, {data: "actions"}
            ],
            fnServerData: function (source, data, callback) {
                $.ajax({
                    dataType: "json",
                    type: "GET",
                    url: source,
                    data: data,
                    success: callback,
                    timeout: 5000
                }).done(function () {
                    $dataTable.find("a").tooltip({tooltipClass: "ui-tooltip-notice", track: true});
                });
            }
        });
    });

    function delete_alias(link, name) {
        return jQuery.imscp.confirmOnclick(link, sprintf("{TR_MESSAGE_DELETE_ALIAS}", name));
    }

    function delete_alias_order(link, name) {
        return jQuery.imscp.confirmOnclick(link, sprintf("{TR_MESSAGE_DELETE_ALIAS_ORDER}", name));
    }
</script>
<table class="datatable">
    <thead>
    <tr>
        <th>{TR_ALIAS_NAME}</th>
        <th>{TR_MOUNT_POINT}</th>
        <th>{TR_FORWARD_URL}</th>
        <th>{TR_CUSTOMER}</th>
        <th>{TR_STATUS}</th>
        <th>{TR_ACTIONS}</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td colspan="6" class="dataTables_empty">{TR_PROCESSING_DATA}</td>
    </tr>
    </tbody>
</table>
<!-- BDP: als_add_button -->
<div style="float:right;">
    <a class="link_as_button" href="alias_add.php">{TR_ADD_DOMAIN_ALIAS}</a>
</div>
<!-- EDP: als_add_button -->
