
<form name="addHtaccessGroup" method="post" action="protected_group_add.php">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_HTACCESS_GROUP}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td><label for="groupname">{TR_GROUPNAME}</label></td>
			<td><input name="groupname" type="text" id="groupname" value=""/></td>
		</tr>
		</tbody>
	</table>

	<div class="buttons">
		<input type="hidden" name="uaction" value="add_group"/>
		<input name="Submit" type="submit" value="{TR_ADD_GROUP}"/>
		<a class="link_as_button" href="protected_user_manage.php">{TR_CANCEL}</a>
	</div>
</form>
