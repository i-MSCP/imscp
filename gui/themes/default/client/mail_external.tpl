
<script>
    $(function () {
        $('.datatable').dataTable(
            {
                language: imscp_i18n.core.datatable,
                displayLength: 10,
                stateSave: true,
                pagingType: "simple"
            }
        );
    });
</script>
<p class="hint" style="font-variant: small-caps;font-size: small;">{TR_INTRO}</p>
<br>
<table class="firstColFixed datatable">
    <thead>
    <tr>
        <th>{TR_DOMAIN}</th>
        <th>{TR_STATUS}</th>
        <th>{TR_ACTION}</th>
    </tr>
    </thead>
    <tfoot>
    <tr>
        <td>{TR_DOMAIN}</td>
        <td>{TR_STATUS}</td>
        <td>{TR_ACTION}</td>
    </tr>
    </tfoot>
    <tbody>
    <!-- BDP: item -->
    <tr>
        <td>{DOMAIN}</td>
        <td>{STATUS}</td>
        <td>
            <!-- BDP: activate_link -->
            <a href="?action=activate&domain_id={DOMAIN_ID}&domain_type={DOMAIN_TYPE}" class="icon i_open">{TR_ACTIVATE}</a>
            <!-- EDP: activate_link -->
            <!-- BDP: deactivate_link -->
            <a href="?action=deactivate&domain_id={DOMAIN_ID}&domain_type={DOMAIN_TYPE}" class="icon i_close">{TR_DEACTIVATE}</a>
            <!-- EDP: deactivate_link -->
        </td>
    </tr>
    <!-- EDP: item -->
    </tbody>
</table>
