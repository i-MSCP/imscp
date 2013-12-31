
<script type="text/javascript">
	/*<![CDATA[*/
	$(document).ready(function () {
		errFieldsStack = {ERR_FIELDS_STACK};
		$.each(errFieldsStack, function () {
			$("#" + this).css('border-color', '#ca1d11');
		});
		$('.datatable').dataTable(
				{
					"oLanguage": {DATATABLE_TRANSLATIONS},
					"bStateSave": true
				}
		);

		$('table a').replaceWith(function () {
			var href = $(this).attr('href');
			if (href == "#") {
				return '<span class="icon i_error">' + $(this).text() + "</span>";
			}
			return '<a class="icon i_delete" href="' + href + '" onclick="' + $(this).attr('onclick') + '">' + $(this).text() + "</a>";
		});
	});

	// Delete the given ip address
	function confirm_deletion(ip_address) {
		return confirm(sprintf({TR_MESSAGE_DELETE}, ip_address));
	}
	/*]]>*/
</script>

<p class="hint" style="font-variant: small-caps;font-size: small;">{TR_TIP}</p>
<br />

<!-- BDP: ip_addresses_block -->
<h3 class="ip"><span>{TR_CONFIGURED_IPS}</span></h3>
<table class="datatable">
	<thead>
	<tr>
		<th>{TR_IP}</th>
		<th>{TR_NETWORK_CARD}</th>
		<th>{TR_ACTION}</th>
	</tr>
	</thead>
	<tbody>
	<!-- BDP: ip_address_block -->
	<tr>
		<td>{IP}</td>
		<td>{NETWORK_CARD}</td>
		<td>
			<a class="icon i_delete" href="{ACTION_URL}" onclick="return confirm_deletion('{IP}')" title="{ACTION_NAME}"
			   class="{STATUS}">{ACTION_NAME}</a>
		</td>
	</tr>
	<!-- EDP: ip_address_block -->
	</tbody>
</table>
<!-- EDP: ip_addresses_block -->

<!-- BDP: ip_address_form_block -->
<h3 class="ip"><span>{TR_ADD_NEW_IP}</span></h3>

<form name="addIpFrm" method="post" action="ip_manage.php">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_IP_DATA}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td><label for="ip_number">{TR_IP}</label></td>
			<td><input name="ip_number" id="ip_number" type="text" value="{VALUE_IP}" maxlength="39"/></td>
		</tr>
		<tr>
			<td><label for="ip_card">{TR_NETWORK_CARD}</label></td>
			<td>
				<select name="ip_card" id="ip_card">
					<!-- BDP: network_card_block -->
					<option {SELECTED}>{NETWORK_CARD}</option>
					<!-- EDP: network_card_block -->
				</select>
			</td>
		</tr>
		</tbody>
	</table>
	<div class="buttons">
		<button name="submit" type="submit">{TR_ADD}</button>
		<a class="link_as_button" href="settings.php">{TR_CANCEL}</a>
	</div>
</form>
<!-- EDP: ip_address_form_block -->
