
			<table class="firstColFixed">
				<tr>
					<th colspan="2">{TR_DOMAIN_DETAILS}</th>
				</tr>
				<tr>
					<td>{TR_DOMAIN_NAME}</td>
					<td>{VL_DOMAIN_NAME}</td>
				</tr>
				<tr>
					<td>{TR_DOMAIN_IP}</td>
					<td>{VL_DOMAIN_IP}</td>
				</tr>
				<tr>
					<td>{TR_STATUS}</td>
					<td>{VL_STATUS}</td>
				</tr>
				<tr>
					<td>{TR_PHP_SUPP}</td>
					<td>{VL_PHP_SUPP}</td>
				</tr>
				<tr>
					<td>{TR_CGI_SUPP}</td>
					<td>{VL_CGI_SUPP}</td>
				</tr>
				<tr>
					<td>{TR_DNS_SUPP}</td>
					<td>{VL_DNS_SUPP}</td>
				</tr>
				<tr>
					<td>{TR_SOFTWARE_SUPP}</td>
					<td>{VL_SOFTWARE_SUPP}</td>
				</tr>
				<tr>
					<td>{TR_MYSQL_SUPP}</td>
					<td>{VL_MYSQL_SUPP}</td>
				</tr>
				<tr>
					<td>{TR_TRAFFIC}</td>
					<td>
						<div class="graph"><span style="width:{VL_TRAFFIC_PERCENT}%">&nbsp;</span></div>
						{VL_TRAFFIC_USED} / {VL_TRAFFIC_LIMIT}
					</td>
				</tr>
				<tr>
					<td>{TR_DISK}</td>
					<td>
						<div class="graph"><span style="width:{VL_DISK_PERCENT}%">&nbsp;</span></div>
						{VL_DISK_USED} / {VL_DISK_LIMIT}
					</td>
				</tr>
			</table>

			<table class="firstColFixed">
				<tr>
					<th>{TR_FEATURE}</th>
					<th>{TR_USED}</th>
					<th>{TR_LIMIT}</th>
				</tr>
				<tr>
					<td>{TR_MAIL_ACCOUNTS}</td>
					<td>{VL_MAIL_ACCOUNTS_USED}</td>
					<td>{VL_MAIL_ACCOUNTS_LIIT}</td>
				</tr>
				<tr>
					<td>{TR_FTP_ACCOUNTS}</td>
					<td>{VL_FTP_ACCOUNTS_USED}</td>
					<td>{VL_FTP_ACCOUNTS_LIIT}</td>
				</tr>
				<tr>
					<td>{TR_SQL_DB_ACCOUNTS}</td>
					<td>{VL_SQL_DB_ACCOUNTS_USED}</td>
					<td>{VL_SQL_DB_ACCOUNTS_LIIT}</td>
				</tr>
				<tr>
					<td>{TR_SQL_USER_ACCOUNTS}</td>
					<td>{VL_SQL_USER_ACCOUNTS_USED}</td>
					<td>{VL_SQL_USER_ACCOUNTS_LIIT}</td>
				</tr>
				<tr>
					<td>{TR_SUBDOM_ACCOUNTS}</td>
					<td>{VL_SUBDOM_ACCOUNTS_USED}</td>
					<td>{VL_SUBDOM_ACCOUNTS_LIIT}</td>
				</tr>
				<tr>
					<td>{TR_DOMALIAS_ACCOUNTS}</td>
					<td>{VL_DOMALIAS_ACCOUNTS_USED}</td>
					<td>{VL_DOMALIAS_ACCOUNTS_LIIT}</td>
				</tr>
			</table>
