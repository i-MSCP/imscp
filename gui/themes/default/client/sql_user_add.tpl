<form name="sql_add_user_frm" method="post" action="sql_user_add.php">
	<!-- BDP: show_sqluser_list -->
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_ASSIGN_EXISTING_SQL_USER}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td><label for="sqluser_id">{TR_SQL_USER_NAME}</label></td>
			<td>
				<select name="sqluser_id" id="sqluser_id">
					<!-- BDP: sqluser_list -->
					<option value="{SQLUSER_ID}" {SQLUSER_SELECTED}>{SQLUSER_NAME}@{SQLUSER_HOST}</option>
					<!-- EDP: sqluser_list -->
				</select>
			</td>
		</tr>
		</tbody>
	</table>

	<div class="buttons">
		<input name="Add_Exist" type="submit" id="Add_Exist" value="{TR_ADD_EXIST}" tabindex="1"/>
	</div>

	<br/>

	<!-- EDP: show_sqluser_list -->
	<!-- BDP: create_sqluser -->
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_NEW_SQL_USER_DATA}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td><label for="user_name">{TR_USER_NAME}</label></td>
			<td><input type="text" id="user_name" name="user_name" value="{USER_NAME}"/></td>
		</tr>
		<tr>
			<td><label for="user_host">{TR_USER_HOST}<span class="tips icon i_help" title="{TR_USER_HOST_TIP}"></span></label></td>
			<td><input type="text" id="user_host" name="user_host" value="{USER_HOST}"/></td>
		</tr>
		<tr>
			<td>
				<!-- BDP: mysql_prefix_yes -->
				<label><input type="checkbox" name="use_dmn_id" {USE_DMN_ID} /></label>
				<!-- EDP: mysql_prefix_yes -->
				<!-- BDP: mysql_prefix_no -->
				<input type="hidden" name="use_dmn_id" value="on"/>
				<!-- EDP: mysql_prefix_no -->
				{TR_USE_DMN_ID}
			</td>
			<td>
				<!-- BDP: mysql_prefix_all -->
				<label>
					<select name="id_pos">
						<option value="start"{START_ID_POS_CHECKED}>{TR_START_ID_POS}</option>
						<option value="end"{START_ID_POS_CHECKED}>{TR_END_ID_POS}</option>
					</select>
				</label>
				<!-- EDP: mysql_prefix_all -->
				<!-- BDP: mysql_prefix_infront -->
				<input type="hidden" name="id_pos" value="start" checked="checked"/>{TR_START_ID_POS}
				<!-- EDP: mysql_prefix_infront -->
				<!-- BDP: mysql_prefix_behind -->
				<input type="hidden" name="id_pos" value="end" checked="checked"/>{TR_END_ID_POS}
				<!-- EDP: mysql_prefix_behind -->
			</td>
		</tr>
		<tr>
			<td><label for="pass">{TR_PASS}</label></td>
			<td><input id="pass" type="password" name="pass" value="" autocomplete="off"/></td>
		</tr>
		<tr>
			<td><label for="pass_rep">{TR_PASS_REP}</label></td>
			<td><input id="pass_rep" type="password" name="pass_rep" value="" autocomplete="off"/></td>
		</tr>
		</tbody>
	</table>

	<div class="buttons">
		<input type="hidden" name="uaction" value="add_user"/>
		<input type="hidden" name="id" value="{ID}"/>
		<input name="Add_New" type="submit" id="Add_New" value="{TR_ADD}"/>
		<a class="link_as_button" href="sql_manage.php">{TR_CANCEL}</a>
	</div>
	<!-- EDP: create_sqluser -->
</form>
