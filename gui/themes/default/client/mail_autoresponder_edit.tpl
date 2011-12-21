
		<form action="" method="post" id="client_mail_autoresponder_edit">
			<fieldset>
				<legend>{TR_ARSP_MESSAGE}</legend>
				<textarea name="arsp_message" cols="50" rows="15">{ARSP_MESSAGE}</textarea>
			</fieldset>
			<div class="buttons">
				<input type="hidden" name="id" value="{ID}" />
				<input type="hidden" name="uaction" value="enable_arsp" />
				<input type="submit" name="submit" value="{TR_ENABLE}" />
				<input type="button" name="Submit2" value="{TR_CANCEL}" onclick="location = 'mail_accounts.php'" />
			</div>
		</form>
