
<script type="text/javascript">
	/* <![CDATA[ */
	function action_delete(url, subject, object) {
		if (object == 'database') {
			msg = "{TR_DATABASE_MESSAGE_DELETE}"
		} else {
			msg = "{TR_USER_MESSAGE_DELETE}"
		}

		if (confirm(sprintf(msg, subject))) {
			location = url;
		}

		return false;
	}
	/* ]]> */
</script>

<!-- BDP: sql_databases_users_list -->
<table class="firstColFixed">
	<thead>
	<tr>
		<th>{TR_DATABASE}</th>
		<th>{TR_ACTIONS}</th>
	</tr>
	</thead>
	<tbody>
	<!-- BDP: sql_databases_list -->
	<tr>
		<td><strong>{DB_NAME}</strong></td>
		<td>
			<a href="sql_user_add.php?id={DB_ID}" class="icon i_add_user" title="{TR_ADD_USER}">{TR_ADD_USER}</a>
			<a href="#" class="icon i_delete"
			   onclick="return action_delete('sql_database_delete.php?id={DB_ID}', '{DB_NAME}', 'database')"
			   title="{TR_DELETE}">{TR_DELETE}</a>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<!-- BDP: sql_users_list -->
			<table>
				<tr>
					<td>{DB_USER}</td>
					<td>
						<a href="pma_auth.php?id={USER_ID}" class="icon i_pma" target="{PMA_TARGET}"
						   title="{TR_LOGIN_PMA}">{TR_PHPMYADMIN}</a>
						<a href="sql_change_password.php?id={USER_ID}" class="icon i_change_password"
						   title="{TR_CHANGE_PASSWORD}">{TR_CHANGE_PASSWORD}</a>
						<a href="#" class="icon i_delete"
						   onclick="return action_delete('sql_delete_user.php?id={USER_ID}', '{DB_USER}', 'user')"
						   title="{TR_DELETE}">{TR_DELETE}</a>
					</td>
				</tr>
			</table>
			<!-- EDP: sql_users_list -->
		</td>
	</tr>
	<!-- EDP: sql_databases_list -->
	</tbody>
</table>
<!-- EDP: sql_databases_users_list -->
