
<form action="" method="post" id="update_autoresponder_message">
	<h3><label for="autoresponder_message">{TR_AUTORESPONDER_MESSAGE}</label></h3>
	<textarea id="autoresponder_message" name="autoresponder_message">{AUTORESPONDER_MESSAGE}</textarea>
	<div class="buttons">
		<input type="hidden" name="mail_account_id" value="{MAIL_ACCOUNT_ID}"/>
		<input type="button" name="cancel" value="{TR_CANCEL}" onclick="window.location='mail_accounts.php'"/>
		<input type="submit" name="submit" value="{TR_ACTION}"/>
	</div>
</form>
