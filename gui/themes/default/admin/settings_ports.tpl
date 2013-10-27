
<script type="text/javascript">
	/*<![CDATA[*/
	function action_delete(service) {
		return confirm(sprintf("{TR_MESSAGE_DELETE}", service));
	}

	function enable_for_post() {
		for (var i = 0; i < document.frm_to_updt.length; i++) {
			for (var j = 0; j < document.frm_to_updt.elements[i].length; j++) {
				if (document.frm_to_updt.elements[i].name == 'port_type[]') {
					document.frm_to_updt.elements[i].disabled = false;
				}
			}
		}

		return true;
	}

	var error_fields_ids = {ERROR_FIELDS_IDS};

	$(document).ready(function () {
		$('.datatable').dataTable(
			{
				"oLanguage": {DATATABLE_TRANSLATIONS},
				"iDisplayLength": 5,
				"bStateSave": true,
				"bFilter": false
			}
		);

		$.each(error_fields_ids, function () {
			$('#' + this).css({ 'border': '1px solid red', 'font-weight': 'bolder'});
		});

		$('input[name=submitForReset]').click( function () { $('input[name=uaction]').val('reset'); } );
	});
	/*]]>*/
</script>

<!-- start form edit -->
<form name="editFrm" method="post" action="settings_ports.php" onsubmit="return enable_for_post();">
	<table class="datatable">
		<thead>
		<tr>
			<th>{TR_SERVICE}</th>
			<th>{TR_IP}</th>
			<th>{TR_PORT}</th>
			<th>{TR_PROTOCOL}</th>
			<th>{TR_SHOW}</th>
			<th>{TR_ACTION}</th>
		</tr>
		</thead>
		<tbody>
		<!-- BDP: service_ports -->
		<tr>
			<td>
				<label for="name{NUM}">{SERVICE}</label>
				<input name="var_name[]" type="hidden" id="var_name{NUM}" value="{VAR_NAME}"/>
			</td>
			<td>
				<label>
					<input name="ip[]" type="text" id="ip{NUM}" value="{IP}" maxlength="15" {DISABLED} />
				</label>
			</td>
			<td>
				<label>
					<input name="port[]" type="text" id="port{NUM}" value="{PORT}" maxlength="5" {DISABLED} />
				</label>
			</td>
			<td>
				<label>
					<select name="port_type[]" id="port_type{NUM}" {DISABLED}>
						<option value="udp" {SELECTED_UDP}>{TR_UDP}</option>
						<option value="tcp" {SELECTED_TCP}>{TR_TCP}</option>
					</select>
				</label>
			</td>
			<td>
				<label>
					<select name="show_val[]" id="show_val{NUM}">
						<option value="1" {SELECTED_ON}>{TR_ENABLED}</option>
						<option value="0" {SELECTED_OFF}>{TR_DISABLED}</option>
					</select>
				</label>
			</td>
			<td>
				<!-- BDP: port_delete_link -->
				<a href="{URL_DELETE}" class="icon i_delete" onclick="return action_delete('{NAME}')">{TR_DELETE}</a>
				<!-- EDP: port_delete_link -->
			</td>
		</tr>
		<!-- EDP: service_ports -->
		</tbody>
	</table>

	<div class="buttons">
		<input type="hidden" name="uaction" value="update"/>
		<input name="submitForUpdate" type="submit" value="{VAL_FOR_SUBMIT_ON_UPDATE}"/>
		<input name="submitForReset" type="submit" value="{VAL_FOR_SUBMIT_ON_RESET}"/>
	</div>
</form>

<form name="addFrm" method="post" action="settings_ports.php">
	<table>
		<thead>
		<tr>
			<th>{TR_SERVICE}</th>
			<th>{TR_IP}</th>
			<th>{TR_PORT}</th>
			<th>{TR_PROTOCOL}</th>
			<th>{TR_SHOW}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td>
				<label><input name="name_new" type="text" id="name" value="{VAL_FOR_NAME_NEW}" maxlength="25"/></label>
			</td>
			<td>
				<label><input name="ip_new" type="text" id="ip" value="{VAL_FOR_IP_NEW}" maxlength="15"/></label>
			</td>
			<td>
				<label><input name="port_new" type="text" id="port" value="{VAL_FOR_PORT_NEW}" maxlength="6"/></label>
			</td>
			<td>
				<label>
					<select name="port_type_new" id="port_type">
						<option value="udp">{TR_UDP}</option>
						<option value="tcp" selected="selected">{TR_TCP}</option>
					</select>
				</label>
			</td>
			<td>
				<label>
					<select name="show_val_new" id="show_val">
						<option value="1" selected="selected">{TR_ENABLED}</option>
						<option value="0">{TR_DISABLED}</option>
					</select>
				</label>
			</td>
		</tr>
		</tbody>
	</table>
	<div class="buttons">
		<input type="hidden" name="uaction" value="add"/>
		<input name="submitForAdd" type="submit" value="{VAL_FOR_SUBMIT_ON_ADD}"/>
	</div>
</form>
