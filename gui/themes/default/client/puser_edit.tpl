
			<h2 class="users"><span>{TR_EDIT_USER}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<form name="edit_user" method="post" action="protected_user_edit.php">
				<table>
					<tr>
						<td>{TR_USERNAME}</td>
						<td>{UNAME}</td>
					</tr>
					<tr>
						<td><label for="pass">{TR_PASSWORD}</label></td>
						<td><input type="password" id="pass" name="pass" value="" /></td>
					</tr>
					<tr>
						<td><label for="pass_rep">{TR_PASSWORD_REPEAT}</label></td>
						<td><input type="password" id="pass_rep" name="pass_rep" value="" /></td>
					</tr>
				</table>
				<div class="buttons">
					 <input name="Submit" type="submit" value="{TR_UPDATE}" />
					<input name="Button" type="button" onclick="MM_goToURL('parent','protected_user_manage.php');return document.MM_returnValue" value="{TR_CANCEL}" />
				</div>

				<input type="hidden" name="nadmin_name" value="{UID}" />
				<input type="hidden" name="uaction" value="modify_user" />
			</form>
