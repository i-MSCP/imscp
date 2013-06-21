
			<script type="text/javascript">
			/* <![CDATA[ */
				$(document).ready(function(){
					// $('.datatable').dataTable({"oLanguage": {DATATABLE_TRANSLATIONS}});

					$('#dialog_box').dialog({modal: true, autoOpen:false, hide:'blind', show:'blind', width:'500'});
					$('.i_change_password').click(function(e) {
						e.preventDefault();
						var href = $(this).attr("href");

						$("#dialog_box").dialog('option', 'buttons', {
							"{TR_PROTECT}" : function() {document.location.href = href;},
							"{TR_CANCEL}" : function() {$(this).dialog("close");}
						});

						$("#dialog_box").dialog("open");
					});

					$('#bulkActionsTop, #bulkActionsBottom').change(function(){
						$('select[name="bulkActions"] option[value='+$(this).val()+']').attr("selected", "selected");
					});

					$("th :checkbox").change(function(){$("table :checkbox").prop('checked', $(this).is(':checked'));});

					$('button[name=updatePluginList]').click(function(){document.location = "?updatePluginList=all";});
				});
			/*]]>*/
			</script>
			<!-- BDP: plugins_block -->

			<div id="dialog_box" title="{TR_PLUGIN_CONFIRMATION_TITLE}">
				<p>{TR_PROTECT_CONFIRMATION}</p>
			</div>

			<form name="pluginsFrm" action="settings_plugins.php" method="post">
				<div style="float:left;">
					<select name="bulkActions" id="bulkActionsTop">
						<option value="dummy">{TR_BULK_ACTIONS}</option>
						<option value="activate">{TR_ACTIVATE}</option>
						<option value="install">{TR_REINSTALL}</option>
						<option value="deactivate">{TR_DEACTIVATE}</option>
						<option value="protect">{TR_PROTECT}</option>
					</select>
					<label for="bulkActionsTop"><input type="submit" name="Submit" value="{TR_APPLY}"/></label>
				</div>
				<div class="buttons" style="margin: 0px;">
					<button type="button" name="updatePluginList">{TR_UPDATE_PLUGIN_LIST}</button>
				</div>
				<table>
					<thead>
					<tr>
						<th style="width:21px;"><input type="checkbox"/></th>
						<th style="width:150px">{TR_PLUGIN}</th>
						<th>{TR_DESCRIPTION}</th>
						<th>{TR_STATUS}</th>
						<th>{TR_ACTIONS}</th>
					</tr>
					</thead>
					<tfoot>
					<tr>
						<th><input type="checkbox"/></th>
						<th>{TR_PLUGIN}</th>
						<th>{TR_DESCRIPTION}</th>
						<th>{TR_STATUS}</th>
						<th>{TR_ACTIONS}</th>
					</tr>
					</tfoot>
					<tbody>
					<!-- BDP: plugin_block -->
					<tr>
						<td><input type='checkbox' name='checked[]' value="{PLUGIN_NAME}"/></td>
						<td>
							<p><strong>{PLUGIN_NAME}</strong></p>
						</td>
						<td>
							<p>{PLUGIN_DESCRIPTION}</p>
							<span class="bold italic">
								{TR_VERSION} {PLUGIN_VERSION} | <a href="mailto:{PLUGIN_MAILTO}">{TR_BY} {PLUGIN_AUTHOR}</a> | <a href="{PLUGIN_SITE}" target="_blank">{TR_VISIT_PLUGIN_SITE}</a>
							</span>
						</td>
						<td>{PLUGIN_STATUS}</td>
						<td>
							<!-- BDP: plugin_activate_link -->
							<a style="vertical-align: middle" class="icon i_open" href="settings_plugins.php?activate={PLUGIN_NAME}" title="{TR_ACTIVATE_TOOLTIP}">{TR_ACTIVATE}</a>
							<!-- EDP: plugin_activate_link -->

							<!-- BDP: plugin_reinstall_link -->
							<a style="vertical-align: middle" class="icon i_refresh" href="settings_plugins.php?reinstall={PLUGIN_NAME}" title="{TR_REINSTALL_TOOLTIP}">{TR_REINSTALL}</a>
							<!-- EDP: plugin_reinstall_link -->

							<!-- BDP: plugin_deactivate_link -->
							<a style="vertical-align: middle" class="icon i_close" href="settings_plugins.php?deactivate={PLUGIN_NAME}" title="{TR_DEACTIVATE_TOOLTIP}">{TR_DEACTIVATE}</a>
							<a style="vertical-align: middle" class="icon i_change_password" href="settings_plugins.php?protect={PLUGIN_NAME}" title="{TR_PROTECT_TOOLTIP}">{TR_PROTECT}</a>
							<!-- EDP: plugin_deactivate_link -->
						</td>
					</tr>
					<!-- EDP: plugin_block -->
					</tbody>
				</table>
				<div style="float:left;">
					<select name="bulkActions" id="bulkActionsBottom">
						<option value="dummy">{TR_BULK_ACTIONS}</option>
						<option value="activate">{TR_ACTIVATE}</option>
						<option value="install">{TR_REINSTALL}</option>
						<option value="deactivate">{TR_DEACTIVATE}</option>
						<option value="protect">{TR_PROTECT}</option>
					</select>
					<label for="bulkActionsBottom"><input type="submit" name="Submit" value="{TR_APPLY}"/></label>
				</div>
			</form>
			<!-- EDP: plugins_block -->

			<div class="buttons" style="margin: 0px;">
				<button type="button" name="updatePluginList">{TR_UPDATE_PLUGIN_LIST}</button>
			</div>

			<br /><br/>

			<h2 class="plugin"><span>{TR_PLUGIN_UPLOAD}</span></h2>
			<form name="pluginsUploadFrm" action="settings_plugins.php" method="post" enctype="multipart/form-data">
				<table class="firstColFixed">
					<tr>
						<th colspan="2">{TR_UPLOAD}</th>
					</tr>
					<tr>
						<td>{TR_PLUGIN_ARCHIVE}<span class="upload_help icon i_help" title="{TR_PLUGIN_ARCHIVE_TOOLTIP}" style="vertical-align: middle;">Help</span></td>
						<td>
							<input type="file" name="pluginArchive" />
							<input type="submit" class="frm-button" value="{TR_UPLOAD}"/>
						</td>
					</tr>
				</table>
			</form>
