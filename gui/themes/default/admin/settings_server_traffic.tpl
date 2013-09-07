
<form action="settings_server_traffic.php" method="post" name="serverTrafficFrm">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_SET_SERVER_TRAFFIC_SETTINGS}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td><label for="max_traffic">{TR_MAX_TRAFFIC}</label></td>
			<td><input name="max_traffic" type="text" id="max_traffic" value="{MAX_TRAFFIC}"/> {TR_MIB}</td>
		</tr>
		<tr>
			<td><label for="traffic_warning">{TR_WARNING}</label></td>
			<td><input name="traffic_warning" type="text" id="traffic_warning" value="{TRAFFIC_WARNING}"/> {TR_MIB}</td>
		</tr>
		</tbody>
	</table>
	<div class="buttons">
		<input name="Submit" type="submit" value="{TR_UPDATE}"/>
	</div>
</form>
