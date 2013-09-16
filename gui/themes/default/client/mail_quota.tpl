
<form name="quotaFrm" method="post" action="mail_quota.php?id={MAIL_ID_VAL}">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_MAIL_ACCOUNT}: {MAIL_ADDRESS_VAL}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td><label for="quota">{TR_QUOTA}</label></td>
			<td><input name="quota" id="quota" type="text" value="{QUOTA}"/></td>
		</tr>
		</tbody>
	</table>

	<div class="buttons">
		<input name="submit" type="submit" value="{TR_UPDATE}"/>
		<a class ="link_as_button" href="mail_accounts.php">{TR_CANCEL}</a>
	</div>
</form>
