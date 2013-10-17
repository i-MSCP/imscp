

<script type="text/javascript">
	/* <![CDATA[ */
	$(document).ready(function () {
		$("#normal, #forward, #normal_forward").on('change', function() {
			if($(this).val() == '1') {
				if($(this).is(':checked')) {
					$("#tr_password, #tr_password_rep, #tr_quota").show();
					$("#tr_forward_list").hide();
				} else {
					$("#tr_password, #tr_password_rep, #tr_quota").hide();
				}
			} else if($(this).val() == '2') {
				if($(this).is(":checked")) {
					$("#tr_forward_list").show();
					$("#tr_password, #tr_password_rep, #tr_quota").hide();
				} else {
					$("#tr_forward_list").hide();
				}
			} else {
				if($(this).is(':checked')) {
					$("#tr_password, #tr_password_rep, #tr_quota").show();
					$("#tr_forward_list").show();
				}
			}
		}).trigger('change');
	});
	/* ]]> */
</script>

<form name="client_mail_edit" action="mail_edit.php?id={MAIL_ID}" method="post">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_MAIl_ACCOUNT_DATA}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td>{TR_MAIL_ACCOUNT_TYPE}</td>
			<td>
				<div class="radio">
					<input type="radio" name="account_type" id="normal" value="1"{NORMAL_CHECKED}/>
					<label for="normal">{TR_NORMAL_MAIL}</label>
					<input type="radio" name="account_type" id="forward" value="2"{FORWARD_CHECKED}/>
					<label for="forward">{TR_FORWARD_MAIL}</label>
					<input type="radio" name="account_type" id="normal_forward" value="3"{NORMAL_FORWARD_CHECKED}/>
					<label for="normal_forward">{TR_FORWARD_NORMAL_MAIL}</label>
				</div>
			</td>
		</tr>
		<tr>
			<td><label for="username">{TR_USERNAME}</label></td>
			<td><input type="text" name="username" id="username" value="{USERNAME}" disabled="disabled"/></td>
		</tr>
		<tr>
			<td><label for="domain_name">{TR_DOMAIN_NAME}</label></td>
			<td>
				<select name="domain_name" id="domain_name" disabled="disabled">
					<option value="{DOMAIN_NAME}"{DOMAIN_NAME_SELECTED}>{DOMAIN_NAME_UNICODE}</option>
				</select>
			</td>
		</tr>
		<tr id="tr_password">
			<td><label for="password">{TR_PASSWORD}</label></td>
			<td><input id="password" type="password" name="password" value="{PASSWORD}" autocomplete="off"/></td>
		</tr>
		<tr id="tr_password_rep">
			<td><label for="password_rep">{TR_PASSWORD_REPEAT}</label></td>
			<td>
				<input id="password_rep" type="password" name="password_rep" value="{PASSWORD_REP}" autocomplete="off"/>
			</td>
		</tr>
		<tr id="tr_quota">
			<td><label for="quota">{TR_QUOTA}</label></td>
			<td><input name="quota" id="quota" type="text" value="{QUOTA}"/></td>
		</tr>
		<tr id="tr_forward_list">
			<td>
				<label for="forward_list">{TR_FORWARD_TO}</label>
				<span class="tips icon i_help" id="fwd_help" title="{TR_FWD_HELP}"></span>
			</td>
			<td><textarea name="forward_list" id="forward_list">{FORWARD_LIST}</textarea></td>
		</tr>
		</tbody>
	</table>

	<div class="buttons">
		<input type="submit" name="submit" value="{TR_UPDATE}"/>
		<a href="mail_accounts.php" class="link_as_button">{TR_CANCEL}</a>
	</div>
</form>
