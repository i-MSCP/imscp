
			<form name="client_change_pass_frm" method="post" action="password_change.php">
				<table>
					<tr>
						<th colspan="2">{TR_PASSWORD}</th>
					</tr>
					<tr>
						<td style="width: 300px;"><label for="curr_pass">{TR_CURR_PASSWORD}</label></td>
						<td><input id="curr_pass" name="curr_pass" type="password" value="" /></td>
					</tr><tr>
						<td><label for="pass">{TR_PASSWORD}</label></td>
						<td><input name="pass" id="pass" type="password" value="" /></td>
					</tr><tr>
						<td><label for="pass_rep">{TR_PASSWORD_REPEAT}</label></td>
						<td><input name="pass_rep" id="pass_rep" type="password" value="" /></td>
					</tr>
				</table>
				<div class="buttons">
					<input type="hidden" name="uaction" value="updt_pass" />
					<input type="submit" name="Submit" value="{TR_UPDATE_PASSWORD}" />
				</div>
			</form>
