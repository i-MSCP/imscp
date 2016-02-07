
<form id="hostingPlanEditFrm" name="hostingPlanEditFrm" method="post" action="hosting_plan_edit.php?id={ID}">
    <table class="firstColFixed">
        <thead>
        <tr>
            <th colspan="2">{TR_HOSTING_PLAN}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><label for="hp_name">{TR_NAME}</label></td>
            <td><input id="hp_name" type="text" name="hp_name" value="{NAME}" class="inputTitle"></td>
        </tr>
        <tr>
            <td><label for="hp_description">{TR_DESCRIPTON}</label></td>
            <td><textarea id="hp_description" name="hp_description">{DESCRIPTION}</textarea></td>
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
            <td><label for="hp_sub">{TR_MAX_SUB}</label></td>
            <td><input id="hp_sub" type="text" name="hp_sub" value="{MAX_SUB}"></td>
        </tr>
        <!-- EDP: nb_subdomains -->
        <!-- BDP: nb_domain_aliases -->
        <tr>
            <td><label for="hp_als">{TR_MAX_ALS}</label></td>
            <td><input id="hp_als" type="text" name="hp_als" value="{MAX_ALS}"></td>
        </tr>
        <!-- EDP: nb_domain_aliases -->
        <!-- BDP: nb_mail -->
        <tr>
            <td><label for="hp_mail">{TR_MAX_MAIL}</label></td>
            <td><input id="hp_mail" type="text" name="hp_mail" value="{MAX_MAIL}"></td>
        </tr>
        <tr>
            <td><label for="hp_mail_quota">{TR_MAIL_QUOTA}</label></td>
            <td><input id="hp_mail_quota" type="text" name="hp_mail_quota" value="{MAIL_QUOTA}"></td>
        </tr>
        <!-- EDP: nb_mail -->
        <!-- BDP: nb_ftp -->
        <tr>
            <td><label for="hp_ftp">{TR_MAX_FTP}</label></td>
            <td><input id="hp_ftp" type="text" name="hp_ftp" value="{MAX_FTP}"></td>
        </tr>
        <!-- EDP: nb_ftp -->
        <!-- BDP: nb_sqld -->
        <tr>
            <td><label for="hp_sql_db">{TR_MAX_SQLD}</label></td>
            <td><input id="hp_sql_db" type="text" name="hp_sql_db" value="{MAX_SQLD}"></td>
        </tr>
        <!-- EDP: nb_sqld -->
        <!-- BDP: nb_sqlu -->
        <tr>
            <td><label for="hp_sql_user">{TR_MAX_SQLU}</label></td>
            <td><input id="hp_sql_user" type="text" name="hp_sql_user" value="{MAX_SQLU}"></td>
        </tr>
        <!-- EDP: nb_sqlu -->
        <tr>
            <td><label for="hp_traff">{TR_MONTHLY_TRAFFIC}</label></td>
            <td><input id="hp_traff" type="text" name="hp_traff" value="{MONTHLY_TRAFFIC}"></td>
        </tr>
        <tr>
            <td><label for="hp_disk">{TR_MAX_DISKSPACE}</label></td>
            <td><input id="hp_disk" type="text" name="hp_disk" value="{MAX_DISKSPACE}"></td>
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
                    <input type="radio" name="hp_php" value="_yes_" id="hp_php_yes"{PHP_YES}>
                    <label for="hp_php_yes">{TR_YES}</label>
                    <input type="radio" name="hp_php" value="_no_" id="hp_php_no"{PHP_NO}>
                    <label for="hp_php_no">{TR_NO}</label>
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
                <div style="margin:0" id="php_editor_dialog" title="{TR_PHP_EDITOR_SETTINGS}">
                    <div class="php_editor_error static_success">
                        <span id="php_editor_msg_default">{TR_FIELDS_OK}</span>
                    </div>
                    <!-- BDP: php_editor_permissions_block -->
                    <table>
                        <thead>
                        <tr class="description">
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
                        <tr class="description">
                            <th colspan="2">{TR_DIRECTIVES_VALUES}</th>
                        </tr>
                        </thead>
                        <tbody id="php_ini_values">
                        <tr>
                            <td><label for="post_max_size">{TR_POST_MAX_SIZE}</label></td>
                            <td><input type="text" name="post_max_size" id="post_max_size" data-limit="{POST_MAX_SIZE_LIMIT}" value="{POST_MAX_SIZE}"> <span>{TR_MIB}</span></td>
                        </tr>
                        <tr>
                            <td><label for="upload_max_filesize">{TR_UPLOAD_MAX_FILEZISE}</label></td>
                            <td><input type="text" name="upload_max_filesize" id="upload_max_filesize" data-limit="{UPLOAD_MAX_FILESIZE_LIMIT}" value="{UPLOAD_MAX_FILESIZE}"> <span>{TR_MIB}</span></td>
                        </tr>
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
                            <td><input type="text" name="memory_limit" id="memory_limit" data-limit="{MEMORY_LIMIT_LIMIT}" value="{MEMORY_LIMIT}"> <span>{TR_MIB}</span>
                            </td>
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
                    <input type="radio" name="hp_cgi" value="_yes_" id="hp_cgi_yes"{CGI_YES}>
                    <label for="hp_cgi_yes">{TR_YES}</label>
                    <input type="radio" name="hp_cgi" value="_no_" id="hp_cgi_no"{CGI_NO}>
                    <label for="hp_cgi_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        <!-- EDP: cgi_feature -->
        <!-- BDP: custom_dns_records_feature -->
        <tr>
            <td>{TR_DNS}</td>
            <td>
                <div class="radio">
                    <input type="radio" name="hp_dns" value="_yes_" id="hp_dns_yes"{DNS_YES}>
                    <label for="hp_dns_yes">{TR_YES}</label>
                    <input type="radio" name="hp_dns" value="_no_" id="hp_dns_no"{DNS_NO}>
                    <label for="hp_dns_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        <!-- EDP: custom_dns_records_feature -->
        <!-- BDP: aps_feature -->
        <tr>
            <td>{TR_SOFTWARE_SUPP}</td>
            <td>
                <div class="radio">
                    <input type="radio" name="hp_softwares_installer" value="_yes_" id="hp_softwares_installer_yes"{SOFTWARE_YES}>
                    <label for="hp_softwares_installer_yes">{TR_YES}</label>
                    <input type="radio" name="hp_softwares_installer" value="_no_" id="hp_softwares_installer_no"{SOFTWARE_NO}>
                    <label for="hp_softwares_installer_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        <!-- EDP: aps_feature -->
        <!-- BDP: ext_mail_feature -->
        <tr>
            <td>{TR_EXTMAIL}</td>
            <td>
                <div class="radio">
                    <input type="radio" name="hp_external_mail" value="_yes_" id="hp_extmail_yes"{EXTMAIL_YES}>
                    <label for="hp_extmail_yes">{TR_YES}</label>
                    <input type="radio" name="hp_external_mail" value="_no_" id="hp_extmail_no"{EXTMAIL_NO}>
                    <label for="hp_extmail_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        <!-- EDP: ext_mail_feature -->
        <!-- BDP: backup_feature -->
        <tr>
            <td>{TR_BACKUP}</td>
            <td>
                <div class="checkbox">
                    <input type="checkbox" name="hp_backup[]" value="_dmn_" id="hp_backup_dmn"{BACKUPD}>
                    <label for="hp_backup_dmn">{TR_BACKUP_DOMAIN}</label>
                    <input type="checkbox" name="hp_backup[]" value="_sql_" id="hp_backup_sql"{BACKUPS}>
                    <label for="hp_backup_sql">{TR_BACKUP_SQL}</label>
                    <input type="checkbox" name="hp_backup[]" value="_mail_" id="hp_backup_mail"{BACKUPM}>
                    <label for="hp_backup_mail">{TR_BACKUP_MAIL}</label>
                </div>
            </td>
        </tr>
        <!-- EDP: backup_feature -->
        <tr>
            <td>
                <label>{TR_WEB_FOLDER_PROTECTION}</label>
                <span style="vertical-align:middle" class="icon i_help" id="web_folder_protection_help" title="{TR_WEB_FOLDER_PROTECTION_HELP}"></span>
            </td>
            <td>
                <div class="radio">
                    <input type="radio" name="hp_protected_webfolders" value="_yes_" id="hp_protected_webfolders_yes"{PROTECT_WEB_FOLDERS_YES}>
                    <label for="hp_protected_webfolders_yes">{TR_YES}</label>
                    <input type="radio" name="hp_protected_webfolders" value="_no_" id="hp_protected_webfolders_no"{PROTECT_WEB_FOLDERS_NO}>
                    <label for="hp_protected_webfolders_no">{TR_NO}</label>
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
                    <input type="radio" name="hp_status" value="1" id="hp_status_yes"{STATUS_YES}>
                    <label for="hp_status_yes">{TR_YES}</label>
                    <input type="radio" name="hp_status" value="0" id="hp_status_no"{STATUS_NO}>
                    <label for="hp_status_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
    <!-- BDP: submit_button -->
    <div class="buttons">
        <input name="Submit" type="submit" value="{TR_UPDATE}">
    </div>
    <!-- EDP: submit_button -->
</form>
