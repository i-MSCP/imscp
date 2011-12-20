
		<div class="body">
			<h2 class="support"><span>{TR_ENABLE_MAIL_AUTORESPONDER}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<form name="manage_users_common_frm" method="post" action="">
				<fieldset>
					<legend>{TR_ARSP_MESSAGE}</legend>
					<textarea name="arsp_message" cols="50" rows="15"></textarea>
				</fieldset>

				<div class="buttons">
					<input name="Submit" type="submit" value="{TR_ENABLE}" />
					<input type="button" name="Submit2" value="{TR_CANCEL}" onclick="location = 'mail_accounts.php'" />
				</div>
				<input type="hidden" name="uaction" value="enable_arsp" />
				<input type="hidden" name="id" value="{ID}" />
			</form>

		</div>
