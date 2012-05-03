
	<script type="text/javascript">
	/* <![CDATA[ */
		$(document).ready(function(){
			if(!$('#forwardAccount').is(':checked') && $('#forwardList').val() == '') {
				$('#forwardList').attr('disabled', true);
			}

			$('#forwardAccount').change(function(){
				if($(this).is(':checked')) {
					$('#forwardList').removeAttr('disabled');
				} else {
					$('#forwardList').attr('disabled', true).val('');
				}
			});
		});
	/* ]]> */
	</script>
		<form name="editFrm" method="post" action="mail_edit.php?id={MAIL_ID_VAL}">
			<table class="firstColFixed">
				<tr>
					<th colspan="2">
						<span style="vertical-align: middle">{TR_MAIL_ACCOUNT} : {MAIL_ADDRESS_VAL}</span>
					</th>
				</tr>
				<!-- BDP: password_frm -->
				<tr>
					<td><label for="password">{TR_PASSWORD}</label></td>
					<td><input name="password" id="password" type="password" value=""/></td>
				</tr>
				<tr>
					<td><label for="passwordConfirmation">{TR_PASSWORD_CONFIRMATION}</label></td>
					<td>
						<input name="passwordConfirmation" id="passwordConfirmation" type="password" value=""/>
					</td>
				</tr>
				<tr>
					<td>
						<label for="forwardAccount">{TR_FORWARD_ACCOUNT}</label>
					</td>
					<td>
						<input name="forwardAccount" id="forwardAccount" type="checkbox"{FORWARD_ACCOUNT_CHECKED}/>
					</td>
				</tr>
				<!-- EDP: password_frm -->
				<tr>
					<td>
						<label for="forwardList">{TR_FORWARD_TO}</label><span style="vertical-align: middle;" class="icon i_help" id="fwd_help" title="{TR_FWD_HELP}">{TR_HELP}</span>
					</td>
					<td>
						<textarea name="forwardList" id="forwardList">{FORWARD_LIST_VAL}</textarea>
					</td>
				</tr>
			</table>
			<div class="buttons">
				<input name="submit" type="submit" value="{TR_UPDATE}"/>
				<input name="cancel" type="button" onclick="MM_goToURL('parent','mail_accounts.php');return document.MM_returnValue" value="{TR_CANCEL}"/>
			</div>
		</form>
