
<form name="quotaFrm" method="post" action="mail_quota.php?id={MAIL_ID_VAL}">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">
				<span style="vertical-align: middle">{TR_MAIL_ACCOUNT} : {MAIL_ADDRESS_VAL}</span>
			</th>
		</tr>
		</thead>
		<tbody>
		<!-- BDP: quota_frm -->
		<tr>
			<td><label for="quota">{TR_QUOTA}</label></td>
			<td><input name="quota" id="quota" type="quota" value="{QUOTA}"/></td>
		</tr>
		<!-- EDP: quota_frm -->
		</tbody>
	</table>

	<div class="buttons">
		<input name="submit" type="submit" value="{TR_UPDATE}"/>
		<a class ="link_as_button" href="mail_accounts.php">{TR_CANCEL}</a>
	</div>
</form>
