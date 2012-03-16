
		<script type="text/javascript">
			/*<![CDATA[*/
			function action_delete(url, subject) {
				if (!confirm(sprintf("{TR_MESSAGE_DELETE}", subject))) {
					return false;
				}

				location = url;
			}
			/*]]>*/
		</script>
			<!-- BDP: ftp_accounts -->
			<table>
				<thead>
					<tr>
						<th>{TR_FTP_ACCOUNT}</th>
						<th>{TR_FTP_ACTION}</th>
					</tr>
				</thead>
				<tbody>
					<!-- BDP: ftp_item -->
					<tr>
						<td>{FTP_ACCOUNT}</td>
						<td>
							<a href="ftp_auth.php?id={UID}" target="{FILEMANAGER_TARGET}" class="icon i_filemanager">{TR_LOGINAS}</a>
							<a href="ftp_edit.php?id={UID}" class="icon i_edit">{TR_EDIT}</a>
							<a href="#" class="icon i_delete" onclick="action_delete('ftp_delete.php?id={UID}', '{FTP_ACCOUNT}'); return false;">{TR_DELETE}</a>
						</td>
					</tr>
					<!-- EDP: ftp_item -->
				</tbody>
				<!-- BDP: ftps_total -->
				<tfoot>
					<tr>
						<td colspan="2">{TR_TOTAL_FTP_ACCOUNTS}
							&nbsp;<strong>{TOTAL_FTP_ACCOUNTS}</strong></td>
					</tr>
				</tfoot>
				<!-- EDP: ftps_total -->
			</table>
			<!-- EDP: ftp_accounts -->
