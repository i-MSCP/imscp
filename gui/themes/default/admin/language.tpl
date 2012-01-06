
			<form name="adminChangeLanguage" method="post" action="language.php">
				<table class="firstColFixed">
					<tr>
						<th colspan="2">{TR_LANGUAGE}</th>
					</tr>
					<tr>
						<td><label for="def_language">{TR_CHOOSE_LANGUAGE}</label></td>
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
					<input name="submit" type="submit" class="button" value="{TR_UPDATE}"/>
				</div>
			</form>
