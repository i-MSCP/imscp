
			<script type="text/javascript">
				/*<![CDATA[*/
				$(document).ready(function () {
					$('.datatable').dataTable({"oLanguage": {DATATABLE_TRANSLATIONS}});
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
						<td colspan="5" class="buttons">
							<button name="rebuildIndex" type="submit" onclick="$('#uaction').val('rebuildIndex')">{TR_REBUILD_INDEX}</button>
							<button name="changeLanguage" type="submit" onclick="$('#uaction').val('changeLanguage');">{TR_SAVE}</button>
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
						<td><input type="radio" name="defaultLanguage" id="defaultLanguage_{LOCALE}" value="{LOCALE}" {LOCALE_CHECKED}/></td>
					</tr>
					<!-- EDP: language_block -->
					</tbody>
				</table>
				<!-- EDP: languages_block -->
				<table class="firstColFixed">
					<tr>
						<th colspan="2">{TR_INSTALL_NEW_LANGUAGE}</th>
					</tr>
					<tr>
						<td>{TR_LANGUAGE_FILE} <span class="upload_help icon i_help"  title="{TR_UPLOAD_HELP}" style="vertical-align: middle;">{TR_HELP}</span></td>
						<td>
							<input type="file" name="languageFile"/>
							<button name="uploadLanguage" type="submit" onclick="$('#uaction').val('uploadLanguage')" class="frm-button">{TR_INSTALL}</button>
						</td>
					</tr>
				</table>
				<div class="buttons">
					<input type="hidden" name="uaction" id="uaction" value=""/>
					<button name="cancel" type="button" onclick="location.href='settings.php'" class="frm-button">{TR_CANCEL}</button>
				</div>
			</form>
