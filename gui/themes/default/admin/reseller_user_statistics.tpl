
<script type="text/javascript">
	/*<![CDATA[*/
	$(document).ready(function () {
		$('.datatable').dataTable(
			{
				"oLanguage": {DATATABLE_TRANSLATIONS},
				"bStateSave": true
			}
		);
	});
	/*]]>*/
</script>

<!-- BDP: domain_statistics_entries_block -->
<table class="datatable" style="text-align: center;">
	<thead>
	<tr>
		<th>{TR_DOMAIN_NAME}</th>
		<th>{TR_TRAFF}</th>
		<th>{TR_DISK}</th>
		<th>{TR_WEB}</th>
		<th>{TR_FTP_TRAFF}</th>
		<th>{TR_SMTP}</th>
		<th>{TR_POP3}</th>
		<th>{TR_SUBDOMAIN}</th>
		<th>{TR_ALIAS}</th>
		<th>{TR_MAIL}</th>
		<th>{TR_FTP}</th>
		<th>{TR_SQL_DB}</th>
		<th>{TR_SQL_USER}</th>
	</tr>
	</thead>
	<tfoot>
	<tr>
		<td>{TR_DOMAIN_NAME}</td>
		<td>{TR_TRAFF}</td>
		<td>{TR_DISK}</td>
		<td>{TR_WEB}</td>
		<td>{TR_FTP_TRAFF}</td>
		<td>{TR_SMTP}</td>
		<td>{TR_POP3}</td>
		<td>{TR_SUBDOMAIN}</td>
		<td>{TR_ALIAS}</td>
		<td>{TR_MAIL}</td>
		<td>{TR_FTP}</td>
		<td>{TR_SQL_DB}</td>
		<td>{TR_SQL_USER}</td>
	</tr>
	</tfoot>
	<tbody>
	<!-- BDP: domain_statistics_entry_block -->
	<tr>
		<td>
			<a href="domain_statistics.php?domain_id={DOMAIN_ID}"
			   class="icon i_domain_icon" title="{TR_DOMAIN_TOOLTIP}">{DOMAIN_NAME}</a>
		</td>
		<td>
			<div class="graph">
				<span style="width: {TRAFF_PERCENT}%"></span>
				<strong>{TRAFF_PERCENT} %</strong>
			</div>
			{TRAFF_MSG}
		</td>
		<td>
			<div class="graph">
				<span style="width: {DISK_PERCENT}%"></span>
				<strong>{TRAFF_PERCENT} %</strong>
			</div>
			{DISK_MSG}
		</td>
		<td>{WEB}</td>
		<td>{FTP}</td>
		<td>{SMTP}</td>
		<td>{POP3}</td>
		<td>{SUB_MSG}</td>
		<td>{ALS_MSG}</td>
		<td>{MAIL_MSG}</td>
		<td>{FTP_MSG}</td>
		<td>{SQL_DB_MSG}</td>
		<td>{SQL_USER_MSG}</td>
	</tr>
	<!-- EDP: domain_statistics_entry_block -->
	</tbody>
</table>
<!-- EDP: domain_statistics_entries_block -->
