<script type="text/javascript">
    /* <![CDATA[ */
    function action(url, domain) {
        if (url.indexOf('delete') == -1) {
            location = url;
        } else if (confirm(sprintf("{TR_DELETE_MESSAGE}", domain))) {
            location = url;
        }

        return false;
    }
    /* ]]> */
</script>
<table>
    <thead>
    <tr>
        <th>{TR_DOMAIN}</th>
        <th>{TR_STATUS}</th>
        <th>{TR_ACTION}</th>
    </tr>
    </thead>
    <tbody>
    <!-- BDP: item -->
    <tr>
        <td>{DOMAIN}</td>
        <td>{STATUS}</td>
        <td>
            <!-- BDP: create_link -->
            <a href="#" class="icon i_users" onclick="action('{CREATE_ACTION_URL}', '')">{TR_CREATE}</a>
            <!-- EDP: create_link -->
            <!-- BDP: edit_link -->
            <a href="#" class="icon i_edit" onclick="action('{EDIT_ACTION_URL}', '')">{TR_EDIT}</a>
            <!-- EDP: edit_link -->
            <!-- BDP: delete_link -->
            <a href="#" class="icon i_delete" onclick="action('{DELETE_ACTION_URL}', '{DOMAIN}')">{TR_DELETE}</a>
            <!-- EDP: delete_link -->
        </td>
    </tr>
    <!-- EDP: item -->
    </tbody>
</table>
