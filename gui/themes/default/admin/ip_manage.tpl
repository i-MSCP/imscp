<!-- INCLUDE "../shared/layout/header.tpl" -->
	<body>
		<script type="text/javascript">
			/* <![CDATA[ */
			$(document).ready(function() {
				errFieldsStack = {ERR_FIELDS_STACK};
				$.each(errFieldsStack, function(){$('#'+this).css('border-color', '#ca1d11');});
			});

			function action_delete(url, subject) {
				if(url == '#') {
					alert(sprintf('{TR_MESSAGE_DENY_DELETE}', subject));
					return false;
				} else if(!confirm(sprintf('{TR_MESSAGE_DELETE}', subject))) {
					return false;
				}

				location = url;
			}
			/* ]]> */
		</script>
		<div class="header">
			{MAIN_MENU}
			<div class="logo">
				<img src="{ISP_LOGO}" alt="i-MSCP logo" />
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

			<!-- BDP: ips_list -->
			<h3>{TR_REGISTERED_IPS}</h3>

			<table>
				<tr align="center">
					<th style="width:300px;">{TR_IP}</th>
					<th>{TR_DOMAIN}</th>
					<th>{TR_ALIAS}</th>
					<th>{TR_NETWORK_CARD}</th>
					<th>{TR_ACTION}</th>
				</tr>

				<!-- BDP: ip_row -->
				<tr>
					<td>{IP}</td>
					<td>{DOMAIN}</td>
					<td>{ALIAS}</td>
					<td>{NETWORK_CARD}</td>
					<td>
						<a href="#"
							onclick="action_delete('{IP_ACTION_SCRIPT}', '{IP}'); return false;"
							title="{IP_ACTION}" class="icon i_delete">{IP_ACTION}</a>
					</td>
				</tr>
				<!-- EDP: ip_row -->
			</table>
			<!-- EDP: ips_list -->

			<!-- BDP: add_ip -->
			<h3>{TR_ADD_NEW_IP}</h3>

			<form name="addNewIpFrm" method="post" action="ip_manage.php">
				<table>
					<tr>
						<th colspan="2">{TR_IP_DATA}</th>
					</tr>
					<tr>
						<td style="width:300px;"><label for="ip_number">{TR_IP}</label></td>
						<td>
							<input name="ip_number" id="ip_number" type="text" value="{VALUE_IP}" maxlength="39"/>
						</td>
					</tr>
					<tr>
						<td><label for="domain">{TR_DOMAIN}</label></td>
						<td>
							<input type="text" name="domain" id="domain" value="{VALUE_DOMAIN}"/>
						</td>
					</tr>
					<tr>
						<td><label for="alias">{TR_ALIAS}</label></td>
						<td>
							<input type="text" name="alias" id="alias" value="{VALUE_ALIAS}"/>
						</td>
					</tr>
					<tr>
						<td><label for="ip_card">{TR_NETWORK_CARD}</label></td>
						<td>
							<select name="ip_card" id="ip_card">
								<!-- BDP: cards_list -->
								<option {SELECTED}>{NETWORK_CARD}</option>
								<!-- EDP: cards_list -->
							</select>
						</td>
					</tr>
				</table>

				<div class="buttons">
					<input name="Submit" type="submit" value="{TR_ADD}"/>
					<input type="hidden" name="uaction" value="addIpAddress"/>
				</div>
			</form>
			<!-- EDP: add_ip -->
		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
