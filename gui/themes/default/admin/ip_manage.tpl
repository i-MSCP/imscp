<!-- INCLUDE "../shared/layout/header.tpl" -->
<body>
<script type="text/javascript">
/*<![CDATA[*/
	$(document).ready(function() {
		errFieldsStack = {ERR_FIELDS_STACK};
		$.each(errFieldsStack, function(){$("#"+this).css('border-color', '#ca1d11');});
		$('.frm-button').button();
		$('.datatable').dataTable();

		$('table a').replaceWith(function(){
			var href = $(this).attr('href');
			if(href == "#") {
				return '<span class="icon i_error">' + $(this).text() + "</span>";
			}
			return '<a class="icon i_delete" href="' + href + '" onclick="' +  $(this).attr('onclick')
				+  '">' + $(this).text() + "</a>";
		});
	});

	// Delete the given ip address
	function confirm_deletion(ip_address) {
		return confirm(sprintf({TR_MESSAGE_DELETE}, ip_address));
	}
/*]]>*/
</script>
<div class="header">
	{MAIN_MENU}
	<div class="logo">
		<img src="{ISP_LOGO}" alt="i-MSCP logo"/>
	</div>
</div>

<div class="location">
	<div class="location-area">
		<h1 class="settings">{TR_MENU_SETTINGS}</h1>
	</div>
	<ul class="location-menu">
		<!-- <li><a class="help" href="#">Help</a></li> -->
		<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a>
		</li>
	</ul>
	<ul class="path">
		<li><a href="settings.php">{TR_GENERAL_SETTINGS}</a></li>
		<li><a href="#" onclick="return false;">{MANAGE_IPS}</a></li>
	</ul>
</div>

<div class="left_menu">
	{MENU}
</div>
<div class="body">
	<h2 class="ip"><span>{MANAGE_IPS}</span></h2>

	<!-- BDP: page_message -->
	<div class="{MESSAGE_CLS}">{MESSAGE}</div>
	<!-- EDP: page_message -->

	<!-- BDP: ip_addresses_block -->
	<h3 class="ip"><span>{TR_CONFIGURED_IPS}</span></h3>

	<table class="datatable">
		<thead>
			<tr>
				<th>{TR_IP}</th>
				<th>{TR_DOMAIN}</th>
				<th>{TR_ALIAS}</th>
				<th>{TR_NETWORK_CARD}</th>
				<th>{TR_ACTION}</th>
			</tr>
		</thead>
		<tbody>
		<!-- BDP: ip_address_block -->
			<tr>
				<td>{IP}</td>
				<td>{DOMAIN}</td>
				<td>{ALIAS}</td>
				<td>{NETWORK_CARD}</td>
				<td>
					<a class="icon i_delete" href="{ACTION_URL}" onclick="return confirm_deletion('{IP}')"  title="{ACTION_NAME}" class="{STATUS}">{ACTION_NAME}</a>
				</td>
			</tr>
		<!-- EDP: ip_address_block -->
		</tbody>
	</table>
	<!-- EDP: ip_addresses_block -->

	<!-- BDP: ip_address_form_block -->
	<h3 class="ip"><span>{TR_ADD_NEW_IP}</span></h3>

	<form name="addIpFrm" method="post" action="ip_manage.php">
		<table>
			<tr>
				<th colspan="2">{TR_IP_DATA}</th>
			</tr>

			<tr>
				<td><label for="ip_number">{TR_IP}</label></td>
				<td>
					<input name="ip_number" id="ip_number" type="text" value="{VALUE_IP}" maxlength="39" />
				</td>
			</tr>
			<tr>
				<td><label for="domain">{TR_DOMAIN}</label></td>
				<td>
					<input type="text" name="domain" id="domain" value="{VALUE_DOMAIN}" />
				</td>
			</tr>
			<tr>
				<td><label for="alias">{TR_ALIAS}</label></td>
				<td>
					<input type="text" name="alias" id="alias" value="{VALUE_ALIAS}" />
				</td>
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
		</table>
		<div class="buttons">
			<button name="submit" type="submit" class="frm-button">{TR_ADD}</button>
			<button name="cancel" type="button" class="frm-button" onclick="location.href='settings.php'">{TR_CANCEL}</button>

		</div>
	</form>
	<!-- EDP: ip_address_form_block -->
</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
