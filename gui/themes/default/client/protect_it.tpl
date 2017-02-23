
<!-- BDP: auth_selectors_js -->
<script>
    $(function () {
        $("#ptype_1,#ptype_2").each(function () {
            if ($(this).is(":checked")) {
                $('select[name="users[]"]').prop("disabled", $(this).data("type") == 'group');
                $('select[name="groups[]"]').prop("disabled", $(this).data("type") == 'user');
            }

            $(this).on('change', function () {
                if ($(this).is(":checked")) {
                    $('select[name="users[]"]').prop("disabled", $(this).data("type") == 'group').find("option").attr("selected", false);
                    $('select[name="groups[]"]').prop("disabled", $(this).data("type") == 'user').find("option").attr("selected", false);
                }
            });
        });
    });
</script>
<!-- EDP: auth_selectors_js -->
<form name="addProtectedAreaFrm" method="post" action="protected_areas_add.php">
    <table class="firstColFixed">
        <thead>
        <tr>
            <th colspan="2">{TR_PROTECTED_AREA_DATA}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><label for="protected_area_name">{TR_AREA_NAME}</label></td>
            <td><input name="protected_area_name" type="text" class="textinput" id="protected_area_name" value="{AREA_NAME}"></td>
        </tr>
        <tr>
            <td><label for="ftp_directory">{TR_PATH}</label></td>
            <td>
                <input name="protected_area_path" type="text" class="textinput" id="ftp_directory" value="{PATH}">
                <span class="icon i_bc_folder ftp_choose_dir clickable">{TR_CHOOSE_DIR}</span>
            </td>
        </tr>
        </tbody>
    </table>
    <table class="firstColFixed">
        <thead>
        <tr>
            <th colspan="2">{TR_AUTHENTICATION_DATA}</th>
        </tr>
        </thead>
        <tbody>
        <!-- BDP: auth_selectors -->
        <tr>
            <td>
                <label for="ptype_1">{TR_USER_AUTH}</label>
                <input type="radio" id="ptype_1" name="protection_type" value="user" data-type="user"{USER_CHECKED}>
            </td>
            <td>
                <label for="ptype_2">{TR_GROUP_AUTH}</label>
                <input type="radio" id="ptype_2" name="protection_type" value="group" data-type="group"{GROUP_CHECKED}>
            </td>
        </tr>
        <!-- EDP: auth_selectors -->
        <tr>
            <td>
                <label>
                    <select name="users[]" multiple="multiple" size="5" style="min-width:150px">
                        <!-- BDP: user_item -->
                        <option value="{USER_VALUE}"{USER_SELECTED}>{USER_LABEL}</option>
                        <!-- EDP: user_item -->
                    </select>
                </label>
            </td>
            <!-- BDP: auth_group_list -->
            <td>
                <label>
                    <select name="groups[]" multiple="multiple" size="5" style="min-width:150px">
                        <!-- BDP: group_item -->
                        <option value="{GROUP_VALUE}"{GROUP_SELECTED}>{GROUP_LABEL}</option>
                        <!-- EDP: group_item -->
                    </select>
                </label>
            </td>
            <!-- EDP: auth_group_list -->
        </tr>
        </tbody>
    </table>
    <div class="buttons">
        <input type="submit" name="Submit" value="{TR_PROTECT_IT}">
        <input type="hidden" name="id" value="{ID}">
        <a class="link_as_button" href="protected_areas.php">{TR_CANCEL}</a>
    </div>
</form>
