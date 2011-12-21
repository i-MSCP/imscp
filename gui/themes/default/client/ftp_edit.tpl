
		<form name="editFrm" method="post" action="ftp_edit.php">
			<table>
				<tr>
					<th colspan="2">
						{TR_FTP_USER_DATA}
					</th>
				</tr>
				<tr>
					<td><label for="ftp_account">{TR_FTP_ACCOUNT}</label></td>
					<td>
						<input id="ftp_account" type="text" name="username" value="{FTP_ACCOUNT}" readonly="readonly"/>
					</td>
				</tr>
				<tr>
					<td><label for="pass">{TR_PASSWORD}</label></td>
					<td><input id="pass" type="password" name="pass" value=""/></td>
				</tr>
				<tr>
					<td><label for="pass_rep">{TR_PASSWORD_REPEAT}</label></td>
					<td><input id="pass_rep" type="password" name="pass_rep" value=""/></td>
				</tr>
				<tr>
					<td><input id="use_other_dir" type="checkbox" name="use_other_dir" {USE_OTHER_DIR_CHECKED} />
						<label for="use_other_dir">{TR_USE_OTHER_DIR}</label></td>
					<td>
						<input type="text" name="other_dir" value="{OTHER_DIR}"/>
						<a href="#" onclick="showFileTree();" class="icon i_bc_folder">{CHOOSE_DIR}</a>
					</td>
				</tr>
			</table>
			<div class="buttons">
				<input type="hidden" name="uaction" value="edit_user"/>
				<input type="hidden" name="id" value="{ID}"/>
				<input name="submit" type="submit" value="{TR_CHANGE}"/>
			</div>
		</form>
