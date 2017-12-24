
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
        $("#php_no").on("change", function() {
            if($(this).is(":checked")) {
                $("#softwares_installer_no").prop("checked", true).button("refresh");
            }
        });
    });
</script>
<form method="post" action="hosting_plan_edit.php?id={ID}">
    <table class="firstColFixed">
        <thead>
        <tr>
            <th colspan="2">{TR_HOSTING_PLAN}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><label for="name">{TR_NAME}</label></td>
            <td><input id="name" type="text" name="name" value="{NAME}" class="inputTitle"></td>
        </tr>
        <tr>
            <td><label for="description">{TR_DESCRIPTON}</label></td>
            <td><textarea id="description" name="description">{DESCRIPTION}</textarea></td>
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
            <td><label for="sub">{TR_MAX_SUB}</label></td>
            <td><input id="sub" type="number" name="sub" min="-1" value="{MAX_SUB}"></td>
        </tr>
        <!-- EDP: nb_subdomains -->
        <!-- BDP: nb_domain_aliases -->
        <tr>
            <td><label for="als">{TR_MAX_ALS}</label></td>
            <td><input id="als" type="number" name="als" min="-1" value="{MAX_ALS}"></td>
        </tr>
        <!-- EDP: nb_domain_aliases -->
        <!-- BDP: nb_mail -->
        <tr>
            <td><label for="mail">{TR_MAX_MAIL}</label></td>
            <td><input id="mail" type="number" name="mail" min="-1" value="{MAX_MAIL}"></td>
        </tr>
        <tr>
            <td><label for="mail_quota">{TR_MAIL_QUOTA}</label></td>
            <td><input id="mail_quota" type="number" name="mail_quota" min="0" max="17592186044416" value="{MAIL_QUOTA}"></td>
        </tr>
        <!-- EDP: nb_mail -->
        <!-- BDP: nb_ftp -->
        <tr>
            <td><label for="ftp">{TR_MAX_FTP}</label></td>
            <td><input id="ftp" type="number" name="ftp" min="-1" value="{MAX_FTP}"></td>
        </tr>
        <!-- EDP: nb_ftp -->
        <!-- BDP: nb_sqld -->
        <tr>
            <td><label for="sql_db">{TR_MAX_SQLD}</label></td>
            <td><input id="sql_db" type="number" name="sql_db" min="-1" value="{MAX_SQLD}"></td>
        </tr>
        <!-- EDP: nb_sqld -->
        <!-- BDP: nb_sqlu -->
        <tr>
            <td><label for="sql_user">{TR_MAX_SQLU}</label></td>
            <td><input id="sql_user" type="number" name="sql_user" min="-1" value="{MAX_SQLU}"></td>
        </tr>
        <!-- EDP: nb_sqlu -->
        <tr>
            <td><label for="traff">{TR_MONTHLY_TRAFFIC}</label></td>
            <td><input id="traff" type="number" name="traff" min="0" max="17592186044416" value="{MONTHLY_TRAFFIC}"></td>
        </tr>
        <tr>
            <td><label for="disk">{TR_MAX_DISKSPACE}</label></td>
            <td><input id="disk" type="number" name="disk" min="0" max="17592186044416" value="{MAX_DISKSPACE}"></td>
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
                    <input type="radio" name="php" value="_yes_" id="php_yes"{PHP_YES}>
                    <label for="php_yes">{TR_YES}</label>
                    <input type="radio" name="php" value="_no_" id="php_no"{PHP_NO}>
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
                        <!-- BDP: phpini_perm_config_level_block -->
                        <tr>
                            <td>
                                {TR_PHPINI_PERM_CONFIG_LEVEL}
                                <span class="icon i_help" title="{TR_PHPINI_PERM_CONFIG_LEVEL_HELP}"></span>
                            </td>
                            <td>
                                <div class="radio">
                                    <!-- BDP: phpini_perm_config_level_per_site_block -->
                                    <input type="radio" name="phpini_perm_config_level" id="phpini_perm_config_level_per_site" value="per_site"{PHPINI_PERM_CONFIG_LEVEL_PER_SITE}>
                                    <label for="phpini_perm_config_level_per_site">{TR_PER_SITE}</label>
                                    <!-- EDP: phpini_perm_config_level_per_site_block -->
                                    <input type="radio" name="phpini_perm_config_level" id="phpini_perm_config_level_per_domain" value="per_domain"{PHPINI_PERM_CONFIG_LEVEL_PER_DOMAIN}>
                                    <label for="phpini_perm_config_level_per_domain">{TR_PER_DOMAIN}</label>
                                    <input type="radio" name="phpini_perm_config_level" id="phpini_perm_config_level_per_user" value="per_user"{PHPINI_PERM_CONFIG_LEVEL_PER_USER}>
                                    <label for="phpini_perm_config_level_per_user">{TR_PER_USER}</label>
                                </div>
                            </td>
                        </tr>
                        <!-- EDP: phpini_perm_config_level_block -->
                        <!-- BDP: php_editor_allow_url_fopen_block -->
                        <tr>
                            <td>{TR_CAN_EDIT_ALLOW_URL_FOPEN}</td>
                            <td>
                                <div class="radio">
                                    <input type="radio" name="phpini_perm_allow_url_fopen" id="phpini_perm_allow_url_fopen_yes" value="yes"{ALLOW_URL_FOPEN_YES}>
                                    <label for="phpini_perm_allow_url_fopen_yes">{TR_YES}</label>
                                    <input type="radio" name="phpini_perm_allow_url_fopen" id="phpini_perm_allow_url_fopen_no" value="no"{ALLOW_URL_FOPEN_NO}>
                                    <label for="phpini_perm_allow_url_fopen_no">{TR_NO}</label>
                                </div>
                            </td>
                        </tr>
                        <!-- EDP: php_editor_allow_url_fopen_block -->
                        <!-- BDP: php_editor_display_errors_block -->
                        <tr>
                            <td>{TR_CAN_EDIT_DISPLAY_ERRORS}</td>
                            <td>
                                <div class="radio">
                                    <input type="radio" name="phpini_perm_display_errors" id="phpini_perm_display_errors_yes" value="yes"{DISPLAY_ERRORS_YES}>
                                    <label for="phpini_perm_display_errors_yes">{TR_YES}</label>
                                    <input type="radio" name="phpini_perm_display_errors" id="phpini_perm_display_errors_no" value="no"{DISPLAY_ERRORS_NO}>
                                    <label for="phpini_perm_display_errors_no">{TR_NO}</label>
                                </div>
                            </td>
                        </tr>
                        <!-- EDP: php_editor_display_errors_block -->
                        <!-- BDP: php_editor_disable_functions_block -->
                        <tr>
                            <td>{TR_CAN_EDIT_DISABLE_FUNCTIONS}</td>
                            <td>
                                <div class="radio">
                                    <input type="radio" name="phpini_perm_disable_functions" id="phpini_perm_disable_functions_yes" value="yes"{DISABLE_FUNCTIONS_YES}>
                                    <label for="phpini_perm_disable_functions_yes">{TR_YES}</label>
                                    <input type="radio" name="phpini_perm_disable_functions" id="phpini_perm_disable_functions_no" value="no"{DISABLE_FUNCTIONS_NO}>
                                    <label for="phpini_perm_disable_functions_no">{TR_NO}</label>
                                    <input type="radio" name="phpini_perm_disable_functions" id="phpini_perm_disable_functions_exec" value="exec"{DISABLE_FUNCTIONS_EXEC}>
                                    <label for="phpini_perm_disable_functions_exec">{TR_ONLY_EXEC}</label>
                                </div>
                            </td>
                        </tr>
                        <!-- EDP: php_editor_disable_functions_block -->
                        <!-- BDP: php_editor_mail_function_block -->
                        <tr>
                            <td>{TR_CAN_USE_MAIL_FUNCTION}</td>
                            <td>
                                <div class="radio">
                                    <input type="radio" name="phpini_perm_mail_function" id="phpini_perm_mail_function_yes" value="yes"{MAIL_FUNCTION_YES}>
                                    <label for="phpini_perm_mail_function_yes">{TR_YES}</label>
                                    <input type="radio" name="phpini_perm_mail_function" id="phpini_perm_mail_function_no" value="no"{MAIL_FUNCTION_NO}>
                                    <label for="phpini_perm_mail_function_no">{TR_NO}</label>
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
                            <td><input type="number" name="memory_limit" id="memory_limit" min="1" max="{MEMORY_LIMIT_LIMIT}" value="{MEMORY_LIMIT}"> <span>{TR_MIB}</span>
                            </td>
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
                    <input type="radio" name="cgi" value="_yes_" id="cgi_yes"{CGI_YES}>
                    <label for="cgi_yes">{TR_YES}</label>
                    <input type="radio" name="cgi" value="_no_" id="cgi_no"{CGI_NO}>
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
                    <input type="radio" name="hp_dns" value="_yes_" id="dns_yes"{DNS_YES}>
                    <label for="dns_yes">{TR_YES}</label>
                    <input type="radio" name="hp_dns" value="_no_" id="dns_no"{DNS_NO}>
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
                    <input type="radio" name="softwares_installer" value="_yes_" id="softwares_installer_yes"{SOFTWARE_YES}>
                    <label for="softwares_installer_yes">{TR_YES}</label>
                    <input type="radio" name="softwares_installer" value="_no_" id="softwares_installer_no"{SOFTWARE_NO}>
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
                    <input type="radio" name="external_mail" value="_yes_" id="extmail_yes"{EXTMAIL_YES}>
                    <label for="extmail_yes">{TR_YES}</label>
                    <input type="radio" name="external_mail" value="_no_" id="extmail_no"{EXTMAIL_NO}>
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
                    <input type="checkbox" name="backup[]" value="_dmn_" id="backup_dmn"{BACKUPD}>
                    <label for="backup_dmn">{TR_BACKUP_DOMAIN}</label>
                    <input type="checkbox" name="backup[]" value="_sql_" id="backup_sql"{BACKUPS}>
                    <label for="backup_sql">{TR_BACKUP_SQL}</label>
                    <input type="checkbox" name="backup[]" value="_mail_" id="backup_mail"{BACKUPM}>
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
                    <input type="radio" name="protected_webfolders" value="_yes_" id="protected_webfolders_yes"{PROTECT_WEB_FOLDERS_YES}>
                    <label for="protected_webfolders_yes">{TR_YES}</label>
                    <input type="radio" name="protected_webfolders" value="_no_" id="protected_webfolders_no"{PROTECT_WEB_FOLDERS_NO}>
                    <label for="protected_webfolders_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
    <table class="firstColFixed">
        <thead>
        <tr>
            <th colspan="2">{TR_AVAILABILITY}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>{TR_STATUS}</td>
            <td>
                <div class="radio">
                    <input type="radio" name="status" value="1" id="status_yes"{STATUS_YES}>
                    <label for="status_yes">{TR_YES}</label>
                    <input type="radio" name="status" value="0" id="status_no"{STATUS_NO}>
                    <label for="status_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
    <div class="buttons">
        <input name="Submit" type="submit" value="{TR_UPDATE}">
        <a class="link_as_button" href="hosting_plan.php">{TR_CANCEL}</a>
    </div>
</form>
