
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
						<th style="width:200px">{TR_PLUGIN}</th>
						<th>{TR_DESCRIPTION}</th>
					</tr>
					</thead>
					<tfoot>
					<tr>
						<th><input type="checkbox"/></th>
						<th>{TR_PLUGIN}</th>
						<th>{TR_DESCRIPTION}</th>
					</tr>
					</tfoot>
					<tbody>
					<!-- BDP: plugin_block -->
					<tr>
						<td><input type='checkbox' name='checked[]' value="{PLUGIN_NAME}"/></td>
						<td>
							<p><strong>{PLUGIN_NAME}</strong></p>
							<!-- BDP: plugin_activate_link -->
							<a class="icon i_open" href="settings_plugins.php?activate={PLUGIN_NAME}" title="{TR_ACTIVATE_TOOLTIP}">{TR_ACTIVATE}</a>
							<!-- EDP: plugin_activate_link -->

							<!-- BDP: plugin_deactivate_link -->
							<a class="icon i_close" href="settings_plugins.php?deactivate={PLUGIN_NAME}" title="{TR_DEACTIVATE_TOOLTIP}">{TR_DEACTIVATE}</a>
							<a class="icon i_change_password" href="settings_plugins.php?protect={PLUGIN_NAME}" title="{TR_PROTECT_TOOLTIP}">{TR_PROTECT}</a>
							<!-- EDP: plugin_deactivate_link -->
						</td>
						<td>
							<p>{PLUGIN_DESCRIPTION}</p>
							<span class="bold italic">
								{TR_VERSION} {PLUGIN_VERSION} | <a href="mailto:{PLUGIN_MAILTO}">{TR_BY} {PLUGIN_AUTHOR}</a> | <a href="{PLUGIN_SITE}" target="_blank">{TR_VISIT_PLUGIN_SITE}</a>
							</span>
						</td>
					</tr>
					<!-- EDP: plugin_block -->
					</tbody>
				</table>
				<div style="float:left;">
					<select name="bulkActions" id="bulkActionsBottom">
						<option value="dummy">{TR_BULK_ACTIONS}</option>
						<option value="activate">{TR_ACTIVATE}</option>
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

