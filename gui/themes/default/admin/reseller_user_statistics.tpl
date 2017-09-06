
<script>
    $(function () {
        $('.datatable').dataTable({
            language: imscp_i18n.core.dataTable,
            stateSave: true,
            pagingType: "simple"
        });
    });
</script>
<table class="datatable">
    <thead>
    <tr>
        <th>{TR_USERNAME}</th>
        <th>{TR_DISK}</th>
        <th>{TR_TRAFF}</th>
        <th>{TR_WEB}</th>
        <th>{TR_FTP_TRAFF}</th>
        <th>{TR_SMTP}</th>
        <th>{TR_POP3}</th>
        <th>{TR_SUBDOMAIN}</th>
        <th>{TR_ALIAS}</th>
        <th>{TR_MAIL}</th>
        <th>{TR_FTP}</th>
        <th>{TR_SQL_DB}</th>
        <th>{TR_SQL_USER}</th>
    </tr>
    </thead>
    <tfoot>
    <tr>
        <td>{TR_USERNAME}</td>
        <td>{TR_DISK}</td>
        <td>{TR_TRAFF}</td>
        <td>{TR_WEB}</td>
        <td>{TR_FTP_TRAFF}</td>
        <td>{TR_SMTP}</td>
        <td>{TR_POP3}</td>
        <td>{TR_SUBDOMAIN}</td>
        <td>{TR_ALIAS}</td>
        <td>{TR_MAIL}</td>
        <td>{TR_FTP}</td>
        <td>{TR_SQL_DB}</td>
        <td>{TR_SQL_USER}</td>
    </tr>
    </tfoot>
    <tbody>
    <!-- BDP: reseller_user_statistics_block -->
    <tr>
        <td>
            <a href="reseller_user_statistics_details.php?user_id={USER_ID}" class="icon i_domain_icon" title="{TR_DETAILED_STATS_TOOLTIP}">{USER_NAME}</a>
        </td>
        <td>
            <div class="graph">
                <span style="width:{DISK_PERCENT_WIDTH}%"></span>
                <strong>{DISK_PERCENT}%</strong>
            </div>
            {DISK_MSG}
        </td>
        <td>
            <div class="graph">
                <span style="width:{TRAFFIC_PERCENT_WIDTH}%"></span>
                <strong>{TRAFFIC_PERCENT}%</strong>
            </div>
            {TRAFFIC_MSG}
        </td>
        <td>{WEB}</td>
        <td>{FTP}</td>
        <td>{SMTP}</td>
        <td>{POP3}</td>
        <td>{SUB_MSG}</td>
        <td>{ALS_MSG}</td>
        <td>{MAIL_MSG}</td>
        <td>{FTP_MSG}</td>
        <td>{SQL_DB_MSG}</td>
        <td>{SQL_USER_MSG}</td>
    </tr>
    <!-- EDP: reseller_user_statistics_block -->
    </tbody>
</table>
