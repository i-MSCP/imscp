
<script>
    $(function() {
        $.each(imscp_i18n.core.error_field_stack, function (i, k) {
            $("#" + k).css("border-color", "#ca1d11");
        });

        $("#nreseller_max_disk").on("keyup mouseup paste copy cut", function () {
            var storageQuotaLimit = parseInt($(this).val());
            var $mailQuotaField = $("#nreseller_mail_quota");

            if (storageQuotaLimit > 0) {
                $mailQuotaField.attr("min", 1).attr("max", storageQuotaLimit);
                return;
            }

            $mailQuotaField.attr("min", 0).removeAttr("max");
        });

        // Ensure that PHP is enabled when software installer is enabled
        $("#software_allowed_yes").on("change", function() {
            if($(this).is(":checked")) {
                var $el = $("#php_yes");
                if(!$el.is(":checked")) {
                    $el.prop("checked", true).button("refresh").trigger("change");
                }
            }
        });

        // Ensure that software installer is disabled when PHP is disabled
        $("#php_no").on("change", function() {
            if($(this).is(":checked")) {
                $("#software_allowed_no").prop("checked", true).button("refresh");
            }
        });
    });
</script>
<form method="post" action="user_add2.php">
    <table class="firstColFixed">
        <thead>
        <tr>
            <th colspan="2">{TR_HOSTING_PLAN}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>{TR_NAME}</td>
            <td><input type="hidden" name="template" id="template" value="{VL_TEMPLATE_NAME}">{VL_TEMPLATE_NAME}</td>
        </tr>
        </tbody>
    </table>
    <table class="firstColFixed">
        <thead>
        <tr>
            <th colspan="2">{TR_LIMITS}</th>
        </tr>
        </thead>
        <tbody>
        <!-- BDP: subdomain_feature -->
        <tr>
            <td><label for="nreseller_max_subdomain_cnt">{TR_MAX_SUBDOMAIN}</label></td>
            <td><input type="number" name="nreseller_max_subdomain_cnt" id="nreseller_max_subdomain_cnt" min="-1" value="{MAX_SUBDMN_CNT}"></td>
        </tr>
        <!-- EDP: subdomain_feature -->
        <!-- BDP: alias_feature -->
        <tr>
            <td><label for="nreseller_max_alias_cnt">{TR_MAX_DOMAIN_ALIAS}</label></td>
            <td><input type="number" name="nreseller_max_alias_cnt" id="nreseller_max_alias_cnt" min="-1" value="{MAX_DMN_ALIAS_CNT}"></td>
        </tr>
        <!-- EDP: alias_feature -->
        <!-- BDP: mail_feature -->
        <tr>
            <td><label for="nreseller_max_mail_cnt">{TR_MAX_MAIL_COUNT}</label></td>
            <td><input type="number" name="nreseller_max_mail_cnt" id="nreseller_max_mail_cnt" min="-1" value="{MAX_MAIL_CNT}"></td>
        </tr>
        <tr>
            <td><label for="nreseller_mail_quota">{TR_MAIL_QUOTA}</label></td>
            <td><input type="number" name="nreseller_mail_quota" id="nreseller_mail_quota" min="0" max="17592186044416" value="{MAIL_QUOTA}"></td>
        </tr>
        <!-- EDP: mail_feature -->
        <!-- BDP: ftp_feature -->
        <tr>
            <td><label for="nreseller_max_ftp_cnt">{TR_MAX_FTP}</label></td>
            <td><input type="number" name="nreseller_max_ftp_cnt" id="nreseller_max_ftp_cnt" min="-1" value="{MAX_FTP_CNT}"></td>
        </tr>
        <!-- EDP: ftp_feature -->
        <!-- BDP: sql_feature -->
        <tr>
            <td><label for="nreseller_max_sql_db_cnt">{TR_MAX_SQL_DB}</label></td>
            <td><input type="number" name="nreseller_max_sql_db_cnt" id="nreseller_max_sql_db_cnt" min="-1" value="{MAX_SQL_CNT}"></td>
        </tr>
        <tr>
            <td><label for="nreseller_max_sql_user_cnt">{TR_MAX_SQL_USERS}</label></td>
            <td><input type="number" name="nreseller_max_sql_user_cnt" id="nreseller_max_sql_user_cnt" min="-1" value="{VL_MAX_SQL_USERS}"></td>
        </tr>
        <!-- EDP: sql_feature -->
        <tr>
            <td><label for="nreseller_max_traffic">{TR_MAX_TRAFFIC}</label></td>
            <td><input type="number" name="nreseller_max_traffic" id="nreseller_max_traffic" min="0" max="17592186044416" value="{VL_MAX_TRAFFIC}"></td>
        </tr>
        <tr>
            <td><label for="nreseller_max_disk">{TR_MAX_DISK_USAGE}</label></td>
            <td><input type="number" name="nreseller_max_disk" id="nreseller_max_disk" min="0" max="17592186044416" value="{VL_MAX_DISK_USAGE}"></td>
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
            <td>{TR_PHP}</td>
            <td>
                <div class="radio">
                    <input type="radio" name="php" id="php_yes" value="_yes_"{VL_PHPY}>
                    <label for="php_yes">{TR_YES}</label>
                    <input type="radio" name="php" id="php_no" value="_no_"{VL_PHPN}>
                    <label for="php_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        <!-- BDP: php_editor_block -->
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
        <!-- EDP: php_editor_block -->
        <tr>
            <td>{TR_CGI}</td>
            <td>
                <div class="radio">
                    <input type="radio" id="cgi_yes" name="cgi" value="_yes_"{VL_CGIY}>
                    <label for="cgi_yes">{TR_YES}</label>
                    <input type="radio" id="cgi_no" name="cgi" value="_no_" {VL_CGIN}>
                    <label for="cgi_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        <!-- BDP: custom_dns_records_feature -->
        <tr>
            <td>{TR_DNS}</td>
            <td>
                <div class="radio">
                    <input type="radio" id="dns_yes" name="dns" value="_yes_"{VL_DNSY}>
                    <label for="dns_yes">{TR_YES}</label>
                    <input type="radio" id="dns_no" name="dns" value="_no_"{VL_DNSN}>
                    <label for="dns_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        <!-- EDP: custom_dns_records_feature -->
        <!-- BDP: ext_mail_feature -->
        <tr>
            <td>{TR_EXTMAIL}</td>
            <td>
                <div class="radio">
                    <input type="radio" id="extmail_yes" name="external_mail" value="_yes_"{VL_EXTMAILY}>
                    <label for="extmail_yes">{TR_YES}</label>
                    <input type="radio" id="extmail_no" name="external_mail" value="_no_"{VL_EXTMAILN}>
                    <label for="extmail_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        <!-- EDP: ext_mail_feature -->
        <!-- BDP: aps_feature -->
        <tr>
            <td>{TR_SOFTWARE_SUPP}</td>
            <td>
                <div class="radio">
                    <input type="radio" name="software_allowed" value="_yes_" id="software_allowed_yes"{VL_SOFTWAREY}>
                    <label for="software_allowed_yes">{TR_YES}</label>
                    <input type="radio" name="software_allowed" value="_no_" id="software_allowed_no"{VL_SOFTWAREN}>
                    <label for="software_allowed_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        <!-- EDP: aps_feature -->
        <!-- BDP: backup_feature -->
        <tr>
            <td>{TR_BACKUP}</td>
            <td>
                <div class="checkbox">
                    <input type="checkbox" id="backup_dmn" name="backup[]" value="_dmn_"{VL_BACKUPD}>
                    <label for="backup_dmn">{TR_BACKUP_DOMAIN}</label>
                    <input type="checkbox" id="backup_sql" name="backup[]" value="_sql_"{VL_BACKUPS}>
                    <label for="backup_sql">{TR_BACKUP_SQL}</label>
                    <input type="checkbox" id="backup_mail" name="backup[]" value="_mail_"{VL_BACKUPM}>
                    <label for="backup_mail">{TR_BACKUP_MAIL}</label>
                </div>
            </td>
        </tr>
        <!-- EDP: backup_feature -->
        <tr>
            <td>
                <label for="web_folder_protection">{TR_WEB_FOLDER_PROTECTION}</label>
                <span class="icon i_help" id="web_folder_protection_help" title="{TR_WEB_FOLDER_PROTECTION_HELP}"></span>
            </td>
            <td>
                <div class="radio">
                    <input type="radio" id="web_folder_protection_yes" name="web_folder_protection" value="_yes_"{VL_WEB_FOLDER_PROTECTION_YES}>
                    <label for="web_folder_protection_yes">{TR_YES}</label>
                    <input type="radio" id="web_folder_protection_no" name="web_folder_protection" value="_no_"{VL_WEB_FOLDER_PROTECTION_NO}>
                    <label for="web_folder_protection_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
    <div class="buttons">
        <input type="hidden" name="uaction" value="user_add2_nxt">
        <input name="Submit" type="submit" value="{TR_NEXT_STEP}">
    </div>
</form>
