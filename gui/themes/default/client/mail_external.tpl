<script type="text/javascript">
    /* <![CDATA[ */
    $(document).ready(
    	function () {
			$("th :checkbox").change(function (){$("table :checkbox").prop('checked', $(this).is(':checked'));});
		}
    );

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
<form action="mail_external_delete.php" method="post">
    <table>
        <thead>
        <tr>
            <th style="width:21px;"><input type="checkbox" /></th>
            <th>{TR_DOMAIN}</th>
            <th>{TR_STATUS}</th>
            <th>{TR_ACTION}</th>
        </tr>
        </thead>
        <tfoot>
        <tr>
            <th><input type="checkbox" /></th>
            <th>{TR_DOMAIN}</th>
            <th>{TR_STATUS}</th>
            <th>{TR_ACTION}</th>
        </tr>
        </tfoot>
        <tbody>
        <!-- BDP: item -->
        <tr>
            <td><input type='checkbox' name='{ITEM_TYPE}[]' value="{ITEM_ID}"{DISABLED} /></td>
            <td>{DOMAIN}</td>
            <td>{STATUS}</td>
            <td>
                <!-- BDP: activate_link -->
                <a href="#" class="icon i_users" onclick="action('{ACTIVATE_URL}', '')">{TR_ACTIVATE}</a>
                <!-- EDP: activate_link -->
                <!-- BDP: edit_link -->
                <a href="#" class="icon i_edit" onclick="action('{EDIT_URL}', '')">{TR_EDIT}</a>
                <!-- EDP: edit_link -->
                <!-- BDP: deactivate_link -->
                <a href="#" class="icon i_delete" onclick="action('{DEACTIVATE_URL}', '{DOMAIN}')">{TR_DEACTIVATE}</a>
                <!-- EDP: deactivate_link -->
            </td>
        </tr>
        <!-- EDP: item -->
        </tbody>
    </table>
	<input type="hidden" name="from" value="mail_external" />
    <label><input type="submit" name="submit" value="Deactivate selected items"/></label>
</form>


