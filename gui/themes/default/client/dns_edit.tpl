
	<script type="text/javascript">
	/* <![CDATA[ */
		function action_delete(url, subject) {
			if (!confirm(sprintf("{TR_MESSAGE_DELETE}", subject))) {
				return false;
			}

			location = url;
		}

		function in_array(needle, haystack) {
			var n = haystack.length;

			for (var i = 0; i < n; i++) {
				if (haystack[i] == needle) return true;
			}

			return false;
		}

		function dns_show_rows(arr_show) {
			var arr_possible = new Array(
				'name', 'ip_address', 'ip_address_v6', 'srv_name', 'srv_protocol',
				'srv_ttl', 'srv_prio', 'srv_weight', 'srv_host', 'srv_port', 'cname', 'txt_data'
			);

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
				dns_show_rows(new Array(
					'srv_name', 'srv_protocol', 'srv_ttl', 'srv_prio', 'srv_weight',
					'srv_host', 'srv_port'));
			} else if (value == 'CNAME') {
				dns_show_rows(new Array('name', 'cname'));
			} else if (value == 'MX') {
				dns_show_rows(new Array('srv_prio', 'srv_host'));
			} else if (value == 'TXT') {
				dns_show_rows(new Array('txt_data'));
			}
		}

		var IPADDRESS = "[0-9\.]";
		var IPv6ADDRESS = "[0-9a-f:A-F]";
		var NUMBERS = "[0-9]";

		function filterChars(e, allowed) {
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
		<form name="edit_dns_frm" method="post" action="{ACTION_MODE}">
			<table>
				<tr>
					<th colspan="2">
					{TR_CUSTOM_DNS_RECORD_DATA}
					</th>
				</tr>
				<!-- BDP: add_record -->
				<tr>
					<td style="width: 300px;">{TR_DOMAIN}</td>
					<td><select name="alias_id">{SELECT_ALIAS}</select></td>
				</tr>
				<!-- EDP: add_record -->
				<tr>
					<td>{TR_DNS_TYPE}</td>
					<td>
						<select id="dns_type" onchange="dns_type_changed(this.value)" name="type">{SELECT_DNS_TYPE}</select>
					</td>
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
					<td>
						<input type="text" name="dns_srv_name" value="{DNS_SRV_NAME}" />
					</td>
				</tr>
				<tr id="tr_dns_ip_address">
					<td>{TR_DNS_IP_ADDRESS}</td>
					<td>
						<input type="text" onkeypress="return filterChars(event, IPADDRESS);" name="dns_A_address" value="{DNS_ADDRESS}" />
					</td>
				</tr>
				<tr id="tr_dns_ip_address_v6">
					<td>{TR_DNS_IP_ADDRESS_V6}</td>
					<td>
						<input type="text" onkeypress="return filterChars(event, IPv6ADDRESS);" name="dns_AAAA_address" value="{DNS_ADDRESS_V6}" />
					</td>
				</tr>
				<tr id="tr_dns_srv_protocol">
					<td>{TR_DNS_SRV_PROTOCOL}</td>
					<td>
						<select name="srv_proto" id="srv_protocol">{SELECT_DNS_SRV_PROTOCOL}</select>
					</td>
				</tr>
				<tr id="tr_dns_srv_ttl">
					<td>{TR_DNS_SRV_TTL}</td>
					<td>
						<input type="text" onkeypress="return filterChars(event, NUMBERS);" name="dns_srv_ttl" value="{DNS_SRV_TTL}" />
					</td>
				</tr>
				<tr id="tr_dns_srv_prio">
					<td>{TR_DNS_SRV_PRIO}</td>
					<td>
						<input type="text" onkeypress="return filterChars(event, NUMBERS);" name="dns_srv_prio" value="{DNS_SRV_PRIO}" />
					</td>
				</tr>
				<tr id="tr_dns_srv_weight">
					<td>{TR_DNS_SRV_WEIGHT}</td>
					<td>
						<input type="text" onkeypress="return filterChars(event, NUMBERS);" name="dns_srv_weight" value="{DNS_SRV_WEIGHT}" />
					</td>
				</tr>
				<tr id="tr_dns_srv_host">
					<td>{TR_DNS_SRV_HOST}</td>
					<td>
						<input type="text" name="dns_srv_host" value="{DNS_SRV_HOST}" />
					</td>
				</tr>
				<tr id="tr_dns_srv_port">
					<td>{TR_DNS_SRV_PORT}</td>
					<td>
						<input type="text" onkeypress="return filterChars(event, NUMBERS);" name="dns_srv_port" value="{DNS_SRV_PORT}" />
					</td>
				</tr>
				<tr id="tr_dns_cname">
					<td>{TR_DNS_CNAME}</td>
					<td><input type="text" name="dns_cname" value="{DNS_CNAME}" /></td>
				</tr>
				<tr id="tr_dns_txt_data">
					<td>{TR_DNS_TXT_DATA}</td>
					<td><input type="text" name="dns_txt_data" value="{DNS_TXT_DATA}" /></td>
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
		</form>

	<script type="text/javascript">
		/* <![CDATA[ */
		dns_type_changed(document.getElementById('dns_type').value);
		/* ]]> */
	</script>
