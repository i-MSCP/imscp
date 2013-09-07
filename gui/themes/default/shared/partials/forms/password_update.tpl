
<script type="text/javascript">
	/*<![CDATA[*/
	$(document).ready(function () {
		$.ajaxSetup({
			url: $(location).attr('pathname'),
			type: 'GET',
			datatype: 'text',
			beforeSend: function (xhr){xhr.setRequestHeader('Accept','text/plain');},
			success: function (r) {
				$('#password, #password_confirmation').val(r);
			},
			error: iMSCPajxError
		});

		$('#generate_password').click(function () {
			$.ajax();
		});
		$('#reset_password').click(function () {
			$('#password, #password_confirmation').val('');
		});

		// Create dialog box for some messages (password and notices)
		$('#dialog_box').dialog({
			modal: true, autoOpen: false, hide: 'blind', show: 'blind',
			buttons: { Ok: function(){ $(this).dialog('close'); }}
		});

		// Show generated password in specific dialog box
		$('#show_password').click(function () {
			var password = $('#password').val();
			if (password == '') {
				password = '<br/>{TR_PASSWORD_GENERATION_NEEDED}';
			} else {
				password = '<br/>{TR_NEW_PASSWORD_IS}: <strong>' + $('#password').val() + '</strong>';
			}
			$('#dialog_box').dialog("option", "title", '{TR_PASSWORD}').html(password);
			$('#dialog_box').dialog('open');
		});
	});
	/*]]>*/
</script>
<div id="dialog_box"></div>
<form name="passwordUpdate" method="post" action="password_update.php">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="3">{TR_PASSWORD_DATA}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td><label for="current_password">{TR_CURRENT_PASSWORD}</label></td>
			<td colspan="2">
				<input id="current_password" name="current_password" type="password" value="" autocomplete="off"/>
			</td>
		</tr>
		<tr>
			<td><label for="password">{TR_PASSWORD}</label></td>
			<td><input name="password" id="password" type="password" value="" autocomplete="off"/></td>
			<td>
				<button type="button" id="generate_password">{TR_GENERATE}</button>
				<button type="button" id="show_password">{TR_SHOW}</button>
				<button type="button" id="reset_password">{TR_RESET}</button>
			</td>
		</tr>
		<tr>
			<td><label for="password_confirmation">{TR_PASSWORD_CONFIRMATION}</label></td>
			<td colspan="2">
				<input name="password_confirmation" id="password_confirmation" type="password" value=""
					   autocomplete="off"/>
			</td>
		</tr>
		</tbody>
	</table>
	<div class="buttons">
		<input type="submit" name="Submit" value="{TR_UPDATE}"/>
	</div>
</form>
