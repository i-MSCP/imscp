
<script>
    $(function () {
        $.each(imscp_i18n.core.error_field_stack, function (i, k) {
            $("#" + k).css("border-color", "#ca1d11");
        });

        $("#datatable").dataTable({
            language: imscp_i18n.core.dataTable,
            stateSave: true,
            pagingType: "simple"
        });
    });
</script>

<form method="post" action="reseller_add.php">
    <table class="firstColFixed">
        <thead>
        <tr>
            <th colspan="2">{TR_ACCOUNT_DATA}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><label for="admin_name">{TR_RESELLER_NAME}</label></td>
            <td><input type="text" name="admin_name" id="admin_name" value="{RESELLER_NAME}"></td>
        </tr>
        <tr>
            <td><label for="password">{TR_PASSWORD}</label></td>
            <td><input type="password" name="password" id="password" value="" autocomplete="off"></td>
        </tr>
        <tr>
            <td><label for="cpassword">{TR_PASSWORD_CONFIRMATION}</label></td>
            <td><input type="password" name="password_confirmation" id="cpassword" class="pwd_generator pwd_prefill" value="" autocomplete="off"></td>
        </tr>
        <tr>
            <td><label for="email">{TR_EMAIL}</label></td>
            <td><input type="text" name="email" id="email" value="{EMAIL}"></td>
        </tr>
        </tbody>
    </table>
    <!-- BDP: ips_block -->
    <table class="firstColFixed datatable">
        <thead>
        <tr>
            <th>{TR_IP_ADDRESS}</th>
            <th>{TR_ASSIGN}</th>
        </tr>
        </thead>
        <tbody>
        <!-- BDP: ip_block -->
        <tr>
            <td><label for="ip_{IP_ID}">{IP_NUMBER}</label></td>
            <td><input type="checkbox" id="ip_{IP_ID}" name="reseller_ips[]" value="{IP_ID}"{IP_ASSIGNED}></td>
        </tr>
        <!-- EDP: ip_block -->
        </tbody>
    </table>
    <!-- EDP: ips_block -->

    <table class="firstColFixed">
        <thead>
        <tr>
            <th colspan="2">{TR_ACCOUNT_LIMITS}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><label for="max_dmn_cnt">{TR_MAX_DMN_CNT}</label></td>
            <td><input type="text" name="max_dmn_cnt" id="max_dmn_cnt" value="{MAX_DMN_CNT}"></td>
        </tr>
        <tr>
            <td><label for="max_sub_cnt">{TR_MAX_SUB_CNT}</label></td>
            <td><input type="text" name="max_sub_cnt" id="max_sub_cnt" value="{MAX_SUB_CNT}"></td>
        </tr>
        <tr>
            <td><label for="max_als_cnt">{TR_MAX_ALS_CNT}</label></td>
            <td><input type="text" name="max_als_cnt" id="max_als_cnt" value="{MAX_ALS_CNT}"></td>
        </tr>
        <tr>
            <td><label for="max_mail_cnt">{TR_MAX_MAIL_CNT}</label></td>
            <td><input type="text" name="max_mail_cnt" id="max_mail_cnt" value="{MAX_MAIL_CNT}"></td>
        </tr>
        <tr>
            <td><label for="max_ftp_cnt">{TR_MAX_FTP_CNT}</label></td>
            <td><input type="text" name="max_ftp_cnt" id="max_ftp_cnt" value="{MAX_FTP_CNT}"></td>
        </tr>
        <tr>
            <td><label for="max_sql_db_cnt">{TR_MAX_SQL_DB_CNT}</label></td>
            <td><input type="text" name="max_sql_db_cnt" id="max_sql_db_cnt" value="{MAX_SQL_DB_CNT}"></td>
        </tr>
        <tr>
            <td><label for="max_sql_user_cnt">{TR_MAX_SQL_USER_CNT}</label></td>
            <td><input type="text" name="max_sql_user_cnt" id="max_sql_user_cnt" value="{MAX_SQL_USER_CNT}"></td>
        </tr>
        <tr>
            <td><label for="max_traff_amnt">{TR_MAX_TRAFF_AMNT}</label></td>
            <td><input type="text" name="max_traff_amnt" id="max_traff_amnt" value="{MAX_TRAFF_AMNT}"></td>
        </tr>
        <tr>
            <td><label for="max_disk_amnt">{TR_MAX_DISK_AMNT}</label></td>
            <td><input type="text" name="max_disk_amnt" id="max_disk_amnt" value="{MAX_DISK_AMNT}"></td>
        </tr>
        </tbody>
    </table>
    <table class="firstColFixed">
        <thead>
        <tr>
            <th colspan="2">{TR_FEATURES}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><label>{TR_PHP_EDITOR}</label></td>
            <td>
                <div class="radio">
                    <input type="radio" name="php_ini_system" id="php_ini_system_yes" value="yes"{PHP_INI_SYSTEM_YES}>
                    <label for="php_ini_system_yes">{TR_YES}</label>
                    <input type="radio" name="php_ini_system" id="php_ini_system_no" value="no"{PHP_INI_SYSTEM_NO}>
                    <label for="php_ini_system_no">{TR_NO}</label>
                </div>
                <button type="button" id="php_editor_dialog_open">{TR_SETTINGS}</button>
                <div id="php_editor_dialog" title="{TR_PHP_EDITOR_SETTINGS}">
                    <div class="php_editor_error static_success">
                        <span id="php_editor_msg_default">{TR_FIELDS_OK}</span>
                    </div>
                    <table>
                        <thead>
                        <tr>
                            <th colspan="2">{TR_PERMISSIONS}</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>
                                {TR_PHP_INI_AL_ALLOW_URL_FOPEN}
                                <span class="icon i_help" title="{TR_PHP_INI_PERMISSION_HELP}"></span>
                            </td>
                            <td>
                                <div class="radio">
                                    <input type="radio" name="php_ini_al_allow_url_fopen" id="php_ini_al_allow_url_fopen_yes" value="yes"{PHP_INI_AL_ALLOW_URL_FOPEN_YES}>
                                    <label for="php_ini_al_allow_url_fopen_yes">{TR_YES}</label>
                                    <input type="radio" name="php_ini_al_allow_url_fopen" id="php_ini_al_allow_url_fopen_no" value="no"{PHP_INI_AL_ALLOW_URL_FOPEN_NO}>
                                    <label for="php_ini_al_allow_url_fopen_no">{TR_NO}</label>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                {TR_PHP_INI_AL_DISPLAY_ERRORS}
                                <span class="icon i_help" title="{TR_PHP_INI_PERMISSION_HELP}"></span>
                            </td>
                            <td>
                                <div class="radio">
                                    <input type="radio" name="php_ini_al_display_errors" id="php_ini_al_display_errors_yes" value="yes"{PHP_INI_AL_DISPLAY_ERRORS_YES}>
                                    <label for="php_ini_al_display_errors_yes">{TR_YES}</label>
                                    <input type="radio" name="php_ini_al_display_errors" id="php_ini_al_display_errors_no" value="no"{PHP_INI_AL_DISPLAY_ERRORS_NO}>
                                    <label for="php_ini_al_display_errors_no">{TR_NO}</label>
                                </div>
                            </td>
                        </tr>
                        <!-- BDP: php_editor_disable_functions_block -->
                        <tr>
                            <td>
                                {TR_PHP_INI_AL_DISABLE_FUNCTIONS}
                                <span class="icon i_help" title="{TR_PHP_INI_PERMISSION_HELP}"></span>
                            </td>
                            <td>
                                <div class="radio">
                                    <input type="radio" name="php_ini_al_disable_functions" id="php_ini_al_disable_functions_yes" value="yes"{PHP_INI_AL_DISABLE_FUNCTIONS_YES}>
                                    <label for="php_ini_al_disable_functions_yes">{TR_YES}</label>
                                    <input type="radio" name="php_ini_al_disable_functions" id="php_ini_al_disable_functions_no" value="no"{PHP_INI_AL_DISABLE_FUNCTIONS_NO}>
                                    <label for="php_ini_al_disable_functions_no">{TR_NO}</label>
                                </div>
                            </td>
                        </tr>
                        <!-- EDP: php_editor_disable_functions_block -->
                        <!-- BDP: php_editor_mail_function_block -->
                        <tr>
                            <td>
                                {TR_PHP_INI_AL_MAIL_FUNCTION} <span class="icon i_help" title="{TR_PHP_INI_AL_MAIL_FUNCTION_HELP}"></span>
                            </td>
                            <td>
                                <div class="radio">
                                    <input type="radio" name="php_ini_al_mail_function" id="php_ini_al_mail_function_yes" value="yes"{PHP_INI_AL_MAIL_FUNCTION_YES}>
                                    <label for="php_ini_al_mail_function_yes">{TR_YES}</label>
                                    <input type="radio" name="php_ini_al_mail_function" id="php_ini_al_mail_function_no" value="no"{PHP_INI_AL_MAIL_FUNCTION_NO}>
                                    <label for="php_ini_al_mail_function_no">{TR_NO}</label>
                                </div>
                            </td>
                        </tr>
                        <!-- EDP: php_editor_mail_function_block -->
                        </tbody>
                    </table>
                    <table>
                        <thead>
                        <tr>
                            <th colspan="2">{TR_DIRECTIVES_VALUES}</th>
                        </tr>
                        </thead>
                        <tbody id="php_ini_values">
                        <tr>
                            <td><label for="max_execution_time">{TR_MAX_EXECUTION_TIME}</label></td>
                            <td><input type="text" name="max_execution_time" id="max_execution_time" data-limit="10000" value="{MAX_EXECUTION_TIME}"> <span>{TR_SEC}</span></td>
                        </tr>
                        <tr>
                            <td><label for="max_input_time">{TR_MAX_INPUT_TIME}</label></td>
                            <td><input type="text" name="max_input_time" id="max_input_time" data-limit="10000" value="{MAX_INPUT_TIME}"> <span>{TR_SEC}</span></td>
                        </tr>
                        <tr>
                            <td><label for="memory_limit">{TR_MEMORY_LIMIT}</label></td>
                            <td><input type="text" name="memory_limit" id="memory_limit" data-limit="10000" value="{MEMORY_LIMIT}"> <span>{TR_MIB}</span></td>
                        </tr>
                        <tr>
                            <td><label for="post_max_size">{TR_POST_MAX_SIZE}</label></td>
                            <td><input type="text" name="post_max_size" id="post_max_size" data-limit="10000" value="{POST_MAX_SIZE}"> <span>{TR_MIB}</span></td>
                        </tr>
                        <tr>
                            <td><label for="upload_max_filesize">{TR_UPLOAD_MAX_FILESIZE}</label></td>
                            <td><input type="text" name="upload_max_filesize" id="upload_max_filesize" data-limit="10000" value="{UPLOAD_MAX_FILESIZE}"> <span>{TR_MIB}</span></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </td>
        </tr>
        <tr>
            <td>{TR_SOFTWARES_INSTALLER}</td>
            <td>
                <div class="radio">
                    <input type="radio" name="software_allowed" id="software_allowed_yes" value="yes"{SOFTWARES_INSTALLER_YES}>
                    <label for="software_allowed_yes">{TR_YES}</label>
                    <input type="radio" name="software_allowed" id="software_allowed_no" value="no"{SOFTWARES_INSTALLER_NO}>
                    <label for="software_allowed_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        <tr>
            <td>{TR_SOFTWARES_REPOSITORY}</td>
            <td>
                <div class="radio">
                    <input type="radio" name="softwaredepot_allowed" id="softwaredepot_allowed_yes" value="yes"{SOFTWARES_REPOSITORY_YES}>
                    <label for="softwaredepot_allowed_yes">{TR_YES}</label>
                    <input type="radio" name="softwaredepot_allowed" id="softwaredepot_allowed_no" value="no"{SOFTWARES_REPOSITORY_NO}>
                    <label for="softwaredepot_allowed_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        <tr>
            <td>{TR_WEB_SOFTWARES_REPOSITORY}</td>
            <td>
                <div class="radio">
                    <input type="radio" name="websoftwaredepot_allowed" id="websoftwaredepot_allowed_yes" value="yes"{WEB_SOFTWARES_REPOSITORY_YES}>
                    <label for="websoftwaredepot_allowed_yes">{TR_YES}</label>
                    <input type="radio" name="websoftwaredepot_allowed" id="websoftwaredepot_allowed_no" value="no"{WEB_SOFTWARES_REPOSITORY_NO}>
                    <label for="websoftwaredepot_allowed_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        <tr>
            <td>{TR_SUPPORT_SYSTEM}</td>
            <td>
                <div class="radio">
                    <input type="radio" name="support_system" id="support_system_yes" value="yes"{SUPPORT_SYSTEM_YES}>
                    <label for="support_system_yes">{TR_YES}</label>
                    <input type="radio" name="support_system" id="support_system_no" value="no"{SUPPORT_SYSTEM_NO}>
                    <label for="support_system_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
    <table class="firstColFixed">
        <thead>
        <tr>
            <th colspan="2">{TR_PERSONAL_DATA}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><label for="customer_id">{TR_CUSTOMER_ID}</label></td>
            <td><input type="text" name="customer_id" id="customer_id" value="{CUSTOMER_ID}"></td>
        </tr>
        <tr>
            <td><label for="fname">{TR_FNAME}</label></td>
            <td><input type="text" name="fname" id="fname" value="{FNAME}"></td>
        </tr>
        <tr>
            <td><label for="lname">{TR_LNAME}</label></td>
            <td><input type="text" name="lname" id="lname" value="{LNAME}"></td>
        </tr>
        <tr>
            <td><label for="gender">{TR_GENDER}</label></td>
            <td>
                <select id="gender" name="gender">
                    <option value="M"{MALE}>{TR_MALE}</option>
                    <option value="F"{FEMALE}>{TR_FEMALE}</option>
                    <option value="U"{UNKNOWN}>{TR_UNKNOWN}</option>
                </select>
            </td>
        </tr>
        <tr>
            <td><label for="firm">{TR_FIRM}</label></td>
            <td><input type="text" name="firm" id="firm" value="{FIRM}"></td>
        </tr>
        <tr>
            <td><label for="street1">{TR_STREET1}</label></td>
            <td><input type="text" name="street1" id="street1" value="{STREET1}"></td>
        </tr>
        <tr>
            <td><label for="street2">{TR_STREET2}</label></td>
            <td><input type="text" name="street2" id="street2" value="{STREET2}"></td>
        </tr>
        <tr>
            <td><label for="zip">{TR_ZIP}</label></td>
            <td><input type="text" name="zip" id="zip" value="{ZIP}"></td>
        </tr>
        <tr>
            <td><label for="city">{TR_CITY}</label></td>
            <td><input type="text" name="city" id="city" value="{CITY}"></td>
        </tr>
        <tr>
            <td><label for="state">{TR_STATE}</label></td>
            <td><input type="text" name="state" id="state" value="{STATE}"></td>
        </tr>
        <tr>
            <td><label for="country">{TR_COUNTRY}</label></td>
            <td><input type="text" name="country" id="country" value="{COUNTRY}"></td>
        </tr>
        <tr>
            <td><label for="phone">{TR_PHONE}</label></td>
            <td><input type="text" name="phone" id="phone" value="{PHONE}"></td>
        </tr>
        <tr>
            <td><label for="fax">{TR_FAX}</label></td>
            <td><input type="text" name="fax" id="fax" value="{FAX}"></td>
        </tr>
        </tbody>
    </table>
    <div class="buttons">
        <button name="submit" type="submit">{TR_CREATE}</button>
        <a class="link_as_button" href="manage_users.php">{TR_CANCEL}</a>
    </div>
</form>
