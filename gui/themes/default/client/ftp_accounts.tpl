
<script>
	function action_delete(subject) {
		return confirm(sprintf("{TR_MESSAGE_DELETE}", subject));
	}
</script>

<!-- BDP: ftp_accounts -->
<table>
	<thead>
	<tr>
		<th>{TR_FTP_ACCOUNT}</th>
		<th>{TR_FTP_ACCOUNT_STATUS}</th>
		<th>{TR_FTP_ACTION}</th>
	</tr>
	</thead>
	<!-- BDP: ftps_total -->
	<tfoot>
	<tr>
		<td colspan="3">{TR_TOTAL_FTP_ACCOUNTS} {TOTAL_FTP_ACCOUNTS}</td>
	</tr>
	</tfoot>
	<!-- EDP: ftps_total -->
	<tbody>
	<!-- BDP: ftp_item -->
	<tr>
		<td>{FTP_ACCOUNT}</td>
		<td>{FTP_ACCOUNT_STATUS}</td>
		<td>
			<!-- BDP: ftp_actions -->
			<!-- BDP: ftp_easy_login -->
			<a href="ftp_auth.php?id={UID}" target="_blank" class="icon i_filemanager">{TR_LOGINAS}</a>
			<!-- EDP: ftp_easy_login -->
			<a href="ftp_edit.php?id={UID}" class="icon i_edit">{TR_EDIT}</a>
			<a href="ftp_delete.php?id={UID}" class="icon i_delete"
			   onclick="return action_delete('{FTP_ACCOUNT}');">{TR_DELETE}</a>
			<!-- EDP: ftp_actions -->
		</td>
	</tr>
	<!-- EDP: ftp_item -->
	</tbody>
</table>
<!-- EDP: ftp_accounts -->
