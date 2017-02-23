
<script>
    $(function () {
        var $dataTable;

        function flashMessage(type, message) {
            $('<div>', {
                "class": "flash_message " + type,
                "html": $.parseHTML(message),
                "hide": true
            }).prependTo(".body").trigger('message_timeout');
        }

        function doRequest(rType, action, data) {
            return $.ajax({
                dataType: "json",
                type: rType,
                url: "/admin/admin_log.php?action=" + action,
                data: data,
                timeout: 3000
            });
        }

        jQuery.fn.dataTableExt.oApi.fnProcessingIndicator = function (settings, onoff) {
            if (typeof(onoff) == "undefined") {
                onoff = true;
            }

            this.oApi._fnProcessingDisplay(settings, onoff);
        };

        $dataTable = $(".datatable").dataTable({
            language: imscp_i18n.core.dataTable,
            displayLength: parseInt({ROWS_PER_PAGE}),
            processing: true,
            serverSide: true,
            pagingType: "simple",
            ajaxSource: "/admin/admin_log.php?action=get_logs",
            stateSave: true,
            order: [0, "desc"],
            columns: [
                {data: "log_time"},
                {data: "log_message"}
            ],
            serverData: function (source, data, callback) {
                $.ajax({
                    dataType: "json",
                    type: "GET",
                    url: source,
                    data: data,
                    success: callback,
                    timeout: 3000
                }).fail(function (jqXHR) {
                    $dataTable.fnProcessingIndicator(false);
                    flashMessage('error', $.parseJSON(jqXHR.responseText).message);
                });
            }
        });

        $dataTable.on("draw.dt", function () {
            if ($dataTable.fnSettings().fnRecordsTotal() < 2) {
                $("#clear_log").hide();
            } else {
                $("#clear_log").show();
            }
        });

        $("#clear_log_frm").submit(function (event) {
            event.preventDefault();

            doRequest("POST", "clear_logs", $(this).serialize()).done(function (data) {
                flashMessage("success", data.message);
                $dataTable.fnDraw();
            });
        });

        $(document).ajaxStart(function () {
            $dataTable.fnProcessingIndicator();
        }).ajaxStop(function () {
            $dataTable.fnProcessingIndicator(false);
        }).ajaxError(function (e, jqXHR, settings, exception) {
            if (jqXHR.status == 403) {
                window.location.replace("/index.php");
            } else if (jqXHR.responseJSON !== "undefined") {
                flashMessage("error", jqXHR.responseJSON.message);
            } else if (exception == "timeout") {
                flashMessage("error", {TR_TIMEOUT_ERROR});
            } else {
                flashMessage("error", {TR_UNEXPECTED_ERROR});
            }
        });
    });
</script>
<table class="datatable firstColFixed">
    <thead>
    <tr>
        <th>{TR_DATE}</th>
        <th>{TR_MESSAGE}</th>
    </tr>
    </thead>
    <tfoot>
    <tr>
        <td>{TR_DATE}</td>
        <td>{TR_MESSAGE}</td>
    </tr>
    </tfoot>
    <tbody>
    <tr>
        <td colspan="2">{TR_LOADING_DATA}</td>
    </tr>
    </tbody>
    <tbody id="clear_log">
    <tr>
        <td colspan="2" style="text-align:right;">
            <form name="clear_log_frm" id="clear_log_frm">
                <label for="uaction_clear">{TR_CLEAR_LOG_MESSAGE}</label>
                <select name="uaction_clear" id="uaction_clear">
                    <option value="0">{TR_CLEAR_LOG_EVERYTHING}</option>
                    <option value="2">{TR_CLEAR_LOG_LAST2}</option>
                    <option value="4">{TR_CLEAR_LOG_LAST4}</option>
                    <option value="12">{TR_CLEAR_LOG_LAST12}</option>
                    <option value="26">{TR_CLEAR_LOG_LAST26}</option>
                    <option value="52">{TR_CLEAR_LOG_LAST52}</option>
                </select>
                <input name="Submit" type="submit" value="{TR_CLEAR_LOG}">
            </form>
        </td>
    </tr>
    </tbody>
</table>
