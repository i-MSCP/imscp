
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
	<input name="submit" type="submit" value="{TR_CLEAR_LOG}"/>
</form>

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
		<td colspan="2">Loading...</td>
	</tr>
	</tbody>
</table>
<script>
	var oTable;

	function flashMessage(type, message) {
		$('<div />',
			{
				"class": 'flash_message ' + type,
				"html": $.parseHTML(message),
				"hide": true
			}
		).prependTo(".body").trigger('message_timeout');
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

	$(document).ready(function () {
		jQuery.fn.dataTableExt.oApi.fnProcessingIndicator = function (oSettings, onoff) {
			if (typeof(onoff) == "undefined") {
				onoff = true;
			}

			this.oApi._fnProcessingDisplay(oSettings, onoff);
		};

		oTable = $(".datatable").dataTable({
			oLanguage: {DATATABLE_TRANSLATIONS},
			iDisplayLength: {ROWS_PER_PAGE},
			bProcessing: true,
			bServerSide: true,
			info: false,
			lengthChange: false,
			filter:false,
			pagingType: "simple",
			sAjaxSource: "/admin/admin_log.php?action=get_logs",
			bStateSave: false,
			aoColumns: [
				{ mData: "log_time" },
				{ mData: "log_message" }
			],
			fnServerData: function (sSource, aoData, fnCallback) {
				$.ajax({
					dataType: "json",
					type: "GET",
					url: sSource,
					data: aoData,
					success: fnCallback,
					timeout: 3000,
					error: function (xhr, textStatus, error) {
						oTable.fnProcessingIndicator(false);
					}
				});
			}
		});

		oTable.on('draw.dt', function () {
			if(oTable.fnSettings().fnRecordsTotal() < 2) {
				$("#clear_log_frm").hide();
			}
		});

		$( "#clear_log_frm" ).submit(function( event ) {
			event.preventDefault();

			doRequest('POST', 'clear_logs', $(this).serialize()).done(function (data) {
				flashMessage('success', data.message);
				oTable.fnDraw();
			});
		});

		$(document).ajaxStart(function () { oTable.fnProcessingIndicator();});
		$(document).ajaxStop(function () { oTable.fnProcessingIndicator(false);});
		$(document).ajaxError(function (e, jqXHR, settings, exception) {
			if(jqXHR.status == 403) {
				window.location.href = "/index.php";
			} else if (jqXHR.responseJSON != "") {
				flashMessage("error", jqXHR.responseJSON.message);
			} else if (exception == "timeout") {
				flashMessage("error", {TR_TIMEOUT_ERROR});
			} else {
				flashMessage("error", {TR_UNEXPECTED_ERROR});
			}
		});
	});
</script>
