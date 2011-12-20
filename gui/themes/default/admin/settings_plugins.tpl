
<script type="text/javascript">
/* <![CDATA[ */
	$(document).ready(function(){
		$('select[name=bulkActions] ').change(function(){
			$('select[name=bulkActions] option[value='+$(this).val()+']').attr("selected", "selected");
		});
	});
/*]]>*/
</script>
		<form name="pluginsFrm" action="settings_plugins.php" method="post">
			<div>
				<select name="bulkActions" id="bulkActionsTop">
					<option value="dummy">Bulk actions</option>
					<option value="enable">Enable</option>
					<option value="reset">Reset</option>
					<option value="disable">Disable</option>
				</select>
				<label for="bulkActionsTop"><input type="submit" name="submit" value="Apply" /></label>
			</div>
			<table>
				<tr>
					<th scope='col'>
						<input type="checkbox" />
					</th>
					<th>Plugin</th>
					<th>Description</th>
				</tr>
				<tr>
					<td scope='row'>
						<input type="checkbox" />
					</td>
					<td>
						<p><strong>Debug Bar</strong></p>
						<a class="icon i_edit" href="settings_plugins.php?settings=1">Settings</a> |
						<a class="icon i_reload" href="settings_plugins.php?reset=1">Reset</a> |
						<a class="icon i_delete" href="settings_plugins.php?delete=1">Disable</a>
					</td>
					<td>
						<p>Development helper that provides useful debug information displayed in a small bar at the bottom of every page</p>
						<span class="bold italic">Version 0.0.1 | By Laurent Declercq / i-MSCP team</span>
					</td>
				</tr>
				<tr>
					<th scope='col'>
						<input type="checkbox" />
					</th>
					<th>Plugin</th>
					<th>Description</th>
				</tr>
			</table>
			<div>
				<select name="bulkActions" id="bulkActionsBottom">
					<option value="dummy">Bulk actions</option>
					<option value="enable">Enable</option>
					<option value="reset">Reset</option>
					<option value="disable">Disable</option>
				</select>
				<label for="bulkActionsBottom"><input type="submit" name="submit" value="Apply" /></label>
			</div>
		</form>
