
<script>
	$(function() {
		$('.datatable').dataTable(
				{
					"language": imscp_i18n.core.dataTable,
					"stateSave": true,
					"pagingType": "simple",
					"columnDefs": [
						{ "type": "natural", "targets": [ 1 ] }
					]
				}
		);

		$('input[type="submit"]').click(function() {
			$("#uaction").val($(this).attr("name"));
		})
	});
</script>

<form name="i18nFrm" action="multilanguage.php" method="post" enctype="multipart/form-data">
	<table>
		<thead>
		<tr>
			<th colspan="2">{TR_IMPORT_NEW_LANGUAGE}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td>{TR_LANGUAGE_FILE} <span class="tips icon i_help" title="{TR_UPLOAD_HELP}"></span></td>
			<td>
				<input type="file" name="languageFile">
				<input name="uploadLanguage" type="submit" value="{TR_IMPORT}">
			</td>
		</tr>
		</tbody>
	</table>

	<!-- BDP: languages_block -->
	<table class="datatable firstColFixed">
		<thead>
		<tr>
			<th>{TR_LANGUAGE_NAME}</th>
			<th>{TR_NUMBER_TRANSLATED_STRINGS}</th>
			<th>{TR_LANGUAGE_REVISION}</th>
			<th>{TR_DEFAULT_LANGUAGE}</th>
		</tr>
		</thead>
		<tbody>
		<!-- BDP: language_block -->
		<tr>
			<td><label for="defaultLanguage_{LOCALE}"><span class="icon i_locale">{LANGUAGE_NAME}</span></label></td>
			<td>{NUMBER_TRANSLATED_STRINGS}</td>
			<td>{LANGUAGE_REVISION}</td>
			<td><input type="radio" name="defaultLanguage" id="defaultLanguage_{LOCALE}" value="{LOCALE}" {LOCALE_CHECKED}></td>
		</tr>
		<!-- EDP: language_block -->
		</tbody>
		<tr>
			<td class="buttons" colspan="4">
				<input name="rebuildIndex" type="submit" value="{TR_REBUILD_INDEX}">
				<input name="changeLanguage" type="submit" value="{TR_SAVE}">
			</td>
		</tr>
	</table>
	<!-- EDP: languages_block -->
	<input type="hidden" name="uaction" id="uaction" value=""/>
</form>
