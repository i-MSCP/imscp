
<form name="sql_add_database_frm" method="post" action="sql_database_add.php">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_DATABASE}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td><label for="db_name">{TR_DB_NAME}</label></td>
			<td><input type="text" id="db_name" name="db_name" value="{DB_NAME}"/></td>
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
						<option value="start"{START_ID_POS_SELECTED}>{TR_START_ID_POS}</option>
						<option value="end"{END_ID_POS_SELECTED}>{TR_END_ID_POS}</option>
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
		</tbody>
	</table>

	<div class="buttons">
		<input name="Add_New" type="submit" id="Add_New" value="{TR_ADD}"/>
	</div>
	<input type="hidden" name="uaction" value="add_db"/>
</form>
