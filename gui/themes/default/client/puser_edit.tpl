
<form name="editHtaccessUserFrm" method="post" action="protected_user_edit.php">
	<table>
		<thead>
		<tr>
			<th colspan="2">{TR_HTACCESS_USER}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td>{TR_USERNAME}</td>
			<td>{UNAME}</td>
		</tr>
		<tr>
			<td><label for="pass">{TR_PASSWORD}</label></td>
			<td><input type="password" id="pass" name="pass" value="" autocomplete="off"/></td>
		</tr>
		<tr>
			<td><label for="pass_rep">{TR_PASSWORD_REPEAT}</label></td>
			<td><input type="password" id="pass_rep" name="pass_rep" value="" autocomplete="off"/></td>
		</tr>
		</tbody>
	</table>

	<div class="buttons">
		<input type="hidden" name="nadmin_name" value="{UID}"/>
		<input type="hidden" name="uaction" value="modify_user"/>
		<input name="Submit" type="submit" value="{TR_UPDATE}"/>
		<a class="link_as_button" href="protected_user_manage.php">{TR_CANCEL}</a>
	</div>
</form>
