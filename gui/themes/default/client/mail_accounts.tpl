
	<script type="text/javascript">
	/* <![CDATA[ */

		function action_delete(url, subject) {
			if (!confirm(sprintf("{TR_MESSAGE_DELETE}", subject)))
				return false;
			location = url;
		}
	/* ]]> */
	</script>
		<!-- BDP: mail_message -->
		<div class="info">{MAIL_MSG}</div>
		<!-- EDP: mail_message -->

		<table>
			<thead>
				<tr>
					<th>{TR_MAIL}</th>
					<th>{TR_TYPE}</th>
					<th>{TR_STATUS}</th>
					<th>{TR_ACTION}</th>
				</tr>
			</thead>
			<tbody>
				<!-- BDP: mail_item -->
				<tr>
					<td style="width: 300px;">
						<span class="icon i_mail_icon">{MAIL_ACC}</span>
						<!-- BDP: auto_respond -->
						<div style="display: {AUTO_RESPOND_VIS};">
							<br />
					  		{TR_AUTORESPOND}:
					  		[
					  			<a href="{AUTO_RESPOND_DISABLE_SCRIPT}" class="icon i_reload">{AUTO_RESPOND_DISABLE}</a>
					  			<a href="{AUTO_RESPOND_EDIT_SCRIPT}" class="icon i_edit">{AUTO_RESPOND_EDIT}</a>
					  		]
						</div>
						<!-- EDP: auto_respond -->
					</td>
					<td>{MAIL_TYPE}</td>
					<td>{MAIL_STATUS}</td>
					<td>
						<a href="{MAIL_EDIT_SCRIPT}" title="{MAIL_EDIT}" class="icon i_edit">{MAIL_EDIT}</a>
						<a href="#" onclick="action_delete('{MAIL_DELETE_SCRIPT}', '{MAIL_ACC}')" title="{MAIL_DELETE}" class="icon i_delete">{MAIL_DELETE}</a>
					</td>
				</tr>
				<!-- EDP: mail_item -->
			</tbody>
			<!-- BDP: mails_total -->
			<tfoot>
				<tr>
					<td colspan="4">{TR_TOTAL_MAIL_ACCOUNTS}: <strong>{TOTAL_MAIL_ACCOUNTS}</strong>/{ALLOWED_MAIL_ACCOUNTS}</td>
				</tr>
			</tfoot>
			<!-- EDP: mails_total -->
		</table>
		<!-- BDP: default_mails_form -->
		<form action="mail_accounts.php" method="post" id="showdefault">
			<div class="buttons">
				<input type="hidden" name="uaction" value="{VL_DEFAULT_EMAILS_BUTTON}" />
				<input type="submit" name="Submit" value="{TR_DEFAULT_EMAILS_BUTTON}" />
			</div>
		</form>
		<!-- EDP: default_mails_form -->
