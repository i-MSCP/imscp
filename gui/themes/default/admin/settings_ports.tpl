<!-- INCLUDE "../shared/layout/header.tpl" -->
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

			jQuery(document).ready(function() {
				jQuery.each(error_fields_ids, function() {
					jQuery('#'+this).css({'border' : '1px solid red', 'font-weight' : 'bolder'});
				});

				jQuery('input[name=submitForReset]').click(
					function(){jQuery('input[name=uaction]').val('reset');}
				);
			});
			/*]]>*/
		</script>
		<div class="header">
			{MAIN_MENU}
			<div class="logo"><img src="{ISP_LOGO}" alt="i-MSCP logo" /></div>
		</div>
		<div class="location">
			<div class="location-area">
				<h1 class="settings">{TR_MENU_SETTINGS}</h1>
			</div>
			<ul class="location-menu">
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="index.php">Admin</a></li>
				<li><a href="settings.php">Settings</a></li>
				<li><a href="settings_ports.php">Server ports</a></li>
			</ul>
		</div>
		<div class="left_menu">
			{MENU}
		</div>
		<div class="body">
			<h2 class="general"><span>{TR_SERVERPORTS}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<!-- start form edit -->
			<form name="frm_to_updt" method="post" action="settings_ports.php" onsubmit="return enable_for_post();">
				<fieldset>
					<legend>{TR_SHOW_UPDATE_SERVICE_PORT}</legend>
					<table style="text-align:center;">
						<tr>
							<th style="text-align:center">{TR_SERVICE}</th>
							<th style="text-align:center">{TR_IP}</th>
							<th style="text-align:center">{TR_PORT}</th>
							<th style="text-align:center">{TR_PROTOCOL}</th>
							<th style="text-align:center">{TR_SHOW}</th>
							<th style="text-align:center">{TR_ACTION}</th>
						</tr>
						<!-- BDP: service_ports -->
						<tr>
							<td style="text-align:left;">
								<label for="name{NUM}">{SERVICE}</label>
								<input name="var_name[]" type="hidden" id="var_name{NUM}" value="{VAR_NAME}" />
								<input name="custom[]" type="hidden" id="custom{NUM}" value="{CUSTOM}" />
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
									<img src="{THEME_COLOR_PATH}/images/icons/delete.png" width="16" height="16" border="0" style="vertical-align:middle" alt="" />{TR_DELETE}
								</a>
								<!-- EDP: port_delete_link -->
							</td>
						</tr>
						<!-- EDP: service_ports -->
					</table>
				</fieldset>
				<div class="buttons">
					<input type="hidden" name="uaction" value="update" />
					<input name="submitForUpdate" type="submit" class="button" value="{VAL_FOR_SUBMIT_ON_UPDATE}" />
					<input name="submitForReset" type="submit" class="button" value="{VAL_FOR_SUBMIT_ON_RESET}"/>
				</div>
			</form>
			<form name="frm_to_add" method="post" action="settings_ports.php">
				<fieldset>
					<legend>{TR_ADD_NEW_SERVICE_PORT}</legend>
					<table style="text-align:center;">
						<tr>
							<th style="text-align:center;">{TR_SERVICE}</th>
							<th style="text-align:center;">{TR_IP}</th>
							<th style="text-align:center;">{TR_PORT}</th>
							<th style="text-align:center;">{TR_PROTOCOL}</th>
							<th style="text-align:center;">{TR_SHOW}</th>
						</tr>
						<tr>
							<td><input name="name_new" type="text" id="name" value="{VAL_FOR_NAME_NEW}" maxlength="25"/></td>
							<td><input name="ip_new" type="text" id="ip" value="{VAL_FOR_IP_NEW}" maxlength="15" /></td>
							<td><input name="port_new" type="text" id="port" value="{VAL_FOR_PORT_NEW}" maxlength="6" /></td>
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
				</fieldset>
				<div class="buttons">
					<input type="hidden" name="uaction" value="add" />
					<input name="submitForAdd" type="submit" class="button" value="{VAL_FOR_SUBMIT_ON_ADD}" />
				</div>
			</form>
		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
