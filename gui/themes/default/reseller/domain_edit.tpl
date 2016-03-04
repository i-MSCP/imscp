
<script>
    $(function() {
        $.each(imscp_i18n.core.error_field_stack, function (i, k) {
            $("#" + k).css("border-color", "#ca1d11");
        });

        $("#domain_expires").datepicker();
        $("#domain_never_expires").change(function () {
            if ($(this).is(":checked")) {
                $("#domain_expires").val("").css("border-color", "#dfdfdf").attr("disabled", "disabled");
            } else {
                $("#domain_expires").removeAttr("disabled");
            }
        });
    });
</script>

<form method="post" action="domain_edit.php?edit_id={EDIT_ID}" autocomplete="off">
    <table class="firstColFixed">
        <thead>
        <tr>
            <th colspan="2">{TR_DOMAIN_OVERVIEW}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>{TR_DOMAIN_NAME}</td>
            <td>{DOMAIN_NAME}</td>
        </tr>
        <tr>
            <td>{TR_DOMAIN_EXPIRE_DATE}</td>
            <td>{DOMAIN_EXPIRE_DATE}</td>
        </tr>
        <tr>
            <td><label for="domain_expires">{TR_DOMAIN_NEW_EXPIRE_DATE}</label></td>
            <td>
                <input type="text" id="domain_expires" name="domain_expires" value="{DOMAIN_NEW_EXPIRE_DATE}"{DOMAIN_NEW_EXPIRE_DATE_DISABLED}>
                <input type="checkbox" name="domain_never_expires" id="domain_never_expires"{DOMAIN_NEVER_EXPIRES_CHECKED}>
                <label for="domain_never_expires">{TR_DOMAIN_NEVER_EXPIRES}</label>
            </td>
        </tr>
        <tr>
            <td><label for="domain_ip_id">{TR_DOMAIN_IP}</label></td>
            <td>
                <select id="domain_ip_id" name="domain_ip_id">
                    <!-- BDP: ip_entry -->
                    <option value="{IP_VALUE}"{IP_SELECTED}>{IP_NUM}</option>
                    <!-- EDP: ip_entry -->
                </select>
            </td>
        </tr>
        </tbody>
    </table>
    <table class="firstColFixed">
        <thead>
        <tr>
            <th>{TR_DOMAIN_LIMITS}</th>
            <th>{TR_LIMIT_VALUE}</th>
            <th>{TR_CUSTOMER_CONSUMPTION}</th>
            <th>{TR_RESELLER_CONSUMPTION}</th>
        </tr>
        </thead>
        <tbody>
        <!-- BDP: subdomain_limit_block -->
        <tr>
            <td><label for="domain_subd_limit">{TR_SUBDOMAINS_LIMIT}</label></td>
            <td><input type="text" name="domain_subd_limit" id="domain_subd_limit" value="{SUBDOMAIN_LIMIT}"></td>
            <td>{TR_CUSTOMER_SUBDOMAINS_COMSUPTION}</td>
            <td>{TR_RESELLER_SUBDOMAINS_COMSUPTION}</td>
        </tr>
        <!-- EDP: subdomain_limit_block -->
        <!-- BDP: domain_aliases_limit_block -->
        <tr>
            <td><label for="domain_alias_limit">{TR_ALIASSES_LIMIT}</label></td>
            <td><input type="text" name="domain_alias_limit" id="domain_alias_limit" value="{DOMAIN_ALIASSES_LIMIT}"></td>
            <td>{TR_CUSTOMER_DOMAIN_ALIASSES_COMSUPTION}</td>
            <td>{TR_RESELLER_DOMAIN_ALIASSES_COMSUPTION}</td>
        </tr>
        <!-- EDP: domain_aliases_limit_block -->
        <!-- BDP: mail_accounts_limit_block -->
        <tr>
            <td><label for="domain_mailacc_limit">{TR_MAIL_ACCOUNTS_LIMIT}</label></td>
            <td><input type="text" name="domain_mailacc_limit" id="domain_mailacc_limit" value="{MAIL_ACCOUNTS_LIMIT}"></td>
            <td>{TR_CUSTOMER_MAIL_ACCOUNTS_COMSUPTION}</td>
            <td>{TR_RESELLER_MAIL_ACCOUNTS_COMSUPTION}</td>
        </tr>
        <tr>
            <td><label for="mail_quota">{TR_MAIL_QUOTA}</label></td>
            <td><input type="text" name="mail_quota" id="mail_quota" value="{MAIL_QUOTA}"></td>
            <td>{TR_CUSTOMER_MAIL_QUOTA_COMSUPTION}</td>
            <td>{TR_NO_AVAILABLE}</td>
        </tr>
        <!-- EDP: mail_accounts_limit_block -->
        <!-- BDP: ftp_accounts_limit_block -->
        <tr>
            <td><label for="domain_ftpacc_limit">{TR_FTP_ACCOUNTS_LIMIT}</label></td>
            <td><input type="text" name="domain_ftpacc_limit" id="domain_ftpacc_limit" value="{FTP_ACCOUNTS_LIMIT}"></td>
            <td>{TR_CUSTOMER_FTP_ACCOUNTS_COMSUPTION}</td>
            <td>{TR_RESELLER_FTP_ACCOUNTS_COMSUPTION}</td>
        </tr>
        <!-- EDP: ftp_accounts_limit_block -->
        <!-- BDP: sql_db_and_users_limit_block -->
        <tr>
            <td><label for="domain_sqld_limit">{TR_SQL_DATABASES_LIMIT}</label></td>
            <td><input type="text" name="domain_sqld_limit" id="domain_sqld_limit" value="{SQL_DATABASES_LIMIT}"></td>
            <td>{TR_CUSTOMER_SQL_DATABASES_COMSUPTION}</td>
            <td>{TR_RESELLER_SQL_DATABASES_COMSUPTION}</td>
        </tr>
        <tr>
            <td><label for="domain_sqlu_limit">{TR_SQL_USERS_LIMIT}</label></td>
            <td><input type="text" name="domain_sqlu_limit" id="domain_sqlu_limit" value="{SQL_USERS_LIMIT}"></td>
            <td>{TR_CUSTOMER_SQL_USERS_COMSUPTION}</td>
            <td>{TR_RESELLER_SQL_USERS_COMSUPTION}</td>
        </tr>
        <!-- EDP: sql_db_and_users_limit_block -->
        <tr>
            <td><label for="domain_traffic_limit">{TR_TRAFFIC_LIMIT}</label></td>
            <td><input type="text" name="domain_traffic_limit" id="domain_traffic_limit" value="{TRAFFIC_LIMIT}"></td>
            <td>{TR_CUSTOMER_TRAFFIC_COMSUPTION}</td>
            <td>{TR_RESELLER_TRAFFIC_COMSUPTION}</td>
        </tr>
        <tr>
            <td><label for="domain_disk_limit">{TR_DISK_LIMIT}</label></td>
            <td><input type="text" name="domain_disk_limit" id="domain_disk_limit" value="{DISK_LIMIT}"></td>
            <td>{TR_CUSTOMER_DISKPACE_COMSUPTION}</td>
            <td>{TR_RESELLER_DISKPACE_COMSUPTION}</td>
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
        <!-- BDP: php_block -->
        <tr>
            <td>{TR_PHP}</td>
            <td>
                <div class="radio">
                    <input type="radio" id="php_yes" name="domain_php" value="yes"{PHP_YES}>
                    <label for="php_yes">{TR_YES}</label>
                    <input type="radio" id="php_no" name="domain_php" value="no"{PHP_NO}>
                    <label for="php_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        <!-- BDP: php_editor_block -->
        <tr id="php_editor_block">
            <td><label for="phpiniSystem">{TR_PHP_EDITOR}</label></td>
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
                            <td><input type="text" name="max_execution_time" id="max_execution_time" data-limit="{MAX_EXECUTION_TIME_LIMIT}" value="{MAX_EXECUTION_TIME}"> <span>{TR_SEC}</span></td>
                        </tr>
                        <tr>
                            <td><label for="max_input_time">{TR_MAX_INPUT_TIME}</label></td>
                            <td><input type="text" name="max_input_time" id="max_input_time" data-limit="{MAX_INPUT_TIME_LIMIT}" value="{MAX_INPUT_TIME}"> <span>{TR_SEC}</span></td>
                        </tr>
                        <tr>
                            <td><label for="memory_limit">{TR_MEMORY_LIMIT}</label></td>
                            <td><input type="text" name="memory_limit" id="memory_limit" data-limit="{MEMORY_LIMIT_LIMIT}" value="{MEMORY_LIMIT}"> <span>{TR_MIB}</span></td>
                        </tr>
                        <tr>
                            <td><label for="post_max_size">{TR_POST_MAX_SIZE}</label></td>
                            <td><input type="text" name="post_max_size" id="post_max_size" data-limit="{POST_MAX_SIZE_LIMIT}" value="{POST_MAX_SIZE}"> <span>{TR_MIB}</span></td>
                        </tr>
                        <tr>
                            <td><label for="upload_max_filesize">{TR_UPLOAD_MAX_FILEZISE}</label></td>
                            <td><input type="text" name="upload_max_filesize" id="upload_max_filesize" data-limit="{UPLOAD_MAX_FILESIZE_LIMIT}" value="{UPLOAD_MAX_FILESIZE}"> <span>{TR_MIB}</span></td>
                        </tr>
                        </tbody>
                    </table>
                    <!-- EDP: php_editor_default_values_block -->
                </div>
            </td>
        </tr>
        <!-- EDP: php_editor_block -->
        <!-- EDP: php_block -->
        <!-- BDP: cgi_block -->
        <tr>
            <td>{TR_CGI}</td>
            <td>
                <div class="radio">
                    <input type="radio" id="domain_cgi_yes" name="domain_cgi" value="yes"{CGI_YES}>
                    <label for="domain_cgi_yes">{TR_YES}</label>
                    <input type="radio" id="domain_cgi_no" name="domain_cgi" value="no"{CGI_NO}>
                    <label for="domain_cgi_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        <!-- EDP: cgi_block -->
        <!-- BDP: custom_dns_records_feature -->
        <tr>
            <td>{TR_DNS}</td>
            <td>
                <div class="radio">
                    <input type="radio" id="domain_dns_yes" name="domain_dns" value="yes"{DNS_YES}>
                    <label for="domain_dns_yes">{TR_YES}</label>
                    <input type="radio" id="domain_dns_no" name="domain_dns" value="no"{DNS_NO}>
                    <label for="domain_dns_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        <!-- EDP: custom_dns_records_feature -->
        <!-- BDP: aps_block -->
        <tr>
            <td>{TR_APS}</td>
            <td>
                <div class="radio">
                    <input type="radio" name="domain_software_allowed" value="yes" id="domain_software_allowed_yes"{APS_YES}>
                    <label for="domain_software_allowed_yes">{TR_YES}</label>
                    <input type="radio" name="domain_software_allowed" value="no" id="domain_software_allowed_no"{APS_NO}>
                    <label for="domain_software_allowed_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        <!-- EDP: aps_block -->
        <!-- BDP: ext_mail_block -->
        <tr>
            <td>{TR_EXTMAIL}</td>
            <td>
                <div class="radio">
                    <input type="radio" id="extmail_yes" name="domain_external_mail" value="yes"{EXTMAIL_YES}>
                    <label for="extmail_yes">{TR_YES}</label>
                    <input type="radio" id="extmail_no" name="domain_external_mail" value="no"{EXTMAIL_NO}>
                    <label for="extmail_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        <!-- EDP: ext_mail_block -->
        <!-- BDP: backup_block -->
        <tr>
            <td>{TR_BACKUP}</td>
            <td>
                <div class="checkbox">
                    <input type="checkbox" id="backup_dmn" name="allowbackup[]" value="dmn"{BACKUP_DOMAIN}>
                    <label for="backup_dmn">{TR_BACKUP_DOMAIN}</label>
                    <input type="checkbox" id="backup_sql" name="allowbackup[]" value="sql"{BACKUP_SQL}>
                    <label for="backup_sql">{TR_BACKUP_SQL}</label>
                    <input type="checkbox" id="backup_mail" name="allowbackup[]" value="mail"{BACKUP_MAIL}>
                    <label for="backup_mail">{TR_BACKUP_MAIL}</label>
                </div>
            </td>
        </tr>
        <!-- EDP: backup_block -->
        <tr>
            <td>
                <label for="web_folder_protection">{TR_WEB_FOLDER_PROTECTION}</label>
                <span class="icon i_help" id="web_folder_protection_help" title="{TR_WEB_FOLDER_PROTECTION_HELP}"></span>
            </td>
            <td>
                <div class="radio">
                    <input type="radio" id="web_folder_protection_yes" name="web_folder_protection" value="yes"{WEB_FOLDER_PROTECTION_YES}>
                    <label for="web_folder_protection_yes">{TR_YES}</label>
                    <input type="radio" id="web_folder_protection_no" name="web_folder_protection" value="no"{WEB_FOLDER_PROTECTION_NO}>
                    <label for="web_folder_protection_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
    <div class="buttons">
        <input name="submit" type="submit" value="{TR_UPDATE}">
        <a class="link_as_button" href="users.php">{TR_CANCEL}</a>
    </div>
</form>
