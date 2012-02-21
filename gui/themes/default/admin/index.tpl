
			<!-- BDP: imscp_update_message -->
			<div class="info">{UPDATE}</div>
			<!-- EDP: imscp_update_message -->

			<!-- BDP: imscp_database_update_message -->
			<div class="info">
				{TR_DATABASE_UPDATE}
				<a href="database_update.php" class="link">{TR_DATABASE_UPDATE_LINK}</a>
			</div>
			<!-- EDP: imscp_database_update_message -->

			<table class="firstColFixed">
				<tr>
					<th>{TR_PROPERTIES}</th>
					<th>{TR_VALUES}</th>
				</tr>
				<tr>
					<td>{TR_ADMIN_USERS}</td>
					<td>{ADMIN_USERS}</td>
				</tr>
				<tr>
					<td>{TR_RESELLER_USERS}</td>
					<td>{RESELLER_USERS}</td>
				</tr>
				<tr>
					<td>{TR_NORMAL_USERS}</td>
					<td>{NORMAL_USERS}</td>
				</tr>
				<tr>
					<td>{TR_DOMAINS}</td>
					<td>{DOMAINS}</td>
				</tr>
				<tr>
					<td>{TR_SUBDOMAINS}</td>
					<td>{SUBDOMAINS}</td>
				</tr>
				<tr>
					<td>{TR_DOMAINS_ALIASES}</td>
					<td>{DOMAINS_ALIASES}</td>
				</tr>
				<tr>
					<td>{TR_MAIL_ACCOUNTS}</td>
					<td>{MAIL_ACCOUNTS}</td>
				</tr>
				<tr>
					<td>{TR_FTP_ACCOUNTS}</td>
					<td>{FTP_ACCOUNTS}</td>
				</tr>
				<tr>
					<td>{TR_SQL_DATABASES}</td>
					<td>{SQL_DATABASES}</td>
				</tr>
				<tr>
					<td>{TR_SQL_USERS}</td>
					<td>{SQL_USERS}</td>
				</tr>
			</table>

			<h2 class="traffic"><span>{TR_SERVER_TRAFFIC}</span></h2>

			<p>{TRAFFIC_WARNING}</p>

			<div class="graph">
				<span style="width:{TRAFFIC_PERCENT}%">&nbsp;</span>
			</div>
