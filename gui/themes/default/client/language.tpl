
		<form action="language.php" method="post" id="client_language_frm">
			<table>
				<tr>
					<th colspan="2">{TR_LANGUAGE}</th>
				</tr>
				<tr>
					<td style="width:300px;"><label for="def_language">{TR_CHOOSE_LANGUAGE}</label></td>
					<td>
						<select name="def_language" id="def_language">
							<!-- BDP: def_language -->
							<option value="{LANG_VALUE}" {LANG_SELECTED}>{LANG_NAME}</option>
							<!-- EDP: def_language -->
						</select>
					</td>
				</tr>
			</table>
			<div class="buttons">
				<input type="submit" name="submit" value="{TR_UPDATE}" />
			</div>
		</form>
