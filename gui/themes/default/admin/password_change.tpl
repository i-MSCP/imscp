
			<form name="updatePasswordFrm" action="password_change.php" method="post">
				<table class="firstColFixed">
					<tr>
						<th colspan="2">{TR_PASSWORD}</th>
					</tr>
					<tr>
						<td>
							<label for="curr_pass">{TR_CURR_PASSWORD}</label>
						</td>
						<td><input type="password" name="curr_pass" id="curr_pass" value=""/></td>
					</tr>
					<tr>
						<td><label for="pass">{TR_PASSWORD}</label></td>
						<td><input type="password" name="pass" id="pass" value=""/></td>
					</tr>
					<tr>
						<td><label for="pass_rep">{TR_PASSWORD_REPEAT}</label></td>
						<td><input type="password" name="pass_rep" id="pass_rep" value=""/></td>
					</tr>
				</table>
				<div class="buttons">
					<input type="hidden" name="uaction" value="updt_pass"/>
					<input name="submit" type="submit" value="{TR_UPDATE}"/>
				</div>
			</form>
