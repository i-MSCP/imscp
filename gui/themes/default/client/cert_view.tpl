
<form name="ssl_cert_frm" method="post" action="cert_view.php?domain_id={DOMAIN_ID}&domain_type={DOMAIN_TYPE}">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_CERTIFICATE_DATA}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td>{TR_CERT_FOR}</td>
			<td>{DOMAIN_NAME}</td>
		</tr>
		<!-- BDP: ssl_certificate_status -->
		<tr>
			<td>{TR_STATUS}</td>
			<td>{STATUS}</td>
		</tr>
		<!-- EDP: ssl_certificate_status -->
		<tr>
			<td><label for="allow_hsts">{TR_ALLOW_HSTS}</label></td>
			<td><input type="checkbox" id="allow_hsts" name="allow_hsts"{HSTS_CHECKED}></td>
		</tr>
		<tr>
			<td><label for="selfsigned">{TR_GENERATE_SELFSIGNED_CERTIFICAT}</label></td>
			<td><input type="checkbox" id="selfsigned" name="selfsigned"></td>
		</tr>
		</tbody>
		<tbody id="input_fields">
		<tr>
			<td><label for="passphrase">{TR_PASSWORD}</label></td>
			<td><input id="passphrase" type="password" name="passphrase" value="" autocomplete="off"/></td>
		</tr>
		<tr>
			<td><label for="private_key">{TR_PRIVATE_KEY}</label></td>
			<td><textarea name="private_key" id="private_key">{KEY_CERT}</textarea></td>
		</tr>
		<tr>
			<td><label for="certificate">{TR_CERTIFICATE}</label></td>
			<td><textarea name="certificate" id="certificate">{CERTIFICATE}</textarea></td>
		</tr>
		<tr>
			<td><label for="ca_bundle">{TR_CA_BUNDLE}</label></td>
			<td><textarea name="ca_bundle" id="ca_bundle">{CA_BUNDLE}</textarea></td>
		</tr>
		</tbody>
	</table>
	<div class="buttons">
		<!-- BDP: ssl_certificate_actions -->
		<input name="add_update" id="add_update" type="submit" value="{TR_ACTION}"/>
		<input name="delete" id="delete" type="submit" value="{TR_DELETE}"/>
		<input name="cert_id" type="hidden" value="{CERT_ID}"/>
		<!-- EDP: ssl_certificate_actions -->
		<a class="link_as_button" href="domains_manage.php">{TR_CANCEL}</a>
	</div>
</form>
<script>
	$(function() {
		if(!$("#add_update").length) {
			$("input,textarea").prop('disabled', true);
		}

		$("#selfsigned").change(function() {
			if($(this).is(':checked')) {
				$("#input_fields input,textarea");
				$("#input_fields").hide();
			} else {
				$("#input_fields").show();
			}
		});
	});
</script>
