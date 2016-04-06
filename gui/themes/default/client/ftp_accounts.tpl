
<script>
    $(function() {
        $('.datatable').dataTable(
                {
                    language: imscp_i18n.core.dataTable,
                    stateSave: true,
                    pagingType: "simple",
                    columnDefs: [
                        { type: "natural", targets: [1] }
                    ]
                }
        );

        $(".i_delete").on('click', function(){
           return confirm(sprintf(imscp_i18n.core.deletion_confirm_msg, $(this).data('userid')))
        });
    })
</script>

<!-- BDP: ftp_accounts -->
<table class="datatable">
    <thead>
    <tr>
        <th>{TR_FTP_ACCOUNT}</th>
        <th>{TR_FTP_ACCOUNT_STATUS}</th>
        <th>{TR_FTP_ACTIONS}</th>
    </tr>
    </thead>
    <!-- BDP: ftps_total -->
    <tfoot>
    <tr>
        <td>{TR_FTP_ACCOUNT}</td>
        <td>{TR_FTP_ACCOUNT_STATUS}</td>
        <td>{TR_FTP_ACTIONS}</td>
    </tr>
    </tfoot>
    <!-- EDP: ftps_total -->
    <tbody>
    <!-- BDP: ftp_item -->
    <tr>
        <td>{FTP_ACCOUNT}</td>
        <td>{FTP_ACCOUNT_STATUS}</td>
        <td>
            <!-- BDP: ftp_actions -->
            <!-- BDP: ftp_easy_login -->
            <a href="ftp_auth.php?id={UID}" target="_blank" class="icon i_filemanager">{TR_LOGINAS}</a>
            <!-- EDP: ftp_easy_login -->
            <a href="ftp_edit.php?id={UID}" class="icon i_edit">{TR_EDIT}</a>
            <a href="ftp_delete.php?id={UID}" class="icon i_delete" data-userid="{UID}">{TR_DELETE}</a>
            <!-- EDP: ftp_actions -->
        </td>
    </tr>
    <!-- EDP: ftp_item -->
    </tbody>
</table>
<!-- EDP: ftp_accounts -->
