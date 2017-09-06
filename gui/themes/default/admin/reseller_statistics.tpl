
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
        <th>{TR_RESELLER_NAME}</th>
        <th>{TR_DISK_USAGE}</th>
        <th>{TR_TRAFFIC_USAGE}</th>
        <th>{TR_DOMAINS}</th>
        <th>{TR_SUBDOMAINS}</th>
        <th>{TR_DOMAIN_ALIASES}</th>
        <th>{TR_MAIL_ACCOUNTS}</th>
        <th>{TR_FTP_ACCOUNTS}</th>
        <th>{TR_SQL_DATABASES}</th>
        <th>{TR_SQL_USERS}</th>
    </tr>
    </thead>
    <tfoot>
    <tr>
        <td>{TR_RESELLER_NAME}</td>
        <td>{TR_DISK_USAGE}</td>
        <td>{TR_TRAFFIC_USAGE}</td>
        <td>{TR_DOMAINS}</td>
        <td>{TR_SUBDOMAINS}</td>
        <td>{TR_DOMAIN_ALIASES}</td>
        <td>{TR_MAIL_ACCOUNTS}</td>
        <td>{TR_FTP_ACCOUNTS}</td>
        <td>{TR_SQL_DATABASES}</td>
        <td>{TR_SQL_USERS}</td>
    </tr>
    </tfoot>
    <tbody>
    <!-- BDP: reseller_statistics_block -->
    <tr>
        <td>
            <a href="reseller_user_statistics.php?reseller_id={RESELLER_ID}" title="{TR_DETAILED_STATS_TOOLTIPS}" class="icon i_domain_icon">{RESELLER_NAME}</a>
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
        <td>{DMN_MSG}</td>
        <td>{SUB_MSG}</td>
        <td>{ALS_MSG}</td>
        <td>{MAIL_MSG}</td>
        <td>{FTP_MSG}</td>
        <td>{SQL_DB_MSG}</td>
        <td>{SQL_USER_MSG}</td>
    </tr>
    <!-- EDP: reseller_statistics_block -->
    </tbody>
</table>
