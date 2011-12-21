
			<form name="sql_add_user_frm" method="post" action="sql_user_add.php">
				<!-- BDP: show_sqluser_list -->
				<table>
					<tr>
						<th colspan="2">{TR_ASSIGN_EXISTING_SQL_USER}</th>
					</tr>
					<tr>
						<td style="width: 300px;"><label for="sqluser_id">{TR_SQL_USER_NAME}</label></td>
						<td>
							<select name="sqluser_id" id="sqluser_id">
								<!-- BDP: sqluser_list -->
								<option value="{SQLUSER_ID}" {SQLUSER_SELECTED}>{SQLUSER_NAME}</option>
								<!-- EDP: sqluser_list -->
							</select>
						</td>
					</tr>
				</table>

				<div class="buttons">
					<input name="Add_Exist" type="submit" id="Add_Exist" value="{TR_ADD_EXIST}" tabindex="1" />
				</div>
				<br />
				<!-- EDP: show_sqluser_list -->

				<!-- BDP: create_sqluser -->
				<table>
					<tr>
						<th colspan="2">{TR_NEW_SQL_USER_DATA}</th>
					</tr>
					<tr>
						<td style="width: 300px;"><label for="user_name">{TR_USER_NAME}</label></td>
						<td>
							<input type="text" id="user_name" name="user_name" value="{USER_NAME}" />
						</td>
					</tr>
					<tr>
						<td>
							<!-- BDP: mysql_prefix_yes -->
							<input type="checkbox" name="use_dmn_id" {USE_DMN_ID} />
							<!-- EDP: mysql_prefix_yes -->
							<!-- BDP: mysql_prefix_no -->
							<input type="hidden" name="use_dmn_id" value="on" />
							<!-- EDP: mysql_prefix_no -->
						{TR_USE_DMN_ID}
						</td>
						<td>
							<!-- BDP: mysql_prefix_all -->
							<input type="radio" name="id_pos" value="start" {START_ID_POS_CHECKED} />{TR_START_ID_POS}
							<br />
							<input type="radio" name="id_pos" value="end" {END_ID_POS_CHECKED} />{TR_END_ID_POS}
							<!-- EDP: mysql_prefix_all -->
							<!-- BDP: mysql_prefix_infront -->
							<input type="hidden" name="id_pos" value="start" checked="checked" />{TR_START_ID_POS}
							<!-- EDP: mysql_prefix_infront -->
							<!-- BDP: mysql_prefix_behind -->
							<input type="hidden" name="id_pos" value="end" checked="checked" />{TR_END_ID_POS}
							<!-- EDP: mysql_prefix_behind -->
						</td>
					</tr>
					<tr>
						<td><label for="pass">{TR_PASS}</label></td>
						<td><input id="pass" type="password" name="pass" value="" />
						</td>
					</tr>
					<tr>
						<td><label for="pass_rep">{TR_PASS_REP}</label></td>
						<td>
							<input id="pass_rep" type="password" name="pass_rep" value="" />
						</td>
					</tr>
				</table>

				<div class="buttons">
					<input type="hidden" name="uaction" value="add_user" />
					<input type="hidden" name="id" value="{ID}" />
					<input name="Add_New" type="submit" class="button" id="Add_New" value="{TR_ADD}" />
					<input type="button" name="Submit" value="{TR_CANCEL}" onclick="location.href = 'sql_manage.php'" class="button" />
				</div>
				<!-- EDP: create_sqluser -->
			</form>
