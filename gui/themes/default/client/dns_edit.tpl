<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_EDIT_DNS_PAGE_TITLE}</title>
		<meta name="robots" content="nofollow, noindex" />
		<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
		<script type="text/javascript" src="{THEME_COLOR_PATH}/js/ispcp.js"></script>
		<!--[if IE 6]>
		<script type="text/javascript" src="{THEME_COLOR_PATH}/js/DD_belatedPNG_0.0.8a-min.js"></script>
		<script type="text/javascript">
			DD_belatedPNG.fix('*');
		</script>
		<![endif]-->
		<script type="text/javascript">
		/* <![CDATA[ */
		function action_delete(url, subject) {
			if (!confirm(sprintf("{TR_MESSAGE_DELETE}", subject)))
				return false;
			location = url;
		}
		/* ]]> */
		</script>
		<script type="text/javascript">
		/* <![CDATA[ */

			function in_array(needle, haystack) {
				var n = haystack.length;
				for (var i = 0; i < n; i++) {
					if (haystack[i] == needle) return true;
				}
				return false;
			}

			function dns_show_rows(arr_show) {
				var arr_possible = new Array('name', 'ip_address', 'ip_address_v6',
					'srv_name', 'srv_protocol', 'srv_ttl', 'srv_prio',
					'srv_weight', 'srv_host', 'srv_port', 'cname');
				var n = arr_possible.length;
				var trname;
				for (var i = 0; i < n; i++) {
					trname = 'tr_dns_'+arr_possible[i];
					o = document.getElementById(trname);
					if (o) {
						if (in_array(arr_possible[i], arr_show)) {
							o.style.display = 'table-row';
						} else {
							o.style.display = 'none';
						}
					} else {
						alert('Not found: '+trname);
					}
				}
			}

			function dns_type_changed(value) {
				if (value == 'A') {
					dns_show_rows(new Array('name', 'ip_address'));
				} else if (value == 'AAAA') {
					dns_show_rows(new Array('name', 'ip_address_v6'));
				} else if (value == 'SRV') {
					dns_show_rows(new Array('srv_name', 'srv_protocol', 'srv_ttl',
						'srv_prio', 'srv_weight', 'srv_host', 'srv_port'));
				} else if (value == 'CNAME') {
					dns_show_rows(new Array('name', 'cname'));
				} else if (value == 'MX') {
					dns_show_rows(new Array('srv_prio', 'srv_host'));
				}
			}

			var IPADDRESS = "[0-9\.]";
			var IPv6ADDRESS = "[0-9a-f:A-F]";
			var NUMBERS = "[0-9]";

			function filterChars(e, allowed){
				var keynum;
				if (window.event){
					keynum = window.event.keyCode;
					e = window.event;
				} else if (e) {
					keynum = e.which;
				} else {
					return true;
				}

				if ((keynum == 8) || (keynum == 0)) {
					return true;
				}
				var keychar = String.fromCharCode(keynum);

				if (e.ctrlKey && ((keychar=="C") || (keychar=="c") || (keychar=="V") || (keychar=="v"))) {
					return true;
				}
				var re = new RegExp(allowed);
				return re.test(keychar);
			}

		/* ]]> */
		</script>
	</head>
	<body>
		<div class="header">
			{MAIN_MENU}

			<div class="logo">
				<img src="{THEME_COLOR_PATH}/images/ispcp_logo.png" alt="IspCP logo" />
				<img src="{THEME_COLOR_PATH}/images/ispcp_webhosting.png" alt="IspCP omega" />
			</div>
		</div>

		<div class="location">
			<div class="location-area icons-left">
				<h1 class="domains">{TR_MENU_MANAGE_DOMAINS}</h1>
			</div>
			<ul class="location-menu">
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="domains_manage.php">{TR_MENU_MANAGE_DOMAINS}</a></li>
				<li>{TR_EDIT_DNS}</li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<!-- BDP: page_message -->
				<div class="warning">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<h2 class="domains"><span>{TR_MANAGE_DOMAIN_DNS}</span></h2>
			<form name="edit_alias_frm" method="post" action="{ACTION_MODE}">
				<fieldset>
					<legend>{TR_EDIT_DNS}</legend>
					<table>
						<!-- BDP: add_record -->
							<tr>
								<td>{TR_DOMAIN}</td>
								<td><select name="alias_id">{SELECT_ALIAS}</select></td>
							</tr>
						<!-- EDP: add_record -->
					<tr>
						<td>{TR_DNS_TYPE}</td>
						<td><select id="dns_type" onchange="dns_type_changed(this.value)" name="type">{SELECT_DNS_TYPE}</select></td>
					</tr>
					<tr>
						<td>{TR_DNS_CLASS}</td>
						<td><select name="class">{SELECT_DNS_CLASS}</select></td>
					</tr>
					<tr id="tr_dns_name">
						<td>{TR_DNS_NAME}</td>
						<td><input type="text" name="dns_name" value="{DNS_NAME}" /></td>
					</tr>
					<tr id="tr_dns_srv_name">
						<td>{TR_DNS_SRV_NAME}</td>
						<td><input type="text" name="dns_srv_name" value="{DNS_SRV_NAME}" /></td>
					</tr>
					<tr id="tr_dns_ip_address">
						<td>{TR_DNS_IP_ADDRESS}</td>
						<td><input type="text" onkeypress="return filterChars(event, IPADDRESS);" name="dns_A_address" value="{DNS_ADDRESS}" /></td>
					</tr>
					<tr id="tr_dns_ip_address_v6">
						<td>{TR_DNS_IP_ADDRESS_V6}</td>
						<td><input type="text" onkeypress="return filterChars(event, IPv6ADDRESS);" name="dns_AAAA_address" value="{DNS_ADDRESS_V6}" /></td>
					</tr>
					<tr id="tr_dns_srv_protocol">
						<td>{TR_DNS_SRV_PROTOCOL}</td>
						<td><select name="srv_proto" id="srv_protocol">{SELECT_DNS_SRV_PROTOCOL}</select></td>
					</tr>
					<tr id="tr_dns_srv_ttl">
						<td>{TR_DNS_SRV_TTL}</td>
						<td><input type="text" onkeypress="return filterChars(event, NUMBERS);" name="dns_srv_ttl" value="{DNS_SRV_TTL}" /></td>
					</tr>
					<tr id="tr_dns_srv_prio">
						<td>{TR_DNS_SRV_PRIO}</td>
						<td><input type="text" onkeypress="return filterChars(event, NUMBERS);" name="dns_srv_prio" value="{DNS_SRV_PRIO}" /></td>
					</tr>
					<tr id="tr_dns_srv_weight">
						<td>{TR_DNS_SRV_WEIGHT}</td>
						<td><input type="text" onkeypress="return filterChars(event, NUMBERS);"name="dns_srv_weight" value="{DNS_SRV_WEIGHT}" /></td>
					</tr>
					<tr id="tr_dns_srv_host">
						<td>{TR_DNS_SRV_HOST}</td>
						<td><input type="text" name="dns_srv_host" value="{DNS_SRV_HOST}" /></td>
					</tr>
					<tr id="tr_dns_srv_port">
						<td>{TR_DNS_SRV_PORT}</td>
						<td><input type="text" onkeypress="return filterChars(event, NUMBERS);" name="dns_srv_port" value="{DNS_SRV_PORT}" /></td>
					</tr>
					<tr id="tr_dns_cname">
						<td>{TR_DNS_CNAME}</td>
						<td><input type="text" name="dns_cname" value="{DNS_CNAME}" />.</td>
					</tr>
				</table>

				<div class="buttons">
					<!-- BDP: form_edit_mode -->
						<input name="Submit" type="submit" value="{TR_MODIFY}" />
						<input type="hidden" name="uaction" value="modify" />
					<!-- EDP: form_edit_mode -->
					<!-- BDP: form_add_mode -->
						<input name="Submit" type="submit" value="{TR_ADD}" />
						<input type="hidden" name="uaction" value="add" />
					<!-- EDP: form_add_mode -->
					<input name="Submit" type="submit" onclick="MM_goToURL('parent','domains_manage.php');return document.MM_returnValue" value="{TR_CANCEL}" />
				</div>
				</fieldset>
			</form>
		</div>

		<div class="footer">
			ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
		</div>
		<script type="text/javascript">
		/* <![CDATA[ */

			dns_type_changed(document.getElementById('dns_type').value);

		/* ]]> */
		</script>
	</body>
</html>
