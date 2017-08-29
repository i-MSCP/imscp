
<script>
    $(function() {
        $.each(imscp_i18n.core.error_field_stack, function (i, k) {
            $("#" + k).css("border-color", "#ca1d11");
        });

        $("#disk").on('keyup mouseup paste copy cut', function () {
            var storageQuotaLimit = parseInt($(this).val());
            var $mailQuotaField = $("#mail_quota");

            if (storageQuotaLimit > 0) {
                $mailQuotaField.attr("min", 1).attr("max", storageQuotaLimit);
                return;
            }

            $mailQuotaField.attr("min", 0).removeAttr("max");
        });

        // Ensure that PHP is enabled when software installer is enabled
        $("#softwares_installer_yes").on('change', function() {
            if($(this).is(":checked")) {
                var $el = $("#php_yes");
                if(!$el.is(":checked")) {
                    $el.prop("checked", true).button("refresh").trigger("change");
                }
            }
        });

        // Ensure that software installer is disabled when PHP is disabled
        $("#php_no").on('change', function() {
            if($(this).is(":checked")) {
                $("#softwares_installer_no").prop("checked", true).button("refresh");
            }
        });
    });
</script>
<form method="post" action="hosting_plan_add.php">
    <table class="firstColFixed">
        <thead>
        <tr>
            <th colspan="2">{TR_HOSTING_PLAN}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><label for="name">{TR_NAME}</label></td>
            <td><input type="text" name="name" id="name" value="{NAME_VALUE}" class="inputTitle"></td>
        </tr>
        <tr>
            <td><label for="description">{TR_DESCRIPTON}</label></td>
            <td><textarea name="description" id="description">{DESCRIPTION_VALUE}</textarea></td>
        </tr>
        </tbody>
    </table>
    <table class="firstColFixed">
        <thead>
        <tr>
            <th colspan="2">{TR_HOSTING_PLAN_LIMITS}</th>
        </tr>
        </thead>
        <tbody>
        <!-- BDP: nb_subdomains -->
        <tr>
            <td><label for="sub">{TR_MAX_SUBDOMAINS}</label></td>
            <td><input type="number" name="sub" id="sub" min="-1" value="{MAX_SUB_LIMITS}"></td>
        </tr>
        <!-- EDP: nb_subdomains -->
        <!-- BDP: nb_domain_aliases -->
        <tr>
            <td><label for="als">{TR_MAX_ALIASES}</label></td>
            <td><input type="number" name="als" id="als" min="-1" value="{MAX_ALS_VALUES}"></td>
        </tr>
        <!-- EDP: nb_domain_aliases -->
        <!-- BDP: nb_mail -->
        <tr>
            <td><label for="mail">{TR_MAX_MAILACCOUNTS}</label></td>
            <td><input type="number" name="mail" id="mail" min="-1" value="{MAIL_VALUE}"></td>
        </tr>
        <tr>
            <td><label for="mail_quota">{TR_MAIL_QUOTA}</label></td>
            <td><input type="number" name="mail_quota" id="mail_quota" min="0" max="17592186044416" value="{MAIL_QUOTA_VALUE}"></td>
        </tr>
        <!-- EDP: nb_mail -->
        <!-- BDP: nb_ftp -->
        <tr>
            <td><label for="ftp">{TR_MAX_FTP}</label></td>
            <td><input type="number" name="ftp" id="ftp" min="-1" value="{FTP_VALUE}"></td>
        </tr>
        <!-- EDP: nb_ftp -->
        <!-- BDP: nb_sqld -->
        <tr>
            <td><label for="sql_db">{TR_MAX_SQL}</label></td>
            <td><input type="number" name="sql_db" id="sql_db" min="-1" value="{SQL_DB_VALUE}"></td>
        </tr>
        <!-- EDP: nb_sqld -->
        <!-- BDP: nb_sqlu -->
        <tr>
            <td><label for="sql_user">{TR_MAX_SQL_USERS}</label></td>
            <td><input type="number" name="sql_user" id="sql_user" min="-1" value="{SQL_USER_VALUE}"></td>
        </tr>
        <!-- EDP: nb_sqlu -->
        <tr>
            <td><label for="traff">{TR_MAX_TRAFFIC}</label></td>
            <td><input type="number" name="traff" id="traff" min="0" max="17592186044416" value="{TRAFF_VALUE}"></td>
        </tr>
        <tr>
            <td><label for="disk">{TR_DISK_LIMIT}</label></td>
            <td><input type="number" name="disk" id="disk" min="0" max="17592186044416" value="{DISK_VALUE}"></td>
        </tr>
        </tbody>
    </table>
    <table class="firstColFixed">
        <thead>
        <tr>
            <th colspan="2">{TR_HOSTING_PLAN_FEATURES}</th>
        </tr>
        </thead>
        <tbody>
        <!-- BDP: php_feature -->
        <tr>
            <td>{TR_PHP}</td>
            <td>
                <div class="radio">
                    <input type="radio" name="php" id="php_yes" value="_yes_"{TR_PHP_YES} >
                    <label for="php_yes">{TR_YES}</label>
                    <input type="radio" name="php" id="php_no" value="_no_"{TR_PHP_NO} >
                    <label for="php_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        <!-- EDP: php_feature -->
        <!-- BDP: php_editor_feature -->
        <tr id="php_editor_block">
            <td><label>{TR_PHP_EDITOR}</label></td>
            <td>
                <div class="radio">
                    <input type="radio" name="php_ini_system" id="php_ini_system_yes" value="yes"{PHP_EDITOR_YES}>
                    <label for="php_ini_system_yes">{TR_YES}</label>
                    <input type="radio" name="php_ini_system" id="php_ini_system_no" value="no"{PHP_EDITOR_NO}>
                    <label for="php_ini_system_no">{TR_NO}</label>
                </div>
                <button type="button" id="php_editor_dialog_open">{TR_SETTINGS}</button>
                <div id="php_editor_dialog" title="{TR_PHP_EDITOR_SETTINGS}">
                    <div class="php_editor_error static_success">
                        <span id="php_editor_msg_default">{TR_FIELDS_OK}</span>
                    </div>
                    <!-- BDP: php_editor_permissions_block -->
                    <table>
                        <thead>
                        <tr>
                            <th colspan="2">{TR_PERMISSIONS}</th>
                        </tr>
                        </thead>
                        <tbody>
                        <!-- BDP: php_editor_allow_url_fopen_block -->
                        <tr>
                            <td>{TR_CAN_EDIT_ALLOW_URL_FOPEN}</td>
                            <td>
                                <div class="radio">
                                    <input type="radio" name="phpini_perm_allow_url_fopen" id="phpiniAllowUrlFopenYes" value="yes"{ALLOW_URL_FOPEN_YES}>
                                    <label for="phpiniAllowUrlFopenYes">{TR_YES}</label>
                                    <input type="radio" name="phpini_perm_allow_url_fopen" id="phpiniAllowUrlFopenNo" value="no"{ALLOW_URL_FOPEN_NO}>
                                    <label for="phpiniAllowUrlFopenNo">{TR_NO}</label>
                                </div>
                            </td>
                        </tr>
                        <!-- EDP: php_editor_allow_url_fopen_block -->
                        <!-- BDP: php_editor_display_errors_block -->
                        <tr>
                            <td>{TR_CAN_EDIT_DISPLAY_ERRORS}</td>
                            <td>
                                <div class="radio">
                                    <input type="radio" name="phpini_perm_display_errors" id="phpiniDisplayErrorsYes" value="yes"{DISPLAY_ERRORS_YES}>
                                    <label for="phpiniDisplayErrorsYes">{TR_YES}</label>
                                    <input type="radio" name="phpini_perm_display_errors" id="phpiniDisplayErrorsNo" value="no"{DISPLAY_ERRORS_NO}>
                                    <label for="phpiniDisplayErrorsNo">{TR_NO}</label>
                                </div>
                            </td>
                        </tr>
                        <!-- EDP: php_editor_display_errors_block -->
                        <!-- BDP: php_editor_disable_functions_block -->
                        <tr>
                            <td>{TR_CAN_EDIT_DISABLE_FUNCTIONS}</td>
                            <td>
                                <div class="radio">
                                    <input type="radio" name="phpini_perm_disable_functions" id="phpiniDisableFunctionsYes" value="yes"{DISABLE_FUNCTIONS_YES}>
                                    <label for="phpiniDisableFunctionsYes">{TR_YES}</label>
                                    <input type="radio" name="phpini_perm_disable_functions" id="phpiniDisableFunctionsNo" value="no"{DISABLE_FUNCTIONS_NO}>
                                    <label for="phpiniDisableFunctionsNo">{TR_NO}</label>
                                    <input type="radio" name="phpini_perm_disable_functions" id="phpiniDisableFunctionsExec" value="exec"{DISABLE_FUNCTIONS_EXEC}>
                                    <label for="phpiniDisableFunctionsExec">{TR_ONLY_EXEC}</label>
                                </div>
                            </td>
                        </tr>
                        <!-- EDP: php_editor_disable_functions_block -->
                        <!-- BDP: php_editor_mail_function_block -->
                        <tr>
                            <td>{TR_CAN_USE_MAIL_FUNCTION}</td>
                            <td>
                                <div class="radio">
                                    <input type="radio" name="phpini_perm_mail_function" id="phpiniMailFunctionYes" value="yes"{MAIL_FUNCTION_YES}>
                                    <label for="phpiniMailFunctionYes">{TR_YES}</label>
                                    <input type="radio" name="phpini_perm_mail_function" id="phpiniMailFunctionNo" value="no"{MAIL_FUNCTION_NO}>
                                    <label for="phpiniMailFunctionNo">{TR_NO}</label>
                                </div>
                            </td>
                        </tr>
                        <!-- EDP: php_editor_mail_function_block -->
                        </tbody>
                    </table>
                    <!-- EDP: php_editor_permissions_block -->
                    <!-- BDP: php_editor_default_values_block -->
                    <table>
                        <thead>
                        <tr>
                            <th colspan="2">{TR_DIRECTIVES_VALUES}</th>
                        </tr>
                        </thead>
                        <tbody id="php_ini_values">
                        <tr>
                            <td><label for="max_execution_time">{TR_MAX_EXECUTION_TIME}</label></td>
                            <td><input type="number" name="max_execution_time" id="max_execution_time" min="1" max="{MAX_EXECUTION_TIME_LIMIT}" value="{MAX_EXECUTION_TIME}"> <span>{TR_SEC}</span></td>
                        </tr>
                        <tr>
                            <td><label for="max_input_time">{TR_MAX_INPUT_TIME}</label></td>
                            <td><input type="number" name="max_input_time" id="max_input_time" min="1" max="{MAX_INPUT_TIME_LIMIT}" value="{MAX_INPUT_TIME}"> <span>{TR_SEC}</span></td>
                        </tr>
                        <tr>
                            <td><label for="memory_limit">{TR_MEMORY_LIMIT}</label></td>
                            <td><input type="number" name="memory_limit" id="memory_limit" min="1" max="{MEMORY_LIMIT_LIMIT}" value="{MEMORY_LIMIT}"> <span>{TR_MIB}</span></td>
                        </tr>
                        <tr>
                            <td><label for="post_max_size">{TR_POST_MAX_SIZE}</label></td>
                            <td><input type="number" name="post_max_size" id="post_max_size" min="1" max="{POST_MAX_SIZE_LIMIT}" value="{POST_MAX_SIZE}"> <span>{TR_MIB}</span></td>
                        </tr>
                        <tr>
                            <td><label for="upload_max_filesize">{TR_UPLOAD_MAX_FILEZISE}</label></td>
                            <td><input type="number" name="upload_max_filesize" id="upload_max_filesize" min="1" max="{UPLOAD_MAX_FILESIZE_LIMIT}" value="{UPLOAD_MAX_FILESIZE}"> <span>{TR_MIB}</span></td>
                        </tr>
                        </tbody>
                    </table>
                    <!-- EDP: php_editor_default_values_block -->
                </div>
            </td>
        </tr>
        <!-- EDP: php_editor_feature -->
        <!-- BDP: cgi_feature -->
        <tr>
            <td>{TR_CGI}</td>
            <td>
                <div class="radio">
                    <input type="radio" name="cgi" value="_yes_" id="cgi_yes"{TR_CGI_YES}>
                    <label for="cgi_yes">{TR_YES}</label>
                    <input type="radio" name="cgi" value="_no_" id="cgi_no"{TR_CGI_NO}>
                    <label for="cgi_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        <!-- EDP: cgi_feature -->
        <!-- BDP: custom_dns_records_feature -->
        <tr>
            <td>{TR_DNS}</td>
            <td>
                <div class="radio">
                    <input type="radio" name="dns" value="_yes_" id="dns_yes"{TR_DNS_YES}>
                    <label for="dns_yes">{TR_YES}</label>
                    <input type="radio" name="dns" value="_no_" id="dns_no"{TR_DNS_NO}>
                    <label for="dns_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        <!-- EDP: custom_dns_records_feature -->
        <!-- BDP: aps_feature -->
        <tr>
            <td>{TR_SOFTWARE_SUPP}</td>
            <td>
                <div class="radio">
                    <input type="radio" name="softwares_installer" value="_yes_" id="softwares_installer_yes"{TR_SOFTWARE_YES}>
                    <label for="softwares_installer_yes">{TR_YES}</label>
                    <input type="radio" name="softwares_installer" value="_no_" id="softwares_installer_no"{TR_SOFTWARE_NO}>
                    <label for="softwares_installer_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        <!-- EDP: aps_feature -->
        <!-- BDP: ext_mail_feature -->
        <tr>
            <td>{TR_EXTMAIL}</td>
            <td>
                <div class="radio">
                    <input type="radio" name="external_mail" value="_yes_" id="extmail_yes"{TR_EXTMAIL_YES}>
                    <label for="extmail_yes">{TR_YES}</label>
                    <input type="radio" name="external_mail" value="_no_" id="extmail_no"{TR_EXTMAIL_NO}>
                    <label for="extmail_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        <!-- EDP: ext_mail_feature -->
        <!-- BDP: backup_feature -->
        <tr>
            <td>{TR_BACKUP}</td>
            <td>
                <div class="checkbox">
                    <input type="checkbox" name="backup[]" value="_dmn_" id="backup_dmn"{VL_BACKUPD}>
                    <label for="backup_dmn">{TR_BACKUP_DOMAIN}</label>
                    <input type="checkbox" name="backup[]" value="_sql_" id="backup_sql"{VL_BACKUPS}>
                    <label for="backup_sql">{TR_BACKUP_SQL}</label>
                    <input type="checkbox" name="backup[]" value="_mail_" id="backup_mail"{VL_BACKUPM}>
                    <label for="backup_mail">{TR_BACKUP_MAIL}</label>
                </div>
            </td>
        </tr>
        <!-- EDP: backup_feature -->
        <tr>
            <td>
                <label>{TR_WEB_FOLDER_PROTECTION}</label>
                <span class="icon i_help" id="web_folder_protection_help" title="{TR_WEB_FOLDER_PROTECTION_HELP}"></span>
            </td>
            <td>
                <div class="radio">
                    <input type="radio" name="protected_webfolders" value="_yes_" id="protected_webfolders_yes"{TR_PROTECT_WEB_FOLDERS_YES}>
                    <label for="protected_webfolders_yes">{TR_YES}</label>
                    <input type="radio" name="protected_webfolders" value="_no_" id="protected_webfolders_no"{TR_PROTECT_WEB_FOLDERS_NO}>
                    <label for="protected_webfolders_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
    <table class="firstColFixed">
        <thead>
        <tr>
            <th colspan="2">{TR_HP_AVAILABILITY}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>{TR_STATUS}</td>
            <td>
                <div class="radio">
                    <input type="radio" name="status" value="1" id="status_yes"{TR_STATUS_YES}>
                    <label for="status_yes">{TR_YES}</label>
                    <input type="radio" name="status" value="0" id="status_no"{TR_STATUS_NO}>
                    <label for="status_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
    <div class="buttons">
        <input name="Submit" type="submit" value="{TR_ADD}">
        <a class="link_as_button" href="hosting_plan.php">{TR_CANCEL}</a>
    </div>
</form>
