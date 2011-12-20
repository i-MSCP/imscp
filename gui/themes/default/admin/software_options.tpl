
			<form action="software_options.php" method="post" name="appssettings" id="appssettings">
				<fieldset>
					<legend>{TR_MAIN_OPTIONS}</legend>
					<table>
						<tr>
							<td style="width:300px;">
								<label for="use_webdepot">{TR_USE_WEBDEPOT}</label>
							</td>
							<td>
								<select name="use_webdepot" id="use_webdepot">
									<option value="0"{USE_WEBDEPOT_SELECTED_OFF}>{TR_DISABLED}</option>
									<option value="1"{USE_WEBDEPOT_SELECTED_ON}>{TR_ENABLED}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td width="300"><label for="webdepot_xml_url">{TR_WEBDEPOT_XML_URL}</label></td>
							<td><input type="text" name="webdepot_xml_url" id="webdepot_xml_url" value="{WEBDEPOT_XML_URL_VALUE}"/></td>
						</tr>
						<tr>
							<td width="300"><label>{TR_WEBDEPOT_LAST_UPDATE}</label></td>
							<td>{WEBDEPOT_LAST_UPDATE_VALUE}</td>
						</tr>
					</table>
				</fieldset>
				<div class="buttons">
					<input name="Submit" type="submit" class="button" value="{TR_APPLY_CHANGES}" />
					<input type="hidden" name="uaction" value="apply" />
				</div>
			</form>
