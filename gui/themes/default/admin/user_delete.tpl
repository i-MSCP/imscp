
<script>
	$(document).ready(function () {
		$("#delete").on('change', function() {
			if($(this).is(':checked')) {
				$("#submit").show();
			} else {
				$("#submit").hide();
			}
		}).trigger("change");
	});
</script>

<form name="admin_delete_customer_frm" method="post" action="user_delete.php">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2"><strong>{TR_ACCOUNT_SUMMARY}</strong></th>
		</tr>
		</thead>
		<tbody>
		<!-- BDP: mail_list -->
		<tr>
			<td colspan="2"><strong><i>{TR_EMAILS}</i></strong>
			</td>
		</tr>
		<!-- BDP: mail_item -->
		<tr>
			<td>{MAIL_ADDR}</td>
			<td>{MAIL_TYPE}</td>
		</tr>
		<!-- EDP: mail_item -->
		<!-- EDP: mail_list -->
		<!-- BDP: ftp_list -->
		<tr>
			<td colspan="2"><strong><i>{TR_FTP_ACCOUNTS}</i></strong></td>
		</tr>
		<!-- BDP: ftp_item -->
		<tr>
			<td>{FTP_USER}</td>
			<td>{FTP_HOME}</td>
		</tr>
		<!-- EDP: ftp_item -->
		<!-- EDP: ftp_list -->
		<!-- BDP: dmn_list -->
		<tr>
			<td colspan="2"><strong><i>{TR_DOMAINS}</i></strong></td>
		</tr>
		<!-- BDP: dmn_item -->
		<tr>
			<td colspan="2">{DOMAIN_NAME}</td>
		</tr>
		<!-- EDP: dmn_item -->
		<!-- EDP: dmn_list -->
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
			<td colspan="2"><strong><i>{TR_SUBDOMAINS}</i></strong></td>
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
			<td colspan="2"><strong><i>{TR_DATABASES}</i></strong></td>
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
				<p>{TR_REALLY_WANT_TO_DELETE_CUSTOMER_ACCOUNT}</p>
				<input type="checkbox" value="1" name="delete" id="delete"/>
				<label for="delete">{TR_YES_DELETE_ACCOUNT}</label>
			</td>
		</tr>
		</tbody>
	</table>

	<div class="buttons">
		<input type="hidden" name="user_id" value="{USER_ID}"/>
		<input type="submit" id="submit" value="{TR_DELETE}"/>
		<a class="link_as_button" href="manage_users.php">{TR_CANCEL}</a>
	</div>
</form>
