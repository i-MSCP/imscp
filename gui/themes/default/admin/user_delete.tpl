
			<form name="admin_delete_domain_frm" method="post" action="user_delete.php">
				<table>
					<tr>
						<th colspan="2"><strong>{TR_DOMAIN_SUMMARY}</strong></th>
					</tr>

					<!-- BDP: mail_list -->
					<tr>
						<td colspan="2"><strong><i>{TR_DOMAIN_EMAILS}</i></strong>
						</td>
					</tr>
					<!-- BDP: mail_item -->
					<tr>
						<td style="width:300px">{MAIL_ADDR}</td>
						<td>{MAIL_TYPE}</td>
					</tr>
					<!-- EDP: mail_item -->
					<!-- EDP: mail_list -->

					<!-- BDP: ftp_list -->
					<tr>
						<td colspan="2"><strong><i>{TR_DOMAIN_FTPS}</i></strong></td>
					</tr>
					<!-- BDP: ftp_item -->
					<tr>
						<td>{FTP_USER}</td>
						<td>{FTP_HOME}</td>
					</tr>
					<!-- EDP: ftp_item -->
					<!-- EDP: ftp_list -->

					<!-- BDP: als_list -->
					<tr>
						<td colspan="2"><strong><i>{TR_DOMAIN_ALIASES}</i></strong>
						</td>
					</tr>
					<!-- BDP: als_item -->
					<tr>
						<td>{ALS_NAME}</td>
						<td>{ALS_MNT}</td>
					</tr>
					<!-- EDP: als_item -->
					<!-- EDP: als_list -->

					<!-- BDP: sub_list -->
					<tr>
						<td colspan="2"><strong><i>{TR_DOMAIN_SUBS}</i></strong></td>
					</tr>
					<!-- BDP: sub_item -->
					<tr>
						<td>{SUB_NAME}</td>
						<td>{SUB_MNT}</td>
					</tr>
					<!-- EDP: sub_item -->
					<!-- EDP: sub_list -->

					<!-- BDP: db_list -->
					<tr>
						<td colspan="2"><strong><i>{TR_DOMAIN_DBS}</i></strong></td>
					</tr>
					<!-- BDP: db_item -->
					<tr>
						<td>{DB_NAME}</td>
						<td>{DB_USERS}</td>
					</tr>
					<!-- EDP: db_item -->
					<!-- EDP: db_list -->
					<tr>
						<td colspan="2">&nbsp;</td>
					</tr>
					<tr>
						<td colspan="2">
							<p>{TR_REALLY_WANT_TO_DELETE_DOMAIN}</p>
							<input type="hidden" name="domain_id" value="{DOMAIN_ID}" />
							<input type="checkbox" value="1" name="delete" id="delete" style="vertical-align: middle" />
							<label for="delete">{TR_YES_DELETE_DOMAIN}</label>
						</td>
					</tr>
				</table>
				<div class="buttons">
					<input type="submit" value="{TR_BUTTON_DELETE}" />
				</div>
			</form>
