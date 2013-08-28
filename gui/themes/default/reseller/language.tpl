
<!-- BDP: languages_available -->
<form name="client_change_language" method="post" action="language.php">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_LANGUAGE}</th>
		</tr>
		</thead>
		<tbody>
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
		</tbody>
	</table>
	<div class="buttons">
		<input name="Submit" type="submit" value="{TR_UPDATE}"/>
	</div>
</form>
<!-- EDP: languages_available -->
