<script>
    $(function () {
        var $dataTable = $(".datatable").dataTable({
            language: imscp_i18n.core.dataTable,
            displayLength: 10,
            stateSave: true,
            pagingType: "simple"
        });

        $('#bulkActionsTop, #bulkActionsBottom').change(function () {
            $("select[name=\"bulkActions\"] option[value=" + $(this).val() + "]").attr("selected", "selected");
        });

        $("thead :checkbox, tfoot :checkbox").change(function () {
            $("table :checkbox").prop('checked', $(this).is(':checked'));
        });

        $dataTable.on("click", ".plugin_error", function () {
            var errDialog = $('<div>' + '<pre>' + $.trim($(this).html()) + '</pre>' + '</div>');
            var pluginName = $(this).attr('id');

            errDialog.dialog({
                modal: true,
                title: pluginName + " " + imscp_i18n.core.error_details,
                show: "clip",
                hide: "clip",
                minHeight: 200,
                minWidth: 500,
                buttons: [
                    {
                        text: imscp_i18n.core.force_retry,
                        click: function () {
                            window.location.replace("?retry=" + pluginName)
                        }
                    },
                    {
                        text: imscp_i18n.core.close,
                        click: function () {
                            $(this).dialog("close").dialog("destroy")
                        }
                    }
                ]
            });

            return false;
        });

        $('#bulk_actions').on("change", function () {
            var $button = $("#bulk_actions_submit");
            if ($(this).val() === 'noaction') {
                $button.prop("disabled", true);
            } else {
                $button.prop("disabled", false);
            }

            $button.button("refresh");
        });
    });
</script>
<p class="hint" style="font-variant: small-caps;font-size: small;">{TR_PLUGIN_HINT}</p><br>
<!-- BDP: plugins_block -->
<form name="plugin_frm" action="settings_plugins.php" method="post">
    <table class="datatable">
        <thead>
        <tr style="border: none;">
            <th style="width:21px;"><label><input type="checkbox"></label></th>
            <th style="width:200px">{TR_PLUGIN}</th>
            <th>{TR_DESCRIPTION}</th>
            <th>{TR_STATUS}</th>
            <th>{TR_ACTIONS}</th>
        </tr>
        </thead>
        <tfoot>
        <tr>
            <td><label><input type="checkbox"></label></td>
            <td>{TR_PLUGIN}</td>
            <td>{TR_DESCRIPTION}</td>
            <td>{TR_STATUS}</td>
            <td>{TR_ACTIONS}</td>
        </tr>
        </tfoot>
        <tbody>
        <!-- BDP: plugin_block -->
        <tr>
            <td><label><input type='checkbox' name='checked[]' value="{PLUGIN_NAME}"></label></td>
            <td><p style="font-size: 110%"><strong>{PLUGIN_NAME}</strong></p></td>
            <td>
                <p class="bold" style="font-size: 110%">{PLUGIN_DESCRIPTION}</p>
                <span class="italic" style="font-size: 90%">
                    <span class="bold">{TR_VERSION} {PLUGIN_VERSION}</span> (Build {PLUGIN_BUILD})<br>
                    <a href="mailto:{PLUGIN_MAILTO}">{TR_BY} {PLUGIN_AUTHOR}</a> |
                    <a href="{PLUGIN_SITE}" target="_blank">{TR_VISIT_PLUGIN_SITE}</a>
                </span>
            </td>
            <td>
                {PLUGIN_STATUS}
                <!-- BDP: plugin_status_details_block -->
                <span id="{PLUGIN_NAME}" class="plugin_error icon i_help" title="{TR_CLICK_FOR_MORE_DETAILS}">{PLUGIN_STATUS_DETAILS}</span>
                <!-- EDP: plugin_status_details_block -->
            </td>
            <td>
                <!-- BDP: plugin_activate_link -->
                <a style="vertical-align: middle;" class="icon i_open" href="settings_plugins.php?{ACTIVATE_ACTION}={PLUGIN_NAME}" title="{TR_ACTIVATE_TOOLTIP}"></a>
                <a style="vertical-align: middle;" class="icon i_close" href="settings_plugins.php?{UNINSTALL_ACTION}={PLUGIN_NAME}" title="{TR_UNINSTALL_TOOLTIP}"></a>
                <!-- EDP: plugin_activate_link -->
                <!-- BDP: plugin_deactivate_link -->
                <a style="vertical-align: middle;" class="icon i_close" href="settings_plugins.php?disable={PLUGIN_NAME}" title="{TR_DEACTIVATE_TOOLTIP}"></a>
                <a style="vertical-align: middle;" class="icon i_lock" href="settings_plugins.php?protect={PLUGIN_NAME}" title="{TR_PROTECT_TOOLTIP}"></a>
                <!-- EDP: plugin_deactivate_link -->
                <!-- BDP: plugin_protected_link -->
                <span style="vertical-align: middle;" class="icon i_unlock" title="{TR_UNPROTECT_TOOLTIP}">&nbsp;</span>
                <!-- EDP: plugin_protected_link -->
            </td>
        </tr>
        <!-- EDP: plugin_block -->
        </tbody>
    </table>
    <div style="float:left;">
        <select name="bulk_actions" id="bulk_actions">
            <option value="noaction" selected>{TR_BULK_ACTIONS}</option>
            <option value="install">{TR_INSTALL}</option>
            <option value="enable">{TR_ACTIVATE}</option>
            <option value="disable">{TR_DEACTIVATE}</option>
            <option value="uninstall">{TR_UNINSTALL}</option>
            <option value="protect">{TR_PROTECT}</option>
            <option value="delete">{TR_DELETE}</option>
        </select>
        <button type="submit" name="Submit" id="bulk_actions_submit" disabled>{TR_APPLY}</button>
    </div>
</form>
<!-- EDP: plugins_block -->
<div class="buttons">
    <a href="settings_plugins.php?update_plugin_list=1" class="link_as_button">{TR_UPDATE_PLUGIN_LIST}</a>
</div>
<br>
<h2 class="plugin"><span>{TR_PLUGIN_UPLOAD}</span></h2>
<form name="plugin_upload_frm" action="settings_plugins.php" method="post" enctype="multipart/form-data">
    <table class="firstColFixed">
        <thead>
        <tr>
            <th colspan="2">{TR_UPLOAD}</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>
                {TR_PLUGIN_ARCHIVE}
                <span class="icon i_help" title="{TR_PLUGIN_ARCHIVE_TOOLTIP}"></span>
            </td>
            <td>
                <input type="file" name="plugin_archive">
                <input type="submit" value="{TR_UPLOAD}">
            </td>
        </tr>
        </tbody>
    </table>
</form>
