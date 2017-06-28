<script>
    function action_delete(link, recordType) {
        if(link.href == '#') return false;

        var msg;
        switch (recordType) {
            case 'als':
                msg = imscp_i18n.core.als_delete_alert;
                break;
            case 'sub':
                msg = imscp_i18n.core.sub_delete_alert;
                break;
            default:
                msg = imscp_i18n.core.dns_delete_alert;
        }

        return jQuery.imscp.confirmOnclick(link, msg);
    }

    $(function () {
        $(".datatable").dataTable({
            language: imscp_i18n.core.dataTable,
            displayLength: 10,
            stateSave: true,
            pagingType: "simple"
        });
    });
</script>
<h3 class="domains"><span>{TR_DOMAINS}</span></h3>
<!-- BDP: domain_list -->
<table class="firstColFixed">
    <thead>
    <tr>
        <th>{TR_NAME}</th>
        <th>{TR_MOUNT_POINT}</th>
        <th>{TR_DOCUMENT_ROOT}</th>
        <th>{TR_REDIRECT}</th>
        <th>{TR_STATUS}</th>
        <th>{TR_SSL_STATUS}</th>
        <th>{TR_ACTIONS}</th>
    </tr>
    </thead>
    <tbody>
    <!-- BDP: domain_item -->
    <tr>
        <!-- BDP: domain_status_reload_true -->
        <td>
            <a href="http://{DOMAIN_NAME}/" class="icon i_domain_icon" target="_blank" title="{DOMAIN_NAME}">{DOMAIN_NAME}</a>
            <!-- BDP: dmn_alt_url -->
            <a href="http://{ALTERNATE_URL}" target="_blank" title="{ALTERNATE_URL_TOOLTIP}"><small><strong>[{TR_ALT_URL}]</strong></small></a>
            <!-- EDP: dmn_alt_url -->
        </td>
        <!-- EDP: domain_status_reload_true -->
        <!-- BDP: domain_status_reload_false -->
        <td>
            <span class="tips icon i_domain_icon" title="{DOMAIN_NAME}">{DOMAIN_NAME}</span>
        </td>
        <!-- EDP: domain_status_reload_false -->
        <td>{DOMAIN_MOUNT_POINT}</td>
        <td>{DOMAIN_DOCUMENT_ROOT}</td>
        <td>{DOMAIN_REDIRECT}</td>
        <td>{DOMAIN_STATUS}</td>
        <td>{DOMAIN_SSL_STATUS}</td>
        <td>
            <a href="{CERT_SCRIPT}" class="icon i_edit" title="{VIEW_CERT}">{VIEW_CERT}</a>
            <a class="icon i_edit" href="{DOMAIN_EDIT_LINK}" title="{DOMAIN_EDIT}">{DOMAIN_EDIT}</a>
        </td>
    </tr>
    <!-- EDP: domain_item -->
    </tbody>
</table>
<!-- EDP: domains_list -->

<!-- BDP: domain_aliases_block -->
<h3 class="domains"><span>{TR_DOMAIN_ALIASES}</span></h3>
<!-- BDP: als_message -->
<div class="static_info">{ALS_MSG}</div>
<!-- EDP: als_message -->
<!-- BDP: als_list -->
<table class="firstColFixed datatable">
    <thead>
    <tr>
        <th>{TR_NAME}</th>
        <th>{TR_MOUNT_POINT}</th>
        <th>{TR_DOCUMENT_ROOT}</th>
        <th>{TR_REDIRECT}</th>
        <th>{TR_STATUS}</th>
        <th>{TR_SSL_STATUS}</th>
        <th>{TR_ACTIONS}</th>
    </tr>
    </thead>
    <tbody>
    <!-- BDP: als_item -->
    <tr>
        <!-- BDP: als_status_reload_true -->
        <td>
            <a href="http://{ALS_NAME}/" class="icon i_domain_icon" target="_blank" title="{ALS_NAME}">{ALS_NAME}</a>
            <!-- BDP: als_alt_url -->
            <a href="http://{ALTERNATE_URL}" target="_blank" title="{ALTERNATE_URL_TOOLTIP}"><small><strong>[{TR_ALT_URL}]</strong></small></a>
            <!-- EDP: als_alt_url -->
        </td>
        <!-- EDP: als_status_reload_true -->
        <!-- BDP: als_status_reload_false -->
        <td>
            <span class="tips icon i_domain_icon" title="{ALS_NAME}">{ALS_NAME}</span>
        </td>
        <!-- EDP: als_status_reload_false -->
        <td>{ALS_MOUNT_POINT}</td>
        <td>{ALS_DOCUMENT_ROOT}</td>
        <td>{ALS_REDIRECT}</td>
        <td>{ALS_STATUS}</td>
        <td>{ALS_SSL_STATUS}</td>
        <td>
            <a href="{CERT_SCRIPT}" class="icon i_edit" title="{VIEW_CERT}">{VIEW_CERT}</a>
            <a class="icon i_edit" href="{ALS_EDIT_LINK}" title="{ALS_EDIT}">{ALS_EDIT}</a>
            <a class="icon i_delete" href="{ALS_ACTION_SCRIPT}" onclick="return action_delete(this, 'als');" title="{ALS_ACTION}">{ALS_ACTION}</a>
        </td>
    </tr>
    <!-- EDP: als_item -->
    </tbody>
</table>
<!-- EDP: als_list -->
<!-- EDP: domain_aliases_block -->

<!-- BDP: subdomains_block -->
<h3 class="domains"><span>{TR_SUBDOMAINS}</span></h3>
<!-- BDP: sub_message -->
<div class="static_info">{SUB_MSG}</div>
<!-- EDP: sub_message -->
<!-- BDP: sub_list -->
<table class="firstColFixed datatable">
    <thead>
    <tr>
        <th>{TR_NAME}</th>
        <th>{TR_MOUNT_POINT}</th>
        <th>{TR_DOCUMENT_ROOT}</th>
        <th>{TR_REDIRECT}</th>
        <th>{TR_STATUS}</th>
        <th>{TR_SSL_STATUS}</th>
        <th>{TR_ACTIONS}</th>
    </tr>
    </thead>
    <tbody>
    <!-- BDP: sub_item -->
    <tr>
        <!-- BDP: sub_status_reload_true -->
        <td>
            <a href="http://{SUB_NAME}.{SUB_ALIAS_NAME}/" class="icon i_domain_icon" target="_blank" title="{SUB_NAME}.{SUB_ALIAS_NAME}">{SUB_NAME}.{SUB_ALIAS_NAME}</a>
            <!-- BDP: sub_alt_url -->
            <a href="http://{ALTERNATE_URL}" target="_blank" title="{ALTERNATE_URL_TOOLTIP}"><small><strong>[{TR_ALT_URL}]</strong></small></a>
            <!-- EDP: sub_alt_url -->
        </td>
        <!-- EDP: sub_status_reload_true -->
        <!-- BDP: sub_status_reload_false -->
        <td><span class="tips icon i_domain_icon" title="{SUB_NAME}.{SUB_ALIAS_NAME}">{SUB_NAME}.{SUB_ALIAS_NAME}</span></td>
        <!-- EDP: sub_status_reload_false -->
        <td>{SUB_MOUNT_POINT}</td>
        <td>{SUB_DOCUMENT_ROOT}</td>
        <td>{SUB_REDIRECT}</td>
        <td>{SUB_STATUS}</td>
        <td>{SUB_SSL_STATUS}</td>
        <td>
            <a href="{CERT_SCRIPT}" class="icon i_edit" title="{VIEW_CERT}">{VIEW_CERT}</a>
            <a class="icon i_edit" href="{SUB_EDIT_LINK}" title="{SUB_EDIT}">{SUB_EDIT}</a>
            <a class="icon i_delete" href="{SUB_ACTION_SCRIPT}" onclick="return action_delete(this, 'sub');">{SUB_ACTION}</a>
        </td>
    </tr>
    <!-- EDP: sub_item -->
    </tbody>
</table>
<!-- EDP: sub_list -->
<!-- EDP: subdomains_block -->
<!-- BDP: custom_dns_records_block -->
<h3 class="domains"><span>{TR_DNS}</span></h3>
<!-- BDP: dns_message -->
<div class="static_info">{DNS_MSG}</div>
<!-- EDP: dns_message -->
<!-- BDP: dns_list -->
<table class="firstColFixed datatable">
    <thead>
    <tr>
        <th>{TR_ZONE}</th>
        <th>{TR_DNS_NAME}</th>
        <th>{TR_TTL}</th>
        <th>{TR_DNS_CLASS}</th>
        <th>{TR_DNS_TYPE}</th>
        <th>{TR_DNS_DATA}</th>
        <th>{TR_DNS_STATUS}</th>
        <th>{TR_DNS_ACTION}</th>
    </tr>
    </thead>
    <tbody>
    <!-- BDP: dns_item -->
    <tr>
        <td><span class="icon i_domain_icon">{DNS_DOMAIN}</span></td>
        <td>{DNS_NAME}</td>
        <td>{DNS_TTL}</td>
        <td>{DNS_CLASS}</td>
        <td>{DNS_TYPE}</td>
        <td><span class="tips" title="{LONG_DNS_DATA}">{SHORT_DNS_DATA}</span></td>
        <td><span class="tips" title="{LONG_DNS_STATUS}">{SHORT_DNS_STATUS}</span></td>
        <td>
            <!-- BDP: dns_edit_link -->
            <a class="icon i_edit" href="{DNS_ACTION_SCRIPT_EDIT}" title="{DNS_ACTION_EDIT}">{DNS_ACTION_EDIT}</a>
            <!-- EDP: dns_edit_link -->
            <!-- BDP: dns_delete_link -->
            <a href="{DNS_ACTION_SCRIPT_DELETE}" class="icon i_delete" onclick="return action_delete(this, 'dns');" title="{DNS_ACTION_DELETE}">{DNS_ACTION_DELETE}</a>
            <!-- EDP: dns_delete_link -->
        </td>
    </tr>
    <!-- EDP: dns_item -->
    </tbody>
</table>
<!-- EDP: dns_list -->
<!-- EDP: custom_dns_records_block -->
