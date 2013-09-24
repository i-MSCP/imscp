
<table class="firstColFixed">
	<thead>
	<tr>
		<th colspan="2">{TR_DOMAIN_ACCOUNT}</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td>{TR_ACCOUNT_NAME} / {TR_DOMAIN_NAME}</td>
		<td>{DOMAIN_NAME}</td>
	</tr>
	<!-- BDP: domain_alternative_url -->
	<tr>
		<td>{TR_DOMAIN_ALTERNATIVE_URL}</td>
		<td>
			<a id="dmn_tmp_access" href="{HREF_DOMAIN_ALTERNATIVE_URL}"target="_blank">{HREF_DOMAIN_ALTERNATIVE_URL}</a>
		</td>
	</tr>
	<!-- EDP: domain_alternative_url -->
	<tr>
		<td>{TR_DOMAIN_EXPIRES_DATE}</td>
		<td>{DOMAIN_REMAINING_TIME} {DOMAIN_EXPIRES_DATE}</td>
	</tr>
	</tbody>
</table>

<table class="firstColFixed">
	<thead>
	<tr>
		<th>{TR_FEATURE}</th>
		<th>{TR_FEATURE_STATUS}</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td>{TR_DOMAIN_FEATURE}</td>
		<td>{DOMAIN_FEATURE_STATUS}</td>
	</tr>
	<tr>
		<td>{TR_DOMAIN_ALIASES_FEATURE}</td>
		<td>{DOMAIN_ALIASES_FEATURE_STATUS}</td>
	</tr>
	<tr>
		<td>{TR_SUBDOMAINS_FEATURE}</td>
		<td>{SUBDOMAINS_FEATURE_STATUS}</td>
	</tr>
	<tr>
		<td>{TR_FTP_ACCOUNTS_FEATURE}</td>
		<td>{FTP_ACCOUNTS_FEATURE_STATUS}</td>
	</tr>
	<tr>
		<td>{TR_MAIL_ACCOUNTS_FEATURE}</td>
		<td>{MAIL_ACCOUNTS_FEATURE_STATUS}</td>
	</tr>
	<tr>
		<td>{TR_MAIL_QUOTA}</td>
		<td>{EMAIL_QUOTA_STATUS}</td>
	</tr>
	<tr>
		<td>{TR_SQL_DATABASES_FEATURE}</td>
		<td>{SQL_DATABASE_FEATURE_STATUS}</td>
	</tr>
	<tr>
		<td>{TR_SQL_USERS_FEATURE}</td>
		<td>{SQL_USERS_FEATURE_STATUS}</td>
	</tr>
	<tr>
		<td>{TR_PHP_SUPPORT_FEATURE}</td>
		<td>{PHP_FEATURE_STATUS}</td>
	</tr>
	<tr>
		<td>{TR_PHP_DIRECTIVES_EDITOR_SUPPORT_FEATURE}</td>
		<td>{PHP_DIRECTIVES_EDITOR_STATUS}</td>
	</tr>
	<tr>
		<td>{TR_CGI_SUPPORT_FEATURE}</td>
		<td>{CGI_FEATURE_STATUS}</td>
	</tr>
	<tr>
		<td>{TR_CUSTOM_DNS_RECORDS_FEATURE}</td>
		<td>{CUSTOM_DNS_RECORDS_FEATURE_STATUS}</td>
	</tr>
	<tr>
		<td>{TR_EXTERNAL_MAIL_SERVER_FEATURE}</td>
		<td>{EXTERNAL_MAIL_SERVERS_FEATURE_STATUS}</td>
	</tr>
	<tr>
		<td>{TR_APP_INSTALLER_FEATURE}</td>
		<td>{APP_INSTALLER_FEATURE_STATUS}</td>
	</tr>
	<tr>
		<td>{TR_WEBSTATS_FEATURE}</td>
		<td>{WEBSTATS_FEATURE_STATUS}</td>
	</tr>
	<tr>
		<td>{TR_BACKUP_FEATURE}</td>
		<td>{BACKUP_FEATURE_STATUS}</td>
	</tr>
	</tbody>
</table>

<h2 class="traffic"><span>{TR_TRAFFIC_USAGE}</span></h2>

<!-- BDP: traffic_warning -->
<div class="warning">{TR_TRAFFIC_WARNING}</div>
<!-- EDP: traffic_warning -->

<div class="graph"><span style="width:{TRAFFIC_PERCENT}%">&nbsp;</span></div>
<p style="margin-left:5px;font-size: smaller;">{TRAFFIC_USAGE_DATA}</p>

<h2 class="diskusage"><span>{TR_DISK_USAGE}</span></h2>

<!-- BDP: disk_warning -->
<div class="warning">{TR_DISK_WARNING}</div>
<!-- EDP: disk_warning -->

<div class="graph"><span style="width:{DISK_PERCENT}%">&nbsp;</span></div>
<p style="margin-left:5px;font-size: smaller;">{DISK_USAGE_DATA}</p>

<table class="firstColFixed">
	<thead>
	<tr>
		<th colspan="2">{TR_DISK_USAGE_DETAIL}</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td>{TR_DISK_FILE_USAGE}</td>
		<td>{DISK_FILESIZE}</td>
	</tr>
	<tr>
		<td>{TR_DISK_DATABASE_USAGE}</td>
		<td>{DISK_SQLSIZE}</td>
	</tr>
	<tr>
		<td>{TR_DISK_MAIL_USAGE}</td>
		<td>{DISK_MAILSIZE}</td>
	</tr>
	</tbody>
</table>
