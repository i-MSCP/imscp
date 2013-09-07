
<table class="firstColFixed">
	<thead>
	<tr>
		<th colspan="2">{TR_ACCOUNT_LIMITS}</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td >{DOMAINS}</td>
		<td>{DMN_MSG}</td>
	</tr>
	<tr>
		<td>{SUBDOMAINS}</td>
		<td>{SUB_MSG}</td>
	</tr>
	<tr>
		<td>{ALIASES}</td>
		<td>{ALS_MSG}</td>
	</tr>
	<tr>
		<td>{MAIL_ACCOUNTS}</td>
		<td>{MAIL_MSG}</td>
	</tr>
	<tr>
		<td>{TR_FTP_ACCOUNTS}</td>
		<td>{FTP_MSG}</td>
	</tr>
	<tr>
		<td>{SQL_DATABASES}</td>
		<td>{SQL_DB_MSG}</td>
	</tr>
	<tr>
		<td>{SQL_USERS}</td>
		<td>{SQL_USER_MSG}</td>
	</tr>
	</tbody>
</table>

<table class="firstColFixed">
	<thead>
	<tr>
		<th colspan="2">{TR_FEATURES}</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td>{TR_SUPPORT}</td>
		<td>{SUPPORT_STATUS}</td>
	</tr>
	<tr>
		<td>{TR_PHP_EDITOR}</td>
		<td>{PHP_EDITOR_STATUS}</td>
	</tr>
	<tr>
		<td>{TR_APS}</td>
		<td>{APS_STATUS}</td>
	</tr>
	</tbody>
</table>

<h2 class="traffic"><span>{TR_TRAFFIC_USAGE}</span></h2>

<!-- BDP: traffic_warning_message -->
<div class="warning">{TR_TRAFFIC_WARNING}</div>
<!-- EDP: traffic_warning_message -->

<p>{TRAFFIC_USAGE_DATA}</p>

<div class="graph">
	<span style="width:{TRAFFIC_PERCENT}%"></span>
</div>

<h2 class="diskusage"><span>{TR_DISK_USAGE}</span></h2>

<!-- BDP: disk_warning_message -->
<div class="warning">{TR_DISK_WARNING}</div>
<!-- EDP: disk_warning_message -->

<p>{DISK_USAGE_DATA}</p>

<div class="graph">
	<span style="width:{DISK_PERCENT}%"></span>
</div>
