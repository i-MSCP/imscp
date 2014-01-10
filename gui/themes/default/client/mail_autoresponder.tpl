
<form action="" method="post" id="update_autoresponder_message">
	<h3><label for="autoresponder_message">{TR_AUTORESPONDER_MESSAGE}</label></h3>
	<textarea id="autoresponder_message" name="autoresponder_message">{AUTORESPONDER_MESSAGE}</textarea>

	<div class="buttons">
		<input type="submit" name="submit" value="{TR_ACTION}"/>
		<a class="link_as_button" href="mail_accounts.php">{TR_CANCEL}</a>
		<input type="hidden" name="mail_account_id" value="{MAIL_ACCOUNT_ID}"/>
	</div>
</form>
