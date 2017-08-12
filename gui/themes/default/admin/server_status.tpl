<script>
    $(function () {
        $('.datatable').dataTable(
            {
                language: imscp_i18n.core.dataTable,
                displayLength: 10,
                stateSave: true,
                pagingType: "simple"
            }
        );
    });
</script>
<table class="datatable firstColFixed">
    <thead>
    <tr>
        <th>{TR_SERVICE}</th>
        <th>{TR_IP}</th>
        <th>{TR_PORT}</th>
        <th>{TR_STATUS}</th>
    </tr>
    </thead>
    <tbody>
    <!-- BDP: service_status -->
    <tr>
        <td>{SERVICE}</td>
        <td>{IP}</td>
        <td>{PORT}</td>
        <td class="{CLASS}"><span class="tips" title="{STATUS_TOOLTIP}"><strong>{STATUS}</strong></span></td>
    </tr>
    <!-- EDP: service_status -->
    </tbody>
    <tbody>
    <tr style="background-color:#b0def5">
        <td colspan="4" class="buttons">
            <button type="button" onclick="window.location.href = window.location.href.replace(/[\?#].*|$/, '?refresh');">{TR_FORCE_REFRESH}</button>
        </td>
    </tr>
    </tbody>
</table>
