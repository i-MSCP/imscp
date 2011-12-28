
			<form name="addHtaccessUserFrm" method="post" action="protected_user_add.php">
				<table class="firstColFixed">
					<tr>
						<th colspan="2">{TR_HTACCESS_USER}</th>
					</tr>
					<tr>
						<td><label for="username">{TR_USERNAME}</label></td>
						<td><input name="username" id="username" type="text" value=""/></td>
					</tr>
					<tr>
						<td><label for="pass">{TR_PASSWORD}</label></td>
						<td><input type="password" id="pass" name="pass" value=""/></td>
					</tr>
					<tr>
						<td><label for="pass_rep">{TR_PASSWORD_REPEAT}</label></td>
						<td><input type="password" id="pass_rep" name="pass_rep" value=""/></td>
					</tr>
				</table>
				<div class="buttons">
					<input type="hidden" name="uaction" value="add_user"/>
					<input name="Submit" type="submit" value="{TR_ADD_USER}"/>
					<input name="Button" type="button" onclick="MM_goToURL('parent','protected_user_manage.php');return document.MM_returnValue" value="{TR_CANCEL}"/>
				</div>
			</form>
