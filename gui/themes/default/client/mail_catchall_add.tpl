
<script type="text/javascript">
	/* <![CDATA[ */
	$(document).ready(function () {
		$("#normal, #forward").on('change', function() {
			if($(this).val() == 'normal' && $(this).is(':checked')) {
				$("#mail_id").prop('disabled', false);
				$("#forward_list").prop('disabled', true).val('');
			} else if($(this).val() == 'forward' && $(this).is(':checked')) {
				$("#mail_id").prop('disabled', true);
				$("#forward_list").prop('disabled', false);
			}
		}).trigger('change');
	});
	/* ]]> */
</script>

<form name="create_catchall_frm" method="post" action="mail_catchall_add.php">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_CATCHALL}</th>
		</tr>
		</thead>
		<tbody>
		<!-- BDP: mail_list -->
		<tr>
			<td>
				<input type="radio" name="mail_type" id="normal" value="normal"{NORMAL_MAIL_CHECKED}/>
				<label for="normal">{TR_MAIL_LIST}</label>
			</td>
			<td>
				<label>
					<select name="mail_id" id="mail_id">
						<!-- BDP: mail_item -->
						<option value="{MAIL_ID};{MAIL_ACCOUNT_PUNNY};">{MAIL_ACCOUNT}</option>
						<!-- EDP: mail_item -->
					</select>
				</label>
			</td>
		</tr>
		<!-- EDP: mail_list -->
		<tr>
			<td>
				<input type="radio" name="mail_type" id="forward" value="forward"{FORWARD_MAIL_CHECKED}/>
				<label for="forward">{TR_FORWARD_MAIL}</label>
				<span class="tips icon i_help" title="{TR_FWD_HELP}"></span>
			</td>
			<td><label><textarea name="forward_list" id="forward_list">{FORWARD_LIST_VAL}</textarea></label></td>
		</tr>
		</tbody>
	</table>

	<div class="buttons">
		<input type="hidden" name="uaction" value="create_catchall"/>
		<input type="hidden" name="id" value="{ID}"/>
		<input name="Submit" type="submit" value="{TR_ADD}"/>
		<a href="mail_catchall.php" class="link_as_button">{TR_CANCEL}</a>
	</div>
</form>
