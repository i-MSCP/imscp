
<script>
    $(function () {
        $("#domain_id").on("change", function () {
            window.location.href = '?' + $.param({
                    domain_id: $(this).val(),
                    domain_type: $(this).find("option:selected").data("domain-type")
                });
        });
    });
</script>
<form name="editFrm" method="post" action="phpini.php">
    <table class="firstColFixed">
        <thead>
        <tr>
            <th colspan="2">{TR_PHP_SETTINGS}</th>
        </tr>
        </thead>
        <tbody>
        <!-- BDP: domain_list_block -->
        <tr>
            <td>
                <label for="domain_id">{TR_DOMAIN} <span class="icon i_help" title="{TR_DOMAIN_TOOLTIP}"></span></label>
            </td>
            <td>
                <select name="domain_id" id="domain_id">
                    <!-- BDP: domain_name_block -->
                    <option value="{DOMAIN_ID}" data-domain-type="{DOMAIN_TYPE}"{SELECTED}>{DOMAIN_NAME_UNICODE}</option>
                    <!-- EDP: domain_name_block -->
                </select>
                <input type="hidden" name="domain_type" value="{DOMAIN_TYPE}">
            </td>
        </tr>
        <!-- EDP: domain_list_block -->
        <!-- BDP: allow_url_fopen_block -->
        <tr>
            <td><label for="allow_url_fopen">{TR_ALLOW_URL_FOPEN}</label></td>
            <td>
                <div class="radio">
                    <input type="radio" name="allow_url_fopen" id="allow_url_fopen_on" value="on"{ALLOW_URL_FOPEN_ON}>
                    <label for="allow_url_fopen_on">{TR_YES}</label>
                    <input type="radio" name="allow_url_fopen" id="allow_url_fopen_off" value="off"{ALLOW_URL_FOPEN_OFF}>
                    <label for="allow_url_fopen_off">{TR_NO}</label>
                </div>
            </td>
        </tr>
        <!-- EDP: allow_url_fopen_block -->
        <!-- BDP: display_errors_block -->
        <tr>
            <td><label for="display_errors">{TR_DISPLAY_ERRORS}</label></td>
            <td>
                <div class="radio">
                    <input type="radio" name="display_errors" id="display_errors_on" value="on"{DISPLAY_ERRORS_ON}>
                    <label for="display_errors_on">{TR_YES}</label>
                    <input type="radio" name="display_errors" id="display_errors_off" value="off"{DISPLAY_ERRORS_OFF}>
                    <label for="display_errors_off">{TR_NO}</label>
                </div>
            </td>
        </tr>
        <!-- EDP: display_errors_block -->
        <!-- BDP: error_reporting_block -->
        <tr>
            <td><label for="error_reporting">{TR_ERROR_REPORTING}</label></td>
            <td>
                <select name="error_reporting" id="error_reporting">
                    <option value="E_ALL &amp; ~E_NOTICE &amp; ~E_STRICT &amp; ~E_DEPRECATED"{ERROR_REPORTING_0}>{TR_ERROR_REPORTING_DEFAULT}</option>
                    <option value="E_ALL &amp; ~E_DEPRECATED &amp; ~E_STRICT"{ERROR_REPORTING_1}>{TR_ERROR_REPORTING_PRODUCTION}</option>
                    <option value="-1"{ERROR_REPORTING_2}>{TR_ERROR_REPORTING_DEVELOPEMENT}</option>
                </select>
            </td>
        </tr>
        <!-- EDP: error_reporting_block -->
        <!-- BDP: disable_functions_block -->
        <tr>
            <td><label>{TR_DISABLE_FUNCTIONS}</label></td>
            <td>
                <div class="checkbox">
                    <input type="checkbox" name="show_source" id="show_source" value="show_source"{SHOW_SOURCE}>
                    <label for="show_source">show_source</label>
                    <input type="checkbox" name="system" id="system" value="system"{SYSTEM}>
                    <label for="system">system</label>
                    <input type="checkbox" name="shell_exec" id="shell_exec" value="shell_exec"{SHELL_EXEC}>
                    <label for="shell_exec">shell_exec</label>
                    <input type="checkbox" name="passthru" id="passthru" value="passthru"{PASSTHRU}>
                    <label for="passthru">passthru</label>
                    <input name="exec" id="exec" type="checkbox" value="exec"{EXEC}>
                    <label for="exec">exec</label>
                    <input type="checkbox" name="phpinfo" id="phpinfo" value="phpinfo"{PHPINFO}>
                    <label for="phpinfo">phpinfo</label>
                    <input type="checkbox" name="shell" id="shell" value="shell"{SHELL}>
                    <label for="shell">shell</label>
                    <input type="checkbox" name="symlink" id="symlink" value="symlink"{SYMLINK}>
                    <label for="symlink">symlink</label>
                    <input type="checkbox" name="proc_open" id="proc_open" value="proc_open"{PROC_OPEN}>
                    <label for="proc_open">proc_open</label>
                    <input type="checkbox" name="popen" id="popen" value="popen"{POPEN}>
                    <label for="popen">popen</label>
                    <!-- BDP: mail_function_block -->
                    <input type="checkbox" name="mail" id="mail" value="mail"{MAIL}>
                    <label for="mail">mail</label>
                    <!-- EDP: mail_function_block -->
                </div>
            </td>
        </tr>
        <!-- EDP: disable_functions_block -->
        <!-- BDP: disable_exec_block -->
        <tr>
            <td>
                <label>{TR_DISABLE_FUNCTIONS_EXEC}</label>
                <span class="icon i_help" id="exec_help" title="{TR_EXEC_HELP}"></span>
            </td>
            <td>
                <div class="radio">
                    <input type="radio" name="exec" id="exec_yes" value="yes"{EXEC_YES}>
                    <label for="exec_yes">{TR_YES}</label>
                    <input type="radio" name="exec" value="no" id="exec_no"{EXEC_NO}>
                    <label for="exec_no">{TR_NO}</label>
                </div>
            </td>
        </tr>
        <!-- EDP: disable_exec_block -->
        </tbody>
    </table>
    <div class="buttons">
        <input name="Submit" type="submit" value="{TR_UPDATE}">
        <a class="link_as_button" href="domains_manage.php">{TR_CANCEL}</a>
    </div>
</form>
