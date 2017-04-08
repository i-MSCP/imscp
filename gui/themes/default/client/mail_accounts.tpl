
<!-- BDP: mail_feature -->
<script>
    $(function () {
        var $oTable = $('.datatable').dataTable(
            {
                language: imscp_i18n.core.dataTable,
                displayLength: 10,
                stateSave: true,
                columnDefs: [ { sortable: false, targets: [5] }],
                pagingType: "simple"
            }
        );

        $(".dataTables_paginate").click(function () {
            if ($oTable.find("tbody input[type=checkbox]:checked").length == $("tbody input[type=checkbox]:not(':disabled')").length) {
                $oTable.find("thead input[type=checkbox],tfoot input[type=checkbox]").prop('checked', true);
            } else {
                $oTable.find("thead input[type=checkbox],tfoot input[type=checkbox]").prop('checked', false);
            }
        });

        $oTable.find("tbody").on("click", "input[type=checkbox]:not(':disabled')", function () {
            if ($(this).find("input[type=checkbox]:checked").length == $("tbody input[type=checkbox]:not(':disabled')").length) {
                $oTable.find("thead input[type=checkbox],tfoot input[type=checkbox]").prop('checked', true);
            } else {
                $oTable.find("thead input[type=checkbox],tfoot input[type=checkbox]").prop('checked', false);
            }
        });

        $oTable.find("thead :checkbox, tfoot input[type=checkbox]").click(function (e) {
            if ($oTable.find("tbody input[type=checkbox]:not(':disabled')").length != 0) {
                $oTable.find("input[type=checkbox]:not(':disabled')").prop('checked', $(this).is(':checked'));
            } else {
                e.preventDefault();
            }
        });

        $("input[type=submit]").click(function () {
            var input = this;
            input.blur();

            if($("input[type=checkbox]:checked", $oTable.fnGetNodes()).length < 1) {
                alert("{TR_MESSAGE_DELETE_SELECTED_ITEMS_ERR}");
                return false;
            }

            return jQuery.imscp.confirm("{TR_MESSAGE_DELETE_SELECTED_ITEMS}", function(ret) {
                if(ret) {
                    $(input).closest("form").submit();
                }
            });
        });
    });

    function action_delete(link, subject) {
        return jQuery.imscp.confirmOnclick(link, sprintf("{TR_MESSAGE_DELETE}", subject));
    }
</script>
<!-- BDP: mail_items -->
<form action="mail_delete.php" method="post">
    <table class="datatable">
        <thead>
        <tr>
            <th>{TR_MAIL}</th>
            <th>{TR_TYPE}</th>
            <th>{TR_QUOTA_INFO}</th>
            <th>{TR_STATUS}</th>
            <th>{TR_ACTIONS}</th>
            <th style="width:21px"><label><input type="checkbox"/></label></th>
        </tr>
        </thead>
        <tfoot>
        <tr>
            <td colspan="5">{TOTAL_MAIL_ACCOUNTS}</td>
            <td style="width:21px"><label><input type="checkbox"/></label></td>
        </tr>
        </tfoot>
        <tbody>
        <!-- BDP: mail_item -->
        <tr>
            <td>
                <span class="icon i_mail_icon">{MAIL_ADDR}</span>
                <!-- BDP: auto_respond_item -->
                <div>
                    {TR_AUTORESPOND}:
                    <a href="{AUTO_RESPOND_SCRIPT}" class="icon i_reload">{AUTO_RESPOND}</a>
                    <!-- BDP: auto_respond_edit_link -->
                    <a href="{AUTO_RESPOND_EDIT_SCRIPT}" class="icon i_edit">{AUTO_RESPOND_EDIT}</a>
                    <!-- EDP: auto_respond_edit_link -->
                </div>
                <!-- EDP: auto_respond_item -->
            </td>
            <td>{MAIL_TYPE}</td>
            <td>{MAIL_QUOTA_INFO}</td>
            <td>{MAIL_STATUS}</td>
            <td>
                <a href="{MAIL_EDIT_SCRIPT}" title="{MAIL_EDIT}" class="icon i_edit">{MAIL_EDIT}</a>
                <a href="{MAIL_DELETE_SCRIPT}" onclick="return action_delete(this, '{MAIL_ADDR}')" title="{MAIL_DELETE}" class="icon i_delete">{MAIL_DELETE}</a>
            </td>
            <td><label><input type="checkbox" name="id[]" value="{DEL_ITEM}"{DISABLED_DEL_ITEM}/></label></td>
        </tr>
        <!-- EDP: mail_item -->
        </tbody>
        <tbody>
        <tr>
            <td colspan="6">
                <div class="buttons">
                    <!-- BDP: sync_quota_info_link -->
                    <a href="/client/mail_accounts.php?sync_quota_info=1" title="{TR_SYNC_QUOTA_TOOLTIP}" class="link_as_button">{TR_SYNC_QUOTA_INFO}</a>
                    <!-- EDP: sync_quota_info_link -->
                    <input type="submit" name="Submit" value="{TR_DELETE_SELECTED_ITEMS}">
                </div>
            </td>
        </tr>
        </tbody>
    </table>
</form>
<!-- EDP: mail_items -->
<!-- EDP: mail_feature -->
