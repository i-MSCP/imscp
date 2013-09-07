
<form name="cert_edit" method="post" action="cert_view.php?id={ID}&type={TYPE}">
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
			<td><label for="pass">{TR_PASSWORD}</label></td>
			<td><input id="pass" type="password" name="pass" value="" autocomplete="off"/></td>
		</tr>
		<tr>
			<td><label for="pass_rep">{TR_PASSWORD_REPEAT}</label></td>
			<td><input id="pass_rep" type="password" name="pass_rep" value="" autocomplete="off"/></td>
		</tr>
		<tr>
			<td><label for="key_cert">{TR_CERTIFICATE_KEY}</label></td>
			<td><textarea name="key_cert" id="key_cert">{KEY_CERT}</textarea></td>
		</tr>
		<tr>
			<td><label for="cert_cert">{TR_CERTIFICATE}</label></td>
			<td><textarea name="cert_cert" id="cert_cert">{CERT}</textarea></td>
		</tr>
		<tr>
			<td><label for="ca_cert">{TR_INTERM_CERTIFICATE}</label></td>
			<td><textarea name="ca_cert" id="ca_cert">{CA_CERT}</textarea></td>
		</tr>
		</tbody>
	</table>

	<div class="buttons">
		<!-- BDP: cert_enable -->
		<input name="send" type="submit" value="{TR_SAVE}"/>
		<!-- EDP: cert_enable -->
		<input name="delete" type="submit" value="{TR_DELETE}"/>
		<a class ="link_as_button" href="domains_manage.php">{TR_CANCEL}</a>
	</div>
</form>
