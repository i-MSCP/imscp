
			<script type="text/javascript">
				/*<![CDATA[*/
				function action_delete(url, service) {
					if (!confirm(sprintf("{TR_MESSAGE_DELETE}", service))) {
						return false;
					}

					location = url;
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
					$.each(error_fields_ids, function() {
					$('#'+this).css({'border' : '1px solid red', 'font-weight' : 'bolder'});
				});

					$('input[name=submitForReset]').click(
					function(){$('input[name=uaction]').val('reset');}
				);
				});
				/*]]>*/
			</script>
			<!-- start form edit -->
			<form name="editFrm" method="post" action="settings_ports.php" onsubmit="return enable_for_post();">
				<table>
					<tr>
						<th>{TR_SERVICE}</th>
						<th>{TR_IP}</th>
						<th>{TR_PORT}</th>
						<th>{TR_PROTOCOL}</th>
						<th>{TR_SHOW}</th>
						<th>{TR_ACTION}</th>
					</tr>
					<!-- BDP: service_ports -->
					<tr>
						<td>
							<label for="name{NUM}">{SERVICE}</label>
							<input name="var_name[]" type="hidden" id="var_name{NUM}" value="{VAR_NAME}"/>
							<input name="custom[]" type="hidden" id="custom{NUM}" value="{CUSTOM}"/>
						</td>
						<td>
							<input name="ip[]" type="text" id="ip{NUM}" value="{IP}" maxlength="15" {PORT_READONLY} />
						</td>
						<td>
							<input name="port[]" type="text" id="port{NUM}" value="{PORT}" maxlength="5" {PORT_READONLY} />
						</td>
						<td>
							<select name="port_type[]" id="port_type{NUM}" {PROTOCOL_READONLY}>
								<option value="udp" {SELECTED_UDP}>{TR_UDP}</option>
								<option value="tcp" {SELECTED_TCP}>{TR_TCP}</option>
							</select>
						</td>
						<td>
							<select name="show_val[]" id="show_val{NUM}">
								<option value="1" {SELECTED_ON}>{TR_ENABLED}</option>
								<option value="0" {SELECTED_OFF}>{TR_DISABLED}</option>
							</select>
						</td>
						<td>
							<!-- BDP: port_delete_show -->
							{TR_DELETE}
							<!-- EDP: port_delete_show -->
							<!-- BDP: port_delete_link -->
							<a href="#" onclick="action_delete('{URL_DELETE}', '{NAME}')" class="link">
								<img src="{THEME_COLOR_PATH}/images/icons/delete.png" width="16" height="16" border="0"
									 style="vertical-align:middle" alt=""/>{TR_DELETE}
							</a>
							<!-- EDP: port_delete_link -->
						</td>
					</tr>
					<!-- EDP: service_ports -->
				</table>
				<div class="buttons">
					<input type="hidden" name="uaction" value="update"/>
					<input name="submitForUpdate" type="submit" class="button" value="{VAL_FOR_SUBMIT_ON_UPDATE}"/>
					<input name="submitForReset" type="submit" class="button" value="{VAL_FOR_SUBMIT_ON_RESET}"/>
				</div>
			</form>

			<form name="addFrm" method="post" action="settings_ports.php">
				<table>
					<tr>
						<th>{TR_SERVICE}</th>
						<th>{TR_IP}</th>
						<th>{TR_PORT}</th>
						<th>{TR_PROTOCOL}</th>
						<th>{TR_SHOW}</th>
					</tr>
					<tr>
						<td><input name="name_new" type="text" id="name" value="{VAL_FOR_NAME_NEW}" maxlength="25"/></td>
						<td><input name="ip_new" type="text" id="ip" value="{VAL_FOR_IP_NEW}" maxlength="15"/></td>
						<td><input name="port_new" type="text" id="port" value="{VAL_FOR_PORT_NEW}" maxlength="6"/>
						</td>
						<td>
							<select name="port_type_new" id="port_type">
								<option value="udp">{TR_UDP}</option>
								<option value="tcp" selected="selected">{TR_TCP}</option>
							</select>
						</td>
						<td>
							<select name="show_val_new" id="show_val">
								<option value="1" selected="selected">{TR_ENABLED}</option>
								<option value="0">{TR_DISABLED}</option>
							</select>
						</td>
					</tr>
				</table>
				<div class="buttons">
					<input type="hidden" name="uaction" value="add"/>
					<input name="submitForAdd" type="submit" class="button" value="{VAL_FOR_SUBMIT_ON_ADD}"/>
				</div>
			</form>
