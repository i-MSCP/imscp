
<form name="cert_edit" method="post" action="cert_view.php?domain_id={ID}&domain_type={TYPE}">
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
		<tr>
			<td>{TR_STATUS}</td>
			<td>{STATUS}</td>
		</tr>
		<tr>
			<td><label for="passphrase">{TR_PASSWORD}</label></td>
			<td><input id="passphrase" type="password" name="passphrase" value="" autocomplete="off"/></td>
		</tr>
		<tr>
			<td><label for="orivate_key">{TR_PRIVATE_KEY}</label></td>
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

    <!-- BDP: cert_enable -->
	<div class="buttons">
		<input name="send" type="submit" value="{TR_SAVE}"/>
		<input name="delete" type="submit" value="{TR_DELETE}"/>
		<a class="link_as_button" href="domains_manage.php">{TR_CANCEL}</a>
	</div>
    <!-- EDP: cert_enable -->
</form>
