
			<form name="sql_add_database_frm" method="post" action="sql_database_add.php">
				<table>
					<tr>
						<th colspan="2">
							{TR_DATABASE}
						</th>
					</tr>
					<tr>
						<td style="width:300px;"><label for="db_name">{TR_DB_NAME}</label></td>
						<td><input type="text" id="db_name" name="db_name" value="{DB_NAME}" /></td>
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
								<input type="radio" name="id_pos" value="start" {START_ID_POS_CHECKED} />{TR_START_ID_POS}<br />
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
				</table>
				<div class="buttons">
					<input name="Add_New" type="submit" class="button" id="Add_New" value="{TR_ADD}" />
				</div>
				<input type="hidden" name="uaction" value="add_db" />
			</form>
