
			<h2 class="users"><span>{TR_ADD_USER}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<form name="add_user_group" method="post" action="protected_user_add.php">
				<table>
					<tr>
						<td style="width: 300px;"><label for="username">{TR_USERNAME}</label></td>
						<td><input name="username" id="username" type="text" value="" /></td>
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
					 <input name="Submit" type="submit" value="{TR_ADD_USER}" />
					<input name="Button" type="button" onclick="MM_goToURL('parent','protected_user_manage.php');return document.MM_returnValue" value="{TR_CANCEL}" />
				</div>

				<input type="hidden" name="uaction" value="add_user" />
			</form>
