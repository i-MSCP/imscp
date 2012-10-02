<script type="text/javascript">
    /* <![CDATA[ */
    $(document).ready(function () {
        var i = {INDEX} +1;

        $('#add').click(
                function () {
                    var str_mx = '<tr class="item_entry">';
                    str_mx += '<td><select name="name[]" id="name_' + i + '"><option value="{DOMAIN}">{DOMAIN}</option><option value="{WILDCARD}">{WILDCARD}</option></select></td>';
                    str_mx += '<td><select name="priority[]" id="priority_' + i + '"><option value="10" selected>10</option><option value="15">15</option><option value="20">20</option><option value="25">25</option><option value="30">30</option></select></td>';
                    str_mx += '<td><input type="text" name="host[]" id="host_' + i + '" value="" /></td>';
                    str_mx += '</tr>';
                    $(".inputs tbody").append(str_mx);
                    i++;
                }
        );

        $('#remove').click(function () {
            if (i > 1) {
                $('.item_entry:last').remove();
                i--;
            }
        });

        $('#reset').click(function () {
            while (i > 1) {
                $('.item_entry:last').remove();
                i--;
            }
        });
    });
    /* ]]> */
</script>
<form name="add_external_mail_server" method="post" action="mail_external_add.php">
    <table class="inputs">
        <thead>
        <tr>
            <th>{TR_NAME}</th>
            <th>{TR_PRIORITY}</th>
            <th>{TR_HOST}</th>
        </tr>
        </thead>
        <tbody>
        <!-- BDP: item_entries -->
        <tr class="item_entry">
            <td>
                <label>
                    <select name="name[]" id="name_{INDEX}">
						<!-- BDP: name_options -->
                        <option value="{OPTION_VALUE}"{SELECTED}>{OPTION_VALUE}</option>
						<!-- EDP: name_options -->
                    </select>
                </label>
            </td>
            <td>
                <label>
                    <select name="priority[]" id="priority_{INDEX}">
                        <!-- BDP: priority_options -->
                        <option value="{OPTION_VALUE}"{SELECTED}>{OPTION_VALUE}</option>
                        <!-- EDP: priority_options -->
                    </select>
                </label>
            </td>
            <td><label><input type="text" name="host[]" id="host_{INDEX}" value="{HOST}"/></label></td>
        </tr>
        <!-- EDP: item_entries -->
        </tbody>
    </table>
    <div style="float:left;">
        <a href="#" id="add">{TR_ADD_NEW_ENTRY}</a> | <a href="#" id="remove">{TR_REMOVE_LAST_ENTRY}</a> | <a href="#" id="reset">{TR_RESET_ENTRIES}</a>
    </div>
    <div class="buttons">
        <input type="hidden" name="id" value="{ITEM_ID}"/>
        <input name="submit" type="submit" value="{TR_ADD_ENTRIES}"/>
    </div>
</form>
