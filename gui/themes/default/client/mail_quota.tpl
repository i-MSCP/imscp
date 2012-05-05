
		<form name="quotaFrm" method="post" action="mail_quota.php?id={MAIL_ID_VAL}">
			<table class="firstColFixed">
				<tr>
					<th colspan="2">
						<span style="vertical-align: middle">{TR_MAIL_ACCOUNT} : {MAIL_ADDRESS_VAL}</span>
					</th>
				</tr>
				<!-- BDP: quota_frm -->
				<tr>
					<td><label for="quota">{TR_QUOTA}</label></td>
					<td><input name="quota" id="quota" type="quota" value="{QUOTA}"/></td>
				</tr>
				<!-- EDP: quota_frm -->
			</table>
			<div class="buttons">
				<input name="submit" type="submit" value="{TR_UPDATE}"/>
				<input name="cancel" type="button" onclick="MM_goToURL('parent','mail_accounts.php');return document.MM_returnValue" value="{TR_CANCEL}"/>
			</div>
		</form>
