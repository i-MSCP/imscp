
<form name="passwordUpdate" method="post" action="password_update.php">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="3">{TR_PASSWORD_DATA}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td><label for="current_password">{TR_CURRENT_PASSWORD}</label></td>
			<td><input id="current_password" name="current_password" type="password" value="" autocomplete="off"/></td>
		</tr>
		<tr>
			<td><label for="password">{TR_PASSWORD}</label></td>
			<td><input name="password" id="password" type="password" value="" class="pwd_generator" autocomplete="off"/></td>
		</tr>
		<tr>
			<td><label for="cpassword">{TR_PASSWORD_CONFIRMATION}</label></td>
			<td><input name="password_confirmation" id="cpassword" type="password" value="" autocomplete="off"/></td>
		</tr>
		</tbody>
	</table>
	<div class="buttons">
		<input type="submit" name="Submit" value="{TR_UPDATE}"/>
	</div>
</form>
