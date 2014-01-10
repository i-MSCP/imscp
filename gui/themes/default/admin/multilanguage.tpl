
<script type="text/javascript">
	/*<![CDATA[*/
	$(document).ready(function () {
		$('.datatable').dataTable(
			{
				"oLanguage": {DATATABLE_TRANSLATIONS},
				"bStateSave": true
			}
		);
		$('.dataTables_paginate div').imscpTooltip();
	});
	/*]]>*/
</script>

<form name="i18nFrm" action="multilanguage.php" method="post" enctype="multipart/form-data">
	<!-- BDP: languages_block -->
	<table class="datatable firstColFixed">
		<thead>
		<tr>
			<th>{TR_LANGUAGE_NAME}</th>
			<th>{TR_NUMBER_TRANSLATED_STRINGS}</th>
			<th>{TR_LANGUAGE_REVISION}</th>
			<th>{TR_LAST_TRANSLATOR}</th>
			<th>{TR_DEFAULT_LANGUAGE}</th>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<td class="buttons" colspan="5" style="height:auto;padding:0;margin: 0">
				<button name="rebuildIndex" type="submit"
						onclick="$('#uaction').val('rebuildIndex')">{TR_REBUILD_INDEX}</button>
				<button name="changeLanguage" type="submit"
						onclick="$('#uaction').val('changeLanguage')">{TR_SAVE}</button>
			</td>
		</tr>
		</tfoot>
		<tbody>
		<!-- BDP: language_block -->
		<tr>
			<td><label for="defaultLanguage_{LOCALE}"><span class="icon i_locale">{LANGUAGE_NAME}</span></label></td>
			<td>{NUMBER_TRANSLATED_STRINGS}</td>
			<td>{LANGUAGE_REVISION}</td>
			<td>{LAST_TRANSLATOR}</td>
			<td>
				<input type="radio" name="defaultLanguage" id="defaultLanguage_{LOCALE}" value="{LOCALE}"
					   {LOCALE_CHECKED}/>
			</td>
		</tr>
		<!-- EDP: language_block -->
		</tbody>
	</table>
	<!-- EDP: languages_block -->

	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_INSTALL_NEW_LANGUAGE}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td>
				{TR_LANGUAGE_FILE}
				<span class="tips icon i_help" title="{TR_UPLOAD_HELP}"></span>
			</td>
			<td>
				<input type="file" name="languageFile"/>
				<button name="uploadLanguage" type="submit" onclick="$('#uaction').val('uploadLanguage')">{TR_INSTALL}</button>
			</td>
		</tr>
		</tbody>
	</table>

	<div class="buttons">
		<input type="hidden" name="uaction" id="uaction" value=""/>
		<a class="link_as_button" href="settings.php">{TR_CANCEL}</a>
	</div>
</form>
